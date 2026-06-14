<?php
/**
 * CONTRÔLEUR Payment — espace de paiement (Stripe Checkout).
 *  - index   : page de paiement (don ponctuel + abonnements) + historique
 *  - checkout: crée la session Stripe et redirige vers la page de paiement
 *  - success / cancel : retours après paiement
 *  - webhook : reçoit les événements Stripe (confirmation de paiement)
 */
class PaymentController
{
    private function uid(): int
    {
        if (!Session::has('user')) {
            redirect('');
        }
        return (int) (Session::user()['id'] ?? 0);
    }

    /** Plans d'abonnement définis par l'admin (réglage JSON). */
    private function plans(): array
    {
        $raw = json_decode((string) Settings::get('stripe_plans', '[]'), true);
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $p) {
            $name = trim((string) ($p['name'] ?? ''));
            $amt  = (float) ($p['amount'] ?? 0);
            if ($name === '' || $amt <= 0) { continue; }
            $out[] = [
                'name'     => $name,
                'amount'   => $amt,
                'interval' => ($p['interval'] ?? 'month') === 'year' ? 'year' : 'month',
            ];
        }
        return $out;
    }

    /** URL absolue (scheme + host + chemin interne). */
    private function absUrl(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . url($path);
    }

    /** Page « Espace paiement ». */
    public function index(): void
    {
        $uid = $this->uid();
        view('payment/index', [
            'user'        => Session::user(),
            'configured'  => StripeClient::isConfigured(),
            'currency'    => strtoupper(StripeClient::currency()),
            'donationLabel'   => (string) Settings::get('stripe_donation_label', 'Soutenir la communauté'),
            'donationAmounts' => array_values(array_filter(array_map('intval', explode(',', (string) Settings::get('stripe_donation_amounts', '5,10,20,50'))))),
            'plans'       => $this->plans(),
            'payments'    => $uid > 0 ? Payment::forUser($uid) : [],
            'notice'      => Session::get('pay_notice'),
            'error'       => Session::get('pay_error'),
            'isAdmin'     => Session::isAdmin(),
        ]);
        Session::remove('pay_notice');
        Session::remove('pay_error');
    }

    /** Crée la session Stripe Checkout puis redirige vers Stripe. */
    public function checkout(): void
    {
        $uid = $this->uid();
        if ($uid <= 0) {
            Session::set('pay_error', 'Le paiement nécessite un compte membre.');
            redirect('paiement');
        }
        if (!StripeClient::isConfigured()) {
            Session::set('pay_error', 'Les paiements ne sont pas encore activés.');
            redirect('paiement');
        }

        $type = ($_POST['type'] ?? 'payment') === 'subscription' ? 'subscription' : 'payment';

        if ($type === 'subscription') {
            $plans = $this->plans();
            $i = (int) ($_POST['plan'] ?? -1);
            if (!isset($plans[$i])) {
                Session::set('pay_error', 'Abonnement introuvable.');
                redirect('paiement');
            }
            $plan     = $plans[$i];
            $cents    = (int) round($plan['amount'] * 100);
            $label    = $plan['name'];
            $interval = $plan['interval'];
        } else {
            $euros = (float) str_replace(',', '.', (string) ($_POST['amount'] ?? 0));
            if ($euros < 1) {
                Session::set('pay_error', 'Montant minimum : 1 €.');
                redirect('paiement');
            }
            $cents    = (int) round($euros * 100);
            $label    = (string) Settings::get('stripe_donation_label', 'Soutenir la communauté');
            $interval = 'month';
        }

        // Création de la session Stripe.
        $session = StripeClient::createCheckout(
            $type,
            $cents,
            $label,
            $uid,
            $this->absUrl('paiement/success') . '?session_id={CHECKOUT_SESSION_ID}',
            $this->absUrl('paiement/cancel')
        );
        if (!empty($session['error']) || empty($session['id']) || empty($session['url'])) {
            Session::set('pay_error', '⚠️ ' . ($session['error'] ?? 'Impossible de créer la session de paiement.'));
            redirect('paiement');
        }

        Payment::create($uid, $type, $cents, StripeClient::currency(), $label, (string) $session['id']);
        // Redirection vers la page de paiement Stripe (URL externe).
        header('Location: ' . $session['url']);
        exit;
    }

    /** Retour après paiement réussi. */
    public function success(): void
    {
        $this->uid();
        $sessionId = (string) ($_GET['session_id'] ?? '');
        $paid = false;
        if ($sessionId !== '' && StripeClient::isConfigured()) {
            // Confirmation immédiate (en plus du webhook) : on relit la session.
            $s = StripeClient::getSession($sessionId);
            $status = $s['payment_status'] ?? ($s['status'] ?? '');
            if ($status === 'paid' || $status === 'complete') {
                Payment::setStatusBySession($sessionId, 'paid', $s['customer'] ?? null);
                $paid = true;
            }
        }
        view('payment/result', [
            'user'   => Session::user(),
            'ok'     => true,
            'paid'   => $paid,
        ]);
    }

    /** Retour après annulation. */
    public function cancel(): void
    {
        $this->uid();
        view('payment/result', [
            'user' => Session::user(),
            'ok'   => false,
            'paid' => false,
        ]);
    }

    /**
     * Webhook Stripe : confirme les paiements de façon fiable (serveur à serveur).
     * À configurer dans Stripe avec l'URL …/paiement/webhook et le secret whsec_…
     */
    public function webhook(): void
    {
        $payload = (string) file_get_contents('php://input');
        $sig     = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
        $event   = StripeClient::verifyWebhook($payload, $sig);

        http_response_code(200); // on répond toujours 200 si traité/ignoré proprement
        if (!$event) {
            return;
        }
        $type = (string) ($event['type'] ?? '');
        $obj  = $event['data']['object'] ?? [];

        if ($type === 'checkout.session.completed') {
            $sessionId = (string) ($obj['id'] ?? '');
            if ($sessionId !== '') {
                Payment::setStatusBySession($sessionId, 'paid', $obj['customer'] ?? null);
            }
        } elseif ($type === 'checkout.session.expired') {
            $sessionId = (string) ($obj['id'] ?? '');
            if ($sessionId !== '') {
                Payment::setStatusBySession($sessionId, 'canceled');
            }
        }
        echo 'ok';
    }
}

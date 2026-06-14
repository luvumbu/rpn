<?php
/**
 * CLASSE StripeClient — intégration Stripe SANS dépendance (Composer), via cURL.
 * On utilise **Stripe Checkout** : aucune donnée de carte ne transite par le site,
 * le paiement se fait sur une page sécurisée hébergée par Stripe.
 *
 * Réglages (admin → Paramètres → Paiements) :
 *   - stripe_secret        : clé secrète (sk_...)
 *   - stripe_publishable   : clé publique (pk_...)  [non indispensable côté serveur]
 *   - stripe_webhook_secret: secret du webhook (whsec_...)
 *   - stripe_currency      : devise (eur par défaut)
 */
class StripeClient
{
    private const API = 'https://api.stripe.com/v1/';

    /** Clé secrète configurée (ou ''). */
    public static function secret(): string
    {
        return trim((string) Settings::get('stripe_secret', ''));
    }

    /** Stripe est-il configuré (clé secrète présente) ? */
    public static function isConfigured(): bool
    {
        return self::secret() !== '';
    }

    /** Devise (minuscule, 'eur' par défaut). */
    public static function currency(): string
    {
        $c = strtolower(trim((string) Settings::get('stripe_currency', 'eur')));
        return preg_match('/^[a-z]{3}$/', $c) ? $c : 'eur';
    }

    /**
     * Appel HTTP à l'API Stripe (form-encoded). Retourne le tableau décodé,
     * ou ['error' => message] en cas d'échec.
     */
    public static function request(string $method, string $path, array $params = []): array
    {
        $secret = self::secret();
        if ($secret === '') {
            return ['error' => 'Stripe non configuré (clé secrète manquante).'];
        }
        if (!function_exists('curl_init')) {
            return ['error' => "L'extension PHP « cURL » n'est pas activée sur le serveur."];
        }
        $url = self::API . ltrim($path, '/');
        $ch  = curl_init();
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $secret,
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ];
        if (strtoupper($method) === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
        } else {
            if ($params) { $url .= '?' . http_build_query($params); }
        }
        $opts[CURLOPT_URL] = $url;
        curl_setopt_array($ch, $opts);

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['error' => 'Connexion à Stripe impossible : ' . $err];
        }
        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            return ['error' => 'Réponse Stripe illisible.'];
        }
        if ($code >= 400) {
            return ['error' => $data['error']['message'] ?? ('Erreur Stripe (HTTP ' . $code . ').')];
        }
        return $data;
    }

    /**
     * Crée une session Stripe Checkout.
     *   $mode     : 'payment' (ponctuel) ou 'subscription' (récurrent)
     *   $amount   : montant en CENTIMES
     *   $label    : libellé produit affiché
     *   $userId   : id du membre (référence)
     *   $successUrl / $cancelUrl : URLs absolues de retour
     *   $interval : 'month' | 'year' (pour les abonnements)
     * Retourne la session (avec 'id' et 'url'), ou ['error' => …].
     */
    public static function createCheckout(string $mode, int $amount, string $label, int $userId, string $successUrl, string $cancelUrl, string $interval = 'month'): array
    {
        $mode = $mode === 'subscription' ? 'subscription' : 'payment';
        $priceData = [
            'currency'     => self::currency(),
            'unit_amount'  => max(50, $amount), // min 0,50
            'product_data' => ['name' => mb_substr($label, 0, 120) ?: 'Paiement'],
        ];
        if ($mode === 'subscription') {
            $priceData['recurring'] = ['interval' => $interval === 'year' ? 'year' : 'month'];
        }
        $params = [
            'mode'                 => $mode,
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
            'client_reference_id'  => (string) $userId,
            'line_items'           => [[
                'price_data' => $priceData,
                'quantity'   => 1,
            ]],
            'metadata'             => ['user_id' => (string) $userId, 'kind' => $mode],
        ];
        return self::request('POST', 'checkout/sessions', $params);
    }

    /** Récupère une session Checkout par son id. */
    public static function getSession(string $sessionId): array
    {
        return self::request('GET', 'checkout/sessions/' . rawurlencode($sessionId), []);
    }

    /**
     * Vérifie la signature d'un webhook Stripe (en-tête Stripe-Signature).
     * Retourne l'événement décodé si la signature est valide, sinon null.
     */
    public static function verifyWebhook(string $payload, string $sigHeader): ?array
    {
        $secret = trim((string) Settings::get('stripe_webhook_secret', ''));
        if ($secret === '' || $sigHeader === '') {
            return null;
        }
        $t = null; $sigs = [];
        foreach (explode(',', $sigHeader) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) !== 2) { continue; }
            if ($kv[0] === 't') { $t = $kv[1]; }
            if ($kv[0] === 'v1') { $sigs[] = $kv[1]; }
        }
        if ($t === null || !$sigs) {
            return null;
        }
        // Tolérance anti-rejeu : 5 minutes.
        if (abs(time() - (int) $t) > 300) {
            return null;
        }
        $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
        $ok = false;
        foreach ($sigs as $s) {
            if (hash_equals($expected, $s)) { $ok = true; break; }
        }
        if (!$ok) {
            return null;
        }
        $event = json_decode($payload, true);
        return is_array($event) ? $event : null;
    }
}

<?php
/**
 * CONTRÔLEUR MessageController — messagerie privée entre membres.
 */
class MessageController
{
    /** Id de l'utilisateur connecté (0 = super-admin, compte technique sans messagerie). */
    private function uid(): int
    {
        if (!Session::has('user')) {
            redirect('');
        }
        return (int) (Session::user()['id'] ?? 0);
    }

    /** Boîte de réception : liste des conversations. */
    public function index(): void
    {
        $uid = $this->uid();
        view('messages/index', [
            'user'         => Session::user(),
            'threads'      => $uid > 0 ? Message::threads($uid) : [],
            'adminBlocked' => $uid <= 0, // super-admin : pas de messagerie de membre
        ]);
    }

    /** Conversation avec un membre (?with=ID). */
    public function thread(): void
    {
        if ($this->uid() <= 0) {
            redirect('messages'); // super-admin → page d'info
        }
        $uid   = $this->uid();
        $with  = (int) ($_GET['with'] ?? 0);
        $other = $with > 0 ? User::findById($with) : null;
        if (!$other || $with === $uid) {
            redirect('messages');
        }
        Message::markRead($uid, $with);   // les messages reçus de cette personne deviennent lus
        view('messages/thread', [
            'user'     => Session::user(),
            'other'    => $other,
            'messages' => Message::conversation($uid, $with),
            'meId'     => $uid,
            'error'    => Session::get('msg_error'),
        ]);
        Session::remove('msg_error');
    }

    /** Sondage temps réel d'une conversation (JSON) : bulles + statut en ligne. */
    public function poll(): void
    {
        $uid   = $this->uid();
        $with  = (int) ($_GET['with'] ?? 0);
        $other = $with > 0 ? User::findById($with) : null;
        header('Content-Type: application/json; charset=utf-8');
        if ($uid <= 0 || !$other || $with === $uid) {
            echo json_encode(['ok' => false]);
            return;
        }
        Message::markRead($uid, $with);
        $msgs = Message::conversation($uid, $with);
        $html = '';
        foreach ($msgs as $m) {
            $mine = (int) $m['sender_id'] === $uid;
            $html .= '<div class="bubble ' . ($mine ? 'mine' : 'theirs') . '">'
                . message_bubble_body($m)
                . '<span class="when">' . date('d/m H\hi', strtotime($m['created_at'])) . '</span></div>';
        }
        echo json_encode([
            'ok'          => true,
            'count'       => count($msgs),
            'html'        => $html,
            'otherOnline' => User::isOnline($with),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** Envoi d'un message (POST) — texte et/ou pièce jointe (image ou document). */
    public function send(): void
    {
        $uid  = $this->uid();
        if ($uid <= 0) {
            redirect('messages');
        }
        $to   = (int) ($_POST['to'] ?? 0);
        $body = (string) ($_POST['body'] ?? '');
        $other = $to > 0 ? User::findById($to) : null;
        if (!$other || $to === $uid) {
            redirect('messages');
        }

        // Pièce jointe facultative : on accepte une image OU un document.
        $file = null; $fileName = null;
        try {
            $imgs = Upload::images('attachment', 'messages');
            if (!empty($imgs)) {
                $file     = $imgs[0];
                $fileName = is_array($_FILES['attachment']['name'] ?? null) ? (string) ($_FILES['attachment']['name'][0] ?? '') : '';
            } else {
                $docs = Upload::documents('attachment', 'messages');
                if (!empty($docs)) {
                    $file     = $docs[0]['filename'];
                    $fileName = (string) ($docs[0]['original'] ?? '');
                }
            }
        } catch (\RuntimeException $e) {
            Session::set('msg_error', '⚠️ Fichier refusé : ' . $e->getMessage());
            redirect('messages/thread?with=' . $to);
        }

        if (trim($body) === '' && $file === null) {
            Session::set('msg_error', 'Écris un message ou joins un fichier avant d\'envoyer.');
            redirect('messages/thread?with=' . $to);
        }
        Message::send($uid, $to, $body, $file, $fileName);
        // Notifie le destinataire.
        $me = Session::user();
        Notification::add($to, 'Nouveau message de ' . ($me['name'] ?? 'un membre'), '✉️', url('messages/thread?with=' . $uid));
        redirect('messages/thread?with=' . $to);
    }

    /**
     * Crée un salon de discussion (visio, type Zoom) via Jitsi Meet et poste le
     * lien dans la conversation. Aucun serveur vidéo requis : le salon vit sur
     * meet.jit.si (audio, vidéo, partage d'écran et de fichiers dans le chat).
     */
    public function meet(): void
    {
        $uid   = $this->uid();
        if ($uid <= 0) {
            redirect('messages');
        }
        $to    = (int) ($_POST['to'] ?? 0);
        $other = $to > 0 ? User::findById($to) : null;
        if (!$other || $to === $uid) {
            redirect('messages');
        }
        $room = 'RPN-' . bin2hex(random_bytes(6));
        $link = 'https://meet.jit.si/' . $room;
        $body = "🎥 Salon de discussion : " . $link
              . "\nClique pour nous rejoindre — visio, audio, partage d'écran et de fichiers.";
        Message::send($uid, $to, $body);

        $me = Session::user();
        Notification::add(
            $to,
            ($me['name'] ?? 'Un membre') . ' t\'invite à un salon visio 🎥',
            '🎥',
            url('messages/thread?with=' . $uid)
        );
        redirect('messages/thread?with=' . $to);
    }
}

<?php
/**
 * CONTRÔLEUR MessageController — messagerie privée entre membres.
 */
class MessageController
{
    private function uid(): int
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $uid = (int) (Session::user()['id'] ?? 0);
        if ($uid <= 0) {
            redirect('dashboard');
        }
        return $uid;
    }

    /** Boîte de réception : liste des conversations. */
    public function index(): void
    {
        $uid = $this->uid();
        view('messages/index', [
            'user'    => Session::user(),
            'threads' => Message::threads($uid),
        ]);
    }

    /** Conversation avec un membre (?with=ID). */
    public function thread(): void
    {
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
        if (!$other || $with === $uid) {
            echo json_encode(['ok' => false]);
            return;
        }
        Message::markRead($uid, $with);
        $msgs = Message::conversation($uid, $with);
        $html = '';
        foreach ($msgs as $m) {
            $mine = (int) $m['sender_id'] === $uid;
            $html .= '<div class="bubble ' . ($mine ? 'mine' : 'theirs') . '">'
                . nl2br(htmlspecialchars($m['body']))
                . '<span class="when">' . date('d/m H\hi', strtotime($m['created_at'])) . '</span></div>';
        }
        echo json_encode([
            'ok'          => true,
            'count'       => count($msgs),
            'html'        => $html,
            'otherOnline' => User::isOnline($with),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** Envoi d'un message (POST). */
    public function send(): void
    {
        $uid  = $this->uid();
        $to   = (int) ($_POST['to'] ?? 0);
        $body = (string) ($_POST['body'] ?? '');
        $other = $to > 0 ? User::findById($to) : null;
        if (!$other || $to === $uid) {
            redirect('messages');
        }
        if (trim($body) === '') {
            Session::set('msg_error', 'Écris un message avant d\'envoyer.');
            redirect('messages/thread?with=' . $to);
        }
        Message::send($uid, $to, $body);
        // Notifie le destinataire.
        $me = Session::user();
        Notification::add($to, 'Nouveau message de ' . ($me['name'] ?? 'un membre'), '✉️', url('messages/thread?with=' . $uid));
        redirect('messages/thread?with=' . $to);
    }
}

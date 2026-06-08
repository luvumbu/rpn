<?php
/**
 * CONTRÔLEUR Notification
 * Page des notifications in-app du membre connecté (confirmations de réservation,
 * annulations, etc.). L'ouverture de la page marque tout comme lu.
 */
class NotificationController
{
    private function guard(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
    }

    /** Liste des notifications du membre ; les marque toutes comme lues. */
    public function index(): void
    {
        $this->guard();
        $uid = (int) (Session::user()['id'] ?? 0);

        $items = Notification::forUser($uid);
        // Vu = lu : on remet le compteur à zéro à la consultation.
        Notification::markAllRead($uid);

        view('notifications/index', [
            'user'  => Session::user(),
            'items' => $items,
        ]);
    }

    /**
     * Sondage temps réel (AJAX, JSON) : renvoie le nombre de non-lues + les
     * dernières notifications non lues, SANS les marquer comme lues (seule
     * l'ouverture de la page /notifications marque comme lu). Appelé en boucle
     * par le poller injecté sur toutes les pages.
     */
    public function poll(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Session::has('user')) {
            echo json_encode(['ok' => false, 'loggedIn' => false]);
            return;
        }
        $uid   = (int) (Session::user()['id'] ?? 0);
        $items = Notification::unreadForUser($uid, 10);
        $maxId = 0;
        foreach ($items as $it) {
            $maxId = max($maxId, (int) $it['id']);
        }
        echo json_encode([
            'ok'     => true,
            'unread' => Notification::unreadCount($uid),
            'maxId'  => $maxId,
            'items'  => array_map(static function ($n) {
                return [
                    'id'      => (int) $n['id'],
                    'icon'    => $n['icon'] ?: '🔔',
                    'message' => $n['message'],
                    'link'    => $n['link'] ?: '',
                ];
            }, $items),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** Vide la liste des notifications du membre. */
    public function clear(): void
    {
        $this->guard();
        Notification::clearForUser((int) (Session::user()['id'] ?? 0));
        redirect('notifications');
    }
}

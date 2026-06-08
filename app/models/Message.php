<?php
/**
 * MODÈLE Message — messagerie privée entre deux membres.
 */
class Message
{
    /** Envoie un message. Retourne l'id créé (0 si vide). */
    public static function send(int $senderId, int $recipientId, string $body): int
    {
        $body = trim($body);
        if ($senderId <= 0 || $recipientId <= 0 || $senderId === $recipientId || $body === '') {
            return 0;
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO messages (sender_id, recipient_id, body) VALUES (?, ?, ?)'
        );
        $stmt->execute([$senderId, $recipientId, mb_substr($body, 0, 4000)]);
        return (int) Database::pdo()->lastInsertId();
    }

    /** Nombre total de messages non lus reçus par $uid. */
    public static function unreadCount(int $uid): int
    {
        if ($uid <= 0) {
            return 0;
        }
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = 0');
        $stmt->execute([$uid]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Liste des conversations de $uid : pour chaque interlocuteur, le dernier
     * message + le nombre de non-lus. Triées par message le plus récent.
     */
    public static function threads(int $uid): array
    {
        if ($uid <= 0) {
            return [];
        }
        $sql = 'SELECT m.*,
                    CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END AS other_id
                FROM messages m
                WHERE m.sender_id = ? OR m.recipient_id = ?
                ORDER BY m.created_at DESC, m.id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$uid, $uid, $uid]);
        $rows = $stmt->fetchAll();

        $threads = [];
        foreach ($rows as $m) {
            $other = (int) $m['other_id'];
            if (!isset($threads[$other])) {
                $threads[$other] = [
                    'other_id' => $other,
                    'last'     => $m['body'],
                    'last_at'  => $m['created_at'],
                    'mine'     => (int) $m['sender_id'] === $uid,
                    'unread'   => 0,
                ];
            }
            if ((int) $m['recipient_id'] === $uid && (int) $m['is_read'] === 0) {
                $threads[$other]['unread']++;
            }
        }
        // Complète avec le nom/photo de l'interlocuteur.
        foreach ($threads as $oid => &$t) {
            $u = User::findById($oid);
            $t['name']    = $u['name'] ?? ('Membre #' . $oid);
            $t['picture'] = $u['picture'] ?? null;
        }
        unset($t);
        return array_values($threads);
    }

    /** Tous les messages échangés entre $uid et $otherId (ordre chronologique). */
    public static function conversation(int $uid, int $otherId): array
    {
        if ($uid <= 0 || $otherId <= 0) {
            return [];
        }
        $sql = 'SELECT * FROM messages
                WHERE (sender_id = ? AND recipient_id = ?)
                   OR (sender_id = ? AND recipient_id = ?)
                ORDER BY created_at ASC, id ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$uid, $otherId, $otherId, $uid]);
        return $stmt->fetchAll();
    }

    /** Marque comme lus les messages reçus par $uid en provenance de $otherId. */
    public static function markRead(int $uid, int $otherId): void
    {
        if ($uid <= 0 || $otherId <= 0) {
            return;
        }
        $stmt = Database::pdo()->prepare(
            'UPDATE messages SET is_read = 1 WHERE recipient_id = ? AND sender_id = ? AND is_read = 0'
        );
        $stmt->execute([$uid, $otherId]);
    }
}

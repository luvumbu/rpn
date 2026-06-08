<?php
/**
 * MODÈLE Notification
 * Notifications in-app destinées à un membre (confirmations de réservation,
 * annulations, etc.). Chaque ligne appartient à UN destinataire (user_id) et
 * peut être lue/non lue. Affichées dans la page « Notifications ».
 */
class Notification
{
    /** Crée une notification pour un destinataire. $link = route interne facultative. */
    public static function add(int $userId, string $message, string $icon = '🔔', string $link = ''): void
    {
        if ($userId <= 0 || trim($message) === '') {
            return;
        }
        // Garde-fous de longueur (colonnes VARCHAR).
        $message = mb_substr($message, 0, 500);
        $icon    = mb_substr($icon, 0, 16);
        $link    = mb_substr($link, 0, 255);

        $stmt = Database::pdo()->prepare(
            'INSERT INTO notifications (user_id, icon, message, link) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $icon, $message, $link]);
    }

    /** Notifications d'un membre, des plus récentes aux plus anciennes. */
    public static function forUser(int $userId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt  = Database::pdo()->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT $limit"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Notifications NON LUES d'un membre (pour le polling temps réel), sans les marquer lues. */
    public static function unreadForUser(int $userId, int $limit = 10): array
    {
        if ($userId <= 0) {
            return [];
        }
        $limit = max(1, min(50, $limit));
        $stmt  = Database::pdo()->prepare(
            "SELECT id, icon, message, link, created_at FROM notifications
             WHERE user_id = ? AND is_read = 0 ORDER BY id DESC LIMIT $limit"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Nombre de notifications non lues d'un membre (pour le badge). */
    public static function unreadCount(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /** Marque toutes les notifications d'un membre comme lues. */
    public static function markAllRead(int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);
    }

    /** Supprime toutes les notifications d'un membre (vider la liste). */
    public static function clearForUser(int $userId): void
    {
        Database::pdo()->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$userId]);
    }
}

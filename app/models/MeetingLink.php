<?php
/**
 * MODÈLE MeetingLink — salons visio (liens Jitsi) enregistrés par un membre.
 * Permet de garder un historique des liens créés pour les retrouver/partager.
 */
class MeetingLink
{
    /** Enregistre un lien pour un membre. Retourne l'id (0 si invalide). */
    public static function add(int $userId, string $url, string $label = ''): int
    {
        $url   = trim($url);
        $label = trim($label);
        // Sécurité : on n'accepte que des liens http(s) raisonnables.
        if ($userId <= 0 || !preg_match('#^https?://#i', $url) || mb_strlen($url) > 255) {
            return 0;
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO meeting_links (user_id, url, label) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $url, $label !== '' ? mb_substr($label, 0, 120) : null]);
        return (int) Database::pdo()->lastInsertId();
    }

    /** Liste des salons enregistrés par un membre (du plus récent au plus ancien). */
    public static function forUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM meeting_links WHERE user_id = ? ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Renomme un salon enregistré (seulement s'il appartient au membre). */
    public static function rename(int $id, int $userId, string $label): void
    {
        $label = trim($label);
        Database::pdo()
            ->prepare('UPDATE meeting_links SET label = ? WHERE id = ? AND user_id = ?')
            ->execute([$label !== '' ? mb_substr($label, 0, 120) : null, $id, $userId]);
    }

    /** Supprime un salon enregistré (seulement s'il appartient au membre). */
    public static function delete(int $id, int $userId): void
    {
        Database::pdo()
            ->prepare('DELETE FROM meeting_links WHERE id = ? AND user_id = ?')
            ->execute([$id, $userId]);
    }
}

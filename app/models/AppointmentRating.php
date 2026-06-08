<?php
/**
 * MODÈLE AppointmentRating
 * Note d'un ÉVÉNEMENT (créneau d'agenda) par un participant (1 à 5 étoiles,
 * + commentaire facultatif). Un avis par participant et par événement.
 */
class AppointmentRating
{
    /** Crée ou met à jour l'avis d'un participant sur un événement. */
    public static function set(int $appointmentId, int $userId, int $stars, ?string $comment = null): void
    {
        $stars   = max(1, min(5, $stars));
        $comment = ($comment !== null && trim($comment) !== '') ? mb_substr(trim($comment), 0, 500) : null;
        Database::pdo()->prepare(
            'INSERT INTO appointment_ratings (appointment_id, user_id, stars, comment)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE stars = VALUES(stars), comment = VALUES(comment), updated_at = NOW()'
        )->execute([$appointmentId, $userId, $stars, $comment]);
    }

    /** Résumé (moyenne + nombre) pour un événement. */
    public static function summary(int $appointmentId): array
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) c, AVG(stars) a FROM appointment_ratings WHERE appointment_id = ?');
        $stmt->execute([$appointmentId]);
        $r = $stmt->fetch();
        return ['count' => (int) ($r['c'] ?? 0), 'avg' => (float) ($r['a'] ?? 0)];
    }

    /** Résumés pour plusieurs événements : id => ['count','avg']. */
    public static function summaryFor(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare("SELECT appointment_id, COUNT(*) c, AVG(stars) a FROM appointment_ratings WHERE appointment_id IN ($in) GROUP BY appointment_id");
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['appointment_id']] = ['count' => (int) $row['c'], 'avg' => (float) $row['a']];
        }
        return $out;
    }

    /** Mon avis sur un événement (ou null). */
    public static function mine(int $appointmentId, int $userId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM appointment_ratings WHERE appointment_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$appointmentId, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function deleteForAppointment(int $appointmentId): void
    {
        Database::pdo()->prepare('DELETE FROM appointment_ratings WHERE appointment_id = ?')->execute([$appointmentId]);
    }
}

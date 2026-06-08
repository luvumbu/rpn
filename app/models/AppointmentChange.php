<?php
/**
 * MODÈLE AppointmentChange
 * Historique des modifications d'un créneau (pour l'instant : le lieu).
 * Chaque entrée garde l'ancienne et la nouvelle valeur + la date du changement.
 */
class AppointmentChange
{
    /** Journalise un changement (ancienne → nouvelle valeur). */
    public static function add(int $appointmentId, string $field, string $old, string $new): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO appointment_changes (appointment_id, field, old_value, new_value) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$appointmentId, $field, $old, $new]);
    }

    /** Historique d'un créneau (le plus récent d'abord). */
    public static function forAppointment(int $appointmentId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM appointment_changes WHERE appointment_id = ? ORDER BY changed_at DESC, id DESC'
        );
        $stmt->execute([$appointmentId]);
        return $stmt->fetchAll();
    }

    /** Supprime l'historique d'un créneau (à sa suppression). */
    public static function deleteForAppointment(int $appointmentId): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM appointment_changes WHERE appointment_id = ?');
        $stmt->execute([$appointmentId]);
    }
}

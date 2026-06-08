<?php
/**
 * MODÈLE AppointmentBooking
 * Réservations de place sur un créneau (Appointment). Un membre ne peut
 * réserver qu'une seule place par créneau (contrainte UNIQUE en base).
 */
class AppointmentBooking
{
    /** Ajoute une réservation. Retourne false si déjà réservé (doublon). */
    public static function add(int $appointmentId, int $userId, string $userName): bool
    {
        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO appointment_bookings (appointment_id, user_id, user_name) VALUES (?, ?, ?)'
            );
            $stmt->execute([$appointmentId, $userId, $userName]);
            return true;
        } catch (PDOException $e) {
            return false; // doublon (clé unique) ou autre erreur
        }
    }

    /** Nombre de places réservées sur un créneau. */
    public static function countFor(int $appointmentId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM appointment_bookings WHERE appointment_id = ?');
        $stmt->execute([$appointmentId]);
        return (int) $stmt->fetchColumn();
    }

    /** Ce membre a-t-il déjà réservé ce créneau ? */
    public static function hasBooked(int $appointmentId, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM appointment_bookings WHERE appointment_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$appointmentId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Liste des membres ayant réservé ce créneau. */
    public static function forAppointment(int $appointmentId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM appointment_bookings WHERE appointment_id = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$appointmentId]);
        return $stmt->fetchAll();
    }

    /** Ids des créneaux réservés par un membre (pour l'affichage). */
    public static function appointmentIdsForUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT appointment_id FROM appointment_bookings WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Détail des créneaux À VENIR réservés par un membre (avec titre, description…). */
    public static function forUserDetailed(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.*, (SELECT COUNT(*) FROM appointment_bookings b2 WHERE b2.appointment_id = a.id) AS booked
             FROM appointment_bookings b
             JOIN appointments a ON a.id = b.appointment_id
             WHERE b.user_id = ? AND a.start_at >= NOW()
             ORDER BY a.start_at ASC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Ce votant a-t-il réservé au moins un créneau de cet hôte ? (droit de noter) */
    public static function userBookedFromOwner(int $raterId, int $ownerId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM appointment_bookings b
             JOIN appointments a ON a.id = b.appointment_id
             WHERE b.user_id = ? AND a.owner_id = ? LIMIT 1'
        );
        $stmt->execute([$raterId, $ownerId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Marque la présence d'un participant à un créneau.
     * $present : 1 = présent, 0 = absent, null = remet « non renseigné ».
     */
    public static function setPresence(int $appointmentId, int $userId, ?int $present): void
    {
        $val = ($present === null) ? null : ($present ? 1 : 0);
        $stmt = Database::pdo()->prepare(
            'UPDATE appointment_bookings SET present = ? WHERE appointment_id = ? AND user_id = ?'
        );
        $stmt->execute([$val, $appointmentId, $userId]);
    }

    /** Annule la réservation de ce membre sur ce créneau. */
    public static function cancel(int $appointmentId, int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM appointment_bookings WHERE appointment_id = ? AND user_id = ?'
        );
        $stmt->execute([$appointmentId, $userId]);
    }

    /** Supprime toutes les réservations d'un créneau (à sa suppression). */
    public static function deleteForAppointment(int $appointmentId): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM appointment_bookings WHERE appointment_id = ?');
        $stmt->execute([$appointmentId]);
    }
}

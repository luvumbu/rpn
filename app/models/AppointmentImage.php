<?php
/**
 * MODÈLE AppointmentImage
 * Photos rattachées à un créneau / événement d'agenda (rangées dans uploads/agenda/).
 */
class AppointmentImage
{
    /** Photos d'un événement (ordre d'ajout). */
    public static function forAppointment(int $appointmentId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM appointment_images WHERE appointment_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$appointmentId]);
        return $stmt->fetchAll();
    }

    /** Photos de plusieurs événements d'un coup : id => [filenames]. */
    public static function forAppointments(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare("SELECT appointment_id, filename FROM appointment_images WHERE appointment_id IN ($in) ORDER BY id ASC");
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['appointment_id']][] = $row['filename'];
        }
        return $out;
    }

    public static function add(int $appointmentId, string $filename): void
    {
        Database::pdo()
            ->prepare('INSERT INTO appointment_images (appointment_id, filename) VALUES (?, ?)')
            ->execute([$appointmentId, $filename]);
    }

    /** Supprime les lignes d'un événement et renvoie les noms de fichiers (pour effacement disque). */
    public static function deleteForAppointment(int $appointmentId): array
    {
        $files = array_column(self::forAppointment($appointmentId), 'filename');
        Database::pdo()->prepare('DELETE FROM appointment_images WHERE appointment_id = ?')->execute([$appointmentId]);
        return $files;
    }
}

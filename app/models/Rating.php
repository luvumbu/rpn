<?php
/**
 * MODÈLE Rating
 * Notes (1 à 5 étoiles) attribuées par un membre à un autre (l'hôte d'un créneau).
 * Une note par couple (hôte, votant), modifiable. Sert à afficher la réputation
 * d'un hôte au moment de réserver.
 */
class Rating
{
    /** Enregistre ou met à jour la note d'un votant pour un hôte. */
    public static function set(int $ownerId, int $raterId, int $stars): void
    {
        $stars = max(1, min(5, $stars));
        $stmt = Database::pdo()->prepare(
            'INSERT INTO member_ratings (owner_id, rater_id, stars) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stars = VALUES(stars), updated_at = NOW()'
        );
        $stmt->execute([$ownerId, $raterId, $stars]);
    }

    /** Moyenne + nombre de notes pour un ensemble d'hôtes : [owner_id => ['avg','count']]. */
    public static function summaryFor(array $ownerIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ownerIds))));
        if (!$ids) {
            return [];
        }
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT owner_id, AVG(stars) AS avg, COUNT(*) AS cnt
             FROM member_ratings WHERE owner_id IN ($in) GROUP BY owner_id"
        );
        $stmt->execute($ids);
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[(int) $r['owner_id']] = ['avg' => (float) $r['avg'], 'count' => (int) $r['cnt']];
        }
        return $map;
    }

    /** Note donnée par un votant à un hôte (0 si aucune). */
    public static function myRating(int $ownerId, int $raterId): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT stars FROM member_ratings WHERE owner_id = ? AND rater_id = ? LIMIT 1'
        );
        $stmt->execute([$ownerId, $raterId]);
        return (int) $stmt->fetchColumn();
    }
}

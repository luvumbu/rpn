<?php
/**
 * MODÈLE Appointment
 * Créneaux de rendez-vous proposés par un membre (son « agenda »).
 * Chaque créneau a un nombre de places ; les autres membres réservent
 * (voir AppointmentBooking). Pas de rôle prof/élève : tout membre peut
 * proposer un créneau ET réserver chez les autres.
 */
class Appointment
{
    /** Crée un créneau, retourne son id. */
    public static function create(array $d): int
    {
        $visibility = ($d['visibility'] ?? 'public') === 'private' ? 'private' : 'public';
        // Chaque événement reçoit un code UNIQUE (même public) : visible dans
        // « Mes créneaux » et partageable.
        $code       = self::generateUniqueCode();
        $mode       = ($d['mode'] ?? 'presentiel') === 'en_ligne' ? 'en_ligne' : 'presentiel';
        $location   = (string) ($d['location'] ?? '');
        // Géocode l'adresse (présentiel) pour la recherche par proximité.
        $coords = ($mode === 'presentiel' && trim($location) !== '') ? Geocoder::geocode($location) : null;

        // Afficher (1) ou non (0) la note globale des inscrits. Défaut : MASQUÉ.
        $showRatings = array_key_exists('show_booker_ratings', $d) ? (int) (bool) $d['show_booker_ratings'] : 0;
        // Délai minimum de réservation avant le début (en heures), borné.
        $minNotice = max(0, min(8760, (int) ($d['min_notice_hours'] ?? 0)));

        $stmt = Database::pdo()->prepare(
            'INSERT INTO appointments (owner_id, owner_name, title, description, start_at, duration_min, capacity, mode, location, visibility, show_booker_ratings, min_notice_hours, code, lat, lng)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int) $d['owner_id'],
            $d['owner_name'] ?? '',
            $d['title'],
            $d['description'] ?? '',
            $d['start_at'],
            (int) ($d['duration_min'] ?? 60),
            (int) ($d['capacity'] ?? 5),
            $mode,
            $location,
            $visibility,
            $showRatings,
            $minNotice,
            $code,
            $coords['lat'] ?? null,
            $coords['lng'] ?? null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    /** Renvoie le code d'un créneau, en le générant s'il n'en a pas encore (anciens créneaux publics). */
    public static function ensureCode(int $id, ?string $current = null): string
    {
        if ($current !== null && $current !== '') {
            return $current;
        }
        $code = self::generateUniqueCode();
        Database::pdo()->prepare('UPDATE appointments SET code = ? WHERE id = ?')->execute([$code, $id]);
        return $code;
    }

    /** Génère un code d'accès unique : 2 lettres + 3 chiffres (jamais en double). */
    public static function generateUniqueCode(): string
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('SELECT 1 FROM appointments WHERE code = ? LIMIT 1');
        $code = '';
        for ($i = 0; $i < 40; $i++) {
            $code = chr(random_int(65, 90)) . chr(random_int(65, 90))
                  . random_int(0, 9) . random_int(0, 9) . random_int(0, 9);
            $stmt->execute([$code]);
            if (!$stmt->fetchColumn()) {
                return $code;
            }
        }
        return $code; // repli (collision quasi impossible)
    }

    /** Trouve un RDV privé par son code (avec le nombre de places réservées). */
    public static function findByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.*, (SELECT COUNT(*) FROM appointment_bookings b WHERE b.appointment_id = a.id) AS booked
             FROM appointments a WHERE a.code = ? AND a.visibility = "private" LIMIT 1'
        );
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    /** Un créneau par son id (ou null). */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM appointments WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Créneaux d'un membre (les siens), avec le nombre de places réservées. */
    public static function forOwner(int $ownerId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.*, (SELECT COUNT(*) FROM appointment_bookings b WHERE b.appointment_id = a.id) AS booked
             FROM appointments a WHERE a.owner_id = ? ORDER BY a.start_at ASC'
        );
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    /** Créneaux À VENIR proposés par les AUTRES membres (réservables). */
    public static function upcomingOthers(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.*, (SELECT COUNT(*) FROM appointment_bookings b WHERE b.appointment_id = a.id) AS booked
             FROM appointments a
             WHERE a.owner_id <> ? AND a.start_at >= NOW() AND a.visibility = "public"
             ORDER BY a.start_at ASC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * AGENDA GLOBAL : l'ensemble des créneaux pour une vue d'ensemble.
     * Règle de visibilité : tous les créneaux PUBLICS sont visibles ; un créneau
     * PRIVÉ n'apparaît que pour son hôte (ou pour un administrateur qui voit tout).
     * Chaque ligne inclut le nombre de places réservées (booked). Tri chronologique.
     */
    public static function allForGlobal(int $viewerId, bool $isAdmin): array
    {
        $booked = '(SELECT COUNT(*) FROM appointment_bookings b WHERE b.appointment_id = a.id) AS booked';
        if ($isAdmin) {
            $stmt = Database::pdo()->query(
                "SELECT a.*, $booked FROM appointments a ORDER BY a.start_at ASC"
            );
            return $stmt->fetchAll();
        }
        $stmt = Database::pdo()->prepare(
            "SELECT a.*, $booked FROM appointments a
             WHERE a.visibility = 'public' OR a.owner_id = ?
             ORDER BY a.start_at ASC"
        );
        $stmt->execute([$viewerId]);
        return $stmt->fetchAll();
    }

    /** Met à jour uniquement le lieu (mode + adresse/lien) d'un créneau + ses coordonnées. */
    public static function updateLocation(int $id, string $mode, string $location): void
    {
        $mode   = $mode === 'en_ligne' ? 'en_ligne' : 'presentiel';
        $coords = ($mode === 'presentiel' && trim($location) !== '') ? Geocoder::geocode($location) : null;
        $stmt = Database::pdo()->prepare('UPDATE appointments SET mode = ?, location = ?, lat = ?, lng = ? WHERE id = ?');
        $stmt->execute([$mode, $location, $coords['lat'] ?? null, $coords['lng'] ?? null, $id]);
    }

    /**
     * Créneaux PUBLICS à venir, en présentiel, dans un rayon (km) autour d'un point.
     * Distance calculée par la formule de Haversine (6371 = rayon terrestre en km).
     */
    public static function nearby(float $lat, float $lng, int $radiusKm, int $viewerId,
                                  bool $includeMine = false, bool $includeOthers = true, bool $isAdmin = false): array
    {
        $params = [$lat, $lng, $lat];   // calcul de distance (3 placeholders)
        $scope  = [];
        if ($includeMine) {
            $scope[]  = 'a.owner_id = ?';                  // mes créneaux, toutes visibilités
            $params[] = $viewerId;
        }
        if ($includeOthers) {
            if ($isAdmin) {
                $scope[]  = 'a.owner_id <> ?';             // admin : autres, public ET privé
                $params[] = $viewerId;
            } else {
                $scope[]  = '(a.owner_id <> ? AND a.visibility = "public")';
                $params[] = $viewerId;
            }
        }
        if (!$scope) {
            return [];   // aucun périmètre sélectionné
        }
        $params[] = $radiusKm;

        $stmt = Database::pdo()->prepare(
            'SELECT a.*,
                    (SELECT COUNT(*) FROM appointment_bookings b WHERE b.appointment_id = a.id) AS booked,
                    (6371 * ACOS(
                        LEAST(1, COS(RADIANS(?)) * COS(RADIANS(a.lat)) * COS(RADIANS(a.lng) - RADIANS(?))
                                 + SIN(RADIANS(?)) * SIN(RADIANS(a.lat)))
                    )) AS distance
             FROM appointments a
             WHERE a.start_at >= NOW() AND a.lat IS NOT NULL
               AND (' . implode(' OR ', $scope) . ')
             HAVING distance <= ?
             ORDER BY distance ASC'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Affiche (1) ou masque (0) la note globale des inscrits, pour un créneau. */
    public static function setShowBookerRatings(int $id, int $show): void
    {
        $stmt = Database::pdo()->prepare('UPDATE appointments SET show_booker_ratings = ? WHERE id = ?');
        $stmt->execute([$show ? 1 : 0, $id]);
    }

    /**
     * Bascule la visibilité d'un créneau (public ⇄ privé).
     * IMPORTANT : le code privé est STABLE. Au passage en privé, on réutilise le
     * code déjà attribué s'il existe (n'en génère un que la toute première fois) ;
     * au passage en public, on CONSERVE le code en base (jamais effacé). Ainsi une
     * double manipulation privé→public→privé garde toujours le même code.
     */
    public static function setVisibility(int $id, string $visibility): void
    {
        $visibility = $visibility === 'private' ? 'private' : 'public';
        $appt = self::find($id);
        if (!$appt) {
            return;
        }
        $existing = (string) ($appt['code'] ?? '');
        if ($visibility === 'private') {
            // Réutilise le code existant ; n'en crée un que s'il n'y en a jamais eu.
            $code = $existing !== '' ? $existing : self::generateUniqueCode();
        } else {
            // Public : on garde le code mémorisé tel quel (pour un éventuel retour en privé).
            $code = $existing;
        }
        $stmt = Database::pdo()->prepare('UPDATE appointments SET visibility = ?, code = ? WHERE id = ?');
        $stmt->execute([$visibility, $code, $id]);
    }

    /** Définit le délai minimum de réservation (en heures, borné). */
    public static function updateMinNotice(int $id, int $hours): void
    {
        $hours = max(0, min(8760, $hours));
        $stmt  = Database::pdo()->prepare('UPDATE appointments SET min_notice_hours = ? WHERE id = ?');
        $stmt->execute([$hours, $id]);
    }

    /** Modifie le nombre de places d'un créneau (borné 1 à 99). */
    public static function updateCapacity(int $id, int $capacity): void
    {
        $capacity = max(1, min(99, $capacity));
        $stmt = Database::pdo()->prepare('UPDATE appointments SET capacity = ? WHERE id = ?');
        $stmt->execute([$capacity, $id]);
    }

    /**
     * Modifie les informations principales d'un créneau (titre, description,
     * date/heure de début, durée). Le lieu, les places, la visibilité et le délai
     * ont leurs propres méthodes dédiées.
     */
    public static function updateDetails(int $id, array $d): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE appointments SET title = ?, description = ?, start_at = ?, duration_min = ? WHERE id = ?'
        );
        $stmt->execute([
            $d['title'],
            $d['description'] ?? '',
            $d['start_at'],
            max(5, min(600, (int) ($d['duration_min'] ?? 60))),
            $id,
        ]);
    }

    /** Supprime un créneau. */
    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM appointments WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Marque (true) ou non (false) un événement comme « URGENT » (alerte carré rouge). */
    public static function setUrgent(int $id, bool $on): void
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('SELECT urgent FROM appointments WHERE id = ?');
        $stmt->execute([$id]);
        $was = (int) $stmt->fetchColumn() === 1;

        $pdo->prepare('UPDATE appointments SET urgent = ? WHERE id = ?')->execute([$on ? 1 : 0, $id]);

        // Passage normal → urgent : on rouvre l'alerte pour tout le monde.
        if ($on && !$was) {
            Urgent::reset('event', $id);
        }
    }

    /** Protège (true) ou non (false) un événement contre l'effacement global. */
    public static function setProtected(int $id, bool $on): void
    {
        Database::pdo()->prepare('UPDATE appointments SET protected = ? WHERE id = ?')->execute([$on ? 1 : 0, $id]);
    }

    /** IDs des événements NON protégés (ceux qu'un « tout effacer » supprimera). */
    public static function unprotectedIds(): array
    {
        return array_map('intval', Database::pdo()
            ->query('SELECT id FROM appointments WHERE protected = 0')
            ->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Nombre d'événements protégés. */
    public static function protectedCount(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM appointments WHERE protected = 1')->fetchColumn();
    }
}

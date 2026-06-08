<?php
/**
 * MODÈLE User
 * Représente un utilisateur ET gère toutes les requêtes en base de données.
 */
class User
{
    /** Cherche un utilisateur par son email. */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /** Cherche un utilisateur par son id. */
    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Cherche un ADMIN par son identifiant de connexion :
     * ce peut être un nom d'utilisateur (ex: root) OU un email.
     */
    public static function findAdminByLogin(string $login): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'admin' LIMIT 1"
        );
        $stmt->execute([$login, $login]);
        return $stmt->fetch() ?: null;
    }

    /** Liste tous les utilisateurs (les plus récents d'abord). */
    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
    }

    /** Nombre total d'utilisateurs. */
    public static function count(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /** Nombre d'utilisateurs d'un rôle donné ('admin' ou 'membre'). */
    public static function countByRole(string $role): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
        $stmt->execute([$role]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Crée ou met à jour un membre à partir des données Google,
     * puis retourne la ligne complète depuis la base.
     */
    public static function upsertFromGoogle(array $g, bool $isAdmin): array
    {
        $pdo      = Database::pdo();
        $existing = self::findByEmail($g['email']);

        // Un email de la liste admin reste admin ; sinon on garde le rôle existant (ou 'membre')
        $role = $isAdmin ? 'admin' : ($existing['role'] ?? 'membre');

        if ($existing) {
            $stmt = $pdo->prepare(
                'UPDATE users SET google_id = ?, name = ?, picture = ?, role = ?, last_login = NOW() WHERE email = ?'
            );
            $stmt->execute([$g['sub'] ?? '', $g['name'] ?? '', $g['picture'] ?? '', $role, $g['email']]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO users (google_id, name, email, picture, role, last_login)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$g['sub'] ?? '', $g['name'] ?? '', $g['email'], $g['picture'] ?? '', $role]);
        }

        return self::findByEmail($g['email']);
    }

    /**
     * Crée un MEMBRE « classique » (sans Google) : nom + email + mot de passe haché.
     * Retourne la ligne complète. L'unicité de l'email est garantie par la base.
     */
    public static function createMember(string $name, string $email, string $password): ?array
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = Database::pdo()->prepare(
            "INSERT INTO users (name, email, role, password, last_login)
             VALUES (?, ?, 'membre', ?, NOW())"
        );
        $stmt->execute([$name, $email, $hash]);
        return self::findByEmail($email);
    }

    /** Met à jour la date de dernière connexion. */
    public static function touchLogin(int $id): void
    {
        Database::pdo()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$id]);
    }

    /**
     * Crée un administrateur avec identifiant + mot de passe (haché).
     * L'email est optionnel (peut rester vide pour un admin type "root").
     */
    public static function createAdmin(string $username, string $password, string $name = 'Administrateur', ?string $email = null): void
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = Database::pdo()->prepare(
            "INSERT INTO users (username, name, email, role, password) VALUES (?, ?, ?, 'admin', ?)
             ON DUPLICATE KEY UPDATE role = 'admin', password = VALUES(password), name = VALUES(name)"
        );
        $stmt->execute([$username, $name, ($email ?: null), $hash]);
    }

    /** Change le rôle d'un utilisateur ('admin' ou 'membre'). */
    public static function setRole(int $id, string $role): void
    {
        $role = in_array($role, ['admin', 'membre'], true) ? $role : 'membre';
        $stmt = Database::pdo()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $id]);
    }

    /** Bloque (true) ou débloque (false) un utilisateur. */
    public static function setBlocked(int $id, bool $blocked): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET blocked = ? WHERE id = ?');
        $stmt->execute([$blocked ? 1 : 0, $id]);
    }

    /**
     * Enregistre la ville par défaut d'un membre (pour la recherche par proximité)
     * et ses coordonnées mémorisées (lat/lng), ou les efface (null).
     */
    public static function setDefaultCity(int $id, ?string $city, ?float $lat, ?float $lng): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users SET default_city = ?, default_lat = ?, default_lng = ? WHERE id = ?'
        );
        $stmt->execute([$city, $lat, $lng, $id]);
    }

    /**
     * Membres TROUVABLES : ceux qui ont donné leur accord (discoverable = 1).
     * Seuls ceux-là peuvent être ajoutés à une activité ou recherchés par code.
     */
    public static function discoverable(): array
    {
        return Database::pdo()
            ->query("SELECT * FROM users WHERE discoverable = 1 ORDER BY name ASC")
            ->fetchAll();
    }

    /**
     * Recherche de « professeurs » (membres trouvables) par matière/domaine.
     * $domain vide → tous les membres trouvables. Sinon filtre sur la liste
     * de domaines (matières/spécialités) du membre.
     */
    public static function searchTeachers(?string $domain = null): array
    {
        $pdo    = Database::pdo();
        $domain = trim((string) $domain);
        if ($domain === '') {
            return $pdo->query("SELECT * FROM users WHERE discoverable = 1 ORDER BY name ASC")->fetchAll();
        }
        $stmt = $pdo->prepare("SELECT * FROM users WHERE discoverable = 1 AND domains LIKE ? ORDER BY name ASC");
        $stmt->execute(['%' . $domain . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Matières/domaines RÉELLEMENT portés par des membres trouvables, avec le
     * NOMBRE de personnes pour chacun → [domaine => compte]. Ne contient donc que
     * des cases « avec des personnes » (trié par effectif décroissant).
     */
    public static function teacherDomains(): array
    {
        $rows = Database::pdo()
            ->query("SELECT domains FROM users WHERE discoverable = 1 AND domains IS NOT NULL AND domains <> ''")
            ->fetchAll(PDO::FETCH_COLUMN);
        $count = [];
        foreach ($rows as $d) {
            foreach (self::domainsToList($d) as $one) {
                $count[$one] = ($count[$one] ?? 0) + 1;
            }
        }
        arsort($count); // les matières les plus représentées d'abord
        return $count;
    }

    /** Génère un code membre unique : 1 lettre + 3 chiffres (ex. A123). */
    public static function generateUniqueCode(): string
    {
        $pdo = Database::pdo();
        do {
            $code = chr(random_int(65, 90)) . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare('SELECT 1 FROM users WHERE member_code = ? LIMIT 1');
            $stmt->execute([$code]);
        } while ($stmt->fetch());
        return $code;
    }

    /** Renvoie le code membre (en le générant à la première fois si absent). */
    public static function ensureCode(int $id): string
    {
        if ($id <= 0) {
            return '';
        }
        $u = self::findById($id);
        if (!$u) {
            return '';
        }
        if (!empty($u['member_code'])) {
            return (string) $u['member_code'];
        }
        $code = self::generateUniqueCode();
        Database::pdo()->prepare('UPDATE users SET member_code = ? WHERE id = ?')->execute([$code, $id]);
        return $code;
    }

    /** Autorise (true) ou non (false) qu'on ajoute ce membre à des activités. */
    public static function setDiscoverable(int $id, bool $on): void
    {
        if ($on) {
            self::ensureCode($id); // un membre trouvable a forcément un code
        }
        Database::pdo()->prepare('UPDATE users SET discoverable = ? WHERE id = ?')->execute([$on ? 1 : 0, $id]);
    }

    /**
     * Trouve un membre par son code (1 lettre + 3 chiffres). Connaître le code
     * = le membre te l'a donné, donc on le trouve même s'il n'est pas dans la
     * liste publique (« discoverable »). Le code est le canal d'accord privé.
     */
    public static function findByCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z][0-9]{3}$/', $code)) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE member_code = ? LIMIT 1');
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Définit (ou efface avec null) la photo de profil. La valeur est soit une
     * URL http(s) (photo Google), soit un nom de fichier (uploads/avatars/).
     */
    public static function setPicture(int $id, ?string $picture): void
    {
        Database::pdo()->prepare('UPDATE users SET picture = ? WHERE id = ?')->execute([$picture, $id]);
    }

    /** Suggestions de rôles / domaines proposées par défaut dans le profil. */
    public static function domainSuggestions(): array
    {
        // Liste à plat (compat : pré-cochage des chips, calcul des « autres »).
        $flat = [];
        foreach (self::domainCategories() as $items) {
            foreach ($items as $i) {
                $flat[] = $i;
            }
        }
        return $flat;
    }

    /** Suggestions de rôles/matières regroupées par CATÉGORIE (pour l'affichage). */
    public static function domainCategories(): array
    {
        return [
            '👨‍🏫 Enseignement'            => ['Prof de maths', 'Prof de français', "Prof d'anglais", "Prof d'histoire", 'Prof de sciences', "Prof d'informatique", 'Prof de philosophie', 'Prof de lingala', 'Mentor', 'Coach', 'Tuteur·rice'],
            '🗣️ Langues'                  => ['Lingala', 'Lari', 'Wolof', 'Xhosa', 'Créole martiniquais', 'Créole guyanais', 'Anglais', 'Français', 'Espagnol', 'Arabe'],
            '🔬 Sciences'                 => ['Mathématiques', 'Physique', 'Chimie', 'Informatique', 'SVT / Biologie'],
            '📖 Sciences humaines'        => ['Histoire africaine', 'Philosophie', 'Géographie'],
            '🎨 Arts & culture'           => ['Musique', 'Arts plastiques', 'Danse', 'Théâtre'],
            '💪 Vie pratique & bien-être' => ['Sport', 'Santé', 'Cuisine'],
            '🤝 Communauté'               => ['Bénévole', 'Étudiant·e', 'Entrepreneuriat'],
        ];
    }

    /** Transforme la chaîne « domains » (séparée par virgules) en liste propre. */
    public static function domainsToList(?string $domains): array
    {
        $out = [];
        foreach (explode(',', (string) $domains) as $d) {
            $d = trim($d);
            if ($d !== '' && !in_array($d, $out, true)) {
                $out[] = $d;
            }
        }
        return $out;
    }

    /** Enregistre les rôles / domaines d'un membre (liste séparée par des virgules). */
    public static function setDomains(int $id, string $domains): void
    {
        Database::pdo()->prepare('UPDATE users SET domains = ? WHERE id = ?')
            ->execute([mb_substr(trim($domains), 0, 255), $id]);
    }

    /** Heartbeat « en ligne » : marque l'utilisateur actif maintenant. */
    public static function touchSeen(int $id): void
    {
        if ($id > 0) {
            Database::pdo()->prepare('UPDATE users SET last_seen = NOW() WHERE id = ?')->execute([$id]);
        }
    }

    /** L'utilisateur est-il « en ligne » ? (activité dans les 2 dernières minutes) */
    public static function isOnline(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT (last_seen IS NOT NULL AND last_seen >= (NOW() - INTERVAL 2 MINUTE)) FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    /** Pays d'Afrique du membre (un ou plusieurs, séparés par des virgules). */
    public static function setCountries(int $id, array $countries): void
    {
        $clean = [];
        foreach ($countries as $c) {
            $c = trim((string) $c);
            if ($c !== '' && !in_array($c, $clean, true)) {
                $clean[] = $c;
            }
        }
        $clean = array_slice($clean, 0, 10);
        $val = implode(', ', $clean);
        Database::pdo()->prepare('UPDATE users SET countries = ? WHERE id = ?')
            ->execute([$val === '' ? null : mb_substr($val, 0, 255), $id]);
    }

    /** Adresse / ville du membre (affichée dans l'annuaire, sert à la recherche). */
    public static function setAddress(int $id, ?string $address): void
    {
        $address = trim((string) $address);
        Database::pdo()->prepare('UPDATE users SET address = ? WHERE id = ?')
            ->execute([$address === '' ? null : mb_substr($address, 0, 190), $id]);
    }

    /**
     * Recherche LARGE de membres trouvables : par nom, matière/domaine,
     * adresse ou ville. Sert au « rechercher un profil » des actions rapides.
     */
    public static function searchProfiles(?string $q = null): array
    {
        $pdo = Database::pdo();
        $q   = trim((string) $q);
        if ($q === '') {
            return $pdo->query("SELECT * FROM users WHERE discoverable = 1 ORDER BY name ASC")->fetchAll();
        }
        $like = '%' . $q . '%';
        $stmt = $pdo->prepare(
            "SELECT * FROM users
             WHERE discoverable = 1
               AND (name LIKE ? OR domains LIKE ? OR countries LIKE ? OR address LIKE ? OR default_city LIKE ? OR member_code = ?)
             ORDER BY name ASC"
        );
        $stmt->execute([$like, $like, $like, $like, $like, strtoupper($q)]);
        return $stmt->fetchAll();
    }

    /** Définit le thème personnel d'un membre (null/'' = thème du site). */
    public static function setThemePref(int $id, ?string $theme): void
    {
        $theme = ($theme === '' ) ? null : $theme;
        Database::pdo()->prepare('UPDATE users SET theme_pref = ? WHERE id = ?')->execute([$theme, $id]);
    }

    /** Supprime un utilisateur. */
    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}

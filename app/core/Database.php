<?php
/**
 * CLASSE Database
 * Connexion MySQL (PDO) partagée. Les identifiants utilisés sont :
 *   - ceux saisis par l'admin au login (gardés en session, JAMAIS dans un fichier)
 *   - sinon les valeurs par défaut de config.php (XAMPP en local)
 */
class Database
{
    private static ?PDO $pdo = null;

    private const SCHEMA_USERS = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `username`   VARCHAR(50)  DEFAULT NULL,
            `google_id`  VARCHAR(64)  DEFAULT NULL,
            `name`       VARCHAR(150) NOT NULL DEFAULT '',
            `email`      VARCHAR(190) DEFAULT NULL,
            `picture`    TEXT,
            `role`       ENUM('membre','admin') NOT NULL DEFAULT 'membre',
            `password`   VARCHAR(255) DEFAULT NULL,
            `blocked`    TINYINT(1)   NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `last_login` TIMESTAMP    NULL DEFAULT NULL,
            `default_city` VARCHAR(190) DEFAULT NULL,
            `default_lat`  DECIMAL(10,7) DEFAULT NULL,
            `default_lng`  DECIMAL(10,7) DEFAULT NULL,
            UNIQUE KEY `uniq_email`    (`email`),
            UNIQUE KEY `uniq_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_ARTICLES = "
        CREATE TABLE IF NOT EXISTS `articles` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `title`       VARCHAR(200) NOT NULL,
            `content`     MEDIUMTEXT   NOT NULL,
            `image`       VARCHAR(255) DEFAULT NULL,
            `template`    VARCHAR(30)  NOT NULL DEFAULT 'standard',
            `active`      TINYINT(1)   NOT NULL DEFAULT 1,
            `parent_id`   INT          DEFAULT NULL,
            `author_id`   INT          DEFAULT NULL,
            `author_name` VARCHAR(150) NOT NULL DEFAULT '',
            `access_password` VARCHAR(255) DEFAULT NULL,   -- mot de passe d'accès (haché) ; NULL = libre
            `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  TIMESTAMP    NULL DEFAULT NULL,
            KEY `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_ARTICLE_IMAGES = "
        CREATE TABLE IF NOT EXISTS `article_images` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT          NOT NULL,
            `filename`   VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_article` (`article_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_ARTICLE_FILES = "
        CREATE TABLE IF NOT EXISTS `article_files` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT          NOT NULL,
            `filename`   VARCHAR(255) NOT NULL,             -- nom stocké dans uploads/articles/files/
            `original`   VARCHAR(255) NOT NULL DEFAULT '',  -- nom d'origine, affiché à l'utilisateur
            `mime`       VARCHAR(120) NOT NULL DEFAULT '',
            `size`       INT          NOT NULL DEFAULT 0,    -- octets
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_article` (`article_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_APPOINTMENTS = "
        CREATE TABLE IF NOT EXISTS `appointments` (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `owner_id`     INT          NOT NULL,             -- membre qui propose le créneau
            `owner_name`   VARCHAR(150) NOT NULL DEFAULT '',
            `title`        VARCHAR(200) NOT NULL,
            `description`  TEXT,
            `start_at`     DATETIME     NOT NULL,             -- date + heure du rendez-vous
            `duration_min` INT          NOT NULL DEFAULT 60,
            `capacity`     INT          NOT NULL DEFAULT 5,   -- nombre de places
            `mode`         VARCHAR(20)  NOT NULL DEFAULT 'presentiel', -- 'presentiel' | 'en_ligne'
            `location`     VARCHAR(255) NOT NULL DEFAULT '',  -- adresse (présentiel) ou lien visio (en ligne)
            `visibility`   VARCHAR(10)  NOT NULL DEFAULT 'public', -- 'public' | 'private'
            `show_booker_ratings` TINYINT(1) NOT NULL DEFAULT 0, -- 0 = notes des inscrits MASQUÉES par défaut
            `min_notice_hours` INT       NOT NULL DEFAULT 0,    -- délai min. de réservation avant le début (heures)
            `code`         VARCHAR(8)   NOT NULL DEFAULT '',   -- code d'accès (privé) : 2 lettres + 3 chiffres
            `lat`          DECIMAL(10,7) DEFAULT NULL,         -- coordonnées du lieu (présentiel) pour la recherche par proximité
            `lng`          DECIMAL(10,7) DEFAULT NULL,
            `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_code` (`code`),
            KEY `idx_owner` (`owner_id`),
            KEY `idx_start` (`start_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_APPOINTMENT_BOOKINGS = "
        CREATE TABLE IF NOT EXISTS `appointment_bookings` (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `appointment_id` INT          NOT NULL,
            `user_id`        INT          NOT NULL,           -- membre qui réserve une place
            `user_name`      VARCHAR(150) NOT NULL DEFAULT '',
            `present`        TINYINT(1)   DEFAULT NULL,         -- présence : NULL=non renseigné, 1=présent, 0=absent
            `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_booking` (`appointment_id`, `user_id`),
            KEY `idx_appt` (`appointment_id`),
            KEY `idx_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_APPOINTMENT_CHANGES = "
        CREATE TABLE IF NOT EXISTS `appointment_changes` (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `appointment_id` INT          NOT NULL,
            `field`          VARCHAR(30)  NOT NULL DEFAULT 'location',
            `old_value`      VARCHAR(300) NOT NULL DEFAULT '',
            `new_value`      VARCHAR(300) NOT NULL DEFAULT '',
            `changed_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_appt` (`appointment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_APPOINTMENT_IMAGES = "
        CREATE TABLE IF NOT EXISTS `appointment_images` (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `appointment_id` INT          NOT NULL,
            `filename`       VARCHAR(255) NOT NULL,
            `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_appt` (`appointment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_APPOINTMENT_RATINGS = "
        CREATE TABLE IF NOT EXISTS `appointment_ratings` (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `appointment_id` INT       NOT NULL,
            `user_id`        INT       NOT NULL,            -- participant qui note l'événement
            `stars`          TINYINT   NOT NULL DEFAULT 0,  -- 1 à 5
            `comment`        VARCHAR(500) DEFAULT NULL,
            `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`     TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY `uniq_evt_rating` (`appointment_id`, `user_id`),
            KEY `idx_appt` (`appointment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_URGENT_DISMISSALS = "
        CREATE TABLE IF NOT EXISTS `urgent_dismissals` (
            `id`        INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`   INT          NOT NULL,
            `item_type` VARCHAR(10)  NOT NULL,            -- 'article' | 'event'
            `item_id`   INT          NOT NULL,
            `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_dismiss` (`user_id`, `item_type`, `item_id`),
            KEY `idx_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_ARTICLE_REVIEWS = "
        CREATE TABLE IF NOT EXISTS `article_reviews` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT          NOT NULL,
            `user_id`    INT          NOT NULL,
            `user_name`  VARCHAR(150) NOT NULL DEFAULT '',
            `stars`      TINYINT      NOT NULL DEFAULT 0,   -- 1 à 5
            `comment`    TEXT,
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP    NULL DEFAULT NULL,
            UNIQUE KEY `uniq_review` (`article_id`, `user_id`),
            KEY `idx_article` (`article_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_ARTICLE_COMMENTS = "
        CREATE TABLE IF NOT EXISTS `article_comments` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT          NOT NULL,
            `user_id`    INT          NOT NULL,
            `user_name`  VARCHAR(150) NOT NULL DEFAULT '',
            `body`       TEXT         NOT NULL,
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_article` (`article_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_ARTICLE_FLAGS = "
        CREATE TABLE IF NOT EXISTS `article_flags` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT       NOT NULL,
            `user_id`    INT       NOT NULL,            -- membre qui signale
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_aflag` (`article_id`, `user_id`),
            KEY `idx_article` (`article_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_ARTICLE_QUIZZES = "
        CREATE TABLE IF NOT EXISTS `article_quizzes` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT NOT NULL,
            `quiz_id`    INT NOT NULL,                   -- questionnaire proposé à la fin de l'article
            `position`   INT NOT NULL DEFAULT 0,
            UNIQUE KEY `uniq_aq` (`article_id`, `quiz_id`),
            KEY `idx_article` (`article_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_COMMENT_FLAGS = "
        CREATE TABLE IF NOT EXISTS `comment_flags` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `comment_id` INT       NOT NULL,
            `user_id`    INT       NOT NULL,            -- membre qui signale
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_flag` (`comment_id`, `user_id`),
            KEY `idx_comment` (`comment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_ARTICLE_VIEWS = "
        CREATE TABLE IF NOT EXISTS `article_views` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT          NOT NULL,
            `ip`         VARCHAR(45)  NOT NULL,            -- IPv4 ou IPv6 du visiteur
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_view` (`article_id`, `ip`),  -- une IP = une vue par article
            KEY `idx_article` (`article_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_ARTICLE_MEMBER_VIEWS = "
        CREATE TABLE IF NOT EXISTS `article_member_views` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT          NOT NULL,
            `user_id`    INT          NOT NULL,         -- membre inscrit ayant vu l'article
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP    NULL DEFAULT NULL,  -- dernière consultation
            UNIQUE KEY `uniq_member_view` (`article_id`, `user_id`),
            KEY `idx_article` (`article_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_NOTIFICATIONS = "
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`    INT          NOT NULL,            -- destinataire de la notification
            `icon`       VARCHAR(16)  NOT NULL DEFAULT '🔔',
            `message`    VARCHAR(500) NOT NULL,
            `link`       VARCHAR(255) NOT NULL DEFAULT '', -- route interne à ouvrir (facultatif)
            `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_user` (`user_id`, `is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_MEMBER_RATINGS = "
        CREATE TABLE IF NOT EXISTS `member_ratings` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `owner_id`   INT       NOT NULL,            -- membre noté (l'hôte du créneau)
            `rater_id`   INT       NOT NULL,            -- membre qui note
            `stars`      TINYINT   NOT NULL DEFAULT 0,  -- 1 à 5
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY `uniq_rating` (`owner_id`, `rater_id`),
            KEY `idx_owner` (`owner_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    // ---- Questionnaires interactifs (quiz noté) -----------------------------
    private const SCHEMA_QUIZZES = "
        CREATE TABLE IF NOT EXISTS `quizzes` (
            `id`            INT AUTO_INCREMENT PRIMARY KEY,
            `title`         VARCHAR(200) NOT NULL,
            `description`   TEXT,
            `image`         VARCHAR(255) DEFAULT NULL,               -- couverture (uploads/quizzes/)
            `active`        TINYINT(1)   NOT NULL DEFAULT 1,         -- 1 = publié, 0 = brouillon
            `urgent`        TINYINT(1)   NOT NULL DEFAULT 0,         -- 1 = alerte sur le tableau de bord de tous
            `required`      TINYINT(1)   NOT NULL DEFAULT 0,         -- 1 = obligatoire (bloque l'app, admin seulement)
            `pass_required` TINYINT(1)   NOT NULL DEFAULT 0,         -- 1 = il faut TOUT réussir pour continuer
            `max_attempts`  INT          NOT NULL DEFAULT 0,         -- nombre max de tentatives (0 = illimité)
            `author_id`     INT          DEFAULT NULL,
            `author_name`   VARCHAR(150) NOT NULL DEFAULT '',
            `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`    TIMESTAMP    NULL DEFAULT NULL,
            KEY `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_QUIZ_QUESTIONS = "
        CREATE TABLE IF NOT EXISTS `quiz_questions` (
            `id`       INT AUTO_INCREMENT PRIMARY KEY,
            `quiz_id`  INT          NOT NULL,
            `body`     VARCHAR(500) NOT NULL,                        -- l'énoncé de la question
            `type`     VARCHAR(10)  NOT NULL DEFAULT 'single',       -- 'single' (1 bonne réponse) | 'multiple'
            `position` INT          NOT NULL DEFAULT 0,
            KEY `idx_quiz` (`quiz_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_QUIZ_OPTIONS = "
        CREATE TABLE IF NOT EXISTS `quiz_options` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `question_id` INT          NOT NULL,
            `label`       VARCHAR(300) NOT NULL,                     -- une réponse possible
            `is_correct`  TINYINT(1)   NOT NULL DEFAULT 0,           -- 1 = bonne réponse
            `position`    INT          NOT NULL DEFAULT 0,
            KEY `idx_question` (`question_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_QUIZ_RESPONSES = "
        CREATE TABLE IF NOT EXISTS `quiz_responses` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `quiz_id`    INT          NOT NULL,
            `user_id`    INT          NOT NULL,                      -- membre qui a répondu
            `user_name`  VARCHAR(150) NOT NULL DEFAULT '',
            `score`      INT          NOT NULL DEFAULT 0,            -- bonnes questions
            `total`      INT          NOT NULL DEFAULT 0,            -- nombre de questions
            `attempts`   INT          NOT NULL DEFAULT 1,            -- nombre de tentatives effectuées
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_response` (`quiz_id`, `user_id`),
            KEY `idx_quiz` (`quiz_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_QUIZ_ANSWERS = "
        CREATE TABLE IF NOT EXISTS `quiz_answers` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `response_id` INT NOT NULL,                              -- la participation concernée
            `question_id` INT NOT NULL,
            `option_id`   INT NOT NULL,                              -- option cochée par le membre
            KEY `idx_response` (`response_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    private const SCHEMA_MESSAGES = "
        CREATE TABLE IF NOT EXISTS `messages` (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `sender_id`    INT NOT NULL,                 -- expéditeur
            `recipient_id` INT NOT NULL,                 -- destinataire
            `body`         TEXT NOT NULL,
            `is_read`      TINYINT(1) NOT NULL DEFAULT 0, -- lu par le destinataire ?
            `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_pair` (`sender_id`,`recipient_id`),
            KEY `idx_inbox` (`recipient_id`,`is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    /**
     * Identifiants effectifs : ceux saisis par l'admin (session) en priorité,
     * sinon les valeurs par défaut de config.php.
     */
    private static function creds(): array
    {
        $s = $_SESSION['db'] ?? [];
        return [
            'host' => $s['host'] ?? DB_HOST,
            'name' => $s['name'] ?? DB_NAME,
            'user' => $s['user'] ?? DB_USER,
            'pass' => $s['pass'] ?? DB_PASS,
        ];
    }

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $c = self::creds();
        try {
            self::$pdo = self::open($c['host'], $c['name'], $c['user'], $c['pass']);
        } catch (PDOException $e) {
            // Base absente (XAMPP local) → on la crée puis on réessaie
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                self::createDatabase($c);
                self::$pdo = self::open($c['host'], $c['name'], $c['user'], $c['pass']);
            } else {
                self::fail($e);
            }
        }

        // Aligne l'heure de MySQL sur Paris (NOW() et comparaisons de dates).
        // date('P') = décalage courant de Europe/Paris (+01:00/+02:00, gère l'heure d'été).
        try { self::$pdo->exec("SET time_zone = '" . date('P') . "'"); } catch (\Throwable $e) {}

        // Garantit que les tables existent (utile sur un hébergeur où la base existe déjà)
        self::$pdo->exec(self::SCHEMA_USERS);
        self::$pdo->exec(self::SCHEMA_ARTICLES);
        self::$pdo->exec(self::SCHEMA_ARTICLE_IMAGES);
        self::$pdo->exec(self::SCHEMA_ARTICLE_FILES);
        self::$pdo->exec(self::SCHEMA_APPOINTMENTS);
        self::$pdo->exec(self::SCHEMA_APPOINTMENT_BOOKINGS);
        self::$pdo->exec(self::SCHEMA_APPOINTMENT_CHANGES);
        self::$pdo->exec(self::SCHEMA_APPOINTMENT_IMAGES);
        self::$pdo->exec(self::SCHEMA_APPOINTMENT_RATINGS);
        self::$pdo->exec(self::SCHEMA_URGENT_DISMISSALS);
        self::$pdo->exec(self::SCHEMA_ARTICLE_REVIEWS);
        self::$pdo->exec(self::SCHEMA_ARTICLE_COMMENTS);
        self::$pdo->exec(self::SCHEMA_ARTICLE_FLAGS);
        self::$pdo->exec(self::SCHEMA_ARTICLE_QUIZZES);
        self::$pdo->exec(self::SCHEMA_COMMENT_FLAGS);
        self::$pdo->exec(self::SCHEMA_ARTICLE_VIEWS);
        self::$pdo->exec(self::SCHEMA_ARTICLE_MEMBER_VIEWS);
        self::$pdo->exec(self::SCHEMA_NOTIFICATIONS);
        self::$pdo->exec(self::SCHEMA_MEMBER_RATINGS);
        self::$pdo->exec(self::SCHEMA_QUIZZES);
        self::$pdo->exec(self::SCHEMA_QUIZ_QUESTIONS);
        self::$pdo->exec(self::SCHEMA_QUIZ_OPTIONS);
        self::$pdo->exec(self::SCHEMA_QUIZ_RESPONSES);
        self::$pdo->exec(self::SCHEMA_QUIZ_ANSWERS);
        self::$pdo->exec(self::SCHEMA_MESSAGES);
        // Paiements Stripe (dons/cotisations ponctuels + abonnements).
        self::$pdo->exec(
            "CREATE TABLE IF NOT EXISTS `payments` (
                `id`            INT AUTO_INCREMENT PRIMARY KEY,
                `user_id`       INT NOT NULL,
                `type`          VARCHAR(20) NOT NULL DEFAULT 'payment',   -- payment | subscription
                `amount`        INT NOT NULL DEFAULT 0,                   -- en centimes
                `currency`      VARCHAR(8) NOT NULL DEFAULT 'eur',
                `status`        VARCHAR(20) NOT NULL DEFAULT 'pending',   -- pending | paid | failed | canceled
                `description`   VARCHAR(190) DEFAULT NULL,
                `session_id`    VARCHAR(255) DEFAULT NULL,                -- id de la session Stripe Checkout
                `customer_id`   VARCHAR(255) DEFAULT NULL,
                `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_user` (`user_id`),
                KEY `idx_session` (`session_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        // Réinitialisation de mot de passe (jetons à usage unique, expirables).
        self::$pdo->exec(
            "CREATE TABLE IF NOT EXISTS `password_resets` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `user_id`    INT NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `used`       TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_token` (`token_hash`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        // Salons visio enregistrés par les membres (historique « Mes salons »).
        self::$pdo->exec(
            "CREATE TABLE IF NOT EXISTS `meeting_links` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `user_id`    INT NOT NULL,
                `url`        VARCHAR(255) NOT NULL,
                `label`      VARCHAR(120) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Petites migrations pour les bases déjà créées avant l'ajout de colonnes.
        self::ensureColumn('articles', 'template', "VARCHAR(30) NOT NULL DEFAULT 'standard' AFTER `image`");
        self::ensureColumn('articles', 'active', "TINYINT(1) NOT NULL DEFAULT 1 AFTER `template`");
        self::ensureColumn('articles', 'parent_id', "INT DEFAULT NULL AFTER `active`");
        self::ensureColumn('articles', 'protected', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`"); // protégé contre l'effacement global
        self::ensureColumn('articles', 'announcement', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `protected`"); // mis en avant sur l'accueil
        self::ensureColumn('articles', 'urgent', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `announcement`"); // alerte URGENT (carré rouge)
        self::ensureColumn('articles', 'position', "INT NOT NULL DEFAULT 0 AFTER `urgent`"); // ordre manuel sur l'accueil (petit = en premier)
        self::ensureColumn('articles', 'gallery_style', "VARCHAR(20) NOT NULL DEFAULT 'auto' AFTER `position`"); // style d'affichage des images multiples
        self::ensureColumn('articles', 'tags', "VARCHAR(255) DEFAULT NULL AFTER `gallery_style`"); // mots-clés / catégories (séparés par des virgules)
        self::ensureColumn('articles', 'access_password', "VARCHAR(255) DEFAULT NULL AFTER `author_name`"); // mot de passe d'accès (haché)
        self::ensureColumn('appointments', 'mode', "VARCHAR(20) NOT NULL DEFAULT 'presentiel' AFTER `capacity`");
        self::ensureColumn('appointments', 'location', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `mode`");
        self::ensureColumn('appointments', 'visibility', "VARCHAR(10) NOT NULL DEFAULT 'public' AFTER `location`");
        self::ensureColumn('appointments', 'show_booker_ratings', "TINYINT(1) NOT NULL DEFAULT 1 AFTER `visibility`");
        self::ensureColumn('appointments', 'min_notice_hours', "INT NOT NULL DEFAULT 0 AFTER `show_booker_ratings`");
        self::ensureColumn('appointment_bookings', 'present', "TINYINT(1) DEFAULT NULL AFTER `user_name`");
        self::ensureColumn('appointment_bookings', 'reminded', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `present`"); // rappel ~1h avant déjà envoyé ?
        // Messagerie : pièce jointe facultative (fichier partagé) par message.
        self::ensureColumn('messages', 'file', "VARCHAR(255) DEFAULT NULL AFTER `body`");
        self::ensureColumn('messages', 'file_name', "VARCHAR(255) DEFAULT NULL AFTER `file`");
        self::ensureColumn('appointments', 'code', "VARCHAR(8) NOT NULL DEFAULT '' AFTER `visibility`");
        self::ensureColumn('appointments', 'protected', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `code`"); // protégé contre l'effacement global
        self::ensureColumn('appointments', 'urgent', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `protected`"); // alerte URGENT (carré rouge)
        self::ensureColumn('appointments', 'lat', "DECIMAL(10,7) DEFAULT NULL AFTER `code`");
        self::ensureColumn('appointments', 'lng', "DECIMAL(10,7) DEFAULT NULL AFTER `lat`");
        self::ensureColumn('users', 'default_city', "VARCHAR(190) DEFAULT NULL AFTER `last_login`");
        self::ensureColumn('users', 'default_lat', "DECIMAL(10,7) DEFAULT NULL AFTER `default_city`");
        self::ensureColumn('users', 'default_lng', "DECIMAL(10,7) DEFAULT NULL AFTER `default_lat`");
        // Consentement à être ajouté aux activités (0 = non trouvable par défaut)
        // + code membre « 1 lettre + 3 chiffres » pour la recherche directe.
        self::ensureColumn('users', 'discoverable', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `blocked`");
        self::ensureColumn('users', 'member_code', "VARCHAR(8) DEFAULT NULL AFTER `discoverable`");
        // Thème PERSONNEL du membre (s'applique seulement à lui ; NULL = thème du site).
        self::ensureColumn('users', 'theme_pref', "VARCHAR(30) DEFAULT NULL AFTER `member_code`");
        // Rôle(s) / domaine(s) du membre, choisis dans son profil (liste séparée par des virgules).
        self::ensureColumn('users', 'domains', "VARCHAR(255) DEFAULT NULL AFTER `theme_pref`");
        self::ensureColumn('users', 'address', "VARCHAR(190) DEFAULT NULL AFTER `domains`");
        self::ensureColumn('users', 'countries', "VARCHAR(255) DEFAULT NULL AFTER `address`");
        self::ensureColumn('users', 'last_seen', "TIMESTAMP NULL DEFAULT NULL AFTER `last_login`");
        // Questionnaires : alerte urgente + caractère obligatoire (bloque l'app).
        self::ensureColumn('quizzes', 'image', "VARCHAR(255) DEFAULT NULL AFTER `description`");
        self::ensureColumn('quizzes', 'urgent', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`");
        self::ensureColumn('quizzes', 'required', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `urgent`");
        self::ensureColumn('quizzes', 'pass_required', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `required`");
        self::ensureColumn('quizzes', 'max_attempts', "INT NOT NULL DEFAULT 0 AFTER `pass_required`");
        // Options d'affichage : questions une par une, retour immédiat, effets visuels.
        self::ensureColumn('quizzes', 'one_by_one', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `max_attempts`");
        self::ensureColumn('quizzes', 'instant_feedback', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `one_by_one`");
        self::ensureColumn('quizzes', 'effects', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `instant_feedback`");
        self::ensureColumn('quizzes', 'time_limit', "INT NOT NULL DEFAULT 0 AFTER `effects`");        // secondes (0 = pas de chrono)
        self::ensureColumn('quizzes', 'shuffle', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `time_limit`"); // mélange questions + réponses
        self::ensureColumn('quizzes', 'pass_threshold', "INT NOT NULL DEFAULT 0 AFTER `shuffle`");    // % minimum pour réussir (0 = aucun)
        self::ensureColumn('quizzes', 'msg_pass', "VARCHAR(255) DEFAULT NULL AFTER `pass_threshold`"); // message si réussi
        self::ensureColumn('quizzes', 'msg_fail', "VARCHAR(255) DEFAULT NULL AFTER `msg_pass`");       // message si échoué
        self::ensureColumn('quiz_questions', 'image', "VARCHAR(255) DEFAULT NULL AFTER `body`"); // image propre à chaque question
        self::ensureColumn('quiz_questions', 'explanation', "VARCHAR(500) DEFAULT NULL AFTER `image`"); // explication « pourquoi »
        self::ensureColumn('quiz_responses', 'attempts', "INT NOT NULL DEFAULT 1 AFTER `total`");

        // Migration ponctuelle : « une question à la fois » devient le défaut.
        // On bascule TOUS les quiz existants une seule fois (les changements faits
        // ensuite, quiz par quiz, sont respectés car cela ne s'exécute qu'une fois).
        if ((int) Settings::get('qz_one_by_one_applied', 0) !== 1) {
            try {
                self::$pdo->exec('UPDATE quizzes SET one_by_one = 1');
                try { self::$pdo->exec('ALTER TABLE quizzes ALTER one_by_one SET DEFAULT 1'); } catch (\Throwable $e2) { /* selon version MySQL */ }
                Settings::save(['qz_one_by_one_applied' => 1]);
            } catch (\Throwable $e) { /* non critique : réessaiera au prochain chargement */ }
        }

        return self::$pdo;
    }

    /**
     * Ajoute une colonne à une table si elle n'existe pas encore (migration simple).
     * Sans danger : si la colonne est déjà là, on ne fait rien.
     */
    private static function ensureColumn(string $table, string $column, string $definition): void
    {
        try {
            // $table/$column viennent du code (jamais d'une saisie) : interpolation sûre.
            // On évite un placeholder ? car SHOW COLUMNS ne le supporte pas en requête native.
            $cols = self::$pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array($column, $cols, true)) {
                self::$pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        } catch (PDOException $e) {
            // Migration non critique : on ignore en cas d'échec.
        }
    }

    /**
     * Teste un couple (nom de base / mot de passe) en se connectant réellement.
     * Sur Hostinger l'utilisateur = le nom de la base. Sert au login admin.
     */
    public static function tryConnect(string $host, string $name, string $user, string $pass): bool
    {
        try {
            self::open($host, $name, $user, $pass);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Vérifie un mot de passe en se connectant réellement avec les identifiants
     * EFFECTIFS de la base (host/nom/utilisateur courants) + le mot de passe
     * fourni. Sert à confirmer les actions sensibles (effacement total).
     */
    public static function verifyPassword(string $pass): bool
    {
        $c = self::creds();
        return self::tryConnect($c['host'], $c['name'], $c['user'], $pass);
    }

    /**
     * Enregistre les identifiants dans config/db.php (fichier protégé) pour que
     * TOUT le site (membres compris) puisse accéder à la base. Appelé quand
     * l'admin se connecte avec succès. Écrit seulement en ligne (en local,
     * la config par défaut suffit).
     */
    public static function persist(array $db): bool
    {
        if (IS_LOCAL) {
            return true; // en local, la config par défaut suffit
        }
        $content = "<?php\n"
            . "// Généré à la connexion admin — NE PAS publier. Supprime ce fichier pour réinitialiser.\n"
            . "define('DB_HOST',    " . var_export($db['host'], true) . ");\n"
            . "define('DB_NAME',    " . var_export($db['name'], true) . ");\n"
            . "define('DB_USER',    " . var_export($db['user'], true) . ");\n"
            . "define('DB_PASS',    " . var_export($db['pass'], true) . ");\n"
            . "define('DB_CHARSET', 'utf8mb4');\n";

        // On tente storage/ (inscriptible) puis config/ en secours
        foreach ([__DIR__ . '/../../storage/db.php', __DIR__ . '/../../config/db.php'] as $path) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (@file_put_contents($path, $content, LOCK_EX) !== false) {
                return true;
            }
        }
        return false; // aucune écriture possible (droits du dossier)
    }

    /** Ouvre une connexion PDO. */
    private static function open(string $host, ?string $name, string $user, string $pass): PDO
    {
        $dsn = 'mysql:host=' . $host
             . ($name ? ';dbname=' . $name : '')
             . ';charset=' . DB_CHARSET;

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    /** Crée la base de données si elle n'existe pas (cas XAMPP local). */
    private static function createDatabase(array $c): void
    {
        try {
            $server = self::open($c['host'], null, $c['user'], $c['pass']);
            $server->exec("CREATE DATABASE IF NOT EXISTS `{$c['name']}`
                           DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            self::fail($e);
        }
    }

    /**
     * La base est-elle « non configurée » ? (en ligne, sans identifiants).
     * Test CHEAP (aucune connexion) : sert au garde global du routeur pour
     * afficher la page d'installation partout tant que ce n'est pas réglé.
     */
    public static function needsSetup(): bool
    {
        if (IS_LOCAL) {
            return false;                       // XAMPP local : config par défaut
        }
        if (!empty($_SESSION['db']['user'])) {
            return false;                       // admin connecté (identifiants en session)
        }
        return !(defined('DB_USER') && DB_USER !== ''); // aucun db.php / identifiant
    }

    private static function fail(PDOException $e): never
    {
        // En ligne → page d'installation ; en local → astuce XAMPP.
        if (!IS_LOCAL) {
            self::installPage(htmlspecialchars($e->getMessage()));
        }
        exit(
            'Erreur de connexion à la base de données.<br>'
            . 'En local, vérifie que <b>MySQL est démarré</b> dans XAMPP.<br><br>'
            . '<small>' . htmlspecialchars($e->getMessage()) . '</small>'
        );
    }

    /**
     * Page d'INSTALLATION (forçage de la configuration de la base), affichée par
     * le garde global tant que la base n'est pas configurée — quelle que soit la
     * page demandée. C'est un vrai FORMULAIRE auto-traité (aucun contrôleur ni
     * route nécessaire) :
     *   1. l'utilisateur saisit hôte / base / utilisateur / mot de passe ;
     *   2. on VÉRIFIE la connexion réelle (tryConnect) — rien n'est enregistré si
     *      elle échoue ;
     *   3. on ENREGISTRE partout (session + config/db.php ou storage/db.php) via
     *      persist(), puis on redirige : tout le site est reconnecté.
     * La vérification a lieu à CHAQUE requête (le garde appelle needsSetup()).
     *
     * $msg : message d'erreur éventuel transmis par fail() (connexion perdue).
     */
    public static function installPage(string $msg = ''): never
    {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        $home = ($base !== '' ? $base : '') . '/';
        $action = $base . '/install';

        // Jeton anti-CSRF (la session est démarrée au bootstrap).
        if (empty($_SESSION['_install_csrf'])) {
            $_SESSION['_install_csrf'] = bin2hex(random_bytes(16));
        }
        $csrf = $_SESSION['_install_csrf'];

        // Valeurs (pré-remplissage du formulaire).
        $host = trim((string) ($_POST['db_host'] ?? 'localhost'));
        $name = trim((string) ($_POST['db_name'] ?? ''));
        $user = trim((string) ($_POST['db_user'] ?? ''));
        $pass = (string) ($_POST['db_pass'] ?? '');

        $error  = $msg;
        $manual = '';

        // ---- Traitement de la soumission ------------------------------------
        $submitted = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') && isset($_POST['db_install']);
        if ($submitted) {
            if (!hash_equals($csrf, (string) ($_POST['_csrf'] ?? ''))) {
                $error = 'Session expirée, réessaie.';
            } elseif ($name === '' || $user === '') {
                $error = 'Le nom de la base et l\'utilisateur sont obligatoires.';
            } elseif (!self::tryConnect($host, $name, $user, $pass)) {
                $error = 'Connexion refusée : vérifie le nom de la base, l\'utilisateur et le mot de passe '
                       . '(hPanel → Bases de données MySQL).';
            } else {
                // Vérification OK → on débloque ce navigateur tout de suite…
                $_SESSION['db'] = ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass];
                // …et on écrit le fichier pour reconnecter TOUT le monde.
                if (self::persist(['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass])) {
                    if (!headers_sent()) {
                        http_response_code(302);
                        header('Location: ' . $home);
                    }
                    exit;
                }
                // Connexion OK mais écriture impossible (droits) → config manuelle.
                $fileContent = "<?php\n"
                    . "define('DB_HOST',    " . var_export($host, true) . ");\n"
                    . "define('DB_NAME',    " . var_export($name, true) . ");\n"
                    . "define('DB_USER',    " . var_export($user, true) . ");\n"
                    . "define('DB_PASS',    " . var_export($pass, true) . ");\n"
                    . "define('DB_CHARSET', 'utf8mb4');\n";
                $error  = 'Connexion réussie, mais impossible d\'écrire le fichier de config (droits du dossier). '
                        . 'Crée manuellement le fichier config/db.php avec ce contenu :';
                $manual = '<pre>' . htmlspecialchars($fileContent) . '</pre>';
            }
        }

        // ---- Rendu du formulaire --------------------------------------------
        if (!headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
        }
        $eHost = htmlspecialchars($host);
        $eName = htmlspecialchars($name);
        $eUser = htmlspecialchars($user);
        $eCsrf = htmlspecialchars($csrf);
        $errBlock = $error !== '' ? '<div class="err">' . htmlspecialchars($error) . '</div>' . $manual : '';
        $logout = $base . '/admin/logout';

        exit(<<<HTML
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Installation — Configuration de la base</title>
<style>
 *{box-sizing:border-box;margin:0;padding:0}
 body{font-family:'Segoe UI',system-ui,Arial,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;
   padding:24px;background:radial-gradient(circle at 15% 0%,rgba(230,57,70,.25),transparent 45%),
   radial-gradient(circle at 85% 120%,rgba(42,157,74,.25),transparent 48%),#14110f;color:#fff}
 .box{max-width:480px;width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(244,193,75,.3);border-radius:18px;
   padding:30px 28px;box-shadow:0 24px 64px rgba(0,0,0,.5)}
 .ico{font-size:38px;text-align:center}
 h1{font-size:21px;color:#f4c14b;margin:6px 0 6px;text-align:center}
 p.lead{color:rgba(255,255,255,.78);font-size:13.5px;line-height:1.6;margin-bottom:18px;text-align:center}
 label{display:block;font-size:12.5px;color:rgba(255,255,255,.7);margin:12px 0 5px}
 input{width:100%;padding:11px 13px;border-radius:10px;border:1px solid rgba(255,255,255,.18);
   background:rgba(0,0,0,.25);color:#fff;font-size:14px}
 input:focus{outline:2px solid #f4c14b;border-color:transparent}
 .btn{width:100%;margin-top:20px;border:none;cursor:pointer;font-weight:700;background:#f4c14b;color:#14110f;
   padding:13px;border-radius:12px;font-size:15px}
 .err{background:rgba(230,57,70,.18);border:1px solid rgba(230,57,70,.5);color:#ffd7db;
   padding:11px 13px;border-radius:10px;font-size:13px;margin-bottom:14px;line-height:1.5}
 .err pre{margin-top:8px;background:#0b0d12;border:1px solid rgba(255,255,255,.12);border-radius:8px;
   padding:10px;font-size:11.5px;color:#d7dce5;overflow:auto;white-space:pre-wrap;word-break:break-word}
 .hint{font-size:12px;color:rgba(255,255,255,.5);margin-top:6px;line-height:1.5}
 .reset{display:block;text-align:center;margin-top:16px;color:rgba(255,255,255,.55);font-size:12.5px}
</style></head><body>
 <form class="box" method="post" action="$action" autocomplete="off">
   <div class="ico">🔧</div>
   <h1>Configuration de la base de données</h1>
   <p class="lead">Le site n'est pas encore connecté à sa base. Renseigne les identifiants
      (hPanel → <b>Bases de données MySQL</b>). La connexion est <b>vérifiée</b> avant l'enregistrement.</p>
   $errBlock
   <input type="hidden" name="_csrf" value="$eCsrf">
   <input type="hidden" name="db_install" value="1">
   <label>Hôte</label>
   <input name="db_host" value="$eHost" placeholder="localhost">
   <label>Nom de la base</label>
   <input name="db_name" value="$eName" placeholder="u123456789_rpn" required>
   <label>Utilisateur</label>
   <input name="db_user" value="$eUser" placeholder="u123456789_rpn" required>
   <label>Mot de passe</label>
   <input type="password" name="db_pass" placeholder="mot de passe MySQL">
   <button class="btn" type="submit">Vérifier et installer →</button>
   <div class="hint">Sur Hostinger mutualisé, l'hôte est en général <b>localhost</b>. Le nom de la base et
      l'utilisateur commencent souvent par <b>u…_</b>.</div>
   <a class="reset" href="$logout">Réinitialiser la session</a>
 </form>
</body></html>
HTML);
    }
}

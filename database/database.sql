-- =====================================================================
--  Base de données du projet RPN
--  À importer dans phpMyAdmin (http://localhost/phpmyadmin)
-- =====================================================================

-- 1. Créer la base si elle n'existe pas
CREATE DATABASE IF NOT EXISTS `rpm`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `rpm`;

-- 2. Table des utilisateurs (membres + admins)
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(50)  DEFAULT NULL,      -- identifiant admin (ex: root, admin...)
    `google_id`  VARCHAR(64)  DEFAULT NULL,
    `name`       VARCHAR(150) NOT NULL DEFAULT '',
    `email`      VARCHAR(190) DEFAULT NULL,      -- rempli pour les membres Google
    `picture`    TEXT,
    `role`       ENUM('membre','admin') NOT NULL DEFAULT 'membre',
    `password`   VARCHAR(255) DEFAULT NULL,      -- uniquement pour les admins (haché)
    `blocked`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP    NULL DEFAULT NULL,
    `default_city` VARCHAR(190) DEFAULT NULL,         -- ville par défaut pour la recherche par proximité
    `default_lat`  DECIMAL(10,7) DEFAULT NULL,        -- coordonnées mémorisées de cette ville
    `default_lng`  DECIMAL(10,7) DEFAULT NULL,
    UNIQUE KEY `uniq_email`    (`email`),
    UNIQUE KEY `uniq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table des articles (contenu publié par les admins)
CREATE TABLE IF NOT EXISTS `articles` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `title`       VARCHAR(200) NOT NULL,
    `content`     MEDIUMTEXT   NOT NULL,
    `image`       VARCHAR(255) DEFAULT NULL,      -- nom du fichier dans uploads/articles/
    `template`    VARCHAR(30)  NOT NULL DEFAULT 'standard',  -- mise en page (standard/magazine/minimal/cote/carte)
    `active`      TINYINT(1)   NOT NULL DEFAULT 1,           -- 1 = publié (public), 0 = brouillon (privé)
    `parent_id`   INT          DEFAULT NULL,                 -- article parent (NULL = article de premier niveau)
    `author_id`   INT          DEFAULT NULL,
    `author_name` VARCHAR(150) NOT NULL DEFAULT '',
    `access_password` VARCHAR(255) DEFAULT NULL,           -- mot de passe d'accès (haché) ; NULL = libre
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NULL DEFAULT NULL,
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Photos supplémentaires (galerie) d'un article
CREATE TABLE IF NOT EXISTS `article_images` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT          NOT NULL,
    `filename`   VARCHAR(255) NOT NULL,             -- nom du fichier dans uploads/articles/
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Pièces jointes (documents : PDF, Word…) d'un article
CREATE TABLE IF NOT EXISTS `article_files` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT          NOT NULL,
    `filename`   VARCHAR(255) NOT NULL,             -- nom stocké dans uploads/articles/files/
    `original`   VARCHAR(255) NOT NULL DEFAULT '',  -- nom d'origine, affiché à l'utilisateur
    `mime`       VARCHAR(120) NOT NULL DEFAULT '',
    `size`       INT          NOT NULL DEFAULT 0,    -- taille en octets
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Créneaux de rendez-vous (agenda d'un membre)
CREATE TABLE IF NOT EXISTS `appointments` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id`     INT          NOT NULL,
    `owner_name`   VARCHAR(150) NOT NULL DEFAULT '',
    `title`        VARCHAR(200) NOT NULL,
    `description`  TEXT,
    `start_at`     DATETIME     NOT NULL,
    `duration_min` INT          NOT NULL DEFAULT 60,
    `capacity`     INT          NOT NULL DEFAULT 5,
    `mode`         VARCHAR(20)  NOT NULL DEFAULT 'presentiel', -- 'presentiel' | 'en_ligne'
    `location`     VARCHAR(255) NOT NULL DEFAULT '',  -- adresse (présentiel) ou lien visio
    `visibility`   VARCHAR(10)  NOT NULL DEFAULT 'public', -- 'public' | 'private'
    `show_booker_ratings` TINYINT(1) NOT NULL DEFAULT 1, -- 1 = afficher la note globale des inscrits à l'hôte
    `min_notice_hours` INT       NOT NULL DEFAULT 0,    -- délai minimum de réservation avant le début (en heures)
    `code`         VARCHAR(8)   NOT NULL DEFAULT '',   -- code d'accès (privé) : 2 lettres + 3 chiffres
    `lat`          DECIMAL(10,7) DEFAULT NULL,         -- coordonnées (présentiel) pour la recherche par proximité
    `lng`          DECIMAL(10,7) DEFAULT NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_owner` (`owner_id`),
    KEY `idx_start` (`start_at`),
    KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Réservations de place sur un créneau
CREATE TABLE IF NOT EXISTS `appointment_bookings` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `appointment_id` INT          NOT NULL,
    `user_id`        INT          NOT NULL,
    `user_name`      VARCHAR(150) NOT NULL DEFAULT '',
    `present`        TINYINT(1)   DEFAULT NULL,      -- présence : NULL=non renseigné, 1=présent, 0=absent
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_booking` (`appointment_id`, `user_id`),
    KEY `idx_appt` (`appointment_id`),
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Historique des modifications du lieu d'un créneau
CREATE TABLE IF NOT EXISTS `appointment_changes` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `appointment_id` INT          NOT NULL,
    `field`          VARCHAR(30)  NOT NULL DEFAULT 'location',
    `old_value`      VARCHAR(300) NOT NULL DEFAULT '',
    `new_value`      VARCHAR(300) NOT NULL DEFAULT '',
    `changed_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_appt` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Avis des membres sur les articles (note + commentaire)
CREATE TABLE IF NOT EXISTS `article_reviews` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT          NOT NULL,
    `user_id`    INT          NOT NULL,
    `user_name`  VARCHAR(150) NOT NULL DEFAULT '',
    `stars`      TINYINT      NOT NULL DEFAULT 0,
    `comment`    TEXT,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NULL DEFAULT NULL,
    UNIQUE KEY `uniq_review` (`article_id`, `user_id`),
    KEY `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Discussion (fil de messages) d'un article
CREATE TABLE IF NOT EXISTS `article_comments` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT          NOT NULL,
    `user_id`    INT          NOT NULL,
    `user_name`  VARCHAR(150) NOT NULL DEFAULT '',
    `body`       TEXT         NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12b. Signalements d'articles (3 membres distincts → article masqué au public)
CREATE TABLE IF NOT EXISTS `article_flags` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT       NOT NULL,
    `user_id`    INT       NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_aflag` (`article_id`, `user_id`),
    KEY `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12c. Questionnaires associés à un article (proposés à la fin de la lecture)
CREATE TABLE IF NOT EXISTS `article_quizzes` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT NOT NULL,
    `quiz_id`    INT NOT NULL,
    `position`   INT NOT NULL DEFAULT 0,
    UNIQUE KEY `uniq_aq` (`article_id`, `quiz_id`),
    KEY `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Signalements de messages de discussion
CREATE TABLE IF NOT EXISTS `comment_flags` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `comment_id` INT       NOT NULL,
    `user_id`    INT       NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_flag` (`comment_id`, `user_id`),
    KEY `idx_comment` (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Vues des articles, comptées par adresse IP (une IP = une vue par article)
CREATE TABLE IF NOT EXISTS `article_views` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT          NOT NULL,
    `ip`         VARCHAR(45)  NOT NULL,             -- IPv4 ou IPv6 du visiteur
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_view` (`article_id`, `ip`),   -- une IP ne compte qu'une fois
    KEY `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Membres inscrits ayant consulté un article (un membre = une ligne par article)
CREATE TABLE IF NOT EXISTS `article_member_views` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT          NOT NULL,
    `user_id`    INT          NOT NULL,            -- membre inscrit ayant vu l'article
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- première consultation
    `updated_at` TIMESTAMP    NULL DEFAULT NULL,   -- dernière consultation
    UNIQUE KEY `uniq_member_view` (`article_id`, `user_id`),
    KEY `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Notifications in-app (confirmations de réservation, etc.) par destinataire
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT          NOT NULL,            -- destinataire
    `icon`       VARCHAR(16)  NOT NULL DEFAULT '🔔',
    `message`    VARCHAR(500) NOT NULL,
    `link`       VARCHAR(255) NOT NULL DEFAULT '', -- route interne à ouvrir (facultatif)
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Notes (étoiles) attribuées par un membre à un autre (hôtes de créneaux)
CREATE TABLE IF NOT EXISTS `member_ratings` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id`   INT       NOT NULL,
    `rater_id`   INT       NOT NULL,
    `stars`      TINYINT   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uniq_rating` (`owner_id`, `rater_id`),
    KEY `idx_owner` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Questionnaires interactifs (quiz noté)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Questions d'un questionnaire
CREATE TABLE IF NOT EXISTS `quiz_questions` (
    `id`       INT AUTO_INCREMENT PRIMARY KEY,
    `quiz_id`  INT          NOT NULL,
    `body`     VARCHAR(500) NOT NULL,                        -- l'énoncé
    `type`     VARCHAR(10)  NOT NULL DEFAULT 'single',       -- 'single' (1 bonne réponse) | 'multiple'
    `position` INT          NOT NULL DEFAULT 0,
    KEY `idx_quiz` (`quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Réponses possibles de chaque question
CREATE TABLE IF NOT EXISTS `quiz_options` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `question_id` INT          NOT NULL,
    `label`       VARCHAR(300) NOT NULL,
    `is_correct`  TINYINT(1)   NOT NULL DEFAULT 0,           -- 1 = bonne réponse
    `position`    INT          NOT NULL DEFAULT 0,
    KEY `idx_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Participation d'un membre (1 par membre et par questionnaire)
CREATE TABLE IF NOT EXISTS `quiz_responses` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `quiz_id`    INT          NOT NULL,
    `user_id`    INT          NOT NULL,
    `user_name`  VARCHAR(150) NOT NULL DEFAULT '',
    `score`      INT          NOT NULL DEFAULT 0,            -- bonnes questions
    `total`      INT          NOT NULL DEFAULT 0,            -- nombre de questions
    `attempts`   INT          NOT NULL DEFAULT 1,            -- nombre de tentatives effectuées
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_response` (`quiz_id`, `user_id`),
    KEY `idx_quiz` (`quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Options cochées par le membre dans sa participation
CREATE TABLE IF NOT EXISTS `quiz_answers` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `response_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `option_id`   INT NOT NULL,
    KEY `idx_response` (`response_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  SI tu as DÉJÀ importé l'ancienne version, exécute plutôt ceci :
-- =====================================================================
-- ALTER TABLE `users`
--   ADD COLUMN `username` VARCHAR(50) DEFAULT NULL AFTER `id`,
--   MODIFY COLUMN `email` VARCHAR(190) DEFAULT NULL,
--   ADD UNIQUE KEY `uniq_username` (`username`);

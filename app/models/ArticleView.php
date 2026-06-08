<?php
/**
 * MODÈLE ArticleView
 * Comptage des vues d'un article PAR ADRESSE IP : chaque IP ne compte qu'une
 * seule fois par article (une réactualisation de la page ne gonfle pas le total).
 * La contrainte UNIQUE (article_id, ip) garantit cette unicité côté base.
 */
class ArticleView
{
    /**
     * Enregistre une vue pour (article, IP). Sans effet si cette IP a déjà été
     * comptée pour cet article (INSERT IGNORE). Une IP vide est ignorée.
     */
    public static function record(int $articleId, string $ip): void
    {
        $ip = trim($ip);
        if ($articleId <= 0 || $ip === '') {
            return;
        }
        $stmt = Database::pdo()->prepare(
            'INSERT IGNORE INTO article_views (article_id, ip) VALUES (?, ?)'
        );
        $stmt->execute([$articleId, $ip]);
    }

    /** Nombre d'IP distinctes ayant vu un article. */
    public static function count(int $articleId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM article_views WHERE article_id = ?');
        $stmt->execute([$articleId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Nombre de vues pour plusieurs articles d'un coup : [article_id => count].
     * Utile pour la liste des articles (évite une requête par carte).
     */
    public static function countsFor(array $articleIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $articleIds)));
        if (!$ids) {
            return [];
        }
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT article_id, COUNT(*) AS cnt FROM article_views
             WHERE article_id IN ($in) GROUP BY article_id"
        );
        $stmt->execute($ids);
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[(int) $r['article_id']] = (int) $r['cnt'];
        }
        return $map;
    }

    /**
     * Enregistre la consultation d'un article par un MEMBRE inscrit.
     * Un membre = une seule ligne par article ; les consultations suivantes
     * mettent simplement à jour `updated_at` (dernière visite).
     */
    public static function recordMember(int $articleId, int $userId): void
    {
        if ($articleId <= 0 || $userId <= 0) {
            return;
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO article_member_views (article_id, user_id) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE updated_at = NOW()'
        );
        $stmt->execute([$articleId, $userId]);
    }

    /**
     * Liste des membres inscrits ayant vu un article (du plus récent au plus ancien),
     * avec leur nom / e-mail / photo pour l'affichage.
     */
    public static function members(int $articleId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.name, u.email, u.picture, u.role,
                    v.created_at AS first_view, v.updated_at AS last_view
             FROM article_member_views v
             JOIN users u ON u.id = v.user_id
             WHERE v.article_id = ?
             ORDER BY COALESCE(v.updated_at, v.created_at) DESC'
        );
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }

    /**
     * Liste des adresses IP ayant vu l'article (une par IP), de la plus récente
     * à la plus ancienne. Permet à l'auteur/admin d'identifier les visiteurs
     * NON connectés (qui n'apparaissent pas dans la liste des membres).
     */
    public static function ips(int $articleId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT ip, created_at FROM article_views WHERE article_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }

    /** Nombre de membres inscrits distincts ayant vu un article. */
    public static function memberCount(int $articleId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM article_member_views WHERE article_id = ?');
        $stmt->execute([$articleId]);
        return (int) $stmt->fetchColumn();
    }

    /** Supprime toutes les vues d'un article (appelé à la suppression de l'article). */
    public static function deleteForArticle(int $articleId): void
    {
        Database::pdo()->prepare('DELETE FROM article_views WHERE article_id = ?')->execute([$articleId]);
        Database::pdo()->prepare('DELETE FROM article_member_views WHERE article_id = ?')->execute([$articleId]);
    }
}

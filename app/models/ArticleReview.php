<?php
/**
 * MODÈLE ArticleReview
 * Avis des membres sur un article : une note (1 à 5 étoiles) + un commentaire.
 * Un avis par membre et par article (modifiable). Réservé aux membres connectés.
 */
class ArticleReview
{
    /** Enregistre ou met à jour l'avis d'un membre sur un article. */
    public static function set(int $articleId, int $userId, string $userName, int $stars, string $comment): void
    {
        $stars = max(1, min(5, $stars));
        $stmt = Database::pdo()->prepare(
            'INSERT INTO article_reviews (article_id, user_id, user_name, stars, comment) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE stars = VALUES(stars), comment = VALUES(comment),
                                     user_name = VALUES(user_name), updated_at = NOW()'
        );
        $stmt->execute([$articleId, $userId, $userName, $stars, $comment]);
    }

    /** Tous les avis d'un article (les plus récents d'abord). */
    public static function forArticle(int $articleId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM article_reviews WHERE article_id = ? ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }

    /** Moyenne + nombre d'avis d'un article. */
    public static function summary(int $articleId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT AVG(stars) AS avg, COUNT(*) AS cnt FROM article_reviews WHERE article_id = ?'
        );
        $stmt->execute([$articleId]);
        $r = $stmt->fetch();
        return ['avg' => (float) ($r['avg'] ?? 0), 'count' => (int) ($r['cnt'] ?? 0)];
    }

    /** Moyenne + nombre pour plusieurs articles : [article_id => ['avg','count']]. */
    public static function summaryFor(array $articleIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $articleIds))));
        if (!$ids) {
            return [];
        }
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT article_id, AVG(stars) AS avg, COUNT(*) AS cnt
             FROM article_reviews WHERE article_id IN ($in) GROUP BY article_id"
        );
        $stmt->execute($ids);
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[(int) $r['article_id']] = ['avg' => (float) $r['avg'], 'count' => (int) $r['cnt']];
        }
        return $map;
    }

    /** L'avis d'un membre sur un article (ou null). */
    public static function mine(int $articleId, int $userId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM article_reviews WHERE article_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$articleId, $userId]);
        return $stmt->fetch() ?: null;
    }

    /** Supprime tous les avis d'un article (à sa suppression). */
    public static function deleteForArticle(int $articleId): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM article_reviews WHERE article_id = ?');
        $stmt->execute([$articleId]);
    }
}

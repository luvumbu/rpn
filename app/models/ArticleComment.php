<?php
/**
 * MODÈLE ArticleComment
 * Fil de discussion d'un article : messages des membres (plusieurs par membre).
 * Distinct des avis (ArticleReview = une note + un commentaire par membre).
 */
class ArticleComment
{
    /** Ajoute un message au fil de discussion. */
    public static function add(int $articleId, int $userId, string $userName, string $body): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO article_comments (article_id, user_id, user_name, body) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$articleId, $userId, $userName, $body]);
    }

    /** Tous les messages d'un article (du plus ancien au plus récent). */
    public static function forArticle(int $articleId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM article_comments WHERE article_id = ? ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }

    /** Un message par son id (ou null). */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM article_comments WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Supprime un message (et ses signalements). */
    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM comment_flags WHERE comment_id = ?')->execute([$id]);
        Database::pdo()->prepare('DELETE FROM article_comments WHERE id = ?')->execute([$id]);
    }

    /** Supprime tous les messages d'un article (et leurs signalements). */
    public static function deleteForArticle(int $articleId): void
    {
        Database::pdo()->prepare(
            'DELETE f FROM comment_flags f JOIN article_comments c ON c.id = f.comment_id WHERE c.article_id = ?'
        )->execute([$articleId]);
        Database::pdo()->prepare('DELETE FROM article_comments WHERE article_id = ?')->execute([$articleId]);
    }

    /** Signale un message (un signalement par membre ; sans effet si déjà signalé). */
    public static function flag(int $commentId, int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT IGNORE INTO comment_flags (comment_id, user_id) VALUES (?, ?)'
        );
        $stmt->execute([$commentId, $userId]);
    }

    /** Nombre de signalements par message pour un article : [comment_id => count]. */
    public static function flagCounts(int $articleId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT f.comment_id, COUNT(*) AS cnt FROM comment_flags f
             JOIN article_comments c ON c.id = f.comment_id
             WHERE c.article_id = ? GROUP BY f.comment_id'
        );
        $stmt->execute([$articleId]);
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[(int) $r['comment_id']] = (int) $r['cnt'];
        }
        return $map;
    }

    /** Ids des messages déjà signalés par un membre (dans cet article). */
    public static function userFlags(int $articleId, int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT f.comment_id FROM comment_flags f
             JOIN article_comments c ON c.id = f.comment_id
             WHERE c.article_id = ? AND f.user_id = ?'
        );
        $stmt->execute([$articleId, $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}

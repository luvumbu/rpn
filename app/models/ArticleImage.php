<?php
/**
 * MODÈLE ArticleImage
 * Photos supplémentaires (galerie) rattachées à un article.
 * L'image de couverture reste dans articles.image ; cette table gère les
 * photos additionnelles.
 */
class ArticleImage
{
    /** Toutes les photos d'un article (les plus anciennes d'abord). */
    public static function forArticle(int $articleId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM article_images WHERE article_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }

    /** Une photo par son id. */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM article_images WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Ajoute une photo à un article. */
    public static function add(int $articleId, string $filename): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO article_images (article_id, filename) VALUES (?, ?)'
        );
        $stmt->execute([$articleId, $filename]);
    }

    /** Supprime une photo (la ligne ; le fichier est géré par le contrôleur). */
    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM article_images WHERE id = ?');
        $stmt->execute([$id]);
    }
}

<?php
/**
 * MODÈLE ArticleFile
 * Pièces jointes (PDF, documents…) rattachées à un article.
 * Les fichiers sont rangés dans uploads/articles/files/ ; cette table garde
 * le nom stocké, le nom d'origine (affiché), le type MIME et la taille.
 */
class ArticleFile
{
    /** Toutes les pièces jointes d'un article (les plus anciennes d'abord). */
    public static function forArticle(int $articleId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM article_files WHERE article_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }

    /** Une pièce jointe par son id. */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM article_files WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Ajoute une pièce jointe à un article. */
    public static function add(int $articleId, array $f): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO article_files (article_id, filename, original, mime, size) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $articleId,
            $f['filename'],
            $f['original'] ?? '',
            $f['mime']     ?? '',
            (int) ($f['size'] ?? 0),
        ]);
    }

    /** Supprime une pièce jointe (la ligne ; le fichier est géré par le contrôleur). */
    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM article_files WHERE id = ?');
        $stmt->execute([$id]);
    }
}

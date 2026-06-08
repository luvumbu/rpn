<?php
/**
 * CLASSE ArticleStyle
 * « Modules » de personnalisation GLOBALE de l'affichage des articles,
 * réglables depuis l'admin (Articles → Style des articles).
 * Chaque module = un réglage stocké dans Settings et appliqué au contenu
 * des articles (.article-body) via css().
 *
 * Pour ajouter un module : un réglage ici (lecture), un champ dans la vue
 * admin/articles/style, sa sauvegarde dans AdminArticleController::saveStyle().
 */
class ArticleStyle
{
    /** Polices disponibles : clé => famille CSS. */
    public static function fonts(): array
    {
        return [
            'moderne'   => 'Poppins, sans-serif',
            'classique' => "Georgia, 'Times New Roman', serif",
            'machine'   => "'Courier New', monospace",
        ];
    }

    /** Taille du texte en % par rapport à la normale (bornée 70–200). */
    public static function scale(): int
    {
        $v = (int) Settings::get('art_text_scale', 100);
        return max(70, min(200, $v));
    }

    /** Police personnalisée active ? */
    public static function fontEnabled(): bool
    {
        return (int) Settings::get('art_font_enabled', 0) === 1;
    }

    /** Clé de police choisie (validée). */
    public static function fontKey(): string
    {
        $k = (string) Settings::get('art_font', 'moderne');
        return array_key_exists($k, self::fonts()) ? $k : 'moderne';
    }

    /** Famille CSS de la police personnalisée, ou null si désactivée. */
    public static function fontFamily(): ?string
    {
        return self::fontEnabled() ? self::fonts()[self::fontKey()] : null;
    }

    /**
     * Règle CSS à injecter dans la page d'article (.article-body).
     * Valeurs maîtrisées (admin + bornage) → injection sûre.
     */
    public static function css(): string
    {
        $decls = 'font-size:' . self::scale() . '%;';
        if ($family = self::fontFamily()) {
            $decls .= 'font-family:' . $family . ';';
        }
        return '.article-body{' . $decls . '}';
    }
}

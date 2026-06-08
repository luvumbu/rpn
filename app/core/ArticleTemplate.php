<?php
/**
 * CLASSE ArticleTemplate
 * Liste des modèles de mise en page (« templates ») disponibles pour un article.
 * Chaque article enregistre la clé de son modèle ; la page d'article (show.view)
 * adapte sa disposition en fonction. Pour ajouter un modèle : une entrée dans
 * groups() + le CSS correspondant (.tpl-<clé>) dans le partial _article.css.php
 * (et, si la STRUCTURE diffère, un cas dans _card.php).
 */
class ArticleTemplate
{
    /**
     * Modèles regroupés par famille (pour un menu déroulant lisible).
     * groupe affiché => [ clé => libellé ].
     */
    public static function groups(): array
    {
        return [
            'Simple' => [
                'standard' => 'Standard — image en haut, puis le texte',
                'minimal'  => 'Minimal — sans grande image, texte centré',
                'carte'    => 'Carte — format étroit, centré',
            ],
            'Couverture en vedette' => [
                'magazine' => 'Magazine — grande couverture, titre sur l\'image',
                'affiche'  => 'Affiche — image plein cadre, titre centré dessus',
                'vitrine'  => 'Vitrine — grande image, titre centré dessous',
                'moderne'  => 'Moderne — très grand titre, image arrondie',
            ],
            'Panoramique / large' => [
                'pleine'   => 'Pleine largeur — grande image, texte large',
                'bandeau'  => 'Bandeau — fine image panoramique en tête',
            ],
            'Image à côté du texte' => [
                'cote'     => 'Côte à côte — image à gauche, texte à droite',
                'portrait' => 'Portrait — image à droite, texte à gauche',
                'fiche'    => 'Fiche — petite image à côté, format compact',
            ],
            'Lecture / presse' => [
                'journal'   => 'Journal — texte en deux colonnes',
                'classique' => 'Classique — colonne étroite, texte justifié',
                'elegant'   => 'Élégant — larges marges, lecture aérée',
                'encadre'   => 'Encadré — texte dans un cadre accentué',
            ],
            'Animé / interactif' => [
                'carrousel'    => 'Carrousel — photos défilantes (flèches, points, lecture auto)',
                'diaporama'    => 'Diaporama — fondu enchaîné automatique, titre sur l\'image',
                'galerie-zoom' => 'Galerie animée — défilement continu, grandes vignettes et zoom au survol',
            ],
        ];
    }

    /**
     * Modèles « carrousel » : la couverture et la galerie sont fusionnées en un
     * diaporama interactif dans la carte (au lieu de l'image fixe + grille).
     * Utilisé par _card.php (rendu) et par les vues (pour masquer la galerie en
     * doublon) — la liste reste ainsi définie à un seul endroit.
     */
    public static function carouselKeys(): array
    {
        return ['carrousel', 'diaporama'];
    }

    /** Tous les modèles à plat : clé => libellé (dérivé des groupes). */
    public static function all(): array
    {
        $out = [];
        foreach (self::groups() as $items) {
            $out += $items;
        }
        return $out;
    }

    /** Clé valide (sinon « standard » par défaut). */
    public static function key(?string $key): string
    {
        $key = (string) $key;
        return array_key_exists($key, self::all()) ? $key : 'standard';
    }
}

<?php
/**
 * CLASSE GlobalStyle
 * Style GLOBAL du site entier (toutes les pages), réglable depuis l'admin
 * (Admin → Style global). Complète le THÈME (couleurs) en pilotant le « ressenti »
 * commun à tous les éléments : police, arrondi des cartes/boutons, intensité des
 * ombres, animations. Les réglages sont stockés dans Settings et injectés dans le
 * <head> de chaque page via Theme::css().
 *
 * Pour ajouter un réglage : une lecture ici + un champ dans admin/style.view.php
 * + sa sauvegarde dans AdminController::saveGlobalStyle().
 */
class GlobalStyle
{
    /** Polices disponibles : clé => ['label', 'css' (famille), 'google' (param ou '')]. */
    public static function fonts(): array
    {
        return [
            'theme'        => ['label' => 'Police du thème (Poppins)',      'css' => "'Poppins', sans-serif",                                   'google' => ''],
            'system'       => ['label' => 'Système (rapide)',               'css' => "system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif", 'google' => ''],
            'inter'        => ['label' => 'Inter',                          'css' => "'Inter', sans-serif",        'google' => 'Inter:wght@400;500;700;800'],
            'montserrat'   => ['label' => 'Montserrat',                     'css' => "'Montserrat', sans-serif",   'google' => 'Montserrat:wght@400;600;800'],
            'roboto'       => ['label' => 'Roboto',                         'css' => "'Roboto', sans-serif",       'google' => 'Roboto:wght@400;500;700'],
            'nunito'       => ['label' => 'Nunito (arrondie)',              'css' => "'Nunito', sans-serif",       'google' => 'Nunito:wght@400;600;800'],
            'lora'         => ['label' => 'Lora (serif élégant)',           'css' => "'Lora', serif",              'google' => 'Lora:wght@400;600;700'],
            'merriweather' => ['label' => 'Merriweather (serif)',           'css' => "'Merriweather', serif",      'google' => 'Merriweather:wght@400;700'],
            'georgia'      => ['label' => 'Georgia (serif classique)',      'css' => "Georgia, 'Times New Roman', serif", 'google' => ''],
            'mono'         => ['label' => 'Machine à écrire (mono)',        'css' => "'Courier New', monospace",   'google' => ''],
        ];
    }

    /** Niveaux d'ombre : clé => valeur CSS ('' = garder celle du thème). */
    public static function shadows(): array
    {
        return [
            'none'   => ['label' => 'Aucune',   'css' => 'none'],
            'soft'   => ['label' => 'Légère',   'css' => '0 8px 26px rgba(0,0,0,.18)'],
            'normal' => ['label' => 'Normale (thème)', 'css' => ''],
            'strong' => ['label' => 'Forte',    'css' => '0 36px 90px rgba(0,0,0,.60)'],
        ];
    }

    /** Une police personnalisée est-elle activée ? */
    public static function fontEnabled(): bool
    {
        return (int) Settings::get('gs_font_enabled', 0) === 1;
    }

    /** Clé de police choisie (validée). */
    public static function fontKey(): string
    {
        $k = (string) Settings::get('gs_font', 'theme');
        return array_key_exists($k, self::fonts()) ? $k : 'theme';
    }

    /** Famille CSS de la police choisie. */
    public static function fontFamily(): string
    {
        return self::fonts()[self::fontKey()]['css'] ?? "'Poppins', sans-serif";
    }

    /** Arrondi global des cartes/boutons en px (borné 0–28). */
    public static function radius(): int
    {
        return max(0, min(28, (int) Settings::get('gs_radius', 16)));
    }

    /** Clé du niveau d'ombre (validée). */
    public static function shadowKey(): string
    {
        $k = (string) Settings::get('gs_shadow', 'normal');
        return array_key_exists($k, self::shadows()) ? $k : 'normal';
    }

    /** Animations/transitions activées sur tout le site ? */
    public static function animationsEnabled(): bool
    {
        return (int) Settings::get('gs_anim', 1) === 1;
    }

    /** Lien Google Fonts de la police choisie (si nécessaire), pour le <head>. */
    public static function fontLink(): string
    {
        if (!self::fontEnabled()) {
            return '';
        }
        $g = self::fonts()[self::fontKey()]['google'] ?? '';
        if ($g === '') {
            return '';
        }
        $href = 'https://fonts.googleapis.com/css2?family=' . $g . '&display=swap';
        return '<link href="' . htmlspecialchars($href) . '" rel="stylesheet">';
    }

    /**
     * Feuille de style globale (overrides en !important pour primer sur les
     * styles de chaque page). Valeurs maîtrisées (admin + bornage) → injection sûre.
     */
    public static function css(): string
    {
        $rules = '';

        // 1) Police globale (corps + champs) — surclasse le font-family des pages.
        if (self::fontEnabled()) {
            $fam = self::fontFamily();
            $rules .= "body, button, input, textarea, select, .article-body { font-family: {$fam} !important; }\n";
        }

        // 2) Arrondi global des surfaces rectangulaires et boutons principaux
        //    (on évite les éléments ronds : avatars, pastilles, boutons cercle).
        $r = self::radius();
        $rules .= ".card, .stat, .action, .note, .slot, .cal-cell, .form-card, .flash, .saved, .error,"
                . " .btn, .act.book, .act.cancel { border-radius: {$r}px !important; }\n";

        // 3) Intensité des ombres : redéfinit la variable consommée partout.
        $shadow = self::shadows()[self::shadowKey()]['css'] ?? '';
        if ($shadow !== '') {
            $rules .= ":root { --card-shadow: {$shadow}; }\n";
        }

        // 4) Animations/transitions globales désactivables.
        if (!self::animationsEnabled()) {
            $rules .= "*, *::before, *::after { animation:none !important; transition:none !important; scroll-behavior:auto !important; }\n";
        }

        if (trim($rules) === '') {
            return '';
        }
        return "<style>\n  /* Style global du site (Admin → Style global) */\n  {$rules}</style>";
    }
}

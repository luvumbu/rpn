<?php
/**
 * CLASSE Africa — référentiel des 54 pays d'Afrique (source unique).
 * Utilisée par la vitrine des drapeaux ET par le profil membre.
 */
class Africa
{
    /** Code ISO 3166-1 alpha-2 => nom français. */
    public static function countries(): array
    {
        $pays = [
            'za' => 'Afrique du Sud', 'dz' => 'Algérie', 'ao' => 'Angola', 'bj' => 'Bénin',
            'bw' => 'Botswana', 'bf' => 'Burkina Faso', 'bi' => 'Burundi', 'cm' => 'Cameroun',
            'cv' => 'Cap-Vert', 'cf' => 'République centrafricaine', 'km' => 'Comores',
            'cg' => 'Congo (Brazzaville)', 'cd' => 'RD Congo', 'ci' => "Côte d'Ivoire",
            'dj' => 'Djibouti', 'eg' => 'Égypte', 'er' => 'Érythrée', 'sz' => 'Eswatini',
            'et' => 'Éthiopie', 'ga' => 'Gabon', 'gm' => 'Gambie', 'gh' => 'Ghana',
            'gn' => 'Guinée', 'gq' => 'Guinée équatoriale', 'gw' => 'Guinée-Bissau',
            'ke' => 'Kenya', 'ls' => 'Lesotho', 'lr' => 'Liberia', 'ly' => 'Libye',
            'mg' => 'Madagascar', 'mw' => 'Malawi', 'ml' => 'Mali', 'ma' => 'Maroc',
            'mu' => 'Maurice', 'mr' => 'Mauritanie', 'mz' => 'Mozambique', 'na' => 'Namibie',
            'ne' => 'Niger', 'ng' => 'Nigeria', 'ug' => 'Ouganda', 'rw' => 'Rwanda',
            'st' => 'Sao Tomé-et-Principe', 'sn' => 'Sénégal', 'sc' => 'Seychelles',
            'sl' => 'Sierra Leone', 'so' => 'Somalie', 'sd' => 'Soudan', 'ss' => 'Soudan du Sud',
            'tz' => 'Tanzanie', 'td' => 'Tchad', 'tg' => 'Togo', 'tn' => 'Tunisie',
            'zm' => 'Zambie', 'zw' => 'Zimbabwe',
        ];
        asort($pays, SORT_LOCALE_STRING);
        return $pays;
    }

    /** Emoji drapeau à partir d'un code pays à 2 lettres (ex. 'sn' → 🇸🇳). */
    public static function flag(string $code): string
    {
        $code = strtolower(trim($code));
        if (strlen($code) !== 2 || !ctype_alpha($code)) {
            return '🌍';
        }
        $a = 0x1F1E6; // 🇦
        return mb_convert_encoding('&#' . ($a + (ord($code[0]) - 97)) . ';', 'UTF-8', 'HTML-ENTITIES')
             . mb_convert_encoding('&#' . ($a + (ord($code[1]) - 97)) . ';', 'UTF-8', 'HTML-ENTITIES');
    }

    /** Liste prête pour les chips : nom => drapeau (triée par nom). */
    public static function flagList(): array
    {
        $out = [];
        foreach (self::countries() as $code => $name) {
            $out[$name] = self::flag($code);
        }
        return $out;
    }

    /** Nettoie une chaîne « pays » (séparée par virgules) en liste. */
    public static function toList(?string $countries): array
    {
        $list = [];
        foreach (explode(',', (string) $countries) as $c) {
            $c = trim($c);
            if ($c !== '' && !in_array($c, $list, true)) {
                $list[] = $c;
            }
        }
        return $list;
    }

    /** Drapeau pour un NOM de pays (ex. 'Sénégal' → 🇸🇳, sinon 🌍). */
    public static function flagForName(string $name): string
    {
        foreach (self::countries() as $code => $n) {
            if (mb_strtolower($n) === mb_strtolower(trim($name))) {
                return self::flag($code);
            }
        }
        return '🌍';
    }
}

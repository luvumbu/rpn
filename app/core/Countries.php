<?php
/**
 * CLASSE Countries — référentiel mondial des pays (nom FR + code ISO).
 * Sert au sélecteur de pays avec autocomplétion et drapeaux dans le profil.
 * Les drapeaux (emoji) sont dérivés du code via Africa::flag().
 */
class Countries
{
    /** Code ISO 3166-1 alpha-2 => nom français. */
    public static function all(): array
    {
        $c = [
            'af' => 'Afghanistan', 'za' => 'Afrique du Sud', 'al' => 'Albanie', 'dz' => 'Algérie',
            'de' => 'Allemagne', 'ad' => 'Andorre', 'ao' => 'Angola', 'ag' => 'Antigua-et-Barbuda',
            'sa' => 'Arabie saoudite', 'ar' => 'Argentine', 'am' => 'Arménie', 'au' => 'Australie',
            'at' => 'Autriche', 'az' => 'Azerbaïdjan', 'bs' => 'Bahamas', 'bh' => 'Bahreïn',
            'bd' => 'Bangladesh', 'bb' => 'Barbade', 'be' => 'Belgique', 'bz' => 'Belize',
            'bj' => 'Bénin', 'bt' => 'Bhoutan', 'by' => 'Biélorussie', 'bo' => 'Bolivie',
            'ba' => 'Bosnie-Herzégovine', 'bw' => 'Botswana', 'br' => 'Brésil', 'bn' => 'Brunei',
            'bg' => 'Bulgarie', 'bf' => 'Burkina Faso', 'bi' => 'Burundi', 'kh' => 'Cambodge',
            'cm' => 'Cameroun', 'ca' => 'Canada', 'cv' => 'Cap-Vert', 'cl' => 'Chili',
            'cn' => 'Chine', 'cy' => 'Chypre', 'co' => 'Colombie', 'km' => 'Comores',
            'cg' => 'Congo (Brazzaville)', 'cd' => 'RD Congo', 'kr' => 'Corée du Sud', 'kp' => 'Corée du Nord',
            'cr' => 'Costa Rica', 'ci' => "Côte d'Ivoire", 'hr' => 'Croatie', 'cu' => 'Cuba',
            'dk' => 'Danemark', 'dj' => 'Djibouti', 'dm' => 'Dominique', 'eg' => 'Égypte',
            'ae' => 'Émirats arabes unis', 'ec' => 'Équateur', 'er' => 'Érythrée', 'es' => 'Espagne',
            'ee' => 'Estonie', 'sz' => 'Eswatini', 'us' => 'États-Unis', 'et' => 'Éthiopie',
            'fj' => 'Fidji', 'fi' => 'Finlande', 'fr' => 'France', 'ga' => 'Gabon',
            'gm' => 'Gambie', 'ge' => 'Géorgie', 'gh' => 'Ghana', 'gr' => 'Grèce',
            'gd' => 'Grenade', 'gt' => 'Guatemala', 'gn' => 'Guinée', 'gq' => 'Guinée équatoriale',
            'gw' => 'Guinée-Bissau', 'gy' => 'Guyana', 'gf' => 'Guyane française', 'ht' => 'Haïti',
            'hn' => 'Honduras', 'hu' => 'Hongrie', 'in' => 'Inde', 'id' => 'Indonésie',
            'iq' => 'Irak', 'ir' => 'Iran', 'ie' => 'Irlande', 'is' => 'Islande',
            'il' => 'Israël', 'it' => 'Italie', 'jm' => 'Jamaïque', 'jp' => 'Japon',
            'jo' => 'Jordanie', 'kz' => 'Kazakhstan', 'ke' => 'Kenya', 'kg' => 'Kirghizistan',
            'ki' => 'Kiribati', 'kw' => 'Koweït', 'la' => 'Laos', 'ls' => 'Lesotho',
            'lv' => 'Lettonie', 'lb' => 'Liban', 'lr' => 'Liberia', 'ly' => 'Libye',
            'li' => 'Liechtenstein', 'lt' => 'Lituanie', 'lu' => 'Luxembourg', 'mk' => 'Macédoine du Nord',
            'mg' => 'Madagascar', 'my' => 'Malaisie', 'mw' => 'Malawi', 'mv' => 'Maldives',
            'ml' => 'Mali', 'mt' => 'Malte', 'ma' => 'Maroc', 'mh' => 'Îles Marshall',
            'mq' => 'Martinique', 'mu' => 'Maurice', 'mr' => 'Mauritanie', 'mx' => 'Mexique',
            'fm' => 'Micronésie', 'md' => 'Moldavie', 'mc' => 'Monaco', 'mn' => 'Mongolie',
            'me' => 'Monténégro', 'mz' => 'Mozambique', 'mm' => 'Birmanie (Myanmar)', 'na' => 'Namibie',
            'nr' => 'Nauru', 'np' => 'Népal', 'ni' => 'Nicaragua', 'ne' => 'Niger',
            'ng' => 'Nigeria', 'no' => 'Norvège', 'nz' => 'Nouvelle-Zélande', 'om' => 'Oman',
            'ug' => 'Ouganda', 'uz' => 'Ouzbékistan', 'pk' => 'Pakistan', 'pw' => 'Palaos',
            'ps' => 'Palestine', 'pa' => 'Panama', 'pg' => 'Papouasie-Nouvelle-Guinée', 'py' => 'Paraguay',
            'nl' => 'Pays-Bas', 'pe' => 'Pérou', 'ph' => 'Philippines', 'pl' => 'Pologne',
            'pt' => 'Portugal', 'qa' => 'Qatar', 'ro' => 'Roumanie', 'gb' => 'Royaume-Uni',
            'ru' => 'Russie', 'rw' => 'Rwanda', 'kn' => 'Saint-Kitts-et-Nevis', 'sm' => 'Saint-Marin',
            'vc' => 'Saint-Vincent-et-les-Grenadines', 'lc' => 'Sainte-Lucie', 'sb' => 'Îles Salomon',
            'sv' => 'Salvador', 'ws' => 'Samoa', 'st' => 'Sao Tomé-et-Principe', 'sn' => 'Sénégal',
            'rs' => 'Serbie', 'sc' => 'Seychelles', 'sl' => 'Sierra Leone', 'sg' => 'Singapour',
            'sk' => 'Slovaquie', 'si' => 'Slovénie', 'so' => 'Somalie', 'sd' => 'Soudan',
            'ss' => 'Soudan du Sud', 'lk' => 'Sri Lanka', 'se' => 'Suède', 'ch' => 'Suisse',
            'sr' => 'Suriname', 'sy' => 'Syrie', 'tj' => 'Tadjikistan', 'tz' => 'Tanzanie',
            'td' => 'Tchad', 'cz' => 'Tchéquie', 'th' => 'Thaïlande', 'tl' => 'Timor oriental',
            'tg' => 'Togo', 'to' => 'Tonga', 'tt' => 'Trinité-et-Tobago', 'tn' => 'Tunisie',
            'tm' => 'Turkménistan', 'tr' => 'Turquie', 'tv' => 'Tuvalu', 'ua' => 'Ukraine',
            'uy' => 'Uruguay', 'vu' => 'Vanuatu', 've' => 'Venezuela', 'vn' => 'Viêt Nam',
            'ye' => 'Yémen', 'zm' => 'Zambie', 'zw' => 'Zimbabwe', 'cf' => 'République centrafricaine',
            'gp' => 'Guadeloupe', 're' => 'La Réunion', 'yt' => 'Mayotte', 'nc' => 'Nouvelle-Calédonie',
            'pf' => 'Polynésie française',
        ];
        asort($c, SORT_LOCALE_STRING);
        return $c;
    }

    /** Liste pour l'autocomplétion : [ ['n'=>nom, 'c'=>code, 'f'=>emoji], ... ]. */
    public static function autocomplete(): array
    {
        $out = [];
        foreach (self::all() as $code => $name) {
            $out[] = ['n' => $name, 'c' => $code, 'f' => Africa::flag($code)];
        }
        return $out;
    }

    /** Code ISO (2 lettres) à partir d'un NOM de pays (sinon ''). */
    public static function codeForName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        foreach (self::all() as $code => $n) {
            if (mb_strtolower($n) === $name) {
                return $code;
            }
        }
        return '';
    }

    /** Drapeau emoji pour un NOM de pays (sinon 🌍). */
    public static function flagForName(string $name): string
    {
        $code = self::codeForName($name);
        return $code !== '' ? Africa::flag($code) : '🌍';
    }

    /** Découpe une chaîne « pays » (virgules) en liste propre. */
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
}

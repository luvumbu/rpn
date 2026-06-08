<?php
/**
 * CLASSE Geocoder
 * Convertit une adresse en coordonnées (lat/lng) via Nominatim (OpenStreetMap),
 * sans clé API. Utilisé pour la recherche de créneaux par proximité.
 *
 * Robuste à l'hébergement : essaie cURL d'abord (le mieux supporté en
 * mutualisé, ex. Hostinger), puis file_get_contents en repli. En cas d'échec
 * réseau, renvoie null → la fonctionnalité se dégrade sans casser.
 */
class Geocoder
{
    public static function geocode(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&accept-language=fr&q='
             . rawurlencode($address);

        $json = self::fetch($url);
        if ($json === null) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
            return null;
        }
        return ['lat' => (float) $data[0]['lat'], 'lng' => (float) $data[0]['lon']];
    }

    /**
     * Suggestions de lieux pour l'autocomplétion (jusqu'à $limit résultats).
     * Renvoie [ ['label'=>..., 'lat'=>..., 'lng'=>...], ... ].
     */
    public static function suggest(string $q, int $limit = 5): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 3) {
            return [];
        }
        $url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=0&limit=' . (int) $limit
             . '&accept-language=fr&q=' . rawurlencode($q);
        $json = self::fetch($url);
        if ($json === null) {
            return [];
        }
        $data = json_decode($json, true);
        $out  = [];
        if (is_array($data)) {
            foreach ($data as $d) {
                if (empty($d['display_name']) || empty($d['lat']) || empty($d['lon'])) {
                    continue;
                }
                $out[] = [
                    'label' => (string) $d['display_name'],
                    'lat'   => (float) $d['lat'],
                    'lng'   => (float) $d['lon'],
                ];
            }
        }
        return $out;
    }

    /** Récupère une URL en HTTPS : cURL si dispo, sinon file_get_contents. */
    private static function fetch(string $url): ?string
    {
        // Nominatim EXIGE un User-Agent identifiable, sinon 403.
        $ua = 'RPN-Agenda/1.0 (contact: admin@bokonzi.com)';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_USERAGENT      => $ua,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);
            $res  = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($res !== false && $code >= 200 && $code < 300) {
                return $res;
            }
            // sinon : on tente le repli ci-dessous
        }

        if (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create(['http' => [
                'method'  => 'GET',
                'header'  => "User-Agent: $ua\r\nAccept: application/json\r\n",
                'timeout' => 8,
            ]]);
            $res = @file_get_contents($url, false, $ctx);
            if ($res !== false) {
                return $res;
            }
        }

        return null;
    }
}

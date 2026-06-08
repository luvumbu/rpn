<?php
/**
 * CONTRÔLEUR GeoController — suggestions de villes/adresses (autocomplétion).
 * Proxy léger vers Nominatim (le User-Agent requis est ajouté par Geocoder).
 */
class GeoController
{
    public function suggest(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Session::has('user')) {
            echo '[]';
            return;
        }
        $q = (string) ($_GET['q'] ?? '');
        echo json_encode(Geocoder::suggest($q, 6), JSON_UNESCAPED_UNICODE);
    }
}

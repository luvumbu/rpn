<?php
/**
 * CLASSE ApiKernel — socle commun des contrôleurs d'API JSON (MVC).
 *
 * Regroupe TOUT ce qui était auparavant dupliqué entre les actions de l'API
 * (createArticle, createQuiz…) :
 *   - lecture du corps de requête (JSON ou formulaire) et accès aux champs ;
 *   - authentification par CLÉ API (en-tête X-API-Key, corps, $_POST ou $_GET) ;
 *   - réponses JSON normalisées (succès / erreur) ;
 *   - construction d'URL absolue vers une route interne ;
 *   - téléchargement d'une image depuis une URL.
 *
 * Tout nouveau contrôleur d'API étend cette classe : la logique transversale
 * (sécurité, parsing, réponses) vit ici une seule fois, jamais recopiée.
 *
 * SÉCURITÉ — points garantis par ce socle :
 *   • requireKey() bloque (401) toute écriture sans clé valide ;
 *   • la comparaison de clé est à temps constant (hash_equals) ;
 *   • aucune requête SQL n'est construite ici : les modèles utilisent
 *     exclusivement des requêtes préparées (PDO) → pas d'injection SQL.
 */
abstract class ApiKernel
{
    /** Corps de requête JSON décodé (rempli par readInput()). */
    protected array $input = [];

    /** Indique si readInput() a déjà été appelé (évite de relire php://input). */
    private bool $inputRead = false;

    /* ------------------------------------------------------------------ *
     *  ENTRÉE : lecture du corps + accès aux champs
     * ------------------------------------------------------------------ */

    /** Lit une seule fois le corps JSON (si Content-Type: application/json). */
    protected function readInput(): void
    {
        if ($this->inputRead) {
            return;
        }
        $this->inputRead = true;

        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ct, 'application/json') !== false) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $this->input = is_array($decoded) ? $decoded : [];
        }
    }

    /** Valeur d'un champ : corps JSON, puis $_POST, puis valeur par défaut. */
    protected function field(string $key, $default = '')
    {
        return $this->input[$key] ?? $_POST[$key] ?? $default;
    }

    /** Champ texte « propre » : chaîne, sans balise HTML, espaces retirés, tronquée. */
    protected function text(string $key, int $max = 0, string $default = ''): string
    {
        $val = trim(strip_tags((string) $this->field($key, $default)));
        return $max > 0 ? mb_substr($val, 0, $max) : $val;
    }

    /* ------------------------------------------------------------------ *
     *  SORTIE : réponses JSON
     * ------------------------------------------------------------------ */

    /** Réponse JSON + arrêt immédiat. */
    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Réponse d'erreur normalisée : { ok:false, error:"…" }. */
    protected function fail(string $message, int $code = 400): void
    {
        $this->json(['ok' => false, 'error' => $message], $code);
    }

    /* ------------------------------------------------------------------ *
     *  SÉCURITÉ : clé API
     * ------------------------------------------------------------------ */

    /**
     * Exige une clé API valide, sinon coupe avec un 401.
     * La clé fournie est cherchée dans : en-tête X-API-Key, corps JSON,
     * $_POST puis $_GET. La clé de référence est Settings « api_key »
     * (régénérable depuis l'admin — régénérer révoque l'ancienne) ; à défaut,
     * la constante API_KEY de config.php.
     */
    protected function requireKey(): void
    {
        $this->readInput(); // pour pouvoir lire une éventuelle clé dans le corps JSON

        $provided = (string) (
            $_SERVER['HTTP_X_API_KEY']
            ?? ($this->input['key'] ?? ($_POST['key'] ?? ($_GET['key'] ?? '')))
        );

        $stored    = (string) Settings::get('api_key', '');
        $reference = $stored !== '' ? $stored : (defined('API_KEY') ? (string) API_KEY : '');

        if ($reference === '' || $provided === '' || !hash_equals($reference, $provided)) {
            $this->fail('Clé API manquante ou invalide.', 401);
        }
    }

    /* ------------------------------------------------------------------ *
     *  UTILITAIRES
     * ------------------------------------------------------------------ */

    /** Construit une URL absolue vers une route interne (+ query éventuelle). */
    protected function absoluteUrl(string $route, array $query = []): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $url    = $scheme . '://' . $host . url($route);
        if ($query) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }

    /**
     * Télécharge une image depuis une URL http(s) et l'enregistre dans le
     * dossier donné. Renvoie le nom de fichier enregistré, ou null en cas
     * d'échec (URL invalide, téléchargement impossible, format refusé).
     */
    protected function downloadImage(string $rawUrl, string $folder = 'articles'): ?string
    {
        $url = trim($rawUrl);
        if (!preg_match('#^https?://#i', $url)) {
            return null;
        }

        $data = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_USERAGENT      => 'RPN/1.0',
            ]);
            $res  = curl_exec($ch);
            $data = $res === false ? null : $res;
            curl_close($ch);
        }
        if ($data === null && ini_get('allow_url_fopen')) {
            $data = @file_get_contents($url);
            if ($data === false) { $data = null; }
        }
        if ($data === null || $data === '') {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'rpnimg_');
        @file_put_contents($tmp, $data);
        try { $name = Upload::imageFromPath($tmp, $folder); }
        catch (\Throwable $e) { $name = null; }
        @unlink($tmp);

        return $name;
    }
}

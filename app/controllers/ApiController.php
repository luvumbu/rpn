<?php
/**
 * CONTRÔLEUR API
 * Petite API JSON pour piloter le site à distance — actuellement : créer des
 * articles. Protégée par une CLÉ API (constante API_KEY dans config.php),
 * passée dans l'en-tête « X-API-Key » ou le paramètre « key ».
 *
 *   POST /rpm/api/article   → crée un article (titre, contenu HTML, etc.)
 *   GET  /rpm/api/ping       → test de disponibilité
 *
 * Permet par exemple de demander à un assistant d'écrire un article et de le
 * publier directement sur le site.
 */
class ApiController
{
    /** Réponse JSON + arrêt. */
    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Vérifie la clé API (en-tête X-API-Key, ou paramètre key). 401 sinon. */
    private function requireKey(): void
    {
        $key = (string) ($_SERVER['HTTP_X_API_KEY'] ?? ($_POST['key'] ?? ($_GET['key'] ?? '')));
        // Clé effective : dès qu'une clé a été générée depuis l'admin (Settings
        // « api_key »), c'est la SEULE valable (régénérer révoque l'ancienne).
        // Sinon, on retombe sur API_KEY de config.php.
        $stored    = (string) Settings::get('api_key', '');
        $reference = $stored !== '' ? $stored : (defined('API_KEY') ? (string) API_KEY : '');
        if ($reference === '' || $key === '' || !hash_equals($reference, $key)) {
            $this->json(['ok' => false, 'error' => 'Clé API manquante ou invalide.'], 401);
        }
    }

    /** Télécharge une image depuis une URL (http/https) et l'enregistre. Renvoie le nom ou null. */
    private function downloadImage(string $url): ?string
    {
        if (!preg_match('#^https?://#i', $url)) {
            return null;
        }
        $data = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 15, CURLOPT_USERAGENT => 'RPN/1.0',
            ]);
            $res = curl_exec($ch);
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
        $tmp = tempnam(sys_get_temp_dir(), 'rpmimg_');
        @file_put_contents($tmp, $data);
        try { $name = Upload::imageFromPath($tmp, 'articles'); }
        catch (\Throwable $e) { $name = null; }
        @unlink($tmp);
        return $name;
    }

    /** Test : GET /rpm/api/ping */
    public function ping(): void
    {
        $this->json(['ok' => true, 'service' => 'RPN API', 'date' => date('c')]);
    }

    /**
     * Crée un article. POST /rpm/api/article
     * Corps accepté : JSON ou form-urlencoded.
     *   title*       (string)  — titre
     *   content*     (string)  — contenu HTML (nettoyé par Html::clean)
     *   template     (string)  — clé de mise en page (défaut « standard »)
     *   active       (0|1)     — 1 = publié (défaut), 0 = brouillon
     *   author_name  (string)  — nom affiché de l'auteur (défaut « RPN »)
     */
    public function createArticle(): void
    {
        $this->requireKey();

        // Accepte JSON (Content-Type: application/json) ou form-urlencoded.
        $in  = [];
        $ct  = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ct, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $in  = json_decode((string) $raw, true) ?: [];
        }
        $field = static function (string $k, $def = '') use ($in) {
            return $in[$k] ?? $_POST[$k] ?? $def;
        };

        $title   = trim((string) $field('title'));
        $content = (string) $field('content');
        if ($title === '' || trim(strip_tags($content)) === '') {
            $this->json(['ok' => false, 'error' => 'Champs requis : title et content.'], 422);
        }

        $template = ArticleTemplate::key((string) $field('template', 'standard'));
        $active   = !empty($field('active', 1)) ? 1 : 0;
        $author   = trim((string) $field('author_name', 'RPN'));
        // Sous-article : parent_id (rattache l'article à un article parent existant).
        $parentId = (int) $field('parent_id', 0);
        $parent   = $parentId > 0 ? Article::find($parentId) : null;
        $parentId = $parent ? (int) $parent['id'] : null;

        // Image de couverture : fichier 'cover' (multipart) OU 'image_url' (téléchargée).
        $cover = null;
        try { $cover = Upload::image('cover', 'articles'); } catch (\Throwable $e) { $cover = null; }
        if (!$cover) {
            $imgUrl = trim((string) $field('image_url', ''));
            if ($imgUrl !== '') { $cover = $this->downloadImage($imgUrl); }
        }

        $id = Article::create([
            'title'       => mb_substr($title, 0, 255),
            'content'     => Html::clean($content),
            'image'       => $cover,
            'template'    => $template,
            'active'      => $active,
            'parent_id'   => $parentId,
            'author_id'   => 0,
            'author_name' => $author !== '' ? mb_substr($author, 0, 190) : 'RPN',
        ]);

        // Galerie : fichiers 'photos[]' (multipart) et/ou 'gallery_urls' (liste d'URL).
        foreach (Upload::images('photos', 'articles') as $g) {
            ArticleImage::add($id, $g);
        }
        $gurls = $field('gallery_urls', []);
        if (is_array($gurls)) {
            foreach ($gurls as $gu) {
                $n = $this->downloadImage((string) $gu);
                if ($n) { ArticleImage::add($id, $n); }
            }
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $this->json([
            'ok'       => true,
            'id'       => $id,
            'active'   => $active,
            'template' => $template,
            'url'      => $scheme . '://' . $host . url('article') . '?id=' . $id,
        ], 201);
    }
}

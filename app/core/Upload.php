<?php
/**
 * CLASSE Upload
 * Envoi sécurisé d'images (articles…).
 *
 * Les images ne sont PAS refusées si elles sont lourdes : elles sont
 * automatiquement redimensionnées (côté le plus long ≤ 1200 px) et
 * recompressées via GD, ce qui réduit fortement leur poids. On valide
 * le type réel, on génère un nom unique, puis on range le fichier dans
 * uploads/<sous-dossier>/.
 */
class Upload
{
    /** Plafond absolu du fichier reçu (garde-fou anti-abus). */
    private const MAX_BYTES = 25 * 1024 * 1024;   // 25 Mo

    /** Côté le plus long de l'image enregistrée (taille utile à l'affichage). */
    private const MAX_DIM = 1200;

    /** Limite de pixels en entrée (évite de saturer la mémoire). */
    private const MAX_PIXELS = 40000000;          // ~40 mégapixels

    private const JPEG_QUALITY = 82;
    private const WEBP_QUALITY  = 82;
    private const PNG_LEVEL     = 6;

    /** Types acceptés → extension de sortie. */
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Traite le champ d'upload $field (ex: 'image').
     * Retourne le nom de fichier stocké (à mettre en base), ou null si aucun
     * fichier valide. Lève une RuntimeException en cas d'erreur explicite.
     */
    public static function image(string $field, string $subdir): ?string
    {
        $f = $_FILES[$field] ?? null;
        if (!is_array($f) || is_array($f['name'] ?? null)) {
            return null; // champ absent ou champ multiple → rien à faire ici
        }
        return self::store($f, $subdir);
    }

    /**
     * Enregistre une image depuis un FICHIER LOCAL (pas un upload HTTP) — ex.
     * image téléchargée par l'API via une URL. Valide le type réel, redimensionne
     * et range dans uploads/<subdir>/. Retourne le nom stocké (ou null).
     */
    public static function imageFromPath(string $src, string $subdir): ?string
    {
        if (!is_file($src)) {
            return null;
        }
        $info = @getimagesize($src);
        $mime = $info['mime'] ?? '';
        if (!isset(self::TYPES[$mime])) {
            throw new RuntimeException('Format non autorisé (JPG, PNG, GIF ou WEBP uniquement).');
        }
        $ext = self::TYPES[$mime];

        $dir = APP_ROOT . '/uploads/' . $subdir;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Impossible de créer le dossier d'upload.");
        }
        $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        self::process($src, $dir . '/' . $name, $ext, (int) $info[0], (int) $info[1]);
        return $name;
    }

    /**
     * Plusieurs images (champ multiple, ex: name="photos[]").
     * Retourne la liste des noms de fichiers enregistrés. Une photo invalide
     * est ignorée et n'empêche pas les autres d'être traitées.
     */
    public static function images(string $field, string $subdir): array
    {
        $f = $_FILES[$field] ?? null;
        if (!is_array($f) || !is_array($f['name'] ?? null)) {
            return [];
        }

        $out   = [];
        $count = count($f['name']);
        for ($i = 0; $i < $count; $i++) {
            if ((int) ($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue; // case vide
            }
            $single = [
                'name'     => $f['name'][$i],
                'type'     => $f['type'][$i],
                'tmp_name' => $f['tmp_name'][$i],
                'error'    => $f['error'][$i],
                'size'     => $f['size'][$i],
            ];
            try {
                $name = self::store($single, $subdir);
                if ($name !== null) {
                    $out[] = $name;
                }
            } catch (RuntimeException $e) {
                // On ignore une photo problématique et on continue.
            }
        }
        return $out;
    }

    /** Extensions de documents autorisées en pièce jointe. */
    private const DOC_EXT = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'zip', 'odt', 'ods', 'odp',
    ];

    /** Types MIME jamais acceptés (servis tels quels = risque XSS/exécution). */
    private const DOC_MIME_DENY = [
        'text/html', 'application/xhtml+xml', 'image/svg+xml',
        'application/x-httpd-php', 'application/x-php', 'text/x-php',
        'application/javascript', 'text/javascript',
    ];

    /**
     * Plusieurs documents (champ multiple, ex: name="docs[]").
     * Retourne une liste de méta : ['filename','original','mime','size'].
     * Un fichier invalide est ignoré sans bloquer les autres.
     */
    public static function documents(string $field, string $subdir): array
    {
        $f = $_FILES[$field] ?? null;
        if (!is_array($f) || !is_array($f['name'] ?? null)) {
            return [];
        }

        $out   = [];
        $count = count($f['name']);
        for ($i = 0; $i < $count; $i++) {
            if ((int) ($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $single = [
                'name'     => $f['name'][$i],
                'type'     => $f['type'][$i],
                'tmp_name' => $f['tmp_name'][$i],
                'error'    => $f['error'][$i],
                'size'     => $f['size'][$i],
            ];
            try {
                $meta = self::storeDocument($single, $subdir);
                if ($meta !== null) {
                    $out[] = $meta;
                }
            } catch (RuntimeException $e) {
                // Document problématique ignoré, on continue.
            }
        }
        return $out;
    }

    /** Valide + range UN document, renvoie ses méta (ou null si vide). */
    private static function storeDocument(array $f, string $subdir): ?array
    {
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Échec de l'envoi du document (code {$f['error']}).");
        }
        if (!is_uploaded_file($f['tmp_name'])) {
            throw new RuntimeException('Fichier de document invalide.');
        }
        if ($f['size'] > self::MAX_BYTES) {
            throw new RuntimeException('Document trop lourd (max 25 Mo).');
        }

        $original = basename((string) $f['name']);
        $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, self::DOC_EXT, true)) {
            throw new RuntimeException('Type de document non autorisé.');
        }

        // Type MIME réel (refuse les fichiers exécutables/HTML déguisés).
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = (string) (finfo_file($fi, $f['tmp_name']) ?: $mime);
                finfo_close($fi);
            }
        }
        if (in_array(strtolower($mime), self::DOC_MIME_DENY, true)) {
            throw new RuntimeException('Type de document non autorisé.');
        }

        $dir = APP_ROOT . '/uploads/' . $subdir;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Impossible de créer le dossier d'upload.");
        }

        $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!@move_uploaded_file($f['tmp_name'], $dest) && !@copy($f['tmp_name'], $dest)) {
            throw new RuntimeException("Impossible d'enregistrer le document.");
        }

        return [
            'filename' => $name,
            'original' => $original,
            'mime'     => $mime,
            'size'     => (int) $f['size'],
        ];
    }

    /** Valide + traite UN fichier image, renvoie son nom stocké (ou null si vide). */
    private static function store(array $f, string $subdir): ?string
    {
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null; // aucun fichier → on ne touche à rien
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Échec de l'envoi de l'image (code {$f['error']}).");
        }
        if (!is_uploaded_file($f['tmp_name'])) {
            throw new RuntimeException("Fichier d'upload invalide.");
        }
        if ($f['size'] > self::MAX_BYTES) {
            throw new RuntimeException('Image beaucoup trop lourde (max 25 Mo).');
        }

        // Type RÉEL (pas seulement l'extension annoncée)
        $info = @getimagesize($f['tmp_name']);
        $mime = $info['mime'] ?? '';
        if (!isset(self::TYPES[$mime])) {
            throw new RuntimeException('Format non autorisé (JPG, PNG, GIF ou WEBP uniquement).');
        }
        $ext = self::TYPES[$mime];

        $dir = APP_ROOT . '/uploads/' . $subdir;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Impossible de créer le dossier d'upload.");
        }

        $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        $dest = $dir . '/' . $name;

        self::process($f['tmp_name'], $dest, $ext, (int) $info[0], (int) $info[1]);

        return $name;
    }

    /** Redimensionne + recompresse l'image. Repli : copie brute si GD manque. */
    private static function process(string $src, string $dest, string $ext, int $w, int $h): void
    {
        $create = [
            'jpg' => 'imagecreatefromjpeg', 'png' => 'imagecreatefrompng',
            'gif' => 'imagecreatefromgif',  'webp' => 'imagecreatefromwebp',
        ][$ext];
        $save = [
            'jpg' => 'imagejpeg', 'png' => 'imagepng',
            'gif' => 'imagegif',  'webp' => 'imagewebp',
        ][$ext];

        // GD absent (ou format non géré) → on garde le fichier d'origine.
        if (!function_exists($create) || !function_exists($save)) {
            if (!@move_uploaded_file($src, $dest) && !@copy($src, $dest)) {
                throw new RuntimeException("Impossible d'enregistrer l'image.");
            }
            return;
        }

        if ($w * $h > self::MAX_PIXELS) {
            throw new RuntimeException('Image trop grande (dimensions excessives).');
        }

        @ini_set('memory_limit', '256M');
        $img = @$create($src);
        if (!$img) {
            throw new RuntimeException('Image illisible ou corrompue.');
        }

        // Corrige l'orientation des photos (EXIF), fréquent sur smartphone.
        if ($ext === 'jpg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($src);
            $o = (int) ($exif['Orientation'] ?? 0);
            if ($o === 3) {
                $img = imagerotate($img, 180, 0);
            } elseif ($o === 6) {
                $img = imagerotate($img, -90, 0);
                [$w, $h] = [$h, $w];
            } elseif ($o === 8) {
                $img = imagerotate($img, 90, 0);
                [$w, $h] = [$h, $w];
            }
        }

        $scale = min(1, self::MAX_DIM / max($w, $h));
        if ($scale < 1) {
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            if (in_array($ext, ['png', 'webp', 'gif'], true)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
            }
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        } else {
            $dst = $img; // déjà assez petite → on recompresse seulement
        }

        $ok = match ($ext) {
            'jpg'  => imagejpeg($dst, $dest, self::JPEG_QUALITY),
            'png'  => imagepng($dst, $dest, self::PNG_LEVEL),
            'webp' => imagewebp($dst, $dest, self::WEBP_QUALITY),
            'gif'  => imagegif($dst, $dest),
        };

        if ($dst !== $img) {
            imagedestroy($dst);
        }
        imagedestroy($img);

        if (!$ok) {
            throw new RuntimeException("Impossible d'enregistrer l'image.");
        }
    }

    /** Supprime une image stockée (lors d'un remplacement ou d'une suppression). */
    public static function delete(?string $name, string $subdir): void
    {
        if (!$name) {
            return;
        }
        $name = basename($name); // sécurité : jamais de chemin
        $path = APP_ROOT . '/uploads/' . $subdir . '/' . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

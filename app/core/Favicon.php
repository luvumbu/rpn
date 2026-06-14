<?php
/**
 * CLASSE Favicon
 * Génère le favicon du site (icône d'onglet + icônes PWA) à la demande de
 * l'administrateur, de deux façons :
 *   - depuis une IMAGE envoyée (recadrée au carré + redimensionnée) ;
 *   - depuis un TEXTE (1 à 3 lettres) sur un fond coloré, coins arrondis au choix.
 *
 * Les fichiers écrits dans assets/ (favicon.png, favicon.ico, icon-192.png,
 * icon-512.png) sont ceux référencés par Theme::favicon() / le manifeste. Un
 * numéro de version (Settings 'favicon_version') force les navigateurs à
 * recharger la nouvelle icône immédiatement.
 */
class Favicon
{
    /** Génère le favicon à partir d'une image envoyée (champ de formulaire). */
    public static function fromUpload(string $field, string $shape = 'square'): void
    {
        self::ensureGd();
        // On réutilise la validation sûre de Upload (type réel, EXIF, redimension).
        $stored = Upload::image($field, 'branding');
        if (!$stored) {
            throw new RuntimeException('Aucune image reçue.');
        }
        $path = APP_ROOT . '/uploads/branding/' . $stored;
        try {
            $master = self::loadSquare($path);
            self::applyShapeMask($master, $shape, 512); // découpe arrondie/cercle si demandé
            self::writeAll($master);
            imagedestroy($master);
        } finally {
            Upload::delete($stored, 'branding'); // on ne garde pas l'original
        }
    }

    /**
     * Génère le favicon à partir d'un texte court.
     *   $shape       : 'square' | 'round' | 'circle' (forme du fond)
     *   $transparent : true → pas de fond coloré (lettre seule sur transparence)
     *   $fontStyle   : 'bold' | 'regular' | 'serif' | 'mono'
     */
    public static function fromText(string $text, string $bg, string $fg, string $shape = 'round', bool $transparent = false, string $fontStyle = 'bold'): void
    {
        self::ensureGd();
        $S      = 512;
        $master = self::canvas($S);

        // Fond (sauf si transparent) selon la forme choisie.
        if (!$transparent) {
            [$br, $bgr, $bb] = self::rgb($bg, [20, 17, 15]);
            $bgCol = imagecolorallocate($master, $br, $bgr, $bb);
            if ($shape === 'circle') {
                imagefilledellipse($master, (int) ($S / 2), (int) ($S / 2), $S, $S, $bgCol);
            } else {
                $radius = $shape === 'round' ? (int) round($S * 0.20) : 0;
                self::fillRounded($master, 0, 0, $S, $S, $radius, $bgCol);
            }
        }

        // Texte centré (1 à 3 caractères, en majuscules).
        $txt = mb_strtoupper(mb_substr(trim($text), 0, 3));
        if ($txt === '') {
            $txt = 'R';
        }
        [$fr, $fgr, $fb] = self::rgb($fg, [244, 193, 75]);
        $fgCol = imagecolorallocate($master, $fr, $fgr, $fb);

        $font = self::fontPath($fontStyle);
        if ($font) {
            // Cherche la plus grande taille qui tient dans ~75% du carré.
            $max = (int) ($S * 0.74);
            $size = 320;
            for (; $size > 12; $size -= 4) {
                $bb2 = imagettfbbox($size, 0, $font, $txt);
                $tw  = abs($bb2[2] - $bb2[0]);
                $th  = abs($bb2[7] - $bb2[1]);
                if ($tw <= $max && $th <= $max) {
                    break;
                }
            }
            $bb2 = imagettfbbox($size, 0, $font, $txt);
            $tw  = $bb2[2] - $bb2[0];
            $th  = $bb2[1] - $bb2[7];
            $x   = (int) (($S - $tw) / 2 - $bb2[0]);
            $y   = (int) (($S - $th) / 2 - $bb2[7]);
            imagettftext($master, $size, 0, $x, $y, $fgCol, $font, $txt);
        } else {
            // Repli sans police TTF : police bitmap agrandie (moins net, mais fonctionne).
            $tmp = self::canvas(120);
            $tcol = imagecolorallocate($tmp, $fr, $fgr, $fb);
            $fw = imagefontwidth(5) * strlen($txt);
            imagestring($tmp, 5, (int) ((120 - $fw) / 2), 48, $txt, $tcol);
            imagecopyresampled($master, $tmp, 96, 96, 0, 0, $S - 192, $S - 192, 120, 120);
            imagedestroy($tmp);
        }

        self::writeAll($master);
        imagedestroy($master);
    }

    /* ------------------------------------------------------------------ */

    /** Toile carrée transparente prête à dessiner. */
    private static function canvas(int $s)
    {
        $im = imagecreatetruecolor($s, $s);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefilledrectangle($im, 0, 0, $s, $s, $transparent);
        imagealphablending($im, true);
        return $im;
    }

    /** Charge une image quelconque, la recadre au carré (centre) et la met en 512. */
    private static function loadSquare(string $path)
    {
        $data = @file_get_contents($path);
        $src  = $data ? @imagecreatefromstring($data) : false;
        if (!$src) {
            throw new RuntimeException('Image illisible ou format non géré.');
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $sx = (int) (($w - $side) / 2);
        $sy = (int) (($h - $side) / 2);

        $master = self::canvas(512);
        imagealphablending($master, false);
        imagecopyresampled($master, $src, 0, 0, $sx, $sy, 512, 512, $side, $side);
        imagesavealpha($master, true);
        imagedestroy($src);
        return $master;
    }

    /** Écrit toutes les tailles à la racine + favicon.ico, puis bump la version. */
    private static function writeAll($master): void
    {
        $dir = APP_ROOT . '/assets';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            throw new RuntimeException("Le dossier assets/ est introuvable et n'a pas pu être créé.");
        }
        if (!is_writable($dir)) {
            throw new RuntimeException("Le dossier assets/ n'est pas accessible en écriture (favicon non enregistré).");
        }

        self::writePng($master, $dir . '/icon-512.png', 512);
        self::writePng($master, $dir . '/icon-192.png', 192);
        self::writePng($master, $dir . '/favicon.png', 64);

        // favicon.ico : un PNG 48×48 embarqué (accepté par les navigateurs modernes).
        $ico48 = self::resized($master, 48);
        ob_start();
        imagepng($ico48);
        $png48 = (string) ob_get_clean();
        imagedestroy($ico48);
        if (@file_put_contents($dir . '/favicon.ico', self::ico($png48, 48)) === false) {
            throw new RuntimeException("Impossible d'écrire favicon.ico.");
        }

        // Version (cache-busting) + marque « favicon personnalisé » (on n'utilise
        // alors plus le favicon.svg historique, qui aurait la priorité).
        Settings::save(['favicon_version' => (string) time(), 'favicon_custom' => 1]);
    }

    /** Redimensionne $master à $size et l'enregistre en PNG. */
    private static function writePng($master, string $dest, int $size): void
    {
        $im = self::resized($master, $size);
        $ok = imagepng($im, $dest);
        imagedestroy($im);
        if (!$ok) {
            throw new RuntimeException("Impossible d'écrire " . basename($dest) . '.');
        }
    }

    /** Copie redimensionnée (carrée) de $master, alpha conservé. */
    private static function resized($master, int $size)
    {
        $im = self::canvas($size);
        imagealphablending($im, false);
        imagecopyresampled($im, $master, 0, 0, 0, 0, $size, $size, imagesx($master), imagesy($master));
        imagesavealpha($im, true);
        return $im;
    }

    /** Emballe un PNG dans un conteneur .ico (une seule image). */
    private static function ico(string $png, int $size): string
    {
        $bytes  = strlen($png);
        $dim     = $size >= 256 ? 0 : $size;
        $header = pack('vvv', 0, 1, 1);                       // réservé, type=icône, 1 image
        $entry  = pack('CCCCvvVV', $dim, $dim, 0, 0, 1, 32, $bytes, 22); // 6 + 16 = 22
        return $header . $entry . $png;
    }

    /** Remplit un rectangle (éventuellement à coins arrondis) d'une couleur. */
    private static function fillRounded($im, int $x, int $y, int $w, int $h, int $r, int $col): void
    {
        if ($r <= 0) {
            imagefilledrectangle($im, $x, $y, $x + $w - 1, $y + $h - 1, $col);
            return;
        }
        $r = min($r, (int) ($w / 2), (int) ($h / 2));
        $d = $r * 2;
        imagefilledrectangle($im, $x + $r, $y, $x + $w - 1 - $r, $y + $h - 1, $col);
        imagefilledrectangle($im, $x, $y + $r, $x + $w - 1, $y + $h - 1 - $r, $col);
        imagefilledellipse($im, $x + $r, $y + $r, $d, $d, $col);
        imagefilledellipse($im, $x + $w - 1 - $r, $y + $r, $d, $d, $col);
        imagefilledellipse($im, $x + $r, $y + $h - 1 - $r, $d, $d, $col);
        imagefilledellipse($im, $x + $w - 1 - $r, $y + $h - 1 - $r, $d, $d, $col);
    }

    /** #rrggbb → [r,g,b]. Repli si la valeur est invalide. */
    private static function rgb(string $hex, array $fallback): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return $fallback;
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /**
     * Police TTF selon le STYLE demandé (gras, normale, serif, mono), avec repli
     * sur les autres styles puis sur n'importe quelle police trouvée. null si aucune.
     */
    private static function fontPath(string $style = 'bold'): ?string
    {
        $sets = [
            'bold'    => ['C:/Windows/Fonts/arialbd.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf', '/Library/Fonts/Arial Bold.ttf'],
            'regular' => ['C:/Windows/Fonts/arial.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', '/usr/share/fonts/dejavu/DejaVuSans.ttf', '/Library/Fonts/Arial.ttf'],
            'serif'   => ['C:/Windows/Fonts/timesbd.ttf', 'C:/Windows/Fonts/times.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf'],
            'mono'    => ['C:/Windows/Fonts/courbd.ttf', 'C:/Windows/Fonts/cour.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSansMono-Bold.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf'],
        ];
        $order = array_merge($sets[$style] ?? [], $sets['bold'], $sets['regular'], $sets['serif'], $sets['mono']);
        foreach ($order as $c) {
            if (is_file($c)) {
                return $c;
            }
        }
        return null;
    }

    /** Vérifie que l'extension GD (traitement d'images) est disponible. */
    private static function ensureGd(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new RuntimeException("L'extension PHP « GD » n'est pas activée sur le serveur : impossible de générer une image.");
        }
    }

    /**
     * Rend transparent tout ce qui sort de la forme demandée ('round' ou 'circle').
     * Sert à découper une image carrée en icône arrondie ou ronde. 'square' = rien.
     */
    private static function applyShapeMask($im, string $shape, int $S): void
    {
        if ($shape !== 'round' && $shape !== 'circle') {
            return;
        }
        imagesavealpha($im, true);
        imagealphablending($im, false);
        $clear = imagecolorallocatealpha($im, 0, 0, 0, 127);

        if ($shape === 'circle') {
            $cx = $S / 2.0; $cy = $S / 2.0; $r = $S / 2.0; $r2 = $r * $r;
            for ($y = 0; $y < $S; $y++) {
                for ($x = 0; $x < $S; $x++) {
                    $dx = $x + 0.5 - $cx; $dy = $y + 0.5 - $cy;
                    if ($dx * $dx + $dy * $dy > $r2) {
                        imagesetpixel($im, $x, $y, $clear);
                    }
                }
            }
        } else { // 'round' : on ne nettoie que les 4 coins, hors de l'arc.
            $rad  = (int) round($S * 0.20);
            $rad2 = $rad * $rad;
            $corners = [[$rad, $rad], [$S - 1 - $rad, $rad], [$rad, $S - 1 - $rad], [$S - 1 - $rad, $S - 1 - $rad]];
            foreach ($corners as $idx => $c) {
                $x0 = ($idx % 2 === 0) ? 0 : $S - $rad;
                $y0 = ($idx < 2) ? 0 : $S - $rad;
                for ($y = $y0; $y < $y0 + $rad; $y++) {
                    for ($x = $x0; $x < $x0 + $rad; $x++) {
                        $dx = $x + 0.5 - $c[0]; $dy = $y + 0.5 - $c[1];
                        if ($dx * $dx + $dy * $dy > $rad2) {
                            imagesetpixel($im, $x, $y, $clear);
                        }
                    }
                }
            }
        }
        imagealphablending($im, true);
    }
}

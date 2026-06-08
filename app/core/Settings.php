<?php
/**
 * CLASSE Settings
 * Réglages modifiables depuis l'espace admin (clés API Google, durée de blocage...).
 * Stockés dans storage/settings.json. Si une valeur n'est pas définie,
 * on retombe sur la valeur par défaut passée en argument.
 */
class Settings
{
    private static ?array $cache = null;

    private static function file(): string
    {
        return __DIR__ . '/../../storage/settings.json';
    }

    private static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $f    = self::file();
        $data = is_file($f) ? json_decode((string) @file_get_contents($f), true) : [];
        return self::$cache = (is_array($data) ? $data : []);
    }

    /** Récupère une valeur (ou la valeur par défaut si vide/absente). */
    public static function get(string $key, $default = null)
    {
        $v = self::load()[$key] ?? null;
        return ($v === null || $v === '') ? $default : $v;
    }

    /** Enregistre un ou plusieurs réglages. */
    public static function save(array $values): void
    {
        $data = array_merge(self::load(), $values);
        $dir  = dirname(self::file());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            self::file(),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        self::$cache = $data;
    }
}

<?php
/**
 * CLASSE LoginGuard
 * Protection anti-bruteforce du login admin.
 * Après MAX_ATTEMPTS échecs → l'IP est bloquée pendant BLOCK_SECONDS.
 * Les tentatives sont stockées dans un fichier (le login admin n'a pas
 * encore accès à la base de données).
 */
class LoginGuard
{
    private static function file(): string
    {
        return __DIR__ . '/../../storage/login_attempts.json';
    }

    /** Nombre d'essais avant blocage (réglable dans l'admin, défaut 3). */
    private static function maxAttempts(): int
    {
        return max(1, (int) Settings::get('max_attempts', 3));
    }

    /** Durée de blocage en secondes (réglable dans l'admin, défaut 24h). */
    private static function blockSeconds(): int
    {
        return max(1, (int) Settings::get('block_hours', 24)) * 3600;
    }

    /** IP du visiteur (gère les hébergeurs derrière un proxy). */
    public static function ip(): string
    {
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($xff !== '') {
            $parts = explode(',', $xff);
            return trim($parts[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private static function load(): array
    {
        $f = self::file();
        if (!is_file($f)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents($f), true);
        return is_array($data) ? $data : [];
    }

    private static function save(array $data): void
    {
        $dir = dirname(self::file());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(self::file(), json_encode($data), LOCK_EX);
    }

    /** Secondes restantes de blocage pour cette IP (0 si non bloquée). */
    public static function blockedFor(string $ip): int
    {
        $until = self::load()[$ip]['blocked_until'] ?? 0;
        $left  = $until - time();
        return $left > 0 ? $left : 0;
    }

    public static function isBlocked(string $ip): bool
    {
        return self::blockedFor($ip) > 0;
    }

    /** Nombre d'essais restants avant blocage. */
    public static function attemptsLeft(string $ip): int
    {
        $count = self::load()[$ip]['count'] ?? 0;
        $left  = self::maxAttempts() - $count;
        return $left > 0 ? $left : 0;
    }

    /** Enregistre un échec. Au seuil défini → blocage. */
    public static function recordFailure(string $ip): void
    {
        $data  = self::load();
        $entry = $data[$ip] ?? ['count' => 0, 'blocked_until' => 0];

        $entry['count'] = ($entry['count'] ?? 0) + 1;

        if ($entry['count'] >= self::maxAttempts()) {
            $entry['blocked_until'] = time() + self::blockSeconds();
            $entry['count']         = 0; // remis à zéro : le blocage prend le relais
        }

        $data[$ip] = $entry;
        self::save($data);
    }

    /** Réinitialise les tentatives (après une connexion réussie). */
    public static function reset(string $ip): void
    {
        $data = self::load();
        unset($data[$ip]);
        self::save($data);
    }

    /** Annule TOUS les blocages et toutes les tentatives. */
    public static function resetAll(): void
    {
        @unlink(self::file());
    }

    /** Liste des IP actuellement bloquées → [ip => secondes restantes]. */
    public static function blockedList(): array
    {
        $out = [];
        foreach (self::load() as $ip => $entry) {
            $left = ($entry['blocked_until'] ?? 0) - time();
            if ($left > 0) {
                $out[$ip] = $left;
            }
        }
        return $out;
    }

    /** Formate une durée en texte court (ex: "23 h 12 min"). */
    public static function humanRemaining(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        if ($h > 0) {
            return $h . ' h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . ' min';
        }
        return max(1, $m) . ' min';
    }
}

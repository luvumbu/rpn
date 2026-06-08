<?php
/**
 * CLASSE Session
 * Regroupe toute la gestion de la session, utilisée dans plusieurs fichiers
 * (controllers, callback, déconnexion...). Évite de répéter $_SESSION partout.
 */
class Session
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return !empty($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Retourne l'utilisateur connecté (ou null). */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /** L'utilisateur connecté est-il administrateur ? */
    public static function isAdmin(): bool
    {
        return (($_SESSION['user']['role'] ?? '') === 'admin');
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}

<?php
/**
 * MODÈLE PasswordReset — jetons de réinitialisation de mot de passe.
 * Le jeton brut est envoyé par e-mail ; en base on ne stocke que son hachage
 * (SHA-256). À usage unique, expiration courte (1 h).
 */
class PasswordReset
{
    /** Durée de validité d'un jeton, en secondes (1 h). */
    public const TTL = 3600;

    /**
     * Crée un jeton pour un membre et renvoie le jeton BRUT (à mettre dans le lien).
     * Invalide d'abord les anciens jetons du membre.
     */
    public static function create(int $userId): string
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$userId]);

        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);
        $exp   = date('Y-m-d H:i:s', time() + self::TTL);
        $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)')
            ->execute([$userId, $hash, $exp]);
        return $token;
    }

    /** Ligne valide (non utilisée, non expirée) pour ce jeton brut, ou null. */
    public static function findValid(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $hash = hash('sha256', $token);
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at >= NOW() LIMIT 1'
        );
        $stmt->execute([$hash]);
        return $stmt->fetch() ?: null;
    }

    /** Marque un jeton comme utilisé (après changement de mot de passe). */
    public static function markUsed(int $id): void
    {
        Database::pdo()->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$id]);
    }
}

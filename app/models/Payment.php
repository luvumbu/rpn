<?php
/**
 * MODÈLE Payment — enregistrement des paiements/abonnements Stripe.
 */
class Payment
{
    /** Crée un paiement « en attente » et retourne son id. */
    public static function create(int $userId, string $type, int $amount, string $currency, string $description, string $sessionId): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO payments (user_id, type, amount, currency, status, description, session_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $type === 'subscription' ? 'subscription' : 'payment',
            max(0, $amount),
            strtolower($currency) ?: 'eur',
            'pending',
            mb_substr($description, 0, 190),
            $sessionId,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    /** Met à jour le statut d'un paiement par son id de session Stripe. */
    public static function setStatusBySession(string $sessionId, string $status, ?string $customerId = null): void
    {
        $status = in_array($status, ['pending', 'paid', 'failed', 'canceled'], true) ? $status : 'pending';
        if ($customerId !== null) {
            Database::pdo()->prepare('UPDATE payments SET status = ?, customer_id = ? WHERE session_id = ?')
                ->execute([$status, $customerId, $sessionId]);
        } else {
            Database::pdo()->prepare('UPDATE payments SET status = ? WHERE session_id = ?')
                ->execute([$status, $sessionId]);
        }
    }

    /** Un paiement par son id de session Stripe (ou null). */
    public static function findBySession(string $sessionId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM payments WHERE session_id = ? LIMIT 1');
        $stmt->execute([$sessionId]);
        return $stmt->fetch() ?: null;
    }

    /** Paiements d'un membre (du plus récent au plus ancien). */
    public static function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 50');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Tous les paiements payés (pour l'admin / les stats), récents d'abord. */
    public static function allPaid(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        return Database::pdo()
            ->query("SELECT * FROM payments WHERE status = 'paid' ORDER BY created_at DESC LIMIT $limit")
            ->fetchAll();
    }

    /** Total encaissé (centimes), tous paiements 'paid'. */
    public static function totalPaid(): int
    {
        return (int) Database::pdo()->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'paid'")->fetchColumn();
    }
}

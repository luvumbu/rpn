<?php
/**
 * MODÈLE Urgent
 * Centralise les éléments marqués « URGENT » (articles + événements) qui
 * doivent s'afficher sur le tableau de bord de CHAQUE utilisateur, tant qu'il
 * ne les a pas fermés (croix). Les fermetures sont par utilisateur
 * (table urgent_dismissals).
 */
class Urgent
{
    /** Éléments urgents encore actifs et NON fermés par cet utilisateur. */
    public static function forUser(int $uid): array
    {
        $pdo = Database::pdo();
        $out = [];

        // Articles urgents (publiés) — avec leur image de couverture si elle existe.
        $st = $pdo->prepare(
            "SELECT a.id, a.title, a.image FROM articles a
             WHERE a.urgent = 1 AND a.active = 1
               AND NOT EXISTS (SELECT 1 FROM urgent_dismissals d
                               WHERE d.user_id = ? AND d.item_type = 'article' AND d.item_id = a.id)
             ORDER BY a.created_at DESC"
        );
        $st->execute([$uid]);
        foreach ($st->fetchAll() as $r) {
            $img = trim((string) ($r['image'] ?? ''));
            $out[] = [
                'type'  => 'article',
                'id'    => (int) $r['id'],
                'title' => $r['title'],
                'link'  => url('article') . '?id=' . (int) $r['id'],
                'icon'  => '📰',
                'image' => $img !== '' ? url('uploads/articles/' . rawurlencode($img)) : '',
            ];
        }

        // Événements urgents (à venir) — avec leur 1re photo si elle existe.
        $st = $pdo->prepare(
            "SELECT a.id, a.title, a.start_at,
                    (SELECT filename FROM appointment_images WHERE appointment_id = a.id ORDER BY id ASC LIMIT 1) AS img
             FROM appointments a
             WHERE a.urgent = 1 AND a.start_at >= NOW()
               AND NOT EXISTS (SELECT 1 FROM urgent_dismissals d
                               WHERE d.user_id = ? AND d.item_type = 'event' AND d.item_id = a.id)
             ORDER BY a.start_at ASC"
        );
        $st->execute([$uid]);
        foreach ($st->fetchAll() as $r) {
            $img = trim((string) ($r['img'] ?? ''));
            $out[] = [
                'type'  => 'event',
                'id'    => (int) $r['id'],
                'title' => $r['title'],
                'link'  => url('agenda/event') . '?id=' . (int) $r['id'],
                'icon'  => '📅',
                'image' => $img !== '' ? url('uploads/agenda/' . rawurlencode($img)) : '',
            ];
        }

        // Questionnaires urgents (publiés)
        $st = $pdo->prepare(
            "SELECT q.id, q.title FROM quizzes q
             WHERE q.urgent = 1 AND q.active = 1
               AND NOT EXISTS (SELECT 1 FROM urgent_dismissals d
                               WHERE d.user_id = ? AND d.item_type = 'quiz' AND d.item_id = q.id)
             ORDER BY q.created_at DESC"
        );
        $st->execute([$uid]);
        foreach ($st->fetchAll() as $r) {
            $out[] = [
                'type'  => 'quiz',
                'id'    => (int) $r['id'],
                'title' => $r['title'],
                'link'  => url('quiz/show') . '?id=' . (int) $r['id'],
                'icon'  => '❓',
            ];
        }
        return $out;
    }

    /** L'utilisateur ferme une alerte urgente (pour lui seul). */
    public static function dismiss(int $uid, string $type, int $id): void
    {
        $type = in_array($type, ['event', 'quiz'], true) ? $type : 'article';
        Database::pdo()
            ->prepare("INSERT IGNORE INTO urgent_dismissals (user_id, item_type, item_id) VALUES (?, ?, ?)")
            ->execute([$uid, $type, $id]);
    }

    /**
     * Efface TOUTES les fermetures d'un élément. Appelé quand un élément passe
     * de « normal » à « urgent » : l'alerte réapparaît alors chez tout le monde,
     * même chez ceux qui l'avaient déjà fermée auparavant.
     */
    public static function reset(string $type, int $id): void
    {
        $type = in_array($type, ['event', 'quiz'], true) ? $type : 'article';
        Database::pdo()
            ->prepare("DELETE FROM urgent_dismissals WHERE item_type = ? AND item_id = ?")
            ->execute([$type, $id]);
    }
}

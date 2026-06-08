<?php
/**
 * CLASSE Level — gamification « Héritier → Sage ».
 * Les points sont calculés À LA VOLÉE à partir de l'activité réelle du membre
 * (aucune table supplémentaire) : articles publiés, QCM passés, avis,
 * commentaires, réservations, et ancienneté du compte.
 */
class Level
{
    /** Paliers : niveau => [nom, emoji, points minimum]. */
    public const LEVELS = [
        1 => ['name' => 'Héritier',     'emoji' => '🌱', 'min' => 0],
        2 => ['name' => 'Transmetteur', 'emoji' => '🤝', 'min' => 50],
        3 => ['name' => 'Gardien',      'emoji' => '🛡️', 'min' => 150],
        4 => ['name' => 'Bâtisseur',    'emoji' => '🏗️', 'min' => 350],
        5 => ['name' => 'Sage',         'emoji' => '🦉', 'min' => 700],
    ];

    /** Barème des points par type d'activité. */
    private const PTS = [
        'article' => 15,  // article publié (assiduité + participation)
        'quiz'    => 10,  // QCM passé (progression)
        'review'  => 3,   // avis laissé
        'comment' => 2,   // message de discussion
        'booking' => 5,   // réservation d'un cours
        'week'    => 2,   // par semaine d'ancienneté (plafonné à 52)
    ];

    /** Détail des points d'un membre (pour l'affichage). */
    public static function breakdown(int $uid): array
    {
        if ($uid <= 0) {
            return ['article' => 0, 'quiz' => 0, 'review' => 0, 'comment' => 0, 'booking' => 0, 'weeks' => 0];
        }
        $pdo = Database::pdo();
        $c = function (string $sql) use ($pdo, $uid): int {
            $s = $pdo->prepare($sql);
            $s->execute([$uid]);
            return (int) $s->fetchColumn();
        };
        $weeks = 0;
        $s = $pdo->prepare('SELECT created_at FROM users WHERE id = ?');
        $s->execute([$uid]);
        $created = $s->fetchColumn();
        if ($created) {
            $weeks = (int) floor((time() - strtotime((string) $created)) / 604800);
        }
        return [
            'article' => $c('SELECT COUNT(*) FROM articles WHERE author_id = ? AND active = 1'),
            'quiz'    => $c('SELECT COUNT(*) FROM quiz_responses WHERE user_id = ?'),
            'review'  => $c('SELECT COUNT(*) FROM article_reviews WHERE user_id = ?'),
            'comment' => $c('SELECT COUNT(*) FROM article_comments WHERE user_id = ?'),
            'booking' => $c('SELECT COUNT(*) FROM appointment_bookings WHERE user_id = ?'),
            'weeks'   => max(0, $weeks),
        ];
    }

    /** Total de points d'un membre. */
    public static function points(int $uid): int
    {
        $b = self::breakdown($uid);
        return $b['article'] * self::PTS['article']
            + $b['quiz']    * self::PTS['quiz']
            + $b['review']  * self::PTS['review']
            + $b['comment'] * self::PTS['comment']
            + $b['booking'] * self::PTS['booking']
            + min($b['weeks'], 52) * self::PTS['week'];
    }

    /** Informations de niveau à partir d'un total de points. */
    public static function fromPoints(int $pts): array
    {
        $lvl = 1;
        foreach (self::LEVELS as $n => $d) {
            if ($pts >= $d['min']) {
                $lvl = $n;
            }
        }
        $cur  = self::LEVELS[$lvl];
        $next = self::LEVELS[$lvl + 1] ?? null;
        $progress = 100;
        $toNext   = 0;
        if ($next) {
            $span     = $next['min'] - $cur['min'];
            $progress = $span > 0 ? (int) round(($pts - $cur['min']) / $span * 100) : 100;
            $toNext   = max(0, $next['min'] - $pts);
        }
        return [
            'points'    => $pts,
            'level'     => $lvl,
            'name'      => $cur['name'],
            'emoji'     => $cur['emoji'],
            'next'      => $next ? $next['name'] : null,
            'nextEmoji' => $next ? $next['emoji'] : null,
            'toNext'    => $toNext,
            'progress'  => max(0, min(100, $progress)),
        ];
    }

    /** Raccourci : niveau complet d'un membre. */
    public static function info(int $uid): array
    {
        return self::fromPoints(self::points($uid));
    }

    /** Barème lisible (pour la page « Niveaux »). */
    public static function scale(): array
    {
        return [
            ['icon' => '📰', 'label' => 'Article publié',               'pts' => self::PTS['article']],
            ['icon' => '❓', 'label' => 'Questionnaire (QCM) passé',     'pts' => self::PTS['quiz']],
            ['icon' => '📅', 'label' => 'Réservation d\'un cours',       'pts' => self::PTS['booking']],
            ['icon' => '⭐', 'label' => 'Avis laissé sur un article',    'pts' => self::PTS['review']],
            ['icon' => '💬', 'label' => 'Message de discussion',         'pts' => self::PTS['comment']],
            ['icon' => '⏳', 'label' => 'Ancienneté (par semaine, max 52)', 'pts' => self::PTS['week']],
        ];
    }

    /** Badge court « 🌱 Héritier » pour un membre (ou '' si invité). */
    public static function badge(int $uid): string
    {
        if ($uid <= 0) {
            return '';
        }
        $i = self::info($uid);
        return $i['emoji'] . ' ' . $i['name'];
    }
}

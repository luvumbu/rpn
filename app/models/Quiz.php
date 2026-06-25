<?php
/**
 * MODÈLE Quiz (questionnaires interactifs)
 * Regroupe tout ce qui touche aux questionnaires « notés » :
 *   - quizzes          : le questionnaire (titre, description, auteur, publié…)
 *   - quiz_questions   : ses questions (mode 'single' = 1 bonne réponse, 'multiple' = plusieurs)
 *   - quiz_options     : les réponses possibles de chaque question (+ drapeau « bonne réponse »)
 *   - quiz_responses   : la participation d'un membre (1 par membre) avec son score
 *   - quiz_answers     : les options cochées par le membre
 *
 * Un seul modèle pour toute la fonctionnalité : les tables sont étroitement liées
 * et ne servent qu'ici.
 */
class Quiz
{
    /* =====================================================================
     *  QUESTIONNAIRES
     * ===================================================================== */

    /** Tous les questionnaires, du plus récent au plus ancien. */
    public static function all(): array
    {
        return Database::pdo()
            ->query('SELECT * FROM quizzes ORDER BY created_at DESC')
            ->fetchAll();
    }

    /** Un questionnaire par son id (ou null). */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM quizzes WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Nombre de questionnaires publiés. */
    public static function countActive(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM quizzes WHERE active = 1')->fetchColumn();
    }

    /** Un questionnaire du même TITRE appartenant à cet auteur (anti-doublon à l'import). */
    public static function findByTitleAuthor(string $title, int $authorId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM quizzes WHERE title = ? AND author_id = ? LIMIT 1');
        $stmt->execute([trim($title), $authorId]);
        return $stmt->fetch() ?: null;
    }

    /** Tous les questionnaires d'un auteur (pour l'export « mon projet »). */
    public static function byAuthor(int $authorId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM quizzes WHERE author_id = ? ORDER BY created_at DESC');
        $stmt->execute([$authorId]);
        return $stmt->fetchAll();
    }

    /** Nombre de questionnaires d'un auteur donné. */
    public static function countByAuthor(int $authorId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM quizzes WHERE author_id = ?');
        $stmt->execute([$authorId]);
        return (int) $stmt->fetchColumn();
    }

    /** Crée un questionnaire (sans ses questions). Retourne le nouvel id. */
    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO quizzes (title, description, image, active, max_attempts, author_id, author_name)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['description'] ?? '',
            $data['image']       ?? null,
            isset($data['active']) ? (int) $data['active'] : 1,
            max(0, (int) ($data['max_attempts'] ?? 0)),
            $data['author_id']   ?? null,
            $data['author_name'] ?? '',
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Met à jour le « cartouche » d'un questionnaire (pas ses questions).
     * Si la clé 'image' est présente, on remplace la couverture ; sinon on la garde.
     */
    public static function update(int $id, array $data): void
    {
        $active = isset($data['active']) ? (int) $data['active'] : 1;
        $maxAtt = max(0, (int) ($data['max_attempts'] ?? 0));
        if (array_key_exists('image', $data)) {
            $stmt = Database::pdo()->prepare(
                'UPDATE quizzes SET title = ?, description = ?, image = ?, active = ?, max_attempts = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$data['title'], $data['description'] ?? '', $data['image'], $active, $maxAtt, $id]);
        } else {
            $stmt = Database::pdo()->prepare(
                'UPDATE quizzes SET title = ?, description = ?, active = ?, max_attempts = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$data['title'], $data['description'] ?? '', $active, $maxAtt, $id]);
        }
    }

    /** Définit l'état publié (active) sans toucher au reste. */
    public static function setActive(int $id, int $active): void
    {
        $stmt = Database::pdo()->prepare('UPDATE quizzes SET active = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    /** Marque (true) ou non (false) un questionnaire comme « URGENT » (alerte tableau de bord). */
    public static function setUrgent(int $id, bool $on): void
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('SELECT urgent FROM quizzes WHERE id = ?');
        $stmt->execute([$id]);
        $was = (int) $stmt->fetchColumn() === 1;

        $pdo->prepare('UPDATE quizzes SET urgent = ? WHERE id = ?')->execute([$on ? 1 : 0, $id]);

        // Passage normal → urgent : on rouvre l'alerte pour tout le monde.
        if ($on && !$was) {
            Urgent::reset('quiz', $id);
        }
    }

    /**
     * Définit le caractère obligatoire : $required = bloque l'app tant que le
     * membre n'a pas terminé ; $passRequired = il faut TOUTES les bonnes réponses
     * pour continuer (sinon répondre suffit). Réservé aux administrateurs.
     */
    public static function setRequired(int $id, bool $required, bool $passRequired): void
    {
        Database::pdo()
            ->prepare('UPDATE quizzes SET required = ?, pass_required = ? WHERE id = ?')
            ->execute([$required ? 1 : 0, $passRequired ? 1 : 0, $id]);
    }

    /**
     * Options d'affichage d'un quiz (sans toucher au reste) :
     *  - $oneByOne : afficher les questions une par une
     *  - $instant  : dire immédiatement si la réponse est bonne ou non
     *  - $effects  : effets visuels (confettis / secousse) au retour
     */
    public static function setModes(int $id, bool $oneByOne, bool $instant, bool $effects): void
    {
        Database::pdo()
            ->prepare('UPDATE quizzes SET one_by_one = ?, instant_feedback = ?, effects = ? WHERE id = ?')
            ->execute([$oneByOne ? 1 : 0, $instant ? 1 : 0, $effects ? 1 : 0, $id]);
    }

    /**
     * Réglages avancés : chrono (secondes), ordre aléatoire, seuil de réussite (%),
     * et messages personnalisés affichés selon le score (réussite / échec).
     */
    public static function setExtra(int $id, int $timeLimit, bool $shuffle, int $passThreshold, ?string $msgPass, ?string $msgFail): void
    {
        Database::pdo()->prepare(
            'UPDATE quizzes SET time_limit = ?, shuffle = ?, pass_threshold = ?, msg_pass = ?, msg_fail = ? WHERE id = ?'
        )->execute([
            max(0, $timeLimit), $shuffle ? 1 : 0, max(0, min(100, $passThreshold)),
            ($msgPass !== null && $msgPass !== '') ? mb_substr($msgPass, 0, 255) : null,
            ($msgFail !== null && $msgFail !== '') ? mb_substr($msgFail, 0, 255) : null,
            $id,
        ]);
    }

    /** Définit (ou retire avec null) la photo de couverture, sans toucher au reste. */
    public static function setImage(int $id, ?string $image): void
    {
        Database::pdo()
            ->prepare('UPDATE quizzes SET image = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$image, $id]);
    }

    /* =====================================================================
     *  QUESTIONNAIRE OBLIGATOIRE (blocage de l'application)
     * ===================================================================== */

    /**
     * Premier questionnaire OBLIGATOIRE (publié, avec au moins une question) que
     * ce membre n'a pas encore terminé — ou null s'il est à jour. Sert au garde
     * global du routeur. Un quiz vide n'est jamais bloquant (anti-verrouillage).
     */
    public static function firstPendingRequired(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        $rows = Database::pdo()
            ->query('SELECT * FROM quizzes WHERE required = 1 AND active = 1 ORDER BY created_at ASC')
            ->fetchAll();
        foreach ($rows as $q) {
            if (self::questionCount((int) $q['id']) < 1) {
                continue; // questionnaire vide : on ne bloque pas dessus
            }
            if (!self::isCompletedBy($q, $userId)) {
                return $q;
            }
        }
        return null;
    }

    /**
     * Ce membre a-t-il « terminé » ce questionnaire ?
     *  - pass_required = 1 → il doit avoir TOUTES les bonnes réponses ;
     *  - sinon → avoir répondu suffit.
     */
    public static function isCompletedBy(array $quiz, int $userId): bool
    {
        $resp = self::responseFor((int) $quiz['id'], $userId);
        if (!$resp) {
            return false;
        }
        if ((int) ($quiz['pass_required'] ?? 0) === 1) {
            $total = (int) $resp['total'];
            $score = (int) $resp['score'];
            $threshold = (int) ($quiz['pass_threshold'] ?? 0);
            if ($threshold > 0) {                              // réussite = score % ≥ seuil
                return $total > 0 && self::percent($score, $total) >= $threshold;
            }
            return $total > 0 && $score === $total;            // sinon : toutes les bonnes réponses
        }
        return true;
    }

    /** Supprime un questionnaire ET tout ce qui en dépend (questions, options, réponses). */
    public static function delete(int $id): void
    {
        $pdo = Database::pdo();
        // Réponses + leurs détails
        $rids = $pdo->prepare('SELECT id FROM quiz_responses WHERE quiz_id = ?');
        $rids->execute([$id]);
        foreach ($rids->fetchAll(PDO::FETCH_COLUMN) as $rid) {
            $pdo->prepare('DELETE FROM quiz_answers WHERE response_id = ?')->execute([(int) $rid]);
        }
        $pdo->prepare('DELETE FROM quiz_responses WHERE quiz_id = ?')->execute([$id]);
        // Questions + options
        self::deleteQuestions($id);
        // Le questionnaire lui-même
        $pdo->prepare('DELETE FROM quizzes WHERE id = ?')->execute([$id]);
    }

    /* =====================================================================
     *  QUESTIONS & OPTIONS
     * ===================================================================== */

    /**
     * Questions d'un questionnaire, chacune avec ses options (clé 'options'),
     * dans l'ordre d'affichage (position).
     */
    public static function questions(int $quizId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY position ASC, id ASC'
        );
        $stmt->execute([$quizId]);
        $questions = $stmt->fetchAll();

        foreach ($questions as &$q) {
            $q['options'] = self::options((int) $q['id']);
        }
        unset($q);
        return $questions;
    }

    /** Options d'une question, dans l'ordre (position). */
    public static function options(int $questionId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM quiz_options WHERE question_id = ? ORDER BY position ASC, id ASC'
        );
        $stmt->execute([$questionId]);
        return $stmt->fetchAll();
    }

    /** Les types de questions reconnus. */
    public const TYPES = ['single', 'multiple', 'numeric', 'text', 'fill', 'order', 'match'];

    /** Normalise une valeur de type (repli sur 'single' si inconnue). */
    public static function normalizeType(string $type): string
    {
        return in_array($type, self::TYPES, true) ? $type : 'single';
    }

    /**
     * Ajoute une question et retourne son id.
     *  - $image / $explanation : facultatifs (comme avant) ;
     *  - $answer : réponse attendue pour numeric/text/fill (variantes ou trous séparés par « | ») ;
     *  - $tolerance : marge acceptée pour numeric.
     */
    public static function addQuestion(int $quizId, string $body, string $type, int $position, ?string $image = null, ?string $explanation = null, ?string $answer = null, float $tolerance = 0.0): int
    {
        $type = self::normalizeType($type);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO quiz_questions (quiz_id, body, image, explanation, answer, tolerance, type, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $quizId, mb_substr($body, 0, 500), $image,
            ($explanation !== null && $explanation !== '') ? mb_substr($explanation, 0, 500) : null,
            ($answer !== null && $answer !== '') ? mb_substr($answer, 0, 500) : null,
            max(0, $tolerance),
            $type, $position,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Ajoute une option (réponse possible) à une question.
     *  - $pair : pour le type 'match', l'étiquette de droite à associer à ce label.
     */
    public static function addOption(int $questionId, string $label, bool $isCorrect, int $position, ?string $pair = null): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO quiz_options (question_id, label, pair, is_correct, position) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $questionId, mb_substr($label, 0, 300),
            ($pair !== null && $pair !== '') ? mb_substr($pair, 0, 300) : null,
            $isCorrect ? 1 : 0, $position,
        ]);
    }

    /** Supprime toutes les questions (et leurs options) d'un questionnaire. */
    public static function deleteQuestions(int $quizId): void
    {
        $pdo  = Database::pdo();
        $qids = $pdo->prepare('SELECT id FROM quiz_questions WHERE quiz_id = ?');
        $qids->execute([$quizId]);
        foreach ($qids->fetchAll(PDO::FETCH_COLUMN) as $qid) {
            $pdo->prepare('DELETE FROM quiz_options WHERE question_id = ?')->execute([(int) $qid]);
        }
        $pdo->prepare('DELETE FROM quiz_questions WHERE quiz_id = ?')->execute([$quizId]);
    }

    /** Nombre de questions d'un questionnaire. */
    public static function questionCount(int $quizId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?');
        $stmt->execute([$quizId]);
        return (int) $stmt->fetchColumn();
    }

    /* =====================================================================
     *  PARTICIPATIONS (RÉPONSES DES MEMBRES)
     * ===================================================================== */

    /** La participation d'un membre à un questionnaire (ou null). */
    public static function responseFor(int $quizId, int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM quiz_responses WHERE quiz_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$quizId, $userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * IDs des options cochées par le membre, indexés par question_id → [option_id…].
     * Les lignes « texte » (option_id = 0, réponse saisie) sont ignorées ici.
     * Pour 'order', l'ordre d'insertion (position) reflète l'ordre choisi.
     */
    public static function answersFor(int $responseId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT question_id, option_id FROM quiz_answers WHERE response_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$responseId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            if ((int) $row['option_id'] === 0) { continue; } // ligne porteuse d'un texte
            $out[(int) $row['question_id']][] = (int) $row['option_id'];
        }
        return $out;
    }

    /** Réponses SAISIES par le membre (numeric/text/fill/order/match), par question_id → texte. */
    public static function textsFor(int $responseId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT question_id, answer_text FROM quiz_answers WHERE response_id = ? AND answer_text IS NOT NULL'
        );
        $stmt->execute([$responseId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['question_id']] = (string) $row['answer_text'];
        }
        return $out;
    }

    /** Nombre de participations à un questionnaire. */
    public static function responseCount(int $quizId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM quiz_responses WHERE quiz_id = ?');
        $stmt->execute([$quizId]);
        return (int) $stmt->fetchColumn();
    }

    /** Nombre de tentatives déjà effectuées par un membre (0 si jamais joué). */
    public static function attemptsFor(int $quizId, int $userId): int
    {
        $r = self::responseFor($quizId, $userId);
        return $r ? (int) $r['attempts'] : 0;
    }

    /**
     * Ce membre peut-il (re)tenter le questionnaire ?
     *  - tentatives illimitées si max_attempts <= 0 ;
     *  - un questionnaire OBLIGATOIRE n'est jamais bloqué par la limite
     *    (sinon un membre pourrait rester coincé) ;
     *  - sinon : tant que le nombre de tentatives n'atteint pas la limite.
     */
    public static function canAttempt(array $quiz, int $userId): bool
    {
        $max = (int) ($quiz['max_attempts'] ?? 0);
        if ($max <= 0 || (int) ($quiz['required'] ?? 0) === 1) {
            return true;
        }
        return self::attemptsFor((int) $quiz['id'], $userId) < $max;
    }

    /** Pourcentage de réussite (score/total) arrondi à l'entier. */
    public static function percent(int $score, int $total): int
    {
        return $total > 0 ? (int) round($score / $total * 100) : 0;
    }

    /* =====================================================================
     *  NOTATION PAR TYPE (utilisée par submit ET le corrigé)
     * ===================================================================== */

    /** Normalise un texte pour comparaison : minuscules, sans accent, espaces réduits, ponctuation de fin retirée. */
    public static function normText(string $s): string
    {
        $s = trim(mb_strtolower($s));
        $from = ['à','â','ä','á','ã','ç','é','è','ê','ë','î','ï','í','ô','ö','ó','õ','ù','û','ü','ú','ñ'];
        $to   = ['a','a','a','a','a','c','e','e','e','e','i','i','i','o','o','o','o','u','u','u','u','n'];
        $s = str_replace($from, $to, $s);
        // Apostrophes et traits d'union traités comme des espaces (tolérance de saisie).
        $s = str_replace(["'", "\u{2019}", '`', "\u{00b4}", '-', "\u{2013}"], ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim((string) $s, " \t\n\r\0\x0B.,;:!?");
    }

    /** Convertit une saisie en nombre (accepte la virgule décimale), ou null si invalide. */
    public static function toNumber(string $s): ?float
    {
        $s = str_replace([' ', "\u{00a0}"], '', trim($s));
        $s = str_replace(',', '.', $s);
        return is_numeric($s) ? (float) $s : null;
    }

    /**
     * Une question est-elle réussie ? Logique unique pour tous les types.
     *  - $chosen   : option_id cochés par le membre (single/multiple/order = ordre choisi).
     *  - $answerText : texte/sequence saisi par le membre (numeric/text/fill/order/match).
     * Retourne true si la réponse est entièrement correcte.
     */
    public static function gradeQuestion(array $q, array $chosen, ?string $answerText): bool
    {
        $type = self::normalizeType((string) ($q['type'] ?? 'single'));
        $options = $q['options'] ?? self::options((int) $q['id']);

        switch ($type) {
            case 'numeric': {
                $expected = self::toNumber((string) ($q['answer'] ?? ''));
                $got      = self::toNumber((string) $answerText);
                if ($expected === null || $got === null) { return false; }
                $tol = (float) ($q['tolerance'] ?? 0);
                return abs($got - $expected) <= $tol + 1e-9;
            }
            case 'text': {
                // Plusieurs variantes acceptées, séparées par « | ».
                $variants = array_filter(array_map([self::class, 'normText'], explode('|', (string) ($q['answer'] ?? ''))), fn ($v) => $v !== '');
                return in_array(self::normText((string) $answerText), $variants, true);
            }
            case 'fill': {
                // Chaque trou (séparé par « | ») doit correspondre, dans l'ordre.
                $expected = array_map([self::class, 'normText'], explode('|', (string) ($q['answer'] ?? '')));
                $given    = array_map([self::class, 'normText'], explode('|', (string) $answerText));
                if (count($given) !== count($expected) || empty($expected)) { return false; }
                foreach ($expected as $i => $exp) {
                    if ($exp === '' || ($given[$i] ?? '') !== $exp) { return false; }
                }
                return true;
            }
            case 'order': {
                // Ordre correct = options triées par position. Le membre a renvoyé une suite d'option_id.
                $correct = array_map(fn ($o) => (int) $o['id'], $options); // déjà triées par position dans options()
                return !empty($correct) && $chosen === $correct;
            }
            case 'match': {
                // answerText = « optId:cible » séparés par des virgules ; chaque cible doit égaler option.pair.
                if (empty($options)) { return false; }
                $picked = [];
                foreach (explode(',', (string) $answerText) as $couple) {
                    $parts = explode(':', $couple, 2);
                    if (count($parts) === 2) { $picked[(int) $parts[0]] = self::normText($parts[1]); }
                }
                foreach ($options as $o) {
                    $target = self::normText((string) ($o['pair'] ?? ''));
                    if ($target === '' || ($picked[(int) $o['id']] ?? '') !== $target) { return false; }
                }
                return true;
            }
            case 'multiple':
            case 'single':
            default: {
                $correctSet = [];
                foreach ($options as $o) {
                    if ((int) $o['is_correct'] === 1) { $correctSet[] = (int) $o['id']; }
                }
                $c = $chosen; sort($c); sort($correctSet);
                return $c === $correctSet && !empty($correctSet);
            }
        }
    }

    /**
     * Enregistre la participation d'un membre : crée la ligne de réponse + les
     * options cochées. $picks = [question_id => [option_id, …]]. Remplace la
     * participation précédente mais CUMULE le compteur de tentatives.
     */
    public static function saveResponse(int $quizId, int $userId, string $userName, array $picks, int $score, int $total, array $texts = []): void
    {
        $pdo = Database::pdo();

        // Tentative précédente → on récupère son compteur puis on la remplace.
        $prev     = self::responseFor($quizId, $userId);
        $attempts = ($prev ? (int) $prev['attempts'] : 0) + 1;
        if ($prev) {
            $pdo->prepare('DELETE FROM quiz_answers WHERE response_id = ?')->execute([(int) $prev['id']]);
            $pdo->prepare('DELETE FROM quiz_responses WHERE id = ?')->execute([(int) $prev['id']]);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO quiz_responses (quiz_id, user_id, user_name, score, total, attempts) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$quizId, $userId, mb_substr($userName, 0, 150), $score, $total, $attempts]);
        $rid = (int) $pdo->lastInsertId();

        // Options cochées (single/multiple) ou suite ordonnée (order) : une ligne par option_id.
        $ins = $pdo->prepare(
            'INSERT INTO quiz_answers (response_id, question_id, option_id) VALUES (?, ?, ?)'
        );
        foreach ($picks as $qid => $optionIds) {
            foreach ((array) $optionIds as $oid) {
                $ins->execute([$rid, (int) $qid, (int) $oid]);
            }
        }

        // Réponses saisies (numeric/text/fill/match, et sequence brute pour order) :
        // une ligne porteuse avec option_id = 0 et le texte.
        $insTxt = $pdo->prepare(
            'INSERT INTO quiz_answers (response_id, question_id, option_id, answer_text) VALUES (?, ?, 0, ?)'
        );
        foreach ($texts as $qid => $txt) {
            if ($txt === null || $txt === '') { continue; }
            $insTxt->execute([$rid, (int) $qid, mb_substr((string) $txt, 0, 500)]);
        }
    }
}

<?php
/**
 * CONTRÔLEUR AssistantController — « Assistant Sankofa ».
 * Ce n'est PAS une IA : c'est un ALGORITHME à base de règles + recherche par
 * mots-clés dans les articles et questionnaires. Il oriente le membre vers les
 * bonnes ressources de la plateforme.
 */
class AssistantController
{
    public function index(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        view('assistant', [
            'user'  => Session::user(),
            'q'     => $q,
            'reply' => $this->answer($q),
        ]);
    }

    /** Produit la réponse de l'algorithme : ['text'=>..., 'links'=>[...]]. */
    private function answer(string $q): array
    {
        if ($q === '') {
            return [
                'text'  => "Bonjour 👋 Je suis l'assistant Sankofa. Pose-moi une question ou un mot-clé (ex. « lingala », « réserver un cours », « trouver un prof de maths », « quiz distributivité ») et je te guide vers la bonne ressource.",
                'links' => $this->defaultLinks(),
            ];
        }

        $ql    = $this->normalize($q);
        $links = [];
        $intent = [];

        // --- Intentions par mots-clés ---
        if ($this->has($ql, ['prof', 'professeur', 'enseignant', 'apprendre', 'matiere', 'cours de'])) {
            $links[] = ['icon' => '🔍', 'label' => 'Trouver un professeur (par matière)', 'url' => url('professeurs')];
            $intent[] = 'prof';
        }
        if ($this->has($ql, ['rendez', 'rdv', 'agenda', 'reserv', 'creneau', 'cours'])) {
            $links[] = ['icon' => '📅', 'label' => 'Agenda & réservation de cours', 'url' => url('agenda')];
            $intent[] = 'agenda';
        }
        if ($this->has($ql, ['message', 'contacter', 'ecrire', 'parler', 'discuter'])) {
            $links[] = ['icon' => '✉️', 'label' => 'Mes messages', 'url' => url('messages')];
            $intent[] = 'message';
        }
        if ($this->has($ql, ['quiz', 'qcm', 'test', 'exercice', 'evaluation'])) {
            $links[] = ['icon' => '❓', 'label' => 'Les questionnaires (QCM)', 'url' => url('quiz')];
            $intent[] = 'quiz';
        }
        if ($this->has($ql, ['niveau', 'point', 'sage', 'heritier', 'progress', 'badge'])) {
            $links[] = ['icon' => '🏅', 'label' => 'Mon niveau & ma progression', 'url' => url('dashboard') . '#backup'];
            $intent[] = 'niveau';
        }
        if ($this->has($ql, ['diaspora', 'carte', 'pays', 'origine'])) {
            $links[] = ['icon' => '🗺️', 'label' => 'Carte de la diaspora', 'url' => url('diaspora')];
            $intent[] = 'diaspora';
        }

        // --- Recherche de contenu (articles + quiz) ---
        $arts = $this->searchArticles($q);
        foreach ($arts as $a) {
            $links[] = ['icon' => '📰', 'label' => $a['title'], 'url' => url('article') . '?id=' . (int) $a['id']];
        }
        $quizzes = $this->searchQuizzes($q);
        foreach ($quizzes as $z) {
            $links[] = ['icon' => '❓', 'label' => $z['title'], 'url' => url('quiz/show') . '?id=' . (int) $z['id']];
        }

        // --- Texte de réponse ---
        if (empty($links)) {
            return [
                'text'  => "Je n'ai pas trouvé de ressource pour « " . $q . " » 🤔. Voici par où commencer — et reformule avec un mot-clé plus simple si besoin.",
                'links' => $this->defaultLinks(),
            ];
        }
        $found = count($arts) + count($quizzes);
        $text  = $found > 0
            ? "Voici ce que j'ai trouvé pour « " . $q . " » 👇"
            : "Pour « " . $q . " », je te conseille ceci 👇";
        return ['text' => $text, 'links' => $links];
    }

    private function defaultLinks(): array
    {
        return [
            ['icon' => '📰', 'label' => 'Parcourir les articles', 'url' => url('articles')],
            ['icon' => '🔍', 'label' => 'Trouver un professeur', 'url' => url('professeurs')],
            ['icon' => '📅', 'label' => 'Agenda & rendez-vous', 'url' => url('agenda')],
            ['icon' => '❓', 'label' => 'Questionnaires', 'url' => url('quiz')],
        ];
    }

    /** Minuscule + sans accents, pour comparer les mots-clés. */
    private function normalize(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $from = ['á','à','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','ö','ú','ù','û','ü','ç'];
        $to   = ['a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','u','u','u','u','c'];
        return str_replace($from, $to, $s);
    }

    private function has(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if (strpos($haystack, $n) !== false) {
                return true;
            }
        }
        return false;
    }

    /** Recherche d'articles publiés par mots significatifs (titre + contenu). */
    private function searchArticles(string $q): array
    {
        $words = array_filter(preg_split('/\s+/', $q), static fn ($w) => mb_strlen($w) >= 3);
        if (empty($words)) {
            return [];
        }
        $conds = [];
        $args  = [];
        foreach (array_slice(array_values($words), 0, 4) as $w) {
            $conds[] = '(title LIKE ? OR content LIKE ?)';
            $args[]  = '%' . $w . '%';
            $args[]  = '%' . $w . '%';
        }
        $sql = 'SELECT id, title FROM articles WHERE active = 1 AND access_password IS NULL AND ('
            . implode(' OR ', $conds) . ') ORDER BY created_at DESC LIMIT 5';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }

    /** Recherche de questionnaires publiés par titre/description. */
    private function searchQuizzes(string $q): array
    {
        $words = array_filter(preg_split('/\s+/', $q), static fn ($w) => mb_strlen($w) >= 3);
        if (empty($words)) {
            return [];
        }
        $conds = [];
        $args  = [];
        foreach (array_slice(array_values($words), 0, 4) as $w) {
            $conds[] = '(title LIKE ? OR description LIKE ?)';
            $args[]  = '%' . $w . '%';
            $args[]  = '%' . $w . '%';
        }
        $sql = 'SELECT id, title FROM quizzes WHERE active = 1 AND ('
            . implode(' OR ', $conds) . ') ORDER BY created_at DESC LIMIT 3';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }
}

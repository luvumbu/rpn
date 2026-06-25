<?php
/**
 * CONTRÔLEUR Quiz (questionnaires interactifs)
 * Lecture ET écriture des questionnaires, pour tout membre connecté.
 *  - n'importe quel membre peut créer un questionnaire ;
 *  - il peut modifier / supprimer LES SIENS ; un admin peut tout gérer ;
 *  - tout membre connecté peut répondre à un questionnaire publié.
 *
 * Chaque question est notée : mode « unique » (1 bonne réponse) ou « multiple »
 * (plusieurs bonnes réponses). Après avoir répondu, le membre voit son score et
 * ce qui était juste / faux.
 */
class QuizController
{
    /** Bloque l'accès aux visiteurs non connectés → page de connexion. */
    private function guard(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
    }

    /** Peut modifier/supprimer ce questionnaire ? (son auteur, ou un admin) */
    private function canManage(array $quiz): bool
    {
        if (Session::isAdmin()) {
            return true;
        }
        $u = Session::user();
        return (int) ($quiz['author_id'] ?? -1) === (int) ($u['id'] ?? -2);
    }

    /**
     * Liste des questionnaires : /rpn/quiz
     * Un membre voit tous les questionnaires PUBLIÉS + ses propres brouillons ;
     * un admin voit tout.
     */
    public function index(): void
    {
        $this->guard();
        $quizzes = Quiz::all();

        if (!Session::isAdmin()) {
            $uid = (int) (Session::user()['id'] ?? 0);
            $quizzes = array_values(array_filter($quizzes, function ($q) use ($uid) {
                return (int) $q['active'] === 1 || ($uid && (int) $q['author_id'] === $uid);
            }));
        }

        // Méta pour chaque carte : nb de questions, nb de participants, ma participation.
        $uid  = (int) (Session::user()['id'] ?? 0);
        $meta = [];
        foreach ($quizzes as $q) {
            $qid = (int) $q['id'];
            $meta[$qid] = [
                'questions'    => Quiz::questionCount($qid),
                'participants' => Quiz::responseCount($qid),
                'myResponse'   => Quiz::responseFor($qid, $uid),
            ];
        }

        view('quiz/index', [
            'user'    => Session::user(),
            'quizzes' => $quizzes,
            'meta'    => $meta,
            'isAdmin' => Session::isAdmin(),
            'notice'  => Session::get('quiz_notice'),
        ]);
        Session::remove('quiz_notice');
    }

    /** Formulaire de création. */
    public function create(): void
    {
        $this->guard();
        view('quiz/form', [
            'user'           => Session::user(),
            'quiz'           => null,
            'questions'      => [],
            'error'          => Session::get('quiz_error'),
            'action'         => url('quiz/save'),
            'active'         => 1,
            'isAdmin'        => Session::isAdmin(),
            'isUrgent'       => 0,
            'isRequired'     => 0,
            'isPassRequired' => 0,
            'maxAttempts'    => 0,
            'oneByOne'       => 1,
            'instantFeedback'=> 0,
            'effects'        => 0,
            'timeLimit'      => 0,
            'shuffle'        => 0,
            'passThreshold'  => 0,
            'msgPass'        => '',
            'msgFail'        => '',
        ]);
        Session::remove('quiz_error');
    }

    /** Formulaire de modification (auteur ou admin uniquement). */
    public function edit(): void
    {
        $this->guard();
        $id   = (int) ($_GET['id'] ?? 0);
        $quiz = $id ? Quiz::find($id) : null;
        if (!$quiz || !$this->canManage($quiz)) {
            redirect('quiz');
        }
        view('quiz/form', [
            'user'           => Session::user(),
            'quiz'           => $quiz,
            'questions'      => Quiz::questions($id),
            'error'          => Session::get('quiz_error'),
            'action'         => url('quiz/save'),
            'active'         => (int) ($quiz['active'] ?? 1),
            'isAdmin'        => Session::isAdmin(),
            'isUrgent'       => (int) ($quiz['urgent'] ?? 0),
            'isRequired'     => (int) ($quiz['required'] ?? 0),
            'isPassRequired' => (int) ($quiz['pass_required'] ?? 0),
            'maxAttempts'    => (int) ($quiz['max_attempts'] ?? 0),
            'oneByOne'       => (int) ($quiz['one_by_one'] ?? 0),
            'instantFeedback'=> (int) ($quiz['instant_feedback'] ?? 0),
            'effects'        => (int) ($quiz['effects'] ?? 0),
            'timeLimit'      => (int) round((int) ($quiz['time_limit'] ?? 0) / 60),
            'shuffle'        => (int) ($quiz['shuffle'] ?? 0),
            'passThreshold'  => (int) ($quiz['pass_threshold'] ?? 0),
            'msgPass'        => (string) ($quiz['msg_pass'] ?? ''),
            'msgFail'        => (string) ($quiz['msg_fail'] ?? ''),
        ]);
        Session::remove('quiz_error');
    }

    /**
     * Enregistre un questionnaire (création ou modification).
     * Le formulaire envoie les questions sous forme de tableau imbriqué :
     *   q[IDX][body], q[IDX][type] = single|multiple,
     *   q[IDX][opt][OPTIDX][label], q[IDX][opt][OPTIDX][correct] = 1 si bonne réponse.
     * À chaque enregistrement on recrée intégralement les questions/options.
     */
    public function store(): void
    {
        $this->guard();

        $id          = (int) ($_POST['id'] ?? 0);
        $title       = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $active      = isset($_POST['active']) ? 1 : 0;

        // Droits vérifiés AVANT toute écriture (en édition : auteur ou admin).
        $existing = $id ? Quiz::find($id) : null;
        if ($id && (!$existing || !$this->canManage($existing))) {
            redirect('quiz');
        }

        // Validation : titre + au moins une question valide.
        $rawQuestions = is_array($_POST['q'] ?? null) ? $_POST['q'] : [];
        $clean        = $this->normalizeQuestions($rawQuestions);

        if ($title === '' || empty($clean)) {
            Session::set('quiz_error', $title === ''
                ? 'Donne un titre à ton questionnaire.'
                : 'Ajoute au moins une question avec 2 réponses et 1 bonne réponse cochée.');
            // Conserve la saisie pour ne pas tout reperdre.
            Session::set('quiz_old', ['title' => $title, 'description' => $description, 'q' => $rawQuestions, 'active' => $active]);
            redirect($id ? 'quiz/edit?id=' . $id : 'quiz/new');
        }
        Session::remove('quiz_old');

        // Image de couverture (facultative) : envoi + suppression éventuelle.
        $newImage = null;
        $imgError = null;
        try { $newImage = Upload::image('image', 'quizzes'); } catch (\RuntimeException $e) { $imgError = $e->getMessage(); }
        $removeImage = !empty($_POST['remove_image']);

        // Nombre max de tentatives (0 = illimité) — défini par le créateur.
        $maxAttempts = max(0, min(50, (int) ($_POST['max_attempts'] ?? 0)));

        // --- Images PAR QUESTION : nouvelle (upload) / conservée / retirée ------
        // On capture d'abord les anciennes images (édition) pour effacer ensuite
        // celles devenues orphelines (remplacées, retirées, ou question supprimée).
        $oldQImages = [];
        if ($existing) {
            foreach (Quiz::questions($id) as $oq) {
                if (!empty($oq['image'])) { $oldQImages[] = $oq['image']; }
            }
        }
        $usedQImages = [];
        foreach ($clean as &$cq) {
            $k   = $cq['key'];
            $new = $this->uploadQuestionImage($k);
            if ($new !== null) {
                $cq['image'] = $new;                                       // nouvelle image
            } elseif (!empty($rawQuestions[$k]['remove_image'])) {
                $cq['image'] = null;                                       // retirée
            } else {
                $ex = trim((string) ($rawQuestions[$k]['existing_image'] ?? ''));
                $cq['image'] = ($ex !== '' && !preg_match('#[/\\\\]#', $ex)) ? $ex : null; // conservée
            }
            if ($cq['image'] !== null) { $usedQImages[] = $cq['image']; }
        }
        unset($cq);

        // Crée ou met à jour le cartouche.
        if ($id) {
            $data = ['title' => $title, 'description' => $description, 'active' => $active, 'max_attempts' => $maxAttempts];
            if ($newImage !== null) {
                if (!empty($existing['image'])) { Upload::delete($existing['image'], 'quizzes'); }
                $data['image'] = $newImage;
            } elseif ($removeImage) {
                if (!empty($existing['image'])) { Upload::delete($existing['image'], 'quizzes'); }
                $data['image'] = null;
            }
            Quiz::update($id, $data);
            Quiz::deleteQuestions($id); // on recrée tout proprement
        } else {
            $me = Session::user();
            $id = Quiz::create([
                'title'        => $title,
                'description'  => $description,
                'image'        => $newImage,
                'active'       => $active,
                'max_attempts' => $maxAttempts,
                'author_id'    => (int) ($me['id'] ?? 0),
                'author_name'  => $me['name'] ?: ($me['email'] ?? ''),
            ]);
        }

        // (Re)crée les questions et leurs options.
        $qpos = 0;
        foreach ($clean as $q) {
            $qid = Quiz::addQuestion(
                $id, $q['body'], $q['type'], $qpos++,
                $q['image'] ?? null, $q['explanation'] ?? null,
                $q['answer'] ?? null, (float) ($q['tolerance'] ?? 0)
            );
            $opos = 0;
            foreach ($q['options'] as $opt) {
                Quiz::addOption($qid, $opt['label'], $opt['correct'], $opos++, $opt['pair'] ?? null);
            }
        }

        // Efface les anciennes images de questions devenues inutilisées.
        foreach ($oldQImages as $oimg) {
            if (!in_array($oimg, $usedQImages, true)) {
                Upload::delete($oimg, 'quizzes');
            }
        }

        // « URGENT » (alerte sur le tableau de bord de tout le monde) : ouvert à TOUS.
        Quiz::setUrgent($id, !empty($_POST['urgent']));

        // « Obligatoire » + « il faut réussir pour continuer » : ADMIN uniquement
        // (ça peut bloquer toute l'application pour les membres).
        if (Session::isAdmin()) {
            Quiz::setRequired($id, !empty($_POST['required']), !empty($_POST['pass_required']));
        }

        // Options d'affichage (une par une, retour immédiat, effets) — ouvert à tous les créateurs.
        Quiz::setModes($id, !empty($_POST['one_by_one']), !empty($_POST['instant_feedback']), !empty($_POST['effects']));

        // Réglages avancés : chrono (minutes → secondes), ordre aléatoire, seuil + messages.
        $timeLimit = max(0, min(180, (int) ($_POST['time_limit'] ?? 0))) * 60;
        Quiz::setExtra(
            $id,
            $timeLimit,
            !empty($_POST['shuffle']),
            (int) ($_POST['pass_threshold'] ?? 0),
            trim((string) ($_POST['msg_pass'] ?? '')),
            trim((string) ($_POST['msg_fail'] ?? ''))
        );

        $wasActive = $existing ? ((int) ($existing['active'] ?? 0) === 1) : false;
        if ($active === 1 && !$wasActive) {
            $this->notifyPublished($id, $title);
        } else {
            Session::set('quiz_notice', $active === 1 ? '✅ Questionnaire mis à jour.' : '📝 Brouillon enregistré.');
        }
        // Si l'image a été refusée, on le DIT (au lieu de l'ignorer en silence).
        if ($imgError !== null) {
            Session::set('quiz_error', '⚠️ Le questionnaire est enregistré, mais l\'image n\'a pas pu être ajoutée : ' . $imgError);
        }

        redirect('quiz/show?id=' . $id);
    }

    /**
     * Ajoute / change / retire UNIQUEMENT la photo de couverture d'un quiz,
     * depuis la page du questionnaire (auteur ou admin), sans toucher aux questions.
     */
    public function setImage(): void
    {
        $this->guard();
        $id   = (int) ($_POST['id'] ?? 0);
        $quiz = $id ? Quiz::find($id) : null;
        if (!$quiz || !$this->canManage($quiz)) {
            redirect('quiz');
        }

        $newImage = null;
        try {
            $newImage = Upload::image('image', 'quizzes');
        } catch (\RuntimeException $e) {
            Session::set('quiz_error', '⚠️ Image refusée : ' . $e->getMessage());
            redirect('quiz/show?id=' . $id);
        }

        if ($newImage !== null) {
            if (!empty($quiz['image'])) {
                Upload::delete($quiz['image'], 'quizzes');
            }
            Quiz::setImage($id, $newImage);
            Session::set('quiz_notice', '✅ Photo de couverture ' . (!empty($quiz['image']) ? 'changée' : 'ajoutée') . '.');
        } elseif (!empty($_POST['remove_image'])) {
            if (!empty($quiz['image'])) {
                Upload::delete($quiz['image'], 'quizzes');
            }
            Quiz::setImage($id, null);
            Session::set('quiz_notice', '🗑️ Photo de couverture retirée.');
        } else {
            Session::set('quiz_error', 'Choisis une image à ajouter.');
        }
        redirect('quiz/show?id=' . $id);
    }

    /**
     * Nettoie/valide les questions reçues du formulaire.
     * Ne garde que les questions ayant un énoncé, ≥ 2 réponses non vides et
     * ≥ 1 bonne réponse cochée. Pour le mode « single », on ne garde qu'UNE
     * bonne réponse (la première cochée).
     * @return array<int, array{body:string,type:string,options:array<int,array{label:string,correct:bool}>}>
     */
    private function normalizeQuestions(array $raw): array
    {
        $out = [];
        foreach ($raw as $k => $q) {
            if (!is_array($q)) {
                continue;
            }
            $body = trim((string) ($q['body'] ?? ''));
            $expl = trim((string) ($q['explanation'] ?? ''));
            $type = Quiz::normalizeType((string) ($q['type'] ?? 'single'));
            if ($body === '') {
                continue;
            }

            $base = ['key' => $k, 'body' => $body, 'explanation' => $expl, 'type' => $type,
                     'options' => [], 'answer' => null, 'tolerance' => 0.0];

            // ---- Exercice interactif : on stocke la clé du manipulable choisi ----
            if ($type === 'interactive') {
                $widget = trim((string) ($q['answer'] ?? ''));
                if (!Quiz::isWidget($widget)) { continue; } // clé d'exercice inconnue → ignoré
                $base['answer'] = $widget;
                $out[] = $base;
                continue;
            }

            // ---- Types « à saisir » : réponse attendue, pas d'options -----------
            if ($type === 'numeric') {
                $ans = trim((string) ($q['answer'] ?? ''));
                if (Quiz::toNumber($ans) === null) { continue; }          // il faut un nombre valide
                $base['answer']    = $ans;
                $base['tolerance'] = max(0, (float) Quiz::toNumber((string) ($q['tolerance'] ?? '0')));
                $out[] = $base;
                continue;
            }
            if ($type === 'text') {
                $ans = trim((string) ($q['answer'] ?? ''));
                if ($ans === '') { continue; }                            // au moins une réponse acceptée
                $base['answer'] = $ans;
                $out[] = $base;
                continue;
            }
            if ($type === 'fill') {
                // L'énoncé doit contenir au moins un trou marqué […] ; les réponses
                // sont séparées par « | » et doivent être aussi nombreuses que les trous.
                $blanks = preg_match_all('/\[[^\]]*\]/', $body);
                $ans    = trim((string) ($q['answer'] ?? ''));
                $parts  = array_filter(array_map('trim', explode('|', $ans)), fn ($v) => $v !== '');
                if ($blanks < 1 || count($parts) !== $blanks) { continue; }
                $base['answer'] = implode('|', $parts);
                $out[] = $base;
                continue;
            }

            // ---- Types « à options » : single / multiple / order / match --------
            $rawOpts = is_array($q['opt'] ?? null) ? $q['opt'] : [];
            $options = [];
            $correctSeen = false;
            foreach ($rawOpts as $opt) {
                if (!is_array($opt)) { continue; }
                $label = trim((string) ($opt['label'] ?? ''));
                if ($label === '') { continue; }
                $pair = trim((string) ($opt['pair'] ?? ''));
                $correct = !empty($opt['correct']);
                if ($correct && $type === 'single' && $correctSeen) { $correct = false; }
                if ($correct) { $correctSeen = true; }
                $options[] = ['label' => $label, 'correct' => $correct, 'pair' => $pair];
            }

            if (count($options) < 2) { continue; }                        // tout type à options : ≥ 2

            if ($type === 'single' || $type === 'multiple') {
                if (!$correctSeen) { continue; }                          // ≥ 1 bonne réponse cochée
            } elseif ($type === 'match') {
                // Chaque ligne doit avoir une cible à associer.
                foreach ($options as $o) { if ($o['pair'] === '') { continue 2; } }
            }
            // ('order' : l'ordre saisi EST la bonne réponse, rien d'autre à valider)

            $base['options'] = $options;
            $out[] = $base;
        }
        return $out;
    }

    /**
     * Récupère l'image envoyée pour la question d'index $k dans le champ multiple
     * <input type="file" name="qimg[$k]">, la valide/redimensionne via Upload, et
     * renvoie le nom stocké (ou null si aucune image / refusée).
     */
    private function uploadQuestionImage($k): ?string
    {
        $f = $_FILES['qimg'] ?? null;
        if (!is_array($f) || !isset($f['name'][$k])) {
            return null;
        }
        if ((int) ($f['error'][$k] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        // On reconstruit un tableau « fichier unique » et on réutilise Upload::image.
        $_FILES['__qimg_one'] = [
            'name'     => $f['name'][$k],
            'type'     => $f['type'][$k] ?? '',
            'tmp_name' => $f['tmp_name'][$k],
            'error'    => $f['error'][$k],
            'size'     => $f['size'][$k] ?? 0,
        ];
        try {
            return Upload::image('__qimg_one', 'quizzes');
        } catch (\RuntimeException $e) {
            return null; // image refusée pour cette question : on l'ignore sans bloquer
        } finally {
            unset($_FILES['__qimg_one']);
        }
    }

    /**
     * Affiche un questionnaire : /rpn/quiz/show?id=5
     *  - si le membre a déjà répondu → résultats (son score + corrigé) ;
     *  - sinon → formulaire pour répondre.
     */
    public function show(): void
    {
        $this->guard();
        $id   = (int) ($_GET['id'] ?? 0);
        $quiz = $id ? Quiz::find($id) : null;

        // Introuvable, ou brouillon consulté par quelqu'un sans droit → 404.
        if (!$quiz || ((int) $quiz['active'] !== 1 && !$this->canManage($quiz))) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        $uid       = (int) (Session::user()['id'] ?? 0);
        $questions = Quiz::questions($id);
        $response  = Quiz::responseFor($id, $uid);
        $myAnswers = $response ? Quiz::answersFor((int) $response['id']) : [];
        $myTexts   = $response ? Quiz::textsFor((int) $response['id']) : [];

        // Ce questionnaire bloque-t-il l'accès pour ce membre (obligatoire non terminé) ?
        $mustComplete = !Session::isAdmin()
            && (int) ($quiz['required'] ?? 0) === 1
            && (int) ($quiz['active'] ?? 0) === 1
            && !empty($questions)
            && !Quiz::isCompletedBy($quiz, $uid);

        // Ordre aléatoire (option) : seulement en mode « répondre », pas dans le corrigé.
        $canRetry = Quiz::canAttempt($quiz, $uid);
        $asForm   = !$response || (isset($_GET['redo']) && $canRetry) || $mustComplete;
        if ($asForm && (int) ($quiz['shuffle'] ?? 0) === 1) {
            shuffle($questions);
            foreach ($questions as &$qShuf) {
                if (!empty($qShuf['options']) && is_array($qShuf['options'])) {
                    shuffle($qShuf['options']);
                }
            }
            unset($qShuf);
        }

        view('quiz/show', [
            'user'         => Session::user(),
            'quiz'         => $quiz,
            'questions'    => $questions,
            'canManage'    => $this->canManage($quiz),
            'participants' => Quiz::responseCount($id),
            'response'     => $response,   // null si pas encore répondu
            'myAnswers'    => $myAnswers,  // [question_id => [option_id…]]
            'myTexts'      => $myTexts,    // [question_id => texte saisi] (numeric/text/fill/match)
            'mustComplete' => $mustComplete,
            'maxAttempts'  => (int) ($quiz['max_attempts'] ?? 0),
            'attemptsUsed' => $response ? (int) $response['attempts'] : 0,
            'canRetry'     => $canRetry,
            'error'        => Session::get('quiz_error'),
            'notice'       => Session::get('quiz_notice'),
        ]);
        Session::remove('quiz_error');
        Session::remove('quiz_notice');
    }

    /**
     * Enregistre les réponses d'un membre et calcule son score.
     * Le formulaire envoie answer[question_id] (single, valeur = option_id)
     * ou answer[question_id][] (multiple, valeurs = option_id cochés).
     */
    public function submit(): void
    {
        $this->guard();
        $id   = (int) ($_POST['id'] ?? 0);
        $quiz = $id ? Quiz::find($id) : null;
        if (!$quiz || ((int) $quiz['active'] !== 1 && !$this->canManage($quiz))) {
            redirect('quiz');
        }

        $questions = Quiz::questions($id);
        if (empty($questions)) {
            Session::set('quiz_error', 'Ce questionnaire ne contient aucune question.');
            redirect('quiz/show?id=' . $id);
        }

        // Limite de tentatives (sauf questionnaire obligatoire, jamais bloqué).
        $uid = (int) (Session::user()['id'] ?? 0);
        if (!Quiz::canAttempt($quiz, $uid)) {
            Session::set('quiz_error', 'Tu as atteint le nombre maximum de tentatives autorisées pour ce questionnaire.');
            redirect('quiz/show?id=' . $id . '#resultats');
        }

        // Saisies du membre, par famille de champ (selon le type de question).
        $given      = is_array($_POST['answer'] ?? null)        ? $_POST['answer']        : []; // single/multiple
        $givenText  = is_array($_POST['answer_text'] ?? null)   ? $_POST['answer_text']   : []; // numeric/text
        $givenFill  = is_array($_POST['answer_fill'] ?? null)   ? $_POST['answer_fill']   : []; // fill (un champ par trou)
        $givenOrder = is_array($_POST['answer_order'] ?? null)  ? $_POST['answer_order']  : []; // order (suite d'option_id)
        $givenMatch = is_array($_POST['answer_match'] ?? null)  ? $_POST['answer_match']  : []; // match (« optId:cible,… »)

        $picks = [];
        $texts = [];
        $score = 0;
        $graded = 0; // nombre de questions réellement notées (hors exercices interactifs)

        foreach ($questions as $q) {
            $qid    = (int) $q['id'];
            $type   = Quiz::normalizeType((string) $q['type']);

            // Exercice interactif : exploration, jamais noté ni compté dans le total.
            if ($type === 'interactive') {
                continue;
            }
            $graded++;
            $optIds = array_map(fn ($o) => (int) $o['id'], $q['options']);

            $chosen = [];     // option_id (single/multiple/order)
            $text   = null;   // réponse saisie (numeric/text/fill/match)

            switch ($type) {
                case 'numeric':
                case 'text':
                    $text = trim((string) ($givenText[$qid] ?? ''));
                    break;

                case 'fill':
                    $blanks = (array) ($givenFill[$qid] ?? []);
                    $text = implode('|', array_map(fn ($b) => trim((string) $b), $blanks));
                    break;

                case 'order':
                    // « id,id,id » → on ne garde que des option_id réels de CETTE question, sans doublon.
                    foreach (explode(',', (string) ($givenOrder[$qid] ?? '')) as $v) {
                        $v = (int) trim($v);
                        if (in_array($v, $optIds, true) && !in_array($v, $chosen, true)) { $chosen[] = $v; }
                    }
                    $text = implode(',', $chosen);
                    break;

                case 'match':
                    $text = trim((string) ($givenMatch[$qid] ?? ''));
                    break;

                default: // single / multiple
                    foreach ((array) ($given[$qid] ?? []) as $v) {
                        $v = (int) $v;
                        if (in_array($v, $optIds, true) && !in_array($v, $chosen, true)) { $chosen[] = $v; }
                    }
            }

            $picks[$qid] = $chosen;
            if ($text !== null && $text !== '') { $texts[$qid] = $text; }

            if (Quiz::gradeQuestion($q, $chosen, $text)) {
                $score++;
            }
        }

        $me = Session::user();
        Quiz::saveResponse(
            $id,
            (int) ($me['id'] ?? 0),
            $me['name'] ?: ($me['email'] ?? ''),
            $picks,
            $score,
            $graded, // total = questions notées (les exercices interactifs sont exclus)
            $texts
        );

        // Cas d'un questionnaire OBLIGATOIRE pour un membre (hors admin) : on
        // vérifie s'il vient de le « terminer » (répondre, ou tout réussir selon
        // le réglage) pour le laisser sortir vers l'application.
        if (!Session::isAdmin() && (int) ($quiz['required'] ?? 0) === 1 && (int) ($quiz['active'] ?? 0) === 1) {
            $done = Quiz::isCompletedBy($quiz, (int) ($me['id'] ?? 0));
            if ($done) {
                Session::set('quiz_notice', '✅ Questionnaire obligatoire terminé : ' . $score . ' / ' . count($questions) . '. Bon retour sur l\'application !');
                redirect('dashboard');
            }
            // Échoué alors qu'il faut tout réussir → on le renvoie le refaire.
            Session::set('quiz_error', 'Il faut TOUTES les bonnes réponses pour continuer. Ton score : '
                . $score . ' / ' . count($questions) . '. Réessaie pour accéder à l\'application.');
            redirect('quiz/show?id=' . $id . '&forced=1');
        }

        Session::set('quiz_notice', '✅ Réponses enregistrées : ' . $score . ' / ' . count($questions) . '.');
        redirect('quiz/show?id=' . $id . '#resultats');
    }

    /** Bascule rapide publié ⇄ brouillon (auteur ou admin). */
    public function toggle(): void
    {
        $this->guard();
        $id   = (int) ($_POST['id'] ?? 0);
        $quiz = $id ? Quiz::find($id) : null;
        if ($quiz && $this->canManage($quiz)) {
            $wasActive = (int) $quiz['active'] === 1;
            Quiz::setActive($id, $wasActive ? 0 : 1);
            if (!$wasActive) {
                $this->notifyPublished($id, $quiz['title']);
            } else {
                Session::set('quiz_notice', '📝 Questionnaire repassé en brouillon.');
            }
        }
        redirect('quiz/show?id=' . $id);
    }

    /** Supprime un questionnaire (auteur ou admin) + tout ce qui en dépend. */
    public function delete(): void
    {
        $this->guard();
        $id   = (int) ($_POST['id'] ?? 0);
        $quiz = $id ? Quiz::find($id) : null;
        if ($quiz && $this->canManage($quiz)) {
            // Efface les fichiers images : couverture + image de chaque question.
            if (!empty($quiz['image'])) { Upload::delete($quiz['image'], 'quizzes'); }
            foreach (Quiz::questions($id) as $q) {
                if (!empty($q['image'])) { Upload::delete($q['image'], 'quizzes'); }
            }
            Quiz::delete($id);
            Session::set('quiz_notice', '🗑️ Questionnaire supprimé.');
        }
        redirect('quiz');
    }

    /** Prévient tous les membres (sauf l'auteur) qu'un questionnaire est publié. */
    private function notifyPublished(int $id, string $title): void
    {
        $me         = Session::user();
        $authorId   = (int) ($me['id'] ?? 0);
        $authorName = ($me['name'] ?? '') ?: ($me['email'] ?? 'Un membre');
        $n = 0;
        foreach (User::all() as $member) {
            $mid = (int) $member['id'];
            if ($mid <= 0 || $mid === $authorId) {
                continue;
            }
            Notification::add(
                $mid,
                $authorName . ' a publié un questionnaire : « ' . $title . ' ».',
                '❓', 'quiz/show?id=' . $id
            );
            $n++;
        }
        Session::set('quiz_notice', '✅ Questionnaire publié.'
            . ($n > 0 ? ' ' . $n . ' membre' . ($n > 1 ? 's' : '') . ' prévenu' . ($n > 1 ? 's' : '') . '.' : ''));
    }
}

<?php
/**
 * CONTRÔLEUR API (JSON)
 * Petite API pour piloter le site à distance : créer des articles et des
 * questionnaires (quiz), éventuellement liés entre eux.
 *
 * Toute la « plomberie » commune (lecture du corps, clé API, réponses JSON,
 * URL absolue, téléchargement d'image) est factorisée dans la classe de base
 * ApiKernel : ce contrôleur ne contient plus que la logique métier de chaque
 * action.
 *
 * Points d'entrée :
 *   GET  /rpm/api/ping      → test de disponibilité (public)
 *   POST /rpm/api/article   → crée un article            (clé API requise)
 *   POST /rpm/api/quiz      → crée un quiz (+ lien article) (clé API requise)
 *
 * SÉCURITÉ : les deux écritures appellent requireKey() AVANT toute action ;
 * sans clé valide → 401, rien n'est créé. Les modèles n'utilisent que des
 * requêtes préparées (pas d'injection SQL) et le HTML des articles est filtré
 * par Html::clean (anti-XSS) ; le texte des quiz est stocké sans balise et
 * échappé à l'affichage.
 */
class ApiController extends ApiKernel
{
    /** GET /rpm/api/ping — test de disponibilité (aucune donnée modifiée). */
    public function ping(): void
    {
        $this->json(['ok' => true, 'service' => 'RPN API', 'date' => date('c')]);
    }

    /**
     * POST /rpm/api/article — crée un article.
     *   title*       (string)  — titre
     *   content*     (string)  — contenu HTML (filtré par Html::clean)
     *   template     (string)  — clé de mise en page (défaut « standard »)
     *   active       (0|1)     — 1 = publié (défaut), 0 = brouillon
     *   author_name  (string)  — nom affiché de l'auteur (défaut « RPN »)
     *   parent_id    (int)     — rattache à un article parent existant
     *   image_url    (string)  — couverture téléchargée depuis une URL
     *   gallery_urls (array)   — URLs d'images de galerie
     *   (multipart : champs fichier « cover » et « photos[] » également gérés)
     */
    public function createArticle(): void
    {
        $this->readInput();
        $this->requireKey();

        $title   = $this->text('title', 255);
        $content = (string) $this->field('content');
        if ($title === '' || trim(strip_tags($content)) === '') {
            $this->fail('Champs requis : title et content.', 422);
        }

        $template = ArticleTemplate::key((string) $this->field('template', 'standard'));
        $active   = !empty($this->field('active', 1)) ? 1 : 0;
        $author   = $this->text('author_name', 190);
        if ($author === '') { $author = 'RPN'; }

        // Sous-article : parent_id (doit exister, sinon ignoré → article racine).
        $parentId = (int) $this->field('parent_id', 0);
        $parent   = $parentId > 0 ? Article::find($parentId) : null;
        $parentId = $parent ? (int) $parent['id'] : null;

        // Couverture : fichier « cover » (multipart) OU « image_url » (téléchargée).
        $cover = null;
        try { $cover = Upload::image('cover', 'articles'); } catch (\Throwable $e) { $cover = null; }
        if (!$cover) {
            $imgUrl = trim((string) $this->field('image_url', ''));
            if ($imgUrl !== '') { $cover = $this->downloadImage($imgUrl); }
        }

        // ANTI-DOUBLON : si un article du même titre (créé par l'API) existe
        // déjà, on le MET À JOUR au lieu d'en créer un second. Republier le même
        // contenu est donc idempotent (aucun doublon).
        $existing   = Article::findByTitleAuthor($title, 0);
        $createdNew = ($existing === null);

        if ($createdNew) {
            $id = Article::create([
                'title'       => $title,
                'content'     => Html::clean($content),
                'image'       => $cover,
                'template'    => $template,
                'active'      => $active,
                'parent_id'   => $parentId,
                'author_id'   => 0,
                'author_name' => $author,
            ]);

            // Galerie (à la création seulement, pour ne pas dupliquer les photos).
            foreach (Upload::images('photos', 'articles') as $g) {
                ArticleImage::add($id, $g);
            }
            $gurls = $this->field('gallery_urls', []);
            if (is_array($gurls)) {
                foreach ($gurls as $gu) {
                    $n = $this->downloadImage((string) $gu);
                    if ($n) { ArticleImage::add($id, $n); }
                }
            }
        } else {
            $id = (int) $existing['id'];
            $data = [
                'title'     => $title,
                'content'   => Html::clean($content),
                'template'  => $template,
                'active'    => $active,
                'parent_id' => $parentId,
            ];
            if ($cover) { $data['image'] = $cover; } // on ne change l'image que si une nouvelle est fournie
            Article::update($id, $data);
        }

        $this->json([
            'ok'       => true,
            'id'       => $id,
            'created'  => $createdNew,
            'active'   => $active,
            'template' => $template,
            'url'      => $this->absoluteUrl('article', ['id' => $id]),
        ], $createdNew ? 201 : 200);
    }

    /**
     * POST /rpm/api/quiz — crée un questionnaire et, en option, le rattache à
     * un article (table article_quizzes).
     *   title*        (string)  — titre du questionnaire
     *   description   (string)  — présentation (facultatif)
     *   active        (0|1)     — 1 = publié (défaut)
     *   author_name   (string)  — auteur affiché (défaut « RPN »)
     *   article_id    (int)     — rattache le quiz à cet article (facultatif)
     *   max_attempts  (int)     — 0 = illimité (défaut)
     *   questions*    (array)   — [{ body, type:'single'|'multiple',
     *                               options:[{ label, correct:bool }, …] }, …]
     */
    public function createQuiz(): void
    {
        $this->readInput();
        $this->requireKey();

        $title     = $this->text('title', 200);
        $questions = $this->field('questions', []);
        if ($title === '' || !is_array($questions) || count($questions) === 0) {
            $this->fail('Champs requis : title et questions[].', 422);
        }

        $description = (string) $this->field('description', '');
        $active      = !empty($this->field('active', 1)) ? 1 : 0;
        $author      = $this->text('author_name', 150);
        if ($author === '') { $author = 'RPN'; }
        $articleId   = (int) $this->field('article_id', 0);
        $maxAttempts = max(0, (int) $this->field('max_attempts', 0));

        // Couverture : fichier « cover » (multipart), sinon « image_url »
        // (téléchargée), sinon HÉRITÉE de la couverture de l'article lié.
        $cover = null;
        try { $cover = Upload::image('cover', 'quizzes'); } catch (\Throwable $e) { $cover = null; }
        if (!$cover) {
            $imgUrl = trim((string) $this->field('image_url', ''));
            if ($imgUrl !== '') { $cover = $this->downloadImage($imgUrl, 'quizzes'); }
        }
        if (!$cover && $articleId > 0) {
            $cover = $this->coverFromArticle($articleId);
        }

        // ANTI-DOUBLON : un quiz du même titre (créé par l'API) → mise à jour
        // (on remplace ses questions) au lieu d'un second quiz identique.
        $existing   = Quiz::findByTitleAuthor($title, 0);
        $createdNew = ($existing === null);

        if ($createdNew) {
            $quizId = Quiz::create([
                'title'        => $title,
                'description'  => trim(strip_tags($description)),
                'image'        => $cover,
                'active'       => $active,
                'max_attempts' => $maxAttempts,
                'author_id'    => 0,
                'author_name'  => $author,
            ]);
        } else {
            $quizId = (int) $existing['id'];
            $upd = [
                'title'        => $title,
                'description'  => trim(strip_tags($description)),
                'active'       => $active,
                'max_attempts' => $maxAttempts,
            ];
            if ($cover) { $upd['image'] = $cover; }
            Quiz::update($quizId, $upd);
            Quiz::deleteQuestions($quizId); // on remplace l'ancien jeu de questions
        }

        // Crée questions + options. On ignore les questions invalides.
        // Le texte des questions/options est stocké SANS balise (anti-XSS).
        // Types acceptés : single | multiple | numeric | text | fill | order | match.
        $qpos = 0; $nQ = 0; $nO = 0;
        foreach ($questions as $q) {
            if (!is_array($q)) { continue; }
            $body = trim(strip_tags((string) ($q['body'] ?? '')));
            if ($body === '') { continue; }
            $type = Quiz::normalizeType((string) ($q['type'] ?? 'single'));
            $expl = trim(strip_tags((string) ($q['explanation'] ?? '')));

            // ---- Exercice interactif : clé d'un manipulable intégré --------------
            if ($type === 'interactive') {
                $widget = trim(strip_tags((string) ($q['answer'] ?? ($q['widget'] ?? ''))));
                if (!Quiz::isWidget($widget)) { continue; }
                Quiz::addQuestion($quizId, $body, 'interactive', $qpos++, null, $expl ?: null, $widget, 0); $nQ++;
                continue;
            }

            // ---- Types « à saisir » : pas d'options, une réponse attendue --------
            if (in_array($type, ['numeric', 'text', 'fill'], true)) {
                $answer = trim(strip_tags((string) ($q['answer'] ?? '')));
                if ($type === 'numeric') {
                    if (Quiz::toNumber($answer) === null) { continue; }
                } elseif ($type === 'fill') {
                    $blanks = preg_match_all('/\[[^\]]*\]/', $body);
                    $parts  = array_filter(array_map('trim', explode('|', $answer)), fn ($v) => $v !== '');
                    if ($blanks < 1 || count($parts) !== $blanks) { continue; }
                    $answer = implode('|', $parts);
                } elseif ($answer === '') {
                    continue;
                }
                $tol = max(0, (float) (Quiz::toNumber((string) ($q['tolerance'] ?? '0')) ?? 0));
                Quiz::addQuestion($quizId, $body, $type, $qpos++, null, $expl ?: null, $answer, $tol); $nQ++;
                continue;
            }

            // ---- Types « à options » : single / multiple / order / match ---------
            $opts = $q['options'] ?? [];
            if (!is_array($opts) || count($opts) < 2) { continue; }

            $clean = [];
            $hasCorrect = false; $allPaired = true;
            foreach ($opts as $o) {
                if (!is_array($o)) { continue; }
                $label = trim(strip_tags((string) ($o['label'] ?? '')));
                if ($label === '') { continue; }
                $pair  = trim(strip_tags((string) ($o['pair'] ?? '')));
                if (!empty($o['correct'])) { $hasCorrect = true; }
                if ($pair === '') { $allPaired = false; }
                $clean[] = ['label' => $label, 'correct' => !empty($o['correct']), 'pair' => $pair];
            }
            if (count($clean) < 2) { continue; }
            if (($type === 'single' || $type === 'multiple') && !$hasCorrect) { continue; }
            if ($type === 'match' && !$allPaired) { continue; }

            $qid = Quiz::addQuestion($quizId, $body, $type, $qpos++, null, $expl ?: null); $nQ++;
            $opos = 0;
            foreach ($clean as $o) {
                Quiz::addOption($qid, $o['label'], $o['correct'], $opos++, $o['pair'] ?: null); $nO++;
            }
        }

        if ($nQ === 0) {
            if ($createdNew) { Quiz::delete($quizId); } // on n'efface que ce qu'on vient de créer
            $this->fail('Aucune question valide (il faut au moins 2 options et 1 bonne réponse cochée).', 422);
        }

        // Rattachement à un article existant (on conserve les liens déjà en place).
        $linked = null;
        if ($articleId > 0 && Article::find($articleId)) {
            $ids   = Article::quizIds($articleId);
            $ids[] = $quizId;
            Article::setQuizzes($articleId, $ids);
            $linked = $articleId;
        }

        $this->json([
            'ok'             => true,
            'quiz_id'        => $quizId,
            'created'        => $createdNew,
            'questions'      => $nQ,
            'options'        => $nO,
            'linked_article' => $linked,
            'image'          => $cover,
            'active'         => $active,
            'url'            => $this->absoluteUrl('quiz/show', ['id' => $quizId]),
        ], $createdNew ? 201 : 200);
    }

    /**
     * POST /rpm/api/quiz/image — définit (ou remplace) la couverture d'un quiz
     * existant. **Clé requise.**
     *   quiz_id*    (int)    — le quiz à illustrer
     *   image_url   (string) — image téléchargée depuis une URL
     *   cover       (fichier)— image envoyée en multipart
     *   article_id  (int)    — à défaut d'image fournie, hérite de la couverture
     *                          de cet article
     */
    public function setQuizImage(): void
    {
        $this->readInput();
        $this->requireKey();

        $quizId = (int) $this->field('quiz_id', $this->field('id', 0));
        $quiz   = Quiz::find($quizId);
        if (!$quiz) {
            $this->fail('Quiz introuvable.', 404);
        }

        // Source de l'image : fichier « cover », sinon « image_url », sinon
        // couverture héritée d'un article.
        $cover = null;
        try { $cover = Upload::image('cover', 'quizzes'); } catch (\Throwable $e) { $cover = null; }
        if (!$cover) {
            $imgUrl = trim((string) $this->field('image_url', ''));
            if ($imgUrl !== '') { $cover = $this->downloadImage($imgUrl, 'quizzes'); }
        }
        if (!$cover) {
            $cover = $this->coverFromArticle((int) $this->field('article_id', 0));
        }
        if (!$cover) {
            $this->fail('Aucune image : fournis « cover » (fichier), « image_url », ou « article_id » d\'un article ayant une couverture.', 422);
        }

        $old = $quiz['image'] ?? null;
        Quiz::update($quizId, [
            'title'        => $quiz['title'],
            'description'  => $quiz['description'] ?? '',
            'active'       => (int) ($quiz['active'] ?? 1),
            'max_attempts' => (int) ($quiz['max_attempts'] ?? 0),
            'image'        => $cover,
        ]);
        if ($old && $old !== $cover) {
            try { Upload::delete($old, 'quizzes'); } catch (\Throwable $e) { /* ignore */ }
        }

        $this->json(['ok' => true, 'quiz_id' => $quizId, 'image' => $cover], 200);
    }

    /**
     * POST /rpm/api/article/image — définit (ou remplace) la couverture d'un
     * article existant. **Clé requise.**
     *   article_id* (int)    — l'article à illustrer
     *   image_url   (string) — image téléchargée depuis une URL
     *   cover       (fichier)— image envoyée en multipart
     */
    public function setArticleImage(): void
    {
        $this->readInput();
        $this->requireKey();

        $articleId = (int) $this->field('article_id', $this->field('id', 0));
        $art = Article::find($articleId);
        if (!$art) {
            $this->fail('Article introuvable.', 404);
        }

        $cover = null;
        try { $cover = Upload::image('cover', 'articles'); } catch (\Throwable $e) { $cover = null; }
        if (!$cover) {
            $imgUrl = trim((string) $this->field('image_url', ''));
            if ($imgUrl !== '') { $cover = $this->downloadImage($imgUrl, 'articles'); }
        }
        if (!$cover) {
            $this->fail('Aucune image : fournis « cover » (fichier) ou « image_url ».', 422);
        }

        $old = $art['image'] ?? null;
        Article::update($articleId, [
            'title'     => $art['title'],
            'content'   => $art['content'],
            'template'  => $art['template'] ?? 'standard',
            'active'    => (int) ($art['active'] ?? 1),
            'parent_id' => $art['parent_id'] ?? null,
            'image'     => $cover,
        ]);
        if ($old && $old !== $cover) {
            try { Upload::delete($old, 'articles'); } catch (\Throwable $e) { /* déjà absent */ }
        }

        $this->json([
            'ok'         => true,
            'article_id' => $articleId,
            'image'      => $cover,
            'url'        => $this->absoluteUrl('article', ['id' => $articleId]),
        ], 200);
    }

    /**
     * Copie la couverture d'un article vers uploads/quizzes/ (l'original n'est
     * pas modifié). Renvoie le nom de fichier créé, ou null.
     */
    private function coverFromArticle(int $articleId): ?string
    {
        $art = $articleId > 0 ? Article::find($articleId) : null;
        if (!$art || empty($art['image'])) {
            return null;
        }
        $src = APP_ROOT . '/uploads/articles/' . $art['image'];
        if (!is_file($src)) {
            return null;
        }
        try { return Upload::imageFromPath($src, 'quizzes'); }
        catch (\Throwable $e) { return null; }
    }
}

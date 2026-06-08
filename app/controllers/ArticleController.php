<?php
/**
 * CONTRÔLEUR Article
 * Lecture ET écriture des articles, pour tout membre connecté.
 *  - n'importe quel membre peut créer un article ;
 *  - il peut modifier / supprimer SES propres articles ;
 *  - un administrateur peut modifier / supprimer TOUS les articles (modération).
 * Les images sont gérées via la classe Upload.
 */
class ArticleController
{
    /** Sous-dossier de uploads/ où sont rangées les images d'articles. */
    private const IMG_DIR = 'articles';

    /** Bloque l'accès aux visiteurs non connectés → page de connexion. */
    private function guard(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
    }

    /**
     * Articles pouvant servir de parent (pour le sélecteur du formulaire) :
     * articles de premier niveau que l'utilisateur peut gérer (les siens, ou
     * tous pour un admin), en excluant l'article en cours d'édition.
     */
    private function parentChoices(?int $excludeId): array
    {
        $uid     = (int) (Session::user()['id'] ?? 0);
        $isAdmin = Session::isAdmin();
        return array_values(array_filter(Article::roots(), function ($r) use ($uid, $isAdmin, $excludeId) {
            if ($excludeId && (int) $r['id'] === $excludeId) {
                return false;
            }
            return $isAdmin || (int) $r['author_id'] === $uid;
        }));
    }

    /** Peut modifier/supprimer cet article ? (son auteur, ou un admin) */
    private function canManage(array $article): bool
    {
        if (Session::isAdmin()) {
            return true;
        }
        $u = Session::user();
        return (int) ($article['author_id'] ?? -1) === (int) ($u['id'] ?? -2);
    }

    /**
     * Liste des articles : /rpm/articles — PUBLIQUE.
     * Un visiteur (non connecté) voit tous les articles PUBLIÉS.
     * Un membre voit en plus ses propres brouillons ; un admin voit tout.
     */
    public function index(): void
    {
        $articles = Article::roots(); // seuls les articles de premier niveau (les sous-articles s'ouvrent via leur parent)
        $uid      = (int) (Session::user()['id'] ?? 0);
        $isAdmin  = Session::isAdmin();

        // Hors admin : on ne montre que les articles publiés, NON masqués par
        // signalements (sauf protégé/annonce)… et les siens (brouillons compris).
        if (!$isAdmin) {
            $flags = Article::flagCountsFor(array_map(fn ($a) => (int) $a['id'], $articles));
            $articles = array_values(array_filter($articles, function ($a) use ($uid, $flags) {
                if ($uid && (int) $a['author_id'] === $uid) {
                    return true; // l'auteur voit toujours les siens
                }
                if ((int) $a['active'] !== 1) {
                    return false;
                }
                return !Article::isFlagHidden($a, $flags[(int) $a['id']] ?? 0);
            }));
        }

        // Sous-articles VISIBLES de chaque article (affichés en petits carrés sur la carte).
        $children = [];
        foreach ($articles as $a) {
            $kids = Article::children((int) $a['id']);
            if (!$isAdmin) {
                $kFlags = Article::flagCountsFor(array_map(fn ($c) => (int) $c['id'], $kids));
                $kids = array_values(array_filter($kids, function ($c) use ($uid, $kFlags) {
                    if ($uid && (int) $c['author_id'] === $uid) {
                        return true;
                    }
                    if ((int) $c['active'] !== 1) {
                        return false;
                    }
                    return !Article::isFlagHidden($c, $kFlags[(int) $c['id']] ?? 0);
                }));
            }
            $children[(int) $a['id']] = $kids;
        }

        view('articles/index', [
            'user'     => Session::user(), // peut être null (visiteur non connecté)
            'articles' => $articles,
            'children' => $children,
            'reviews'  => ArticleReview::summaryFor(array_map(fn ($a) => (int) $a['id'], $articles)),
            'views'    => ArticleView::countsFor(array_map(fn ($a) => (int) $a['id'], $articles)),
        ]);
    }

    /**
     * Détail d'un article : /rpm/article?id=5
     * Un article PUBLIÉ (active=1) est visible PAR TOUT LE MONDE, même sans connexion.
     * Un brouillon n'est visible que par son auteur ou un admin.
     */
    public function show(): void
    {
        $id      = (int) ($_GET['id'] ?? 0);
        $article = $id ? Article::find($id) : null;

        // Introuvable, ou brouillon consulté par quelqu'un sans droit → faux 404.
        if (!$article || ((int) $article['active'] !== 1 && !$this->canManage($article))) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        // Masqué au public suite à des signalements (sauf protégé/annonce) :
        // invisible pour tout le monde sauf l'auteur et les admins.
        if (!$this->canManage($article) && Article::isFlagHidden($article)) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        // Protégé par mot de passe : tant qu'on n'a pas saisi le bon mot de passe
        // (ou qu'on n'est pas l'auteur/admin), on affiche l'écran de déverrouillage.
        if (Article::hasPassword($article) && !$this->canManage($article)
            && empty($_SESSION['article_unlocked'][(int) $article['id']])) {
            view('articles/locked', [
                'article' => $article,
                'error'   => Session::get('article_lock_error'),
            ]);
            Session::remove('article_lock_error');
            return;
        }

        // Comptabilise la vue par adresse IP (une IP = une vue, réactualisation incluse).
        ArticleView::record((int) $article['id'], client_ip());
        // Mémorise aussi quel membre inscrit a consulté l'article (le cas échéant).
        if (Session::has('user')) {
            ArticleView::recordMember((int) $article['id'], (int) (Session::user()['id'] ?? 0));
        }

        // Sous-articles visibles (publiés et non masqués, ou tous si on peut les gérer).
        $children = array_values(array_filter(
            Article::children((int) $article['id']),
            fn ($c) => $this->canManage($c) || ((int) $c['active'] === 1 && !Article::isFlagHidden($c))
        ));

        // Article parent (pour le fil d'Ariane), seulement s'il est visible.
        $parent = !empty($article['parent_id']) ? Article::find((int) $article['parent_id']) : null;
        if ($parent && (int) $parent['active'] !== 1 && !$this->canManage($parent)) {
            $parent = null;
        }

        // Fil de discussion + signalements (nombre + déjà signalé par moi).
        $cuid       = (int) (Session::user()['id'] ?? 0);
        $comments   = ArticleComment::forArticle((int) $article['id']);
        $flagCounts = ArticleComment::flagCounts((int) $article['id']);
        $myFlags    = $cuid ? ArticleComment::userFlags((int) $article['id'], $cuid) : [];
        foreach ($comments as &$c) {
            $c['flags']         = $flagCounts[(int) $c['id']] ?? 0;
            $c['flagged_by_me'] = in_array((int) $c['id'], $myFlags, true);
        }
        unset($c);

        view('articles/show', [
            'user'      => Session::user(),
            'article'   => $article,
            'canManage' => $this->canManage($article),
            'views'     => ArticleView::count((int) $article['id']),
            // Photo + nom de l'auteur (avatar rond affiché en haut de l'article).
            'authorAvatar' => avatar_url(
                !empty($article['author_id']) ? (string) (User::findById((int) $article['author_id'])['picture'] ?? '') : '',
                (string) ($article['author_name'] ?: 'Auteur')
            ),
            'authorName' => (string) ($article['author_name'] ?: 'Auteur'),
            // Liste des membres inscrits ayant vu l'article (réservée à l'auteur/admin).
            'viewers'   => $this->canManage($article) ? ArticleView::members((int) $article['id']) : [],
            // Adresses IP des visiteurs (pour repérer les non-membres) — auteur/admin.
            'viewerIps' => $this->canManage($article) ? ArticleView::ips((int) $article['id']) : [],
            'images'    => ArticleImage::forArticle((int) $article['id']),
            'files'     => ArticleFile::forArticle((int) $article['id']),
            'children'  => $children,
            'parent'    => $parent,
            'reviews'       => ArticleReview::forArticle((int) $article['id']),
            'reviewSummary' => ArticleReview::summary((int) $article['id']),
            'myReview'      => Session::has('user')
                ? ArticleReview::mine((int) $article['id'], (int) (Session::user()['id'] ?? 0))
                : null,
            'comments'      => $comments,
            // Questionnaires associés à proposer à la fin de la lecture (publiés),
            // enrichis du nombre de questions et de la participation du membre.
            // L'auteur/admin voit les quiz associés MÊME en brouillon (pour les publier) ;
            // le public ne voit que les quiz publiés.
            'articleQuizzes' => array_map(function ($q) use ($cuid) {
                $q['questionCount'] = Quiz::questionCount((int) $q['id']);
                $q['myResponse']    = $cuid ? Quiz::responseFor((int) $q['id'], $cuid) : null;
                return $q;
            }, Article::quizzesFor((int) $article['id'], !$this->canManage($article))),
            // Signalement de l'article (bouton « Signaler » + bandeau pour l'auteur/admin).
            'flagCount'     => Article::flagCount((int) $article['id']),
            'iFlagged'      => Article::userFlagged((int) $article['id'], $cuid),
            'flagLimit'     => Article::FLAG_LIMIT,
            'flagHidden'    => Article::isFlagHidden($article),
            'reviewError'   => Session::get('article_error'),
            'notice'        => Session::get('article_notice'),
        ]);
        Session::remove('article_error');
        Session::remove('article_notice');
    }

    /**
     * Déverrouille un article protégé par mot de passe : vérifie le mot de passe
     * et mémorise l'accès en session (pour cet article). Ouvert à tous (visiteurs
     * compris) afin qu'un article public protégé reste accessible avec le code.
     */
    public function unlock(): void
    {
        $id      = (int) ($_POST['id'] ?? 0);
        $article = $id ? Article::find($id) : null;
        if (!$article) {
            redirect('articles');
        }
        $pwd = (string) ($_POST['access_password'] ?? '');
        if (Article::checkAccess($article, $pwd)) {
            $_SESSION['article_unlocked'][$id] = true;
        } else {
            Session::set('article_lock_error', 'Mot de passe incorrect. Réessaie.');
        }
        redirect('article?id=' . $id);
    }

    /**
     * Un membre signale un article (1 signalement par membre, pas le sien).
     * Au-delà de la limite (3 membres distincts), l'article est masqué au public
     * — sauf s'il est protégé ou en annonce.
     */
    public function report(): void
    {
        $this->guard();
        $id      = (int) ($_POST['id'] ?? 0);
        $article = $id ? Article::find($id) : null;
        if (!$article) {
            redirect('articles');
        }
        $uid = (int) (Session::user()['id'] ?? 0);

        // On ne signale pas son propre article.
        if ((int) ($article['author_id'] ?? 0) === $uid) {
            Session::set('article_error', 'Tu ne peux pas signaler ton propre article.');
            redirect('article?id=' . $id);
        }

        Article::flag($id, $uid);
        $count = Article::flagCount($id);
        if (Article::isFlagHidden($article, $count)) {
            Session::set('article_notice', '🚩 Merci. Cet article a atteint ' . Article::FLAG_LIMIT . ' signalements et n\'est plus visible publiquement.');
        } else {
            Session::set('article_notice', '🚩 Merci, ton signalement a été pris en compte.');
        }
        redirect('article?id=' . $id);
    }

    /** Réinitialise les signalements d'un article (admin) → le rétablit. */
    public function clearFlags(): void
    {
        $this->guard();
        if (!Session::isAdmin()) {
            redirect('articles');
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            Article::clearFlags($id);
            Session::set('article_notice', '✅ Signalements réinitialisés : l\'article est rétabli.');
        }
        redirect('article?id=' . $id);
    }

    /** Formulaire de création (éventuellement d'un sous-article : ?parent=ID). */
    public function create(): void
    {
        $this->guard();

        $parentId = (int) ($_GET['parent'] ?? 0);
        $parent   = $parentId ? Article::find($parentId) : null;

        // Préremplissage via le partage de l'app (manifest share_target) :
        // une autre appli partage un titre / texte / lien → on ouvre le
        // formulaire déjà rempli. Inoffensif si les paramètres sont absents.
        $shareText = trim((string) ($_GET['text'] ?? ''));
        $shareUrl  = trim((string) ($_GET['url'] ?? ''));
        $prefill   = [
            'title'   => trim((string) ($_GET['title'] ?? '')),
            'content' => trim($shareText . ($shareUrl !== '' ? "\n" . $shareUrl : '')),
        ];

        view('articles/form', [
            'user'            => Session::user(),
            'article'         => null,
            'prefill'         => $prefill,
            'error'           => Session::get('article_error'),
            'action'          => url('articles/save'),
            'back'            => $parent ? url('article') . '?id=' . (int) $parent['id'] : url('articles'),
            'templates'       => ArticleTemplate::all(),
            'currentTemplate' => ArticleTemplate::key(Settings::get('default_template', 'standard')),
            'active'          => 1,
            'parentId'        => $parent ? (int) $parent['id'] : 0,
            'parentTitle'     => $parent['title'] ?? '',
            'parents'         => $this->parentChoices(null),
            'quizzes'         => $this->quizChoices(),
            'selectedQuizzes' => [],
            'hasPassword'     => false,
            'memberCode'      => ($uid = (int) (Session::user()['id'] ?? 0)) > 0 ? User::ensureCode($uid) : '',
            'isAdmin'         => Session::isAdmin(),
            'isAnnouncement'  => 0,
            'isProtected'     => 0,
            'isUrgent'        => 0,
        ]);
        Session::remove('article_error');
    }

    /**
     * Questionnaires proposables à l'association d'un article : ceux que
     * l'utilisateur voit (publiés, les siens, ou tous pour un admin).
     */
    private function quizChoices(): array
    {
        $uid     = (int) (Session::user()['id'] ?? 0);
        $isAdmin = Session::isAdmin();
        return array_values(array_filter(Quiz::all(), function ($q) use ($uid, $isAdmin) {
            return $isAdmin || (int) $q['active'] === 1 || ($uid && (int) $q['author_id'] === $uid);
        }));
    }

    /** Formulaire de modification (auteur ou admin uniquement). */
    public function edit(): void
    {
        $this->guard();
        $id      = (int) ($_GET['id'] ?? 0);
        $article = $id ? Article::find($id) : null;
        if (!$article || !$this->canManage($article)) {
            redirect('articles');
        }
        view('articles/form', [
            'user'            => Session::user(),
            'article'         => $article,
            'prefill'         => [],
            'error'           => Session::get('article_error'),
            'action'          => url('articles/save'),
            'back'            => url('articles'),
            'templates'       => ArticleTemplate::all(),
            'currentTemplate' => ArticleTemplate::key($article['template'] ?? 'standard'),
            'images'          => ArticleImage::forArticle((int) $article['id']),
            'files'           => ArticleFile::forArticle((int) $article['id']),
            'active'          => (int) ($article['active'] ?? 1),
            'parentId'        => (int) ($article['parent_id'] ?? 0),
            'parentTitle'     => !empty($article['parent_id']) ? (Article::find((int) $article['parent_id'])['title'] ?? '') : '',
            'parents'         => $this->parentChoices((int) $article['id']),
            'quizzes'         => $this->quizChoices(),
            'selectedQuizzes' => Article::quizIds((int) $article['id']),
            'hasPassword'     => Article::hasPassword($article),
            'memberCode'      => ($acid = ((int) ($article['author_id'] ?? 0) ?: (int) (Session::user()['id'] ?? 0))) > 0 ? User::ensureCode($acid) : '',
            'isAdmin'         => Session::isAdmin(),
            'isAnnouncement'  => (int) ($article['announcement'] ?? 0),
            'isProtected'     => (int) ($article['protected'] ?? 0),
            'isUrgent'        => (int) ($article['urgent'] ?? 0),
        ]);
        Session::remove('article_error');
    }

    /**
     * Aperçu en temps réel (POST, AJAX) : rend l'article avec le VRAI gabarit
     * sans rien enregistrer. Le formulaire recharge ce HTML dans son <iframe>
     * à chaque frappe. Le contenu passe par le même Html::clean() que store(),
     * donc l'aperçu reflète exactement ce qui sera publié.
     */
    public function preview(): void
    {
        $this->guard();

        // Méta (date + auteur) : reprises de l'article existant en édition,
        // sinon valeurs du brouillon en cours (aujourd'hui + membre connecté).
        $createdAt = date('Y-m-d H:i:s');
        $me        = Session::user();
        $author    = ($me['name'] ?? '') ?: ($me['email'] ?? '');

        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $existing = Article::find($id);
            if ($existing && $this->canManage($existing)) {
                $createdAt = $existing['created_at'] ?: $createdAt;
                $author    = $existing['author_name'] ?: $author;
            }
        }

        $article = [
            'title'       => trim($_POST['title'] ?? '') ?: "Titre de l'article",
            'content'     => Html::clean($_POST['content'] ?? ''),
            'created_at'  => $createdAt,
            'author_name' => $author,
        ];

        // Liste des documents (nom/type/taille) envoyée en JSON par le formulaire.
        $docs = json_decode((string) ($_POST['docs'] ?? '[]'), true);

        view('articles/preview', [
            'article'      => $article,
            'template'     => $_POST['template'] ?? 'standard',
            'hasCover'     => !empty($_POST['has_cover']),
            'galleryCount' => (int) ($_POST['gallery_count'] ?? 0),
            'docs'         => is_array($docs) ? $docs : [],
        ]);
    }

    /** Enregistre un article (création ou modification selon l'id caché). */
    public function store(): void
    {
        $this->guard();

        $id      = (int) ($_POST['id'] ?? 0);
        $title   = trim($_POST['title'] ?? '');
        // Mise en forme autorisée mais nettoyée (anti-XSS) avant enregistrement.
        $content = Html::clean($_POST['content'] ?? '');

        // Contenu « vide » = plus aucun texte réel une fois le HTML retiré.
        if ($title === '' || trim(strip_tags($content)) === '') {
            Session::set('article_error', 'Le titre et le contenu sont obligatoires.');
            redirect($id ? 'articles/edit?id=' . $id : 'articles/new');
        }

        // Modèle de mise en page choisi (validé contre la liste connue).
        $template = ArticleTemplate::key($_POST['template'] ?? '');

        // Publié (visible par tout le monde) ou brouillon (privé).
        $active = isset($_POST['active']) ? 1 : 0;

        // Sous-article : rattachement à un article parent (validé).
        $parentId = (int) ($_POST['parent_id'] ?? 0);
        if ($parentId) {
            $p = Article::find($parentId);
            if (!$p || ($id && $parentId === $id)) {
                $parentId = 0; // parent inexistant, ou l'article lui-même → ignoré
            }
        }

        // --- Photos : liste unifiée, une « principale » (= couverture) ---------
        // Droits vérifiés AVANT toute écriture (en édition : auteur ou admin).
        $existing = $id ? Article::find($id) : null;
        if ($id && (!$existing || !$this->canManage($existing))) {
            redirect('articles');
        }

        // Nouvelles photos envoyées (redimensionnées), dans l'ordre → jeton "n<i>".
        $newFiles = Upload::images('photos', self::IMG_DIR);

        // Photos déjà enregistrées (édition) + suppressions demandées par le formulaire.
        $existingCover   = $existing['image'] ?? null;
        $removeCover     = !empty($_POST['remove_cover']);
        $deletedIds      = array_map('intval', (array) ($_POST['delete_photos'] ?? []));
        $existingGallery = $existing ? ArticleImage::forArticle($id) : [];

        // Survivants : jeton => ['filename', 'origin' (cover|gallery|new), 'id'?].
        $survivors = [];
        if ($existingCover && !$removeCover) {
            $survivors['cover'] = ['filename' => $existingCover, 'origin' => 'cover'];
        }
        foreach ($existingGallery as $g) {
            if (in_array((int) $g['id'], $deletedIds, true)) {
                continue;
            }
            $survivors['g' . $g['id']] = ['filename' => $g['filename'], 'origin' => 'gallery', 'id' => (int) $g['id']];
        }
        foreach ($newFiles as $i => $fn) {
            $survivors['n' . $i] = ['filename' => $fn, 'origin' => 'new'];
        }

        // Jeton de la principale (repli : couverture existante, sinon la 1re photo).
        $ptoken = (string) ($_POST['principal'] ?? '');
        if (!isset($survivors[$ptoken])) {
            $ptoken = isset($survivors['cover']) ? 'cover' : (string) (array_key_first($survivors) ?? '');
        }
        $cover = $ptoken !== '' ? $survivors[$ptoken]['filename'] : null;

        // Suppressions physiques (fichiers + lignes de galerie) des photos retirées.
        if ($existingCover && $removeCover) {
            Upload::delete($existingCover, self::IMG_DIR);
        }
        foreach ($existingGallery as $g) {
            if (in_array((int) $g['id'], $deletedIds, true)) {
                Upload::delete($g['filename'], self::IMG_DIR);
                ArticleImage::delete((int) $g['id']);
            }
        }

        // Enregistre l'article avec sa couverture (= photo principale).
        if ($id) {
            Article::update($id, [
                'title' => $title, 'content' => $content, 'template' => $template,
                'active' => $active, 'parent_id' => $parentId ?: null, 'image' => $cover,
            ]);
        } else {
            $me = Session::user();
            $id = Article::create([
                'title'       => $title,
                'content'     => $content,
                'image'       => $cover,
                'template'    => $template,
                'active'      => $active,
                'parent_id'   => $parentId ?: null,
                'author_id'   => (int) ($me['id'] ?? 0),
                'author_name' => $me['name'] ?: ($me['email'] ?? ''),
            ]);
        }

        // « URGENT » (alerte sur le tableau de bord de tout le monde) : ouvert à
        // TOUS les membres — n'importe quel auteur peut mettre son article en avant.
        Article::setUrgent($id, !empty($_POST['urgent']));

        // « Annonce » (mise en avant sur l'accueil) et « protection » : réservées
        // aux administrateurs car elles touchent tout le site.
        if (Session::isAdmin()) {
            Article::setAnnouncement($id, !empty($_POST['announcement']));
            Article::setProtected($id, !empty($_POST['protected']));
        }

        // Questionnaires associés (proposés à la fin de la lecture).
        Article::setQuizzes($id, (array) ($_POST['quizzes'] ?? []));

        // Mot de passe d'accès (facultatif) — 3 modes : aucun / code membre / personnalisé.
        $pwdMode = (string) ($_POST['pwd_mode'] ?? 'none');
        if ($pwdMode === 'none') {
            Article::setAccessPassword($id, null);
        } elseif ($pwdMode === 'code') {
            // Mot de passe = code membre de l'auteur (par défaut).
            $authorId = $existing ? (int) ($existing['author_id'] ?? 0) : (int) (Session::user()['id'] ?? 0);
            $code     = $authorId > 0 ? User::ensureCode($authorId) : '';
            Article::setAccessPassword($id, $code !== '' ? password_hash($code, PASSWORD_DEFAULT) : null);
        } elseif ($pwdMode === 'custom') {
            $newPwd = trim((string) ($_POST['access_password'] ?? ''));
            if ($newPwd !== '') {
                Article::setAccessPassword($id, password_hash($newPwd, PASSWORD_DEFAULT));
            }
            // vide en mode « personnalisé » : on conserve le mot de passe existant.
        }

        // Range chaque survivant : la principale = couverture, les autres = galerie.
        foreach ($survivors as $token => $s) {
            if ($token === $ptoken) {
                // Principale : si elle venait de la galerie, on retire sa ligne
                // (le fichier reste, désormais référencé comme couverture).
                if ($s['origin'] === 'gallery') {
                    ArticleImage::delete((int) $s['id']);
                }
                continue;
            }
            // Non principale → doit figurer en galerie.
            if ($s['origin'] !== 'gallery') {
                // ancienne couverture rétrogradée, ou nouvelle photo → nouvelle ligne
                ArticleImage::add($id, $s['filename']);
            }
            // (origin === 'gallery' non principale : sa ligne existe déjà → rien à faire)
        }

        // --- Pièces jointes (documents : PDF, etc.) ---------------------------
        // Suppression des documents retirés (édition).
        foreach (array_map('intval', (array) ($_POST['delete_files'] ?? [])) as $fid) {
            $doc = ArticleFile::find($fid);
            if ($doc && (int) $doc['article_id'] === $id) {
                Upload::delete($doc['filename'], self::IMG_DIR . '/files');
                ArticleFile::delete($fid);
            }
        }
        // Ajout des nouveaux documents.
        foreach (Upload::documents('docs', self::IMG_DIR . '/files') as $meta) {
            ArticleFile::add($id, $meta);
        }

        // Publication : si l'article devient public (création publiée, ou
        // brouillon → public), on prévient les membres + message de confirmation.
        $wasActive = $existing ? ((int) ($existing['active'] ?? 0) === 1) : false;
        if ($active == 1 && !$wasActive) {
            $this->notifyArticlePublished($id, $title);
        } else {
            Session::set('article_notice', $active == 1 ? '✅ Article mis à jour.' : '📝 Brouillon enregistré.');
        }

        redirect('article?id=' . $id);
    }

    /**
     * Prévient TOUS les membres (sauf l'auteur) qu'un nouvel article est publié,
     * et prépare un message de confirmation pour l'auteur. Les membres reçoivent
     * la notification in-app (cloche) et l'alerte temps réel.
     */
    private function notifyArticlePublished(int $id, string $title): void
    {
        $me         = Session::user();
        $authorId   = (int) ($me['id'] ?? 0);
        $authorName = ($me['name'] ?? '') ?: ($me['email'] ?? 'Un membre');
        $n = 0;
        foreach (User::all() as $member) {
            $mid = (int) $member['id'];
            if ($mid <= 0 || $mid === $authorId) {
                continue; // pas l'auteur, pas les comptes techniques
            }
            Notification::add(
                $mid,
                $authorName . ' a publié un nouvel article : « ' . $title . ' ».',
                '📰', 'article?id=' . $id
            );
            $n++;
        }
        Session::set('article_notice', '✅ Article publié.'
            . ($n > 0 ? ' ' . $n . ' membre' . ($n > 1 ? 's' : '') . ' prévenu' . ($n > 1 ? 's' : '') . '.' : ''));
    }

    /**
     * Bascule rapide publié ⇄ brouillon (auteur ou admin), sans passer par le
     * formulaire. Renvoie sur la page de l'article.
     */
    public function toggle(): void
    {
        $this->guard();
        $id      = (int) ($_POST['id'] ?? 0);
        $article = $id ? Article::find($id) : null;
        if ($article && $this->canManage($article)) {
            $wasActive = (int) $article['active'] === 1;
            Article::setActive($id, $wasActive ? 0 : 1);
            if (!$wasActive) {
                // On vient de PUBLIER → prévient les membres.
                $this->notifyArticlePublished($id, $article['title']);
            } else {
                Session::set('article_notice', '📝 Article repassé en brouillon.');
            }
        }
        redirect('article?id=' . $id);
    }

    /**
     * Avis d'un membre sur un article : note (1-5) + commentaire.
     * Réservé aux membres connectés ; un avis par membre (modifiable).
     */
    public function review(): void
    {
        $this->guard(); // membres connectés uniquement
        $id      = (int) ($_POST['id'] ?? 0);
        $article = $id ? Article::find($id) : null;
        if (!$article) {
            redirect('articles');
        }

        $me      = Session::user();
        $uid     = (int) ($me['id'] ?? 0);
        $stars   = (int) ($_POST['stars'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));
        if (mb_strlen($comment) > 2000) {
            $comment = mb_substr($comment, 0, 2000);
        }

        // L'auteur ne note pas son propre article (commentaire non plus, pour rester simple).
        if ((int) ($article['author_id'] ?? 0) === $uid) {
            Session::set('article_error', 'Tu ne peux pas noter ton propre article.');
            redirect('article?id=' . $id);
        }
        if ($stars < 1 || $stars > 5) {
            Session::set('article_error', 'Choisis une note de 1 à 5 étoiles.');
            redirect('article?id=' . $id);
        }

        ArticleReview::set($id, $uid, $me['name'] ?: ($me['email'] ?? ''), $stars, $comment);
        redirect('article?id=' . $id . '#avis');
    }

    /** Ajoute un message au fil de discussion d'un article (membres connectés). */
    public function comment(): void
    {
        $this->guard();
        $id      = (int) ($_POST['id'] ?? 0);
        $article = $id ? Article::find($id) : null;
        if (!$article) {
            redirect('articles');
        }
        $me   = Session::user();
        $body = trim((string) ($_POST['body'] ?? ''));
        if (mb_strlen($body) > 2000) {
            $body = mb_substr($body, 0, 2000);
        }
        if ($body === '') {
            Session::set('article_error', 'Ton message est vide.');
            redirect('article?id=' . $id . '#discussion');
        }
        ArticleComment::add($id, (int) ($me['id'] ?? 0), $me['name'] ?: ($me['email'] ?? ''), $body);
        redirect('article?id=' . $id . '#discussion');
    }

    /** Supprime un message (son auteur, l'auteur de l'article, ou un admin). */
    public function commentDelete(): void
    {
        $this->guard();
        $cid     = (int) ($_POST['comment_id'] ?? 0);
        $comment = $cid ? ArticleComment::find($cid) : null;
        if (!$comment) {
            redirect('articles');
        }
        $article = Article::find((int) $comment['article_id']);
        $uid     = (int) (Session::user()['id'] ?? 0);
        $canMod  = Session::isAdmin()
            || (int) $comment['user_id'] === $uid
            || ($article && (int) ($article['author_id'] ?? 0) === $uid);
        if ($canMod) {
            ArticleComment::delete($cid);
        }
        redirect('article?id=' . (int) $comment['article_id'] . '#discussion');
    }

    /** Signale un message (membre connecté, hors ses propres messages). */
    public function commentReport(): void
    {
        $this->guard();
        $cid     = (int) ($_POST['comment_id'] ?? 0);
        $comment = $cid ? ArticleComment::find($cid) : null;
        if (!$comment) {
            redirect('articles');
        }
        $uid = (int) (Session::user()['id'] ?? 0);
        if ((int) $comment['user_id'] !== $uid) { // on ne signale pas son propre message
            ArticleComment::flag($cid, $uid);
        }
        redirect('article?id=' . (int) $comment['article_id'] . '#discussion');
    }

    /** Supprime un article (auteur ou admin) + son image. */
    public function delete(): void
    {
        $this->guard();
        $id      = (int) ($_POST['id'] ?? 0);
        $article = $id ? Article::find($id) : null;
        if ($article && $this->canManage($article)) {
            // Les sous-articles ne sont pas supprimés : ils redeviennent de premier niveau.
            Article::orphanChildren($id);
            // Supprime aussi les photos de la galerie (fichiers + lignes).
            foreach (ArticleImage::forArticle($id) as $img) {
                Upload::delete($img['filename'], self::IMG_DIR);
                ArticleImage::delete((int) $img['id']);
            }
            // Supprime les pièces jointes (fichiers + lignes).
            foreach (ArticleFile::forArticle($id) as $doc) {
                Upload::delete($doc['filename'], self::IMG_DIR . '/files');
                ArticleFile::delete((int) $doc['id']);
            }
            // Supprime les avis (notes), la discussion et les vues.
            ArticleReview::deleteForArticle($id);
            ArticleComment::deleteForArticle($id);
            ArticleView::deleteForArticle($id);
            Upload::delete($article['image'], self::IMG_DIR);
            Article::delete($id);
        }
        redirect('articles');
    }
}

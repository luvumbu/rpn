<?php
/**
 * CONTRÔLEUR AdminArticle (espace administrateur)
 * Vue d'ensemble de TOUS les articles pour la modération.
 * La création / modification / suppression elle-même est gérée par
 * ArticleController (routes /articles/...), accessible aux membres,
 * avec contrôle « auteur ou admin ».
 */
class AdminArticleController
{
    /** Bloque l'accès si l'utilisateur n'est pas admin. */
    private function guard(): void
    {
        if (!Session::isAdmin()) {
            redirect('admin/login');
        }
    }

    /** Liste de tous les articles (modération). */
    public function index(): void
    {
        $this->guard();
        $articles = Article::all();
        view('admin/articles/index', [
            'user'     => Session::user(),
            'articles' => $articles,
            'members'  => User::all(),
            'flags'    => Article::flagCountsFor(array_map(fn ($a) => (int) $a['id'], $articles)),
            'flagLimit'=> Article::FLAG_LIMIT,
            'notice'   => Session::get('art_notice'),
            'error'    => Session::get('art_error'),
        ]);
        Session::remove('art_notice');
        Session::remove('art_error');
    }

    /** Réinitialise les signalements d'un article (le rétablit) — reste dans la modération. */
    public function clearFlags(): void
    {
        $this->guard();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id && Article::find($id)) {
            Article::clearFlags($id);
            Session::set('art_notice', '✅ Signalements réinitialisés : l\'article est rétabli.');
        }
        redirect('admin/articles');
    }

    /** Bascule la PROTECTION d'un article (protégé = épargné par « tout effacer »). */
    public function protect(): void
    {
        $this->guard();
        $id = (int) ($_POST['id'] ?? 0);
        $a  = $id ? Article::find($id) : null;
        if ($a) {
            $on = (int) ($a['protected'] ?? 0) === 0; // bascule
            Article::setProtected($id, $on);
            Session::set('art_notice', $on ? '🔒 Article protégé : il sera conservé lors d\'un effacement global.' : '🔓 Protection retirée.');
        }
        redirect('admin/articles');
    }

    /** Bascule le statut « ANNONCE » (article mis en avant sur la page d'accueil de tous). */
    public function announce(): void
    {
        $this->guard();
        $id = (int) ($_POST['id'] ?? 0);
        $a  = $id ? Article::find($id) : null;
        if ($a) {
            $on = (int) ($a['announcement'] ?? 0) === 0; // bascule
            Article::setAnnouncement($id, $on);
            Session::set('art_notice', $on ? '📣 Article passé en ANNONCE : il apparaît sur l\'accueil de tout le monde.' : 'Annonce retirée (redevient un article normal).');
        }
        redirect('admin/articles');
    }

    /**
     * EXPORT : produit un fichier .zip contenant tous les articles (articles.json)
     * et leurs médias (image de couverture, galerie, pièces jointes), prêt à être
     * réimporté ici ou sur un autre site.
     */
    public function export(): void
    {
        $this->guard();

        if (!class_exists('ZipArchive')) {
            Session::set('art_error', "L'extension ZIP de PHP est indisponible : export impossible.");
            redirect('admin/articles');
        }

        $articles = Article::all();

        // Fichier temporaire pour assembler l'archive.
        $tmp = tempnam(sys_get_temp_dir(), 'rpm_export_');
        $zip = new ZipArchive();
        if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            Session::set('art_error', "Impossible de créer l'archive d'export.");
            redirect('admin/articles');
        }

        $imgDir  = APP_ROOT . '/uploads/articles/';
        $fileDir = APP_ROOT . '/uploads/articles/files/';
        $payload = [];
        $added   = [];   // entrées déjà ajoutées au zip (évite les doublons)

        foreach ($articles as $a) {
            $aid     = (int) $a['id'];
            $gallery = ArticleImage::forArticle($aid);
            $files   = ArticleFile::forArticle($aid);

            // Image de couverture.
            if (!empty($a['image'])) {
                $fn   = basename($a['image']);
                $name = 'media/' . $fn;
                if (!isset($added[$name]) && is_file($imgDir . $fn)) {
                    $zip->addFile($imgDir . $fn, $name);
                    $added[$name] = true;
                }
            }
            // Galerie.
            $galleryNames = [];
            foreach ($gallery as $g) {
                $fn = basename($g['filename']);
                $galleryNames[] = $fn;
                $name = 'media/' . $fn;
                if (!isset($added[$name]) && is_file($imgDir . $fn)) {
                    $zip->addFile($imgDir . $fn, $name);
                    $added[$name] = true;
                }
            }
            // Pièces jointes.
            $fileMeta = [];
            foreach ($files as $f) {
                $fn = basename($f['filename']);
                $fileMeta[] = [
                    'filename' => $fn,
                    'original' => $f['original'] ?? '',
                    'mime'     => $f['mime'] ?? '',
                    'size'     => (int) ($f['size'] ?? 0),
                ];
                $name = 'media/files/' . $fn;
                if (!isset($added[$name]) && is_file($fileDir . $fn)) {
                    $zip->addFile($fileDir . $fn, $name);
                    $added[$name] = true;
                }
            }

            $payload[] = [
                'ref'         => $aid,
                'title'       => $a['title'],
                'content'     => $a['content'],
                'image'       => !empty($a['image']) ? basename($a['image']) : null,
                'template'    => $a['template'] ?? 'standard',
                'active'      => (int) $a['active'],
                'parent_ref'  => !empty($a['parent_id']) ? (int) $a['parent_id'] : null,
                'author_name' => $a['author_name'] ?? '',
                'created_at'  => $a['created_at'] ?? null,
                'gallery'     => $galleryNames,
                'files'       => $fileMeta,
            ];
        }

        // --- Questionnaires (+ questions/options) et leurs images -------------
        $quizDir     = APP_ROOT . '/uploads/quizzes/';
        $quizPayload = [];
        foreach (Quiz::all() as $z) {
            $zid = (int) $z['id'];
            if (!empty($z['image'])) {
                $fn = basename($z['image']);
                $name = 'media/quizzes/' . $fn;
                if (!isset($added[$name]) && is_file($quizDir . $fn)) {
                    $zip->addFile($quizDir . $fn, $name);
                    $added[$name] = true;
                }
            }
            $qs = [];
            foreach (Quiz::questions($zid) as $q) {
                $opts = [];
                foreach ($q['options'] as $o) {
                    $opts[] = ['label' => $o['label'], 'is_correct' => (int) $o['is_correct'], 'position' => (int) $o['position']];
                }
                $qs[] = ['body' => $q['body'], 'type' => $q['type'], 'position' => (int) $q['position'], 'options' => $opts];
            }
            $quizPayload[] = [
                'ref'          => $zid,
                'title'        => $z['title'],
                'description'  => $z['description'] ?? '',
                'image'        => !empty($z['image']) ? basename($z['image']) : null,
                'active'       => (int) $z['active'],
                'max_attempts' => (int) ($z['max_attempts'] ?? 0),
                'author_name'  => $z['author_name'] ?? '',
                'created_at'   => $z['created_at'] ?? null,
                'questions'    => $qs,
            ];
        }

        // --- Associations article ↔ questionnaire (par référence d'export) ----
        $linkPayload = [];
        foreach ($articles as $a) {
            foreach (Article::quizIds((int) $a['id']) as $qref) {
                $linkPayload[] = ['article_ref' => (int) $a['id'], 'quiz_ref' => (int) $qref];
            }
        }

        $manifest = [
            'format'         => 'rpm-articles',
            'version'        => 2,
            'exported_at'    => date('Y-m-d H:i:s'),
            'count'          => count($payload),
            'articles'       => $payload,
            'quizzes'        => $quizPayload,
            'article_quizzes' => $linkPayload,
        ];
        $zip->addFromString('articles.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->close();

        $filename = 'articles-export-' . date('Y-m-d-His') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: no-store');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    /**
     * IMPORT : recrée les articles d'un fichier .zip d'export. Tous les articles
     * importés sont attribués à l'ADMIN connecté et passés en BROUILLON, à charge
     * pour l'admin de les réattribuer ensuite à l'utilisateur de son choix.
     */
    public function import(): void
    {
        $this->guard();

        if (!class_exists('ZipArchive')) {
            Session::set('art_error', "L'extension ZIP de PHP est indisponible : import impossible.");
            redirect('admin/articles');
        }

        $f = $_FILES['archive'] ?? null;
        if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            Session::set('art_error', 'Choisis un fichier .zip d\'export valide.');
            redirect('admin/articles');
        }

        $zip = new ZipArchive();
        if ($zip->open($f['tmp_name']) !== true) {
            Session::set('art_error', 'Archive illisible (fichier .zip attendu).');
            redirect('admin/articles');
        }

        $json = $zip->getFromName('articles.json');
        $data = $json !== false ? json_decode($json, true) : null;
        if (!is_array($data) || ($data['format'] ?? '') !== 'rpm-articles' || !is_array($data['articles'] ?? null)) {
            $zip->close();
            Session::set('art_error', 'Archive non reconnue (manifeste articles.json manquant ou invalide).');
            redirect('admin/articles');
        }

        $admin     = Session::user();
        $adminId   = (int) ($admin['id'] ?? 0);
        $adminName = $admin['name'] ?: ($admin['email'] ?? 'Administrateur');

        $imgDir  = APP_ROOT . '/uploads/articles/';
        $fileDir = APP_ROOT . '/uploads/articles/files/';
        $quizDir = APP_ROOT . '/uploads/quizzes/';
        @mkdir($imgDir, 0775, true);
        @mkdir($fileDir, 0775, true);
        @mkdir($quizDir, 0775, true);

        // Extensions autorisées à la copie (garde-fou anti-fichier exécutable).
        $imgExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'odt', 'ods', 'odp'];

        $refToId  = [];   // ref d'export → nouvel id
        $parentOf = [];   // nouvel id → parent_ref
        $imported = 0;

        foreach ($data['articles'] as $entry) {
            if (!is_array($entry) || empty($entry['title'])) {
                continue;
            }

            // Image de couverture.
            $newImage = $this->copyFromZip($zip, 'media/' . basename((string) ($entry['image'] ?? '')), $imgDir, $imgExt, !empty($entry['image']));

            $newId = Article::create([
                'title'       => (string) $entry['title'],
                'content'     => (string) ($entry['content'] ?? ''),
                'image'       => $newImage,
                'template'    => (string) ($entry['template'] ?? 'standard'),
                'active'      => 0,             // import → brouillon, par sécurité
                'parent_id'   => null,          // rattaché au 2e passage
                'author_id'   => $adminId,      // attribué à l'admin
                'author_name' => $adminName,
            ]);

            if (isset($entry['ref'])) {
                $refToId[(int) $entry['ref']] = $newId;
            }
            if (!empty($entry['parent_ref'])) {
                $parentOf[$newId] = (int) $entry['parent_ref'];
            }

            // Galerie.
            foreach ((array) ($entry['gallery'] ?? []) as $g) {
                $copied = $this->copyFromZip($zip, 'media/' . basename((string) $g), $imgDir, $imgExt, true);
                if ($copied !== null) {
                    ArticleImage::add($newId, $copied);
                }
            }
            // Pièces jointes.
            foreach ((array) ($entry['files'] ?? []) as $file) {
                if (!is_array($file) || empty($file['filename'])) {
                    continue;
                }
                $copied = $this->copyFromZip($zip, 'media/files/' . basename((string) $file['filename']), $fileDir, $fileExt, true);
                if ($copied !== null) {
                    ArticleFile::add($newId, [
                        'filename' => $copied,
                        'original' => (string) ($file['original'] ?? $file['filename']),
                        'mime'     => (string) ($file['mime'] ?? ''),
                        'size'     => (int) ($file['size'] ?? 0),
                    ]);
                }
            }

            $imported++;
        }

        // --- Questionnaires (recréés en BROUILLON, attribués à l'admin) -------
        $quizRefToId   = [];
        $importedQuiz  = 0;
        foreach ((array) ($data['quizzes'] ?? []) as $z) {
            if (!is_array($z) || empty($z['title'])) {
                continue;
            }
            $newImg = $this->copyFromZip($zip, 'media/quizzes/' . basename((string) ($z['image'] ?? '')), $quizDir, $imgExt, !empty($z['image']));
            $nzid = Quiz::create([
                'title'        => (string) $z['title'],
                'description'  => (string) ($z['description'] ?? ''),
                'image'        => $newImg,
                'active'       => 0,                 // import → brouillon, par sécurité
                'max_attempts' => (int) ($z['max_attempts'] ?? 0),
                'author_id'    => $adminId,
                'author_name'  => $adminName,
            ]);
            if (isset($z['ref'])) {
                $quizRefToId[(int) $z['ref']] = $nzid;
            }
            $pos = 0;
            foreach ((array) ($z['questions'] ?? []) as $q) {
                if (!is_array($q) || trim((string) ($q['body'] ?? '')) === '') {
                    continue;
                }
                $qqid = Quiz::addQuestion($nzid, (string) $q['body'], (string) ($q['type'] ?? 'single'), $pos++);
                $opos = 0;
                foreach ((array) ($q['options'] ?? []) as $o) {
                    if (!is_array($o) || trim((string) ($o['label'] ?? '')) === '') {
                        continue;
                    }
                    Quiz::addOption($qqid, (string) $o['label'], (int) ($o['is_correct'] ?? 0) === 1, $opos++);
                }
            }
            $importedQuiz++;
        }

        $zip->close();

        // 2e passage : rétablit la hiérarchie parent → enfant via la table de correspondance.
        foreach ($parentOf as $childId => $parentRef) {
            if (isset($refToId[$parentRef])) {
                Article::setParent($childId, $refToId[$parentRef]);
            }
        }

        // Rétablit les associations article ↔ questionnaire (regroupées par article).
        $links = [];
        foreach ((array) ($data['article_quizzes'] ?? []) as $lk) {
            if (!is_array($lk)) {
                continue;
            }
            $aId = $refToId[(int) ($lk['article_ref'] ?? 0)] ?? null;
            $qId = $quizRefToId[(int) ($lk['quiz_ref'] ?? 0)] ?? null;
            if ($aId && $qId) {
                $links[$aId][] = $qId;
            }
        }
        foreach ($links as $aId => $qIds) {
            Article::setQuizzes($aId, array_values(array_unique($qIds)));
        }

        if ($imported === 0 && $importedQuiz === 0) {
            Session::set('art_error', "Archive vide : aucun article ni questionnaire trouvé. (As-tu exporté un projet qui contenait du contenu ?)");
        } else {
            $parts = [];
            if ($imported > 0) {
                $parts[] = "$imported article" . ($imported > 1 ? 's' : '');
            }
            if ($importedQuiz > 0) {
                $parts[] = "$importedQuiz questionnaire" . ($importedQuiz > 1 ? 's' : '');
            }
            $msg = '✅ Importé : ' . implode(' et ', $parts) . ' (en brouillon, attribué' . (($imported + $importedQuiz) > 1 ? 's' : '') . ' à toi).';
            if ($imported > 0) {
                $msg .= ' Réattribue les articles à un utilisateur ci-dessous.';
            }
            Session::set('art_notice', $msg);
        }
        redirect('admin/articles');
    }

    /**
     * Copie un fichier de l'archive vers $destDir sous un nom unique, en
     * conservant l'extension (validée). Retourne le nouveau nom, ou null.
     */
    private function copyFromZip(ZipArchive $zip, string $zipPath, string $destDir, array $allowedExt, bool $expected): ?string
    {
        if (!$expected || $zipPath === '' || basename($zipPath) === '') {
            return null;
        }
        $content = $zip->getFromName($zipPath);
        if ($content === false) {
            return null;
        }
        $ext = strtolower(pathinfo($zipPath, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return null; // refuse tout ce qui n'est pas un média/document attendu
        }
        $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        if (@file_put_contents($destDir . $name, $content) === false) {
            return null;
        }
        return $name;
    }

    /**
     * RÉATTRIBUTION : l'admin attribue un article à l'utilisateur de son choix
     * (le nom d'auteur affiché suit). Réservé à l'admin.
     */
    public function assign(): void
    {
        $this->guard();
        $id      = (int) ($_POST['article_id'] ?? 0);
        $userId  = (int) ($_POST['user_id'] ?? 0);
        $article = $id ? Article::find($id) : null;

        if (!$article) {
            redirect('admin/articles');
        }
        $member = $userId ? User::findById($userId) : null;
        if (!$member) {
            Session::set('art_error', 'Utilisateur introuvable.');
            redirect('admin/articles');
        }

        $name = $member['name'] ?: ($member['email'] ?? ('Membre #' . $member['id']));
        Article::setAuthor($id, (int) $member['id'], $name);

        // Notifie le nouvel auteur qu'un article lui a été attribué.
        Notification::add(
            (int) $member['id'],
            'L\'administrateur vous a attribué l\'article « ' . $article['title'] . ' ».',
            '📰', 'article?id=' . $id
        );

        Session::set('art_notice', 'Article attribué à ' . $name . '.');
        redirect('admin/articles');
    }

    /** Panneau « modules » de style global des articles. */
    /** Style des articles : désormais regroupé dans Paramètres → Articles. */
    public function style(): void
    {
        $this->guard();
        redirect('admin/settings#p-articles');
    }

    /** Enregistre les réglages de style global. */
    public function saveStyle(): void
    {
        $this->guard();
        $font  = $_POST['art_font'] ?? 'moderne';
        $width = $_POST['art_width'] ?? 'default';
        Settings::save([
            'art_text_scale'   => max(70, min(200, (int) ($_POST['art_text_scale'] ?? 100))),
            'art_font_enabled' => isset($_POST['art_font_enabled']) ? 1 : 0,
            'art_font'         => array_key_exists($font, ArticleStyle::fonts()) ? $font : 'moderne',
            'art_width'        => array_key_exists($width, ArticleStyle::widths()) ? $width : 'default',
        ]);
        Session::set('style_saved', true);
        redirect('admin/settings#p-articles');
    }

    /**
     * « Style général » (mise en page). Enregistre toujours le modèle choisi comme
     * mise en page par défaut (nouveaux articles) ; si action = « all », l'applique
     * en plus à TOUS les articles existants (écrase leur modèle individuel).
     */
    public function applyStyle(): void
    {
        $this->guard();
        $tpl = ArticleTemplate::key($_POST['general_template'] ?? 'standard');
        Settings::save(['default_template' => $tpl]);

        $label = ArticleTemplate::all()[$tpl] ?? $tpl;
        if (($_POST['action'] ?? 'default') === 'all') {
            $n = Article::setTemplateForAll($tpl);
            Session::set('gentpl_msg',
                '✅ Mise en page « ' . $label . ' » appliquée à ' . $n . ' article' . ($n > 1 ? 's' : '') . '.');
        } else {
            Session::set('gentpl_msg',
                '✅ « ' . $label . ' » enregistré comme mise en page par défaut (nouveaux articles).');
        }
        redirect('admin/settings#p-articles');
    }
}

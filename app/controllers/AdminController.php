<?php
/**
 * CONTRÔLEUR Admin
 * Gère l'espace administrateur :
 *  - connexion par email + mot de passe (en plus de la connexion Google)
 *  - tableau de bord (statistiques)
 *  - liste des membres + bloquer / débloquer / supprimer
 */
class AdminController
{
    /** Bloque l'accès aux pages admin si l'utilisateur n'est pas admin. */
    private function guard(): void
    {
        if (!Session::isAdmin()) {
            redirect('admin/login');
        }
    }

    /** Affiche le formulaire de connexion admin. */
    public function showLogin(): void
    {
        if (Session::isAdmin()) {
            redirect('admin/dashboard');
        }
        $error = Session::get('admin_error');
        Session::remove('admin_error');

        $ip      = LoginGuard::ip();
        $blocked = LoginGuard::isBlocked($ip);
        if ($blocked) {
            $error = 'Trop de tentatives échouées. Réessaie dans '
                   . LoginGuard::humanRemaining(LoginGuard::blockedFor($ip)) . '.';
        }

        view('admin/login', ['error' => $error, 'blocked' => $blocked]);
    }

    /**
     * Authentifie l'admin avec le NOM DE LA BASE + le MOT DE PASSE MySQL.
     * Sur Hostinger, l'utilisateur = le nom de la base. Si la connexion réelle
     * réussit → accès admin, et les identifiants sont gardés en SESSION
     * (jamais écrits dans un fichier) pour toute la durée de la connexion.
     */
    public function login(): void
    {
        $ip = LoginGuard::ip();

        // Anti-bruteforce : IP bloquée ?
        if (LoginGuard::isBlocked($ip)) {
            Session::set('admin_error', 'Trop de tentatives échouées. Réessaie dans '
                . LoginGuard::humanRemaining(LoginGuard::blockedFor($ip)) . '.');
            redirect('admin/login');
        }

        $login = trim($_POST['login'] ?? '');   // nom de base (Hostinger) OU utilisateur (root en local)
        $pass  = $_POST['password'] ?? '';
        $host  = DB_HOST;
        $db    = null;

        if ($login !== '') {
            if (IS_LOCAL) {
                // LOCAL : la base est connue (config), le saisi est l'utilisateur (ex: root)
                if (Database::tryConnect($host, DB_NAME, $login, $pass)) {
                    $db = ['host' => $host, 'name' => DB_NAME, 'user' => $login, 'pass' => $pass];
                }
            } else {
                // EN LIGNE (Hostinger) : le saisi est à la fois la base ET l'utilisateur
                if (Database::tryConnect($host, $login, $login, $pass)) {
                    $db = ['host' => $host, 'name' => $login, 'user' => $login, 'pass' => $pass];
                }
            }
        }

        if ($db === null) {
            // Échec → on enregistre la tentative
            LoginGuard::recordFailure($ip);

            if (LoginGuard::isBlocked($ip)) {
                $msg = 'Trop de tentatives. IP bloquée pendant 24h.';
            } else {
                $left = LoginGuard::attemptsLeft($ip);
                $msg  = 'Nom de base ou mot de passe incorrect. '
                      . $left . ' essai' . ($left > 1 ? 's' : '') . ' restant' . ($left > 1 ? 's' : '') . '.';
            }
            Session::set('admin_error', $msg);
            redirect('admin/login');
        }

        // Succès → on efface les tentatives échouées de cette IP
        LoginGuard::reset($ip);

        // Identifiants saisis → gardés en session pour la durée de la connexion
        $_SESSION['db'] = $db;

        // …et enregistrés (en ligne) pour que les MEMBRES aussi accèdent à la base
        Database::persist($db);

        Session::set('user', [
            'id'      => 0,
            'name'    => (string) Settings::get('admin_name', '') ?: $db['user'], // nom choisi, sinon défaut
            'email'   => '',
            'picture' => (string) Settings::get('admin_picture', ''),             // photo perso du super-admin
            'role'    => 'admin',
        ]);
        redirect('admin/dashboard');
    }

    /** Tableau de bord admin (statistiques). */
    public function dashboard(): void
    {
        $this->guard();
        view('admin/dashboard', [
            'user'      => Session::user(),
            'total'     => User::count(),
            'admins'    => User::countByRole('admin'),
            'membres'   => User::countByRole('membre'),
            'isSuper'   => $this->currentIsSuper(),
            'notice'    => Session::get('admin_notice'),
        ]);
        Session::remove('admin_notice');
    }

    /** Tableau de bord analytique (statistiques d'usage) — admin. */
    public function analytics(): void
    {
        $this->guard();
        $pdo = Database::pdo();
        $scalar = static function (string $sql) use ($pdo) {
            try { return $pdo->query($sql)->fetchColumn(); } catch (\Throwable $e) { return 0; }
        };
        $rows = static function (string $sql) use ($pdo): array {
            try { return $pdo->query($sql)->fetchAll(); } catch (\Throwable $e) { return []; }
        };

        view('admin/analytics', [
            'user'         => Session::user(),
            'members'      => (int) User::count(),
            'activeMembers'=> (int) $scalar("SELECT COUNT(*) FROM users WHERE last_login >= (NOW() - INTERVAL 30 DAY)"),
            'newMembers'   => (int) $scalar("SELECT COUNT(*) FROM users WHERE created_at >= (NOW() - INTERVAL 30 DAY)"),
            'articles'     => (int) Article::count(),
            'articlesActive'=> (int) Article::countActive(),
            'totalViews'   => (int) $scalar("SELECT COUNT(*) FROM article_views"),
            'topArticles'  => $rows("SELECT a.id, a.title, COUNT(v.id) AS views
                                      FROM articles a LEFT JOIN article_views v ON v.article_id = a.id
                                      GROUP BY a.id ORDER BY views DESC, a.created_at DESC LIMIT 6"),
            'quizzes'      => (int) $scalar("SELECT COUNT(*) FROM quizzes"),
            'quizActive'   => (int) Quiz::countActive(),
            'quizResponses'=> (int) $scalar("SELECT COUNT(*) FROM quiz_responses"),
            'quizAvg'      => (int) round((float) $scalar("SELECT AVG(score/total*100) FROM quiz_responses WHERE total > 0")),
            'topQuizzes'   => $rows("SELECT q.id, q.title, COUNT(r.id) AS participants,
                                      ROUND(AVG(CASE WHEN r.total>0 THEN r.score/r.total*100 END)) AS avgpct
                                      FROM quizzes q LEFT JOIN quiz_responses r ON r.quiz_id = q.id
                                      GROUP BY q.id ORDER BY participants DESC, q.created_at DESC LIMIT 6"),
            'appointments' => (int) $scalar("SELECT COUNT(*) FROM appointments"),
            'bookings'     => (int) $scalar("SELECT COUNT(*) FROM appointment_bookings"),

            // Paiements (Stripe)
            'payTotal'     => (int) $scalar("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'"),
            'payCount'     => (int) $scalar("SELECT COUNT(*) FROM payments WHERE status='paid'"),
            'paySubs'      => (int) $scalar("SELECT COUNT(*) FROM payments WHERE status='paid' AND type='subscription'"),
            'payCurrency'  => strtoupper(StripeClient::currency()),
            'recentPays'   => $rows("SELECT p.created_at, p.type, p.amount, p.currency, p.user_id,
                                      (SELECT u.name FROM users u WHERE u.id = p.user_id) AS uname
                                      FROM payments p WHERE p.status='paid' ORDER BY p.created_at DESC LIMIT 12"),
        ]);
    }

    /** Générateur de favicon : désormais regroupé dans Paramètres → Favicon. */
    public function favicon(): void
    {
        $this->guard();
        redirect('admin/settings#p-favicon');
    }

    /** Génère et applique le nouveau favicon (depuis un texte ou une image). */
    public function saveFavicon(): void
    {
        $this->guard();
        $mode  = ($_POST['mode'] ?? 'text') === 'image' ? 'image' : 'text';
        $shape = in_array($_POST['shape'] ?? 'round', ['square', 'round', 'circle'], true) ? $_POST['shape'] : 'round';
        $font  = in_array($_POST['font'] ?? 'bold', ['bold', 'regular', 'serif', 'mono'], true) ? $_POST['font'] : 'bold';
        try {
            if ($mode === 'image' && !empty($_FILES['favicon_img']['name'])) {
                Favicon::fromUpload('favicon_img', $shape);
            } else {
                Favicon::fromText(
                    (string) ($_POST['text'] ?? 'R'),
                    (string) ($_POST['bg'] ?? '#14110f'),
                    (string) ($_POST['fg'] ?? '#f4c14b'),
                    $shape,
                    !empty($_POST['transparent']),
                    $font
                );
            }
            Session::set('favicon_saved', true);
        } catch (\Throwable $e) {
            Session::set('favicon_error', $e->getMessage());
        }
        redirect('admin/settings#p-favicon');
    }

    /** Supprime les fichiers d'un dossier d'uploads (récursif), en gardant .htaccess. */
    private function purgeUploads(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach ((array) glob($dir . '/*') as $path) {
            if (is_dir($path)) {
                $this->purgeUploads($path);
            } elseif (is_file($path) && basename($path) !== '.htaccess') {
                @unlink($path);
            }
        }
    }

    /** Vide une liste de tables (sans casser si l'une manque). */
    private function wipeTables(array $tables): void
    {
        $pdo = Database::pdo();
        foreach ($tables as $t) {
            try { $pdo->exec('DELETE FROM ' . $t); } catch (\Throwable $e) { /* table absente : on ignore */ }
        }
    }

    /** EFFACE TOUT L'AGENDA (créneaux, réservations, historique, notes). Super-admin only. */
    public function wipeAgenda(): void
    {
        $this->guard();
        if (!$this->currentIsSuper()) {
            Session::set('admin_notice', '⛔ Action réservée au super-administrateur.');
            redirect('admin/dashboard');
        }
        if (!Database::verifyPassword((string) ($_POST['db_password'] ?? ''))) {
            Session::set('admin_notice', '⛔ Mot de passe de la base incorrect — effacement de l\'agenda annulé.');
            redirect('admin/dashboard');
        }

        $ids = Appointment::unprotectedIds(); // on saute les événements protégés
        if (!$ids) {
            Session::set('admin_notice', 'ℹ️ Aucun événement effacé : tous sont protégés 🔒.');
            redirect('admin/dashboard');
        }
        foreach ($ids as $id) {
            AppointmentBooking::deleteForAppointment($id);
            AppointmentChange::deleteForAppointment($id);
            foreach (AppointmentImage::deleteForAppointment($id) as $fn) {
                Upload::delete($fn, 'agenda');
            }
            AppointmentRating::deleteForAppointment($id);
            Appointment::delete($id);
        }
        $kept = Appointment::protectedCount();
        Session::set('admin_notice',
            '🗑️ ' . count($ids) . ' événement(s) effacé(s).' . ($kept ? ' ' . $kept . ' événement(s) protégé(s) 🔒 conservé(s).' : ''));
        redirect('admin/dashboard');
    }

    /**
     * EFFACE LES ARTICLES (+ images, fichiers, avis, discussions), SAUF ceux
     * marqués « protégés ». Super-admin only, confirmé par mot de passe BDD.
     */
    public function wipeArticles(): void
    {
        $this->guard();
        if (!$this->currentIsSuper()) {
            Session::set('admin_notice', '⛔ Action réservée au super-administrateur.');
            redirect('admin/dashboard');
        }
        if (!Database::verifyPassword((string) ($_POST['db_password'] ?? ''))) {
            Session::set('admin_notice', '⛔ Mot de passe de la base incorrect — effacement des articles annulé.');
            redirect('admin/dashboard');
        }

        $ids = Article::unprotectedIds(); // on saute les articles protégés
        if (!$ids) {
            Session::set('admin_notice', 'ℹ️ Aucun article effacé : tous les articles sont protégés 🔒.');
            redirect('admin/dashboard');
        }
        // Suppression complète article par article (galerie, pièces jointes, avis,
        // discussion, vues, couverture) — comme une suppression individuelle.
        foreach ($ids as $id) {
            Article::orphanChildren($id);
            foreach (ArticleImage::forArticle($id) as $img) {
                Upload::delete($img['filename'], 'articles');
                ArticleImage::delete((int) $img['id']);
            }
            foreach (ArticleFile::forArticle($id) as $doc) {
                Upload::delete($doc['filename'], 'articles/files');
                ArticleFile::delete((int) $doc['id']);
            }
            ArticleReview::deleteForArticle($id);
            ArticleComment::deleteForArticle($id);
            ArticleView::deleteForArticle($id);
            $a = Article::find($id);
            if ($a) { Upload::delete($a['image'], 'articles'); }
            Article::delete($id);
        }
        $kept = Article::protectedCount();
        Session::set('admin_notice',
            '🗑️ ' . count($ids) . ' article(s) effacé(s).' . ($kept ? ' ' . $kept . ' article(s) protégé(s) 🔒 conservé(s).' : ''));
        redirect('admin/dashboard');
    }

    /** Emails super-admin (définis dans config) : intouchables. */
    private function superEmails(): array
    {
        return array_map('strtolower', ADMIN_EMAILS);
    }

    /** Un utilisateur (ligne BDD) est-il super-admin ? */
    private function isSuperAdmin(?array $u): bool
    {
        return $u && in_array(strtolower($u['email'] ?? ''), $this->superEmails(), true);
    }

    /** L'admin ACTUELLEMENT connecté est-il super-admin ? */
    private function currentIsSuper(): bool
    {
        $u = Session::user();
        if (!$u) {
            return false;
        }
        // Connexion par identifiants MySQL (id 0) = propriétaire = super-admin
        if ((int) ($u['id'] ?? -1) === 0) {
            return true;
        }
        // Connexion Google dont l'email est dans ADMIN_EMAILS = super-admin
        return in_array(strtolower($u['email'] ?? ''), $this->superEmails(), true);
    }

    /** Liste des membres. */
    public function members(): void
    {
        $this->guard();
        view('admin/members', [
            'me'          => Session::user(),
            'users'       => User::all(),
            'superEmails' => $this->superEmails(),
            'canManage'   => $this->currentIsSuper(), // l'admin connecté est-il super ?
        ]);
    }

    /** Promeut un membre en administrateur. (super-admin uniquement) */
    public function promote(): void
    {
        $this->guard();
        if (!$this->currentIsSuper()) {
            redirect('admin/members');
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            User::setRole($id, 'admin');
        }
        redirect('admin/members');
    }

    /** Retire les droits admin. (super-admin uniquement, sauf super-admin) */
    public function demote(): void
    {
        $this->guard();
        if (!$this->currentIsSuper()) {
            redirect('admin/members');
        }
        $id = (int) ($_POST['id'] ?? 0);
        $u  = $id ? User::findById($id) : null;
        if ($u && !$this->isSuperAdmin($u)) {
            User::setRole($id, 'membre');
        }
        redirect('admin/members');
    }

    /**
     * Bloque un membre.
     * - super-admin : peut bloquer tout le monde (sauf un autre super-admin)
     * - admin normal : peut bloquer uniquement les simples membres
     */
    public function block(): void
    {
        $this->guard();
        $id = (int) ($_POST['id'] ?? 0);
        $u  = $id ? User::findById($id) : null;
        if ($u && !$this->isSuperAdmin($u) && ($this->currentIsSuper() || $u['role'] === 'membre')) {
            User::setBlocked($id, true);
        }
        redirect('admin/members');
    }

    /** Débloque un membre (mêmes règles que le blocage). */
    public function unblock(): void
    {
        $this->guard();
        $id = (int) ($_POST['id'] ?? 0);
        $u  = $id ? User::findById($id) : null;
        if ($u && !$this->isSuperAdmin($u) && ($this->currentIsSuper() || $u['role'] === 'membre')) {
            User::setBlocked($id, false);
        }
        redirect('admin/members');
    }

    /** Supprime un membre. (super-admin uniquement, sauf super-admin) */
    public function delete(): void
    {
        $this->guard();
        if (!$this->currentIsSuper()) {
            redirect('admin/members');
        }
        $id = (int) ($_POST['id'] ?? 0);
        $u  = $id ? User::findById($id) : null;
        if ($u && !$this->isSuperAdmin($u)) {
            User::delete($id);
        }
        redirect('admin/members');
    }

    /** Page Sécurité : voir et débloquer les IP bloquées. */
    public function security(): void
    {
        $this->guard();
        view('admin/security', [
            'user'    => Session::user(),
            'blocked' => LoginGuard::blockedList(),
        ]);
    }

    /** Débloque une IP précise. */
    public function ipUnblock(): void
    {
        $this->guard();
        $ip = trim($_POST['ip'] ?? '');
        if ($ip !== '') {
            LoginGuard::reset($ip);
        }
        redirect('admin/security');
    }

    /** Débloque toutes les IP. */
    public function ipUnblockAll(): void
    {
        $this->guard();
        LoginGuard::resetAll();
        redirect('admin/security');
    }

    /** Panneau de STYLE GLOBAL du site (police, arrondi, ombres, animations). */
    /** Style global : désormais regroupé dans Paramètres → Style global. */
    public function globalStyle(): void
    {
        $this->guard();
        redirect('admin/settings#p-style-global');
    }

    /** Enregistre les réglages de style global. */
    public function saveGlobalStyle(): void
    {
        $this->guard();
        $font   = $_POST['gs_font'] ?? 'theme';
        $shadow = $_POST['gs_shadow'] ?? 'normal';
        Settings::save([
            'gs_font_enabled' => isset($_POST['gs_font_enabled']) ? 1 : 0,
            'gs_font'         => array_key_exists($font, GlobalStyle::fonts()) ? $font : 'theme',
            'gs_radius'       => max(0, min(28, (int) ($_POST['gs_radius'] ?? 16))),
            'gs_shadow'       => array_key_exists($shadow, GlobalStyle::shadows()) ? $shadow : 'normal',
            'gs_anim'         => isset($_POST['gs_anim']) ? 1 : 0,
        ]);
        Session::set('gstyle_saved', true);
        redirect('admin/settings#p-style-global');
    }

    /** Page des paramètres (clés API Google, durée de blocage...). */
    public function settings(): void
    {
        $this->guard();
        // Confirmations « enregistré » de chaque section (toutes regroupées ici).
        $saved             = Session::get('settings_saved');
        $savedGlobalStyle  = Session::get('gstyle_saved');
        $savedFavicon      = Session::get('favicon_saved');
        $savedArticleStyle = Session::get('style_saved');
        $genTemplateMsg    = Session::get('gentpl_msg');
        $faviconError      = Session::get('favicon_error');
        Session::remove('settings_saved');
        Session::remove('gstyle_saved');
        Session::remove('favicon_saved');
        Session::remove('style_saved');
        Session::remove('gentpl_msg');
        Session::remove('favicon_error');

        view('admin/settings', [
            'user'         => Session::user(),
            'clientId'     => Settings::get('google_client_id', GOOGLE_CLIENT_ID),
            'clientSecret' => Settings::get('google_client_secret', GOOGLE_CLIENT_SECRET),
            'blockHours'   => (int) Settings::get('block_hours', 24),
            'maxAttempts'  => (int) Settings::get('max_attempts', 3),
            'theme'        => Theme::key(),
            'themes'       => Theme::all(),
            'custom'       => [
                'bg'     => Theme::hex(Settings::get('theme_custom_bg', '#14110f'), '#14110f'),
                'text'   => Theme::hex(Settings::get('theme_custom_text', '#ffffff'), '#ffffff'),
                'accent' => Theme::hex(Settings::get('theme_custom_accent', '#f4c14b'), '#f4c14b'),
                'ink'    => Theme::hex(Settings::get('theme_custom_ink', '#14110f'), '#14110f'),
                'rouge'  => Theme::hex(Settings::get('theme_custom_rouge', '#e63946'), '#e63946'),
                'vert'   => Theme::hex(Settings::get('theme_custom_vert', '#2a9d4a'), '#2a9d4a'),
                'or'     => Theme::hex(Settings::get('theme_custom_or', '#f4c14b'), '#f4c14b'),
            ],
            'mainTitle'    => Settings::get('main_title', 'RPN'),
            'mainMessage'  => Settings::get('main_message', 'Bienvenue. Connecte-toi pour rejoindre la communauté.'),
            'mainFooter'   => Settings::get('main_footer', 'Ensemble, plus forts'),
            'articleTemplates' => ArticleTemplate::all(),
            'defaultTemplate'  => ArticleTemplate::key(Settings::get('default_template', 'standard')),
            'apiKey'       => Settings::get('api_key', '') ?: (defined('API_KEY') ? API_KEY : ''),
            'defaultDiscoverable' => (int) Settings::get('default_discoverable', 0),
            'mailFrom'     => (string) Settings::get('mail_from', ''),

            // --- Paiements (Stripe) ---------------------------------------------
            'stripeSecret'      => (string) Settings::get('stripe_secret', ''),
            'stripePublishable' => (string) Settings::get('stripe_publishable', ''),
            'stripeWebhook'     => (string) Settings::get('stripe_webhook_secret', ''),
            'stripeCurrency'    => (string) Settings::get('stripe_currency', 'eur'),
            'stripeDonationLabel'   => (string) Settings::get('stripe_donation_label', 'Soutenir la communauté'),
            'stripeDonationAmounts' => (string) Settings::get('stripe_donation_amounts', '5,10,20,50'),
            'stripePlans'       => (function () {
                $p = json_decode((string) Settings::get('stripe_plans', '[]'), true);
                $p = is_array($p) ? $p : [];
                // Toujours 3 lignes pour le formulaire.
                for ($i = 0; $i < 3; $i++) {
                    $p[$i] = [
                        'name'     => (string) ($p[$i]['name'] ?? ''),
                        'amount'   => (string) ($p[$i]['amount'] ?? ''),
                        'interval' => ($p[$i]['interval'] ?? 'month') === 'year' ? 'year' : 'month',
                    ];
                }
                return array_slice($p, 0, 3);
            })(),

            'saved'        => $saved,

            // --- Style global du site (police, arrondi, ombres, animations) -----
            'gsFonts'       => GlobalStyle::fonts(),
            'gsShadows'     => GlobalStyle::shadows(),
            'gsFontEnabled' => GlobalStyle::fontEnabled(),
            'gsFontKey'     => GlobalStyle::fontKey(),
            'gsRadius'      => GlobalStyle::radius(),
            'gsShadowKey'   => GlobalStyle::shadowKey(),
            'gsAnim'        => GlobalStyle::animationsEnabled(),
            'savedGlobalStyle' => $savedGlobalStyle,

            // --- Favicon --------------------------------------------------------
            'favVersion'   => (string) Settings::get('favicon_version', '1'),
            'favCustom'    => (int) Settings::get('favicon_custom', 0) === 1,
            'savedFavicon' => $savedFavicon,
            'faviconError' => $faviconError,

            // --- Style des articles (taille + police) ---------------------------
            'artScale'       => ArticleStyle::scale(),
            'artFontEnabled' => ArticleStyle::fontEnabled(),
            'artFontKey'     => ArticleStyle::fontKey(),
            'artFonts'       => ArticleStyle::fonts(),
            'artWidthKey'    => ArticleStyle::widthKey(),
            'artWidths'      => ArticleStyle::widths(),
            'savedArticleStyle' => $savedArticleStyle,
            'genTemplateMsg'    => $genTemplateMsg,
        ]);
    }

    /**
     * Enregistre les paramètres. MODULAIRE : chaque section de la page
     * a son propre formulaire et n'envoie que ses champs ; on n'enregistre
     * donc QUE les clés réellement soumises (les autres sont préservées).
     */
    public function saveSettings(): void
    {
        $this->guard();
        $vals = [];

        // Connexion Google
        if (isset($_POST['client_id']))     { $vals['google_client_id']     = trim($_POST['client_id']); }
        if (isset($_POST['client_secret'])) { $vals['google_client_secret'] = trim($_POST['client_secret']); }

        // Sécurité
        if (isset($_POST['block_hours']))  { $vals['block_hours']  = max(1, (int) $_POST['block_hours']); }
        if (isset($_POST['max_attempts'])) { $vals['max_attempts'] = max(1, (int) $_POST['max_attempts']); }

        // Thème + couleurs personnalisées (soumis ensemble)
        if (isset($_POST['theme'])) {
            $vals['theme']              = array_key_exists($_POST['theme'], Theme::all()) ? $_POST['theme'] : 'panafricain';
            $vals['theme_custom_bg']    = Theme::hex($_POST['tc_bg'] ?? '', '#14110f');
            $vals['theme_custom_text']  = Theme::hex($_POST['tc_text'] ?? '', '#ffffff');
            $vals['theme_custom_accent']= Theme::hex($_POST['tc_accent'] ?? '', '#f4c14b');
            $vals['theme_custom_ink']   = Theme::hex($_POST['tc_ink'] ?? '', '#14110f');
            $vals['theme_custom_rouge'] = Theme::hex($_POST['tc_rouge'] ?? '', '#e63946');
            $vals['theme_custom_vert']  = Theme::hex($_POST['tc_vert'] ?? '', '#2a9d4a');
            $vals['theme_custom_or']    = Theme::hex($_POST['tc_or'] ?? '', '#f4c14b');
        }

        // Articles — mise en page par défaut
        if (isset($_POST['default_template'])) { $vals['default_template'] = ArticleTemplate::key($_POST['default_template']); }

        // Membres — visibilité par défaut à l'inscription
        if (isset($_POST['default_discoverable'])) { $vals['default_discoverable'] = ((int) $_POST['default_discoverable'] === 1) ? 1 : 0; }

        // E-mail expéditeur (pour les emails transactionnels : mot de passe oublié…)
        if (array_key_exists('mail_from', $_POST)) {
            $mf = trim((string) $_POST['mail_from']);
            $vals['mail_from'] = ($mf === '' || filter_var($mf, FILTER_VALIDATE_EMAIL)) ? $mf : Settings::get('mail_from', '');
        }

        // Paiements Stripe (clés + don + plans d'abonnement)
        if (array_key_exists('stripe_secret', $_POST)) {
            $vals['stripe_secret']         = trim((string) $_POST['stripe_secret']);
            $vals['stripe_publishable']    = trim((string) ($_POST['stripe_publishable'] ?? ''));
            $vals['stripe_webhook_secret'] = trim((string) ($_POST['stripe_webhook_secret'] ?? ''));
            $cur = strtolower(trim((string) ($_POST['stripe_currency'] ?? 'eur')));
            $vals['stripe_currency'] = preg_match('/^[a-z]{3}$/', $cur) ? $cur : 'eur';
            $vals['stripe_donation_label'] = mb_substr(trim((string) ($_POST['stripe_donation_label'] ?? '')), 0, 120) ?: 'Soutenir la communauté';
            $amts = array_values(array_filter(array_map('intval', explode(',', (string) ($_POST['stripe_donation_amounts'] ?? '')))));
            $vals['stripe_donation_amounts'] = $amts ? implode(',', $amts) : '5,10,20,50';

            $names     = (array) ($_POST['plan_name'] ?? []);
            $amounts   = (array) ($_POST['plan_amount'] ?? []);
            $intervals = (array) ($_POST['plan_interval'] ?? []);
            $plans = [];
            $n = count($names);
            for ($i = 0; $i < $n; $i++) {
                $nm = trim((string) ($names[$i] ?? ''));
                $am = (float) str_replace(',', '.', (string) ($amounts[$i] ?? 0));
                if ($nm === '' || $am <= 0) { continue; }
                $plans[] = [
                    'name'     => mb_substr($nm, 0, 80),
                    'amount'   => round($am, 2),
                    'interval' => (($intervals[$i] ?? 'month') === 'year') ? 'year' : 'month',
                ];
            }
            $vals['stripe_plans'] = json_encode(array_values($plans), JSON_UNESCAPED_UNICODE);
        }

        // Page d'accueil
        if (isset($_POST['main_title']))                { $vals['main_title']   = trim($_POST['main_title']) ?: 'RPN'; }
        if (array_key_exists('main_message', $_POST))   { $vals['main_message'] = trim($_POST['main_message']); }
        if (array_key_exists('main_footer', $_POST))    { $vals['main_footer']  = trim($_POST['main_footer']); }

        if ($vals) {
            Settings::save($vals);
        }
        // Régénération de la clé API (case cochée) : nouvelle clé aléatoire.
        if (!empty($_POST['regen_api_key'])) {
            Settings::save(['api_key' => 'rpmapi_' . bin2hex(random_bytes(18))]);
        }
        Session::set('settings_saved', true);
        redirect('admin/settings' . $this->sectionHash());
    }

    /** Ancre #onglet à rouvrir après enregistrement (champ caché « section »). */
    private function sectionHash(): string
    {
        $s = preg_replace('/[^a-z0-9\-]/', '', (string) ($_POST['section'] ?? ''));
        return $s !== '' ? '#' . $s : '';
    }

    /** Déconnexion de l'espace admin. */
    public function logout(): void
    {
        Session::destroy();
        redirect('admin/login');
    }
}

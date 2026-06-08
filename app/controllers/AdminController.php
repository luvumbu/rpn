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
            'name'    => $db['user'],
            'email'   => '',
            'picture' => '',
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

    /** Générateur de favicon (admin) : page de réglages. */
    public function favicon(): void
    {
        $this->guard();
        view('admin/favicon', [
            'user'    => Session::user(),
            'version' => (string) Settings::get('favicon_version', '1'),
            'custom'  => (int) Settings::get('favicon_custom', 0) === 1,
            'saved'   => Session::get('favicon_saved'),
            'error'   => Session::get('favicon_error'),
        ]);
        Session::remove('favicon_saved');
        Session::remove('favicon_error');
    }

    /** Génère et applique le nouveau favicon (depuis un texte ou une image). */
    public function saveFavicon(): void
    {
        $this->guard();
        $mode = ($_POST['mode'] ?? 'text') === 'image' ? 'image' : 'text';
        try {
            if ($mode === 'image' && !empty($_FILES['favicon_img']['name'])) {
                Favicon::fromUpload('favicon_img');
            } else {
                Favicon::fromText(
                    (string) ($_POST['text'] ?? 'R'),
                    (string) ($_POST['bg'] ?? '#14110f'),
                    (string) ($_POST['fg'] ?? '#f4c14b'),
                    !empty($_POST['round'])
                );
            }
            Session::set('favicon_saved', true);
        } catch (\Throwable $e) {
            Session::set('favicon_error', $e->getMessage());
        }
        redirect('admin/favicon');
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
    public function globalStyle(): void
    {
        $this->guard();
        $saved = Session::get('gstyle_saved');
        Session::remove('gstyle_saved');

        view('admin/style', [
            'user'        => Session::user(),
            'fonts'       => GlobalStyle::fonts(),
            'shadows'     => GlobalStyle::shadows(),
            'fontEnabled' => GlobalStyle::fontEnabled(),
            'fontKey'     => GlobalStyle::fontKey(),
            'radius'      => GlobalStyle::radius(),
            'shadowKey'   => GlobalStyle::shadowKey(),
            'anim'        => GlobalStyle::animationsEnabled(),
            'saved'       => $saved,
        ]);
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
        redirect('admin/style');
    }

    /** Page des paramètres (clés API Google, durée de blocage...). */
    public function settings(): void
    {
        $this->guard();
        $saved = Session::get('settings_saved');
        Session::remove('settings_saved');

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
            'saved'        => $saved,
        ]);
    }

    /** Enregistre les paramètres. */
    public function saveSettings(): void
    {
        $this->guard();
        Settings::save([
            'google_client_id'     => trim($_POST['client_id'] ?? ''),
            'google_client_secret' => trim($_POST['client_secret'] ?? ''),
            'block_hours'          => max(1, (int) ($_POST['block_hours'] ?? 24)),
            'max_attempts'         => max(1, (int) ($_POST['max_attempts'] ?? 3)),
            'theme'                => array_key_exists($_POST['theme'] ?? '', Theme::all()) ? $_POST['theme'] : 'panafricain',
            'theme_custom_bg'      => Theme::hex($_POST['tc_bg'] ?? '', '#14110f'),
            'theme_custom_text'    => Theme::hex($_POST['tc_text'] ?? '', '#ffffff'),
            'theme_custom_accent'  => Theme::hex($_POST['tc_accent'] ?? '', '#f4c14b'),
            'theme_custom_ink'     => Theme::hex($_POST['tc_ink'] ?? '', '#14110f'),
            'theme_custom_rouge'   => Theme::hex($_POST['tc_rouge'] ?? '', '#e63946'),
            'theme_custom_vert'    => Theme::hex($_POST['tc_vert'] ?? '', '#2a9d4a'),
            'theme_custom_or'      => Theme::hex($_POST['tc_or'] ?? '', '#f4c14b'),
            'default_template'     => ArticleTemplate::key($_POST['default_template'] ?? 'standard'),
            'main_title'           => trim($_POST['main_title'] ?? '') ?: 'RPN',
            'main_message'         => trim($_POST['main_message'] ?? ''),
            'main_footer'          => trim($_POST['main_footer'] ?? ''),
        ]);
        // Régénération de la clé API (case cochée) : nouvelle clé aléatoire.
        if (!empty($_POST['regen_api_key'])) {
            Settings::save(['api_key' => 'rpmapi_' . bin2hex(random_bytes(18))]);
        }
        Session::set('settings_saved', true);
        redirect('admin/settings');
    }

    /** Déconnexion de l'espace admin. */
    public function logout(): void
    {
        Session::destroy();
        redirect('admin/login');
    }
}

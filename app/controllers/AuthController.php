<?php
/**
 * CONTRÔLEUR Auth
 * Gère la connexion, le retour de Google (callback) et la déconnexion.
 */
class AuthController
{
    private GoogleClient $google;

    public function __construct()
    {
        $this->google = new GoogleClient();
    }

    /** Affiche la page de connexion. */
    public function showLogin(): void
    {
        if (Session::has('user')) {
            redirect('dashboard');
        }

        // "state" anti-CSRF (sécurité)
        $state = bin2hex(random_bytes(16));
        Session::set('oauth_state', $state);

        // Tous les articles PUBLICS (publiés, de premier niveau, non masqués par
        // signalements) — affichés sur la page d'accueil pour tout visiteur.
        $roots = Article::roots();
        $flags = Article::flagCountsFor(array_map(fn ($a) => (int) $a['id'], $roots));
        $publicArticles = array_values(array_filter($roots, function ($a) use ($flags) {
            return (int) $a['active'] === 1
                && !Article::isFlagHidden($a, $flags[(int) $a['id']] ?? 0);
        }));

        view('login', [
            'googleLoginUrl' => $this->google->getAuthUrl($state),
            'announcements'  => Article::announcements(5),
            'articles'       => $publicArticles,
            'error'          => Session::get('auth_error'),
            'success'        => Session::get('login_success'),
            'old'            => Session::get('auth_old') ?? [],
        ]);
        Session::remove('auth_error');
        Session::remove('login_success');
        Session::remove('auth_old');
    }

    /** Affiche le formulaire d'inscription (membre sans compte Google). */
    public function showRegister(): void
    {
        if (Session::has('user')) {
            redirect('dashboard');
        }
        view('register', [
            'error' => Session::get('auth_error'),
            'old'   => Session::get('auth_old') ?? [],
        ]);
        Session::remove('auth_error');
        Session::remove('auth_old');
    }

    /**
     * Inscription classique : nom + email + mot de passe.
     * Toujours créé en « membre » (jamais admin, même si l'email correspond à un
     * administrateur — la promotion admin passe uniquement par Google / l'espace admin).
     */
    public function register(): void
    {
        if (Session::has('user')) {
            redirect('dashboard');
        }

        $name  = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $pass  = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password2'] ?? '');

        $err = null;
        if ($name === '' || $email === '' || $pass === '') {
            $err = 'Merci de remplir tous les champs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Cette adresse email n\'est pas valide.';
        } elseif (mb_strlen($pass) < 6) {
            $err = 'Le mot de passe doit faire au moins 6 caractères.';
        } elseif ($pass !== $pass2) {
            $err = 'Les deux mots de passe ne sont pas identiques.';
        } elseif (User::findByEmail($email)) {
            $err = 'Un compte existe déjà avec cet email. Connecte-toi plutôt.';
        }

        if ($err) {
            Session::set('auth_error', $err);
            Session::set('auth_old', ['name' => $name, 'email' => $email]);
            redirect('register');
        }

        $row = User::createMember($name, $email, $pass);
        if (!$row) {
            Session::set('auth_error', 'Impossible de créer le compte. Réessaie.');
            redirect('register');
        }

        $this->startSession($row);
        redirect('dashboard');
    }

    /** Connexion classique : email + mot de passe (membre non-Google). */
    public function login(): void
    {
        if (Session::has('user')) {
            redirect('dashboard');
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $pass  = (string) ($_POST['password'] ?? '');
        $row   = $email !== '' ? User::findByEmail($email) : null;

        // Identifiants faux, ou compte sans mot de passe (créé via Google) → erreur générique.
        if (!$row || empty($row['password']) || !password_verify($pass, $row['password'])) {
            Session::set('auth_error', 'Email ou mot de passe incorrect.');
            Session::set('auth_old', ['email' => $email]);
            redirect('');
        }
        if (!empty($row['blocked'])) {
            Session::set('auth_error', 'Ton compte a été bloqué.');
            redirect('');
        }

        User::touchLogin((int) $row['id']);
        $this->startSession($row);
        redirect('dashboard');
    }

    /** Démarre la session pour un utilisateur (Google, inscription ou connexion email). */
    private function startSession(array $row): void
    {
        Session::set('user', [
            'id'      => (int) $row['id'],
            'name'    => $row['name'],
            'email'   => $row['email'],
            'picture' => $row['picture'] ?? null,
            'role'    => $row['role'],
        ]);
        // Thème personnel du membre (s'applique uniquement à lui).
        Session::set('theme_pref', (string) ($row['theme_pref'] ?? ''));
    }

    /** Reçoit la réponse de Google après connexion. */
    public function callback(): void
    {
        if (isset($_GET['error'])) {
            exit('Connexion refusée : ' . htmlspecialchars($_GET['error']));
        }

        // Déjà connecté (appel en double / rechargement) → tableau de bord
        if (Session::has('user')) {
            redirect('dashboard');
        }

        if (empty($_GET['code'])) {
            exit('Code manquant. <a href="' . url('') . '">Réessayer</a>');
        }

        // Vérification anti-CSRF : le state doit correspondre
        $state = Session::get('oauth_state');
        if (empty($_GET['state']) || empty($state) || !hash_equals($state, $_GET['state'])) {
            exit('Session expirée. <a href="' . url('') . '">Se reconnecter</a>');
        }

        // 1. Code → token
        $token = $this->google->fetchToken($_GET['code']);
        if (!$token) {
            exit('Erreur lors de la récupération du token Google.');
        }

        // 2. Token → profil
        $info = $this->google->fetchUserInfo($token['access_token']);
        if (!$info) {
            exit('Impossible de récupérer le profil Google.');
        }

        // 3. Cet email fait-il partie des administrateurs ?
        $adminEmails = array_map('strtolower', ADMIN_EMAILS);
        $isAdmin = in_array(strtolower($info['email']), $adminEmails, true);

        // 4. Enregistre (ou met à jour) le membre en base de données
        $row = User::upsertFromGoogle($info, $isAdmin);

        // 5. Compte bloqué ? → accès refusé
        if (!empty($row['blocked'])) {
            exit('Ton compte a été bloqué. <a href="' . url('') . '">Retour</a>');
        }

        // 6. Connexion réussie → on stocke l'utilisateur en session
        $this->startSession($row);
        Session::remove('oauth_state');

        redirect('dashboard');
    }

    /** Déconnecte l'utilisateur. */
    public function logout(): void
    {
        Session::destroy();
        redirect('');
    }

    /** Affiche le formulaire « Mot de passe oublié ». */
    public function showForgot(): void
    {
        if (Session::has('user')) {
            redirect('dashboard');
        }
        view('auth/forgot', [
            'error'  => Session::get('auth_error'),
            'notice' => Session::get('auth_notice'),
        ]);
        Session::remove('auth_error');
        Session::remove('auth_notice');
    }

    /**
     * Traite la demande : si un compte (avec mot de passe) existe pour cet email,
     * envoie un lien de réinitialisation. Réponse TOUJOURS générique (on ne révèle
     * pas si l'email existe).
     */
    public function forgot(): void
    {
        if (Session::has('user')) {
            redirect('dashboard');
        }
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $row = User::findByEmail($email);
            // Uniquement les comptes avec mot de passe (les comptes Google seuls n'en ont pas).
            if ($row && !empty($row['password'])) {
                $token  = PasswordReset::create((int) $row['id']);
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $link   = $scheme . '://' . $host . url('reset') . '?token=' . $token;
                $body = Mailer::template(
                    'Réinitialisation de ton mot de passe',
                    '<p>Bonjour,</p>'
                    . '<p>Tu as demandé à réinitialiser ton mot de passe. Clique sur le bouton ci-dessous (valable 1 heure) :</p>'
                    . '<p><a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#f4c14b;color:#14110f;font-weight:bold;text-decoration:none;padding:12px 22px;border-radius:10px;">Réinitialiser mon mot de passe</a></p>'
                    . '<p style="font-size:12px;color:#6b7280;">Si le bouton ne fonctionne pas, copie ce lien dans ton navigateur :<br>' . htmlspecialchars($link) . '</p>'
                    . '<p style="font-size:12px;color:#6b7280;">Si tu n\'es pas à l\'origine de cette demande, ignore cet e-mail : rien ne sera changé.</p>'
                );
                Mailer::send($email, 'Réinitialisation de ton mot de passe', $body);
            }
        }
        Session::set('auth_notice', 'Si un compte existe avec cet e-mail, un lien de réinitialisation vient d\'être envoyé. Vérifie ta boîte de réception (et les spams).');
        redirect('forgot');
    }

    /** Affiche le formulaire de nouveau mot de passe (jeton passé dans l'URL). */
    public function showReset(): void
    {
        if (Session::has('user')) {
            redirect('dashboard');
        }
        $token = (string) ($_GET['token'] ?? '');
        view('auth/reset', [
            'token' => $token,
            'valid' => PasswordReset::findValid($token) !== null,
            'error' => Session::get('auth_error'),
        ]);
        Session::remove('auth_error');
    }

    /** Enregistre le nouveau mot de passe après vérification du jeton. */
    public function reset(): void
    {
        if (Session::has('user')) {
            redirect('dashboard');
        }
        $token = (string) ($_POST['token'] ?? '');
        $pass  = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password2'] ?? '');

        $row = PasswordReset::findValid($token);
        if (!$row) {
            Session::set('auth_error', 'Lien invalide ou expiré. Refais une demande.');
            redirect('forgot');
        }
        if (mb_strlen($pass) < 6) {
            Session::set('auth_error', 'Le mot de passe doit faire au moins 6 caractères.');
            redirect('reset?token=' . urlencode($token));
        }
        if ($pass !== $pass2) {
            Session::set('auth_error', 'Les deux mots de passe ne sont pas identiques.');
            redirect('reset?token=' . urlencode($token));
        }

        User::setPassword((int) $row['user_id'], password_hash($pass, PASSWORD_DEFAULT));
        PasswordReset::markUsed((int) $row['id']);
        Session::set('login_success', '✅ Ton mot de passe a été modifié. Tu peux te connecter.');
        redirect('');
    }
}

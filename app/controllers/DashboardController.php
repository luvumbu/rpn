<?php
/**
 * CONTRÔLEUR Dashboard
 * Affiche le tableau de bord, accessible uniquement si l'utilisateur est connecté.
 */
class DashboardController
{
    public function index(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }

        $user = Session::user();
        $uid  = (int) ($user['id'] ?? 0);

        // Rappel : mes créneaux réservés qui ont lieu AUJOURD'HUI (à venir).
        $today       = date('Y-m-d');
        $todayEvents = array_values(array_filter(
            AppointmentBooking::forUserDetailed($uid),
            static fn ($b) => date('Y-m-d', strtotime($b['start_at'])) === $today
        ));

        // Code membre + consentement (seulement pour un vrai membre, pas l'admin id 0).
        $myCode       = $uid > 0 ? User::ensureCode($uid) : '';
        $me           = $uid > 0 ? User::findById($uid) : null;
        $discoverable = $me ? ((int) ($me['discoverable'] ?? 0) === 1) : false;
        $myTheme      = $me ? (string) ($me['theme_pref'] ?? '') : (string) ($_SESSION['theme_pref'] ?? '');

        view('dashboard', [
            'user'          => $user,
            'urgents'       => $uid > 0 ? Urgent::forUser($uid) : [],
            'totalArticles' => Article::countActive(),
            'myArticles'    => Article::countByAuthor($uid),
            'totalQuizzes'  => Quiz::countActive(),
            'myQuizzes'     => Quiz::countByAuthor($uid),
            'unreadNotifs'  => Notification::unreadCount($uid),
            'unreadMsgs'    => $uid > 0 ? Message::unreadCount($uid) : 0,
            'level'         => $uid > 0 ? Level::info($uid) : null,
            'levelDetail'   => $uid > 0 ? Level::breakdown($uid) : [],
            'todayEvents'   => $todayEvents,
            'myCode'        => $myCode,
            'discoverable'  => $discoverable,
            'themes'        => Theme::all(),
            'myTheme'       => $myTheme,
            'siteThemeKey'  => array_key_exists(Settings::get('theme', 'panafricain'), Theme::all())
                ? Settings::get('theme', 'panafricain') : 'panafricain',
            'photoError'    => Session::get('photo_error'),
            'myDomains'         => $me ? User::domainsToList($me['domains'] ?? '') : [],
            'domainSuggestions' => User::domainSuggestions(),
            'domainCategories'  => User::domainCategories(),
            'myAddress'         => $me['address'] ?? '',
            'myCountries'       => $me ? Countries::toList($me['countries'] ?? '') : [],
            'dashNotice'    => Session::get('dash_notice'),
            'dashError'     => Session::get('dash_error'),
        ]);
        Session::remove('photo_error');
        Session::remove('dash_notice');
        Session::remove('dash_error');
    }

    /** Page « Niveaux » : tous les paliers, le barème de points et ma position. */
    public function levels(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $uid = (int) (Session::user()['id'] ?? 0);
        view('niveaux', [
            'user'   => Session::user(),
            'info'   => $uid > 0 ? Level::info($uid) : Level::fromPoints(0),
            'detail' => $uid > 0 ? Level::breakdown($uid) : [],
            'levels' => Level::LEVELS,
            'scale'  => Level::scale(),
        ]);
    }

    /** Exporte le projet de l'utilisateur connecté (SES articles + SES questionnaires). */
    public function exportMine(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $uid = (int) (Session::user()['id'] ?? 0);
        if ($uid <= 0) {
            redirect('dashboard');
        }
        if (!class_exists('ZipArchive')) {
            Session::set('dash_error', "L'extension ZIP de PHP est indisponible : export impossible.");
            redirect('dashboard');
        }
        ProjectArchive::export(Article::byAuthor($uid), Quiz::byAuthor($uid), 'mon-projet-rpn');
    }

    /** Importe une archive : recrée le contenu EN BROUILLON, attribué à l'utilisateur. */
    /** Page d'import dédiée (formulaire simple, sans JS) — accès fiable. */
    public function importPage(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        view('import', [
            'user'   => Session::user(),
            'notice' => Session::get('dash_notice'),
            'error'  => Session::get('dash_error'),
        ]);
        Session::remove('dash_notice');
        Session::remove('dash_error');
    }

    public function importMine(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $me  = Session::user();
        $uid = (int) ($me['id'] ?? 0);
        if ($uid <= 0) {
            redirect('dashboard');
        }
        if (!class_exists('ZipArchive')) {
            Session::set('dash_error', "L'extension ZIP de PHP est indisponible : import impossible.");
            redirect('dashboard');
        }
        $f = $_FILES['archive'] ?? null;
        if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            Session::set('dash_error', 'Choisis un fichier .zip d\'export valide.');
            redirect('dashboard');
        }
        $res = ProjectArchive::import($f['tmp_name'], $uid, $me['name'] ?: ($me['email'] ?? 'Membre'));
        if (isset($res['error'])) {
            Session::set('dash_error', $res['error']);
        } else {
            $a = (int) $res['articles'];
            $q = (int) $res['quizzes'];
            $sk = (int) ($res['skipped'] ?? 0);
            $msg = "✅ Import : $a article" . ($a > 1 ? 's' : '') . " et $q questionnaire" . ($q > 1 ? 's' : '')
                . " ajouté(s) en brouillon, à ton nom.";
            if ($sk > 0) {
                $msg .= " ($sk déjà présent" . ($sk > 1 ? 's' : '') . " → ignoré" . ($sk > 1 ? 's' : '') . ", pas de doublon.)";
            }
            Session::set('dash_notice', $msg);
        }
        redirect('dashboard');
    }

    /** Le membre définit son/ses rôle(s) ou domaine(s) (suggestions + saisie libre). */
    public function saveRoles(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $uid = (int) (Session::user()['id'] ?? 0);
        if ($uid <= 0) {
            redirect('dashboard');
        }

        // Domaines pré-cochés (suggestions) + saisie libre « Autres ».
        $picked = array_map('strval', (array) ($_POST['domains'] ?? []));
        $custom = (string) ($_POST['domains_custom'] ?? '');
        $list   = [];
        foreach (array_merge($picked, explode(',', $custom)) as $d) {
            $d = trim($d);
            if ($d !== '' && !in_array($d, $list, true)) {
                $list[] = $d;
            }
        }
        $list = array_slice($list, 0, 12); // garde-fou (nombre raisonnable)

        User::setDomains($uid, implode(', ', $list));

        // Adresse : enregistrée ET géocodée (lat/lng) pour la carte de la diaspora
        // et la recherche de proximité par kilomètres (mêmes champs que l'agenda).
        $addr = trim((string) ($_POST['address'] ?? ''));
        User::setAddress($uid, $addr);
        $lat = $lng = null;
        if ($addr !== '') {
            // Coords choisies via l'autocomplétion → on les utilise (évite un appel réseau).
            if (is_numeric($_POST['addr_lat'] ?? '') && is_numeric($_POST['addr_lng'] ?? '')) {
                $lat = (float) $_POST['addr_lat'];
                $lng = (float) $_POST['addr_lng'];
            } else {
                $geo = Geocoder::geocode($addr);
                if ($geo) {
                    $lat = $geo['lat'];
                    $lng = $geo['lng'];
                }
            }
        }
        User::setDefaultCity($uid, $addr !== '' ? $addr : null, $lat, $lng);

        redirect('dashboard');
    }

    /** Le membre enregistre son/ses pays d'origine (carte séparée). */
    public function saveCountries(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $uid = (int) (Session::user()['id'] ?? 0);
        if ($uid <= 0) {
            redirect('dashboard');
        }
        User::setCountries($uid, (array) ($_POST['countries'] ?? []));
        redirect('dashboard');
    }

    /** Le membre ajoute / change / retire sa photo de profil. */
    public function savePhoto(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $uid = (int) (Session::user()['id'] ?? 0);
        if ($uid <= 0) {
            redirect('dashboard');
        }
        $me      = User::findById($uid);
        $current = (string) ($me['picture'] ?? '');
        $isLocal = $current !== '' && !preg_match('#^https?://#i', $current);

        // Retrait demandé.
        if (!empty($_POST['remove_photo'])) {
            if ($isLocal) {
                Upload::delete($current, 'avatars');
            }
            User::setPicture($uid, null);
            $this->refreshSessionPicture(null);
            redirect('dashboard');
        }

        // Envoi d'une nouvelle photo.
        try {
            $name = Upload::image('photo', 'avatars');
        } catch (RuntimeException $e) {
            Session::set('photo_error', $e->getMessage());
            redirect('dashboard');
        }
        if (!empty($name)) {
            if ($isLocal) {
                Upload::delete($current, 'avatars'); // remplace l'ancienne photo locale
            }
            User::setPicture($uid, $name);
            $this->refreshSessionPicture($name);
        }
        redirect('dashboard');
    }

    /** Met à jour la photo dans la session pour un affichage immédiat. */
    private function refreshSessionPicture(?string $picture): void
    {
        $user = Session::user();
        if (is_array($user)) {
            $user['picture'] = $picture;
            Session::set('user', $user);
        }
    }

    /** L'utilisateur ferme une alerte URGENTE (croix) — pour lui seul. */
    public function dismissUrgent(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $uid = (int) (Session::user()['id'] ?? 0);
        $type = (string) ($_POST['type'] ?? '');
        $id   = (int) ($_POST['id'] ?? 0);
        if ($uid > 0 && $id > 0) {
            Urgent::dismiss($uid, $type, $id);
        }
        redirect('dashboard');
    }

    /** Le membre choisit son thème PERSONNEL (s'applique uniquement à lui). */
    public function saveTheme(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $uid   = (int) (Session::user()['id'] ?? 0);
        $theme = (string) ($_POST['theme_pref'] ?? '');
        if ($theme !== '' && !array_key_exists($theme, Theme::all())) {
            $theme = ''; // valeur inconnue → thème du site
        }
        if ($uid > 0) {
            User::setThemePref($uid, $theme);
        }
        $_SESSION['theme_pref'] = $theme; // application immédiate pour cette session
        redirect('dashboard');
    }

    /** Le membre autorise / révoque le fait d'être ajouté aux activités. */
    public function toggleDiscoverable(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $uid = (int) (Session::user()['id'] ?? 0);
        if ($uid > 0) {
            User::setDiscoverable($uid, !empty($_POST['discoverable']));
        }
        redirect('dashboard');
    }
}

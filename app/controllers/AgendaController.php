<?php
/**
 * CONTRÔLEUR Agenda
 * Rendez-vous entre membres. Chaque membre connecté peut :
 *  - proposer des créneaux dans son agenda (avec un nombre de places) ;
 *  - réserver une place sur les créneaux des autres membres.
 * Aucun rôle « prof » / « élève » : tout le monde peut proposer et réserver.
 */
class AgendaController
{
    /** Bornes du nombre de places par créneau. */
    private const CAP_MIN = 1;
    private const CAP_MAX = 20;

    private function guard(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
    }

    /** Date/heure lisible d'un créneau : « le 09/06/2026 à 14:00 ». */
    private function slotWhen(string $start): string
    {
        $ts = strtotime($start);
        return 'le ' . date('d/m/Y', $ts) . ' à ' . date('H:i', $ts);
    }

    /** Un événement est « terminé » (passé) quand début + durée est dépassé. */
    private function isEnded(array $appt): bool
    {
        return strtotime($appt['start_at']) + (int) ($appt['duration_min'] ?? 0) * 60 < time();
    }

    /**
     * Bloque toute modification d'un créneau déjà terminé (archive en lecture
     * seule). Renvoie true si l'action doit s'arrêter (et a déjà redirigé).
     */
    private function blockIfEnded(array $appt): bool
    {
        if ($this->isEnded($appt)) {
            Session::set('agenda_error', 'Cet événement est terminé : il ne peut plus être modifié.');
            redirect('agenda'); // redirect() fait exit
            return true;
        }
        return false;
    }

    /** Page agenda : mes créneaux + créneaux réservables des autres. */
    public function index(): void
    {
        $this->guard();
        $u   = Session::user();
        $uid = (int) ($u['id'] ?? 0);

        // Ville par défaut de l'utilisateur (membre via la table users ; admin id=0
        // via la session) — proposée comme raccourci dans la recherche par proximité.
        $defaultCity = $defaultLat = $defaultLng = null;
        if ($uid > 0 && ($me = User::findById($uid))) {
            $defaultCity = (string) ($me['default_city'] ?? '');
            $defaultLat  = $me['default_lat'] !== null ? (float) $me['default_lat'] : null;
            $defaultLng  = $me['default_lng'] !== null ? (float) $me['default_lng'] : null;
        } else {
            $defaultCity = (string) ($_SESSION['default_city'] ?? '');
            $defaultLat  = isset($_SESSION['default_lat']) ? (float) $_SESSION['default_lat'] : null;
            $defaultLng  = isset($_SESSION['default_lng']) ? (float) $_SESSION['default_lng'] : null;
        }

        $mine = Appointment::forOwner($uid);
        foreach ($mine as &$m) {
            $m['bookers'] = AppointmentBooking::forAppointment((int) $m['id']);
            $m['changes'] = AppointmentChange::forAppointment((int) $m['id']);
        }
        unset($m);

        // Sépare MES créneaux : à venir (modifiables) vs déjà terminés (archive en
        // lecture seule, onglet « Événements passés »). « Passé » = l'événement est
        // fini (début + durée < maintenant) ; un événement en cours reste modifiable.
        $now          = time();
        $mineUpcoming = [];
        $minePast     = [];
        foreach ($mine as $m) {
            $end = strtotime($m['start_at']) + (int) $m['duration_min'] * 60;
            if ($end < $now) {
                $minePast[] = $m;
            } else {
                $mineUpcoming[] = $m;
            }
        }
        // Archive : du plus récent au plus ancien.
        usort($minePast, static fn ($x, $y) => strtotime($y['start_at']) <=> strtotime($x['start_at']));

        // Notes (réputation) des membres inscrits. L'HÔTE les voit toujours sur SES
        // créneaux ; sur la fiche publique d'un créneau, elles n'apparaissent que si
        // l'hôte a activé l'option (show_booker_ratings). On accumule ici les ids
        // concernés (mes créneaux + fiches publiques autorisées) pour une seule requête.
        $bookerIds = [];
        foreach ($mine as $m) {
            foreach ($m['bookers'] as $bk) {
                $bookerIds[] = (int) $bk['user_id'];
            }
        }

        // Charge les inscrits d'un créneau public quand l'hôte autorise l'affichage.
        $loadPublicBookers = static function (array &$slot) use (&$bookerIds): void {
            if ((int) ($slot['show_booker_ratings'] ?? 0) === 1) {
                $slot['bookers'] = AppointmentBooking::forAppointment((int) $slot['id']);
                foreach ($slot['bookers'] as $bk) {
                    $bookerIds[] = (int) $bk['user_id'];
                }
            }
        };

        // Tous les créneaux publics, paginés (5 par page, Précédent/Suivant).
        $allAvailable = Appointment::upcomingOthers($uid);
        $perPage      = 5;
        $availPages   = max(1, (int) ceil(count($allAvailable) / $perPage));
        $availPage    = max(1, min($availPages, (int) ($_GET['p'] ?? 1)));
        $available    = array_slice($allAvailable, ($availPage - 1) * $perPage, $perPage);
        foreach ($available as &$av) {
            $av['changes'] = AppointmentChange::forAppointment((int) $av['id']);
            $loadPublicBookers($av);
        }
        unset($av);

        // Mes réservations (créneaux que J'AI réservés, publics ou privés).
        $myBookings = AppointmentBooking::forUserDetailed($uid);
        foreach ($myBookings as &$mb) {
            $mb['changes']   = AppointmentChange::forAppointment((int) $mb['id']);
            $mb['my_rating'] = Rating::myRating((int) $mb['owner_id'], $uid);
            $loadPublicBookers($mb);
        }
        unset($mb);

        // ----- Aperçu calendrier (style Google Agenda) -----
        // Regroupe MES créneaux + MES réservations par jour, pour les vues
        // Jour / Semaine / Mois / Liste de la page principale.
        $calEvents = [];
        $calIds    = [];   // ids déjà présents (mes créneaux + mes réservations)
        foreach ($mine as $m) {
            $m['cal_kind'] = 'mine';
            $calEvents[] = $m;
            $calIds[(int) $m['id']] = true;
        }
        foreach ($myBookings as $b) {
            $b['cal_kind'] = 'booked';
            $calEvents[] = $b;
            $calIds[(int) $b['id']] = true;
        }

        // Option « afficher tous les événements publics » (case à cocher) : ajoute
        // les créneaux publics des AUTRES membres (hors ceux déjà listés ci-dessus).
        $calShowPublic = !empty($_GET['cpublic']);
        if ($calShowPublic) {
            foreach (Appointment::allForGlobal($uid, Session::isAdmin()) as $p) {
                if (isset($calIds[(int) $p['id']])) {
                    continue;
                }
                $p['cal_kind'] = 'public';
                $calEvents[] = $p;
                $calIds[(int) $p['id']] = true;
            }
        }

        $calByDate = [];
        foreach ($calEvents as $e) {
            $calByDate[date('Y-m-d', strtotime($e['start_at']))][] = $e;
        }
        foreach ($calByDate as &$dayEvents) {
            usort($dayEvents, static fn ($x, $y) => strtotime($x['start_at']) <=> strtotime($y['start_at']));
        }
        unset($dayEvents);

        // Paramètres de navigation du calendrier (préfixe c… pour ne pas entrer
        // en conflit avec la pagination ?p= de la liste publique).
        $calView = (string) ($_GET['cview'] ?? 'month');
        if (!in_array($calView, ['month', 'week', 'day', 'list'], true)) {
            $calView = 'month';
        }
        $calMonth = (string) ($_GET['cmonth'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $calMonth)) {
            $calMonth = date('Y-m');
        }
        $calDay = (string) ($_GET['cday'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $calDay)) {
            $calDay = date('Y-m-d');
        }
        $calWeekRaw = (string) ($_GET['cweek'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $calWeekRaw)) {
            $calWeekRaw = date('Y-m-d');
        }
        $calWeekMonday = date('Y-m-d', strtotime('monday this week', strtotime($calWeekRaw)));

        // Onglet actif au chargement : « calendrier » si on navigue dedans.
        $activeTab = (string) ($_GET['tab'] ?? '');
        $calNav    = isset($_GET['cview']) || isset($_GET['cmonth']) || isset($_GET['cweek']) || isset($_GET['cday']);
        if (!in_array($activeTab, ['find', 'book', 'mine', 'new', 'cal', 'past'], true)) {
            if ($calNav) {
                $activeTab = 'cal';
            } elseif (trim($_GET['code'] ?? '') !== '' || trim($_GET['near'] ?? '') !== '' || isset($_GET['p'])) {
                $activeTab = 'find'; // une recherche est en cours → onglet Recherche
            } else {
                $activeTab = 'mine'; // PAR DÉFAUT : mes créneaux (mes événements)
            }
        }

        // Recherche d'un RDV privé par code (2 lettres + 3 chiffres).
        $searchCode  = strtoupper(trim($_GET['code'] ?? ''));
        $found       = null;
        $searchError = null;
        if ($searchCode !== '') {
            if (preg_match('/^[A-Z]{2}[0-9]{3}$/', $searchCode)) {
                $found = Appointment::findByCode($searchCode);
                if ($found) {
                    $found['changes'] = AppointmentChange::forAppointment((int) $found['id']);
                    $loadPublicBookers($found);
                } else {
                    $searchError = "Aucun rendez-vous privé ne correspond au code $searchCode.";
                }
            } else {
                $searchError = 'Le code doit être 2 lettres puis 3 chiffres (ex : AB123).';
            }
        }

        // Recherche par proximité : cours publics dans un rayon autour d'une zone
        // (adresse saisie) OU autour de ma position (géolocalisation navigateur).
        $radiusOptions = [5, 10, 20, 50, 100];
        $RADIUS_ALL    = 20000; // « Toutes » : couvre toute la Terre (distance max ~20015 km)
        $nearQuery     = trim($_GET['near'] ?? '');
        $nearLat       = $_GET['near_lat'] ?? '';
        $nearLng       = $_GET['near_lng'] ?? '';
        $radius        = (int) ($_GET['radius'] ?? 10);
        if (!in_array($radius, $radiusOptions, true) && $radius !== $RADIUS_ALL) {
            $radius = 10;
        }
        $nearResults = null;
        $nearError   = null;
        $nearCenter  = null;
        $hasGeo      = is_numeric($nearLat) && is_numeric($nearLng);

        // Périmètre choisi par l'utilisateur (cases à cocher). Par DÉFAUT (premier
        // chargement, sans soumission), les DEUX sont cochées : mes créneaux ET
        // les créneaux publics des autres. Quand le formulaire est soumis
        // (near_submitted), on lit l'état réel des cases (décochée = absente).
        // Un créneau privé n'apparaît que s'il est à moi (ou pour un admin).
        if (isset($_GET['near_submitted'])) {
            $nearMine   = !empty($_GET['near_mine']);
            $nearOthers = !empty($_GET['near_others']);
        } else {
            $nearMine   = true;
            $nearOthers = true;
        }
        if (!$nearMine && !$nearOthers) {
            $nearOthers = true; // au moins un périmètre
        }

        if ($hasGeo || $nearQuery !== '') {
            if ($hasGeo) {
                $nearCenter = ['lat' => (float) $nearLat, 'lng' => (float) $nearLng];
                if ($nearQuery === '') {
                    $nearQuery = 'Ma position';
                }
            } else {
                $nearCenter = Geocoder::geocode($nearQuery);
            }

            if ($nearCenter) {
                $nearResults = Appointment::nearby($nearCenter['lat'], $nearCenter['lng'], $radius, $uid,
                                                   $nearMine, $nearOthers, Session::isAdmin());
                foreach ($nearResults as &$nr) {
                    $nr['changes'] = AppointmentChange::forAppointment((int) $nr['id']);
                    $loadPublicBookers($nr);
                }
                unset($nr);
            } else {
                $nearError = 'Impossible de localiser « ' . $nearQuery . " ». Essaie une ville ou une adresse plus précise.";
            }
        }

        // Notes (réputation) des hôtes affichés, pour les étoiles.
        $ownerIds = [];
        foreach ($available as $a)   { $ownerIds[] = (int) $a['owner_id']; }
        foreach ($myBookings as $a)  { $ownerIds[] = (int) $a['owner_id']; }
        if ($nearResults) { foreach ($nearResults as $a) { $ownerIds[] = (int) $a['owner_id']; } }
        if ($found)       { $ownerIds[] = (int) $found['owner_id']; }
        $ratings = Rating::summaryFor($ownerIds);

        // Notes des inscrits (réputation) — calculées sur l'ensemble accumulé
        // (mes créneaux + fiches publiques où l'hôte a activé l'affichage).
        $bookerRatings = Rating::summaryFor($bookerIds);

        // Liste des membres pour l'ajout manuel par l'hôte : UNIQUEMENT ceux qui
        // ont donné leur accord (discoverable). Les autres ne sont pas proposés.
        $members = array_map(static function ($m) {
            $name = ($m['name'] ?: $m['email']) ?: ('Membre #' . $m['id']);
            $code = (string) ($m['member_code'] ?? '');
            return [
                'id'    => (int) $m['id'],
                'name'  => $name,
                'code'  => $code,
                'label' => $name . ($code ? ' (' . $code . ')' : ''),
            ];
        }, User::discoverable());

        // Chaque créneau À MOI doit avoir un code (backfill des anciens publics) → affiché dans « Mes créneaux ».
        foreach ($mineUpcoming as &$_mu) { $_mu['code'] = Appointment::ensureCode((int) $_mu['id'], $_mu['code'] ?? ''); }
        unset($_mu);
        foreach ($minePast as &$_mp) { $_mp['code'] = Appointment::ensureCode((int) $_mp['id'], $_mp['code'] ?? ''); }
        unset($_mp);

        // Photos + notes d'événement, pour tous les créneaux affichés (1 requête chacun).
        $apptIds = [];
        foreach ([$mineUpcoming, $minePast, $available, $myBookings, ($nearResults ?: [])] as $_list) {
            foreach ($_list as $_a) { $apptIds[] = (int) $_a['id']; }
        }
        if ($found) { $apptIds[] = (int) $found['id']; }
        $eventPhotos    = AppointmentImage::forAppointments($apptIds);
        $eventRatings   = AppointmentRating::summaryFor($apptIds);
        $myEventRatings = [];
        foreach ($myBookings as $_b) {
            $mr = AppointmentRating::mine((int) $_b['id'], $uid);
            if ($mr) { $myEventRatings[(int) $_b['id']] = (int) $mr['stars']; }
        }

        view('agenda/index', [
            'user'          => $u,
            'mine'          => $mine,
            'mineUpcoming'  => $mineUpcoming,
            'minePast'      => $minePast,
            'eventPhotos'    => $eventPhotos,
            'eventRatings'   => $eventRatings,
            'myEventRatings' => $myEventRatings,
            'bookerRatings' => $bookerRatings,
            'unreadNotifs'  => Notification::unreadCount($uid),
            'members'     => $members,
            'ratings'     => $ratings,
            'available'   => $available,
            'myBookedIds' => AppointmentBooking::appointmentIdsForUser($uid),
            'searchCode'  => $searchCode,
            'found'       => $found,
            'searchError' => $searchError,
            'myBookings'    => $myBookings,
            'calByDate'     => $calByDate,
            'calView'       => $calView,
            'calMonth'      => $calMonth,
            'calDay'        => $calDay,
            'calWeekMonday' => $calWeekMonday,
            'calShowPublic' => $calShowPublic,
            'activeTab'     => $activeTab,
            'availPage'     => $availPage,
            'availPages'    => $availPages,
            'nearQuery'     => $nearQuery,
            'nearMine'      => $nearMine,
            'nearOthers'    => $nearOthers,
            'radius'        => $radius,
            'radiusOptions' => $radiusOptions,
            'radiusAll'     => $RADIUS_ALL,
            'defaultCity'   => $defaultCity,
            'defaultLat'    => $defaultLat,
            'defaultLng'    => $defaultLng,
            'nearResults'   => $nearResults,
            'nearCenter'    => $nearCenter,
            'nearError'     => $nearError,
            'error'       => Session::get('agenda_error'),
            'notice'      => Session::get('agenda_notice'),
        ]);
        Session::remove('agenda_error');
        Session::remove('agenda_notice');
    }

    /**
     * Enregistre (ou efface) la ville par défaut de l'utilisateur, utilisée comme
     * raccourci dans la recherche par proximité. Géocode la ville pour mémoriser
     * ses coordonnées (ou utilise celles fournies par l'autocomplétion).
     */
    public function saveDefaultCity(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $city = trim($_POST['default_city'] ?? '');

        $lat = $lng = null;
        if ($city !== '') {
            if (is_numeric($_POST['default_lat'] ?? '') && is_numeric($_POST['default_lng'] ?? '')) {
                $lat = (float) $_POST['default_lat'];
                $lng = (float) $_POST['default_lng'];
            } else {
                $geo = Geocoder::geocode($city);
                if ($geo) {
                    $lat = $geo['lat'];
                    $lng = $geo['lng'];
                }
            }
        }

        // Persiste pour les membres (table users) ; pour l'admin id=0, on garde en session.
        if ($uid > 0) {
            User::setDefaultCity($uid, $city !== '' ? $city : null, $lat, $lng);
        }
        $_SESSION['default_city'] = $city;
        if ($lat !== null) {
            $_SESSION['default_lat'] = $lat;
            $_SESSION['default_lng'] = $lng;
        } else {
            unset($_SESSION['default_lat'], $_SESSION['default_lng']);
        }

        Session::set('agenda_notice', $city !== ''
            ? 'Ville par défaut enregistrée : ' . $city . '.'
            : 'Ville par défaut effacée.');
        redirect('agenda');
    }

    /**
     * AGENDA GLOBAL : vue d'ensemble de TOUS les rendez-vous (à venir et passés),
     * tous membres confondus. Les créneaux privés ne sont montrés qu'à leur hôte
     * (un admin voit tout). Affiché en frise chronologique, regroupé par jour.
     */
    public function globalAgenda(): void
    {
        $this->guard();
        $u       = Session::user();
        $uid     = (int) ($u['id'] ?? 0);
        $isAdmin = Session::isAdmin();

        $all = Appointment::allForGlobal($uid, $isAdmin);

        // Sépare à venir / passés (les passés du plus récent au plus ancien).
        $now = time();
        $upcoming = [];
        $past     = [];
        $places   = 0;
        $booked   = 0;
        foreach ($all as $a) {
            $places += (int) $a['capacity'];
            $booked += (int) ($a['booked'] ?? 0);
            if (strtotime($a['start_at']) >= $now) {
                $upcoming[] = $a;
            } else {
                $past[] = $a;
            }
        }
        $past = array_reverse($past);

        // Index par jour (Y-m-d => créneaux) pour la vue calendrier.
        $byDate = [];
        foreach ($all as $a) {
            $byDate[date('Y-m-d', strtotime($a['start_at']))][] = $a;
        }

        // Mois affiché par le calendrier (?month=AAAA-MM), défaut = mois courant.
        $month = (string) ($_GET['month'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        // Semaine affichée (?week=AAAA-MM-JJ) → ramenée au lundi de cette semaine.
        $week = (string) ($_GET['week'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $week)) {
            $week = date('Y-m-d');
        }
        $weekMonday = date('Y-m-d', strtotime('monday this week', strtotime($week)));

        // Vue active : 'cal' (mois), 'week' (semaine) ou 'list' (liste).
        $view = (string) ($_GET['view'] ?? 'cal');
        if (!in_array($view, ['cal', 'week', 'list'], true)) {
            $view = 'cal';
        }

        view('agenda/global', [
            'user'        => $u,
            'isAdmin'     => $isAdmin,
            'upcoming'    => $upcoming,
            'past'        => $past,
            'total'       => count($all),
            'totalPlaces' => $places,
            'totalBooked' => $booked,
            'myBookedIds' => AppointmentBooking::appointmentIdsForUser($uid),
            'unreadNotifs'=> Notification::unreadCount($uid),
            'byDate'      => $byDate,
            'month'       => $month,
            'weekMonday'  => $weekMonday,
            'view'        => $view,
        ]);
    }

    /** Crée un créneau dans mon agenda. */
    public function create(): void
    {
        $this->guard();
        $u   = Session::user();
        $uid = (int) ($u['id'] ?? 0);

        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $date     = trim($_POST['date'] ?? '');
        $time     = trim($_POST['time'] ?? '');
        $duration = max(5, min(600, (int) ($_POST['duration_min'] ?? 60)));
        $capacity = max(self::CAP_MIN, min(self::CAP_MAX, (int) ($_POST['capacity'] ?? 5)));

        // Lieu : en ligne (lien visio) ou en présentiel (adresse physique pour la carte).
        $mode     = ($_POST['mode'] ?? 'presentiel') === 'en_ligne' ? 'en_ligne' : 'presentiel';
        $location = trim($_POST['location'] ?? '');

        // Visibilité : public (listé) ou privé (caché, accessible par code).
        $visibility = ($_POST['visibility'] ?? 'public') === 'private' ? 'private' : 'public';

        // Afficher ou non la note globale des inscrits (case cochée par l'hôte).
        $showRatings = isset($_POST['show_ratings']) ? 1 : 0;

        // Délai minimum de réservation avant le début (en heures), défini par l'hôte.
        $minNotice = max(0, min(8760, (int) ($_POST['min_notice_hours'] ?? 0)));

        // Marquer le créneau « URGENT » dès la création (case cochée par l'hôte) :
        // il s'affichera alors dans le tableau de bord de tous les membres.
        $urgent = isset($_POST['urgent']) ? 1 : 0;

        // Validation de la date/heure (format attendu des champs date + time).
        $dt = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
        if ($title === '' || !$dt) {
            Session::set('agenda_error', 'Indique au moins un titre, une date et une heure valides.');
            redirect('agenda');
        }
        // La date/heure doit être DANS LE FUTUR : sinon le créneau serait
        // immédiatement « passé » (invisible dans la recherche et non réservable).
        if ($dt->getTimestamp() < time()) {
            Session::set('agenda_error', "La date et l'heure doivent être dans le futur (ce créneau serait déjà passé).");
            redirect('agenda');
        }

        $newId = Appointment::create([
            'owner_id'     => $uid,
            'owner_name'   => $u['name'] ?: ($u['email'] ?? ''),
            'title'        => $title,
            'description'  => $desc,
            'start_at'     => $dt->format('Y-m-d H:i:00'),
            'duration_min' => $duration,
            'capacity'     => $capacity,
            'mode'         => $mode,
            'location'     => $location,
            'visibility'   => $visibility,
            'show_booker_ratings' => $showRatings,
            'min_notice_hours'    => $minNotice,
        ]);

        // Alerte URGENTE (visible dans le tableau de bord de tous les membres).
        if ($urgent) {
            Appointment::setUrgent($newId, true);
        }

        // Photos de l'événement (une ou plusieurs) → uploads/agenda/.
        foreach (Upload::images('event_photos', 'agenda') as $fn) {
            AppointmentImage::add($newId, $fn);
        }

        // Participants : depuis la liste (membres « trouvables ») ET par CODE membre
        // (ceux qui t'ont donné leur code, même s'ils ne sont pas publics).
        $participantIds = array_map('intval', (array) ($_POST['participants'] ?? []));
        $pcodes = preg_split('/[\s,;]+/', strtoupper(trim((string) ($_POST['participant_codes'] ?? ''))), -1, PREG_SPLIT_NO_EMPTY);
        foreach (array_unique($pcodes) as $pc) {
            $cm = User::findByCode($pc);
            if ($cm) { $participantIds[] = (int) $cm['id']; }
        }
        $participantIds = array_values(array_unique($participantIds));

        $added = 0;
        $when  = $this->slotWhen($dt->format('Y-m-d H:i:00'));
        foreach ($participantIds as $mid) {
            if ($mid <= 0 || $mid === $uid) {
                continue; // ignore l'hôte et les valeurs vides
            }
            $member = User::findById($mid);
            if (!$member) {
                continue;
            }
            if (AppointmentBooking::add($newId, $mid, $member['name'] ?: ($member['email'] ?? 'Un membre'))) {
                $added++;
                Notification::add(
                    $mid,
                    ($u['name'] ?: ($u['email'] ?? 'L\'hôte')) . ' vous a inscrit·e sur le créneau « ' . $title . ' » ' . $when . '.',
                    '✅', 'agenda'
                );
            }
        }

        // Créneau PUBLIC → on prévient les autres membres (notif in-app + push/poll).
        if ($visibility === 'public') {
            $hostName = $u['name'] ?: ($u['email'] ?? 'Un membre');
            foreach (User::all() as $member) {
                $mid = (int) $member['id'];
                if ($mid <= 0 || $mid === $uid) {
                    continue; // pas l'hôte, pas les comptes techniques
                }
                Notification::add(
                    $mid,
                    $hostName . ' a proposé un nouveau créneau public : « ' . $title . ' » ' . $when . '.',
                    '📣', 'agenda'
                );
            }
        }

        $msg = $visibility === 'private'
            ? 'Créneau privé créé. Partage son code pour qu\'on puisse le trouver.'
            : 'Créneau ajouté à ton agenda.';
        if ($added > 0) {
            $msg .= ' ' . $added . ' participant' . ($added > 1 ? 's' : '') . ' ajouté' . ($added > 1 ? 's' : '') . '.';
        }
        Session::set('agenda_notice', $msg);
        redirect('agenda');
    }

    /**
     * L'hôte ajoute manuellement un membre inscrit à SON créneau,
     * même si le nombre de places est dépassé (passe outre la limite).
     */
    public function addMember(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $id   = (int) ($_POST['slot_id'] ?? 0);
        $mid  = (int) ($_POST['member_id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;

        if (!$appt || (int) $appt['owner_id'] !== $uid) {
            redirect('agenda'); // pas mon créneau
        }
        $this->blockIfEnded($appt);

        // Cible : par CODE (ex. A123) si fourni, sinon par id. Dans les deux cas,
        // findByCode/discoverable garantit que le membre a donné son accord.
        $code = strtoupper(trim($_POST['member_code'] ?? ''));
        if ($code !== '') {
            $member = User::findByCode($code);
            if (!$member) {
                Session::set('agenda_error', "Aucun membre avec le code « $code » (vérifie le code : 1 lettre + 3 chiffres).");
                redirect('agenda');
            }
        } else {
            $member = $mid ? User::findById($mid) : null;
            if (!$member) {
                Session::set('agenda_error', 'Choisis un membre dans la liste ou saisis son code.');
                redirect('agenda');
            }
            // Accord obligatoire : on ne peut ajouter qu'un membre « trouvable ».
            if ((int) ($member['discoverable'] ?? 0) !== 1) {
                Session::set('agenda_error', "Ce membre n'a pas autorisé qu'on l'ajoute à des activités.");
                redirect('agenda');
            }
        }
        if ((int) $member['id'] === $uid) {
            Session::set('agenda_error', "Tu es déjà l'hôte de ce créneau.");
            redirect('agenda');
        }

        // Pas de contrôle de capacité ici : l'hôte peut dépasser la limite.
        $memberName = $member['name'] ?: ($member['email'] ?? 'Un membre');
        $ok = AppointmentBooking::add($id, (int) $member['id'], $memberName);

        if ($ok) {
            // Confirmations aux deux parties (l'hôte qui inscrit + le membre inscrit).
            $hostName = Session::user()['name'] ?: (Session::user()['email'] ?? 'L\'hôte');
            $when     = $this->slotWhen($appt['start_at']);
            Notification::add(
                (int) $member['id'],
                $hostName . ' vous a inscrit·e sur le créneau « ' . $appt['title'] . ' » ' . $when . '.',
                '✅', 'agenda'
            );
            Notification::add(
                $uid,
                'Vous avez inscrit ' . $memberName . ' sur votre créneau « ' . $appt['title'] . ' » ' . $when . '.',
                '📅', 'agenda'
            );
        }
        Session::set('agenda_notice', $ok ? 'Membre ajouté au créneau.' : 'Ce membre est déjà inscrit à ce créneau.');
        redirect('agenda');
    }

    /** Modifie en direct le nombre de places d'un de mes créneaux (− / +). */
    public function updateCapacity(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $id   = (int) ($_POST['slot_id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if (!$appt || (int) $appt['owner_id'] !== $uid) {
            redirect('agenda');
        }
        $this->blockIfEnded($appt);
        $old = (int) $appt['capacity'];
        $cap = $old;
        $dir = $_POST['dir'] ?? '';
        if ($dir === 'inc') {
            $cap++;
        } elseif ($dir === 'dec') {
            $cap--;
        } else {
            redirect('agenda');
        }
        $newCap = max(1, min(99, $cap)); // borne identique à Appointment::updateCapacity
        if ($newCap !== $old) {
            AppointmentChange::add($id, 'places', $old . ' place' . ($old > 1 ? 's' : ''), $newCap . ' place' . ($newCap > 1 ? 's' : ''));
            Appointment::updateCapacity($id, $newCap);
        }
        redirect('agenda');
    }

    /**
     * Modifie le délai minimum de réservation d'un de mes créneaux, et journalise
     * le changement (ancien délai → nouveau) pour l'historique.
     */
    public function updateNotice(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $id   = (int) ($_POST['id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if (!$appt || (int) $appt['owner_id'] !== $uid) {
            redirect('agenda');
        }
        $this->blockIfEnded($appt);

        $oldHours = (int) ($appt['min_notice_hours'] ?? 0);
        $newHours = max(0, min(8760, (int) ($_POST['min_notice_hours'] ?? 0)));

        if ($oldHours !== $newHours) {
            AppointmentChange::add($id, 'delai', delai_texte($oldHours), delai_texte($newHours));
            Appointment::updateMinNotice($id, $newHours);
            Session::set('agenda_notice', 'Délai minimum de réservation mis à jour.');
        }
        redirect('agenda');
    }

    /**
     * Bascule la visibilité d'un de mes créneaux : public ⇄ privé.
     * Le code privé reste STABLE (cf. Appointment::setVisibility), même après
     * plusieurs allers-retours public/privé.
     */
    public function toggleVisibility(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $id   = (int) ($_POST['id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if ($appt && (int) $appt['owner_id'] === $uid) {
            $this->blockIfEnded($appt);
            $new = ($appt['visibility'] ?? 'public') === 'private' ? 'public' : 'private';
            Appointment::setVisibility($id, $new);
            Session::set('agenda_notice', $new === 'private'
                ? 'Créneau repassé en privé. Son code d\'accès est inchangé.'
                : 'Créneau rendu public (visible par tout le monde).');
        }
        redirect('agenda');
    }

    /**
     * Marque la présence (présent/absent) d'un participant à MON créneau.
     * Possible UNIQUEMENT une fois l'événement commencé : avant, on ne peut pas
     * encore dire si la personne était présente.
     */
    public function markPresence(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $id   = (int) ($_POST['slot_id'] ?? 0);
        $mid  = (int) ($_POST['member_id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;

        if (!$appt || (int) $appt['owner_id'] !== $uid) {
            redirect('agenda'); // pas mon créneau
        }
        // L'événement doit avoir eu lieu (commencé) pour pouvoir confirmer la présence.
        if (strtotime($appt['start_at']) > time()) {
            Session::set('agenda_error', "Tu pourras indiquer la présence une fois l'événement passé.");
            redirect('agenda');
        }

        // present = '1' (présent), '0' (absent) ou '' (remet à non renseigné).
        $raw     = (string) ($_POST['present'] ?? '');
        $present = $raw === '1' ? 1 : ($raw === '0' ? 0 : null);
        AppointmentBooking::setPresence($id, $mid, $present);
        redirect('agenda');
    }

    /** Bascule l'affichage de la note globale des inscrits (mon créneau uniquement). */
    public function toggleRatings(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $id   = (int) ($_POST['slot_id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if ($appt && (int) $appt['owner_id'] === $uid) {
            $this->blockIfEnded($appt);
            Appointment::setShowBookerRatings($id, (int) ($appt['show_booker_ratings'] ?? 1) === 1 ? 0 : 1);
        }
        redirect('agenda');
    }

    /** Supprime un de mes créneaux (et ses réservations). */
    public function delete(): void
    {
        $this->guard();
        $uid = (int) (Session::user()['id'] ?? 0);
        $id  = (int) ($_POST['id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if ($appt && (int) $appt['owner_id'] === $uid) {
            AppointmentBooking::deleteForAppointment($id);
            AppointmentChange::deleteForAppointment($id);
            // Photos : suppression base + fichiers disque.
            foreach (AppointmentImage::deleteForAppointment($id) as $fn) {
                Upload::delete($fn, 'agenda');
            }
            AppointmentRating::deleteForAppointment($id);
            Appointment::delete($id);
            Session::set('agenda_notice', 'Créneau supprimé.');
        }
        redirect('agenda');
    }

    /**
     * Note d'un ÉVÉNEMENT par un participant (1 à 5 étoiles + commentaire).
     * Réservé à un inscrit, et seulement une fois l'événement commencé.
     */
    public function rateEvent(): void
    {
        $this->guard();
        $uid     = (int) (Session::user()['id'] ?? 0);
        $id      = (int) ($_POST['id'] ?? 0);
        $stars   = (int) ($_POST['stars'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $appt    = $id ? Appointment::find($id) : null;

        if (!$appt) {
            redirect('agenda');
        }
        if (!AppointmentBooking::hasBooked($id, $uid)) {
            Session::set('agenda_error', 'Tu ne peux noter qu\'un événement où tu étais inscrit·e.');
            redirect('agenda');
        }
        if (strtotime($appt['start_at']) > time()) {
            Session::set('agenda_error', 'Tu pourras noter cet événement une fois qu\'il aura commencé.');
            redirect('agenda');
        }
        if ($stars < 1 || $stars > 5) {
            Session::set('agenda_error', 'Choisis une note de 1 à 5 étoiles.');
            redirect('agenda');
        }
        AppointmentRating::set($id, $uid, $stars, $comment);
        Session::set('agenda_notice', 'Merci, ta note a été enregistrée ★');
        redirect('agenda');
    }

    /** L'hôte protège / déprotège son événement (épargné par un effacement global). */
    public function toggleProtect(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $id   = (int) ($_POST['id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if ($appt && (int) $appt['owner_id'] === $uid) {
            $on = (int) ($appt['protected'] ?? 0) === 0;
            Appointment::setProtected($id, $on);
            Session::set('agenda_notice', $on
                ? '🔒 Événement protégé : il sera conservé même lors d\'un effacement global.'
                : '🔓 Protection retirée.');
        }
        redirect('agenda');
    }

    /** L'hôte marque / démarque son événement comme URGENT (alerte rouge pour tous). */
    public function toggleUrgent(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $id   = (int) ($_POST['id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if ($appt && (int) $appt['owner_id'] === $uid) {
            $on = (int) ($appt['urgent'] ?? 0) === 0;
            Appointment::setUrgent($id, $on);
            Session::set('agenda_notice', $on
                ? '🟥 Événement marqué URGENT : une alerte apparaît sur le tableau de bord de tous.'
                : 'Alerte URGENT retirée.');
        }
        redirect('agenda');
    }

    /**
     * PAGE DÉDIÉE d'un événement (détail complet, façon article).
     * Accessible si l'événement est PUBLIC ; sinon réservé au créateur, aux
     * inscrits et aux admins (les privés ne s'exposent pas publiquement).
     */
    public function event(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if (!$appt) {
            http_response_code(404);
            view('errors/404');
            return;
        }
        $uid      = (int) (Session::user()['id'] ?? 0);
        $isAdmin  = Session::isAdmin();
        $isOwner  = $uid && (int) $appt['owner_id'] === $uid;
        $iBooked  = $uid && AppointmentBooking::hasBooked($id, $uid);
        $isPublic = ($appt['visibility'] ?? 'public') === 'public';

        if (!$isPublic && !$isOwner && !$iBooked && !$isAdmin) {
            Session::set('agenda_error', 'Cet événement est privé : il est accessible uniquement par son code.');
            redirect('agenda');
        }

        $bookers          = AppointmentBooking::forAppointment($id);
        $appt['booked']   = count($bookers);
        $appt['bookers']  = $bookers;
        $appt['changes']  = AppointmentChange::forAppointment($id);

        view('agenda/event', [
            'user'       => Session::user(),
            'a'          => $appt,
            'photos'     => array_column(AppointmentImage::forAppointment($id), 'filename'),
            'evtRating'  => AppointmentRating::summary($id),
            'hostRating' => Rating::summaryFor([(int) $appt['owner_id']])[(int) $appt['owner_id']] ?? null,
            'myRating'   => $uid ? (int) (AppointmentRating::mine($id, $uid)['stars'] ?? 0) : 0,
            'isOwner'    => $isOwner,
            'iBooked'    => $iBooked,
            'showBookers' => (int) ($appt['show_booker_ratings'] ?? 0) === 1,
        ]);
    }

    /**
     * Télécharge un événement au format .ics (calendrier universel : Google
     * Agenda, Apple Calendrier, Outlook…). Même contrôle d'accès que la fiche.
     */
    public function ics(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if (!$appt) {
            http_response_code(404);
            view('errors/404');
            return;
        }
        $uid      = (int) (Session::user()['id'] ?? 0);
        $isOwner  = $uid && (int) $appt['owner_id'] === $uid;
        $iBooked  = $uid && AppointmentBooking::hasBooked($id, $uid);
        $isPublic = ($appt['visibility'] ?? 'public') === 'public';
        if (!$isPublic && !$isOwner && !$iBooked && !Session::isAdmin()) {
            redirect('agenda');
        }

        $start = strtotime($appt['start_at']);
        $end   = $start + (int) $appt['duration_min'] * 60;
        $esc = static function (string $s): string {
            // Échappement iCalendar : \ ; , et sauts de ligne.
            return str_replace(["\\", ";", ",", "\r\n", "\n"], ["\\\\", "\\;", "\\,", "\\n", "\\n"], $s);
        };
        $location = rdv_lieu_texte($appt['mode'] ?? 'presentiel', (string) ($appt['location'] ?? ''));
        $desc     = trim((string) ($appt['description'] ?? ''));
        $host     = $_SERVER['HTTP_HOST'] ?? 'rpn';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//RPN//Agenda//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:rpn-event-' . $id . '@' . $host,
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'DTSTART;TZID=Europe/Paris:' . date('Ymd\THis', $start),
            'DTEND;TZID=Europe/Paris:' . date('Ymd\THis', $end),
            'SUMMARY:' . $esc((string) $appt['title']),
            'LOCATION:' . $esc($location),
            'DESCRIPTION:' . $esc($desc),
            'END:VEVENT',
            'END:VCALENDAR',
        ];
        $body = implode("\r\n", $lines) . "\r\n";

        if (!headers_sent()) {
            header('Content-Type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename="evenement-' . $id . '.ics"');
        }
        echo $body;
    }

    /**
     * Modifie les infos principales d'un de mes créneaux (titre, date/heure,
     * durée, description). Si l'horaire change, prévient les inscrits.
     */
    public function update(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $id   = (int) ($_POST['id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if (!$appt || (int) $appt['owner_id'] !== $uid) {
            redirect('agenda'); // pas mon créneau
        }
        $this->blockIfEnded($appt);

        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $date     = trim($_POST['date'] ?? '');
        $time     = trim($_POST['time'] ?? '');
        $duration = max(5, min(600, (int) ($_POST['duration_min'] ?? 60)));

        $dt = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
        if ($title === '' || !$dt) {
            Session::set('agenda_error', 'Indique un titre, une date et une heure valides.');
            redirect('agenda');
        }

        $newStart = $dt->format('Y-m-d H:i:00');
        $oldStart = date('Y-m-d H:i:00', strtotime($appt['start_at']));

        // Journalise chaque champ modifié (historique : ancienne valeur barrée).
        if ($title !== (string) $appt['title']) {
            AppointmentChange::add($id, 'title', (string) $appt['title'], $title);
        }
        if ($newStart !== $oldStart) {
            AppointmentChange::add($id, 'horaire', rdv_horaire_texte($oldStart), rdv_horaire_texte($newStart));
        }
        if ($duration !== (int) $appt['duration_min']) {
            AppointmentChange::add($id, 'duration', (int) $appt['duration_min'] . ' min', $duration . ' min');
        }

        Appointment::updateDetails($id, [
            'title'        => $title,
            'description'  => $desc,
            'start_at'     => $newStart,
            'duration_min' => $duration,
        ]);

        // Si la date/heure a bougé, on prévient les personnes inscrites.
        if ($newStart !== $oldStart) {
            $when = $this->slotWhen($newStart);
            foreach (AppointmentBooking::forAppointment($id) as $bk) {
                Notification::add(
                    (int) $bk['user_id'],
                    'Le créneau « ' . $title . ' » a été déplacé : c\'est désormais ' . $when . '.',
                    '📅', 'agenda'
                );
            }
        }

        Session::set('agenda_notice', 'Rendez-vous modifié.');
        redirect('agenda');
    }

    /** Modifie le lieu d'un de mes créneaux et journalise le changement. */
    public function updateLocation(): void
    {
        $this->guard();
        $uid  = (int) (Session::user()['id'] ?? 0);
        $id   = (int) ($_POST['id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;
        if (!$appt || (int) $appt['owner_id'] !== $uid) {
            redirect('agenda');
        }
        $this->blockIfEnded($appt);

        $newMode = ($_POST['mode'] ?? 'presentiel') === 'en_ligne' ? 'en_ligne' : 'presentiel';
        $newLoc  = trim($_POST['location'] ?? '');

        $oldText = rdv_lieu_texte($appt['mode'] ?? 'presentiel', (string) ($appt['location'] ?? ''));
        $newText = rdv_lieu_texte($newMode, $newLoc);

        if ($oldText !== $newText) {
            AppointmentChange::add($id, 'location', $oldText, $newText);
            Appointment::updateLocation($id, $newMode, $newLoc);
            Session::set('agenda_notice', 'Lieu du rendez-vous mis à jour.');
        }
        redirect('agenda');
    }

    /** Réserve une place sur le créneau d'un autre membre. */
    public function book(): void
    {
        $this->guard();
        $u   = Session::user();
        $uid = (int) ($u['id'] ?? 0);
        $id  = (int) ($_POST['id'] ?? 0);
        $appt = $id ? Appointment::find($id) : null;

        if (!$appt) {
            redirect('agenda');
        }
        if ((int) $appt['owner_id'] === $uid) {
            Session::set('agenda_error', 'Tu ne peux pas réserver ton propre créneau.');
            redirect('agenda');
        }
        if (strtotime($appt['start_at']) < time()) {
            Session::set('agenda_error', 'Ce créneau est déjà passé.');
            redirect('agenda');
        }
        // Délai minimum de réservation imposé par l'hôte (ferme N h avant le début).
        $minNotice = (int) ($appt['min_notice_hours'] ?? 0);
        if ($minNotice > 0 && strtotime($appt['start_at']) - time() < $minNotice * 3600) {
            Session::set('agenda_error', 'Les réservations pour ce créneau ferment ' . delai_texte($minNotice) . ' avant le début.');
            redirect('agenda');
        }
        if (AppointmentBooking::hasBooked($id, $uid)) {
            Session::set('agenda_error', 'Tu as déjà réservé ce créneau.');
            redirect('agenda');
        }
        if (AppointmentBooking::countFor($id) >= (int) $appt['capacity']) {
            Session::set('agenda_error', 'Ce créneau est complet.');
            redirect('agenda');
        }

        $bookerName = $u['name'] ?: ($u['email'] ?? 'Un membre');
        AppointmentBooking::add($id, $uid, $bookerName);

        // Confirmations de réservation aux DEUX parties.
        $when  = $this->slotWhen($appt['start_at']);
        $title = $appt['title'];
        Notification::add(
            $uid,
            'Vous avez réservé une place sur « ' . $title . ' » de '
                . ($appt['owner_name'] ?: 'un membre') . ' ' . $when . '.',
            '✅', 'agenda'
        );
        Notification::add(
            (int) $appt['owner_id'],
            $bookerName . ' vient de réserver une place sur votre créneau « ' . $title . ' » ' . $when . '.',
            '📅', 'agenda'
        );

        Session::set('agenda_notice', 'Place réservée ✔ — une confirmation a été envoyée.');
        redirect('agenda');
    }

    /** Note un hôte (1 à 5 étoiles) — seulement si on a réservé chez lui. */
    public function rate(): void
    {
        $this->guard();
        $uid     = (int) (Session::user()['id'] ?? 0);
        $ownerId = (int) ($_POST['owner_id'] ?? 0);
        $stars   = (int) ($_POST['stars'] ?? 0);

        if ($ownerId <= 0 || $ownerId === $uid || $stars < 1 || $stars > 5) {
            redirect('agenda');
        }
        if (!AppointmentBooking::userBookedFromOwner($uid, $ownerId)) {
            Session::set('agenda_error', 'Tu ne peux noter qu\'un hôte chez qui tu as réservé.');
            redirect('agenda');
        }
        Rating::set($ownerId, $uid, $stars);
        Session::set('agenda_notice', 'Merci pour ta note ★');
        redirect('agenda');
    }

    /** Annule MA réservation sur un créneau. */
    public function cancel(): void
    {
        $this->guard();
        $u    = Session::user();
        $uid  = (int) ($u['id'] ?? 0);
        $id   = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $appt   = Appointment::find($id);
            $booked = $appt && AppointmentBooking::hasBooked($id, $uid);
            AppointmentBooking::cancel($id, $uid);

            // Prévient l'hôte que cette réservation a été annulée.
            if ($appt && $booked && (int) $appt['owner_id'] !== $uid) {
                Notification::add(
                    (int) $appt['owner_id'],
                    ($u['name'] ?: ($u['email'] ?? 'Un membre'))
                        . ' a annulé sa réservation sur votre créneau « ' . $appt['title'] . ' » '
                        . $this->slotWhen($appt['start_at']) . '.',
                    '❌', 'agenda'
                );
            }
            Session::set('agenda_notice', 'Réservation annulée.');
        }
        redirect('agenda');
    }
}

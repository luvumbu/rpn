<?php
/**
 * CONTRÔLEUR DirectoryController
 * Annuaire / recherche de professeurs par matière (domaine).
 * Seuls les membres ayant activé « être trouvable » apparaissent.
 */
class DirectoryController
{
    /** Autocomplétion : suggestions de membres VISIBLES (JSON). */
    public function suggest(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Session::has('user')) {
            echo '[]';
            return;
        }
        $q    = trim((string) ($_GET['q'] ?? ''));
        $meId = (int) (Session::user()['id'] ?? 0);
        if (mb_strlen($q) < 2) {
            echo '[]';
            return;
        }
        $out = [];
        foreach (User::searchProfiles($q, Session::isAdmin()) as $u) { // admin : tout le monde ; sinon trouvables
            $uid = (int) $u['id'];
            if ($uid === $meId) {
                continue;
            }
            $doms = User::domainsToList($u['domains'] ?? '');
            $sub  = $doms[0] ?? ($u['address'] ?: ($u['default_city'] ?? ''));
            $out[] = [
                'id'     => $uid,
                'name'   => $u['name'] ?: 'Membre',
                'avatar' => avatar_url($u['picture'] ?? null, $u['name'] ?? ''),
                'sub'    => $sub,
            ];
            if (count($out) >= 6) {
                break;
            }
        }
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
    }

    public function index(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        $q       = trim((string) ($_GET['q'] ?? ''));
        $meId    = (int) (Session::user()['id'] ?? 0);
        // Recherche LARGE : nom, matière, pays, ville, adresse, code.
        // Un admin recherche TOUT LE MONDE ; un membre ne voit que les profils « trouvables ».
        $results = User::searchProfiles($q, Session::isAdmin());

        // Mes coordonnées (pour calculer la distance « toi ↔ lui »).
        $me     = $meId > 0 ? User::findById($meId) : null;
        $myLat  = $me['default_lat'] ?? null;
        $myLng  = $me['default_lng'] ?? null;

        $teachers = [];
        foreach ($results as $u) {
            $uid = (int) $u['id'];
            if ($uid === $meId) {
                continue; // on ne se liste pas soi-même
            }
            $dist = geo_distance_km($myLat, $myLng, $u['default_lat'] ?? null, $u['default_lng'] ?? null);
            $teachers[] = [
                'id'        => $uid,
                'name'      => $u['name'] ?: 'Membre',
                'picture'   => $u['picture'] ?? null,
                'domains'   => User::domainsToList($u['domains'] ?? ''),
                'countries' => Countries::toList($u['countries'] ?? ''),
                'code'      => $u['member_code'] ?? '',
                'city'      => $u['address'] ?: ($u['default_city'] ?? ''),
                'level'     => Level::info($uid),
                'distance'  => $dist === null ? null : (int) round($dist),
            ];
        }

        // Tri : les plus proches d'abord ; ceux sans distance connue à la fin.
        usort($teachers, static function ($a, $b) {
            $da = $a['distance']; $db = $b['distance'];
            if ($da === null && $db === null) { return strcasecmp($a['name'], $b['name']); }
            if ($da === null) { return 1; }
            if ($db === null) { return -1; }
            return $da <=> $db;
        });

        view('directory', [
            'user'       => Session::user(),
            'q'          => $q,
            'teachers'   => $teachers,
            'allDomains' => User::teacherDomains(),
            'hasMyLoc'   => ($myLat !== null && $myLng !== null),
        ]);
    }
}

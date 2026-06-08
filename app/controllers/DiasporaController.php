<?php
/**
 * CONTRÔLEUR DiasporaController — carte interactive de la diaspora afro-descendante.
 * Affiche les grandes régions de la diaspora + les membres trouvables géolocalisés.
 */
class DiasporaController
{
    public function index(): void
    {
        if (!Session::has('user')) {
            redirect('');
        }
        // Membres trouvables ayant renseigné une ville (géolocalisée).
        $rows = Database::pdo()->query(
            "SELECT name, default_city, default_lat, default_lng
             FROM users
             WHERE discoverable = 1 AND default_lat IS NOT NULL AND default_lng IS NOT NULL"
        )->fetchAll();

        $members = [];
        foreach ($rows as $u) {
            $members[] = [
                'name' => $u['name'] ?: 'Membre',
                'city' => $u['default_city'] ?: '',
                'lat'  => (float) $u['default_lat'],
                'lng'  => (float) $u['default_lng'],
            ];
        }

        view('diaspora', [
            'user'    => Session::user(),
            'members' => $members,
        ]);
    }
}

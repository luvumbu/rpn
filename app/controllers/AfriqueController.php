<?php
/**
 * CONTRÔLEUR Afrique
 * Vitrine des 54 pays d'Afrique avec leur drapeau. Page PUBLIQUE (vitrine
 * culturelle, accessible sans connexion). Les drapeaux sont servis par
 * flagcdn.com (images PNG par code ISO) — rendu fiable sur tous les appareils.
 */
class AfriqueController
{
    public function index(): void
    {
        // 54 pays d'Afrique (source unique : classe Africa).
        $pays = Africa::countries();

        view('afrique/index', [
            'user' => Session::user(),
            'pays' => $pays,
        ]);
    }
}

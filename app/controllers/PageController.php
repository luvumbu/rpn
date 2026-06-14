<?php
/**
 * CONTRÔLEUR Page — pages d'information publiques (mentions légales, confidentialité).
 */
class PageController
{
    /** Mentions légales & politique de confidentialité (RGPD). */
    public function legal(): void
    {
        view('legal', [
            'user' => Session::has('user') ? Session::user() : null,
        ]);
    }
}

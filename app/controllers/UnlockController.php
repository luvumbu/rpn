<?php
/**
 * CONTRÔLEUR Unlock
 * Page secrète de déblocage des IP à distance, accessible uniquement avec la
 * bonne clé secrète :  .../rpm/unlock?key=LA_CLE_SECRETE
 * Sans la clé (ou mauvaise clé) → faux « 404 » pour rester invisible.
 */
class UnlockController
{
    public function index(): void
    {
        // Vérification de la clé secrète
        if (!hash_equals(SECRET_KEY, (string) ($_GET['key'] ?? ''))) {
            http_response_code(404);
            exit('<!DOCTYPE html><title>404 Not Found</title><h1>Not Found</h1>'
                . '<p>The requested URL was not found on this server.</p>');
        }

        $msg = '';
        if (($_GET['all'] ?? '') === '1') {
            LoginGuard::resetAll();
            $msg = '✅ Tous les blocages ont été annulés.';
        } elseif (!empty($_GET['ip'])) {
            LoginGuard::reset($_GET['ip']);
            $msg = '✅ IP ' . htmlspecialchars($_GET['ip']) . ' débloquée.';
        }

        view('unlock', [
            'key'     => SECRET_KEY,
            'msg'     => $msg,
            'blocked' => LoginGuard::blockedList(),
        ]);
    }
}

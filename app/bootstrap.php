<?php
/**
 * Démarrage commun à toutes les pages (front controller + google-callback) :
 *  - charge la configuration et les fonctions utilitaires
 *  - définit le chemin de base de l'application (BASE_PATH)
 *  - met en place l'autoload des classes (core / controllers / models)
 *  - démarre la session
 */

require __DIR__ . '/../config/config.php';
require __DIR__ . '/core/helpers.php';

// Fuseau horaire FIXE : heure de Paris pour TOUTE l'application (création et
// affichage des événements, comptes à rebours, « aujourd'hui »…), quel que soit
// le réglage du serveur d'hébergement.
date_default_timezone_set('Europe/Paris');

// Chemin de base de l'app (ex: "/rpm") — déduit du script réellement appelé.
// Sert à construire TOUTES les URLs internes via url() / redirect().
if (!defined('BASE_PATH')) {
    $__base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    define('BASE_PATH', rtrim($__base, '/'));
}

// Racine du projet sur le disque (pour les chemins de fichiers, ex: uploads/).
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Autoload : trouve automatiquement les classes sans require manuel.
spl_autoload_register(function (string $class) {
    foreach (['core/', 'controllers/', 'models/'] as $dir) {
        $file = __DIR__ . '/' . $dir . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

Session::start();

// Heartbeat « en ligne » : met à jour last_seen (au plus une fois toutes les 30 s)
// pour savoir qui est connecté (messagerie temps réel adaptative : 10 s si l'autre
// est en ligne, sinon 60 s).
if (Session::has('user')) {
    $__uid = (int) (Session::user()['id'] ?? 0);
    if ($__uid > 0 && (!isset($_SESSION['_seen']) || time() - (int) $_SESSION['_seen'] > 30)) {
        $_SESSION['_seen'] = time();
        try { User::touchSeen($__uid); } catch (\Throwable $e) { /* base indisponible : on ignore */ }
    }
}

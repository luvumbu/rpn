<?php
/**
 * Front controller — UNIQUE point d'entrée de l'application.
 * Toutes les requêtes passent ici (voir .htaccess), puis sont aiguillées
 * vers le bon contrôleur par le routeur (config/routes.php).
 */
require __DIR__ . '/app/bootstrap.php';

/** @var Router $router */
$router = require __DIR__ . '/config/routes.php';

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/'
);

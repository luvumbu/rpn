<?php
/**
 * Exception volontairement conservée à la racine.
 * L'URL de redirection « .../rpm/google-callback.php » est enregistrée dans
 * Google Cloud Console : on la garde telle quelle pour ne RIEN changer côté Google.
 * Ce fichier se contente de rejouer la route « google/callback » du routeur.
 */
require __DIR__ . '/app/bootstrap.php';

/** @var Router $router */
$router = require __DIR__ . '/config/routes.php';

$router->run('GET', 'google/callback');

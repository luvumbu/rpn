<?php
/**
 * Table de routage de l'application.
 * Une seule porte d'entrée : index.php. Chaque URL « propre » est associée
 * ici à un contrôleur et une action. Le fichier renvoie le routeur prêt à l'emploi.
 *
 *   url('')                → page d'accueil
 *   url('admin/members')   → /rpm/admin/members
 */

$router = new Router();

// --- Espace public / membres -------------------------------------------------
$router->get('',                'AuthController',      'showLogin');  // accueil (connexion)
$router->post('login',          'AuthController',      'login');      // connexion classique (email + mot de passe)
$router->get('register',        'AuthController',      'showRegister');// formulaire d'inscription (sans Google)
$router->post('register',       'AuthController',      'register');   // création d'un compte membre
$router->get('dashboard',       'DashboardController', 'index');      // tableau de bord membre
$router->get('afrique',         'AfriqueController',   'index');      // vitrine des drapeaux d'Afrique (public)
$router->post('profile/discoverable', 'DashboardController', 'toggleDiscoverable'); // consentement « trouvable »
$router->post('profile/theme',        'DashboardController', 'saveTheme');          // thème personnel du membre
$router->post('profile/photo',        'DashboardController', 'savePhoto');          // photo de profil (ajout/changement/retrait)
$router->post('profile/roles',        'DashboardController', 'saveRoles');          // rôle(s) / domaine(s) du membre
$router->post('profile/countries',    'DashboardController', 'saveCountries');      // pays d'origine du membre
$router->get('geo/suggest',           'GeoController',        'suggest');           // autocomplétion de villes/adresses (JSON)
$router->get('profile/export',        'DashboardController', 'exportMine');         // exporter MON projet (.zip)
$router->get('profile/import',        'DashboardController', 'importPage');         // page d'import (formulaire fiable)
$router->post('profile/import',       'DashboardController', 'importMine');         // importer (recréé en brouillon, à mon nom)
$router->post('profile/dismiss_urgent','DashboardController', 'dismissUrgent');      // fermer une alerte urgente
$router->get('niveaux',         'DashboardController',  'levels');      // page des niveaux (Héritier → Sage)
$router->get('professeurs',     'DirectoryController', 'index');       // recherche de professeurs par matière
$router->get('professeurs/suggest', 'DirectoryController', 'suggest');  // autocomplétion de membres visibles (JSON)
$router->get('diaspora',        'DiasporaController',  'index');       // carte interactive de la diaspora
$router->get('assistant',       'AssistantController', 'index');       // assistant Sankofa (algorithme)
$router->get('messages',        'MessageController',   'index');       // messagerie : boîte de réception
$router->get('messages/thread', 'MessageController',   'thread');      // conversation avec un membre (?with=id)
$router->get('messages/poll',   'MessageController',   'poll');        // sondage temps réel (JSON) d'une conversation
$router->post('messages/send',  'MessageController',   'send');        // envoyer un message
$router->get('logout',          'AuthController',      'logout');     // déconnexion
$router->get('google/callback', 'AuthController',      'callback');   // retour Google OAuth

// --- Articles (lecture + écriture, pour tout membre connecté) -----------------
$router->get('articles',         'ArticleController', 'index');   // liste
$router->get('article',          'ArticleController', 'show');    // détail : ?id=5
$router->get('articles/new',     'ArticleController', 'create');  // formulaire création
$router->get('articles/edit',    'ArticleController', 'edit');    // formulaire édition : ?id=5
$router->post('articles/preview','ArticleController', 'preview'); // aperçu en direct (AJAX)
$router->post('articles/save',   'ArticleController', 'store');   // enregistre (création/édition)
$router->post('articles/toggle', 'ArticleController', 'toggle');  // publier ⇄ brouillon (auteur ou admin)
$router->post('articles/review', 'ArticleController', 'review');  // avis membre (note + commentaire)
$router->post('articles/comment',        'ArticleController', 'comment');       // message de discussion
$router->post('articles/comment_delete', 'ArticleController', 'commentDelete'); // suppression d'un message
$router->post('articles/comment_report', 'ArticleController', 'commentReport'); // signaler un message
$router->post('articles/unlock',         'ArticleController', 'unlock');       // déverrouiller un article protégé par mot de passe
$router->post('articles/report',         'ArticleController', 'report');       // signaler un ARTICLE
$router->post('articles/clear_flags',    'ArticleController', 'clearFlags');    // admin : réinitialiser les signalements
$router->post('articles/delete', 'ArticleController', 'delete');  // suppression (auteur ou admin)

// --- Questionnaires interactifs (quiz noté, pour tout membre connecté) -------
$router->get('quiz',          'QuizController', 'index');   // liste
$router->get('quiz/new',      'QuizController', 'create');  // formulaire création
$router->get('quiz/edit',     'QuizController', 'edit');    // formulaire édition : ?id=5
$router->get('quiz/show',     'QuizController', 'show');    // répondre / voir résultats : ?id=5
$router->post('quiz/save',    'QuizController', 'store');   // enregistre (création/édition)
$router->post('quiz/submit',  'QuizController', 'submit');  // un membre envoie ses réponses
$router->post('quiz/toggle',  'QuizController', 'toggle');  // publier ⇄ brouillon
$router->post('quiz/delete',  'QuizController', 'delete');  // suppression (auteur ou admin)

// --- Agenda / rendez-vous entre membres -------------------------------------
$router->get('agenda',         'AgendaController', 'index');   // mon agenda + créneaux à réserver
$router->get('agenda/event',   'AgendaController', 'event');   // page dédiée d'un événement (détail)
$router->get('agenda/ics',     'AgendaController', 'ics');     // télécharge l'événement (.ics) pour tout agenda
$router->get('agenda/global',  'AgendaController', 'globalAgenda'); // vue d'ensemble de TOUS les RDV
$router->post('agenda/default_city', 'AgendaController', 'saveDefaultCity'); // enregistrer ma ville par défaut
$router->post('agenda/create', 'AgendaController', 'create');  // proposer un créneau
$router->post('agenda/delete', 'AgendaController', 'delete');  // supprimer mon créneau
$router->post('agenda/update', 'AgendaController', 'update');  // modifier mon créneau (titre, date, durée…)
$router->post('agenda/location', 'AgendaController', 'updateLocation'); // modifier le lieu (avec historique)
$router->post('agenda/add_member', 'AgendaController', 'addMember');    // l'hôte ajoute un membre (hors limite)
$router->post('agenda/capacity',  'AgendaController', 'updateCapacity'); // modifier le nombre de places (− / +)
$router->post('agenda/ratings_toggle', 'AgendaController', 'toggleRatings'); // afficher/masquer la note des inscrits
$router->post('agenda/visibility', 'AgendaController', 'toggleVisibility'); // basculer public ⇄ privé (code stable)
$router->post('agenda/presence',  'AgendaController', 'markPresence'); // marquer la présence d'un participant (après l'événement)
$router->post('agenda/notice',    'AgendaController', 'updateNotice'); // modifier le délai min. de réservation (avec historique)
$router->post('agenda/book',   'AgendaController', 'book');    // réserver une place
$router->post('agenda/cancel', 'AgendaController', 'cancel');  // annuler ma réservation
$router->post('agenda/rate',   'AgendaController', 'rate');    // noter un hôte (1-5 étoiles)
$router->post('agenda/rate_event', 'AgendaController', 'rateEvent'); // noter un ÉVÉNEMENT (participant)
$router->post('agenda/protect',    'AgendaController', 'toggleProtect'); // protéger/déprotéger un événement
$router->post('agenda/urgent',     'AgendaController', 'toggleUrgent');  // marquer/démarquer URGENT

// --- Notifications in-app (membre connecté) ---------------------------------
$router->get('notifications',        'NotificationController', 'index'); // liste (marque comme lues)
$router->get('notifications/poll',   'NotificationController', 'poll');  // sondage temps réel (JSON, ne marque pas lu)
$router->post('notifications/clear', 'NotificationController', 'clear'); // vider la liste

// --- API JSON (écriture d'articles à distance, protégée par clé API) --------
$router->get('api/ping',     'ApiController', 'ping');          // test
$router->post('api/article', 'ApiController', 'createArticle'); // crée un article

// --- Page secrète de déblocage des IP ---------------------------------------
$router->get('unlock',          'UnlockController',    'index');

// --- Espace administrateur (pages) -------------------------------------------
$router->get('admin/login',     'AdminController', 'showLogin');
$router->post('admin/login',    'AdminController', 'login');
$router->get('admin/dashboard', 'AdminController', 'dashboard');
$router->get('admin/members',   'AdminController', 'members');
$router->get('admin/security',  'AdminController', 'security');
$router->get('admin/settings',  'AdminController', 'settings');
$router->post('admin/settings', 'AdminController', 'saveSettings');
$router->get('admin/favicon',   'AdminController', 'favicon');         // générateur de favicon
$router->post('admin/favicon',  'AdminController', 'saveFavicon');     // génère et applique le favicon
$router->get('admin/style',     'AdminController', 'globalStyle');     // panneau de style global
$router->post('admin/style',    'AdminController', 'saveGlobalStyle'); // enregistrement
$router->get('admin/logout',    'AdminController', 'logout');

// --- Espace administrateur : modération (vue d'ensemble des articles) --------
$router->get('admin/articles', 'AdminArticleController', 'index');

// Export / import des articles + réattribution à un utilisateur
$router->get('admin/articles/export',  'AdminArticleController', 'export'); // télécharge un .zip
$router->post('admin/articles/import', 'AdminArticleController', 'import'); // recrée depuis un .zip (→ admin)
$router->post('admin/articles/assign', 'AdminArticleController', 'assign'); // attribue un article à un membre
$router->post('admin/articles/protect', 'AdminArticleController', 'protect'); // protège/déprotège (épargné par « tout effacer »)
$router->post('admin/articles/clear_flags', 'AdminArticleController', 'clearFlags'); // réinitialise les signalements d'un article
$router->post('admin/articles/announce', 'AdminArticleController', 'announce'); // passe en « annonce » (mis en avant sur l'accueil)

// Modules de style global des articles (taille du texte, police…)
$router->get('admin/articles/style',  'AdminArticleController', 'style');
$router->post('admin/articles/style', 'AdminArticleController', 'saveStyle');

// --- Espace administrateur (actions, formulaires POST) -----------------------
$router->post('admin/promote',        'AdminController', 'promote');
$router->post('admin/demote',         'AdminController', 'demote');
$router->post('admin/block',          'AdminController', 'block');
$router->post('admin/unblock',        'AdminController', 'unblock');
$router->post('admin/delete',         'AdminController', 'delete');
$router->post('admin/ip_unblock',     'AdminController', 'ipUnblock');
$router->post('admin/ip_unblock_all', 'AdminController', 'ipUnblockAll');

// --- Zone de danger : tout effacer (super-admin) ----------------------------
$router->post('admin/wipe_agenda',   'AdminController', 'wipeAgenda');   // efface tout l'agenda
$router->post('admin/wipe_articles', 'AdminController', 'wipeArticles'); // efface tous les articles

return $router;

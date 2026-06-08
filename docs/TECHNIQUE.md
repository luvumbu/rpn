# RPN — Documentation technique

> Application web communautaire (articles, agenda de rendez-vous entre membres,
> avis & discussions, thèmes personnalisables) écrite en **PHP natif (sans
> framework)** sur **MySQL**, selon une architecture **MVC** avec front
> controller.

---

## 1. Vue d'ensemble

| Aspect | Détail |
|---|---|
| Langage | PHP 8+ (pas de Composer, pas de dépendance serveur) |
| Base de données | MySQL / MariaDB via **PDO** (requêtes préparées) |
| Architecture | MVC maison + front controller + routeur |
| Front-end | HTML/CSS/JS natif (aucun build), variables CSS pour le thème |
| Dépendances externes | Google Fonts, Google OAuth, Google Maps (embed sans clé), Leaflet + OpenStreetMap, Nominatim (géocodage) |
| Sessions | `$_SESSION` (login, identifiants BDD en ligne, messages flash) |
| Stockage fichiers | `uploads/` (images d'articles, documents) |
| Réglages | `storage/settings.json` (clé/valeur) |

Domaines fonctionnels :
1. **Authentification** (Google OAuth pour les membres, identifiant/mot de passe pour les admins) + rôles + anti-bruteforce.
2. **Articles** : rédaction riche, 16 mises en page, images (couverture + galerie), pièces jointes (PDF avec visionneuse), aperçu en direct, brouillon/public, **avis** (note ★ + commentaire) et **discussion** (fil de messages avec signalement).
3. **Agenda** : créneaux de rendez-vous entre membres (public/privé par code, en ligne/présentiel avec carte, places modifiables, recherche par proximité, notation des hôtes).
4. **Apparence** : thèmes prédéfinis + thème personnalisé (sélecteurs de couleur), favicon, polish UX global.
5. **Administration** : modération des membres, des articles, sécurité, réglages.

---

## 2. Prérequis & démarrage

### Local (XAMPP)
- Apache + MySQL démarrés, `mod_rewrite` activé.
- Projet dans `C:\xampp\htdocs\rpm` → accessible sur `http://localhost/rpm/`.
- La base `rpm` et **toutes les tables se créent automatiquement** au premier accès (voir `Database::pdo()`), ou via l'import de `database/database.sql`.

### En ligne
- Déposer les fichiers, garder la structure.
- `BASE_PATH` est déduit automatiquement du script appelé (voir `bootstrap.php`).
- Les identifiants MySQL sont saisis par l'admin **au login** et persistés dans `storage/db.php` (jamais en clair dans le dépôt) — voir `Database::persist()`.

---

## 3. Architecture & cycle d'une requête

```
Navigateur
   │  (URL propre, ex: /rpm/article?id=5)
   ▼
.htaccess (mod_rewrite)  ──►  index.php (front controller unique)
   ▼
app/bootstrap.php   (config, helpers, BASE_PATH, autoload, session)
   ▼
config/routes.php   (Router : table méthode+chemin → Controller@action)
   ▼
Controller::action()   ──►  Model (PDO)        ──►  MySQL
                        └►  view('nom', $data)  ──►  app/views/*.view.php  ──►  HTML
```

- **Front controller** : `index.php` charge `bootstrap.php`, récupère le `Router` de `config/routes.php`, puis `dispatch($method, $uri)`.
- **`.htaccess`** (racine) : sert directement les fichiers réels (images, `favicon.svg`, `index.php`…) et redirige tout le reste vers `index.php`. `Options -Indexes`.
- **`app/.htaccess`** : interdit l'accès direct au dossier `app/`.
- **`uploads/.htaccess`** : bloque l'exécution de scripts (php, phtml, cgi…) et le listing — s'applique aussi aux sous-dossiers (`uploads/articles/files/`).

---

## 4. Arborescence

```
rpm/
├── index.php                 Front controller
├── google-callback.php       Cible de redirection Google OAuth
├── favicon.svg / favicon.png Favicon (SVG + repli PNG)
├── .htaccess                 Réécriture d'URL
├── config/
│   ├── config.php            Secrets (Google, BDD défaut, ADMIN_EMAILS, SECRET_KEY)
│   └── routes.php            Table de routage
├── app/
│   ├── bootstrap.php         Démarrage commun (autoload, session, BASE_PATH)
│   ├── core/                 Briques techniques (voir §8)
│   ├── controllers/          Contrôleurs (voir §9)
│   ├── models/               Modèles / accès BDD (voir §10)
│   └── views/                Gabarits *.view.php + partials _*.php (voir §11)
├── database/
│   └── database.sql          Schéma SQL de référence
├── storage/
│   ├── settings.json         Réglages éditables (thème, textes, sécurité…)
│   └── db.php                Identifiants BDD persistés (en ligne, généré)
├── uploads/
│   └── articles/             Images d'articles
│       └── files/            Pièces jointes (PDF, docs)
└── docs/                     Documentation
```

---

## 5. Configuration

### `config/config.php` (⚠️ ne pas publier — contient des secrets)
Constantes définies : `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` (auto http/https), `GOOGLE_AUTH_URL` / `TOKEN_URL` / `USERINFO_URL`, `IS_LOCAL`, `DB_HOST/NAME/USER/PASS/CHARSET`, `ADMIN_EMAILS` (emails promus admin à la connexion Google), `SECRET_KEY` (page cachée `/unlock`).

### `storage/settings.json` (clé → valeur, via la classe `Settings`)
Exemples de clés : `theme`, `theme_custom_*` (couleurs perso), `main_title`, `main_message`, `main_footer`, `default_template`, `art_text_scale`, `art_font*`, `block_hours`, `max_attempts`, `google_client_id/secret` (surcharge admin).

---

## 6. Base de données

Toutes les tables sont **auto-créées** au premier appel `Database::pdo()` (constantes `SCHEMA_*` exécutées), et des **migrations légères** ajoutent les colonnes manquantes (`ensureColumn`). Le fichier `database/database.sql` reflète le schéma.

| Table | Rôle | Colonnes clés |
|---|---|---|
| `users` | Membres & admins | id, username, google_id, name, email, picture, role (`membre`/`admin`), password (chiffré ; admins **et** membres e-mail), blocked, created_at, last_login, default_city/lat/lng, **discoverable**, **member_code** (1 lettre+3 chiffres, unique), **theme_pref**, **domains** (rôles/domaines, séparés par virgules) |
| `articles` | Articles | id, title, content (HTML nettoyé), image, **template**, **active**, **protected**, **announcement**, **urgent**, parent_id (sous-article), author_id, author_name, **access_password** (haché ; NULL=libre), created_at, updated_at |
| `article_images` | Galerie photo | id, article_id, filename, created_at |
| `article_files` | Pièces jointes | id, article_id, filename, original, mime, size, created_at |
| `article_reviews` | Avis (note+commentaire) | id, article_id, user_id, user_name, stars (1-5), comment, … — **UNIQUE(article_id,user_id)** |
| `article_comments` | Fil de discussion | id, article_id, user_id, user_name, body, created_at |
| `comment_flags` | Signalements de messages | id, comment_id, user_id — **UNIQUE(comment_id,user_id)** |
| `article_flags` | **Signalements d'articles** (3 distincts → masqué) | id, article_id, user_id — **UNIQUE(article_id,user_id)** |
| `article_quizzes` | **Liaison article ↔ questionnaire** | id, article_id, quiz_id, position — **UNIQUE(article_id,quiz_id)** |
| `quizzes` | **Questionnaires (QCM)** | id, title, description, image, active, urgent, **required** (obligatoire), **pass_required**, **max_attempts** (0=illimité), author_id, author_name, created_at, updated_at |
| `quiz_questions` | Questions | id, quiz_id, body, **type** (`single`/`multiple`), position |
| `quiz_options` | Réponses possibles | id, question_id, label, **is_correct**, position |
| `quiz_responses` | Participations | id, quiz_id, user_id, user_name, score, total, **attempts**, created_at — **UNIQUE(quiz_id,user_id)** |
| `quiz_answers` | Options cochées | id, response_id, question_id, option_id |
| `appointments` | Créneaux / événements d'agenda | id, owner_id, owner_name, title, description, start_at, duration_min, capacity, **mode** (`presentiel`/`en_ligne`), location, **visibility** (`public`/`private`), show_booker_ratings, min_notice_hours, code (2 lettres+3 chiffres, unique, sur **tous**), **protected**, **urgent**, lat, lng, created_at |
| `appointment_bookings` | Réservations | id, appointment_id, user_id, user_name, present, created_at — **UNIQUE(appointment_id,user_id)** |
| `appointment_changes` | Historique (lieu, horaire, places, délai) | id, appointment_id, field, old_value, new_value, changed_at |
| `appointment_images` | Photos d'un événement | id, appointment_id, filename, created_at |
| `appointment_ratings` | Notes d'un événement par les participants | id, appointment_id, user_id, stars (1-5), comment, created_at, updated_at — **UNIQUE(appointment_id,user_id)** |
| `member_ratings` | Notes d'hôtes (réputation) | id, owner_id, rater_id, stars, created_at, updated_at — **UNIQUE(owner_id,rater_id)** |
| `urgent_dismissals` | Fermetures d'alertes URGENT (par utilisateur) | id, user_id, item_type (`article`/`event`/`quiz`), item_id — **UNIQUE(user_id,item_type,item_id)** |
| `notifications` | Notifications in-app | id, user_id, icon, message, link, is_read, created_at |

Relations : logiques (pas de clés étrangères strictes) ; le nettoyage en cascade est géré côté contrôleur/modèle (suppression d'un article → images, fichiers, avis, discussion, **signalements, liens quiz** ; suppression d'un quiz → questions, options, réponses ; suppression d'un créneau → réservations, historique, **photos, notes d'événement**). Le passage d'un élément « normal → urgent » **réinitialise les fermetures** (`urgent_dismissals`). Les articles/événements **protégés** sont épargnés par l'effacement global. Fuseau horaire fixé à **Europe/Paris** (PHP + session MySQL).

---

## 7. Routage (`config/routes.php`)

| Méthode | Chemin | Contrôleur@action | Accès |
|---|---|---|---|
| GET | `` | AuthController@showLogin | public |
| GET | `dashboard` | DashboardController@index | membre |
| GET | `afrique` | AfriqueController@index | **public** (54 drapeaux) |
| POST | `profile/discoverable` · `profile/theme` · `profile/dismiss_urgent` | toggleDiscoverable · saveTheme · dismissUrgent | membre |
| GET | `logout` | AuthController@logout | — |
| GET | `google/callback` | AuthController@callback | — |
| GET | `articles` | ArticleController@index | **public** |
| GET | `article` | ArticleController@show | public (publiés) |
| GET | `articles/new` · `articles/edit` | create · edit | membre (auteur/admin pour edit) |
| POST | `articles/preview` | preview | membre (AJAX aperçu) |
| POST | `articles/save` | store | membre |
| POST | `articles/toggle` | toggle | auteur/admin |
| POST | `articles/review` | review | membre |
| POST | `articles/comment` · `comment_delete` · `comment_report` | comment… | membre |
| POST | `articles/delete` | delete | auteur/admin |
| GET | `agenda` | AgendaController@index | membre (vue par défaut : **Mes créneaux**) |
| GET | `agenda/event` | AgendaController@event | public si événement public, sinon hôte/inscrit/admin |
| GET | `agenda/global` | globalAgenda | membre |
| POST | `agenda/create` · `delete` · `update` · `location` · `add_member` · `capacity` · `ratings_toggle` · `visibility` · `presence` · `notice` | … | hôte |
| POST | `agenda/protect` · `agenda/urgent` | toggleProtect · toggleUrgent | hôte |
| POST | `agenda/book` · `cancel` · `rate` · `rate_event` · `default_city` | … | membre |
| GET/POST | `api/ping` · `api/article` | ApiController (clé `X-API-Key`) | clé API (supporte cover, galerie, **parent_id**) |
| GET | `notifications` · `notifications/poll` · POST `notifications/clear` | NotificationController | membre |
| GET | `unlock` | UnlockController@index | clé secrète |
| GET/POST | `admin/login` | AdminController@showLogin/login | public/anti-bruteforce |
| GET | `admin/dashboard` · `members` · `security` · `settings` | … | admin |
| POST | `admin/settings` | saveSettings | admin |
| GET | `admin/articles`, `admin/articles/style`, `admin/style` | AdminArticleController / AdminController | admin |
| POST | `admin/articles/style` · `export` · `import` · `assign` · `protect` · `announce` | … | admin |
| POST | `admin/promote/demote/block/unblock/delete/ip_unblock/ip_unblock_all` | … | admin |
| POST | `admin/wipe_agenda` · `admin/wipe_articles` | wipe… | super-admin (mot de passe BDD, épargne les **protégés**) |

---

## 8. Cœur (`app/core/`)

| Classe | Rôle |
|---|---|
| **Router** | Enregistre `get()/post()`, normalise l'URL (retire `BASE_PATH`), `dispatch()` → instancie le contrôleur et appelle l'action ; 404 sinon. |
| **Session** | Encapsule `$_SESSION` : `start/get/set/has/remove/user()/isAdmin()/destroy()`. |
| **Database** | PDO partagé ; auto-création BDD+tables (`SCHEMA_*`), `ensureColumn()` (migrations), `tryConnect()`, `persist()` (écrit `storage/db.php` en ligne). |
| **Settings** | Lecture/écriture de `storage/settings.json` (cache mémoire). |
| **Theme** | Thèmes (variables CSS) : `all()` (10 prédéfinis + `custom`), `customVars()` (couleurs admin + nuances dérivées via `color-mix`), `css()` (injecte `:root`, **polish UX** `ux()`, **favicon**), `key()`, `hex()`. |
| **Html** | Nettoyage anti-XSS du HTML d'article (`clean()`) : whitelist de balises, suppression scripts/styles, attributs filtrés, `href` sûrs, styles inline limités (alignement/taille/police). |
| **Upload** | Upload sécurisé : images (`image`/`images`, redimensionnées ≤1200px via GD, EXIF), documents (`documents`, whitelist d'extensions, MIME refusés, nom aléatoire). |
| **Geocoder** | Adresse → lat/lng via **Nominatim** (sans clé) ; `null` si réseau indisponible. |
| **ArticleTemplate** | Catalogue des mises en page : `groups()` (5 familles), `all()` (16 clés→libellés), `key()`. |
| **ArticleStyle** | Style global du corps d'article (taille %, police), `css()`. |
| **GoogleClient** | Flux OAuth Google (URL d'auth, échange de code, userinfo). |
| **LoginGuard** | Anti-bruteforce admin (compte les essais par IP, blocage temporaire, déblocage). |
| **helpers.php** | `url()`, `redirect()`, `view()`, `human_filesize()`, `file_type_icon()`, `rating_stars()`, `rdv_lieu_texte()`. |

---

## 9. Contrôleurs (`app/controllers/`)

- **AuthController** — `showLogin` (accueil), `callback` (retour Google → upsert user), `logout`.
- **DashboardController** — `index` (tableau de bord membre : stats + accès rapides).
- **ArticleController** — `index` (liste publique + moyennes d'avis), `show` (article + galerie + documents + avis + discussion), `create/edit/store` (CRUD, réservé auteur/admin), `preview` (rendu fidèle en AJAX via vrai gabarit), `toggle` (public/brouillon), `review` (avis), `comment/commentDelete/commentReport` (discussion), `delete`. Permissions centralisées dans `canManage()` (auteur **ou** admin).
- **AgendaController** — `index` (mes créneaux, mes réservations, recherche proximité + carte, recherche par code, liste publique paginée), `create/delete`, `updateLocation` (+ historique), `updateCapacity` (− / +), `addMember` (hôte, hors limite), `book/cancel`, `rate`.
- **AdminController** — `dashboard`, `members` (promouvoir/rétrograder/bloquer/supprimer), `security` (IP bloquées), `settings/saveSettings` (thème + perso + textes + Google + sécurité), login admin (avec `LoginGuard`).
- **AdminArticleController** — `index` (modération de tous les articles), `style/saveStyle` (style global).
- **UnlockController** — page cachée protégée par `SECRET_KEY` pour débloquer les IP à distance.

---

## 10. Modèles (`app/models/`)

- **User** — `findByEmail/findById/findAdminByLogin`, `all/count/countByRole`, `upsertFromGoogle`, **`createMember`**, `createAdmin`, `setRole/setBlocked/delete`, **`setPicture`**, **`setDomains`/`domainsToList`/`domainSuggestions`**, `ensureCode/findByCode`, `setThemePref`.
- **Article** — `all/roots/children`, `find`, `create/update/setActive/delete`, `setProtected/setAnnouncement/setUrgent`, **signalements** (`flag/flagCount/flagCountsFor/userFlagged/clearFlags/isFlagHidden`), **mot de passe** (`setAccessPassword/hasPassword/checkAccess`), **quiz liés** (`quizIds/quizzesFor/setQuizzes`).
- **Quiz** — `all/find/create/update/delete`, `setActive/setUrgent/setRequired`, questions/options (`questions/addQuestion/addOption`), réponses (`responseFor/saveResponse/answersFor/responseCount`), `attemptsFor/canAttempt`, `percent`, `firstPendingRequired/isCompletedBy`.
- **ArticleImage** — `forArticle/find/add/delete`.
- **ArticleFile** — `forArticle/find/add/delete`.
- **ArticleReview** — `set` (upsert), `forArticle`, `summary`, `summaryFor`, `mine`, `deleteForArticle`.
- **ArticleComment** — `add/forArticle/find/delete/deleteForArticle`, `flag/flagCounts/userFlags` (signalements).
- **Appointment** — `create` (géocode l'adresse), `find`, `forOwner`, `upcomingOthers`, `nearby` (Haversine SQL), `updateLocation` (re-géocode), `updateCapacity`, `generateUniqueCode`, `findByCode`, `delete`.
- **AppointmentBooking** — `add` (sans contrôle de capacité), `countFor`, `hasBooked`, `forAppointment`, `appointmentIdsForUser`, `forUserDetailed`, `userBookedFromOwner`, `cancel`, `deleteForAppointment`.
- **AppointmentChange** — `add/forAppointment/deleteForAppointment` (historique du lieu).
- **Rating** — `set` (upsert), `summaryFor`, `myRating`.

---

## 11. Vues & partials (`app/views/`)

- Chaque vue est un gabarit `*.view.php` autonome (HTML complet) rendu par `view('chemin', $data)` (extract + require).
- **Partials d'article réutilisés** (page publique **et** aperçu) :
  - `articles/_article.css.php` — styles partagés (cartes, 16 modèles, avis, discussion).
  - `articles/_card.php` — la carte d'article selon le modèle (3 familles de structure).
  - `articles/_gallery.php` — galerie photo.
  - `articles/_files.php` — pièces jointes + visionneuse PDF.
- `articles/preview.view.php` — document autonome chargé dans l'`<iframe>` d'aperçu.
- `Theme::css()` est inclus dans le `<head>` de **toutes** les vues → variables de thème + UX globale + favicon en un seul point.

---

## 12. Fonctionnalités détaillées

### Articles
- **Éditeur riche** (`contenteditable`) avec barre d'outils ; repli `<textarea>` sans JS ; contenu nettoyé serveur par `Html::clean()`.
- **19 mises en page** (Simple, Couverture en vedette, Image à côté, Panoramique, Lecture/presse, **Carrousel / Diaporama / Galerie animée**) — pur CSS sauf quelques structures dans `_card.php`.
- **Annonce** (admin) : l'article apparaît en avant sur la **page d'accueil** de tous. **Protection** (admin) : épargné par « effacer tous les articles ». **URGENT** (admin, toggle rouge) : alerte sur le tableau de bord de tous. Réglables depuis le **formulaire** (toggles) **et** la modération.
- **Photos** : liste unifiée ; une photo **principale ⭐** (= couverture), les autres en galerie ; cumul des sélections, suppression, glisser une étoile.
- **Pièces jointes** : PDF/Word/Excel/… ; les **PDF** ont une **visionneuse intégrée** (iframe chargée au clic).
- **Aperçu en direct fidèle** : le formulaire poste vers `articles/preview` qui rend le **vrai gabarit** dans une `<iframe>` (mêmes styles + `Html::clean`), images injectées côté client.
- **Avis** : 1 note ★ + commentaire par membre (moyenne affichée, l'auteur ne note pas le sien).
- **Discussion** : fil de messages (plusieurs par membre) ; suppression par auteur du message / de l'article / admin ; **signalement de message** (un par membre).
- **Sous-articles** : sélecteur « article parent » ; n'apparaissent jamais seuls dans la liste, mais en **petites vignettes-photo** sur la carte du parent.
- **Signalement d'article** (`article_flags`) : 1 par membre ; à **3 membres distincts** l'article est **masqué au public** (sauf `protected`/`announcement`). Bandeau + panneau de modération admin + réinitialisation.
- **Mot de passe d'accès** (`access_password`, haché) : aucun / **code membre** de l'auteur / **personnalisé**. Écran `articles/locked.view.php` ; déverrouillage mémorisé en session (`articles/unlock`). Auteur/admin exemptés.
- **Vues** : total par IP (`article_views`), liste des **membres** qui ont lu (`article_member_views`) **et** des **adresses IP** des visiteurs non connectés (auteur/admin).
- **Questionnaires associés** (`article_quizzes`) : proposés en fin de lecture (encadré « Teste tes connaissances »).
- **Export PDF** : bouton `window.print()` + CSS `@media print` dédié (ne garde que l'article).

### Questionnaires (QCM)
- **Modèle** `Quiz` + tables `quizzes` / `quiz_questions` / `quiz_options` / `quiz_responses` / `quiz_answers`.
- Création par tout membre ; questions **single** (1 bonne réponse) ou **multiple** ; image de couverture (`uploads/quizzes/`).
- **Notation** : une question juste = exactement le bon ensemble d'options ; score + **% de réussite** (barre).
- **Tentatives** : `max_attempts` (0 = illimité) ; `canAttempt()` ; compteur `attempts` ; refaire jusqu'à la limite (ignoré si obligatoire).
- **Obligatoire** (admin) : `required` + `pass_required` ; garde global dans `Router::dispatch()` — un membre non à jour est redirigé vers le quiz et bloqué partout (admins exemptés ; quiz vide jamais bloquant).
- **URGENT** possible (alerte tableau de bord, comme articles/événements).

### Authentification
- **Google OAuth 2.0** (`GoogleClient`) : `state` anti-CSRF, `upsertFromGoogle()`.
- **E-mail + mot de passe** : inscription/connexion (`AuthController@register/login`), `User::createMember()` (mot de passe `password_hash`), toujours rôle **membre**.
- **Admin** : connexion par identifiants MySQL réels (aucun mot de passe admin stocké).
- **Photo de profil** : `User::setPicture()` (URL Google ou fichier `uploads/avatars/`), helper `avatar_url()`.

### Agenda / Événements
- **Création** (« Créer un événement ») : titre, date/heure (heure de **Paris**, futur obligatoire), durée, **places (− / +)**, mode, lieu, visibilité, délai min. de réservation, **photos multiples**, participants (liste à cocher des membres **trouvables** + ajout **par code**).
- **Page dédiée** par événement (`agenda/event?id=`) façon magazine : couverture, badges, « À propos », infos, galerie, **carte + bouton « M'y rendre » (Google Maps itinéraire)**, billetterie (places + **% d'occupation**), note de l'événement.
- **Note de l'événement** par les participants (★, après le début) — distincte de la réputation de l'hôte.
- **Code unique** sur **chaque** événement (visible dans « Mes créneaux », même public). **Protection** et **URGENT** (badge rouge partout + alerte tableau de bord).
- **Recherche** d'événements (titre/hôte/lieu) sur la liste publique ; vue par défaut de l'agenda = **Mes créneaux**.
- **Public / Privé** : un privé est masqué et accessible par **code (2 lettres + 3 chiffres)** via une barre de recherche.
- **Lieu** : *en ligne* (lien visio) ou *présentiel* (adresse + **carte Google** embarquée + itinéraire) ; **autocomplétion** d'adresses (Nominatim) ; **historique** des changements de lieu.
- **Réservation** : contrôle de capacité (`book`), annulation ; l'**hôte peut ajouter un membre inscrit hors limite** (`add_member`).
- **Proximité** : recherche par rayon (5/10/20/50/100 km), résultats triés par distance, **carte Leaflet** multi-marqueurs cliquables (clic → défile vers la fiche), bouton **« Autour de moi »** (géolocalisation).
- **Notation** des hôtes (★) par les membres ayant réservé chez eux.
- **Pagination** de la liste publique (5/page, Précédent/Suivant).
- **Heure exacte** (de … à …) en plus du compte à rebours sur la fiche.
- **Ajouter à Google Agenda** : lien `calendar.google.com/render` pré-rempli (fuseau Paris) sur les cartes et la fiche, + **fichier `.ics`** universel (`agenda/ics`). Helper `google_calendar_url()`.
- **Agenda global** (`agenda/global`) : stats cliquables (déplient la liste), et clic sur un événement (vues Mois/Semaine/Liste) → sa fiche.

### Membres
- **Code membre** unique (1 lettre + 3 chiffres) ; **« trouvable »** (opt-in) pour apparaître dans la liste publique, sinon ajout uniquement **par code** (= accord). Affiché sur le tableau de bord.
- **Rôle / domaine** (`domains`) : le membre choisit ses spécialités (suggestions `User::domainSuggestions()` + saisie libre), affichées en badges 🎓 (`profile/roles`).
- **Photo de profil** : ajout/changement/retrait (`profile/photo`).
- **Thème personnel** : chaque membre choisit son apparence (mémorisée, appliquée à lui seul) — `Theme::key()` privilégie la préférence de session.
- **Alertes URGENT** : les éléments urgents (articles + événements + **questionnaires**) s'affichent en haut du tableau de bord de **tous** (avec leur image), fermables individuellement (`urgent_dismissals`).

### Apparence
- **Thèmes rangés par famille** (`Theme::byFamily()`, rendus en `<optgroup>`) : Communauté, Clairs, Sombres & néon, **Héros & dessins animés** (Simpson, **Black Panther**, **Batman**, **Rayman**), Sur mesure. + **Personnalisé** (7 couleurs, nuances via `color-mix`), aperçu en direct.
- **Générateur de favicon** (`app/core/Favicon.php`, `admin/favicon`) : à partir d'un **texte** (lettres + couleurs + coins arrondis) ou d'une **image** ; écrit `favicon.png/.ico` + `icon-192/512.png`, versionné pour le cache. Émis par `Theme::favicon()`.
- **Polish UX global** (focus clavier, défilement fluide, scrollbars, anti double-soumission…) injecté via `Theme::css()`.

---

## 13. Sécurité

- **XSS** : tout HTML d'article passe par `Html::clean()` (whitelist) ; tout le reste est échappé via `htmlspecialchars()` à l'affichage (titres, commentaires, avis, noms…).
- **SQL** : PDO + requêtes préparées partout (`ATTR_EMULATE_PREPARES=false`).
- **Uploads** : type MIME réel vérifié, extensions en whitelist, noms aléatoires, exécution de scripts bloquée par `.htaccess`, taille plafonnée (25 Mo), images recompressées.
- **OAuth** : flux Google côté serveur ; emails de `ADMIN_EMAILS` promus admin.
- **Anti-bruteforce** admin via `LoginGuard` (blocage IP temporaire, réglable).
- **Permissions** : `canManage()` (articles → auteur/admin) ; vérifications côté serveur sur **toutes** les actions POST (jamais seulement masquées en UI).
- **Secrets** hors dépôt public (`config.php`, `storage/db.php`).

---

## 14. Dépendances externes (réseau)

| Service | Usage | Clé requise |
|---|---|---|
| Google Fonts | Police Poppins | non |
| Google OAuth | Connexion membres | oui (client id/secret) |
| Google Maps (embed) | Carte d'un lieu présentiel | **non** (`output=embed`) |
| Leaflet + OpenStreetMap | Carte des résultats de proximité | non (CDN unpkg) |
| Nominatim (OSM) | Autocomplétion + géocodage | non (User-Agent requis) |

> Le géocodage serveur (`Geocoder`) utilise `file_get_contents` HTTPS → nécessite `allow_url_fopen`. En cas d'échec, la proximité ignore les créneaux sans coordonnées (dégradation sans casse).

---

## 15. Déploiement

1. Copier les fichiers (conserver l'arborescence ; `uploads/` et `storage/` inscriptibles).
2. **Local** : XAMPP, MySQL démarré → ouvrir `http://localhost/rpm/` (tables auto-créées).
3. **En ligne** : se connecter à l'espace admin avec **nom de base + mot de passe MySQL** (persistés dans `storage/db.php`). Configurer l'**URI de redirection Google** = `https://DOMAINE/rpm/google-callback.php`.
4. Vérifier `mod_rewrite` et les `.htaccess`.

---

## 16. Étendre le projet

- **Ajouter un thème** : une entrée dans `Theme::all()` (clé → label + vars). Apparaît automatiquement dans l'admin.
- **Ajouter une mise en page d'article** : une entrée dans `ArticleTemplate::groups()` + le CSS `.tpl-<clé>` dans `_article.css.php` (+ un cas dans `_card.php` si la structure diffère).
- **Ajouter une table** : une constante `SCHEMA_*` dans `Database` + son `exec()` dans `pdo()` (et l'ajouter à `database.sql`).
- **Ajouter une route** : `config/routes.php` → `get()/post()` vers `Controller@action`.
- **Ajouter un réglage** : lecture via `Settings::get()`, écriture dans `AdminController::saveSettings()` + champ dans la vue settings.

---

*Document généré pour le projet RPN — voir aussi `docs/fonctionnement.html` (guide d'utilisation) et `database/database.sql` (schéma).*

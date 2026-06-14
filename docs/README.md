# RPN — Plateforme communautaire

Application web (PHP/MySQL) avec **connexion Google** pour les membres, un **espace administrateur** complet, une **sécurité anti-bruteforce** et un système de **thèmes** personnalisables.

- **Local** : `http://localhost/rpm/`
- **En ligne** : `https://bokonzi.com/rpm/`

---

## 📋 Sommaire
1. [Fonctionnalités](#-fonctionnalités)
2. [Architecture (MVC)](#-architecture-mvc)
3. [Installation en local (XAMPP)](#-installation-en-local-xampp)
4. [Mise en ligne (Hostinger)](#-mise-en-ligne-hostinger)
5. [Connexion Google (OAuth)](#-connexion-google-oauth)
6. [Espace administrateur](#-espace-administrateur)
7. [Sécurité](#-sécurité)
8. [Thèmes & personnalisation](#-thèmes--personnalisation)
9. [Référence des fichiers](#-référence-des-fichiers)
10. [**API JSON (articles & quiz) → `docs/api/`**](api/README.md)

> 📡 **API distante** : créer des articles et des quiz par clé API — voir le dossier complet [`docs/api/`](api/README.md) (fonctionnement, routes/liens, raisons de blocage, sécurité, architecture, tests).

---

## ✨ Fonctionnalités

**Comptes & profil**
- 🔐 **Connexion membre** via « Se connecter avec Google » (OAuth 2.0)
- ✉️ **Inscription / connexion par e-mail + mot de passe** (sans Google ; mot de passe chiffré)
- 👤 **Tableau de bord membre** (profil, stats cliquables, alertes)
- 📷 **Photo de profil** : ajout / changement / retrait depuis le profil
- 🎓 **Rôle / domaine** : le membre choisit ses spécialités (suggestions + saisie libre)
- 🪪 **Code membre & visibilité** : code unique (1 lettre + 3 chiffres), mode « trouvable » ou invisible

**Articles**
- 📝 Éditeur riche, ~19 mises en page, galerie, pièces jointes, **aperçu en direct**
- 🌳 **Sous-articles** affichés en vignettes-photo sur la carte du parent
- 🔑 **Mot de passe d'accès** (aucun / code membre / personnalisé)
- 🚩 **Signalement** : masqué au public à 3 signalements (sauf protégé/annonce)
- ⭐ **Avis & discussion**, 👁️ **vues** (membres + adresses IP des non-connectés)
- 📄 **Export en PDF** (impression mise en forme)

**Questionnaires (QCM)**
- ❓ Quiz notés (choix unique / multiple), **score + % de réussite**
- 🔁 Nombre de **tentatives** réglable (0 = illimité)
- 🔗 **Associés aux articles** (proposés en fin de lecture)
- ⛔ **Obligatoire** (admin) : bloque l'app tant qu'on n'a pas répondu

**Agenda**
- 📅 Créneaux (présentiel / en ligne), réservation, capacité, proximité (carte)
- 📅 **Ajouter à Google Agenda** + fichier **.ics** universel ; heure exacte
- ⭐ Notation des hôtes, historique, présence

**Communauté & admin**
- 🔔 Notifications in-app · 🟥 **Alertes URGENT** (articles, événements, quiz)
- 🛠️ **Espace admin** : connexion par identifiants MySQL (comme phpMyAdmin)
- 👥 Gestion des membres · 👑 super-admin vs admin · 🛡️ anti-bruteforce + URL secrète de déblocage
- 📦 **Export / import complet** (articles **et** questionnaires + associations)
- 🎨 **Générateur de favicon** (texte ou image)
- 🎨 **Thèmes** rangés par famille (Panafricain, Sombre, Océan, Black Panther, Batman, Rayman…) + **personnalisé**, avec aperçu en direct
- 🗄️ **Création automatique** de la base et des tables (migrations légères au chargement)

---

## 🏛️ Architecture (MVC)

```
rpm/
├── index.php              → FRONT CONTROLLER : unique point d'entrée (toutes les requêtes)
├── google-callback.php    → exception conservée (URL enregistrée chez Google) : relai vers le routeur
├── .htaccess              → réécrit toutes les URLs vers index.php (URLs propres)
│
├── config/
│   ├── config.php         → configuration (Google, BDD, admins, clé secrète)
│   ├── routes.php         → TABLE DE ROUTAGE (url → contrôleur@action)
│   ├── db.php             → (généré) identifiants BDD en ligne
│   └── .htaccess          → accès web interdit
│
├── database/
│   ├── database.sql       → structure de la base (référence)
│   └── .htaccess          → accès web interdit
│
├── docs/
│   ├── README.md          → ce fichier
│   └── documentation.html → documentation technique détaillée
│
├── storage/
│   ├── settings.json      → réglages modifiables depuis l'admin
│   ├── login_attempts.json→ tentatives & blocages d'IP
│   └── .htaccess          → accès web interdit
│
└── app/
    ├── bootstrap.php      → démarrage (config + autoload + session + BASE_PATH)
    ├── core/
    │   ├── Router.php      → routeur : associe une URL à un contrôleur/action
    │   ├── Database.php    → connexion PDO, auto-création, persistance
    │   ├── Session.php     → gestion de la session
    │   ├── GoogleClient.php→ communication OAuth Google
    │   ├── LoginGuard.php  → protection anti-bruteforce
    │   ├── Settings.php    → lecture/écriture des réglages
    │   ├── Theme.php       → définition des thèmes
    │   └── helpers.php     → url(), redirect(), view()
    ├── controllers/
    │   ├── AuthController.php       → connexion / callback / déconnexion
    │   ├── DashboardController.php  → tableau de bord membre
    │   ├── AdminController.php      → admin (membres, sécurité, paramètres)
    │   └── UnlockController.php     → page secrète : déblocage IP à distance
    ├── models/
    │   └── User.php        → accès base de données (utilisateurs)
    └── views/
        ├── login.view.php
        ├── dashboard.view.php
        ├── unlock.view.php
        ├── errors/
        │   └── 404.view.php
        └── admin/
            ├── login.view.php
            ├── dashboard.view.php
            ├── members.view.php
            ├── security.view.php
            └── settings.view.php
```

**Principe** : toutes les requêtes entrent par `index.php` (front controller). Le **routeur** (`config/routes.php`) associe chaque URL propre à un **contrôleur**, qui utilise les **modèles** (BDD) et affiche les **vues** (HTML). Les **classes utilitaires** (`core/`) regroupent la logique réutilisée. Seule exception à la racine : `google-callback.php`, conservé parce que son URL est enregistrée chez Google.

### Routes (URLs propres)

| URL | Contrôleur → action |
|---|---|
| `/rpn/` | `AuthController@showLogin` (accueil) |
| `/rpn/login` (POST) · `/rpn/register` | `AuthController@login` / `showRegister` / `register` (e-mail) |
| `/rpn/dashboard` | `DashboardController@index` |
| `/rpn/profile/photo` · `profile/roles` · `profile/theme` · `profile/discoverable` (POST) | `DashboardController@…` |
| `/rpn/logout` · `/rpn/google/callback` | `AuthController@logout` / `callback` |
| `/rpn/articles` · `article?id=` · `articles/new` · `articles/edit` | `ArticleController@…` |
| `/rpn/articles/save` · `unlock` · `report` · `clear_flags` · `delete` (POST) | `ArticleController@…` |
| `/rpn/quiz` · `quiz/new` · `quiz/edit` · `quiz/show` | `QuizController@…` |
| `/rpn/quiz/save` · `submit` · `toggle` · `delete` (POST) | `QuizController@…` |
| `/rpn/agenda` · `agenda/event` · `agenda/global` · `agenda/ics` | `AgendaController@…` |
| `/rpn/notifications` · `notifications/poll` | `NotificationController@…` |
| `/rpn/admin/login` · `dashboard` · `members` · `security` · `settings` | `AdminController@…` |
| `/rpn/admin/favicon` (GET/POST) | `AdminController@favicon` / `saveFavicon` |
| `/rpn/admin/articles` · `…/export` · `…/import` · `…/clear_flags` | `AdminArticleController@…` |
| `/rpn/unlock` | `UnlockController@index` (page secrète IP) |

> Liste complète et commentée dans `config/routes.php`.

---

## 💻 Installation en local (XAMPP)

1. Place le dossier dans `C:\xampp\htdocs\rpm`
2. Démarre **Apache** et **MySQL** dans XAMPP
3. Ouvre `http://localhost/rpm/`
   → La base `rpm` et la table `users` se **créent automatiquement**.

**Identifiants locaux par défaut** (dans `config/config.php`) :
```php
DB_HOST = localhost
DB_NAME = rpm
DB_USER = root
DB_PASS = (vide)
```

---

## 🌍 Mise en ligne (Hostinger)

En ligne, MySQL n'utilise pas `root`. Crée une base + un utilisateur dans **hPanel → Bases de données MySQL**, puis crée le fichier `config/db.php` (via le Gestionnaire de fichiers) :

```php
<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'u123456789_rpm');   // ton nom de base
define('DB_USER',    'u123456789_rpm');   // souvent = nom de base
define('DB_PASS',    'TON_MOT_DE_PASSE');
define('DB_CHARSET', 'utf8mb4');
```

> 💡 Alternative : connecte-toi à l'espace admin avec ton nom de base + mot de passe ; l'app enregistre alors `config/db.php` automatiquement.

---

## 🔑 Connexion Google (OAuth)

Configurée dans **Google Cloud Console → API et services → Identifiants** (ID client OAuth, type « Application Web »).

**URI de redirection autorisés** :
```
http://localhost/rpm/google-callback.php
https://bokonzi.com/rpm/google-callback.php
```

**Origines JavaScript autorisées** :
```
http://localhost
https://bokonzi.com
```

L'`URI de redirection` est détectée **automatiquement** (local vs en ligne) dans `config/config.php`. Les clés (`Client ID` / `Client Secret`) sont modifiables depuis **Admin → Paramètres**.

**Le flux** : accueil `/rpm/` (bouton) → Google → `google-callback.php` (échange du code → token → profil) → enregistrement en base → `/rpm/dashboard`.

---

## 🛠️ Espace administrateur

Accès : `/rpm/admin/login` (lien « 🔐 Connexion administrateur » sur la page d'accueil).

**Connexion** = identifiants **MySQL** (comme phpMyAdmin) :
- En local : `root` + mot de passe vide
- En ligne : nom de base Hostinger + mot de passe

### Hiérarchie

| Action | Super-admin | Admin normal |
|---|---|---|
| Voir les membres | ✅ | ✅ |
| Bloquer un simple membre | ✅ | ✅ |
| Rendre / retirer admin | ✅ | ❌ |
| Supprimer un membre | ✅ | ❌ |
| Modifier un admin | ✅ | ❌ |
| Toucher au super-admin | ❌ | ❌ |

- **Super-admin** = connexion par identifiants MySQL **ou** email listé dans `ADMIN_EMAILS` (config)
- **Admin normal** = membre promu via « Rendre admin »

### Pages admin
- **Tableau de bord** : statistiques (membres, admins) + accès rapides
- **Membres** : gestion + rôles
- **Modération des articles** : tout voir, réattribuer, protéger, annonce, **articles signalés**, **export/import complet**
- **Questionnaires** : créer / modifier / supprimer, rendre obligatoire
- **Sécurité** : IP bloquées (voir / débloquer)
- **Paramètres** : clés Google, blocage, thème, message d'accueil
- **🎨 Favicon** : générateur (texte ou image)
- **Style global** : police, arrondis, ombres, animations

---

## 🛡️ Sécurité

- **Anti-bruteforce** : après `N` essais ratés (défaut 3), l'IP est bloquée `H` heures (défaut 24). Réglable dans **Paramètres**. Bouton et champs **désactivés** quand bloqué.
- **URL secrète** de déblocage (protégée par `SECRET_KEY`, faux 404 sinon) :
  ```
  /rpm/unlock?key=LA_CLE              → liste + déblocage
  /rpm/unlock?key=LA_CLE&all=1        → tout débloquer
  ```
- **Protection CSRF** sur la connexion Google (paramètre `state`)
- **Dossiers protégés** : `config/`, `storage/`, `app/` (accès web interdit par `.htaccess`)
- **Mots de passe** des admins jamais stockés : authentification par connexion MySQL réelle
- **Protection contre l'injection** : requêtes préparées (PDO)

> ⚠️ La clé secrète (`SECRET_KEY`) et le mot de passe de la base ne doivent **jamais** être publiés.

---

## 🎨 Thèmes & personnalisation

Dans **Admin → Paramètres → Apparence** (et **Tableau de bord → Mon thème** pour chaque membre) :
- Thèmes **rangés par famille** : Communauté (Panafricain, Pro Black), Clairs (Classique, Clair, Menthe), Sombres & néon (Sombre, Océan, Forêt, Coucher de soleil, Violet), Héros & dessins animés (Simpson, **Black Panther**, **Batman**, **Rayman**), **Personnalisé** (7 couleurs).
- **Aperçu en direct** : la page change dès la sélection (avant d'enregistrer).
- Le thème du site s'applique à **toutes les pages** ; chaque membre peut aussi choisir un **thème personnel**.
- **🎨 Générateur de favicon** (Admin → Favicon) : icône du site à partir d'un texte ou d'une image.

**Message principal** personnalisable : titre, message d'accueil, bas de page.

---

## 📁 Référence des fichiers

| Fichier | Rôle |
|---|---|
| `config/config.php` | Clés Google, BDD, `ADMIN_EMAILS`, `SECRET_KEY` |
| `app/core/Database.php` | PDO, auto-création, `tryConnect()`, `persist()` |
| `app/core/GoogleClient.php` | `getAuthUrl()`, `fetchToken()`, `fetchUserInfo()` |
| `app/core/LoginGuard.php` | `recordFailure()`, `isBlocked()`, `blockedList()` |
| `app/core/Settings.php` | `get()`, `save()` (storage/settings.json) |
| `app/core/Theme.php` | `all()`, `byFamily()`, `key()`, `css()`, `favicon()` |
| `app/core/Favicon.php` | génération du favicon (texte/image) → `favicon.png/.ico`, `icon-192/512.png` |
| `app/core/Upload.php` | uploads sécurisés (images, documents) |
| `app/models/User.php` | `upsertFromGoogle()`, `createMember()`, `setDomains()`, `setPicture()`, `ensureCode()`, `setBlocked()` |
| `app/models/Article.php` | CRUD, sous-articles, **signalements** (`flag/isFlagHidden`), **mot de passe** (`setAccessPassword/checkAccess`), **liens quiz** (`setQuizzes`) |
| `app/models/Quiz.php` | questionnaires : `create/update`, questions/options, réponses, `canAttempt()`, `percent()` |
| `app/controllers/QuizController.php` | création / réponse / score / obligatoire |
| `app/views/articles/locked.view.php` | écran de déverrouillage (mot de passe article) |

### Tables principales (créées automatiquement)
`users`, `articles`, `article_images`, `article_files`, `article_flags`, `article_quizzes`,
`article_reviews`, `article_comments`, `article_views`, `article_member_views`,
`appointments` (+ bookings / changes / images / ratings), `notifications`, `member_ratings`,
`quizzes`, `quiz_questions`, `quiz_options`, `quiz_responses`, `quiz_answers`.

---

🤖 Guide d'utilisation complet : **`tutoriel.html`** · Documentation technique : **`TECHNIQUE.md`** / **`documentation.html`**.

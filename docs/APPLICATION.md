# RPN — Documentation complète de l'application

> Plateforme web communautaire (PHP natif + MySQL, architecture MVC maison, sans framework ni build).
> Cette fiche décrit **tout ce que fait l'application** dans son état actuel, module par module,
> ainsi que l'architecture, les rôles et les services utilisés.

---

## Sommaire
1. [Présentation](#1-présentation)
2. [Rôles & accès](#2-rôles--accès)
3. [Modules & fonctionnalités](#3-modules--fonctionnalités)
4. [Espace administrateur](#4-espace-administrateur)
5. [Architecture technique](#5-architecture-technique)
6. [Sécurité](#6-sécurité)
7. [Services externes](#7-services-externes)
8. [Installation & déploiement](#8-installation--déploiement)
9. [Coûts & tarification](#9-coûts--tarification)

---

## 1. Présentation

RPN est une application web qui réunit, en un seul outil, tout ce qu'il faut pour **animer une communauté** : contenus (articles), apprentissage (questionnaires), organisation (agenda), échanges (messagerie + visio), et pilotage (espace admin). Elle est **installable** (PWA : mobile/PC, hors-ligne, notifications), **multi-thèmes**, et fonctionne sur un simple hébergement mutualisé PHP/MySQL.

---

## 2. Rôles & accès

| Rôle | Accès |
|---|---|
| **Visiteur** (non connecté) | Page d'accueil avec **tous les articles publics**, lecture des articles publiés, pages vitrines. |
| **Membre** | Tableau de bord, profil, articles (lecture + écriture), quiz, agenda, messagerie, communauté, niveaux/classement. |
| **Administrateur** | Tout l'espace membre **+ espace admin** avec **interface dorée distinctive** (badge « MODE ADMIN » + barre dorée). |

- **Connexion** : Google (OAuth 2.0) ou e-mail + mot de passe.
- **Bascule** : bouton **« 👑 Espace admin »** dans le profil (membre → admin) et **« 🚪 Espace membre »** dans la barre admin (admin → membre).

---

## 3. Modules & fonctionnalités

### 📰 Articles
- Éditeur de texte riche, **modèles de mise en page** multiples, **aperçu en direct côte à côte** (formulaire à gauche, rendu collant à droite).
- **Couverture + galerie** d'images ; **style de galerie au choix** : carrousel automatique, slider manuel (flèches + points), grille/mosaïque, bandeau + miniatures.
- **Pièces jointes** (PDF avec visionneuse, Office, etc.).
- **Tags / catégories** + **recherche** plein-texte (titre, contenu, tags) avec filtres par tag.
- **Position manuelle** (ordre d'affichage sur l'accueil) + **annonces** mises en avant.
- **Sous-articles**, **mot de passe d'accès** (3 modes), **signalements** (masquage auto), **avis** (note ★ + commentaire), **fil de discussion**.
- **Style global des articles** (taille du texte, police, **largeur**) réglable par l'admin, applicable à tous d'un bouton.
- Rendu des **équations mathématiques** (KaTeX).

### ❓ Questionnaires (QCM)
- Création de questions à réponse **unique ou multiple**, **image par question** (en plus de la couverture du quiz).
- **Image de couverture** modifiable à tout moment depuis la page du quiz.
- **Modes de déroulé** : **une question à la fois** (par défaut), **retour immédiat** (bon/faux après chaque question), **effets visuels** (confettis / secousse).
- **Chrono** (temps limité, validation auto à 0), **ordre aléatoire** (questions + réponses).
- **Explication par question** (montrée au corrigé / retour immédiat).
- **Seuil de réussite (%)** + **messages personnalisés** (réussi / échoué), nombre de tentatives, quiz **obligatoire** (bloque l'app tant qu'il n'est pas réussi).
- Association d'un quiz à un article (proposé en fin de lecture).

### 📅 Agenda & rendez-vous
- Création de créneaux, **réservations**, capacité, présentiel/en ligne, **géolocalisation**, historique.
- Export **.ics** + lien Google Agenda, vue d'ensemble globale.
- Notes/évaluations des hôtes et des événements.
- **Rappels automatiques** : notification ~1 h avant un rendez-vous réservé (sans cron, via le sondage de notifications).

### ✉️ Messagerie & visio
- Messages privés **en temps réel** (sondage adaptatif), présence en ligne.
- **Partage de fichiers** (images en aperçu, documents en téléchargement).
- **Salons visio** type Zoom via **Jitsi Meet** : bouton « Créer un lien » présent dans **toutes les pages communauté** (audio, vidéo, partage d'écran).
- **« Mes salons »** : enregistrer les liens créés, les **nommer / renommer**, les rouvrir, copier, supprimer.

### 👥 Communauté & gamification
- **Annuaire** des membres / professeurs (recherche par nom, matière, ville…) — un admin trouve **tout le monde**, un membre seulement les profils « trouvables ».
- **Visibilité** réglable par chaque membre (trouvable / non) + **valeur par défaut à l'inscription** définie par l'admin.
- **Carte de la diaspora** (Leaflet/OpenStreetMap).
- **Niveaux** (Héritier → Sage, points calculés à la volée) et **classement** des membres.

### 🔔 Notifications
- File de notifications in-app, **temps réel** (cloche + toast + son), badge non-lus, marquage lu.

### 🧭 Autres
- **Assistant Sankofa**, pages vitrines (Pays d'Afrique), **niveaux**, **PWA** (installation, hors-ligne, mises à jour forcées).

---

## 4. Espace administrateur

Interface **dorée** distinctive, **barre de navigation unifiée** (Tableau de bord · Statistiques · Articles · Membres · Sécurité · Paramètres) + badge « MODE ADMIN ».

- **Tableau de bord** : statistiques rapides (cartes cliquables) + accès « Apparence & style » + zone de danger (effacements, super-admin).
- **📊 Statistiques** : membres (total/actifs/nouveaux), articles (vues + top), quiz (participations, réussite moyenne, top), agenda.
- **Articles** : modération, export/import (.zip), attribution, protection, annonces, **style général** applicable à tous.
- **Membres** : promouvoir/rétrograder, bloquer, supprimer, **visibilité par défaut**.
- **Sécurité** : IP bloquées, réglages anti-bruteforce.
- **Paramètres (unifiés)** : Thème & couleurs · Style global (police/arrondi/ombres/animations) · Favicon (formes carré/arrondi/cercle, fond transparent, polices, bouton rafraîchir le cache) · Page d'accueil · Articles · Membres · Connexion Google · Sécurité · Clé API · Sauvegarde.

---

## 5. Architecture technique

| Aspect | Détail |
|---|---|
| Langage | PHP 8+ (aucune dépendance Composer) |
| Base de données | MySQL / MariaDB via **PDO** (requêtes préparées) |
| Architecture | **MVC maison** + front controller (`index.php`) + **routeur** (`config/routes.php`) |
| Front-end | HTML/CSS/JS natif (aucun build), **variables CSS** pour les thèmes |
| Sessions | `$_SESSION` (login, messages flash) |
| Fichiers | `uploads/` (articles, quizzes, messages, branding…) |
| Réglages | `storage/settings.json` (clé/valeur) |
| Migrations | auto au chargement (`Database::ensureColumn` / `CREATE TABLE IF NOT EXISTS`) |

**Organisation des fichiers**
- `app/controllers/` — un contrôleur par domaine (Article, Quiz, Agenda, Message, Meet, Admin…).
- `app/models/` — accès aux données (Article, Quiz, User, Message, MeetingLink, Level…).
- `app/core/` — socle (Database, Router, Theme, Settings, Upload, helpers, GlobalStyle, ArticleStyle, Favicon…).
- `app/views/` — gabarits (vue par page + partiels partagés `_*.php`).
- `config/routes.php` — table des routes (URL → contrôleur@action).
- `docs/` — documentation, estimations de coûts, dossier entreprise.

---

## 6. Sécurité
- Mots de passe **hachés** (`password_hash`), **PDO préparé** partout.
- **Anti-bruteforce** par IP sur la connexion admin (essais + durée de blocage paramétrables).
- **State anti-CSRF** sur le flux Google OAuth.
- **Uploads cloisonnés** (type réel vérifié, redimension), `.htaccess` sur `uploads/`.
- Articles trop **signalés** masqués automatiquement.
- ⚠️ Limites connues : fichiers d'upload accessibles par URL (non chiffrés) ; visio hébergée sur meet.jit.si (hors site).

---

## 7. Services externes
| Service | Usage | Coût |
|---|---|---|
| Google OAuth | Connexion membres | gratuit |
| Jitsi Meet | Salons visio | gratuit |
| OpenStreetMap / Leaflet | Carte diaspora | gratuit |
| Google Fonts | Polices | gratuit |
| KaTeX (CDN) | Équations | gratuit |

---

## 8. Installation & déploiement
- **Local** : XAMPP (Apache + PHP 8 + MySQL), placer le projet dans `htdocs`, créer la base ; les tables se créent automatiquement.
- **En ligne** : hébergement mutualisé PHP/MySQL (type Hostinger), HTTPS via Let's Encrypt.
- Détails pas-à-pas : voir [`README.md`](README.md) et [`TECHNIQUE.md`](TECHNIQUE.md).

---

## 9. Coûts & tarification
- **Coûts réels** : ≈ 5 – 18 €/mois (hébergement mutualisé + domaine ; services externes gratuits).
- **Valeur de réalisation** : ≈ 16 000 – 42 000 € (255 – 560 h de développement).
- **Tarif conseillé à une entreprise** : ≈ 500 – 700 €/mois (formule Pro). Tarif solidaire association : 120 – 500 €/mois.
- Détails : [`tarification-entreprise.html`](tarification-entreprise.html) et le [dossier entreprise](dossier-entreprise/RPN-dossier-entreprise.md).

---

*RPN — Institut Sankofa · Documentation complète de l'application · 2026*

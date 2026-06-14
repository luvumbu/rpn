# 03 — Pourquoi l'API peut bloquer (et comment débloquer)

Cette page liste **toutes les raisons** pour lesquelles un appel peut échouer, le code
HTTP renvoyé, le message, et la **solution**.

## Tableau de référence

| Code | `error` | Cause | Solution |
|:----:|---------|-------|----------|
| **401** | `Clé API manquante ou invalide.` | Pas de clé, mauvaise clé, ou clé révoquée | Envoyer la **bonne** clé dans `X-API-Key`. Si régénérée depuis l'admin, l'ancienne ne marche plus. |
| **422** | `Champs requis : title et content.` | Article sans titre ou sans contenu | Fournir `title` **et** `content` non vides. |
| **422** | `Champs requis : title et questions[].` | Quiz sans titre ou sans tableau `questions` | Fournir `title` **et** au moins une question. |
| **422** | `Aucune question valide …` | Toutes les questions étaient invalides | Chaque question : énoncé non vide, **≥ 2 options**, **≥ 1 `correct:true`**. |
| **404** | *(page introuvable du site)* | Mauvaise URL, ou `GET` sur une route `POST` | Utiliser l'URL exacte sans `.php`, en **POST**. |
| **500** | `Erreur serveur …` | Base indisponible, table manquante, droits fichier | Voir la section « 500 » ci-dessous. |

## Détail des blocages

### 401 — Clé API manquante ou invalide
C'est **le blocage le plus fréquent**. Vérifie, dans l'ordre :

1. **L'en-tête est-il envoyé ?** `-H "X-API-Key: …"` (attention aux espaces/guillemets).
2. **La clé est-elle la bonne ?** La référence est `Settings::get('api_key')` si une clé a
   été **régénérée** dans `admin/settings`, sinon la constante `API_KEY` de `config.php`.
3. **A-t-elle été régénérée ?** Régénérer **révoque** instantanément l'ancienne clé : il
   faut redistribuer la nouvelle.
4. **Copie complète ?** Une clé tronquée échoue silencieusement (toujours 401).

> 💡 Test rapide sans rien créer : envoie un POST **avec** la clé mais **sans** les champs.
> Si tu obtiens **422** (et pas 401), la clé est **bonne**.

### 422 — Champs requis / questions invalides
L'écriture est **refusée avant tout enregistrement** (rien n'est créé). Causes :
- `title` vide, ou `content` vide une fois le HTML retiré ;
- `questions` absent, non-tableau, ou vide ;
- chaque question doit avoir **≥ 2 options** et **au moins une** `correct: true`,
  sinon elle est ignorée — et si toutes le sont, le quiz est annulé.

### 404 — page introuvable
- L'URL doit être **sans `.php`** : `/rpn/api/article` (pas `/rpn/api/article.php`).
- Les routes d'écriture n'existent qu'en **POST** : un `GET` dessus → introuvable.
- Si **tout** `/rpn/api/quiz` renvoie 404 alors que `api/ping` marche : l'endpoint n'est
  **pas déployé** (fichiers `ApiController.php` / `ApiKernel.php` / `routes.php` à mettre
  en ligne).

### 500 — erreur serveur
- **Base de données indisponible** ou identifiants `config.php` erronés.
- **Table manquante** (`article_quizzes`, `quizzes`…) : importer le schéma `database/database.sql`.
- **`ApiKernel` introuvable** après une mise en ligne partielle : `ApiController` hérite de
  `ApiKernel` → les **deux** fichiers doivent être déployés ensemble.
- Droits d'écriture sur `uploads/` ou `storage/` insuffisants.

## Ce qui N'EST PAS un blocage de l'API
- La **page d'un quiz** qui demande de se connecter : c'est normal (réponses par membre).
- Un quiz **non affiché** sous l'article alors qu'il est en brouillon (`active = 0`) :
  `quizzesFor()` ne montre que les quiz **publiés**.

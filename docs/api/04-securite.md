# 04 — Sécurité de l'API

L'API étant un point d'écriture exposé publiquement, plusieurs garde-fous sont en place.

## 1. On ne peut RIEN modifier sans la clé

- Les deux écritures (`createArticle`, `createQuiz`) appellent **`requireKey()` en tout
  premier**. Sans clé valide → **401**, et **aucune ligne n'est créée**.
- `ping` est le seul point public, et il **ne modifie rien**.
- La comparaison de clé utilise **`hash_equals()`** → temps constant, immunisé contre les
  attaques temporelles (on ne peut pas deviner la clé caractère par caractère).
- La clé est **régénérable** depuis `admin/settings` (bouton « régénérer »). Régénérer
  **révoque** l'ancienne immédiatement (`Settings::get('api_key')` devient la seule valable).
- Les écritures par l'**interface web** (`articles/save`, `quiz/save`, `admin/*`) sont,
  elles, protégées par la **session** (membre/admin) — un canal indépendant de la clé API.

## 2. Pas d'injection SQL

- **100 % des requêtes des modèles utilisent des requêtes préparées** (PDO + paramètres
  liés `?`). Aucune entrée utilisateur n'est concaténée dans une chaîne SQL.
- Seule exception apparente : `Article::announcements()` interpole `LIMIT $limit` — mais
  `$limit` est un **`int` typé** *et* borné par `max(1, min(20, $limit))`. Non injectable.
- `ApiKernel` ne construit **aucune** requête SQL : il délègue tout aux modèles.

## 3. Pas d'injection HTML / XSS

- Le **contenu HTML des articles** est filtré par **`Html::clean`** : liste blanche de
  balises sûres (`p, div, span, br, b, strong, i, em, u, h2, h3, ul, ol, li, a, blockquote`),
  suppression de `script` / `style` / `iframe`, retrait de tous les attributs sauf un `href`
  sûr et quelques styles inline contrôlés (`text-align`, `font-size`, `font-family`).
- Le **texte des quiz** (titre, énoncés, options, description) est stocké **sans balise**
  (`strip_tags`) côté API, **et** échappé (`htmlspecialchars`) à l'affichage.
- Les **noms d'auteur** sont nettoyés (`text()` : strip_tags + trim + longueur max).

## 4. Validation et robustesse

- Méthode HTTP **stricte** : routes d'écriture en **POST** uniquement.
- Validation **avant** écriture : un appel invalide renvoie 422 **sans rien créer**.
- Les **uploads d'images** passent par `Upload` (contrôle de type/format) ; une image
  refusée n'interrompt pas la création de l'article.
- `parent_id` / `article_id` ne sont utilisés que s'ils **existent réellement** en base.

## 5. Recommandations d'exploitation

- **Garder la clé secrète** : ne pas la committer en clair, la régénérer si elle fuite.
- Servir l'API **uniquement en HTTPS** (déjà le cas en prod).
- Optionnel : limiter l'accès à `/api/*` par IP au niveau du serveur si l'API n'est
  utilisée que depuis quelques machines.

## Checklist de revue rapide

- [x] Écriture impossible sans clé valide (401) — testé.
- [x] Comparaison de clé à temps constant (`hash_equals`).
- [x] Requêtes préparées partout (pas d'injection SQL).
- [x] HTML des articles filtré, texte des quiz échappé (pas de XSS stocké).
- [x] Validation avant écriture (422 sans effet de bord).

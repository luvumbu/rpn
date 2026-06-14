# 01 — Comment l'API fonctionne

## Principe général

L'API suit le même routeur que le reste du site (front controller `index.php` +
`config/routes.php`). Chaque appel passe par un **contrôleur** (`ApiController`) qui
hérite du socle commun **`ApiKernel`** (voir [`05-architecture-mvc.md`](05-architecture-mvc.md)).

Le cycle d'une requête d'écriture est toujours le même :

```
Requête HTTP
   │
   ▼
1. readInput()     → lit le corps (JSON ou formulaire)
2. requireKey()    → vérifie la clé API   ──►  401 si absente / invalide  (STOP)
3. validation      → champs requis présents ? ──►  422 si non  (STOP, rien créé)
4. nettoyage       → strip_tags / Html::clean (anti-XSS)
5. écriture        → modèles en requêtes préparées (PDO)
   │
   ▼
Réponse JSON  { ok:true, … }  (201 Created)
```

## Authentification

La clé est transmise de l'une de ces façons (par ordre de priorité) :

1. en-tête **`X-API-Key: …`** *(recommandé)* ;
2. champ **`key`** dans le corps JSON ;
3. champ `key` en `$_POST` ou `?key=` en query.

La comparaison se fait avec **`hash_equals`** (temps constant, anti-timing-attack).

## Format des requêtes

Deux formats acceptés pour le corps :

- **JSON** : `Content-Type: application/json` *(recommandé)* ;
- **formulaire** : `application/x-www-form-urlencoded` ou `multipart/form-data`
  (obligatoire pour envoyer un **fichier** image en upload direct).

## Format des réponses

Toujours du JSON.

| Cas | Forme |
|-----|-------|
| Succès | `{ "ok": true, … }` + code **200** (ping) ou **201** (création) |
| Erreur | `{ "ok": false, "error": "message" }` + code 401 / 422 / 404 / 500 |

## Les points d'entrée

### `GET /rpn/api/ping`
Test de disponibilité. **Public** (aucune clé, ne modifie rien).
```json
{ "ok": true, "service": "RPN API", "date": "2026-06-11T09:15:55+02:00" }
```

### `POST /rpn/api/article`
Crée un article. **Clé requise.**

| Champ | Type | Obligatoire | Détail |
|-------|------|:-----------:|--------|
| `title` | string | ✅ | titre (≤ 255) |
| `content` | string (HTML) | ✅ | filtré par `Html::clean` (balises sûres seulement) |
| `template` | string | | mise en page (défaut `standard`) |
| `active` | 0/1 | | 1 = publié (défaut), 0 = brouillon |
| `author_name` | string | | auteur affiché (défaut `RPN`) |
| `parent_id` | int | | rattache à un article parent existant |
| `image_url` | string | | couverture téléchargée depuis une URL |
| `gallery_urls` | array | | URLs d'images de galerie |
| `cover`, `photos[]` | fichier | | uploads directs (multipart) |

Réponse `201` :
```json
{ "ok": true, "id": 16, "active": 1, "template": "standard",
  "url": "https://bokonzi.com/rpn/article?id=16" }
```

### `POST /rpn/api/quiz`
Crée un questionnaire, et le **lie à un article** si `article_id` est fourni. **Clé requise.**

| Champ | Type | Obligatoire | Détail |
|-------|------|:-----------:|--------|
| `title` | string | ✅ | titre (≤ 200) |
| `questions` | array | ✅ | voir ci-dessous |
| `description` | string | | présentation |
| `active` | 0/1 | | 1 = publié (défaut) |
| `author_name` | string | | défaut `RPN` |
| `article_id` | int | | rattache le quiz à cet article |
| `max_attempts` | int | | 0 = illimité (défaut) |

Chaque **question** :
```json
{
  "body": "Énoncé de la question",
  "type": "single",            // 'single' (1 bonne réponse) ou 'multiple'
  "options": [
    { "label": "Réponse A", "correct": true },
    { "label": "Réponse B", "correct": false }
  ]
}
```
Une question est **ignorée** si : énoncé vide, **moins de 2 options**, ou **aucune
bonne réponse** cochée. Si au final aucune question valide → `422` et le quiz est annulé.

Réponse `201` :
```json
{ "ok": true, "quiz_id": 9, "questions": 12, "options": 48,
  "linked_article": 14, "active": 1,
  "url": "https://bokonzi.com/rpn/quiz/show?id=9" }
```

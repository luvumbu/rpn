# 02 — Routes (« liens ») de l'API et lien article ↔ quiz

## Les routes de l'API

Définies dans `config/routes.php`, section *« API JSON »* :

```php
$router->get('api/ping',     'ApiController', 'ping');          // test (public)
$router->post('api/article', 'ApiController', 'createArticle'); // crée un article
$router->post('api/quiz',    'ApiController', 'createQuiz');    // crée un quiz (+ lien article)
```

| Méthode | Lien (URL) | Action | Auth |
|---------|------------|--------|------|
| `GET`  | `/rpn/api/ping`    | `ApiController::ping`          | — (public) |
| `POST` | `/rpn/api/article` | `ApiController::createArticle` | clé API |
| `POST` | `/rpn/api/quiz`    | `ApiController::createQuiz`    | clé API |

> ⚠️ **Méthode HTTP stricte** : `api/article` et `api/quiz` ne sont déclarées qu'en
> **POST**. Un `GET` sur ces URLs n'est associé à aucune route → page introuvable.
> C'est volontaire : une écriture ne doit jamais se faire en GET.

> ℹ️ Les URLs sont « propres » (sans `.php`) grâce au routeur. Chercher
> `/rpn/api/article.php` renvoie donc une 404 : la bonne adresse est `/rpn/api/article`.

## Le lien article ↔ quiz

Un quiz **n'appartient pas** à un article : le lien passe par une **table de jointure**,
ce qui permet d'associer un quiz à plusieurs articles (ou aucun).

```
article_quizzes ( article_id , quiz_id , position )
```

Côté code, tout est dans le modèle `Article` :

| Méthode | Rôle |
|---------|------|
| `Article::quizIds($articleId)`        | ids des quiz déjà liés à l'article |
| `Article::setQuizzes($articleId, $ids)` | (re)définit la liste des quiz liés |
| `Article::quizzesFor($articleId)`     | quiz **publiés** d'un article (pour l'affichage) |

### Comment l'API crée le lien

Quand `POST /rpn/api/quiz` reçoit un champ **`article_id`** valide, il :

1. crée le quiz ;
2. récupère les liens existants : `Article::quizIds($articleId)` ;
3. y ajoute le nouveau quiz ;
4. enregistre : `Article::setQuizzes($articleId, $ids)` *(les liens existants sont conservés)*.

La page de l'article affiche alors automatiquement le bloc
**« Tu as terminé la lecture ? Réponds au questionnaire ci-dessous »**
avec un lien vers `/rpn/quiz/show?id=…`.

### Associer un quiz à n'importe quel article

Il suffit de mettre l'id voulu dans la requête :

```json
POST /rpn/api/quiz       (en-tête X-API-Key)
{ "title": "…", "article_id": 11, "questions": [ … ] }
```

→ le quiz est créé **et** rattaché à l'article 11, sans rien casser des autres liens.

## Quiz et accès membre

La **page d'un quiz** (`/rpn/quiz/show`) demande une **connexion membre** pour y répondre
(les scores sont enregistrés par membre). En revanche le **bloc d'invitation** apparaît
publiquement sous l'article. C'est le comportement attendu, pas un blocage de l'API.

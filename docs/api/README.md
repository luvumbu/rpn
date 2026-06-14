# API RPN — Documentation complète

L'**API JSON** de RPN permet de piloter le site à distance : créer des **articles**
et des **questionnaires (quiz)**, sans passer par l'interface web. Elle est par
exemple utilisée pour demander à un assistant de rédiger un cours et de le publier
directement, avec son quiz associé.

> **Base URL (production)** : `https://bokonzi.com/rpn`
> **Authentification** : clé API dans l'en-tête `X-API-Key`

## Sommaire du dossier

| Fichier | Contenu |
|---------|---------|
| [`01-fonctionnement.md`](01-fonctionnement.md) | Comment l'API marche : points d'entrée, requêtes, réponses, exemples |
| [`02-routes-et-liens.md`](02-routes-et-liens.md) | Toutes les routes (« liens ») de l'API + le lien **article ↔ quiz** |
| [`03-blocages.md`](03-blocages.md) | **Pourquoi l'API peut bloquer** (401 / 422 / 404 / 500) et comment débloquer |
| [`04-securite.md`](04-securite.md) | Injections SQL, anti-XSS, permission par clé, bonnes pratiques |
| [`05-architecture-mvc.md`](05-architecture-mvc.md) | Le socle commun `ApiKernel` (refactor MVC, fin des duplications) |
| [`06-tests.md`](06-tests.md) | Lancer les tests unitaires et d'intégration |

## En 30 secondes

```bash
# 1. Tester que l'API répond (public)
curl https://bokonzi.com/rpn/api/ping
# → {"ok":true,"service":"RPN API","date":"…"}

# 2. Créer un article (clé requise)
curl -X POST https://bokonzi.com/rpn/api/article \
  -H "X-API-Key: VOTRE_CLE" -H "Content-Type: application/json" \
  -d '{"title":"Mon article","content":"<p>Bonjour</p>"}'

# 3. Créer un quiz lié à l'article 15
curl -X POST https://bokonzi.com/rpn/api/quiz \
  -H "X-API-Key: VOTRE_CLE" -H "Content-Type: application/json" \
  -d '{"title":"Mon quiz","article_id":15,"questions":[
        {"body":"2+2 ?","type":"single","options":[
          {"label":"4","correct":true},{"label":"5","correct":false}]}]}'
```

## La clé API

- Référence : réglage **`api_key`** (régénérable depuis `admin/settings`) ; à défaut,
  la constante **`API_KEY`** de `config/config.php`.
- **Régénérer la clé révoque immédiatement l'ancienne.**
- Voir [`04-securite.md`](04-securite.md) pour les détails.

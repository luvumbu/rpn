# 05 — Architecture MVC et socle commun `ApiKernel`

## Le problème (avant)

Les deux actions de l'API (`createArticle`, `createQuiz`) **recopiaient** la même
plomberie : lecture du corps JSON, helper d'accès aux champs, réponse JSON, vérification
de la clé, construction d'URL, téléchargement d'image. Du code dupliqué = plus difficile à
lire, à maintenir, et à sécuriser (il faut penser à corriger partout).

## La solution (après) : une classe de base

On regroupe **toutes les fonctions communes** dans une classe socle
**`app/core/ApiKernel.php`**, dont héritent les contrôleurs d'API.

```
ApiKernel (app/core/)         ← logique transversale, une seule fois
   ▲
   │ extends
ApiController (app/controllers/)
   ├── ping()           → uniquement la logique métier
   ├── createArticle()  → uniquement la logique métier
   └── createQuiz()      → uniquement la logique métier
```

### Ce que fournit `ApiKernel`

| Méthode | Rôle |
|---------|------|
| `readInput()` | lit **une fois** le corps JSON (idempotent) |
| `field($k, $def)` | valeur d'un champ : corps JSON → `$_POST` → défaut |
| `text($k, $max)` | champ texte propre : `strip_tags` + `trim` + troncature |
| `json($data, $code)` | réponse JSON + arrêt |
| `fail($msg, $code)` | erreur normalisée `{ ok:false, error }` |
| `requireKey()` | **sécurité** : exige une clé valide, sinon 401 |
| `absoluteUrl($route, $query)` | URL absolue vers une route interne |
| `downloadImage($url, $folder)` | télécharge et enregistre une image distante |

### Respect du modèle MVC

- **Modèle** : `Article`, `Quiz`, `ArticleImage` (accès aux données, requêtes préparées).
- **Vue** : non concernée ici (l'API renvoie du JSON, pas du HTML).
- **Contrôleur** : `ApiController` orchestre ; `ApiKernel` est la **couche transversale**
  partagée par les contrôleurs d'API (réponses, sécurité, parsing).
- L'**autoload** (`app/bootstrap.php`) charge automatiquement `ApiKernel` depuis `core/`.

## Ajouter un nouvel endpoint d'API

C'est désormais trivial — on hérite de tout le socle :

```php
class ApiController extends ApiKernel
{
    public function createMachin(): void
    {
        $this->readInput();
        $this->requireKey();                       // sécurité héritée
        $nom = $this->text('nom', 100);            // parsing hérité
        if ($nom === '') { $this->fail('nom requis', 422); }
        // … $id = Machin::create([...]);          // modèle (requête préparée)
        $this->json(['ok' => true, 'id' => $id,
            'url' => $this->absoluteUrl('machin', ['id' => $id])], 201);
    }
}
```

Puis une ligne dans `config/routes.php` :
```php
$router->post('api/machin', 'ApiController', 'createMachin');
```

## Fichiers concernés par le refactor

| Fichier | État |
|---------|------|
| `app/core/ApiKernel.php` | **nouveau** — le socle commun |
| `app/controllers/ApiController.php` | **réécrit** — hérite d'`ApiKernel`, sans duplication |
| `config/routes.php` | inchangé (routes API déjà en place) |

> ⚠️ **Déploiement** : `ApiController` hérite d'`ApiKernel`. Les deux fichiers doivent
> être mis en ligne **ensemble**, sinon « class ApiKernel not found » (erreur 500).

# 06 — Tests

Deux niveaux de tests accompagnent l'API.

## 1. Tests unitaires (sans base de données)

Fichier : **`tests/ApiKernelTest.php`**. Ils vérifient la logique pure du socle
`ApiKernel` en remplaçant ses dépendances par des stubs (pas de DB, pas de réseau).

```bash
php tests/ApiKernelTest.php
```

Ce qui est testé :
- `field()` lit `$_POST` puis le défaut ;
- `text()` retire le HTML, trim et tronque (anti-XSS d'entrée) ;
- `absoluteUrl()` construit bien `https://host/rpn/route?query` ;
- **sécurité de la clé** : absente → 401, mauvaise → 401, **presque** correcte → 401,
  bonne (en-tête `X-API-Key`) → acceptée, bonne (`?key=`) → acceptée.

Résultat attendu :
```
Résultat : 10 réussi(s), 0 échec(s)
```

## 2. Tests d'intégration (contre le serveur)

Fichier : **`tests/api_integration.ps1`** (PowerShell + `curl`). Ils frappent l'API réelle
**sans créer de données parasites** : seuls des cas d'erreur (401 / 422) et le `ping` sont
exercés.

```powershell
powershell -File tests/api_integration.ps1
# ou en ciblant une autre base / clé :
powershell -File tests/api_integration.ps1 -Base https://bokonzi.com/rpn -Key VOTRE_CLE
```

Ce qui est testé :
- `GET api/ping` → `{ ok:true }` ;
- `POST api/article` **sans** clé → 401 ;
- `POST api/quiz` **sans** clé → 401 ;
- `POST api/article` **mauvaise** clé → 401 ;
- `POST api/article` bonne clé, **sans champs** → 422 (rien créé) ;
- `POST api/quiz` bonne clé, **sans questions** → 422 (rien créé).

Résultat attendu :
```
Résultat : 6 reussi(s), 0 echec(s)
```

## Quand les lancer

| Moment | Test |
|--------|------|
| Après modification d'`ApiKernel` / `ApiController` | unitaires (`php tests/ApiKernelTest.php`) |
| **Avant** de mettre en ligne | unitaires + `php -l` sur les fichiers changés |
| **Après** déploiement FTP | intégration (`api_integration.ps1`) → doit rester **vert** |

> Les tests d'intégration ne créant que des cas d'erreur, ils peuvent être relancés
> autant de fois que voulu sans polluer la base.

<?php
/**
 * Tests unitaires de la classe ApiKernel (socle commun de l'API).
 * Exécution :  php tests/ApiKernelTest.php
 *
 * Aucune base de données requise : on remplace (stubs) les dépendances
 * externes (url(), Settings, Upload) et on vérifie la logique pure :
 * lecture de champ, nettoyage de texte, URL absolue, et surtout la SÉCURITÉ
 * de la clé API (acceptée / refusée / temps constant).
 */

error_reporting(E_ALL);

// ---- Stubs des dépendances (remplacent le vrai framework) -------------------
if (!defined('API_KEY')) {
    define('API_KEY', 'rpmapi_TESTKEY_0123456789');
}
if (!function_exists('url')) {
    function url(string $path = ''): string { return '/rpn/' . ltrim($path, '/'); }
}
class Settings { public static function get($k, $d = null) { return $d; } } // pas de clé en base → fallback API_KEY
class Upload   { public static function imageFromPath($p, $f) { return null; } }

require __DIR__ . '/../app/core/ApiKernel.php';

// ---- Sous-classe de test : expose les méthodes protégées et neutralise exit ----
class ApiStop extends Exception
{
    public int $httpCode; public array $payload;
    public function __construct(int $httpCode, array $payload) { $this->httpCode = $httpCode; $this->payload = $payload; parent::__construct('stop'); }
}
class TestApi extends ApiKernel
{
    protected function json(array $data, int $code = 200): void { throw new ApiStop($code, $data); }
    public function pField($k, $d = '') { return $this->field($k, $d); }
    public function pText($k, $m = 0)   { return $this->text($k, $m); }
    public function pUrl($r, $q = [])   { return $this->absoluteUrl($r, $q); }
    public function pRequireKey(): void { $this->requireKey(); }
}

// ---- Mini-harnais d'assertions ----------------------------------------------
$passed = 0; $failed = 0;
function check(string $label, bool $cond) {
    global $passed, $failed;
    if ($cond) { $passed++; echo "  [OK]   $label\n"; }
    else       { $failed++; echo "  [FAIL] $label\n"; }
}
function expectKeyRejected(TestApi $api, string $label) {
    try { $api->pRequireKey(); check($label . ' (devrait être refusée)', false); }
    catch (ApiStop $e) { check($label, $e->httpCode === 401 && ($e->payload['ok'] ?? true) === false); }
}
function expectKeyAccepted(TestApi $api, string $label) {
    try { $api->pRequireKey(); check($label, true); }
    catch (ApiStop $e) { check($label . ' (rejetée à tort, code ' . $e->httpCode . ')', false); }
}

echo "== Tests ApiKernel ==\n";

// 1) field() : $_POST puis défaut
$_POST = ['title' => 'Bonjour']; $_GET = []; $_SERVER['CONTENT_TYPE'] = '';
$api = new TestApi();
check('field() lit \$_POST',            $api->pField('title') === 'Bonjour');
check('field() renvoie le défaut',      $api->pField('absent', 'def') === 'def');

// 2) text() : retire les balises, espaces, et tronque
$_POST = ['t' => '  <b>Hack</b>  '];
$api = new TestApi();
check('text() retire le HTML + trim',   $api->pText('t') === 'Hack');
$_POST = ['t' => 'abcdefghij'];
$api = new TestApi();
check('text() tronque à la longueur',   $api->pText('t', 4) === 'abcd');

// 3) absoluteUrl()
$_SERVER['HTTPS'] = 'on'; $_SERVER['HTTP_HOST'] = 'bokonzi.com';
$api = new TestApi();
check('absoluteUrl() construit https',  $api->pUrl('article', ['id' => 5]) === 'https://bokonzi.com/rpn/article?id=5');

// 4) SÉCURITÉ — clé API
$_POST = []; $_GET = [];
// 4a. aucune clé → refusée
unset($_SERVER['HTTP_X_API_KEY']);
expectKeyRejected(new TestApi(), 'clé absente → 401');
// 4b. mauvaise clé → refusée
$_SERVER['HTTP_X_API_KEY'] = 'mauvaise-cle';
expectKeyRejected(new TestApi(), 'mauvaise clé → 401');
// 4c. bonne clé (en-tête) → acceptée
$_SERVER['HTTP_X_API_KEY'] = API_KEY;
expectKeyAccepted(new TestApi(), 'bonne clé (en-tête X-API-Key) → acceptée');
// 4d. bonne clé via $_GET → acceptée
unset($_SERVER['HTTP_X_API_KEY']); $_GET = ['key' => API_KEY];
expectKeyAccepted(new TestApi(), 'bonne clé (paramètre ?key=) → acceptée');
// 4e. clé presque correcte (1 caractère en trop) → refusée
$_GET = []; $_SERVER['HTTP_X_API_KEY'] = API_KEY . 'x';
expectKeyRejected(new TestApi(), 'clé presque correcte → 401');

echo "\nRésultat : $passed réussi(s), $failed échec(s)\n";
exit($failed === 0 ? 0 : 1);

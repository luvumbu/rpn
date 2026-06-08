<?php
/**
 * CLASSE Router
 * Aiguille chaque requête vers le bon contrôleur selon l'URL et la méthode HTTP.
 * Toutes les pages passent par index.php (front controller) : on enregistre ici
 * les routes (voir config/routes.php), puis dispatch() trouve la bonne action.
 */
class Router
{
    /** @var array<string, array<string, array{0:string,1:string}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    /** Enregistre une route GET : url('chemin') → Controller@action. */
    public function get(string $path, string $controller, string $action): void
    {
        $this->routes['GET'][trim($path, '/')] = [$controller, $action];
    }

    /** Enregistre une route POST (formulaires). */
    public function post(string $path, string $controller, string $action): void
    {
        $this->routes['POST'][trim($path, '/')] = [$controller, $action];
    }

    /** Extrait la route courante de l'URL (retire BASE_PATH et la query string). */
    public static function currentRoute(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        if (BASE_PATH !== '' && strpos($path, BASE_PATH) === 0) {
            $path = substr($path, strlen(BASE_PATH));
        }
        return trim($path, '/');
    }

    /** Aiguille selon la méthode HTTP et l'URL complète demandée. */
    public function dispatch(string $method, string $uri): void
    {
        $route = self::currentRoute($uri);

        // GARDE GLOBAL : en ligne, tant que la base n'est PAS configurée, on
        // force la page d'installation — PEU IMPORTE la page demandée. Seules
        // exceptions : le login admin (la config elle-même) et la déconnexion.
        if (Database::needsSetup() && !in_array($route, ['admin/login', 'admin/logout'], true)) {
            Database::installPage();
        }

        // GARDE « QUESTIONNAIRE OBLIGATOIRE » : un membre (hors admin) qui a un
        // questionnaire obligatoire non terminé est renvoyé dessus et ne peut
        // accéder à rien d'autre — sauf y répondre (quiz/show, quiz/submit) ou
        // se déconnecter. Les admins ne sont jamais bloqués.
        if (Session::has('user') && !Session::isAdmin()) {
            $allowed = ['logout', 'google/callback', 'quiz/show', 'quiz/submit'];
            if (!in_array($route, $allowed, true)) {
                $pending = Quiz::firstPendingRequired((int) (Session::user()['id'] ?? 0));
                if ($pending) {
                    redirect('quiz/show?id=' . (int) $pending['id'] . '&forced=1');
                }
            }
        }

        $this->run(strtoupper($method), $route);
    }

    /** Exécute une route précise (méthode + chemin déjà normalisés). */
    public function run(string $method, string $route): void
    {
        $handler = $this->routes[$method][$route] ?? null;

        if ($handler === null) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        [$controller, $action] = $handler;
        (new $controller())->{$action}();
    }
}

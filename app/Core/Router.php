<?php
/**
 * AssetFlow — Simple URL Router
 */

class Router
{
    private static array $routes = [];

    /**
     * Register a GET route
     */
    public static function get(string $path, string $controller, string $action): void
    {
        self::$routes[] = [
            'method'     => 'GET',
            'path'       => $path,
            'controller' => $controller,
            'action'     => $action,
        ];
    }

    /**
     * Register a POST route
     */
    public static function post(string $path, string $controller, string $action): void
    {
        self::$routes[] = [
            'method'     => 'POST',
            'path'       => $path,
            'controller' => $controller,
            'action'     => $action,
        ];
    }

    /**
     * Dispatch the current request to the matching route
     */
    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = self::matchRoute($route['path'], $uri);
            if ($params !== false) {
                $controllerName = $route['controller'];
                $actionName = $route['action'];

                $controllerFile = APP_PATH . '/Controllers/' . $controllerName . '.php';
                if (!file_exists($controllerFile)) {
                    self::error(500, "Controller not found: {$controllerName}");
                    return;
                }

                require_once $controllerFile;
                $controller = new $controllerName();

                if (!method_exists($controller, $actionName)) {
                    self::error(500, "Action not found: {$controllerName}::{$actionName}");
                    return;
                }

                call_user_func_array([$controller, $actionName], $params);
                return;
            }
        }

        self::error(404, 'Page not found');
    }

    /**
     * Match a route pattern against a URI
     * Returns array of params on match, false on no match
     * Supports {param} placeholders
     */
    private static function matchRoute(string $pattern, string $uri): array|false
    {
        // Convert route pattern to regex
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Extract only named params
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[] = $value;
                }
            }
            return $params;
        }

        return false;
    }

    /**
     * Display an error page
     */
    public static function error(int $code, string $message = ''): void
    {
        http_response_code($code);
        $errorView = APP_PATH . '/Views/errors/' . $code . '.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<div style='text-align:center;padding:60px;font-family:Inter,sans-serif;'>";
            echo "<h1 style='font-size:72px;color:#7C83FD;margin:0;'>{$code}</h1>";
            echo "<p style='font-size:18px;color:#636E72;'>" . htmlspecialchars($message) . "</p>";
            echo "<a href='/' style='color:#7C83FD;'>← Back to Dashboard</a>";
            echo "</div>";
        }
    }
}

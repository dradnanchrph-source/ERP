<?php
class Router {
    private static array $routes = [];

    public static function add(string $method, string $pattern, string $controller, string $action): void {
        self::$routes[] = compact('method','pattern','controller','action');
    }

    public static function dispatch(string $url): void {
        require_once APP . '/routes.php';

        $method = $_SERVER['REQUEST_METHOD'];
        $url    = strtok($url, '?') ?: '';

        foreach (self::$routes as $route) {
            if (!in_array($method, explode('|', $route['method']))) continue;

            // Convert :param to regex
            $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $route['pattern']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, '/' . $url, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Check main dir and subdirs
                $ctrlFile = APP . '/Controllers/' . $route['controller'] . '.php';
                if (!file_exists($ctrlFile)) {
                    // Search subdirectories
                    $found = false;
                    foreach (['Purchase', 'Sales', 'BP'] as $subDir) {
                        $sub = APP . '/Controllers/' . $subDir . '/' . $route['controller'] . '.php';
                        if (file_exists($sub)) { $ctrlFile = $sub; $found = true; break; }
                    }
                    if (!$found) {
                        http_response_code(500);
                        echo "Controller not found: {$route['controller']}"; exit;
                    }
                }
                require_once $ctrlFile;

                $ctrl = new $route['controller']();
                $action = $route['action'];
                $ctrl->$action(...array_values($params));
                return;
            }
        }

        // 404
        http_response_code(404);
        echo '<!DOCTYPE html><html><head><title>404</title>
        <style>body{font:16px sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
        .box{text-align:center}.num{font-size:5rem;font-weight:900;color:#4f46e5}.msg{color:#94a3b8}</style></head>
        <body><div class="box"><div class="num">404</div><div class="msg">Page not found</div>
        <br><a href="/" style="color:#818cf8">← Go Home</a></div></body></html>';
    }
}

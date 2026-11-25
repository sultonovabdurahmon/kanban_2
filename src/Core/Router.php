<?php
declare(strict_types=1);

final class Router
{
    /** @var array<int, array{string, string, array}> */
    private array $routes = [];

    public function get(string $path, array $handler): void  { $this->routes[] = ['GET', $path, $handler]; }
    public function post(string $path, array $handler): void { $this->routes[] = ['POST', $path, $handler]; }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        // Агар лоиҳа дар зерпапка бошад (XAMPP: /kanban/public), онро аз масир мебарем
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }
            // {id} танҳо рақамҳоро қабул мекунад
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(\d+)', $pattern) . '$#';
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches);
                [$class, $action] = $handler;
                (new $class())->$action(...array_map('intval', $matches));
                return;
            }
        }
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo (new View(BASE_PATH . '/views'))->render('errors/error', [
            'title' => 'Саҳифа ёфт нашуд', 'code' => 404, 'message' => 'Чунин саҳифа вуҷуд надорад.',
        ]);
    }
}

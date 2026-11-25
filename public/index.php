<?php
// public/index.php — front controller: ягона нуқтаи вуруд ба барнома
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Автобор: синф дар ҷузвдони дахлдор ҷустуҷӯ мешавад
spl_autoload_register(function (string $class): void {
    foreach (['Core', 'Controllers', 'Repositories'] as $dir) {
        $file = BASE_PATH . "/src/{$dir}/{$class}.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    $configFile = BASE_PATH . '/config/config.example.php'; // қиматҳои пешфарз барои XAMPP
}
$config = require $configFile;
ini_set('display_errors', $config['debug'] ? '1' : '0');
error_reporting(E_ALL);
Database::configure($config['db']);

// Ҳар истиснои дастгирнашуда → саҳифаи 500 (тафсилот — танҳо дар реҷаи debug)
set_exception_handler(function (Throwable $e) use ($config): void {
    error_log((string) $e);
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo (new View(BASE_PATH . '/views'))->render('errors/error', [
        'title'   => 'Хатои дохилии сервер',
        'code'    => 500,
        'message' => $config['debug'] ? $e->getMessage() : 'Баъдтар кӯшиш кунед ё ба администратор муроҷиат намоед.',
    ]);
});

$router = new Router();
$router->get('/', [DashboardController::class, 'index']);
// Масири хусуситар пеш аз /tasks/{id}
$router->get('/tasks', [TaskController::class, 'index']);
$router->get('/tasks/create', [TaskController::class, 'create']);
$router->post('/tasks/create', [TaskController::class, 'store']);
$router->get('/tasks/{id}', [TaskController::class, 'show']);
$router->get('/tasks/{id}/edit', [TaskController::class, 'edit']);
$router->post('/tasks/{id}/edit', [TaskController::class, 'update']);
$router->post('/tasks/{id}/delete', [TaskController::class, 'delete']);   // нест кардан — танҳо POST
$router->get('/reports/projects', [ReportController::class, 'projects']);
$router->get('/users', [UserController::class, 'index']);
$router->post('/users/{id}/delete', [UserController::class, 'delete']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

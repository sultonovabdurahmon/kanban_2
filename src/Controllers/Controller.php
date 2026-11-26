<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

abstract class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View(dirname(__DIR__, 2) . '/views');
    }

    protected function render(string $template, array $data = [], int $status = 200, string $layout = 'layout'): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $this->view->render($template, $data, $layout);
    }

    /** Равонакунии дубора (HTTP 302) — пас аз фиристодани бомуваффақияти шакл (шаблони PRG) */
    protected function redirect(string $path): never
    {
        header('Location: ' . $this->view->url($path));
        http_response_code(302);
        exit;
    }

    /** Ҷавоб дар формати JSON (барои API) */
    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Саҳифаи хато (403, 404, …) ва анҷоми скрипт */
    protected function abort(int $code, string $message = ''): never
    {
        $titles = [403 => 'Дастрасӣ рад шуд', 404 => 'Ёфт нашуд', 500 => 'Хатои дохилии сервер'];
        $this->render('errors/error', [
            'title' => $titles[$code] ?? 'Хато', 'code' => $code,
            'message' => $message !== '' ? $message : 'Амал иҷро нашуд.',
        ], $code);
        exit;
    }
}

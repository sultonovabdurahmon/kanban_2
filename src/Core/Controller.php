<?php
declare(strict_types=1);

abstract class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View(BASE_PATH . '/views');
    }

    protected function render(string $template, array $vars = [], int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        echo $this->view->render($template, $vars);
    }

    /** PRG: пас аз POST-и бомуваффақият равонакунии 302 ба саҳифаи GET */
    protected function redirect(string $path): never
    {
        header('Location: ' . $this->view->url($path), true, 302);
        exit;
    }

    protected function notFound(string $message = 'Сабт ёфт нашуд.'): never
    {
        $this->render('errors/error', ['title' => 'Ёфт нашуд', 'code' => 404, 'message' => $message], 404);
        exit;
    }
}

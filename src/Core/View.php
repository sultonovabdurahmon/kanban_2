<?php
declare(strict_types=1);

final class View
{
    public function __construct(private string $dir)
    {
    }

    /** Шаблонро дар дохили layout.php мекашад */
    public function render(string $template, array $vars = []): string
    {
        $content = $this->capture($template, $vars);
        return $this->capture('layout', $vars + ['content' => $content]);
    }

    private function capture(string $template, array $vars): string
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        require "{$this->dir}/{$template}.php";
        return (string) ob_get_clean();
    }

    /** Экранкунии HTML (ҳимоя аз XSS) */
    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Суроғаи дохилӣ бо назардошти зерпапка */
    public function url(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        return $base . $path;
    }
}

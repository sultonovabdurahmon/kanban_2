<?php
declare(strict_types=1);

/** Як пайвастшавӣ ба ПД барои тамоми дархост; ҳангоми муроҷиати аввал эҷод мешавад. */
final class Database
{
    private static ?PDO $pdo = null;
    private static array $config = [];

    /** Дар public/index.php як маротиба даъват карда мешавад */
    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $c = self::$config;
            // charset=utf8mb4 — вагарна ҳарфҳои кириллӣ ба «????» табдил меёбанд
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // хатоҳои SQL → истисноҳо
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // сатрҳо ҳамчун массивҳои ассотсиативӣ
                PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statements-и воқеӣ
            ]);
        }
        return self::$pdo;
    }
}

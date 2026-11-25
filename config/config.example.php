<?php
// Нусха гиред: copy config\config.example.php config\config.php — config.php ба Git ДОХИЛ НАМЕШАВАД
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'kanban',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',          // дар XAMPP пароли root холӣ аст
    ],
    // true — тафсилоти хатоҳоро нишон додан (танҳо ҳангоми коркард!)
    // getenv() бо «0» дар ?: нодуруст кор мекунад ('0' — ҳолати «ғалат»), барҷаста санҷида мешавад
    'debug' => (getenv('APP_DEBUG') === false ? '1' : getenv('APP_DEBUG')) === '1',
];

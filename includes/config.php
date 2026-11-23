<?php
// ============ Танзимоти пойгоҳи додаҳо (XAMPP) ============
const DB_HOST = 'localhost';
const DB_NAME = 'kanban_db';
const DB_USER = 'root';
const DB_PASS = '';

session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => true,
            ]);
        } catch (PDOException $ex) {
            http_response_code(500);
            die('Ба пойгоҳи додаҳо пайваст нашуд. Файли database/kanban_db.sql-ро дар phpMyAdmin Import кунед. (' . htmlspecialchars($ex->getMessage()) . ')');
        }
    }
    return $pdo;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function user(): ?array { return $_SESSION['user'] ?? null; }
function uid(): int { return (int)($_SESSION['user']['id'] ?? 0); }

function require_login(): void {
    if (!user()) { header('Location: login.php'); exit; }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); die('Токени амният нодуруст аст.'); }
}

function is_owner(int $pid): bool {
    $st = db()->prepare('SELECT 1 FROM projects WHERE id = ? AND owner_id = ?');
    $st->execute([$pid, uid()]);
    return (bool)$st->fetchColumn();
}
function is_member(int $pid): bool {
    $st = db()->prepare('SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?');
    $st->execute([$pid, uid()]);
    return (bool)$st->fetchColumn();
}

function history_log(int $taskId, string $action, ?string $field = null, ?string $old = null, ?string $new = null): void {
    $st = db()->prepare('INSERT INTO task_history (task_id, user_id, action, field_name, old_value, new_value) VALUES (?,?,?,?,?,?)');
    $st->execute([$taskId, uid(), $action, $field, $old !== null ? mb_substr($old, 0, 250) : null, $new !== null ? mb_substr($new, 0, 250) : null]);
}

function initials(string $name): string {
    $parts = preg_split('/\s+/u', trim($name));
    $a = mb_substr($parts[0] ?? '', 0, 1);
    $b = mb_substr($parts[1] ?? '', 0, 1);
    return mb_strtoupper($a . $b);
}
function avatar(?int $id, ?string $name, string $cls = ''): string {
    if (!$id || !$name) return '<span class="avatar avatar-empty ' . $cls . '" title="Таъин нашудааст">?</span>';
    return '<span class="avatar ' . $cls . '" style="--h:' . (($id * 67) % 360) . '" title="' . e($name) . '">' . e(initials($name)) . '</span>';
}
function fmt_date(?string $d): string { return $d ? date('d.m.Y', strtotime($d)) : '—'; }

// Вазифаҳои мӯҳлаташон гузашта (сутуни охирин = иҷрошуда)
function overdue_sql(): string {
    return "FROM tasks t
            JOIN projects p ON p.id = t.project_id
            JOIN project_members pm ON pm.project_id = t.project_id AND pm.user_id = :uid
            JOIN columns c ON c.id = t.column_id
            LEFT JOIN users u ON u.id = t.assignee_id
            WHERE t.deadline IS NOT NULL AND t.deadline < CURDATE()
              AND c.position < (SELECT MAX(position) FROM columns WHERE project_id = t.project_id)
              AND (p.owner_id = :uid OR t.assignee_id = :uid)";
}
function overdue_count(): int {
    $st = db()->prepare('SELECT COUNT(*) ' . overdue_sql());
    $st->execute(['uid' => uid()]);
    return (int)$st->fetchColumn();
}

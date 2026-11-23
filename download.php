<?php
require __DIR__ . '/includes/config.php';
require_login();
$st = db()->prepare('SELECT a.*, t.project_id, t.assignee_id, p.owner_id
                     FROM task_attachments a JOIN tasks t ON t.id = a.task_id JOIN projects p ON p.id = t.project_id WHERE a.id = ?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$a = $st->fetch();
if (!$a || !is_member((int)$a['project_id']) || ((int)$a['owner_id'] !== uid() && (int)$a['assignee_id'] !== uid())) { http_response_code(404); die('Файл ёфт нашуд.'); }
$path = __DIR__ . '/' . $a['file_path'];
if (!is_file($path)) { http_response_code(404); die('Файл ёфт нашуд.'); }
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($a['file_name']));
header('Content-Length: ' . filesize($path));
readfile($path);

<?php
// API-и JSON: ҳамаи амалҳои канбан бе навсозии саҳифа (fetch)
require __DIR__ . '/includes/config.php';
header('Content-Type: application/json; charset=utf-8');

function out(array $d, int $code = 200): void { http_response_code($code); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function fail(string $m, int $c = 400): void { out(['ok' => false, 'error' => $m], $c); }

if (!user()) fail('Аввал ворид шавед.', 401);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$json = json_decode(file_get_contents('php://input') ?: '[]', true);
$in = $_POST + (is_array($json) ? $json : []);
if ($method === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) fail('Токени амният нодуруст аст. Саҳифаро навсозӣ кунед.', 419);

$pdo = db();

// Вазифа + санҷиши дастрасӣ (роҳбар ҳама, иштирокчӣ — танҳо вазифаҳои худ)
function task_ctx(int $id): array {
    $st = db()->prepare('SELECT t.*, p.owner_id FROM tasks t JOIN projects p ON p.id = t.project_id WHERE t.id = ?');
    $st->execute([$id]);
    $t = $st->fetch();
    if (!$t) fail('Вазифа ёфт нашуд.', 404);
    if (!is_member((int)$t['project_id'])) fail('Дастрасӣ нест.', 403);
    $t['is_owner'] = (int)$t['owner_id'] === uid();
    if (!$t['is_owner'] && (int)$t['assignee_id'] !== uid()) fail('Ин вазифа ба шумо таъин нашудааст.', 403);
    return $t;
}
function need_owner(int $pid): void { if (!is_owner($pid)) fail('Ин амал танҳо барои роҳбари лоиҳа аст.', 403); }
function clean_date($v): ?string {
    $v = trim((string)$v);
    if ($v === '') return null;
    $d = DateTime::createFromFormat('Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v) fail('Санаи мӯҳлат нодуруст аст.');
    return $v;
}
function clean_assignee($v, int $pid): ?int {
    if ($v === '' || $v === null || (int)$v === 0) return null;
    $st = db()->prepare('SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?');
    $st->execute([$pid, (int)$v]);
    if (!$st->fetchColumn()) fail('Иҷрокунанда иштирокчии лоиҳа нест.');
    return (int)$v;
}
function user_name(?int $id): string {
    if (!$id) return 'Таъин нашудааст';
    $st = db()->prepare('SELECT full_name FROM users WHERE id = ?'); $st->execute([$id]);
    return (string)($st->fetchColumn() ?: '—');
}
function resequence(int $colId): void {
    $ids = db()->prepare('SELECT id FROM tasks WHERE column_id = ? ORDER BY position, id');
    $ids->execute([$colId]);
    $up = db()->prepare('UPDATE tasks SET position = ? WHERE id = ?');
    foreach ($ids->fetchAll(PDO::FETCH_COLUMN) as $i => $id) $up->execute([$i, $id]);
}

try {
switch ($action) {

// ---------- Маълумоти пурраи вазифа ----------
case 'get_task':
    $t = task_ctx((int)($_GET['id'] ?? 0));
    $q = $pdo->prepare('SELECT t.*, c.name AS column_name, u.full_name AS assignee_name FROM tasks t JOIN columns c ON c.id = t.column_id LEFT JOIN users u ON u.id = t.assignee_id WHERE t.id = ?');
    $q->execute([$t['id']]); $task = $q->fetch();
    $c = $pdo->prepare('SELECT c.id, c.comment, c.created_at, u.id AS user_id, u.full_name FROM task_comments c JOIN users u ON u.id = c.user_id WHERE c.task_id = ? ORDER BY c.created_at, c.id');
    $c->execute([$t['id']]);
    $a = $pdo->prepare('SELECT a.id, a.file_name, a.file_size, a.created_at, u.full_name FROM task_attachments a JOIN users u ON u.id = a.user_id WHERE a.task_id = ? ORDER BY a.id DESC');
    $a->execute([$t['id']]);
    $h = $pdo->prepare('SELECT h.action, h.field_name, h.old_value, h.new_value, h.created_at, u.id AS user_id, u.full_name FROM task_history h JOIN users u ON u.id = h.user_id WHERE h.task_id = ? ORDER BY h.created_at DESC, h.id DESC');
    $h->execute([$t['id']]);
    out(['ok' => true, 'task' => $task, 'comments' => $c->fetchAll(), 'files' => $a->fetchAll(), 'history' => $h->fetchAll(), 'can_edit' => $t['is_owner']]);

// ---------- Кашидани корт байни сутунҳо ----------
case 'move_task':
    $t = task_ctx((int)($in['task_id'] ?? 0));
    $newCol = (int)($in['column_id'] ?? 0);
    $before = (int)($in['before_id'] ?? 0);
    $cs = $pdo->prepare('SELECT id, name FROM columns WHERE id IN (?, ?) AND project_id = ?');
    $cs->execute([$newCol, $t['column_id'], $t['project_id']]);
    $names = array_column($cs->fetchAll(), 'name', 'id');
    if (!isset($names[$newCol])) fail('Сутун ёфт нашуд.', 404);
    $oldCol = (int)$t['column_id'];
    $pdo->beginTransaction();
    $ids = $pdo->prepare('SELECT id FROM tasks WHERE column_id = ? AND id <> ? ORDER BY position, id');
    $ids->execute([$newCol, $t['id']]);
    $ids = array_map('intval', $ids->fetchAll(PDO::FETCH_COLUMN));
    $idx = $before ? array_search($before, $ids, true) : false;
    array_splice($ids, $idx === false ? count($ids) : $idx, 0, [(int)$t['id']]);
    $up = $pdo->prepare('UPDATE tasks SET column_id = ?, position = ? WHERE id = ?');
    foreach ($ids as $i => $id) $up->execute([$newCol, $i, $id]);
    if ($oldCol !== $newCol) {
        resequence($oldCol);
        history_log((int)$t['id'], 'moved', 'column', $names[$oldCol] ?? '', $names[$newCol]);
    }
    $pdo->commit();
    out(['ok' => true, 'column_name' => $names[$newCol], 'moved' => $oldCol !== $newCol]);

// ---------- Сохтани вазифа (роҳбар) ----------
case 'create_task':
    $pid = (int)($in['project_id'] ?? 0); need_owner($pid);
    $title = trim($in['title'] ?? '');
    if (mb_strlen($title) < 2) fail('Номи вазифа камаш 2 аломат бошад.');
    $cs = $pdo->prepare('SELECT 1 FROM columns WHERE id = ? AND project_id = ?'); $cs->execute([(int)($in['column_id'] ?? 0), $pid]);
    if (!$cs->fetchColumn()) fail('Сутун ёфт нашуд.');
    $prio = in_array($in['priority'] ?? '', ['low', 'medium', 'high'], true) ? $in['priority'] : 'medium';
    $pos = $pdo->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM tasks WHERE column_id = ?'); $pos->execute([(int)$in['column_id']]);
    $pdo->prepare('INSERT INTO tasks (project_id, column_id, title, description, assignee_id, deadline, priority, position, created_by) VALUES (?,?,?,?,?,?,?,?,?)')
        ->execute([$pid, (int)$in['column_id'], $title, trim($in['description'] ?? '') ?: null, clean_assignee($in['assignee_id'] ?? null, $pid), clean_date($in['deadline'] ?? ''), $prio, (int)$pos->fetchColumn(), uid()]);
    $tid = (int)$pdo->lastInsertId();
    history_log($tid, 'created', null, null, $title);
    out(['ok' => true, 'id' => $tid]);

// ---------- Таҳрири вазифа (роҳбар) + таърих ----------
case 'update_task':
    $t = task_ctx((int)($in['id'] ?? 0));
    if (!$t['is_owner']) fail('Танҳо роҳбар вазифаро таҳрир мекунад.', 403);
    $pid = (int)$t['project_id'];
    $new = [
        'title'       => trim($in['title'] ?? ''),
        'description' => trim($in['description'] ?? '') ?: null,
        'assignee_id' => clean_assignee($in['assignee_id'] ?? null, $pid),
        'deadline'    => clean_date($in['deadline'] ?? ''),
        'priority'    => in_array($in['priority'] ?? '', ['low', 'medium', 'high'], true) ? $in['priority'] : $t['priority'],
    ];
    if (mb_strlen($new['title']) < 2) fail('Номи вазифа камаш 2 аломат бошад.');
    $pr = ['high' => 'Баланд', 'medium' => 'Миёна', 'low' => 'Паст'];
    $labels = ['title' => 'title', 'description' => 'description', 'assignee_id' => 'assignee', 'deadline' => 'deadline', 'priority' => 'priority'];
    $pdo->beginTransaction();
    foreach ($new as $f => $v) {
        $o = $t[$f];
        if ((string)$o === (string)$v) continue;
        if ($f === 'assignee_id') { $os = user_name($o ? (int)$o : null); $ns = user_name($v); }
        elseif ($f === 'priority') { $os = $pr[$o]; $ns = $pr[$v]; }
        elseif ($f === 'deadline') { $os = $o ? date('d.m.Y', strtotime($o)) : '—'; $ns = $v ? date('d.m.Y', strtotime($v)) : '—'; }
        else { $os = $o ?? '—'; $ns = $v ?? '—'; }
        history_log((int)$t['id'], 'updated', $labels[$f], $os, $ns);
    }
    $pdo->prepare('UPDATE tasks SET title=?, description=?, assignee_id=?, deadline=?, priority=? WHERE id=?')
        ->execute([$new['title'], $new['description'], $new['assignee_id'], $new['deadline'], $new['priority'], $t['id']]);
    $pdo->commit();
    out(['ok' => true]);

case 'delete_task':
    $t = task_ctx((int)($in['id'] ?? 0));
    if (!$t['is_owner']) fail('Танҳо роҳбар вазифаро нест мекунад.', 403);
    $pdo->prepare('DELETE FROM tasks WHERE id = ?')->execute([$t['id']]);
    resequence((int)$t['column_id']);
    out(['ok' => true]);

// ---------- Шарҳ ----------
case 'add_comment':
    $t = task_ctx((int)($in['task_id'] ?? 0));
    $text = trim($in['comment'] ?? '');
    if ($text === '') fail('Шарҳро нависед.');
    if (mb_strlen($text) > 2000) fail('Шарҳ аз 2000 аломат дароз нест.');
    $pdo->prepare('INSERT INTO task_comments (task_id, user_id, comment) VALUES (?,?,?)')->execute([$t['id'], uid(), $text]);
    history_log((int)$t['id'], 'comment', null, null, $text);
    out(['ok' => true]);

// ---------- Замима (файл) ----------
case 'upload':
    $t = task_ctx((int)($in['task_id'] ?? 0));
    $f = $_FILES['file'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) fail('Файл боргузорӣ нашуд (ҳаҷм аз upload_max_filesize зиёд аст?).');
    if ($f['size'] > 5 * 1024 * 1024) fail('Файл аз 5 МБ калон аст.');
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'], true)) fail('Намуди файл иҷозат дода намешавад.');
    $rel = 'uploads/' . bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/' . $rel)) fail('Файлро сабт карда натавонистам. Ба папкаи uploads ҳуқуқи навиштан диҳед.', 500);
    $name = mb_substr(basename($f['name']), 0, 200);
    $pdo->prepare('INSERT INTO task_attachments (task_id, user_id, file_name, file_path, file_size) VALUES (?,?,?,?,?)')->execute([$t['id'], uid(), $name, $rel, (int)$f['size']]);
    history_log((int)$t['id'], 'attachment', null, null, $name);
    out(['ok' => true]);

// ---------- Сутунҳо ----------
case 'create_column':
    $pid = (int)($in['project_id'] ?? 0); need_owner($pid);
    $name = trim($in['name'] ?? '');
    if ($name === '') fail('Номи сутунро нависед.');
    $pos = $pdo->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM columns WHERE project_id = ?'); $pos->execute([$pid]);
    $pdo->prepare('INSERT INTO columns (project_id, name, position) VALUES (?,?,?)')->execute([$pid, mb_substr($name, 0, 80), (int)$pos->fetchColumn()]);
    out(['ok' => true]);

case 'delete_column':
    $cid = (int)($in['column_id'] ?? 0);
    $q = $pdo->prepare('SELECT project_id FROM columns WHERE id = ?'); $q->execute([$cid]);
    $pid = (int)$q->fetchColumn();
    if (!$pid) fail('Сутун ёфт нашуд.', 404);
    need_owner($pid);
    $n = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE column_id = ?'); $n->execute([$cid]);
    if ((int)$n->fetchColumn() > 0) fail('Сутуни холӣ нест. Аввал вазифаҳоро ба сутуни дигар гузаронед.');
    $c = $pdo->prepare('SELECT COUNT(*) FROM columns WHERE project_id = ?'); $c->execute([$pid]);
    if ((int)$c->fetchColumn() <= 2) fail('Дар канбан камаш ду сутун бояд бошад.');
    $pdo->prepare('DELETE FROM columns WHERE id = ?')->execute([$cid]);
    out(['ok' => true]);

// ---------- Иштирокчиён ----------
case 'add_member':
    $pid = (int)($in['project_id'] ?? 0); need_owner($pid);
    $q = $pdo->prepare('SELECT id, full_name FROM users WHERE email = ?'); $q->execute([trim($in['email'] ?? '')]);
    $u = $q->fetch();
    if (!$u) fail('Корбар бо ин почта ёфт нашуд.', 404);
    $pdo->prepare('INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?,?)')->execute([$pid, $u['id']]);
    out(['ok' => true, 'name' => $u['full_name']]);

case 'remove_member':
    $pid = (int)($in['project_id'] ?? 0); need_owner($pid);
    $mid = (int)($in['user_id'] ?? 0);
    if ($mid === uid()) fail('Роҳбарро аз лоиҳа баровардан мумкин нест.');
    $pdo->prepare('UPDATE tasks SET assignee_id = NULL WHERE project_id = ? AND assignee_id = ?')->execute([$pid, $mid]);
    $pdo->prepare('DELETE FROM project_members WHERE project_id = ? AND user_id = ?')->execute([$pid, $mid]);
    out(['ok' => true]);

default:
    fail('Амали номаълум.', 404);
}
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fail('Хатои сервер: ' . $ex->getMessage(), 500);
}

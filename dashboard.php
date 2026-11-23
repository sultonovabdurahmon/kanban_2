<?php
require __DIR__ . '/includes/config.php';
require_login();
$me = user();
$error = '';

// Сохтани лоиҳаи нав (танҳо роҳбари лоиҳа)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'create_project') {
    check_csrf();
    if ($me['role'] !== 'manager') { http_response_code(403); die('Дастрасӣ нест.'); }
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if (mb_strlen($name) < 3) {
        $error = 'Номи лоиҳа камаш 3 аломат бошад.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO projects (name, description, owner_id) VALUES (?,?,?)')->execute([$name, $desc ?: null, uid()]);
        $pid = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO project_members (project_id, user_id) VALUES (?,?)')->execute([$pid, uid()]);
        $ins = $pdo->prepare('INSERT INTO columns (project_id, name, position) VALUES (?,?,?)');
        foreach (['Барои иҷро', 'Дар иҷро', 'Иҷрошуда'] as $i => $c) $ins->execute([$pid, $c, $i]);
        $pdo->commit();
        header('Location: project.php?id=' . $pid); exit;
    }
}

$st = db()->prepare("SELECT p.*, o.full_name AS owner_name,
    (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS members,
    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND (p.owner_id = :uid OR t.assignee_id = :uid)) AS total,
    (SELECT COUNT(*) FROM tasks t JOIN columns c ON c.id = t.column_id
       WHERE t.project_id = p.id AND (p.owner_id = :uid OR t.assignee_id = :uid)
         AND c.position = (SELECT MAX(position) FROM columns WHERE project_id = p.id)) AS done
  FROM projects p
  JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = :uid
  JOIN users o ON o.id = p.owner_id
  ORDER BY p.created_at DESC");
$st->execute(['uid' => uid()]);
$projects = $st->fetchAll();

$pageTitle = 'Лоиҳаҳо';
require __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="page-head">
    <div>
      <h1>Салом, <?= e(explode(' ', $me['full_name'])[0]) ?></h1>
      <p class="muted"><?= $me['role'] === 'manager' ? 'Лоиҳаҳои шумо ва дастаҳои онҳо' : 'Лоиҳаҳое, ки шумо дар онҳо иштирок мекунед' ?></p>
    </div>
    <?php if ($me['role'] === 'manager'): ?>
      <button class="btn btn-primary" type="button" data-open="projectDlg">Лоиҳаи нав</button>
    <?php endif; ?>
  </div>

  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

  <?php if (!$projects): ?>
    <div class="empty-state glass">
      <h3>Лоиҳа ҳоло нест</h3>
      <p><?= $me['role'] === 'manager' ? 'Лоиҳаи аввалинро созед — тахта бо се сутун худаш пайдо мешавад.' : 'Роҳбари лоиҳа шуморо ба лоиҳа илова кунад, лоиҳа дар ин ҷо пайдо мешавад.' ?></p>
    </div>
  <?php else: ?>
  <div class="project-grid">
    <?php foreach ($projects as $p): $pct = $p['total'] ? round($p['done'] / $p['total'] * 100) : 0; ?>
    <a class="project-card" href="project.php?id=<?= (int)$p['id'] ?>">
      <div class="pc-top">
        <h3><?= e($p['name']) ?></h3>
        <span class="pct"><?= $pct ?>%</span>
      </div>
      <p><?= e($p['description'] ?: 'Тавсиф нест') ?></p>
      <div class="progress"><i style="width:<?= $pct ?>%"></i></div>
      <div class="pc-foot">
        <span><?= (int)$p['done'] ?> аз <?= (int)$p['total'] ?> вазифа иҷро шуд</span>
        <span><?= (int)$p['members'] ?> иштирокчӣ</span>
      </div>
      <small class="muted">Роҳбар: <?= e($p['owner_name']) ?></small>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<?php if ($me['role'] === 'manager'): ?>
<dialog id="projectDlg" class="dlg dlg-sm">
  <form method="post" class="form">
    <div class="dlg-head"><h3>Лоиҳаи нав</h3><button type="button" class="icon-btn" data-close aria-label="Пӯшидан">✕</button></div>
    <input type="hidden" name="do" value="create_project">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Ном<input name="name" required minlength="3" maxlength="150" placeholder="Масалан: Барномаи мобилӣ"></label>
    <label>Тавсиф<textarea name="description" rows="3" placeholder="Лоиҳа дар бораи чӣ аст?"></textarea></label>
    <div class="dlg-foot"><button type="button" class="btn btn-ghost" data-close>Бекор кардан</button><button class="btn btn-primary" type="submit">Сохтани лоиҳа</button></div>
  </form>
</dialog>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

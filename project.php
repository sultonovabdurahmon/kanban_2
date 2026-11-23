<?php
require __DIR__ . '/includes/config.php';
require_login();
$pid = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM projects WHERE id = ?');
$st->execute([$pid]);
$project = $st->fetch();
if (!$project || !is_member($pid)) { http_response_code(404); die('Лоиҳа ёфт нашуд ё дастрасӣ нест.'); }
$owner = (int)$project['owner_id'] === uid();

$columns = db()->prepare('SELECT * FROM columns WHERE project_id = ? ORDER BY position, id');
$columns->execute([$pid]);
$columns = $columns->fetchAll();
$lastColId = $columns ? (int)end($columns)['id'] : 0;

$mem = db()->prepare('SELECT u.id, u.full_name, u.email FROM project_members pm JOIN users u ON u.id = pm.user_id WHERE pm.project_id = ? ORDER BY u.full_name');
$mem->execute([$pid]);
$members = $mem->fetchAll();

// Филтрҳо: иҷрокунанда ва мӯҳлат
$fAssignee = $owner ? (int)($_GET['assignee'] ?? 0) : 0;
$fFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : '';
$fTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '';
$where = 't.project_id = :pid'; $params = ['pid' => $pid];
if (!$owner)    { $where .= ' AND t.assignee_id = :me'; $params['me'] = uid(); }
if ($fAssignee) { $where .= ' AND t.assignee_id = :a';  $params['a'] = $fAssignee; }
if ($fFrom)     { $where .= ' AND t.deadline >= :f';    $params['f'] = $fFrom; }
if ($fTo)       { $where .= ' AND t.deadline <= :to';   $params['to'] = $fTo; }
$ts = db()->prepare("SELECT t.*, u.full_name AS assignee_name,
    (SELECT COUNT(*) FROM task_comments WHERE task_id = t.id) AS n_comments,
    (SELECT COUNT(*) FROM task_attachments WHERE task_id = t.id) AS n_files
  FROM tasks t LEFT JOIN users u ON u.id = t.assignee_id WHERE $where ORDER BY t.position, t.id");
$ts->execute($params);
$byCol = [];
foreach ($ts->fetchAll() as $t) $byCol[(int)$t['column_id']][] = $t;
$filtered = $fAssignee || $fFrom || $fTo;
$today = date('Y-m-d');
$prioName = ['high' => 'Баланд', 'medium' => 'Миёна', 'low' => 'Паст'];

$pageTitle = $project['name']; $bodyClass = 'board-page';
require __DIR__ . '/includes/header.php';
$boot = ['csrf' => csrf_token(), 'project' => $pid, 'owner' => $owner, 'me' => uid(), 'lastCol' => $lastColId,
    'members' => array_map(fn($m) => ['id' => (int)$m['id'], 'name' => $m['full_name'], 'email' => $m['email']], $members),
    'columns' => array_map(fn($c) => ['id' => (int)$c['id'], 'name' => $c['name']], $columns)];
?>
<script>window.KB = <?= json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>

<div class="board-head">
  <div class="bh-title">
    <a class="back" href="dashboard.php">← Лоиҳаҳо</a>
    <h1><?= e($project['name']) ?></h1>
    <?php if ($project['description']): ?><p class="muted"><?= e($project['description']) ?></p><?php endif; ?>
  </div>
  <div class="bh-actions">
    <div class="avatars" title="Иштирокчиён">
      <?php foreach (array_slice($members, 0, 5) as $m) echo avatar((int)$m['id'], $m['full_name']); ?>
      <?php if (count($members) > 5): ?><span class="avatar avatar-more">+<?= count($members) - 5 ?></span><?php endif; ?>
    </div>
    <?php if ($owner): ?>
      <button class="btn btn-ghost" type="button" data-open="memberDlg">Иштирокчиён</button>
      <button class="btn btn-primary" type="button" id="newTaskBtn">Вазифаи нав</button>
    <?php endif; ?>
  </div>
</div>

<form class="filters" method="get">
  <input type="hidden" name="id" value="<?= $pid ?>">
  <?php if ($owner): ?>
  <label>Иҷрокунанда
    <select name="assignee">
      <option value="0">Ҳама</option>
      <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>" <?= $fAssignee === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['full_name']) ?></option><?php endforeach; ?>
    </select>
  </label>
  <?php endif; ?>
  <label>Мӯҳлат аз<input type="date" name="from" value="<?= e($fFrom) ?>"></label>
  <label>то<input type="date" name="to" value="<?= e($fTo) ?>"></label>
  <button class="btn btn-ghost btn-sm" type="submit">Филтр</button>
  <?php if ($filtered): ?><a class="btn btn-ghost btn-sm" href="project.php?id=<?= $pid ?>">Тоза кардан</a><?php endif; ?>
</form>

<main class="board" id="board">
<?php foreach ($columns as $i => $col): $cid = (int)$col['id']; $cards = $byCol[$cid] ?? []; ?>
  <section class="col <?= $cards ? '' : 'is-empty' ?> <?= $cid === $lastColId ? 'col-done' : '' ?>" data-col="<?= $cid ?>" style="--i:<?= $i ?>">
    <header class="col-head">
      <span class="dot"></span>
      <h2><?= e($col['name']) ?></h2>
      <span class="count"><?= count($cards) ?></span>
      <?php if ($owner): ?>
        <button class="icon-btn sm" type="button" data-add-task="<?= $cid ?>" title="Вазифа илова кардан" aria-label="Вазифа илова кардан">+</button>
        <button class="icon-btn sm danger" type="button" data-del-col="<?= $cid ?>" title="Нест кардани сутун" aria-label="Нест кардани сутун">✕</button>
      <?php endif; ?>
    </header>
    <div class="col-body" data-col="<?= $cid ?>"><?php
    foreach ($cards as $t):
        $late = $t['deadline'] && $t['deadline'] < $today && $cid !== $lastColId;
        $can  = $owner || (int)$t['assignee_id'] === uid();
    ?><article class="card prio-<?= e($t['priority']) ?>" tabindex="0" draggable="<?= $can ? 'true' : 'false' ?>" data-task="<?= (int)$t['id'] ?>">
        <h3><?= e($t['title']) ?></h3>
        <div class="card-meta">
          <?php if ($t['deadline']): ?><span class="chip <?= $late ? 'chip-late' : '' ?>" title="Мӯҳлат"><?= fmt_date($t['deadline']) ?></span><?php endif; ?>
          <span class="chip chip-c" <?= $t['n_comments'] ? '' : 'hidden' ?> title="Шарҳҳо">💬 <b><?= (int)$t['n_comments'] ?></b></span>
          <span class="chip chip-f" <?= $t['n_files'] ? '' : 'hidden' ?> title="Замимаҳо">📎 <b><?= (int)$t['n_files'] ?></b></span>
          <span class="spacer"></span>
          <?= avatar($t['assignee_id'] ? (int)$t['assignee_id'] : null, $t['assignee_name']) ?>
        </div>
      </article><?php endforeach; ?></div>
    <div class="col-empty">Корти вазифаро ба ин ҷо кашед</div>
  </section>
<?php endforeach; ?>
<?php if ($owner): ?>
  <section class="col col-new" style="--i:<?= count($columns) ?>">
    <form id="colForm" class="form">
      <label>Сутуни нав<input name="name" maxlength="80" required placeholder="Масалан: Озмоиш"></label>
      <button class="btn btn-ghost" type="submit">Илова кардан</button>
    </form>
  </section>
<?php endif; ?>
</main>

<!-- Тирезаи вазифа -->
<dialog id="taskDlg" class="dlg dlg-lg"><div class="dlg-body"></div></dialog>

<?php if ($owner): ?>
<dialog id="createDlg" class="dlg dlg-sm">
  <form id="createForm" class="form">
    <div class="dlg-head"><h3>Вазифаи нав</h3><button type="button" class="icon-btn" data-close aria-label="Пӯшидан">✕</button></div>
    <label>Ном<input name="title" required maxlength="200"></label>
    <label>Тавсиф<textarea name="description" rows="3"></textarea></label>
    <div class="grid2">
      <label>Сутун<select name="column_id"><?php foreach ($columns as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
      <label>Афзалият<select name="priority"><option value="medium">Миёна</option><option value="high">Баланд</option><option value="low">Паст</option></select></label>
      <label>Иҷрокунанда<select name="assignee_id"><option value="">Таъин нашавад</option><?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['full_name']) ?></option><?php endforeach; ?></select></label>
      <label>Мӯҳлат<input type="date" name="deadline"></label>
    </div>
    <div class="dlg-foot"><button type="button" class="btn btn-ghost" data-close>Бекор кардан</button><button class="btn btn-primary" type="submit">Сохтани вазифа</button></div>
  </form>
</dialog>

<dialog id="memberDlg" class="dlg dlg-sm">
  <div class="dlg-head"><h3>Иштирокчиёни лоиҳа</h3><button type="button" class="icon-btn" data-close aria-label="Пӯшидан">✕</button></div>
  <ul class="member-list">
    <?php foreach ($members as $m): ?>
    <li>
      <?= avatar((int)$m['id'], $m['full_name']) ?>
      <div><strong><?= e($m['full_name']) ?></strong><small class="muted"><?= e($m['email']) ?></small></div>
      <?php if ((int)$m['id'] === (int)$project['owner_id']): ?><span class="chip">Роҳбар</span>
      <?php else: ?><button class="btn btn-ghost btn-sm" type="button" data-rm-member="<?= (int)$m['id'] ?>">Бароварда</button><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
  <form id="memberForm" class="form inline-form">
    <label>Илова бо почта<input type="email" name="email" required placeholder="madina@kanban.tj"></label>
    <button class="btn btn-primary" type="submit">Илова</button>
  </form>
  <p class="hint">Корбар аввал бояд дар сомона сабти ном карда бошад.</p>
</dialog>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

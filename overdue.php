<?php
require __DIR__ . '/includes/config.php';
require_login();
$st = db()->prepare("SELECT t.id, t.title, t.deadline, t.priority, t.project_id, t.assignee_id, p.name AS project_name,
    c.name AS column_name, u.full_name AS assignee_name, DATEDIFF(CURDATE(), t.deadline) AS days_late "
    . overdue_sql() . " ORDER BY t.deadline ASC, t.priority DESC");
$st->execute(['uid' => uid()]);
$rows = $st->fetchAll();
$prio = ['high' => 'Баланд', 'medium' => 'Миёна', 'low' => 'Паст'];
$pageTitle = 'Мӯҳлаташ гузашта';
require __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="page-head">
    <div>
      <h1>Мӯҳлаташ гузашта</h1>
      <p class="muted"><?= count($rows) ?> вазифа ҳанӯз ба сутуни охирин нарасидааст</p>
    </div>
  </div>
  <?php if (!$rows): ?>
    <div class="empty-state glass"><h3>Ҳама дар вақт аст</h3><p>Вазифаи мӯҳлаташ гузашта нест.</p></div>
  <?php else: ?>
  <div class="overdue-list">
    <?php foreach ($rows as $r): ?>
    <a class="overdue-row prio-<?= e($r['priority']) ?>" href="project.php?id=<?= (int)$r['project_id'] ?>&task=<?= (int)$r['id'] ?>">
      <div class="or-main">
        <strong><?= e($r['title']) ?></strong>
        <small class="muted"><?= e($r['project_name']) ?> · <?= e($r['column_name']) ?></small>
      </div>
      <span class="chip chip-prio"><?= $prio[$r['priority']] ?></span>
      <span class="chip chip-late"><?= (int)$r['days_late'] ?> рӯз дер шуд · <?= fmt_date($r['deadline']) ?></span>
      <?= avatar($r['assignee_id'] ? (int)$r['assignee_id'] : null, $r['assignee_name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

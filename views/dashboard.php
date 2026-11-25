<h1 class="mb-1">Ҳолати умумӣ</h1>
<p class="text-muted mb-4">Маълумот аз пойгоҳи додаҳо.</p>

<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="stat-number"><?= $stats['total'] ?></div><div class="stat-label">Ҳамаи вазифаҳо</div></div></div></div>
  <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="stat-number"><?= $stats['active'] ?></div><div class="stat-label">Фаъол</div></div></div></div>
  <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="stat-number"><?= $stats['done'] ?></div><div class="stat-label">Иҷрошуда</div></div></div></div>
  <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card is-danger h-100"><div class="card-body"><div class="stat-number"><?= $stats['overdue'] ?></div><div class="stat-label">Гузашта аз мӯҳлат</div></div></div></div>
</div>

<section aria-labelledby="latest-title">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h2 id="latest-title" class="me-auto mb-0">Вазифаҳои охирин</h2>
    <a class="btn btn-primary" href="<?= $this->url('/tasks/create') ?>">+ Вазифа</a>
  </div>
  <div class="card"><ul class="list-group list-group-flush">
    <?php foreach ($latest as $t): ?>
    <li class="list-group-item d-flex flex-wrap gap-2 justify-content-between">
      <a href="<?= $this->url('/tasks/' . (int) $t['id']) ?>"><?= $this->e($t['title']) ?></a>
      <span class="text-muted"><?= $this->e($t['project_name']) ?> · <?= $this->e($t['column_title']) ?></span>
    </li>
    <?php endforeach; ?>
  </ul></div>
</section>

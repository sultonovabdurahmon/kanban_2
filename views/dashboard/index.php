<h1 class="mb-1">Ҳолати умумӣ</h1>
<p class="text-muted mb-4">Рӯзи 12: ададҳои муваққатӣ. Дар кори лаб. 13 онҳо аз пойгоҳ меоянд.</p>
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="stat-number"><?= (int) $stats['projects'] ?></div><div class="stat-label">Лоиҳаҳо</div></div></div></div>
  <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="stat-number"><?= (int) $stats['active'] ?></div><div class="stat-label">Вазифаҳои фаъол</div></div></div></div>
  <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="stat-number"><?= (int) $stats['done'] ?></div><div class="stat-label">Иҷрошуда</div></div></div></div>
  <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card is-danger h-100"><div class="card-body"><div class="stat-number"><?= (int) $stats['overdue'] ?></div><div class="stat-label">Гузашта аз мӯҳлат</div></div></div></div>
</div>
<a class="btn btn-primary" href="<?= $this->url('/tasks') ?>">Ба рӯйхати вазифаҳо</a>

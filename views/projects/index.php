<h1 class="mb-3">Лоиҳаҳо</h1>
<div class="row g-3">
  <?php foreach ($projects as $p): ?>
  <div class="col-12 col-md-6 col-xl-4">
    <article class="card project-card h-100"><div class="card-body">
      <h2 class="h5"><a href="<?= $this->url('/projects/' . (int) $p['id']) ?>"><?= $this->e($p['name']) ?></a></h2>
      <p class="text-muted"><?= $this->e($p['description']) ?></p>
      <span class="text-muted">Иштирокчиён: <?= (int) $p['members'] ?> · Вазифаҳо: <?= (int) $p['tasks'] ?></span>
    </div></article>
  </div>
  <?php endforeach; ?>
</div>

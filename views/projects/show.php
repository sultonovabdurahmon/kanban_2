<nav aria-label="Занҷираи навигатсия">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $this->url('/projects') ?>">Лоиҳаҳо</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= $this->e($project['name']) ?></li>
  </ol>
</nav>
<h1 class="mb-3"><?= $this->e($project['name']) ?></h1>
<div class="card"><div class="card-header">Маълумоти лоиҳа</div><div class="card-body">
  <dl class="details">
    <dt>Тавсиф</dt><dd><?= $this->e($project['description']) ?></dd>
    <dt>Роҳбар</dt><dd><?= $this->e($project['owner_name']) ?></dd>
    <dt>Иштирокчиён</dt><dd><?= (int) $project['members'] ?></dd>
    <dt>Вазифаҳо</dt><dd><?= (int) $project['tasks'] ?></dd>
    <dt>Сохта шуд</dt><dd><?= $this->e($this->date($project['created_at'])) ?></dd>
  </dl>
</div></div>

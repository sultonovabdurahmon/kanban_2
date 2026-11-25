<nav aria-label="Занҷираи навигатсия">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $this->url('/tasks') ?>">Вазифаҳо</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= $this->e($task['title']) ?></li>
  </ol>
</nav>

<div class="row g-4">
  <aside class="col-12 col-lg-3">
    <div class="card"><div class="card-body text-center">
      <h2 class="h5"><?= $task['assignee_name'] !== null ? $this->e($task['assignee_name']) : 'Таъин нашудааст' ?></h2>
      <p class="text-muted">Иҷрокунанда</p>
      <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
        <span class="badge priority priority-<?= $this->e($task['priority']) ?>"><?= $priorities[$task['priority']] ?? '' ?></span>
        <span class="badge column-tag"><?= $this->e($task['column_title']) ?></span>
      </div>
      <a class="btn btn-outline-primary w-100 mb-2" href="<?= $this->url('/tasks/' . (int) $task['id'] . '/edit') ?>">Таҳрир</a>
      <button type="button" class="btn btn-outline-danger w-100"
              data-delete-url="<?= $this->url('/tasks/' . (int) $task['id'] . '/delete') ?>"
              data-name="Вазифаи «<?= $this->e($task['title']) ?>»">Нест кардан</button>
    </div></div>
  </aside>

  <section class="col-12 col-lg-9" aria-labelledby="task-title">
    <h1 id="task-title" class="mb-3"><?= $this->e($task['title']) ?></h1>
    <div class="card mb-4">
      <div class="card-header">Маълумоти вазифа</div>
      <div class="card-body">
        <dl class="details">
          <dt>Лоиҳа</dt><dd><?= $this->e($task['project_name']) ?></dd>
          <dt>Тавсиф</dt><dd><?= $task['description'] !== null ? nl2br($this->e($task['description'])) : '—' ?></dd>
          <dt>Мӯҳлат</dt><dd><?= $task['due_date'] !== null ? date('d.m.Y', strtotime($task['due_date'])) : '—' ?><?= $task['is_overdue'] ? ' · <span class="is-overdue">гузашта</span>' : '' ?></dd>
          <dt>Афзалият</dt><dd><?= $priorities[$task['priority']] ?? '' ?></dd>
          <dt>Сутун</dt><dd><?= $this->e($task['column_title']) ?></dd>
          <dt>Созанда</dt><dd><?= $this->e($task['creator_name']) ?></dd>
          <dt>Сохта шуд</dt><dd><?= date('d.m.Y H:i', strtotime($task['created_at'])) ?></dd>
        </dl>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">Шарҳҳо</div>
      <div class="card-body">
        <?php if (!$comments): ?><span class="text-muted">Шарҳ нест</span><?php endif; ?>
        <?php foreach ($comments as $c): ?>
        <div class="comment"><strong><?= $this->e($c['name']) ?></strong> <time class="text-muted" datetime="<?= date('Y-m-d\TH:i', strtotime($c['created_at'])) ?>"><?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></time><br><?= $this->e($c['body']) ?></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Таърихи тағйирот</div>
      <div class="card-body">
        <?php foreach ($history as $h): ?>
        <div class="history-item"><strong><?= $this->e($h['name']) ?></strong> · <time datetime="<?= date('Y-m-d\TH:i', strtotime($h['created_at'])) ?>"><?= date('d.m.Y H:i', strtotime($h['created_at'])) ?></time><br><?= $this->e($h['details']) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>

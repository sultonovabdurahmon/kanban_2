<?php
['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages] = $result;
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="me-auto mb-0">Вазифаҳо</h1>
  <a class="btn btn-primary" href="<?= $this->url('/tasks/create') ?>">+ Вазифа</a>
</div>

<form class="card card-body mb-3" method="get" action="<?= $this->url('/tasks') ?>" role="search">
  <div class="row g-2">
    <div class="col-12 col-md-4">
      <label class="visually-hidden" for="q">Ҷустуҷӯ аз рӯи ном</label>
      <input class="form-control" type="search" id="q" name="q" value="<?= $this->e($filters['q']) ?>" placeholder="Ҷустуҷӯ аз рӯи ном…">
    </div>
    <div class="col-12 col-sm-6 col-md-3">
      <label class="visually-hidden" for="project_id">Лоиҳа</label>
      <select class="form-select" id="project_id" name="project_id">
        <option value="">Ҳамаи лоиҳаҳо</option>
        <?php foreach ($projects as $p): ?>
        <option value="<?= (int) $p['id'] ?>"<?= (string) $p['id'] === $filters['project_id'] ? ' selected' : '' ?>><?= $this->e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="visually-hidden" for="priority">Афзалият</label>
      <select class="form-select" id="priority" name="priority">
        <option value="">Афзалият</option>
        <?php foreach ($priorities as $key => $label): ?>
        <option value="<?= $key ?>"<?= $key === $filters['priority'] ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label class="visually-hidden" for="assignee_id">Иҷрокунанда</label>
      <select class="form-select" id="assignee_id" name="assignee_id">
        <option value="">Ҳамаи иҷрокунандагон</option>
        <?php foreach ($users as $u): ?>
        <option value="<?= (int) $u['id'] ?>"<?= (string) $u['id'] === $filters['assignee_id'] ? ' selected' : '' ?>><?= $this->e($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-outline-primary px-4" type="submit">Ёфтан</button></div>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 table-cards">
      <thead>
        <tr><th scope="col">Вазифа</th><th scope="col">Лоиҳа</th><th scope="col">Иҷрокунанда</th><th scope="col">Мӯҳлат</th><th scope="col">Афзалият</th><th scope="col">Сутун</th><th scope="col"><span class="visually-hidden">Амалҳо</span></th></tr>
      </thead>
      <tbody>
      <?php if (!$items): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Вазифаҳо ёфт нашуданд. Ҷустуҷӯро тағйир диҳед.</td></tr>
      <?php endif; ?>
      <?php foreach ($items as $t): ?>
        <tr>
          <td data-label="Вазифа"><a href="<?= $this->url('/tasks/' . (int) $t['id']) ?>"><?= $this->e($t['title']) ?></a></td>
          <td data-label="Лоиҳа"><?= $this->e($t['project_name']) ?></td>
          <td data-label="Иҷрокунанда"><?= $t['assignee_name'] !== null ? $this->e($t['assignee_name']) : '—' ?></td>
          <td data-label="Мӯҳлат">
            <?php if ($t['due_date'] === null): ?>—
            <?php else: $d = date('d.m.Y', strtotime($t['due_date'])); ?>
              <?php if ($t['is_overdue']): ?><span class="is-overdue"><?= $d ?> · гузашта</span><?php else: ?><?= $d ?><?php endif; ?>
            <?php endif; ?>
          </td>
          <td data-label="Афзалият"><span class="badge priority priority-<?= $this->e($t['priority']) ?>"><?= $priorities[$t['priority']] ?? '' ?></span></td>
          <td data-label="Сутун"><span class="badge column-tag"><?= $this->e($t['column_title']) ?></span></td>
          <td class="text-end cell-actions">
            <a class="btn btn-sm btn-outline-primary" href="<?= $this->url('/tasks/' . (int) $t['id'] . '/edit') ?>">Таҳрир</a>
            <button type="button" class="btn btn-sm btn-outline-danger ms-1"
                    data-delete-url="<?= $this->url('/tasks/' . (int) $t['id'] . '/delete') ?>"
                    data-name="Вазифаи «<?= $this->e($t['title']) ?>»">Нест кардан</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<nav class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3" aria-label="Саҳифаҳо">
  <span class="text-muted">Ёфт шуд: <?= $total ?></span>
  <?php $basePath = '/tasks'; require __DIR__ . '/../partials/pagination.php'; ?>
</nav>

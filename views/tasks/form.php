<?php
// Як шакл барои эҷод ва таҳрир: $id === null — вазифаи нав
$action = $id ? '/tasks/' . $id . '/edit' : '/tasks/create';
$val = fn (string $field): string => $this->e($task[$field] ?? '');
$invalid = fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
$error = fn (string $field): string => isset($errors[$field])
    ? '<div class="invalid-feedback" id="' . $field . '-error">' . $this->e($errors[$field]) . '</div>' : '';
$desc = fn (string $field): string => isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . $field . '-error"' : '';
?>
<nav aria-label="Занҷираи навигатсия">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $this->url('/tasks') ?>">Вазифаҳо</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= $id ? 'Таҳрир' : 'Вазифаи нав' ?></li>
  </ol>
</nav>
<h1 class="mb-3"><?= $id ? 'Таҳрири вазифа' : 'Вазифаи нав' ?></h1>

<?php if ($errors): ?>
<div class="alert alert-danger" role="alert">Шакл хато дорад. Майдонҳои қайдшударо ислоҳ кунед.</div>
<?php endif; ?>

<form class="card card-body" method="post" action="<?= $this->url($action) ?>" novalidate>
  <div class="mb-3">
    <label class="form-label" for="title">Ном *</label>
    <input class="form-control<?= $invalid('title') ?>" type="text" id="title" name="title" required maxlength="200" value="<?= $val('title') ?>"<?= $desc('title') ?>>
    <?= $error('title') ?>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-6">
      <label class="form-label" for="column_id">Сутун (лоиҳа) *</label>
      <select class="form-select<?= $invalid('column_id') ?>" id="column_id" name="column_id" required<?= $desc('column_id') ?>>
        <option value="">Сутунро интихоб кунед</option>
        <?php $group = null; foreach ($columns as $c): ?>
          <?php if ($group !== $c['project_name']): if ($group !== null) echo '</optgroup>'; $group = $c['project_name']; ?>
          <optgroup label="<?= $this->e($group) ?>">
          <?php endif; ?>
          <option value="<?= (int) $c['id'] ?>"<?= (string) ($task['column_id'] ?? '') === (string) $c['id'] ? ' selected' : '' ?>><?= $this->e($c['title']) ?></option>
        <?php endforeach; if ($group !== null) echo '</optgroup>'; ?>
      </select>
      <?= $error('column_id') ?>
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label" for="assignee_id">Иҷрокунанда</label>
      <select class="form-select<?= $invalid('assignee_id') ?>" id="assignee_id" name="assignee_id"<?= $desc('assignee_id') ?>>
        <option value="">Таъин нашудааст</option>
        <?php foreach ($users as $u): ?>
        <option value="<?= (int) $u['id'] ?>"<?= (string) ($task['assignee_id'] ?? '') === (string) $u['id'] ? ' selected' : '' ?>><?= $this->e($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $error('assignee_id') ?>
    </div>
    <div class="col-12 col-sm-6">
      <label class="form-label" for="due_date">Мӯҳлат</label>
      <input class="form-control<?= $invalid('due_date') ?>" type="date" id="due_date" name="due_date" value="<?= $val('due_date') ?>"<?= $desc('due_date') ?>>
      <?= $error('due_date') ?>
    </div>
    <div class="col-12 col-sm-6">
      <label class="form-label" for="priority">Афзалият *</label>
      <select class="form-select<?= $invalid('priority') ?>" id="priority" name="priority" required<?= $desc('priority') ?>>
        <option value="">Интихоб кунед</option>
        <?php foreach ($priorities as $key => $label): ?>
        <option value="<?= $key ?>"<?= ($task['priority'] ?? '') === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <?= $error('priority') ?>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label" for="description">Тавсиф</label>
    <textarea class="form-control<?= $invalid('description') ?>" id="description" name="description" rows="3"<?= $desc('description') ?>><?= $val('description') ?></textarea>
    <?= $error('description') ?>
  </div>

  <div class="d-flex flex-wrap gap-2 justify-content-end">
    <a class="btn btn-outline-secondary" href="<?= $this->url($id ? '/tasks/' . $id : '/tasks') ?>">Бекор кардан</a>
    <button class="btn btn-primary" type="submit"><?= $id ? 'Захира кардан' : 'Сохтан' ?></button>
  </div>
</form>

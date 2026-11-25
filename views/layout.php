<?php
// views/layout.php — умумии тамоми саҳифаҳо. $this — объекти View.
$active = $active ?? '';
$nav = ['home' => ['/', 'Асосӣ'], 'tasks' => ['/tasks', 'Вазифаҳо'], 'report' => ['/reports/projects', 'Ҳисобот'], 'users' => ['/users', 'Корбарон']];
?>
<!doctype html>
<html lang="tg">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $this->e($title ?? 'Kanban') ?> — Kanban</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="<?= $this->url('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container">
    <a class="navbar-brand" href="<?= $this->url('/') ?>">📋 Kanban</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Кушодани меню">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <?php foreach ($nav as $key => [$href, $label]): ?>
        <li class="nav-item"><a class="nav-link<?= $key === $active ? ' active' : '' ?>"<?= $key === $active ? ' aria-current="page"' : '' ?> href="<?= $this->url($href) ?>"><?= $label ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">
  <?php require __DIR__ . '/partials/notice.php'; ?>
  <?= $content ?>
</main>

<footer class="app-footer container py-4 text-center">Kanban · идоракунии вазифаҳои даста · 2026</footer>

<div class="modal fade" id="confirm-delete-modal" tabindex="-1" role="dialog" aria-labelledby="confirm-delete-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header">
      <h2 class="modal-title h4" id="confirm-delete-title">Нест кунем?</h2>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Пӯшидан"></button>
    </div>
    <div class="modal-body">
      <p class="mb-0"><strong id="confirm-delete-name"></strong> нест карда мешавад. Ин амалро бекор кардан мумкин нест.</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Бекор кардан</button>
      <form id="confirm-delete-form" method="post" action="#">
        <button type="submit" class="btn btn-danger">Нест кардан</button>
      </form>
    </div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script type="module" src="<?= $this->url('/assets/js/app.js') ?>"></script>
</body>
</html>

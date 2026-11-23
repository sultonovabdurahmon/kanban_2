<?php
// Тағйирёбандҳо: $pageTitle, $auth (true = саҳифаи вуруд), $bodyClass
$pageTitle = $pageTitle ?? 'Канбан';
$auth = $auth ?? false;
$me = user();
$curr = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="tg" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · Канбан</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700&family=Unbounded:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<script>document.documentElement.dataset.theme = localStorage.getItem('kb-theme') || 'dark';</script>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<div class="backdrop" aria-hidden="true"><i></i><i></i><i></i></div>
<?php if (!$auth && $me): ?>
<header class="topbar">
  <a class="brand" href="dashboard.php"><span class="brand-mark"></span>Канбан</a>
  <nav class="nav">
    <a href="dashboard.php" class="<?= in_array($curr, ['dashboard.php', 'project.php']) ? 'active' : '' ?>">Лоиҳаҳо</a>
    <a href="overdue.php" class="<?= $curr === 'overdue.php' ? 'active' : '' ?>">Мӯҳлаташ гузашта
      <?php $oc = overdue_count(); if ($oc): ?><span class="badge"><?= $oc ?></span><?php endif; ?>
    </a>
  </nav>
  <div class="top-right">
    <button class="icon-btn" id="themeBtn" type="button" aria-label="Иваз кардани мавзӯъ">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    </button>
    <div class="user-chip">
      <?= avatar(uid(), $me['full_name']) ?>
      <div><strong><?= e($me['full_name']) ?></strong><small><?= $me['role'] === 'manager' ? 'Роҳбари лоиҳа' : 'Иштирокчӣ' ?></small></div>
    </div>
    <a class="btn btn-ghost btn-sm" href="logout.php">Баромадан</a>
  </div>
</header>
<?php endif; ?>

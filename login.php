<?php
require __DIR__ . '/includes/config.php';
if (user()) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'], 'full_name' => $u['full_name'], 'email' => $u['email'], 'role' => $u['role']];
        header('Location: dashboard.php'); exit;
    }
    $error = 'Почта ё парол нодуруст аст. Аз нав кӯшиш кунед.';
}
$pageTitle = 'Воридшавӣ'; $auth = true; $bodyClass = 'auth-page';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-wrap">
  <section class="auth-hero">
    <span class="brand big"><span class="brand-mark"></span>Канбан</span>
    <h1>Вазифаҳои даста дар як канбан</h1>
    <p>Корти вазифаро аз сутуни «Барои иҷро» ба «Иҷрошуда» кашед — тағйирот худаш сабт мешавад.</p>
    <div class="mini-board" aria-hidden="true">
      <div><b></b><b></b></div><div><b class="m"></b></div><div><b class="d"></b><b class="d"></b></div>
    </div>
  </section>
  <section class="auth-card glass">
    <h2>Воридшавӣ</h2>
    <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
      <label>Почтаи электронӣ<input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>" placeholder="manager@kanban.tj"></label>
      <label>Парол<input type="password" name="password" required placeholder="••••••"></label>
      <button class="btn btn-primary btn-lg" type="submit">Ворид шудан</button>
    </form>
    <p class="auth-alt">Ҳисоб надоред? <a href="register.php">Сабти ном</a></p>
    <p class="hint">Намуна: manager@kanban.tj ё sherali@kanban.tj, парол: <code>password</code></p>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require __DIR__ . '/includes/config.php';
if (user()) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = ($_POST['role'] ?? '') === 'manager' ? 'manager' : 'member';
    if (mb_strlen($name) < 3)                       $error = 'Ному насаб камаш 3 аломат бошад.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Почтаи электронӣ нодуруст аст.';
    elseif (strlen($pass) < 6)                      $error = 'Парол камаш 6 аломат бошад.';
    else {
        $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetchColumn()) $error = 'Ин почта аллакай сабт шудааст.';
        else {
            db()->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?,?,?,?)')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
            $id = (int)db()->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $id, 'full_name' => $name, 'email' => $email, 'role' => $role];
            header('Location: dashboard.php'); exit;
        }
    }
}
$pageTitle = 'Сабти ном'; $auth = true; $bodyClass = 'auth-page';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-wrap">
  <section class="auth-hero">
    <span class="brand big"><span class="brand-mark"></span>Канбан</span>
    <h1>Дастаатонро ба кор оред</h1>
    <p>Роҳбари лоиҳа канбан месозад ва вазифаҳо таъин мекунад, иштирокчӣ вазифаҳои худро пеш мебарад.</p>
  </section>
  <section class="auth-card glass">
    <h2>Сабти ном</h2>
    <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
      <label>Ном ва насаб<input name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></label>
      <label>Почтаи электронӣ<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label>Парол (камаш 6 аломат)<input type="password" name="password" required minlength="6"></label>
      <label>Нақш
        <select name="role">
          <option value="member">Иштирокчӣ</option>
          <option value="manager" <?= ($_POST['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Роҳбари лоиҳа</option>
        </select>
      </label>
      <button class="btn btn-primary btn-lg" type="submit">Сабт кардан</button>
    </form>
    <p class="auth-alt">Ҳисоб доред? <a href="login.php">Ворид шавед</a></p>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

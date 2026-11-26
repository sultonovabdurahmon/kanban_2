<main class="login-wrap">
  <section class="card login-card" aria-labelledby="login-title">
    <div class="card-body p-4">
      <p class="login-brand mb-1">📋 Kanban</p>
      <h1 id="login-title" class="h4 mb-4">Воридшавӣ</h1>
      <form method="post" action="<?= $this->url('/login') ?>">
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" autocomplete="username">
        </div>
        <div class="mb-3">
          <label class="form-label" for="password">Парол</label>
          <input class="form-control" type="password" id="password" name="password" autocomplete="current-password">
        </div>
        <div class="d-grid"><button class="btn btn-primary" type="submit">Ворид шудан</button></div>
      </form>
    </div>
  </section>
</main>

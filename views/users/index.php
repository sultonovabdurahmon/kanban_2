<h1 class="mb-3">Корбарон</h1>
<div class="card">
  <div class="table-responsive">
    <table class="table align-middle mb-0 table-cards">
      <thead>
        <tr><th scope="col">Ном</th><th scope="col">Email</th><th scope="col" class="text-end">Сохтааст</th><th scope="col" class="text-end">Таъин шудааст</th><th scope="col"><span class="visually-hidden">Амалҳо</span></th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td data-label="Ном"><?= $this->e($u['name']) ?></td>
          <td data-label="Email"><?= $this->e($u['email']) ?></td>
          <td data-label="Сохтааст" class="text-end"><?= (int) $u['created_tasks'] ?></td>
          <td data-label="Таъин шудааст" class="text-end"><?= (int) $u['assigned_tasks'] ?></td>
          <td class="text-end cell-actions">
            <button type="button" class="btn btn-sm btn-outline-danger"
                    data-delete-url="<?= $this->url('/users/' . (int) $u['id'] . '/delete') ?>"
                    data-name="Корбар «<?= $this->e($u['name']) ?>»">Нест кардан</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

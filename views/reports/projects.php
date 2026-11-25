<h1 class="mb-3">Ҳисобот: вазифаҳо аз рӯи лоиҳаҳо</h1>
<div class="card">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr><th scope="col">Лоиҳа</th><th scope="col" class="text-end">Ҳамагӣ</th><th scope="col" class="text-end">Фаъол</th><th scope="col" class="text-end">Иҷрошуда</th><th scope="col" class="text-end">Гузашта</th><th scope="col" class="text-end">Афзалияти баланд</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <th scope="row" class="fw-normal"><?= $this->e($r['project_name']) ?></th>
          <td class="text-end"><?= (int) $r['total'] ?></td>
          <td class="text-end"><?= (int) $r['active'] ?></td>
          <td class="text-end"><?= (int) $r['done'] ?></td>
          <td class="text-end<?= (int) $r['overdue'] > 0 ? ' is-overdue' : '' ?>"><?= (int) $r['overdue'] ?></td>
          <td class="text-end"><?= (int) $r['high_count'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="fw-semibold">
          <th scope="row">Ҷамъ</th>
          <td class="text-end"><?= $totals['total'] ?></td><td class="text-end"><?= $totals['active'] ?></td><td class="text-end"><?= $totals['done'] ?></td>
          <td class="text-end"><?= $totals['overdue'] ?></td><td class="text-end"><?= $totals['high_count'] ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

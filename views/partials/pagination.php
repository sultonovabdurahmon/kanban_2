<?php
// Саҳифабандӣ: филтрҳо дар пайвандҳо нигоҳ дошта мешаванд. Ворид: $page, $pages, $filters, $basePath
$link = fn (int $n): string => $this->url($basePath) . '?' . http_build_query(
    array_filter($filters + ['page' => $n], fn ($v) => $v !== '' && $v !== null)
);
if ($pages > 1): ?>
<ul class="pagination mb-0">
  <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= $this->e($link(max(1, $page - 1))) ?>">Қафо</a></li>
  <?php for ($n = 1; $n <= $pages; $n++): ?>
  <li class="page-item<?= $n === $page ? ' active' : '' ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><a class="page-link" href="<?= $this->e($link($n)) ?>"><?= $n ?></a></li>
  <?php endfor; ?>
  <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>"><a class="page-link" href="<?= $this->e($link(min($pages, $page + 1))) ?>">Пеш</a></li>
</ul>
<?php endif; ?>

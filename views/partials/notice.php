<?php
// Паём аз параметри ?msg= (танҳо калидҳои маълум)
$messages = [
    'created'       => ['success', 'Вазифа илова карда шуд.'],
    'updated'       => ['success', 'Тағйирот захира шуд.'],
    'deleted'       => ['success', 'Вазифа нест карда шуд.'],
    'has_refs'      => ['warning', 'Вазифаро нест кардан мумкин нест: ба он сабтҳои дигар истинод мекунанд.'],
    'user_deleted'  => ['success', 'Корбар нест карда шуд.'],
    'user_has_refs' => ['warning', 'Корбареро, ки лоиҳа, вазифа ё шарҳ дорад, нест кардан мумкин нест. Аввал сабтҳои вобастаро нест кунед ё ба корбари дигар гузаронед.'],
];
$msg = (string) ($_GET['msg'] ?? '');
if (isset($messages[$msg])): [$type, $text] = $messages[$msg]; ?>
<div class="alert alert-<?= $type ?>" role="status"><?= $this->e($text) ?></div>
<?php endif; ?>

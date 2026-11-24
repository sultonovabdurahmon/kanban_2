<?php
// public/api/tasks/index.php — API-и МУВАҚҚАТӢ барои рӯзи 11.
// Дар кори лаб. 14 масири /api/tasks дар қисми серверӣ пайдо мешавад ва ин папкаро нест кардан лозим аст.
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$tasks = [
    ['id' => 1,  'title' => 'Тарҳи саҳифаи вуруд',          'project' => 'Сомонаи мағозаи КОРД', 'assignee' => 'Раҳимов Фарҳод',  'due_date' => '2026-11-20', 'priority' => 'high',   'column' => 'Дар иҷро'],
    ['id' => 2,  'title' => 'Пойгоҳи додаҳо барои мағоза',  'project' => 'Сомонаи мағозаи КОРД', 'assignee' => 'Назарова Мадина', 'due_date' => '2026-11-08', 'priority' => 'high',   'column' => 'Барои иҷро'],
    ['id' => 3,  'title' => 'Саҳифаи каталоги маҳсулот',    'project' => 'Сомонаи мағозаи КОРД', 'assignee' => 'Раҳимов Фарҳод',  'due_date' => '2026-11-25', 'priority' => 'medium', 'column' => 'Барои иҷро'],
    ['id' => 4,  'title' => 'Сабад ва пардохт',             'project' => 'Сомонаи мағозаи КОРД', 'assignee' => 'Назарова Мадина', 'due_date' => '2026-11-10', 'priority' => 'medium', 'column' => 'Санҷиш'],
    ['id' => 5,  'title' => 'Логотип ва рангҳо',            'project' => 'Сомонаи мағозаи КОРД', 'assignee' => 'Раҳимов Фарҳод',  'due_date' => '2026-10-30', 'priority' => 'low',    'column' => 'Иҷрошуда'],
    ['id' => 6,  'title' => 'Матни саҳифаи «Дар бораи мо»', 'project' => 'Сомонаи мағозаи КОРД', 'assignee' => null,              'due_date' => null,         'priority' => 'low',    'column' => 'Барои иҷро'],
    ['id' => 7,  'title' => 'Экрани воридшавӣ',             'project' => 'Барномаи мобилӣ',      'assignee' => 'Раҳимов Фарҳод',  'due_date' => '2026-11-18', 'priority' => 'high',   'column' => 'Дар иҷро'],
    ['id' => 8,  'title' => 'Ҳамоҳангсозӣ бо сервер',       'project' => 'Барномаи мобилӣ',      'assignee' => 'Ҷалилова Зарина', 'due_date' => '2026-11-05', 'priority' => 'high',   'column' => 'Барои иҷро'],
    ['id' => 9,  'title' => 'Санҷиши барнома дар Android',  'project' => 'Барномаи мобилӣ',      'assignee' => 'Ҷалилова Зарина', 'due_date' => '2026-12-01', 'priority' => 'medium', 'column' => 'Барои иҷро'],
    ['id' => 10, 'title' => 'Нақшаи ҷойгиркунӣ',            'project' => 'Барномаи мобилӣ',      'assignee' => 'Раҳимов Фарҳод',  'due_date' => '2026-10-20', 'priority' => 'medium', 'column' => 'Иҷрошуда'],
    ['id' => 11, 'title' => 'Боби якуми диплом',            'project' => 'Омодагӣ ба ҳимояи диплом', 'assignee' => 'Ҷалилова Зарина', 'due_date' => '2026-11-30', 'priority' => 'high', 'column' => 'Дар иҷро'],
    ['id' => 12, 'title' => 'Мутолиаи адабиёт',             'project' => 'Омодагӣ ба ҳимояи диплом', 'assignee' => 'Ғуломов Ҷамшед', 'due_date' => '2026-10-15', 'priority' => 'low',  'column' => 'Иҷрошуда'],
    ['id' => 13, 'title' => 'Тайёр кардани слайдҳо',        'project' => 'Омодагӣ ба ҳимояи диплом', 'assignee' => 'Ғуломов Ҷамшед', 'due_date' => '2026-12-10', 'priority' => 'medium', 'column' => 'Барои иҷро'],
    // Ҳарфи калон дар аввал: «ҳам» бо mb_strtolower ҳам «Ҳамоҳангсозӣ», ҳам «Ҳамкорӣ»-ро меёбад
    ['id' => 14, 'title' => 'Ҳамкорӣ бо мизоҷ',             'project' => 'Сомонаи мағозаи КОРД', 'assignee' => 'Назарова Мадина', 'due_date' => '2026-12-05', 'priority' => 'medium', 'column' => 'Барои иҷро'],
    // Сабт барои санҷиши экранкунӣ: ин «ном» набояд ҳамчун HTML иҷро шавад
    ['id' => 15, 'title' => '<img src=x onerror="alert(\'XSS\')"> Тестов', 'project' => 'Санҷишӣ', 'assignee' => null, 'due_date' => '2026-12-31', 'priority' => 'low', 'column' => 'Барои иҷро'],
];

$today = date('Y-m-d');
foreach ($tasks as &$t) {
    // Мӯҳлаташ гузашта ва дар сутуни охирин («Иҷрошуда») нест
    $t['is_overdue'] = $t['due_date'] !== null && $t['due_date'] < $today && $t['column'] !== 'Иҷрошуда';
}
unset($t);

// mb_strtolower — функсияи бисёрбайтӣ: strtolower ҳарфҳои кириллиро (Ҳ, Ҷ, Ғ) ба хурд табдил дода наметавонад
$q = mb_strtolower(trim((string) ($_GET['q'] ?? '')));
if ($q !== '') {
    $tasks = array_values(array_filter(
        $tasks,
        fn (array $t): bool => str_contains(mb_strtolower($t['title']), $q)
    ));
}
echo json_encode($tasks, JSON_UNESCAPED_UNICODE);

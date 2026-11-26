<?php
declare(strict_types=1);

namespace App\Controllers;

final class ProjectController extends Controller
{
    /** Рӯзи 12: додаҳои муваққатӣ. Калидҳо — сутунҳои ҷадвали projects ва тахаллусҳо. */
    private const DEMO = [
        1 => ['id' => 1, 'name' => 'Сомонаи мағозаи КОРД', 'description' => 'Мағозаи онлайнии либоси мардона',
              'owner_name' => 'Каримов Шоҳрух', 'members' => 3, 'tasks' => 6, 'created_at' => '2026-10-01 09:00:00'],
        2 => ['id' => 2, 'name' => 'Барномаи мобилӣ', 'description' => 'Барномаи мобилии мағоза барои Android',
              'owner_name' => 'Саидов Алишер', 'members' => 3, 'tasks' => 4, 'created_at' => '2026-10-05 10:30:00'],
        3 => ['id' => 3, 'name' => 'Омодагӣ ба ҳимояи диплом', 'description' => 'Тайёрии кори хатм ва слайдҳо',
              'owner_name' => 'Назарова Мадина', 'members' => 3, 'tasks' => 3, 'created_at' => '2026-10-10 08:15:00'],
    ];

    /** GET /projects */
    public function index(): void
    {
        $this->render('projects/index', ['title' => 'Лоиҳаҳо', 'active' => 'projects', 'projects' => array_values(self::DEMO)]);
    }

    /** GET /projects/{id} */
    public function show(int $id): void
    {
        $project = self::DEMO[$id] ?? null;
        if ($project === null) {
            $this->abort(404, 'Лоиҳа ёфт нашуд');
        }
        $this->render('projects/show', ['title' => $project['name'], 'active' => 'projects', 'project' => $project]);
    }
}

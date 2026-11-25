<?php
declare(strict_types=1);

final class TaskController extends Controller
{
    private const PER_PAGE = 10;
    public const PRIORITIES = ['high' => 'Баланд', 'medium' => 'Миёна', 'low' => 'Паст'];

    private TaskRepository $tasks;
    private ProjectRepository $projects;
    private ColumnRepository $columns;
    private UserRepository $users;

    public function __construct()
    {
        parent::__construct();
        $this->tasks = new TaskRepository();
        $this->projects = new ProjectRepository();
        $this->columns = new ColumnRepository();
        $this->users = new UserRepository();
    }

    /** GET /tasks — рӯйхат бо ҷустуҷӯ, филтр ва саҳифабандӣ */
    public function index(): void
    {
        $filters = [
            'q'           => trim((string) ($_GET['q'] ?? '')),
            'project_id'  => (string) ($_GET['project_id'] ?? ''),
            'priority'    => (string) ($_GET['priority'] ?? ''),
            'assignee_id' => (string) ($_GET['assignee_id'] ?? ''),
        ];
        $page = (int) ($_GET['page'] ?? 1);   // ?page=abc -> 0 -> дар paginate() ба 1 табдил меёбад
        $result = $this->tasks->paginate($filters, $page, self::PER_PAGE);

        $this->render('tasks/index', [
            'title' => 'Вазифаҳо', 'active' => 'tasks', 'filters' => $filters, 'result' => $result,
            'projects' => $this->projects->all(), 'users' => $this->users->all(), 'priorities' => self::PRIORITIES,
        ]);
    }

    /** GET /tasks/{id} */
    public function show(int $id): void
    {
        $task = $this->findOrFail($id);
        $this->render('tasks/show', [
            'title' => $task['title'], 'active' => 'tasks', 'task' => $task,
            'comments' => $this->tasks->comments($id), 'history' => $this->tasks->history($id),
            'priorities' => self::PRIORITIES,
        ]);
    }

    /** GET /tasks/create */
    public function create(): void
    {
        $this->renderForm(['priority' => 'medium'], [], null);
    }

    /** POST /tasks/create */
    public function store(): void
    {
        [$data, $errors] = $this->validate($_POST, null);
        if ($errors) {
            $this->renderForm($_POST, $errors, null, 422);   // шакл бо додаҳои воридшуда ва хатоҳо
            return;
        }
        // Воридшавӣ ҳанӯз нест (кори лаб. 14): созанда — соҳиби лоиҳа
        $createdBy = $this->projects->ownerId((int) $data['project_id']) ?? 1;
        $id = $this->tasks->create($data, $createdBy);
        $this->redirect('/tasks/' . $id . '?msg=created');    // PRG: пас аз POST — равонакунӣ ба GET
    }

    /** GET /tasks/{id}/edit */
    public function edit(int $id): void
    {
        $task = $this->findOrFail($id);
        $this->renderForm($task, [], $id);
    }

    /** POST /tasks/{id}/edit */
    public function update(int $id): void
    {
        $task = $this->findOrFail($id);
        [$data, $errors] = $this->validate($_POST, $id);
        if ($errors) {
            $this->renderForm($_POST, $errors, $id, 422);
            return;
        }
        $this->tasks->update($id, $data, (int) $task['created_by']);
        $this->redirect('/tasks/' . $id . '?msg=updated');
    }

    /** POST /tasks/{id}/delete — танҳо POST, ҳеҷ гоҳ истинод */
    public function delete(int $id): void
    {
        $this->findOrFail($id);
        try {
            $this->tasks->delete($id);
        } catch (PDOException $e) {
            // 1451 — вайроншавии калиди берунӣ (истинодҳо ба вазифа)
            if (($e->errorInfo[1] ?? null) === 1451) {
                $this->redirect('/tasks/' . $id . '?msg=has_refs');
            }
            throw $e;
        }
        $this->redirect('/tasks?msg=deleted');
    }

    private function findOrFail(int $id): array
    {
        return $this->tasks->find($id) ?? $this->notFound('Вазифа ёфт нашуд.');
    }

    private function renderForm(array $task, array $errors, ?int $id, int $code = 200): void
    {
        $this->render('tasks/form', [
            'title' => $id ? 'Таҳрири вазифа' : 'Вазифаи нав', 'active' => 'tasks',
            'task' => $task, 'errors' => $errors, 'id' => $id,
            'columns' => $this->columns->all(), 'users' => $this->users->all(), 'priorities' => self::PRIORITIES,
        ], $code);
    }

    /** @return array{0: array, 1: array} [додаҳои тозашуда, хатоҳо] */
    private function validate(array $input, ?int $id): array
    {
        $v = new Validator($input);
        $v->required('title', 'Ном')->maxLength('title', 200, 'Ном')
          ->required('column_id', 'Сутун')->in('column_id', $this->columns->ids(), 'Сутун')
          ->maxLength('description', 5000, 'Тавсиф')
          ->date('due_date', 'Мӯҳлат')
          ->required('priority', 'Афзалият')->in('priority', array_keys(self::PRIORITIES), 'Афзалият')
          ->in('assignee_id', $this->users->ids(), 'Иҷрокунанда');

        $str = fn (string $f): string => trim((string) ($input[$f] ?? ''));
        $data = [
            'title'       => $str('title'),
            'column_id'   => $str('column_id') !== '' ? (int) $str('column_id') : null,
            'project_id'  => null,
            'description' => $str('description') !== '' ? $str('description') : null,
            'assignee_id' => $str('assignee_id') !== '' ? (int) $str('assignee_id') : null,
            'due_date'    => $str('due_date') !== '' ? $str('due_date') : null,
            'priority'    => $str('priority'),
        ];

        $errors = $v->errors();
        // Лоиҳа аз рӯи сутун муайян мешавад
        if (!isset($errors['column_id']) && $data['column_id'] !== null) {
            $data['project_id'] = $this->columns->projectIdOf($data['column_id']);
        }
        // Қоидаҳое, ки ба ПД муроҷиат мекунанд — баъд аз қоидаҳои асосӣ
        if ($data['project_id'] !== null) {
            if (!isset($errors['assignee_id']) && $data['assignee_id'] !== null
                && !in_array($data['assignee_id'], $this->users->memberIds($data['project_id']), true)) {
                $v->addError('assignee_id', 'Иҷрокунанда узви ин лоиҳа нест');
            }
            if (!isset($errors['title']) && $data['title'] !== ''
                && $this->tasks->titleExists($data['project_id'], $data['title'], $id)) {
                $v->addError('title', 'Вазифа бо чунин ном дар ин лоиҳа аллакай мавҷуд аст');
            }
        }
        return [$data, $v->errors()];
    }
}

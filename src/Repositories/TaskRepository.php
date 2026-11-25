<?php
declare(strict_types=1);

final class TaskRepository
{
    /** Майдонҳое, ки аз шакл захира кардан мумкин аст (рӯйхати сафед) */
    public const FIELDS = ['project_id', 'column_id', 'title', 'description', 'assignee_id', 'due_date', 'priority'];

    /** Охирин сутуни лоиҳа (= «иҷрошуда») барои вазифаи t */
    private const LAST_COLUMN = '(SELECT c2.id FROM columns c2 WHERE c2.project_id = t.project_id ORDER BY c2.position DESC, c2.id DESC LIMIT 1)';

    /** Қисми умумии SELECT: вазифа + лоиҳа + сутун + иҷрокунанда + созанда */
    private const SELECT = "
        SELECT t.*,
               p.name  AS project_name,
               c.title AS column_title,
               a.name  AS assignee_name,
               cb.name AS creator_name,
               (t.due_date IS NOT NULL AND t.due_date < CURDATE()
                AND t.column_id <> " . self::LAST_COLUMN . ") AS is_overdue
        FROM tasks t
        JOIN projects p  ON p.id = t.project_id
        JOIN columns c   ON c.id = t.column_id
        LEFT JOIN users a ON a.id = t.assignee_id
        JOIN users cb    ON cb.id = t.created_by";

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /** WHERE аз филтрҳо: ба SQL танҳо номи параметрҳо дохил мешавад, қиматҳо — ба $params */
    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            // addcslashes: % ва _ дар сатри ҷустуҷӯ аломати оддӣ мешаванд, на «ҳама чиз»
            $conditions[] = 't.title LIKE :q';
            $params[':q'] = '%' . addcslashes($q, '%_\\') . '%';
        }
        if (!empty($filters['project_id'])) {
            $conditions[] = 't.project_id = :project_id';
            $params[':project_id'] = (int) $filters['project_id'];
        }
        if (!empty($filters['priority']) && in_array($filters['priority'], ['low', 'medium', 'high'], true)) {
            $conditions[] = 't.priority = :priority';
            $params[':priority'] = (string) $filters['priority'];
        }
        if (!empty($filters['assignee_id'])) {
            $conditions[] = 't.assignee_id = :assignee_id';
            $params[':assignee_id'] = (int) $filters['assignee_id'];
        }
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }

    public function paginate(array $filters, int $page, int $perPage = 10): array
    {
        [$where, $params] = $this->buildWhere($filters);

        // 1) ҳамагӣ чанд сабт ба филтр мувофиқ аст
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks t {$where}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // 2) рақами саҳифа бо диапазони 1…pages маҳдуд карда мешавад
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        // 3) саҳифаи лозимӣ: LIMIT — чанд сатр, OFFSET — чандторо гузарондан
        $sql = self::SELECT . " {$where} ORDER BY t.due_date IS NULL, t.due_date, t.id LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE t.id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function comments(int $taskId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.body, c.created_at, u.name FROM task_comments c JOIN users u ON u.id = c.user_id
             WHERE c.task_id = :id ORDER BY c.id'
        );
        $stmt->execute([':id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function history(int $taskId): array
    {
        $stmt = $this->db->prepare(
            'SELECT h.action, h.details, h.created_at, u.name FROM task_history h JOIN users u ON u.id = h.user_id
             WHERE h.task_id = :id ORDER BY h.created_at DESC, h.id DESC'
        );
        $stmt->execute([':id' => $taskId]);
        return $stmt->fetchAll();
    }

    /** Тартиби навбатии охири сутун */
    private function nextPosition(int $columnId): int
    {
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM tasks WHERE column_id = :column_id');
        $stmt->execute([':column_id' => $columnId]);
        return (int) $stmt->fetchColumn();
    }

    /** Эҷод + сабти таърих дар як транзаксия: ҳама ё ҳеҷ */
    public function create(array $data, int $createdBy): int
    {
        $this->db->beginTransaction();
        try {
            $columns = implode(', ', self::FIELDS);
            $placeholders = ':' . implode(', :', self::FIELDS);
            $stmt = $this->db->prepare(
                "INSERT INTO tasks ({$columns}, position, created_by) VALUES ({$placeholders}, :position, :created_by)"
            );
            $stmt->execute($this->onlyFields($data) + [
                ':position'   => $this->nextPosition((int) $data['column_id']),
                ':created_by' => $createdBy,
            ]);
            $id = (int) $this->db->lastInsertId();
            $this->addHistory($id, $createdBy, 'created', 'Вазифа сохта шуд');
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data, int $userId): void
    {
        $this->db->beginTransaction();
        try {
            $old = $this->find($id);
            $set = implode(', ', array_map(fn ($f) => "{$f} = :{$f}", self::FIELDS));
            $params = $this->onlyFields($data) + [':id' => $id];
            // агар сутун иваз шуд, корт ба охири сутуни нав меравад
            $position = '';
            if ($old && (int) $old['column_id'] !== (int) $data['column_id']) {
                $position = ', position = :position';
                $params[':position'] = $this->nextPosition((int) $data['column_id']);
            }
            $stmt = $this->db->prepare("UPDATE tasks SET {$set}{$position} WHERE id = :id");
            $stmt->execute($params);
            $this->addHistory($id, $userId, 'update', 'Вазифа таҳрир шуд');
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /** Беназирӣ: дар як лоиҳа ду вазифа бо як ном нест (ҳарфҳои калону хурд аҳамият надоранд) */
    public function titleExists(int $projectId, string $title, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM tasks WHERE project_id = :project_id AND title = :title';
        $params = [':project_id' => $projectId, ':title' => $title];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function addHistory(int $taskId, int $userId, string $action, string $details): void
    {
        $stmt = $this->db->prepare('INSERT INTO task_history (task_id, user_id, action, details) VALUES (:t, :u, :a, :d)');
        $stmt->execute([':t' => $taskId, ':u' => $userId, ':a' => $action, ':d' => $details]);
    }

    /** Барои саҳифаи асосӣ: ҳамагӣ, иҷрошуда, мӯҳлаташон гузашта */
    public function stats(): array
    {
        $last = self::LAST_COLUMN;
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(t.column_id = {$last}), 0) AS done,
                       COALESCE(SUM(t.due_date IS NOT NULL AND t.due_date < CURDATE() AND t.column_id <> {$last}), 0) AS overdue
                FROM tasks t";
        $row = $this->db->query($sql)->fetch();
        return array_map('intval', $row);
    }

    public function latest(int $limit = 5): array
    {
        $stmt = $this->db->prepare(self::SELECT . ' ORDER BY t.created_at DESC, t.id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Ҳисобот: вазифаҳо аз рӯи лоиҳаҳо (GROUP BY + агрегатсияи шартӣ) */
    public function projectReport(): array
    {
        $last = '(SELECT c2.id FROM columns c2 WHERE c2.project_id = p.id ORDER BY c2.position DESC, c2.id DESC LIMIT 1)';
        $sql = "
            SELECT p.id, p.name AS project_name,
                   COUNT(t.id)                                               AS total,
                   COALESCE(SUM(t.column_id = {$last}), 0)                   AS done,
                   COALESCE(SUM(t.column_id <> {$last}), 0)                  AS active,
                   COALESCE(SUM(t.due_date < CURDATE() AND t.column_id <> {$last}), 0) AS overdue,
                   COALESCE(SUM(t.priority = 'high'), 0)                     AS high_count
            FROM projects p
            LEFT JOIN tasks t ON t.project_id = p.id
            GROUP BY p.id, p.name
            ORDER BY p.name";
        return $this->db->query($sql)->fetchAll();
    }

    /** Танҳо майдонҳои рӯйхати сафед; номи параметр бо «:» оғоз мешавад */
    private function onlyFields(array $data): array
    {
        $out = [];
        foreach (self::FIELDS as $field) {
            $out[':' . $field] = $data[$field] ?? null;
        }
        return $out;
    }
}

<?php
declare(strict_types=1);

final class UserRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function all(): array
    {
        return $this->db->query('SELECT id, name FROM users ORDER BY name')->fetchAll();
    }

    public function ids(): array
    {
        return array_map('intval', array_column($this->all(), 'id'));
    }

    /** Иштирокчиёни лоиҳа */
    public function memberIds(int $projectId): array
    {
        $stmt = $this->db->prepare('SELECT user_id FROM project_members WHERE project_id = :id');
        $stmt->execute([':id' => $projectId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Рӯйхати корбарон бо шумораи вазифаҳояшон */
    public function withStats(): array
    {
        return $this->db->query(
            'SELECT u.id, u.name, u.email,
                    (SELECT COUNT(*) FROM tasks t WHERE t.created_by = u.id)  AS created_tasks,
                    (SELECT COUNT(*) FROM tasks t WHERE t.assignee_id = u.id) AS assigned_tasks
             FROM users u ORDER BY u.name'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}

<?php
declare(strict_types=1);

final class ColumnRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /** Ҳамаи сутунҳо бо номи лоиҳа (барои рӯйхати интихоб) */
    public function all(): array
    {
        return $this->db->query(
            'SELECT c.id, c.title, c.project_id, p.name AS project_name
             FROM columns c JOIN projects p ON p.id = c.project_id
             ORDER BY p.name, c.position, c.id'
        )->fetchAll();
    }

    public function ids(): array
    {
        return array_map('intval', array_column($this->all(), 'id'));
    }

    public function projectIdOf(int $columnId): ?int
    {
        $stmt = $this->db->prepare('SELECT project_id FROM columns WHERE id = :id');
        $stmt->execute([':id' => $columnId]);
        $v = $stmt->fetchColumn();
        return $v === false ? null : (int) $v;
    }
}

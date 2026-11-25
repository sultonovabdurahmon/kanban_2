<?php
declare(strict_types=1);

final class ProjectRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function all(): array
    {
        return $this->db->query('SELECT id, name FROM projects ORDER BY name')->fetchAll();
    }

    public function ownerId(int $projectId): ?int
    {
        $stmt = $this->db->prepare('SELECT owner_id FROM projects WHERE id = :id');
        $stmt->execute([':id' => $projectId]);
        $v = $stmt->fetchColumn();
        return $v === false ? null : (int) $v;
    }
}

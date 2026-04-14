<?php
/**
 * Base Model Class
 * Provides common functionality for all models
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

abstract class Model
{
    protected static string $table;
    protected PDO $db;
    protected array $attributes = [];
    protected array $changes = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new record
     */
    public function create(array $data): array
    {
        $data['id'] = $data['id'] ?? str_replace('-', '', uniqid() . bin2hex(random_bytes(6)));
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->find($data['id']);
    }

    /**
     * Find a record by ID
     */
    public function find(string $id): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find records by criteria
     */
    public function findBy(array $criteria, array $options = []): array
    {
        $where = [];
        $params = [];

        foreach ($criteria as $column => $value) {
            $where[] = "{$column} = ?";
            $params[] = $value;
        }

        $sql = "SELECT * FROM " . static::$table;
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        if ($limit = $options['limit'] ?? null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        if ($offset = $options['offset'] ?? null) {
            $sql .= " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all records
     */
    public function all(array $options = []): array
    {
        $sql = "SELECT * FROM " . static::$table;

        if ($limit = $options['limit'] ?? null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        if ($offset = $options['offset'] ?? null) {
            $sql .= " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update a record
     */
    public function update(string $id, array $data): array
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $set = [];
        $params = [];

        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE " . static::$table . " SET " . implode(", ", $set) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->find($id);
    }

    /**
     * Soft delete a record
     */
    public function delete(string $id): bool
    {
        return (bool)$this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Permanently delete a record
     */
    public function destroy(string $id): bool
    {
        $sql = "DELETE FROM " . static::$table . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        $where = [];
        $params = [];

        foreach ($criteria as $column => $value) {
            $where[] = "{$column} = ?";
            $params[] = $value;
        }

        $sql = "SELECT COUNT(*) as count FROM " . static::$table;
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Execute raw query
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute statement for single row
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Begin transaction
     */
    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    protected function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    protected function rollback(): void
    {
        $this->db->rollBack();
    }
}

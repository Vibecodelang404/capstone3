<?php
/**
 * Supplier Model
 */

declare(straight_types=1);

class Supplier extends Model
{
    protected static string $table = 'suppliers';

    /**
     * Find active suppliers
     */
    public function findActive(array $options = []): array
    {
        $sql = "SELECT * FROM suppliers WHERE is_active = 1 AND deleted_at IS NULL ORDER BY name ASC";

        if ($limit = $options['limit'] ?? null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find by name
     */
    public function findByName(string $name): ?array
    {
        return $this->queryOne(
            "SELECT * FROM suppliers WHERE name = ? AND deleted_at IS NULL",
            [$name]
        );
    }

    /**
     * Search suppliers
     */
    public function search(string $query): array
    {
        $sql = "SELECT * FROM suppliers WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ?) AND is_active = 1 AND deleted_at IS NULL ORDER BY name ASC";
        $search = "%{$query}%";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$search, $search, $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get supplier with product count
     */
    public function getWithProductCount(string $id): ?array
    {
        $supplier = $this->find($id);
        if (!$supplier) return null;

        $count = $this->queryOne(
            "SELECT COUNT(*) as count FROM products WHERE supplier_id = ? AND deleted_at IS NULL",
            [$id]
        );

        $supplier['product_count'] = (int)($count['count'] ?? 0);
        return $supplier;
    }
}

<?php
/**
 * Category Model
 */

declare(strict_types=1);

require_once __DIR__ . '/../utils/Transformer.php';

class Category extends Model
{
    protected static string $table = 'categories';

    /**
     * Find active categories ordered by display order
     */
    public function findActive(): array
    {
        $sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return Transformer::toApiFormatArray($results);
    }

    /**
     * Find by slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->queryOne(
            "SELECT * FROM categories WHERE slug = ? AND is_active = 1",
            [$slug]
        );
    }

    /**
     * Check if slug exists
     */
    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM categories WHERE slug = ?";
        $params = [$slug];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Get category with product count
     */
    public function getWithProductCount(string $id): ?array
    {
        $category = $this->find($id);
        if (!$category) return null;

        $category = Transformer::toApiFormat($category);

        $count = $this->queryOne(
            "SELECT COUNT(*) as count FROM products WHERE category_id = ? AND is_active = 1 AND deleted_at IS NULL",
            [$id]
        );

        $category['productCount'] = (int)($count['count'] ?? 0);
        return $category;
    }
}

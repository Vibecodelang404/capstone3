<?php
/**
 * Product Model
 */

declare(strict_types=1);

class Product extends Model
{
    protected static string $table = 'products';

    /**
     * Find active products
     */
    public function findActive(array $options = []): array
    {
        $sql = "SELECT * FROM products WHERE is_active = 1 AND deleted_at IS NULL";
        
        if ($category = $options['category'] ?? null) {
            $sql .= " AND category_id = ?";
        }

        $sql .= " ORDER BY name ASC";

        if ($limit = $options['limit'] ?? null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $params = [];
        if ($category ?? null) $params[] = $category;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find by category
     */
    public function findByCategory(string $categoryId, array $options = []): array
    {
        $sql = "SELECT * FROM products WHERE category_id = ? AND is_active = 1 AND deleted_at IS NULL ORDER BY name ASC";
        
        if ($limit = $options['limit'] ?? null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find by SKU
     */
    public function findBySku(string $sku): ?array
    {
        return $this->queryOne(
            "SELECT * FROM products WHERE sku = ? AND deleted_at IS NULL",
            [$sku]
        );
    }

    /**
     * Find by barcode
     */
    public function findByBarcode(string $barcode): ?array
    {
        return $this->queryOne(
            "SELECT * FROM products WHERE barcode = ? AND deleted_at IS NULL",
            [$barcode]
        );
    }

    /**
     * Get product with inventory and variants
     */
    public function getWithDetails(string $id): ?array
    {
        $product = $this->find($id);
        if (!$product) return null;

        // Get variants
        $variantModel = new ProductVariant();
        $product['variants'] = $variantModel->findBy(['product_id' => $id]);

        // Get inventory
        $inventoryModel = new Inventory();
        $product['inventory'] = $inventoryModel->findBy(['product_id' => $id])[0] ?? null;

        return $product;
    }

    /**
     * Search products
     */
    public function search(string $query, array $options = []): array
    {
        $sql = "SELECT * FROM products WHERE (name LIKE ? OR sku LIKE ? OR barcode LIKE ?) AND is_active = 1 AND deleted_at IS NULL ORDER BY name ASC";

        if ($limit = $options['limit'] ?? null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $search = "%{$query}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$search, $search, $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

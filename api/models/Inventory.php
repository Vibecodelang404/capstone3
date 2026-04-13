<?php
/**
 * Inventory Model
 */

declare(strict_types=1);

class Inventory extends Model
{
    protected static string $table = 'inventory_levels';

    /**
     * Find by product
     */
    public function findByProduct(string $productId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM inventory_levels WHERE product_id = ?",
            [$productId]
        );
    }

    /**
     * Get low stock products
     */
    public function getLowStock(int $threshold = null): array
    {
        $sql = "SELECT i.*, p.name, p.sku FROM inventory_levels i 
                JOIN products p ON i.product_id = p.id 
                WHERE (i.shelf_qty + i.retail_qty * i.pcs_per_pack + i.wholesale_qty * i.pcs_per_pack * i.packs_per_box) <= i.reorder_point
                AND p.is_active = 1 AND p.deleted_at IS NULL
                ORDER BY i.shelf_qty ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update stock levels
     */
    public function updateStockLevels(string $productId, array $updates): array
    {
        $inventory = $this->findByProduct($productId);
        if (!$inventory) {
            throw new Exception("Inventory not found for product");
        }

        $updates['last_restock_at'] = $updates['last_restock_at'] ?? date('Y-m-d H:i:s');
        return $this->update($inventory['id'], $updates);
    }

    /**
     * Transfer stock between tiers
     */
    public function transfer(string $productId, string $from, string $to, int $quantity): array
    {
        $inventory = $this->findByProduct($productId);
        if (!$inventory) {
            throw new Exception("Inventory not found");
        }

        $fromColumn = $from . '_qty';
        $toColumn = $to . '_qty';

        if ($inventory[$fromColumn] < $quantity) {
            throw new Exception("Insufficient stock in {$from} tier");
        }

        $updates = [
            $fromColumn => $inventory[$fromColumn] - $quantity,
            $toColumn => $inventory[$toColumn] + $quantity,
        ];

        return $this->update($inventory['id'], $updates);
    }

    /**
     * Calculate total quantity
     */
    public function getTotalQuantity(string $productId): int
    {
        $inventory = $this->findByProduct($productId);
        if (!$inventory) return 0;

        return $inventory['shelf_qty'] + 
               ($inventory['retail_qty'] * $inventory['pcs_per_pack']) + 
               ($inventory['wholesale_qty'] * $inventory['pcs_per_pack'] * $inventory['packs_per_box']);
    }

    /**
     * Get all inventory with product info
     */
    public function getAll(bool $includeProducts = true): array
    {
        if ($includeProducts) {
            $sql = "SELECT i.*, p.name as product_name, p.sku as product_sku 
                    FROM inventory_levels i 
                    JOIN products p ON i.product_id = p.id 
                    WHERE p.is_active = 1 AND p.deleted_at IS NULL
                    ORDER BY p.name ASC";
        } else {
            $sql = "SELECT * FROM inventory_levels ORDER BY updated_at DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alias for findByProduct
     */
    public function getByProduct(string $productId): ?array
    {
        return $this->findByProduct($productId);
    }

    /**
     * Update inventory by product ID
     */
    public function updateByProduct(string $productId, array $updates): array
    {
        $inventory = $this->findByProduct($productId);
        if (!$inventory) {
            // Create new inventory record
            return $this->create(array_merge(['product_id' => $productId], $updates));
        }
        return $this->update($inventory['id'], $updates);
    }

    /**
     * Adjust stock with reason
     */
    public function adjust(string $productId, string $tier, int $quantity, string $reason): array
    {
        $inventory = $this->findByProduct($productId);
        if (!$inventory) {
            throw new Exception("Inventory not found");
        }

        $column = $tier . '_qty';
        $newQty = $inventory[$column] + $quantity;

        if ($newQty < 0) {
            throw new Exception("Cannot reduce stock below zero");
        }

        return $this->update($inventory['id'], [$column => $newQty]);
    }

    /**
     * Decrement shelf quantity (for sales)
     */
    public function decrementShelf(string $productId, int $quantity): void
    {
        $sql = "UPDATE inventory_levels SET shelf_qty = shelf_qty - ? WHERE product_id = ? AND shelf_qty >= ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$quantity, $productId, $quantity]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Insufficient shelf stock");
        }
    }

    /**
     * Increment shelf quantity (for returns/restocks)
     */
    public function incrementShelf(string $productId, int $quantity): void
    {
        $sql = "UPDATE inventory_levels SET shelf_qty = shelf_qty + ? WHERE product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$quantity, $productId]);
    }
}

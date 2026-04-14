<?php
/**
 * OrderItem Model
 */

declare(strict_types=1);

class OrderItem extends Model
{
    protected static string $table = 'order_items';

    /**
     * Override create to only add created_at (table doesn't have updated_at)
     */
    public function create(array $data): array
    {
        $data['id'] = $data['id'] ?? str_replace('-', '', uniqid() . bin2hex(random_bytes(6)));
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        // Note: Don't add updated_at - order_items table doesn't have this column

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->find($data['id']);
    }

    /**
     * Find items by order
     */
    public function findByOrder(string $orderId): array
    {
        return $this->findBy(['order_id' => $orderId]);
    }
}

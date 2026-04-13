<?php
/**
 * OrderItem Model
 */

declare(strict_types=1);

class OrderItem extends Model
{
    protected static string $table = 'order_items';

    /**
     * Find items by order
     */
    public function findByOrder(string $orderId): array
    {
        return $this->findBy(['order_id' => $orderId]);
    }
}

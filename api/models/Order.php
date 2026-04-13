<?php
/**
 * Order Model (Online/Customer Orders)
 */

declare(strict_types=1);

class Order extends Model
{
    protected static string $table = 'orders';

    /**
     * Find by order number
     */
    public function findByOrderNumber(string $orderNumber): ?array
    {
        return $this->queryOne(
            "SELECT * FROM orders WHERE order_number = ?",
            [$orderNumber]
        );
    }

    /**
     * Find orders by customer
     */
    public function findByCustomer(string $customerId, array $options = []): array
    {
        $sql = "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC";

        if ($status = $options['status'] ?? null) {
            $sql = str_replace("WHERE customer_id", "WHERE customer_id AND status = '{$status}'", $sql);
        }

        if ($limit = $options['limit'] ?? null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get order with items
     */
    public function getWithItems(string $id): ?array
    {
        $order = $this->find($id);
        if (!$order) return null;

        $itemModel = new OrderItem();
        $order['items'] = $itemModel->findBy(['order_id' => $id]);

        return $order;
    }

    /**
     * Find orders by status
     */
    public function findByStatus(string $status, array $options = []): array
    {
        $sql = "SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC";

        if ($limit = $options['limit'] ?? null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update order status
     */
    public function updateStatus(string $id, string $newStatus, ?string $userId = null): array
    {
        $updates = ['status' => $newStatus];

        if ($newStatus === 'processing' && !$userId) {
            throw new Exception("Processing requires processor ID");
        }

        if ($newStatus === 'processing') {
            $updates['processed_by'] = $userId;
            $updates['processed_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'completed') {
            $updates['completed_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'cancelled') {
            $updates['cancelled_at'] = date('Y-m-d H:i:s');
        }

        return $this->update($id, $updates);
    }

    /**
     * Get pending orders
     */
    public function getPendingOrders(array $options = []): array
    {
        return $this->findByStatus('pending', $options);
    }

    /**
     * Get sales summary
     */
    public function getSalesSummary(string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?";

        $result = $this->queryOne($sql, [$startDate, $endDate]);
        return $result ?? [
            'total_orders' => 0,
            'completed_orders' => 0,
            'pending_orders' => 0,
            'cancelled_orders' => 0,
            'total_revenue' => 0
        ];
    }

    /**
     * Generate unique order number
     */
    public function generateNumber(): string
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        
        // Get today's order count
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()"
        );
        $count = (int)($result['count'] ?? 0) + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }

    /**
     * Get orders by customer (alias)
     */
    public function getByCustomer(string $customerId): array
    {
        return $this->findByCustomer($customerId);
    }

    /**
     * Get paginated orders
     */
    public function getPaginated(int $page, int $perPage, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = "status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $conditions[] = "customer_id = ?";
            $params[] = $filters['customer_id'];
        }
        if (!empty($filters['start_date'])) {
            $conditions[] = "DATE(created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $conditions[] = "DATE(created_at) <= ?";
            $params[] = $filters['end_date'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM orders {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get items
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email as customer_email
                FROM orders o 
                LEFT JOIN users u ON o.customer_id = u.id 
                {$where} 
                ORDER BY o.created_at DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add customer name
        foreach ($items as &$item) {
            $item['customer_name'] = trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''));
            unset($item['first_name'], $item['last_name']);
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}

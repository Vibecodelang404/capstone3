<?php
/**
 * TransactionItem Model
 */

declare(strict_types=1);

class TransactionItem extends Model
{
    protected static string $table = 'transaction_items';

    /**
     * Find items by transaction
     */
    public function findByTransaction(string $transactionId): array
    {
        return $this->findBy(['transaction_id' => $transactionId]);
    }

    /**
     * Get top selling products
     */
    public function getTopProducts(string $startDate, string $endDate, int $limit = 10): array
    {
        $sql = "SELECT 
                ti.product_id,
                ti.product_name,
                SUM(ti.quantity) as total_quantity,
                SUM(ti.subtotal) as total_revenue,
                AVG(ti.unit_price) as avg_price,
                COUNT(DISTINCT ti.transaction_id) as transaction_count
            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status = 'completed'
            GROUP BY ti.product_id, ti.product_name
            ORDER BY total_revenue DESC
            LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

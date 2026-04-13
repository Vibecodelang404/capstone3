<?php
/**
 * Transaction Model (POS sales)
 */

declare(strict_types=1);

class Transaction extends Model
{
    protected static string $table = 'transactions';

    /**
     * Find by invoice number
     */
    public function findByInvoice(string $invoiceNumber): ?array
    {
        return $this->queryOne(
            "SELECT * FROM transactions WHERE invoice_number = ?",
            [$invoiceNumber]
        );
    }

    /**
     * Get transaction with items
     */
    public function getWithItems(string $id): ?array
    {
        $transaction = $this->find($id);
        if (!$transaction) return null;

        $itemModel = new TransactionItem();
        $transaction['items'] = $itemModel->findBy(['transaction_id' => $id]);

        return $transaction;
    }

    /**
     * Get sales for date range
     */
    public function getSalesReport(string $startDate, string $endDate, ?string $paymentType = null): array
    {
        $sql = "SELECT * FROM transactions WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'completed'";
        $params = [$startDate, $endDate];

        if ($paymentType) {
            $sql .= " AND payment_type = ?";
            $params[] = $paymentType;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get summary for date
     */
    public function getDailySummary(string $date): array
    {
        $sql = "SELECT 
                COUNT(*) as transaction_count,
                SUM(subtotal) as total_sales,
                SUM(discount_amount) as total_discounts,
                SUM(tax_amount) as total_tax,
                SUM(total) as grand_total
            FROM transactions 
            WHERE DATE(created_at) = ? AND status = 'completed'";

        $result = $this->queryOne($sql, [$date]);
        return $result ?? [
            'transaction_count' => 0,
            'total_sales' => 0,
            'total_discounts' => 0,
            'total_tax' => 0,
            'grand_total' => 0
        ];
    }

    /**
     * Void transaction
     */
    public function void(string $id, string $reason = ''): array
    {
        return $this->update($id, [
            'status' => 'voided',
            'notes' => $reason
        ]);
    }

    /**
     * Get payment type summary
     */
    public function getPaymentTypeSummary(string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                payment_type,
                COUNT(*) as count,
                SUM(total) as amount
            FROM transactions 
            WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'completed'
            GROUP BY payment_type";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate unique transaction number
     */
    public function generateNumber(): string
    {
        $prefix = 'TXN';
        $date = date('Ymd');
        
        // Get today's transaction count
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM transactions WHERE DATE(created_at) = CURDATE()"
        );
        $count = (int)($result['count'] ?? 0) + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }

    /**
     * Get paginated transactions
     */
    public function getPaginated(int $page, int $perPage, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];

        if (!empty($filters['start_date'])) {
            $conditions[] = "DATE(created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $conditions[] = "DATE(created_at) <= ?";
            $params[] = $filters['end_date'];
        }
        if (!empty($filters['payment_method'])) {
            $conditions[] = "payment_method = ?";
            $params[] = $filters['payment_method'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = "status = ?";
            $params[] = $filters['status'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM transactions {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get items
        $sql = "SELECT * FROM transactions {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Get comprehensive report
     */
    public function getReport(string $startDate, string $endDate): array
    {
        $transactions = $this->getSalesReport($startDate, $endDate);
        $paymentSummary = $this->getPaymentTypeSummary($startDate, $endDate);

        $totalSales = array_sum(array_column($transactions, 'total'));
        $transactionCount = count($transactions);

        return [
            'total_sales' => $totalSales,
            'total_transactions' => $transactionCount,
            'average_transaction' => $transactionCount > 0 ? $totalSales / $transactionCount : 0,
            'by_payment_method' => $paymentSummary,
            'transactions' => $transactions,
        ];
    }
}

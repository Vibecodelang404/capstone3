<?php
/**
 * Batch Model (FEFO tracking)
 */

declare(strict_types=1);

class Batch extends Model
{
    protected static string $table = 'batches';

    /**
     * Find batches by product (ordered by expiry for FEFO)
     */
    public function findByProduct(string $productId): array
    {
        $sql = "SELECT * FROM batches WHERE product_id = ? AND quantity_remaining > 0 ORDER BY expiry_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find expiring soon
     */
    public function findExpiringSoon(int $daysThreshold = 30): array
    {
        $sql = "SELECT b.*, p.name, p.sku FROM batches b 
                JOIN products p ON b.product_id = p.id 
                WHERE b.expiry_date <= DATE_ADD(NOW(), INTERVAL ? DAY) 
                AND b.expiry_date >= CURDATE() 
                AND b.quantity_remaining > 0
                ORDER BY b.expiry_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$daysThreshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find expired
     */
    public function findExpired(): array
    {
        $sql = "SELECT b.*, p.name, p.sku FROM batches b 
                JOIN products p ON b.product_id = p.id 
                WHERE b.expiry_date < CURDATE() 
                AND b.quantity_remaining > 0
                ORDER BY b.expiry_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Consume batch (FEFO)
     */
    public function consume(string $batchId, int $quantity): array
    {
        $batch = $this->find($batchId);
        if (!$batch) {
            throw new Exception("Batch not found");
        }

        if ($batch['quantity_remaining'] < $quantity) {
            throw new Exception("Insufficient quantity in batch");
        }

        return $this->update($batchId, [
            'quantity_remaining' => $batch['quantity_remaining'] - $quantity
        ]);
    }

    /**
     * Get batches by expiry status
     */
    public function getByExpiryStatus(string $productId): array
    {
        $batches = $this->findByProduct($productId);
        $now = date('Y-m-d');
        $expiringSoonDate = date('Y-m-d', strtotime('+30 days'));

        $result = [
            'good' => [],
            'expiring_soon' => [],
            'expired' => []
        ];

        foreach ($batches as $batch) {
            if ($batch['expiry_date'] < $now) {
                $result['expired'][] = $batch;
            } elseif ($batch['expiry_date'] <= $expiringSoonDate) {
                $result['expiring_soon'][] = $batch;
            } else {
                $result['good'][] = $batch;
            }
        }

        return $result;
    }

    /**
     * Get all batches
     */
    public function getAll(bool $includeEmpty = false): array
    {
        $sql = "SELECT b.*, p.name as product_name, p.sku as product_sku 
                FROM batches b 
                JOIN products p ON b.product_id = p.id";
        
        if (!$includeEmpty) {
            $sql .= " WHERE b.quantity_remaining > 0";
        }
        
        $sql .= " ORDER BY b.expiry_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get expiring batches
     */
    public function getExpiring(int $days = 30): array
    {
        return $this->findExpiringSoon($days);
    }

    /**
     * Get expired batches
     */
    public function getExpired(): array
    {
        return $this->findExpired();
    }

    /**
     * Alias for findByProduct
     */
    public function getByProduct(string $productId): array
    {
        return $this->findByProduct($productId);
    }

    /**
     * Check if batch number exists for product
     */
    public function batchExists(string $productId, string $batchNumber): bool
    {
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM batches WHERE product_id = ? AND batch_number = ?",
            [$productId, $batchNumber]
        );
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Consume using FEFO (First Expiry First Out)
     */
    public function consumeFEFO(string $productId, int $quantity): array
    {
        $batches = $this->findByProduct($productId);
        $remaining = $quantity;
        $consumed = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $toConsume = min($remaining, $batch['quantity_remaining']);
            $this->consume($batch['id'], $toConsume);
            
            $consumed[] = [
                'batch_id' => $batch['id'],
                'batch_number' => $batch['batch_number'],
                'quantity' => $toConsume,
                'expiry_date' => $batch['expiry_date'],
            ];

            $remaining -= $toConsume;
        }

        if ($remaining > 0) {
            throw new Exception("Insufficient stock. Short by {$remaining} units.");
        }

        return [
            'product_id' => $productId,
            'quantity_consumed' => $quantity,
            'batches_used' => $consumed,
        ];
    }
}

<?php
/**
 * ProductVariant Model
 */

declare(strict_types=1);

class ProductVariant extends Model
{
    protected static string $table = 'product_variants';

    /**
     * Find variants by product
     */
    public function findByProduct(string $productId): array
    {
        return $this->findBy(['product_id' => $productId, 'is_active' => 1]);
    }

    /**
     * Get variant with total price
     */
    public function getWithPrice(string $id): ?array
    {
        $variant = $this->find($id);
        if (!$variant) return null;

        // Get product to get base price
        $productModel = new Product();
        $product = $productModel->find($variant['product_id']);

        if ($product) {
            $variant['retail_price'] = $product['retail_price'] + $variant['price_adjustment'];
            $variant['wholesale_price'] = $product['wholesale_price'] + $variant['price_adjustment'];
        }

        return $variant;
    }
}

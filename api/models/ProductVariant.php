<?php
/**
 * ProductVariant Model
 */

declare(strict_types=1);

require_once __DIR__ . '/../utils/Transformer.php';

class ProductVariant extends Model
{
    protected static string $table = 'product_variants';

    /**
     * Find variants by product
     */
    public function findByProduct(string $productId): array
    {
        $results = $this->findBy(['product_id' => $productId, 'is_active' => 1]);
        return Transformer::toApiFormatArray($results);
    }

    /**
     * Get variant with total price
     */
    public function getWithPrice(string $id): ?array
    {
        $variant = $this->find($id);
        if (!$variant) return null;

        $variant = Transformer::toApiFormat($variant);

        // Get product to get base price
        $productModel = new Product();
        $product = $productModel->find($variant['productId']);

        if ($product) {
            $product = Transformer::toApiFormat($product);
            $variant['retailPrice'] = $product['retailPrice'] + $variant['priceAdjustment'];
            $variant['wholesalePrice'] = $product['wholesalePrice'] + $variant['priceAdjustment'];
        }

        return $variant;
    }
}

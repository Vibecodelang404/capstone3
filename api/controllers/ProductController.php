<?php
/**
 * Product Controller
 * Handles product CRUD operations
 */

declare(strict_types=1);

class ProductController extends Controller
{
    private Product $model;

    public function __construct()
    {
        $this->model = new Product();
    }

    public function handleRequest(string $method, ?string $id, ?string $action): void
    {
        match ($method) {
            'GET' => $this->handleGet($id, $action),
            'POST' => $this->handlePost(),
            'PUT' => $this->handlePut($id),
            'DELETE' => $this->handleDelete($id),
            default => Response::methodNotAllowed($method),
        };
    }

    /**
     * GET /api/products
     * GET /api/products/{id}
     * GET /api/products/search?q=query
     */
    private function handleGet(?string $id, ?string $action): void
    {
        // Search endpoint
        if ($action === 'search') {
            $query = $this->getQuery('q', '');
            if (strlen($query) < 2) {
                Response::badRequest('Search query must be at least 2 characters');
            }

            $products = $this->model->search($query, ['limit' => 50]);
            Response::success($products, 'Search results');
            return;
        }

        // Get single product
        if ($id) {
            $this->requireRole(['admin', 'manager', 'stockman', 'cashier']);
            $product = $this->model->getWithDetails($id);

            if (!$product) {
                Response::notFound('Product not found');
            }

            Response::success($product, 'Product retrieved');
            return;
        }

        // Get all products
        $this->requireRole(['admin', 'manager', 'stockman', 'cashier', 'customer']);
        
        $options = [];
        if ($category = $this->getQuery('category')) {
            $options['category'] = $category;
        }
        if ($limit = $this->getQuery('limit')) {
            $options['limit'] = (int)$limit;
        }

        $products = $this->model->findActive($options);
        Response::success($products, 'Products retrieved');
    }

    /**
     * POST /api/products
     */
    private function handlePost(): void
    {
        $this->requireRole(['admin', 'manager']);
        $data = $this->getRequestData();

        $errors = $this->validate($data, [
            'sku' => 'required|unique:products',
            'name' => 'required|min:3',
            'category_id' => 'required',
            'retail_price' => 'required|numeric|min:0',
            'wholesale_price' => 'numeric|min:0',
            'cost_price' => 'numeric|min:0',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        try {
            $product = $this->model->create([
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'cost_price' => (float)$data['cost_price'],
                'wholesale_price' => (float)$data['wholesale_price'],
                'retail_price' => (float)$data['retail_price'],
                'image_url' => $data['image_url'] ?? null,
                'is_active' => 1,
            ]);

            Response::success($product, 'Product created', 201);
        } catch (Exception $e) {
            Response::serverError('Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * PUT /api/products/{id}
     */
    private function handlePut(?string $id): void
    {
        $this->requireRole(['admin', 'manager']);

        if (!$id) {
            Response::badRequest('Product ID required');
        }

        $this->checkExists($this->model, $id, 'Product');
        $data = $this->getRequestData();

        $allowedFields = [
            'sku', 'barcode', 'name', 'description',
            'supplier_id', 'cost_price', 'wholesale_price',
            'retail_price', 'image_url', 'is_active'
        ];

        $updates = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            Response::badRequest('No fields to update');
        }

        try {
            $product = $this->model->update($id, $updates);
            Response::success($product, 'Product updated');
        } catch (Exception $e) {
            Response::serverError('Failed to update product');
        }
    }

    /**
     * DELETE /api/products/{id}
     */
    private function handleDelete(?string $id): void
    {
        $this->requireRole(['admin']);

        if (!$id) {
            Response::badRequest('Product ID required');
        }

        $this->checkExists($this->model, $id, 'Product');

        try {
            $this->model->delete($id);
            Response::success([], 'Product deleted');
        } catch (Exception $e) {
            Response::serverError('Failed to delete product');
        }
    }
}

<?php
/**
 * Inventory Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/Product.php';

class InventoryController extends Controller
{
    private Inventory $model;
    private Product $productModel;

    public function __construct()
    {
        $this->model = new Inventory();
        $this->productModel = new Product();
    }

    public function handleRequest(string $method, ?string $id, ?string $action): void
    {
        match ($method) {
            'GET' => $this->handleGet($id, $action),
            'POST' => $this->handlePost($action),
            'PUT' => $this->handlePut($id),
            default => Response::methodNotAllowed($method),
        };
    }

    private function handleGet(?string $id, ?string $action): void
    {
        // Public endpoint for guests to check stock levels

        if ($id === 'low-stock' || $id === 'alerts') {
            $threshold = (int)$this->getQuery('threshold', 10);
            $items = $this->model->getLowStock($threshold);
            Response::success($items, 'Low stock items retrieved');
            return;
        }

        if ($id) {
            $inventory = $this->model->getByProduct($id);
            if (!$inventory) {
                Response::notFound('Inventory not found for product');
            }
            Response::success($inventory, 'Inventory retrieved');
            return;
        }

        $includeProducts = $this->getQuery('include_products', 'true') === 'true';
        $inventory = $this->model->getAll($includeProducts);
        Response::success($inventory, 'Inventory retrieved');
    }

    private function handlePost(?string $action): void
    {
        $this->requireRole(['admin', 'manager', 'stockman']);
        $data = $this->getRequestData();

        match ($action) {
            'transfer' => $this->handleTransfer($data),
            'adjust' => $this->handleAdjust($data),
            default => Response::notFound('Inventory action not found'),
        };
    }

    private function handleTransfer(array $data): void
    {
        $errors = $this->validate($data, [
            'product_id' => 'required',
            'from_tier' => 'required|in:wholesale,retail,shelf',
            'to_tier' => 'required|in:wholesale,retail,shelf',
            'quantity' => 'required|numeric|min:1',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        if ($data['from_tier'] === $data['to_tier']) {
            Response::badRequest('Cannot transfer to same tier');
        }

        try {
            $result = $this->model->transfer(
                $data['product_id'],
                $data['from_tier'],
                $data['to_tier'],
                (int)$data['quantity']
            );
            Response::success($result, 'Stock transferred');
        } catch (Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    private function handleAdjust(array $data): void
    {
        $errors = $this->validate($data, [
            'product_id' => 'required',
            'tier' => 'required|in:wholesale,retail,shelf',
            'quantity' => 'required|numeric',
            'reason' => 'required|min:3',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        try {
            $result = $this->model->adjust(
                $data['product_id'],
                $data['tier'],
                (int)$data['quantity'],
                $data['reason']
            );
            Response::success($result, 'Stock adjusted');
        } catch (Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    private function handlePut(?string $id): void
    {
        $this->requireRole(['admin', 'manager', 'stockman']);

        if (!$id) {
            Response::badRequest('Product ID required');
        }

        if (!$this->productModel->find($id)) {
            Response::notFound('Product not found');
        }

        $data = $this->getRequestData();
        $allowedFields = ['wholesale_qty', 'retail_qty', 'shelf_qty', 'reorder_point', 'max_stock'];
        $updates = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[$field] = (int)$data[$field];
            }
        }

        if (empty($updates)) {
            Response::badRequest('No fields to update');
        }

        try {
            $inventory = $this->model->updateByProduct($id, $updates);
            Response::success($inventory, 'Inventory updated');
        } catch (Exception $e) {
            Response::serverError('Failed to update inventory');
        }
    }
}

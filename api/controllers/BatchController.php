<?php
/**
 * Batch Controller - FEFO tracking
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/Batch.php';
require_once __DIR__ . '/../models/Product.php';

class BatchController extends Controller
{
    private Batch $model;
    private Product $productModel;

    public function __construct()
    {
        $this->model = new Batch();
        $this->productModel = new Product();
    }

    public function handleRequest(string $method, ?string $id, ?string $action): void
    {
        match ($method) {
            'GET' => $this->handleGet($id, $action),
            'POST' => $this->handlePost($action),
            'PUT' => $this->handlePut($id),
            'DELETE' => $this->handleDelete($id),
            default => Response::methodNotAllowed($method),
        };
    }

    private function handleGet(?string $id, ?string $action): void
    {
        $this->requireRole(['admin', 'manager', 'stockman']);

        if ($id === 'expiring') {
            $days = (int)$this->getQuery('days', 30);
            $batches = $this->model->getExpiring($days);
            Response::success($batches, 'Expiring batches retrieved');
            return;
        }

        if ($id === 'expired') {
            $batches = $this->model->getExpired();
            Response::success($batches, 'Expired batches retrieved');
            return;
        }

        if ($id) {
            $batches = $this->model->getByProduct($id);
            Response::success($batches, 'Product batches retrieved');
            return;
        }

        $includeEmpty = $this->getQuery('include_empty', 'false') === 'true';
        $batches = $this->model->getAll($includeEmpty);
        Response::success($batches, 'Batches retrieved');
    }

    private function handlePost(?string $action): void
    {
        $this->requireRole(['admin', 'manager', 'stockman']);
        $data = $this->getRequestData();

        if ($action === 'consume') {
            $this->handleConsume($data);
            return;
        }

        $errors = $this->validate($data, [
            'product_id' => 'required',
            'batch_number' => 'required|min:3',
            'quantity' => 'required|numeric|min:1',
            'expiry_date' => 'required|date',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        if (!$this->productModel->find($data['product_id'])) {
            Response::notFound('Product not found');
        }

        if ($this->model->batchExists($data['product_id'], $data['batch_number'])) {
            Response::badRequest('Batch number already exists for this product');
        }

        try {
            $batch = $this->model->create([
                'product_id' => $data['product_id'],
                'batch_number' => $data['batch_number'],
                'quantity_received' => (int)$data['quantity'],
                'quantity_remaining' => (int)$data['quantity'],
                'cost_price' => (float)($data['cost_price'] ?? 0),
                'expiry_date' => $data['expiry_date'],
                'received_at' => date('Y-m-d H:i:s'),
                'notes' => $data['notes'] ?? null,
            ]);

            Response::created($batch, 'Batch created');
        } catch (Exception $e) {
            Response::serverError('Failed to create batch');
        }
    }

    private function handleConsume(array $data): void
    {
        $errors = $this->validate($data, [
            'product_id' => 'required',
            'quantity' => 'required|numeric|min:1',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        try {
            $result = $this->model->consumeFEFO($data['product_id'], (int)$data['quantity']);
            Response::success($result, 'Stock consumed using FEFO');
        } catch (Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    private function handlePut(?string $id): void
    {
        $this->requireRole(['admin', 'manager', 'stockman']);

        if (!$id) {
            Response::badRequest('Batch ID required');
        }

        $this->checkExists($this->model, $id, 'Batch');
        $data = $this->getRequestData();

        $allowedFields = ['batch_number', 'quantity_remaining', 'cost_price', 'expiry_date', 'notes'];
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
            $batch = $this->model->update($id, $updates);
            Response::success($batch, 'Batch updated');
        } catch (Exception $e) {
            Response::serverError('Failed to update batch');
        }
    }

    private function handleDelete(?string $id): void
    {
        $this->requireRole(['admin']);

        if (!$id) {
            Response::badRequest('Batch ID required');
        }

        $this->checkExists($this->model, $id, 'Batch');

        try {
            $this->model->delete($id);
            Response::success([], 'Batch deleted');
        } catch (Exception $e) {
            Response::serverError('Failed to delete batch');
        }
    }
}

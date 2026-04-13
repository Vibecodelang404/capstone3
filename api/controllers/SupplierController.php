<?php
/**
 * Supplier Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/Supplier.php';

class SupplierController extends Controller
{
    private Supplier $model;

    public function __construct()
    {
        $this->model = new Supplier();
    }

    public function handleRequest(string $method, ?string $id, ?string $action): void
    {
        match ($method) {
            'GET' => $this->handleGet($id),
            'POST' => $this->handlePost(),
            'PUT' => $this->handlePut($id),
            'DELETE' => $this->handleDelete($id),
            default => Response::methodNotAllowed($method),
        };
    }

    private function handleGet(?string $id): void
    {
        $this->requireRole(['admin', 'manager', 'stockman']);

        if ($id) {
            $supplier = $this->model->find($id);
            if (!$supplier) {
                Response::notFound('Supplier not found');
            }
            Response::success($supplier, 'Supplier retrieved');
            return;
        }

        $suppliers = $this->model->findActive();
        Response::success($suppliers, 'Suppliers retrieved');
    }

    private function handlePost(): void
    {
        $this->requireRole(['admin', 'manager']);
        $data = $this->getRequestData();

        $errors = $this->validate($data, [
            'name' => 'required|min:2',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        try {
            $supplier = $this->model->create([
                'name' => $data['name'],
                'contact_person' => $data['contact_person'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => 1,
            ]);

            Response::created($supplier, 'Supplier created');
        } catch (Exception $e) {
            Response::serverError('Failed to create supplier');
        }
    }

    private function handlePut(?string $id): void
    {
        $this->requireRole(['admin', 'manager']);

        if (!$id) {
            Response::badRequest('Supplier ID required');
        }

        $this->checkExists($this->model, $id, 'Supplier');
        $data = $this->getRequestData();

        $allowedFields = ['name', 'contact_person', 'email', 'phone', 'address', 'payment_terms', 'notes', 'is_active'];
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
            $supplier = $this->model->update($id, $updates);
            Response::success($supplier, 'Supplier updated');
        } catch (Exception $e) {
            Response::serverError('Failed to update supplier');
        }
    }

    private function handleDelete(?string $id): void
    {
        $this->requireRole(['admin']);

        if (!$id) {
            Response::badRequest('Supplier ID required');
        }

        $this->checkExists($this->model, $id, 'Supplier');

        try {
            $this->model->delete($id);
            Response::success([], 'Supplier deleted');
        } catch (Exception $e) {
            Response::serverError('Failed to delete supplier');
        }
    }
}

<?php
/**
 * Order Controller - Customer orders
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Inventory.php';

class OrderController extends Controller
{
    private Order $model;
    private OrderItem $itemModel;
    private Product $productModel;
    private Inventory $inventoryModel;

    public function __construct()
    {
        $this->model = new Order();
        $this->itemModel = new OrderItem();
        $this->productModel = new Product();
        $this->inventoryModel = new Inventory();
    }

    public function handleRequest(string $method, ?string $id, ?string $action): void
    {
        match ($method) {
            'GET' => $this->handleGet($id, $action),
            'POST' => $this->handlePost(),
            'PUT' => $this->handlePut($id, $action),
            'DELETE' => $this->handleDelete($id),
            default => Response::methodNotAllowed($method),
        };
    }

    private function handleGet(?string $id, ?string $action): void
    {
        $payload = $this->requireAuth();

        if ($id === 'customer' && $action) {
            $this->requireRole(['admin', 'manager']);
            $orders = $this->model->getByCustomer($action);
            Response::success($orders, 'Customer orders retrieved');
            return;
        }

        if ($id) {
            $order = $this->model->getWithItems($id);
            if (!$order) {
                Response::notFound('Order not found');
            }

            if ($payload['role'] === 'customer' && $order['customer_id'] !== $payload['id']) {
                Response::forbidden('Not authorized to view this order');
            }

            Response::success($order, 'Order retrieved');
            return;
        }

        if ($payload['role'] === 'customer') {
            $orders = $this->model->getByCustomer($payload['id']);
            Response::success($orders, 'Your orders retrieved');
            return;
        }

        $this->requireRole(['admin', 'manager', 'cashier']);
        $page = (int)$this->getQuery('page', 1);
        $perPage = (int)$this->getQuery('per_page', 20);
        $result = $this->model->getPaginated($page, $perPage, [
            'status' => $this->getQuery('status'),
            'start_date' => $this->getQuery('start_date'),
            'end_date' => $this->getQuery('end_date'),
        ]);

        Response::paginated($result['items'], $result['total'], $page, $perPage, 'Orders retrieved');
    }

    private function handlePost(): void
    {
        $payload = $this->requireAuth();
        $data = $this->getRequestData();

        $errors = $this->validate($data, [
            'items' => 'required|array',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        if (empty($data['items'])) {
            Response::badRequest('At least one item is required');
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $subtotal = 0;
            $processedItems = [];

            foreach ($data['items'] as $item) {
                $product = $this->productModel->find($item['product_id']);
                if (!$product) {
                    throw new Exception("Product not found: {$item['product_id']}");
                }

                $quantity = (int)$item['quantity'];
                $price = $product['retail_price'];
                $itemTotal = $price * $quantity;
                $subtotal += $itemTotal;

                $processedItems[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'total' => $itemTotal,
                ];
            }

            $taxRate = (float)($data['tax_rate'] ?? 0);
            $deliveryFee = (float)($data['delivery_fee'] ?? 0);
            $taxAmount = $subtotal * ($taxRate / 100);
            $total = $subtotal + $taxAmount + $deliveryFee;

            $order = $this->model->create([
                'order_number' => $this->model->generateNumber(),
                'customer_id' => $payload['role'] === 'customer' ? $payload['id'] : ($data['customer_id'] ?? $payload['id']),
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_status' => 'pending',
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
            ]);

            foreach ($processedItems as $item) {
                $this->itemModel->create([
                    'order_id' => $order['id'],
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);
            }

            $db->commit();
            $order['items'] = $processedItems;
            Response::created($order, 'Order placed successfully');

        } catch (Exception $e) {
            $db->rollBack();
            Response::badRequest($e->getMessage());
        }
    }

    private function handlePut(?string $id, ?string $action): void
    {
        $payload = $this->requireAuth();

        if (!$id) {
            Response::badRequest('Order ID required');
        }

        $order = $this->model->find($id);
        if (!$order) {
            Response::notFound('Order not found');
        }

        if ($action === 'status') {
            $this->requireRole(['admin', 'manager', 'cashier']);
            $data = $this->getRequestData();

            if (empty($data['status'])) {
                Response::badRequest('Status is required');
            }

            $validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
            if (!in_array($data['status'], $validStatuses)) {
                Response::badRequest('Invalid status');
            }

            try {
                $updated = $this->model->updateStatus($id, $data['status'], $payload['id']);
                Response::success(['status' => $data['status']], 'Order status updated');
            } catch (Exception $e) {
                Response::serverError('Failed to update order status');
            }
            return;
        }

        if ($action === 'cancel') {
            if ($payload['role'] === 'customer' && $order['customer_id'] !== $payload['id']) {
                Response::forbidden('Not authorized to cancel this order');
            }

            if (!in_array($order['status'], ['pending', 'confirmed'])) {
                Response::badRequest('Order cannot be cancelled at this stage');
            }

            try {
                $this->model->updateStatus($id, 'cancelled');
                Response::success([], 'Order cancelled');
            } catch (Exception $e) {
                Response::serverError('Failed to cancel order');
            }
            return;
        }

        Response::badRequest('Invalid action');
    }

    private function handleDelete(?string $id): void
    {
        $this->requireRole(['admin']);

        if (!$id) {
            Response::badRequest('Order ID required');
        }

        $this->checkExists($this->model, $id, 'Order');

        try {
            $this->model->delete($id);
            Response::success([], 'Order deleted');
        } catch (Exception $e) {
            Response::serverError('Failed to delete order');
        }
    }
}

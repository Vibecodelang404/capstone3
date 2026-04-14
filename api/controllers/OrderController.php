<?php
/**
 * Order Controller - Customer orders
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../middleware/auth.php';

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
        if ($id === 'track') {
            match ($method) {
                'GET', 'POST' => $this->track(),
                default => Response::methodNotAllowed($method),
            };
            return;
        }

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

    private function track(): void
    {
        $data = $this->getRequestData();
        $orderNumber = $this->getQuery('order_number') ?? $this->getQuery('orderNumber') ?? $data['order_number'] ?? $data['orderNumber'] ?? null;
        $customerPhone = $this->getQuery('customer_phone') ?? $this->getQuery('customerPhone') ?? $this->getQuery('phone') ?? $data['customer_phone'] ?? $data['customerPhone'] ?? $data['phone'] ?? null;

        if (!$orderNumber || !$customerPhone) {
            Response::badRequest('order_number and customer_phone are required');
        }

        $order = $this->model->findByOrderNumberAndPhone($orderNumber, $customerPhone);
        if (!$order) {
            Response::notFound('Order not found');
        }

        $orderWithItems = $this->model->getWithItems($order['id']);
        if (!$orderWithItems) {
            Response::notFound('Order not found');
        }

        Response::success($orderWithItems, 'Order tracked successfully');
    }

    private function handlePost(): void
    {
        try {
            // Optional authentication allows guest checkout without forcing login.
            AuthMiddleware::optionalAuth();
            $currentUser = AuthMiddleware::getCurrentUser();

            // Parse request data
            $data = $this->getRequestData();
            error_log('OrderController::handlePost received data: ' . json_encode($data));
            
            if (empty($data)) {
                Response::badRequest('Request body is empty');
                return;
            }

            // Validate request
            $validationRules = ['items' => 'required|array'];
            if (!$currentUser) {
                $validationRules['customer_name'] = 'required|string';
                $validationRules['customer_phone'] = 'required|string';
            }

            $errors = $this->validate($data, $validationRules);
            if ($errors) {
                Response::badRequest('Validation failed', $errors);
                return;
            }

            if (empty($data['items']) || !is_array($data['items'])) {
                Response::badRequest('At least one item is required');
                return;
            }

            // Get database connection
            try {
                $db = Database::getInstance();
            } catch (Exception $e) {
                Response::serverError('Database connection failed: ' . $e->getMessage());
                return;
            }

            // Start transaction
            try {
                $db->beginTransaction();
            } catch (Exception $e) {
                Response::serverError('Failed to start transaction: ' . $e->getMessage());
                return;
            }

            $transaction_started = true;

            try {
                $subtotal = 0;
                $processedItems = [];

                // Process items and verify products
                foreach ($data['items'] as $item) {
                    if (!isset($item['product_id'])) {
                        throw new Exception('Missing product_id in item');
                    }

                    $product = $this->productModel->find($item['product_id']);
                    if (!$product) {
                        throw new Exception("Product not found: {$item['product_id']}");
                    }

                    $quantity = (int)($item['quantity'] ?? 0);
                    if ($quantity <= 0) {
                        throw new Exception("Invalid quantity for product {$item['product_id']}");
                    }

                    $price = (float)($product['retail_price'] ?? 0);
                    if ($price <= 0) {
                        throw new Exception("Invalid price for product {$item['product_id']}");
                    }

                    $itemTotal = $price * $quantity;
                    $subtotal += $itemTotal;

                    $processedItems[] = [
                        'product_id' => $product['id'],
                        'product_name' => $product['name'] ?? 'Unknown',
                        'quantity' => $quantity,
                        'unit_type' => $item['unit_type'] ?? 'piece',
                        'unit_price' => $price,
                        'total' => $itemTotal,
                    ];
                }

                if (empty($processedItems)) {
                    throw new Exception('No valid items to process');
                }

                // Calculate totals (use subtotal as total for now)
                $subtotalAmount = $subtotal;  // renamed to avoid confusion with temp variable

                // Create order with only schema-matching fields
                $order = $this->model->create([
                    'order_number' => $this->model->generateNumber(),
                    'customer_id' => $currentUser['id'] ?? null,
                    'customer_name' => $data['customer_name'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? ($data['contact_phone'] ?? null),
                    'subtotal' => $subtotalAmount,
                    'delivery_fee' => (float)($data['delivery_fee'] ?? 0),
                    'total' => $subtotalAmount + ((float)($data['delivery_fee'] ?? 0)),
                    'status' => 'pending',
                    'payment_method' => in_array($data['payment_method'] ?? 'cod', ['cod', 'gcash', 'maya', 'card']) ? $data['payment_method'] : 'cod',
                    'payment_status' => 'pending',
                    'shipping_address' => $data['shipping_address'] ?? ($data['delivery_address'] ?? null),
                    'delivery_address' => $data['delivery_address'] ?? null,
                    'delivery_notes' => $data['delivery_notes'] ?? null,
                ]);

                if (!$order || !isset($order['id'])) {
                    throw new Exception('Failed to create order record');
                }

                // Create order items
                foreach ($processedItems as $item) {
                    // Log item data for debugging
                    error_log('Creating order item: ' . json_encode($item));
                    
                    $itemResult = $this->itemModel->create([
                        'order_id' => $order['id'],
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'unit_type' => $item['unit_type'] ?? 'piece',
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['total'],  // Order items table uses 'subtotal' not 'total'
                    ]);

                    if (!$itemResult) {
                        throw new Exception("Failed to create order item for product {$item['product_id']}");
                    }
                }

                // Commit transaction
                $db->commit();
                $transaction_started = false;

                $order['items'] = $processedItems;
                Response::created($order, 'Order placed successfully');

            } catch (PDOException $e) {
                if ($transaction_started) {
                    try {
                        $db->rollBack();
                    } catch (Exception $rollbackError) {
                        // Ignore rollback errors
                    }
                }
                Response::badRequest('Database error: ' . $e->getMessage());

            } catch (Exception $e) {
                if ($transaction_started) {
                    try {
                        $db->rollBack();
                    } catch (Exception $rollbackError) {
                        // Ignore rollback errors
                    }
                }
                Response::badRequest($e->getMessage());
            }

        } catch (Exception $e) {
            // Catch any unexpected errors
            Response::serverError('Unexpected error: ' . $e->getMessage());
        }
    }

    private function handlePut(?string $id, ?string $action): void
    {
        // For cancel action, use optional auth to allow guests
        if ($action === 'cancel') {
            AuthMiddleware::optionalAuth();
            $payload = AuthMiddleware::getCurrentUser();
        } else {
            $payload = $this->requireAuth();
        }

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
            // Allow guests to cancel if they provide order_number and customer_phone
            $isGuest = empty($payload);
            if ($isGuest) {
                $data = $this->getRequestData();
                $orderNumber = $data['order_number'] ?? $data['orderNumber'] ?? null;
                $customerPhone = $data['customer_phone'] ?? $data['customerPhone'] ?? $data['phone'] ?? null;

                if (!$orderNumber || !$customerPhone) {
                    Response::badRequest('order_number and customer_phone are required for guest cancellation');
                }

                // Verify the order belongs to this guest
                $guestOrder = $this->model->findByOrderNumberAndPhone($orderNumber, $customerPhone);
                if (!$guestOrder || $guestOrder['id'] !== $id) {
                    Response::notFound('Order not found or does not belong to this customer');
                }
            } else {
                // Authenticated user - check permissions
                if ($payload['role'] === 'customer' && $order['customer_id'] !== $payload['id']) {
                    Response::forbidden('Not authorized to cancel this order');
                }
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

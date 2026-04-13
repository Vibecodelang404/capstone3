<?php
/**
 * Transaction Controller - POS sales
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/TransactionItem.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Inventory.php';

class TransactionController extends Controller
{
    private Transaction $model;
    private TransactionItem $itemModel;
    private Product $productModel;
    private Inventory $inventoryModel;

    public function __construct()
    {
        $this->model = new Transaction();
        $this->itemModel = new TransactionItem();
        $this->productModel = new Product();
        $this->inventoryModel = new Inventory();
    }

    public function handleRequest(string $method, ?string $id, ?string $action): void
    {
        match ($method) {
            'GET' => $this->handleGet($id, $action),
            'POST' => $this->handlePost(),
            'PUT' => $this->handlePut($id, $action),
            default => Response::methodNotAllowed($method),
        };
    }

    private function handleGet(?string $id, ?string $action): void
    {
        $this->requireRole(['admin', 'manager', 'cashier']);

        if ($id === 'report') {
            $startDate = $this->getQuery('start_date', date('Y-m-d', strtotime('-30 days')));
            $endDate = $this->getQuery('end_date', date('Y-m-d'));
            $report = $this->model->getReport($startDate, $endDate);
            Response::success($report, 'Sales report retrieved');
            return;
        }

        if ($id === 'daily') {
            $date = $this->getQuery('date', date('Y-m-d'));
            $summary = $this->model->getDailySummary($date);
            Response::success($summary, 'Daily summary retrieved');
            return;
        }

        if ($id) {
            $transaction = $this->model->getWithItems($id);
            if (!$transaction) {
                Response::notFound('Transaction not found');
            }
            Response::success($transaction, 'Transaction retrieved');
            return;
        }

        $page = (int)$this->getQuery('page', 1);
        $perPage = (int)$this->getQuery('per_page', 20);
        $result = $this->model->getPaginated($page, $perPage, [
            'start_date' => $this->getQuery('start_date'),
            'end_date' => $this->getQuery('end_date'),
            'payment_method' => $this->getQuery('payment_method'),
        ]);

        Response::paginated($result['items'], $result['total'], $page, $perPage, 'Transactions retrieved');
    }

    private function handlePost(): void
    {
        $payload = $this->requireRole(['admin', 'manager', 'cashier']);
        $data = $this->getRequestData();

        $errors = $this->validate($data, [
            'items' => 'required|array',
            'payment_method' => 'required|in:cash,card,check,credit',
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
                $price = (float)($item['price'] ?? $product['retail_price']);
                $itemTotal = $price * $quantity;
                $subtotal += $itemTotal;

                $processedItems[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'cost_price' => $product['cost_price'],
                    'total' => $itemTotal,
                ];

                $this->inventoryModel->decrementShelf($product['id'], $quantity);
            }

            $taxRate = (float)($data['tax_rate'] ?? 0);
            $discountAmount = (float)($data['discount_amount'] ?? 0);
            $taxAmount = $subtotal * ($taxRate / 100);
            $total = $subtotal + $taxAmount - $discountAmount;

            $transaction = $this->model->create([
                'transaction_number' => $this->model->generateNumber(),
                'cashier_id' => $payload['id'],
                'customer_id' => $data['customer_id'] ?? null,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'payment_method' => $data['payment_method'],
                'amount_tendered' => (float)($data['amount_tendered'] ?? $total),
                'change_amount' => max(0, (float)($data['amount_tendered'] ?? $total) - $total),
                'status' => 'completed',
            ]);

            foreach ($processedItems as $item) {
                $this->itemModel->create([
                    'transaction_id' => $transaction['id'],
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $item['cost_price'],
                    'total' => $item['total'],
                ]);
            }

            $db->commit();
            $transaction['items'] = $processedItems;
            Response::created($transaction, 'Transaction completed');

        } catch (Exception $e) {
            $db->rollBack();
            Response::badRequest($e->getMessage());
        }
    }

    private function handlePut(?string $id, ?string $action): void
    {
        $this->requireRole(['admin', 'manager']);

        if (!$id) {
            Response::badRequest('Transaction ID required');
        }

        $transaction = $this->model->find($id);
        if (!$transaction) {
            Response::notFound('Transaction not found');
        }

        match ($action) {
            'void' => $this->handleVoid($id, $transaction),
            'refund' => $this->handleRefund($id, $transaction),
            default => Response::badRequest('Invalid action'),
        };
    }

    private function handleVoid(string $id, array $transaction): void
    {
        if ($transaction['status'] !== 'completed') {
            Response::badRequest('Only completed transactions can be voided');
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $items = $this->itemModel->findBy(['transaction_id' => $id]);
            foreach ($items as $item) {
                $this->inventoryModel->incrementShelf($item['product_id'], $item['quantity']);
            }

            $this->model->update($id, ['status' => 'voided']);
            $db->commit();
            Response::success([], 'Transaction voided');
        } catch (Exception $e) {
            $db->rollBack();
            Response::serverError('Failed to void transaction');
        }
    }

    private function handleRefund(string $id, array $transaction): void
    {
        if ($transaction['status'] !== 'completed') {
            Response::badRequest('Only completed transactions can be refunded');
        }

        $data = $this->getRequestData();
        $refundAmount = (float)($data['amount'] ?? $transaction['total']);

        if ($refundAmount > $transaction['total']) {
            Response::badRequest('Refund amount exceeds transaction total');
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            if ($refundAmount >= $transaction['total']) {
                $items = $this->itemModel->findBy(['transaction_id' => $id]);
                foreach ($items as $item) {
                    $this->inventoryModel->incrementShelf($item['product_id'], $item['quantity']);
                }
                $this->model->update($id, ['status' => 'refunded']);
            } else {
                $this->model->update($id, ['status' => 'partial_refund']);
            }

            $db->commit();
            Response::success(['refund_amount' => $refundAmount], 'Transaction refunded');
        } catch (Exception $e) {
            $db->rollBack();
            Response::serverError('Failed to refund transaction');
        }
    }
}

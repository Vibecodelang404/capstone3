<?php
/**
 * User Controller
 * Handles user CRUD operations (admin only)
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/User.php';

class UserController extends Controller
{
    private User $model;

    public function __construct()
    {
        $this->model = new User();
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
        $this->requireRole(['admin', 'manager']);

        if ($id === 'role' && $action) {
            $users = $this->model->findByRole($action);
            Response::success($users, 'Users retrieved by role');
            return;
        }

        if ($id) {
            $user = $this->model->find($id);
            if (!$user) {
                Response::notFound('User not found');
            }
            unset($user['password_hash']);
            Response::success($user, 'User retrieved');
            return;
        }

        $role = $this->getQuery('role');
        $users = $role ? $this->model->findByRole($role) : $this->model->findActive();
        
        $users = array_map(function($user) {
            unset($user['password_hash']);
            return $user;
        }, $users);

        Response::success($users, 'Users retrieved');
    }

    private function handlePost(): void
    {
        $this->requireRole(['admin']);
        $data = $this->getRequestData();

        $errors = $this->validate($data, [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'role' => 'required|in:admin,manager,stockman,cashier,customer',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        if ($this->model->findByEmail($data['email'])) {
            Response::badRequest('Email already exists');
        }

        try {
            $user = $this->model->create([
                'email' => $data['email'],
                'password_hash' => User::hashPassword($data['password']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'],
                'is_active' => 1,
            ]);

            unset($user['password_hash']);
            Response::created($user, 'User created');
        } catch (Exception $e) {
            Response::serverError('Failed to create user: ' . $e->getMessage());
        }
    }

    private function handlePut(?string $id, ?string $action): void
    {
        $this->requireRole(['admin']);

        if (!$id) {
            Response::badRequest('User ID required');
        }

        $this->checkExists($this->model, $id, 'User');

        if ($action === 'toggle') {
            try {
                $user = $this->model->toggleActive($id);
                unset($user['password_hash']);
                Response::success($user, 'User status toggled');
            } catch (Exception $e) {
                Response::serverError('Failed to toggle user status');
            }
            return;
        }

        $data = $this->getRequestData();
        $allowedFields = ['first_name', 'last_name', 'phone', 'role', 'is_active'];
        $updates = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[$field] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $updates['password_hash'] = User::hashPassword($data['password']);
        }

        if (empty($updates)) {
            Response::badRequest('No fields to update');
        }

        try {
            $user = $this->model->update($id, $updates);
            unset($user['password_hash']);
            Response::success($user, 'User updated');
        } catch (Exception $e) {
            Response::serverError('Failed to update user');
        }
    }

    private function handleDelete(?string $id): void
    {
        $payload = $this->requireRole(['admin']);

        if (!$id) {
            Response::badRequest('User ID required');
        }

        if ($id === $payload['id']) {
            Response::badRequest('Cannot delete your own account');
        }

        $this->checkExists($this->model, $id, 'User');

        try {
            $this->model->delete($id);
            Response::success([], 'User deleted');
        } catch (Exception $e) {
            Response::serverError('Failed to delete user');
        }
    }
}

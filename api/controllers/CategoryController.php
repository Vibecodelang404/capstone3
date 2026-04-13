<?php
/**
 * Category Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/Category.php';

class CategoryController extends Controller
{
    private Category $model;

    public function __construct()
    {
        $this->model = new Category();
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
        if ($id) {
            $category = $this->model->getWithProductCount($id);
            if (!$category) {
                Response::notFound('Category not found');
            }
            Response::success($category, 'Category retrieved');
            return;
        }

        $categories = $this->model->findActive();
        Response::success($categories, 'Categories retrieved');
    }

    private function handlePost(): void
    {
        $this->requireRole(['admin', 'manager']);
        $data = $this->getRequestData();

        $errors = $this->validate($data, [
            'name' => 'required|min:2',
            'slug' => 'required|min:2',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        if ($this->model->slugExists($data['slug'])) {
            Response::badRequest('Slug already exists');
        }

        try {
            $category = $this->model->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'display_order' => (int)($data['display_order'] ?? 0),
                'is_active' => 1,
            ]);

            Response::created($category, 'Category created');
        } catch (Exception $e) {
            Response::serverError('Failed to create category');
        }
    }

    private function handlePut(?string $id): void
    {
        $this->requireRole(['admin', 'manager']);

        if (!$id) {
            Response::badRequest('Category ID required');
        }

        $this->checkExists($this->model, $id, 'Category');
        $data = $this->getRequestData();

        if (!empty($data['slug']) && $this->model->slugExists($data['slug'], $id)) {
            Response::badRequest('Slug already exists');
        }

        $allowedFields = ['name', 'slug', 'description', 'icon', 'display_order', 'is_active'];
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
            $category = $this->model->update($id, $updates);
            Response::success($category, 'Category updated');
        } catch (Exception $e) {
            Response::serverError('Failed to update category');
        }
    }

    private function handleDelete(?string $id): void
    {
        $this->requireRole(['admin']);

        if (!$id) {
            Response::badRequest('Category ID required');
        }

        $category = $this->model->getWithProductCount($id);
        if (!$category) {
            Response::notFound('Category not found');
        }

        if ($category['product_count'] > 0) {
            Response::badRequest("Cannot delete category with {$category['product_count']} products");
        }

        try {
            $this->model->delete($id);
            Response::success([], 'Category deleted');
        } catch (Exception $e) {
            Response::serverError('Failed to delete category');
        }
    }
}

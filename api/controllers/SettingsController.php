<?php
/**
 * Settings Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/Settings.php';

class SettingsController extends Controller
{
    private Settings $model;

    public function __construct()
    {
        $this->model = new Settings();
    }

    public function handleRequest(string $method, ?string $id, ?string $action): void
    {
        match ($method) {
            'GET' => $this->handleGet($id),
            'PUT' => $this->handlePut($id),
            default => Response::methodNotAllowed($method),
        };
    }

    private function handleGet(?string $key): void
    {
        $this->requireRole(['admin', 'manager']);

        if ($key) {
            $value = $this->model->get($key);
            if ($value === null) {
                Response::notFound('Setting not found');
            }
            Response::success(['key' => $key, 'value' => $value], 'Setting retrieved');
            return;
        }

        $settings = $this->model->getAll();
        Response::success($settings, 'Settings retrieved');
    }

    private function handlePut(?string $key): void
    {
        $this->requireRole(['admin']);
        $data = $this->getRequestData();

        if ($key) {
            if (!isset($data['value'])) {
                Response::badRequest('Value is required');
            }

            try {
                $this->model->set($key, $data['value']);
                Response::success(['key' => $key, 'value' => $data['value']], 'Setting updated');
            } catch (Exception $e) {
                Response::serverError('Failed to update setting');
            }
            return;
        }

        if (empty($data)) {
            Response::badRequest('Settings data is required');
        }

        try {
            $this->model->setMultiple($data);
            $settings = $this->model->getAll();
            Response::success($settings, 'Settings updated');
        } catch (Exception $e) {
            Response::serverError('Failed to update settings');
        }
    }
}

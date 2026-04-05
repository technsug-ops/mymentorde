<?php

namespace App\Services\Integrations\Adapters\ProjectManagement;

use App\Models\MarketingIntegrationConnection;
use Illuminate\Support\Facades\Http;
use Throwable;

class ClickUpAdapter extends AbstractProjectManagementAdapter
{
    protected function providerCode(): string
    {
        return 'clickup';
    }

    private const BASE = 'https://api.clickup.com/api/v2';

    private function listId(): string
    {
        $conn = MarketingIntegrationConnection::query()
            ->where('provider', $this->providerCode())
            ->where('status', 'connected')
            ->latest('updated_at')
            ->first();

        $meta = is_array($conn?->meta) ? $conn->meta : [];
        return (string) ($meta['list_id'] ?? '');
    }

    public function createTask(array $data): string
    {
        $token  = $this->getToken();
        $listId = $this->listId();

        if (!$token || $listId === '') {
            return parent::createTask($data);
        }

        try {
            $resp = Http::withHeaders(['Authorization' => $token])
                ->timeout(20)
                ->post(self::BASE . "/list/{$listId}/task", [
                    'name'        => $data['name'] ?? 'Task',
                    'description' => $data['description'] ?? '',
                    'priority'    => min(4, max(1, (int) ($data['priority'] ?? 3))),
                    'due_date'    => isset($data['due_date'])
                        ? (strtotime($data['due_date']) * 1000)
                        : null,
                    'status'      => $data['status'] ?? null,
                ]);

            if (!$resp->successful()) {
                return parent::createTask($data);
            }

            $id = (string) ($resp->json('id') ?? '');
            return $id !== '' ? $id : parent::createTask($data);
        } catch (Throwable) {
            return parent::createTask($data);
        }
    }

    public function updateTaskStatus(string $taskId, string $status): bool
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::updateTaskStatus($taskId, $status);
        }

        try {
            $resp = Http::withHeaders(['Authorization' => $token])
                ->timeout(20)
                ->put(self::BASE . "/task/{$taskId}", ['status' => $status]);

            return $resp->successful();
        } catch (Throwable) {
            return parent::updateTaskStatus($taskId, $status);
        }
    }

    public function assignTask(string $taskId, string $userId): bool
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::assignTask($taskId, $userId);
        }

        try {
            $resp = Http::withHeaders(['Authorization' => $token])
                ->timeout(20)
                ->post(self::BASE . "/task/{$taskId}/member", [
                    'id' => (int) $userId,
                ]);

            return $resp->successful();
        } catch (Throwable) {
            return parent::assignTask($taskId, $userId);
        }
    }
}

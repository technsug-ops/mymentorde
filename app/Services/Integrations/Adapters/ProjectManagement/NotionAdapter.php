<?php

namespace App\Services\Integrations\Adapters\ProjectManagement;

use App\Models\MarketingIntegrationConnection;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotionAdapter extends AbstractProjectManagementAdapter
{
    protected function providerCode(): string
    {
        return 'notion';
    }

    private const BASE    = 'https://api.notion.com/v1';
    private const VERSION = '2022-06-28';

    private function databaseId(): string
    {
        $conn = MarketingIntegrationConnection::query()
            ->where('provider', $this->providerCode())
            ->where('status', 'connected')
            ->latest('updated_at')
            ->first();

        $meta = is_array($conn?->meta) ? $conn->meta : [];
        return (string) ($meta['database_id'] ?? '');
    }

    private function notionHeaders(string $token): array
    {
        return [
            'Authorization'  => 'Bearer ' . $token,
            'Notion-Version' => self::VERSION,
            'Content-Type'   => 'application/json',
        ];
    }

    public function createTask(array $data): string
    {
        $token      = $this->getToken();
        $databaseId = $this->databaseId();

        if (!$token || $databaseId === '') {
            return parent::createTask($data);
        }

        try {
            $resp = Http::withHeaders($this->notionHeaders($token))
                ->timeout(20)
                ->post(self::BASE . '/pages', [
                    'parent'     => ['database_id' => $databaseId],
                    'properties' => [
                        'title'  => [
                            'title' => [['text' => ['content' => $data['name'] ?? 'Task']]],
                        ],
                        'Status' => [
                            'select' => ['name' => $data['status'] ?? 'Todo'],
                        ],
                    ],
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
            $resp = Http::withHeaders($this->notionHeaders($token))
                ->timeout(20)
                ->patch(self::BASE . "/pages/{$taskId}", [
                    'properties' => [
                        'Status' => ['select' => ['name' => $status]],
                    ],
                ]);

            return $resp->successful();
        } catch (Throwable) {
            return parent::updateTaskStatus($taskId, $status);
        }
    }

    public function assignTask(string $taskId, string $userId): bool
    {
        // Notion'da user assign için user object gerekir; meta'dan email-to-user mapping yok.
        // getToken() varsa "assigned" kolonu güncellenir, yoksa stub.
        $token = $this->getToken();
        if (!$token) {
            return parent::assignTask($taskId, $userId);
        }

        try {
            $resp = Http::withHeaders($this->notionHeaders($token))
                ->timeout(20)
                ->patch(self::BASE . "/pages/{$taskId}", [
                    'properties' => [
                        'Assignee' => [
                            'people' => [['object' => 'user', 'id' => $userId]],
                        ],
                    ],
                ]);

            return $resp->successful();
        } catch (Throwable) {
            return parent::assignTask($taskId, $userId);
        }
    }
}

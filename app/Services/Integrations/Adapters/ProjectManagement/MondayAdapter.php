<?php

namespace App\Services\Integrations\Adapters\ProjectManagement;

use App\Models\MarketingIntegrationConnection;
use Illuminate\Support\Facades\Http;
use Throwable;

class MondayAdapter extends AbstractProjectManagementAdapter
{
    protected function providerCode(): string
    {
        return 'monday';
    }

    private const BASE = 'https://api.monday.com/v2';

    private function boardId(): int
    {
        $conn = MarketingIntegrationConnection::query()
            ->where('provider', $this->providerCode())
            ->where('status', 'connected')
            ->latest('updated_at')
            ->first();

        $meta = is_array($conn?->meta) ? $conn->meta : [];
        return (int) ($meta['board_id'] ?? 0);
    }

    private function gql(string $token, string $query): array
    {
        $resp = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(20)
            ->post(self::BASE, ['query' => $query]);

        return $resp->json() ?? [];
    }

    public function createTask(array $data): string
    {
        $token   = $this->getToken();
        $boardId = $this->boardId();

        if (!$token || $boardId === 0) {
            return parent::createTask($data);
        }

        try {
            $name    = addslashes($data['name'] ?? 'Task');
            $colVals = json_encode([
                'status' => ['label' => $data['status'] ?? 'Todo'],
            ]);
            $colVals = addslashes($colVals);

            $query  = "mutation { create_item (board_id: {$boardId}, item_name: \"{$name}\", column_values: \"{$colVals}\") { id } }";
            $result = $this->gql($token, $query);
            $id     = (string) ($result['data']['create_item']['id'] ?? '');

            return $id !== '' ? $id : parent::createTask($data);
        } catch (Throwable) {
            return parent::createTask($data);
        }
    }

    public function updateTaskStatus(string $taskId, string $status): bool
    {
        $token   = $this->getToken();
        $boardId = $this->boardId();

        if (!$token || $boardId === 0) {
            return parent::updateTaskStatus($taskId, $status);
        }

        try {
            $label  = addslashes($status);
            $val    = addslashes(json_encode(['label' => $label]));
            $query  = "mutation { change_column_value (board_id: {$boardId}, item_id: {$taskId}, column_id: \"status\", value: \"{$val}\") { id } }";
            $result = $this->gql($token, $query);

            return isset($result['data']['change_column_value']['id']);
        } catch (Throwable) {
            return parent::updateTaskStatus($taskId, $status);
        }
    }

    public function assignTask(string $taskId, string $userId): bool
    {
        $token   = $this->getToken();
        $boardId = $this->boardId();

        if (!$token || $boardId === 0) {
            return parent::assignTask($taskId, $userId);
        }

        try {
            $val   = addslashes(json_encode(['personsAndTeams' => [['id' => (int) $userId, 'kind' => 'person']]]));
            $query = "mutation { change_column_value (board_id: {$boardId}, item_id: {$taskId}, column_id: \"people\", value: \"{$val}\") { id } }";
            $result = $this->gql($token, $query);

            return isset($result['data']['change_column_value']['id']);
        } catch (Throwable) {
            return parent::assignTask($taskId, $userId);
        }
    }
}

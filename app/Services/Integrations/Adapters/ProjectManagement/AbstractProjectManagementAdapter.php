<?php

namespace App\Services\Integrations\Adapters\ProjectManagement;

use App\Models\MarketingIntegrationConnection;
use App\Services\Integrations\Contracts\ProjectManagementInterface;

abstract class AbstractProjectManagementAdapter implements ProjectManagementInterface
{
    abstract protected function providerCode(): string;

    /**
     * MarketingIntegrationConnection tablosundan access_token okur.
     * Token süresi dolmuşsa null döner.
     */
    protected function getToken(?int $companyId = null): ?string
    {
        $conn = MarketingIntegrationConnection::query()
            ->where('provider', $this->providerCode())
            ->where('status', 'connected')
            ->when($companyId !== null, fn ($q) => $q->where('company_id', $companyId))
            ->latest('updated_at')
            ->first();

        if (!$conn) {
            return null;
        }
        if ($conn->token_expires_at && $conn->token_expires_at->isPast()) {
            return null;
        }

        return $conn->access_token ?: null;
    }

    public function createTask(array $data): string
    {
        return $this->providerCode().'-tsk-'.substr(sha1(json_encode($data)), 0, 10);
    }

    public function updateTaskStatus(string $taskId, string $status): bool
    {
        return trim($taskId) !== '' && trim($status) !== '';
    }

    public function assignTask(string $taskId, string $userId): bool
    {
        return trim($taskId) !== '' && trim($userId) !== '';
    }

    public function handleWebhook(array $payload): void
    {
        // MVP: no-op adapter hook
    }
}


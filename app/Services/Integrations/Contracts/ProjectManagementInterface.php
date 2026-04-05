<?php

namespace App\Services\Integrations\Contracts;

interface ProjectManagementInterface
{
    public function createTask(array $data): string;

    public function updateTaskStatus(string $taskId, string $status): bool;

    public function assignTask(string $taskId, string $userId): bool;

    public function handleWebhook(array $payload): void;
}


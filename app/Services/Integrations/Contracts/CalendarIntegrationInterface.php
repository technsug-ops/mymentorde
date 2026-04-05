<?php

namespace App\Services\Integrations\Contracts;

interface CalendarIntegrationInterface
{
    public function createEvent(array $data): string;

    public function cancelEvent(string $eventId): bool;

    public function getAvailability(string $userId, string $date): array;

    public function getSchedulingLink(string $userId): string;

    public function handleWebhook(array $payload): void;
}


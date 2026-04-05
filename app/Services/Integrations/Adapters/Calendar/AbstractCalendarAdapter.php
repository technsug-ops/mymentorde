<?php

namespace App\Services\Integrations\Adapters\Calendar;

use App\Models\MarketingIntegrationConnection;
use App\Services\Integrations\Contracts\CalendarIntegrationInterface;

abstract class AbstractCalendarAdapter implements CalendarIntegrationInterface
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

    public function createEvent(array $data): string
    {
        return $this->providerCode().'-evt-'.substr(sha1(json_encode($data)), 0, 10);
    }

    public function cancelEvent(string $eventId): bool
    {
        return trim($eventId) !== '';
    }

    public function getAvailability(string $userId, string $date): array
    {
        return [
            'provider' => $this->providerCode(),
            'user_id' => $userId,
            'date' => $date,
            'slots' => ['09:00', '11:00', '14:30'],
        ];
    }

    public function getSchedulingLink(string $userId): string
    {
        return 'https://'.$this->providerCode().'.mentorde.local/schedule/'.urlencode($userId);
    }

    public function handleWebhook(array $payload): void
    {
        // MVP: no-op adapter hook
    }
}


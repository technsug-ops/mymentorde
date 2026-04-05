<?php

namespace App\Services\Integrations\Adapters\Video;

use App\Models\MarketingIntegrationConnection;
use App\Services\Integrations\Contracts\VideoConferenceInterface;

abstract class AbstractVideoAdapter implements VideoConferenceInterface
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

    public function createMeeting(array $data): string
    {
        return $this->providerCode().'-meet-'.substr(sha1(json_encode($data)), 0, 10);
    }

    public function cancelMeeting(string $meetingId): bool
    {
        return trim($meetingId) !== '';
    }

    public function getJoinUrl(string $meetingId): string
    {
        return 'https://'.$this->providerCode().'.mentorde.local/meet/'.urlencode($meetingId);
    }

    public function handleWebhook(array $payload): void
    {
        // MVP: no-op adapter hook
    }
}


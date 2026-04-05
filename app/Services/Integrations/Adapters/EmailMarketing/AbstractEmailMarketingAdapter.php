<?php

namespace App\Services\Integrations\Adapters\EmailMarketing;

use App\Models\MarketingIntegrationConnection;
use App\Services\Integrations\Contracts\EmailMarketingInterface;

abstract class AbstractEmailMarketingAdapter implements EmailMarketingInterface
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

    public function createCampaign(array $data): string
    {
        return $this->providerCode().'-cmp-'.substr(sha1(json_encode($data)), 0, 10);
    }

    public function sendCampaign(string $campaignId): bool
    {
        return trim($campaignId) !== '';
    }

    public function getCampaignStats(string $campaignId): array
    {
        return [
            'provider' => $this->providerCode(),
            'campaign_id' => $campaignId,
            'sent' => 0,
            'opened' => 0,
            'clicked' => 0,
        ];
    }

    public function handleWebhook(array $payload): void
    {
        // MVP: no-op adapter hook
    }
}


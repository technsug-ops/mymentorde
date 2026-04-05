<?php

namespace App\Services\Integrations\Adapters\ESign;

use App\Models\MarketingIntegrationConnection;
use App\Services\Integrations\Contracts\ElectronicSignatureInterface;

abstract class AbstractESignAdapter implements ElectronicSignatureInterface
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

    public function createEnvelope(array $data): string
    {
        return $this->providerCode().'-env-'.substr(sha1(json_encode($data)), 0, 10);
    }

    public function sendForSignature(string $envelopeId, array $recipients): bool
    {
        return trim($envelopeId) !== '' && !empty($recipients);
    }

    public function getSigningUrl(string $envelopeId): string
    {
        return 'https://'.$this->providerCode().'.mentorde.local/sign/'.urlencode($envelopeId);
    }

    public function handleWebhook(array $payload): void
    {
        // MVP: no-op adapter hook
    }
}


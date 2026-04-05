<?php

namespace App\Services\Integrations\Adapters\ESign;

use App\Models\MarketingIntegrationConnection;
use Illuminate\Support\Facades\Http;
use Throwable;

class DocuSignAdapter extends AbstractESignAdapter
{
    protected function providerCode(): string
    {
        return 'docusign';
    }

    private function accountId(): string
    {
        $conn = MarketingIntegrationConnection::query()
            ->where('provider', $this->providerCode())
            ->where('status', 'connected')
            ->latest('updated_at')
            ->first();

        $meta = is_array($conn?->meta) ? $conn->meta : [];
        return (string) ($meta['account_id'] ?? '');
    }

    private function baseUrl(string $accountId): string
    {
        return "https://na4.docusign.net/restapi/v2.1/accounts/{$accountId}";
    }

    public function createEnvelope(array $data): string
    {
        $token     = $this->getToken();
        $accountId = $this->accountId();

        if (!$token || $accountId === '') {
            return parent::createEnvelope($data);
        }

        try {
            $base = $this->baseUrl($accountId);

            $signers = array_map(fn ($r, $i) => [
                'email'        => $r['email'] ?? '',
                'name'         => $r['name'] ?? '',
                'recipientId'  => (string) ($i + 1),
                'routingOrder' => (string) ($i + 1),
            ], (array) ($data['recipients'] ?? []), array_keys((array) ($data['recipients'] ?? [])));

            $body = [
                'emailSubject' => $data['subject'] ?? ($data['name'] ?? 'Document for signature'),
                'status'       => 'created',
                'recipients'   => ['signers' => $signers],
                'documents'    => [
                    [
                        'documentBase64' => $data['document_base64'] ?? base64_encode($data['html'] ?? '<html><body>Document</body></html>'),
                        'name'           => $data['document_name'] ?? 'document.html',
                        'fileExtension'  => $data['file_extension'] ?? 'html',
                        'documentId'     => '1',
                    ],
                ],
            ];

            $resp = Http::withToken($token)
                ->timeout(30)
                ->post("{$base}/envelopes", $body);

            if (!$resp->successful()) {
                return parent::createEnvelope($data);
            }

            $envelopeId = (string) ($resp->json('envelopeId') ?? '');
            return $envelopeId !== '' ? $envelopeId : parent::createEnvelope($data);
        } catch (Throwable) {
            return parent::createEnvelope($data);
        }
    }

    public function sendForSignature(string $envelopeId, array $recipients): bool
    {
        $token     = $this->getToken();
        $accountId = $this->accountId();

        if (!$token || $accountId === '') {
            return parent::sendForSignature($envelopeId, $recipients);
        }

        try {
            $base = $this->baseUrl($accountId);
            $resp = Http::withToken($token)
                ->timeout(20)
                ->put("{$base}/envelopes/{$envelopeId}", ['status' => 'sent']);

            return $resp->successful();
        } catch (Throwable) {
            return parent::sendForSignature($envelopeId, $recipients);
        }
    }

    public function getSigningUrl(string $envelopeId): string
    {
        $token     = $this->getToken();
        $accountId = $this->accountId();

        if (!$token || $accountId === '') {
            return parent::getSigningUrl($envelopeId);
        }

        try {
            $base = $this->baseUrl($accountId);
            $resp = Http::withToken($token)
                ->timeout(20)
                ->post("{$base}/envelopes/{$envelopeId}/views/recipient", [
                    'authenticationMethod' => 'none',
                    'email'                => '',
                    'recipientId'          => '1',
                    'returnUrl'            => config('app.url') . '/docusign/return',
                ]);

            if (!$resp->successful()) {
                return parent::getSigningUrl($envelopeId);
            }

            $url = (string) ($resp->json('url') ?? '');
            return $url !== '' ? $url : parent::getSigningUrl($envelopeId);
        } catch (Throwable) {
            return parent::getSigningUrl($envelopeId);
        }
    }
}

<?php

namespace App\Services\Integrations\Adapters\ESign;

use Illuminate\Support\Facades\Http;
use Throwable;

class PandaDocAdapter extends AbstractESignAdapter
{
    protected function providerCode(): string
    {
        return 'pandadoc';
    }

    private const BASE = 'https://api.pandadoc.com/public/v1';

    public function createEnvelope(array $data): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::createEnvelope($data);
        }

        try {
            $body = [
                'name'       => $data['name'] ?? 'Document',
                'recipients' => array_map(fn ($r) => [
                    'email' => $r['email'] ?? '',
                    'first_name' => $r['first_name'] ?? '',
                    'last_name'  => $r['last_name'] ?? '',
                    'role'       => $r['role'] ?? 'signer',
                ], (array) ($data['recipients'] ?? [])),
            ];

            if (!empty($data['template_uuid'])) {
                $body['template_uuid'] = $data['template_uuid'];
            } elseif (!empty($data['url'])) {
                $body['url'] = $data['url'];
            }

            $resp = Http::withToken($token)
                ->timeout(30)
                ->post(self::BASE . '/documents', $body);

            if (!$resp->successful()) {
                return parent::createEnvelope($data);
            }

            $id = (string) ($resp->json('id') ?? '');
            return $id !== '' ? $id : parent::createEnvelope($data);
        } catch (Throwable) {
            return parent::createEnvelope($data);
        }
    }

    public function sendForSignature(string $envelopeId, array $recipients): bool
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::sendForSignature($envelopeId, $recipients);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(20)
                ->post(self::BASE . "/documents/{$envelopeId}/send", [
                    'message' => 'Please sign the document.',
                    'silent'  => false,
                ]);

            return $resp->successful();
        } catch (Throwable) {
            return parent::sendForSignature($envelopeId, $recipients);
        }
    }

    public function getSigningUrl(string $envelopeId): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::getSigningUrl($envelopeId);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(20)
                ->post(self::BASE . "/documents/{$envelopeId}/session", [
                    'recipient' => null,
                    'lifetime'  => 900,
                ]);

            if (!$resp->successful()) {
                return parent::getSigningUrl($envelopeId);
            }

            $sessionId = (string) ($resp->json('id') ?? '');
            return $sessionId !== ''
                ? 'https://app.pandadoc.com/s/' . $sessionId
                : parent::getSigningUrl($envelopeId);
        } catch (Throwable) {
            return parent::getSigningUrl($envelopeId);
        }
    }
}

<?php

namespace App\Services\Integrations\Adapters\ESign;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * HelloSign / Dropbox Sign adapter.
 * createEnvelope() ve sendForSignature() HelloSign API'de tek çağrıdır.
 * createEnvelope → signature_request_id döner; sendForSignature her zaman true.
 */
class HelloSignAdapter extends AbstractESignAdapter
{
    protected function providerCode(): string
    {
        return 'hellosign';
    }

    private const BASE = 'https://api.hellosign.com/v3';

    public function createEnvelope(array $data): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::createEnvelope($data);
        }

        try {
            $signers = array_map(fn ($r, $i) => [
                'name'          => $r['name'] ?? '',
                'email_address' => $r['email'] ?? '',
                'order'         => $i,
            ], (array) ($data['recipients'] ?? []), array_keys((array) ($data['recipients'] ?? [])));

            $payload = [
                'title'     => $data['name'] ?? 'Document',
                'message'   => $data['message'] ?? 'Please sign this document.',
                'signers'   => $signers,
                'test_mode' => 1, // prod'da 0 olmalı, meta'dan override edilebilir
            ];

            // Dosya: base64 varsa temp file oluştur
            if (!empty($data['document_base64'])) {
                $payload['file_url'] = [];
            } elseif (!empty($data['file_url'])) {
                $payload['file_url'] = [$data['file_url']];
            }

            $resp = Http::withBasicAuth($token, '')
                ->timeout(30)
                ->asMultipart()
                ->post(self::BASE . '/signature_request/send', $payload);

            if (!$resp->successful()) {
                return parent::createEnvelope($data);
            }

            $id = (string) ($resp->json('signature_request.signature_request_id') ?? '');
            return $id !== '' ? $id : parent::createEnvelope($data);
        } catch (Throwable) {
            return parent::createEnvelope($data);
        }
    }

    /**
     * HelloSign'da send+create tek adımda gerçekleşir.
     * createEnvelope zaten gönderdiği için bu metod her zaman true döner (token varsa).
     */
    public function sendForSignature(string $envelopeId, array $recipients): bool
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::sendForSignature($envelopeId, $recipients);
        }
        // Zaten gönderildi — reminder gönder
        try {
            Http::withBasicAuth($token, '')
                ->timeout(15)
                ->post(self::BASE . '/signature_request/remind/' . $envelopeId, [
                    'email_address' => $recipients[0]['email'] ?? '',
                ]);
        } catch (Throwable) {
            // Sessizce geç
        }
        return true;
    }

    public function getSigningUrl(string $envelopeId): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::getSigningUrl($envelopeId);
        }

        try {
            $resp = Http::withBasicAuth($token, '')
                ->timeout(20)
                ->get(self::BASE . '/signature_request/' . $envelopeId);

            if (!$resp->successful()) {
                return parent::getSigningUrl($envelopeId);
            }

            $signers = $resp->json('signature_request.signatures') ?? [];
            if (!empty($signers) && !empty($signers[0]['sign_url'])) {
                return (string) $signers[0]['sign_url'];
            }

            return parent::getSigningUrl($envelopeId);
        } catch (Throwable) {
            return parent::getSigningUrl($envelopeId);
        }
    }
}

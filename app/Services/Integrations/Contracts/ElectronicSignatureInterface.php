<?php

namespace App\Services\Integrations\Contracts;

interface ElectronicSignatureInterface
{
    public function createEnvelope(array $data): string;

    public function sendForSignature(string $envelopeId, array $recipients): bool;

    public function getSigningUrl(string $envelopeId): string;

    public function handleWebhook(array $payload): void;
}


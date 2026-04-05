<?php

namespace App\Services\Integrations\Contracts;

interface EmailMarketingInterface
{
    public function createCampaign(array $data): string;

    public function sendCampaign(string $campaignId): bool;

    public function getCampaignStats(string $campaignId): array;

    public function handleWebhook(array $payload): void;
}


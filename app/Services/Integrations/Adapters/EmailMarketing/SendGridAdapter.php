<?php

namespace App\Services\Integrations\Adapters\EmailMarketing;

use Illuminate\Support\Facades\Http;
use Throwable;

class SendGridAdapter extends AbstractEmailMarketingAdapter
{
    protected function providerCode(): string
    {
        return 'sendgrid';
    }

    private const BASE = 'https://api.sendgrid.com';

    public function createCampaign(array $data): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::createCampaign($data);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(20)
                ->post(self::BASE . '/v3/marketing/singlesends', [
                    'name'    => $data['name'] ?? 'Campaign',
                    'send_to' => [
                        'list_ids' => is_array($data['list_ids'] ?? null) ? $data['list_ids'] : [],
                    ],
                    'email_config' => [
                        'subject'          => $data['subject'] ?? ($data['name'] ?? 'Campaign'),
                        'html_content'     => $data['html'] ?? '',
                        'plain_content'    => $data['plain'] ?? '',
                        'sender_id'        => (int) ($data['sender_id'] ?? 0),
                        'suppression_group_id' => (int) ($data['suppression_group_id'] ?? 0),
                    ],
                ]);

            if (!$resp->successful()) {
                return parent::createCampaign($data);
            }

            $id = (string) ($resp->json('id') ?? '');
            return $id !== '' ? $id : parent::createCampaign($data);
        } catch (Throwable) {
            return parent::createCampaign($data);
        }
    }

    public function sendCampaign(string $campaignId): bool
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::sendCampaign($campaignId);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(20)
                ->put(self::BASE . "/v3/marketing/singlesends/{$campaignId}/schedule", [
                    'send_at' => 'now',
                ]);

            return $resp->successful();
        } catch (Throwable) {
            return parent::sendCampaign($campaignId);
        }
    }

    public function getCampaignStats(string $campaignId): array
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::getCampaignStats($campaignId);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(20)
                ->post(self::BASE . '/v3/marketing/stats/singlesends', [
                    'ids' => [$campaignId],
                ]);

            if (!$resp->successful()) {
                return parent::getCampaignStats($campaignId);
            }

            $results = $resp->json('results') ?? [];
            $stats   = is_array($results) && !empty($results) ? ($results[0]['stats']['full'] ?? []) : [];

            return [
                'provider'    => $this->providerCode(),
                'campaign_id' => $campaignId,
                'sent'        => (int) ($stats['requests'] ?? 0),
                'opened'      => (int) ($stats['unique_opens'] ?? 0),
                'clicked'     => (int) ($stats['unique_clicks'] ?? 0),
                'open_rate'   => round((float) ($stats['open_rate'] ?? 0) * 100, 2),
                'click_rate'  => round((float) ($stats['click_rate'] ?? 0) * 100, 2),
                'unsubscribed' => (int) ($stats['unsubscribes'] ?? 0),
                'bounced'     => (int) ($stats['bounces'] ?? 0),
            ];
        } catch (Throwable) {
            return parent::getCampaignStats($campaignId);
        }
    }
}

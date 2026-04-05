<?php

namespace App\Services\Integrations\Adapters\EmailMarketing;

use Illuminate\Support\Facades\Http;
use Throwable;

class ZohoAdapter extends AbstractEmailMarketingAdapter
{
    protected function providerCode(): string
    {
        return 'zoho';
    }

    private const BASE = 'https://campaigns.zoho.com/api/v1.1';

    public function getCampaignStats(string $campaignId): array
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::getCampaignStats($campaignId);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(20)
                ->get(self::BASE . '/campaign/stats', [
                    'resfmt'      => 'JSON',
                    'campaignkey' => $campaignId,
                ]);

            if (!$resp->successful()) {
                return parent::getCampaignStats($campaignId);
            }

            $data = $resp->json() ?? [];
            return [
                'provider'    => $this->providerCode(),
                'campaign_id' => $campaignId,
                'sent'        => (int) ($data['noofrecipients'] ?? 0),
                'opened'      => (int) ($data['uniqueopens'] ?? 0),
                'clicked'     => (int) ($data['uniqueclicks'] ?? 0),
                'open_rate'   => round((float) ($data['openrate'] ?? 0), 2),
                'click_rate'  => round((float) ($data['clickrate'] ?? 0), 2),
                'unsubscribed' => (int) ($data['unsubscribes'] ?? 0),
                'bounced'     => (int) ($data['hardbounces'] ?? 0) + (int) ($data['softbounces'] ?? 0),
            ];
        } catch (Throwable) {
            return parent::getCampaignStats($campaignId);
        }
    }
}

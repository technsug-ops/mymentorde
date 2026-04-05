<?php

namespace App\Services\Integrations\Adapters\EmailMarketing;

use Illuminate\Support\Facades\Http;
use Throwable;

class MailchimpAdapter extends AbstractEmailMarketingAdapter
{
    protected function providerCode(): string
    {
        return 'mailchimp';
    }

    /** API key'den datacenter prefix'ini çıkar (us1, us6 vb.) */
    private function datacenter(string $apiKey): string
    {
        $parts = explode('-', $apiKey);
        return count($parts) >= 2 ? end($parts) : 'us1';
    }

    private function baseUrl(string $apiKey): string
    {
        return 'https://' . $this->datacenter($apiKey) . '.api.mailchimp.com/3.0';
    }

    public function createCampaign(array $data): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::createCampaign($data);
        }

        try {
            $base = $this->baseUrl($token);

            // Kampanya oluştur
            $resp = Http::withBasicAuth('anystring', $token)
                ->timeout(20)
                ->post("{$base}/campaigns", [
                    'type'       => $data['type'] ?? 'regular',
                    'settings'   => [
                        'subject_line' => $data['subject'] ?? ($data['name'] ?? 'Campaign'),
                        'from_name'    => $data['from_name'] ?? '',
                        'reply_to'     => $data['reply_to'] ?? '',
                        'title'        => $data['name'] ?? 'Campaign',
                        'list_id'      => $data['list_id'] ?? '',
                    ],
                ]);

            if (!$resp->successful()) {
                return parent::createCampaign($data);
            }

            $campaignId = (string) ($resp->json('id') ?? '');

            // İçerik varsa ayarla
            if (!empty($data['html']) && $campaignId !== '') {
                Http::withBasicAuth('anystring', $token)
                    ->timeout(20)
                    ->put("{$base}/campaigns/{$campaignId}/content", [
                        'html' => $data['html'],
                    ]);
            }

            return $campaignId !== '' ? $campaignId : parent::createCampaign($data);
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
            $base = $this->baseUrl($token);
            $resp = Http::withBasicAuth('anystring', $token)
                ->timeout(20)
                ->post("{$base}/campaigns/{$campaignId}/actions/send");

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
            $base = $this->baseUrl($token);
            $resp = Http::withBasicAuth('anystring', $token)
                ->timeout(20)
                ->get("{$base}/reports/{$campaignId}");

            if (!$resp->successful()) {
                return parent::getCampaignStats($campaignId);
            }

            $data = $resp->json();
            return [
                'provider'   => $this->providerCode(),
                'campaign_id' => $campaignId,
                'sent'       => (int) ($data['emails_sent'] ?? 0),
                'opened'     => (int) ($data['opens']['unique_opens'] ?? 0),
                'clicked'    => (int) ($data['clicks']['unique_subscriber_clicks'] ?? 0),
                'open_rate'  => round((float) ($data['opens']['open_rate'] ?? 0) * 100, 2),
                'click_rate' => round((float) ($data['clicks']['click_rate'] ?? 0) * 100, 2),
                'unsubscribed' => (int) ($data['unsubscribed'] ?? 0),
                'bounced'    => (int) ($data['bounces']['hard_bounces'] ?? 0) + (int) ($data['bounces']['soft_bounces'] ?? 0),
            ];
        } catch (Throwable) {
            return parent::getCampaignStats($campaignId);
        }
    }
}

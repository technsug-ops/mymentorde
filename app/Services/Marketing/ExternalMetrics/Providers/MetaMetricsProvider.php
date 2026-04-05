<?php

namespace App\Services\Marketing\ExternalMetrics\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class MetaMetricsProvider extends AbstractMetricsProvider
{
    public function fetch(Carbon $start, Carbon $end, int $companyId, array $cfg): array
    {
        $token     = trim((string) ($cfg['access_token'] ?? ''));
        $accountId = trim((string) ($cfg['ad_account_id'] ?? ''));
        $version   = trim((string) ($cfg['api_version'] ?? 'v21.0'));

        if ($token === '' || $accountId === '') {
            throw new InvalidArgumentException('meta config missing: access_token or ad_account_id');
        }

        $cleanAccountId = str_starts_with($accountId, 'act_') ? substr($accountId, 4) : $accountId;
        $endpoint = sprintf('https://graph.facebook.com/%s/act_%s/insights', $version, $cleanAccountId);

        $response = Http::timeout(45)->get($endpoint, [
            'access_token'    => $token,
            'level'           => 'campaign',
            'time_increment'  => 1,
            'time_range'      => json_encode([
                'since' => $start->toDateString(),
                'until' => $end->toDateString(),
            ]),
            'fields'          => 'campaign_id,campaign_name,date_start,impressions,clicks,spend,actions',
            'limit'           => 5000,
        ])->throw()->json();

        $data = Arr::get($response, 'data', []);
        if (! is_array($data)) {
            return [];
        }

        $rows = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                continue;
            }

            $actions     = is_array($item['actions'] ?? null) ? $item['actions'] : [];
            $leads       = $this->extractMetaActionCount($actions, [
                'lead', 'onsite_conversion.lead_grouped', 'offsite_conversion.fb_pixel_lead',
            ]);
            $conversions = $this->extractMetaActionCount($actions, [
                'lead', 'onsite_conversion.lead_grouped', 'offsite_conversion.fb_pixel_lead',
                'purchase', 'offsite_conversion.fb_pixel_purchase', 'complete_registration',
            ]);

            $campaignId   = trim((string) ($item['campaign_id'] ?? ''));
            $campaignName = trim((string) ($item['campaign_name'] ?? ''));

            $rows[] = $this->normalizeRow([
                'company_id'    => $companyId,
                'provider'      => 'meta',
                'account_ref'   => 'act_' . $cleanAccountId,
                'metric_date'   => (string) ($item['date_start'] ?? $start->toDateString()),
                'campaign_key'  => $campaignId !== '' ? $campaignId : $campaignName,
                'campaign_name' => $campaignName !== '' ? $campaignName : null,
                'source'        => 'meta',
                'medium'        => 'paid_social',
                'impressions'   => $this->toInt($item['impressions'] ?? 0),
                'clicks'        => $this->toInt($item['clicks'] ?? 0),
                'spend'         => $this->toFloat($item['spend'] ?? 0),
                'leads'         => $leads,
                'conversions'   => $conversions,
                'raw_payload'   => $item,
            ]);
        }

        return $rows;
    }
}

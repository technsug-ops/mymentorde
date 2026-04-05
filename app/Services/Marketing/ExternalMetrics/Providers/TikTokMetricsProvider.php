<?php

namespace App\Services\Marketing\ExternalMetrics\Providers;

use App\Models\MarketingIntegrationConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class TikTokMetricsProvider extends AbstractMetricsProvider
{
    public function fetch(Carbon $start, Carbon $end, int $companyId, array $cfg): array
    {
        $conn = MarketingIntegrationConnection::query()
            ->where('company_id', $companyId)
            ->where('provider', 'tiktok_ads')
            ->where('status', 'connected')
            ->first();
        $token        = $conn?->access_token ?? trim((string) ($cfg['access_token'] ?? ''));
        $advertiserId = trim((string) ($cfg['advertiser_id'] ?? ''));

        if ($token === '' || $advertiserId === '') {
            throw new InvalidArgumentException('tiktok_ads config missing: access_token or advertiser_id');
        }

        $response = Http::timeout(45)
            ->withHeaders(['Access-Token' => $token])
            ->post('https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/', [
                'advertiser_id'   => $advertiserId,
                'report_type'     => 'BASIC',
                'data_level'      => 'AUCTION_CAMPAIGN',
                'dimensions'      => ['campaign_id', 'stat_time_day'],
                'metrics'         => ['campaign_name', 'spend', 'impressions', 'clicks', 'conversions', 'cpc', 'ctr'],
                'start_date'      => $start->toDateString(),
                'end_date'        => $end->toDateString(),
                'date_range_type' => 'CUSTOM_DATE',
                'page_size'       => 1000,
            ])
            ->throw()
            ->json();

        $data = Arr::get($response, 'data.list', []);
        if (! is_array($data)) {
            return [];
        }

        $rows = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                continue;
            }

            $dims         = is_array($item['dimensions'] ?? null) ? $item['dimensions'] : [];
            $metrics      = is_array($item['metrics'] ?? null) ? $item['metrics'] : [];
            $campaignId   = trim((string) ($dims['campaign_id'] ?? ''));
            $metricDate   = trim((string) ($dims['stat_time_day'] ?? $start->toDateString()));
            $campaignName = trim((string) ($metrics['campaign_name'] ?? ''));

            $rows[] = $this->normalizeRow([
                'company_id'    => $companyId,
                'provider'      => 'tiktok_ads',
                'account_ref'   => $advertiserId,
                'metric_date'   => substr($metricDate, 0, 10),
                'campaign_key'  => $campaignId !== '' ? $campaignId : $campaignName,
                'campaign_name' => $campaignName !== '' ? $campaignName : null,
                'source'        => 'tiktok',
                'medium'        => 'paid_social',
                'impressions'   => $this->toInt($metrics['impressions'] ?? 0),
                'clicks'        => $this->toInt($metrics['clicks'] ?? 0),
                'spend'         => $this->toFloat($metrics['spend'] ?? 0),
                'leads'         => $this->toInt($metrics['clicks'] ?? 0),
                'conversions'   => $this->toInt($metrics['conversions'] ?? 0),
                'raw_payload'   => $item,
            ]);
        }

        return $rows;
    }
}

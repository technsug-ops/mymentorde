<?php

namespace App\Services\Marketing\ExternalMetrics\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class GoogleAdsMetricsProvider extends AbstractMetricsProvider
{
    public function fetch(Carbon $start, Carbon $end, int $companyId, array $cfg): array
    {
        $customerId      = trim((string) ($cfg['customer_id'] ?? ''));
        $developerToken  = trim((string) ($cfg['developer_token'] ?? ''));
        $accessToken     = trim((string) ($cfg['access_token'] ?? ''));
        $loginCustomerId = trim((string) ($cfg['login_customer_id'] ?? ''));

        if ($customerId === '' || $developerToken === '' || $accessToken === '') {
            throw new InvalidArgumentException('google_ads config missing: customer_id, developer_token or access_token');
        }

        $query = sprintf(
            "SELECT segments.date, campaign.id, campaign.name, metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.conversions FROM campaign WHERE segments.date BETWEEN '%s' AND '%s'",
            $start->toDateString(),
            $end->toDateString()
        );

        $headers = [
            'developer-token' => $developerToken,
            'Content-Type'    => 'application/json',
        ];
        if ($loginCustomerId !== '') {
            $headers['login-customer-id'] = $loginCustomerId;
        }

        $endpoint = sprintf(
            'https://googleads.googleapis.com/v18/customers/%s/googleAds:searchStream',
            $customerId
        );

        $response = Http::timeout(45)
            ->withToken($accessToken)
            ->withHeaders($headers)
            ->post($endpoint, ['query' => $query])
            ->throw()
            ->json();

        $chunks = is_array($response) ? $response : [];
        $rows   = [];

        foreach ($chunks as $chunk) {
            if (! is_array($chunk)) {
                continue;
            }
            $resultRows = is_array($chunk['results'] ?? null) ? $chunk['results'] : [];
            foreach ($resultRows as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $date         = trim((string) data_get($item, 'segments.date', $start->toDateString()));
                $campaignId   = trim((string) data_get($item, 'campaign.id', ''));
                $campaignName = trim((string) data_get($item, 'campaign.name', ''));
                $impressions  = $this->toInt(data_get($item, 'metrics.impressions', 0));
                $clicks       = $this->toInt(data_get($item, 'metrics.clicks', 0));
                $costMicros   = $this->toFloat(data_get($item, 'metrics.costMicros', 0));
                $spend        = $costMicros / 1000000;
                $conversions  = (int) round($this->toFloat(data_get($item, 'metrics.conversions', 0)));

                $rows[] = $this->normalizeRow([
                    'company_id'    => $companyId,
                    'provider'      => 'google_ads',
                    'account_ref'   => $customerId,
                    'metric_date'   => $date,
                    'campaign_key'  => $campaignId !== '' ? $campaignId : $campaignName,
                    'campaign_name' => $campaignName !== '' ? $campaignName : null,
                    'source'        => 'google',
                    'medium'        => 'cpc',
                    'impressions'   => $impressions,
                    'clicks'        => $clicks,
                    'spend'         => $spend,
                    'leads'         => $clicks,
                    'conversions'   => max(0, $conversions),
                    'raw_payload'   => $item,
                ]);
            }
        }

        return $rows;
    }
}

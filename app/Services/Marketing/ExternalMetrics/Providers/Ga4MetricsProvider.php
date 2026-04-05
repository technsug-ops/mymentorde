<?php

namespace App\Services\Marketing\ExternalMetrics\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class Ga4MetricsProvider extends AbstractMetricsProvider
{
    public function fetch(Carbon $start, Carbon $end, int $companyId, array $cfg): array
    {
        $propertyId      = trim((string) ($cfg['property_id'] ?? ''));
        $credentialsPath = trim((string) ($cfg['credentials'] ?? ''));
        $accessToken     = trim((string) ($cfg['access_token'] ?? ''));

        if ($propertyId === '') {
            throw new InvalidArgumentException('ga4 config missing: property_id');
        }

        $token = $accessToken;
        if ($token === '') {
            if ($credentialsPath === '') {
                throw new InvalidArgumentException('ga4 config missing: access_token or credentials');
            }
            if (! file_exists($credentialsPath)) {
                throw new InvalidArgumentException('ga4 credentials file not found');
            }
            $token = $this->googleAccessToken(
                $credentialsPath,
                ['https://www.googleapis.com/auth/analytics.readonly']
            );
        }

        $endpoint = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport";
        $response = Http::timeout(45)
            ->withToken($token)
            ->post($endpoint, [
                'dateRanges' => [['startDate' => $start->toDateString(), 'endDate' => $end->toDateString()]],
                'dimensions' => [
                    ['name' => 'date'],
                    ['name' => 'sessionCampaignName'],
                    ['name' => 'sessionSource'],
                    ['name' => 'sessionMedium'],
                ],
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'conversions'],
                ],
                'limit' => 100000,
            ])
            ->throw()
            ->json();

        $dataRows = Arr::get($response, 'rows', []);
        if (! is_array($dataRows)) {
            return [];
        }

        $rows = [];
        foreach ($dataRows as $item) {
            if (! is_array($item)) {
                continue;
            }

            $dimensions   = is_array($item['dimensionValues'] ?? null) ? $item['dimensionValues'] : [];
            $metrics      = is_array($item['metricValues'] ?? null) ? $item['metricValues'] : [];
            $rawDate      = trim((string) data_get($dimensions, '0.value', $start->format('Ymd')));
            $date         = $this->normalizeGa4Date($rawDate);
            $campaignName = trim((string) data_get($dimensions, '1.value', '(not set)'));
            $source       = trim((string) data_get($dimensions, '2.value', '(not set)'));
            $medium       = trim((string) data_get($dimensions, '3.value', '(not set)'));
            $sessions     = $this->toInt(data_get($metrics, '0.value', 0));
            $conversions  = (int) round($this->toFloat(data_get($metrics, '1.value', 0)));

            $rows[] = $this->normalizeRow([
                'company_id'    => $companyId,
                'provider'      => 'ga4',
                'account_ref'   => $propertyId,
                'metric_date'   => $date,
                'campaign_key'  => $campaignName !== '' ? $campaignName : '(not set)',
                'campaign_name' => $campaignName,
                'source'        => $source,
                'medium'        => $medium,
                'impressions'   => 0,
                'clicks'        => 0,
                'spend'         => 0,
                'leads'         => $sessions,
                'conversions'   => max(0, $conversions),
                'raw_payload'   => $item,
            ]);
        }

        return $rows;
    }
}

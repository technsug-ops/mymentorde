<?php

namespace App\Services\Marketing\ExternalMetrics\Providers;

use App\Models\MarketingIntegrationConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class LinkedInMetricsProvider extends AbstractMetricsProvider
{
    public function fetch(Carbon $start, Carbon $end, int $companyId, array $cfg): array
    {
        $conn = MarketingIntegrationConnection::query()
            ->where('company_id', $companyId)
            ->where('provider', 'linkedin_ads')
            ->where('status', 'connected')
            ->first();
        $token     = $conn?->access_token ?? trim((string) ($cfg['access_token'] ?? ''));
        $accountId = trim((string) ($cfg['ad_account_id'] ?? ''));

        if ($token === '' || $accountId === '') {
            throw new InvalidArgumentException('linkedin_ads config missing: access_token or ad_account_id');
        }

        $campaignsResp = Http::timeout(30)
            ->withToken($token)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->get('https://api.linkedin.com/v2/adCampaignsV2', [
                'q'                         => 'search',
                'search.account.values[0]'  => 'urn:li:sponsoredAccount:' . $accountId,
                'count'                     => 500,
            ])
            ->throw()
            ->json();

        $campaignMap = [];
        foreach (Arr::get($campaignsResp, 'elements', []) as $c) {
            $urn  = (string) ($c['id'] ?? '');
            $name = (string) ($c['name'] ?? '');
            if ($urn !== '') {
                $campaignMap[$urn] = $name;
            }
        }

        $analyticsResp = Http::timeout(45)
            ->withToken($token)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->get('https://api.linkedin.com/v2/adAnalyticsV2', [
                'q'                     => 'analytics',
                'pivot'                 => 'CAMPAIGN',
                'dateRange.start.day'   => (int) $start->format('d'),
                'dateRange.start.month' => (int) $start->format('m'),
                'dateRange.start.year'  => (int) $start->format('Y'),
                'dateRange.end.day'     => (int) $end->format('d'),
                'dateRange.end.month'   => (int) $end->format('m'),
                'dateRange.end.year'    => (int) $end->format('Y'),
                'timeGranularity'       => 'DAILY',
                'fields'                => 'pivotValues,dateRange,costInLocalCurrency,impressions,clicks,leadGenerationMailContactInfoShares,externalWebsiteConversions',
                'accounts[0]'           => 'urn:li:sponsoredAccount:' . $accountId,
                'count'                 => 5000,
            ])
            ->throw()
            ->json();

        $elements = Arr::get($analyticsResp, 'elements', []);
        if (! is_array($elements)) {
            return [];
        }

        $rows = [];
        foreach ($elements as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pivotValues  = is_array($item['pivotValues'] ?? null) ? $item['pivotValues'] : [];
            $campaignUrn  = trim((string) ($pivotValues[0] ?? ''));
            $campaignName = $campaignMap[$campaignUrn] ?? $campaignUrn;
            $dateRange    = is_array($item['dateRange'] ?? null) ? $item['dateRange'] : [];
            $startDay     = $dateRange['start'] ?? [];
            $metricDate   = isset($startDay['year'], $startDay['month'], $startDay['day'])
                ? sprintf('%04d-%02d-%02d', $startDay['year'], $startDay['month'], $startDay['day'])
                : $start->toDateString();

            $rows[] = $this->normalizeRow([
                'company_id'    => $companyId,
                'provider'      => 'linkedin_ads',
                'account_ref'   => $accountId,
                'metric_date'   => $metricDate,
                'campaign_key'  => $campaignUrn !== '' ? $campaignUrn : $campaignName,
                'campaign_name' => $campaignName !== '' ? $campaignName : null,
                'source'        => 'linkedin',
                'medium'        => 'paid_social',
                'impressions'   => $this->toInt($item['impressions'] ?? 0),
                'clicks'        => $this->toInt($item['clicks'] ?? 0),
                'spend'         => $this->toFloat($item['costInLocalCurrency'] ?? 0),
                'leads'         => $this->toInt($item['leadGenerationMailContactInfoShares'] ?? 0),
                'conversions'   => $this->toInt($item['externalWebsiteConversions'] ?? 0),
                'raw_payload'   => $item,
            ]);
        }

        return $rows;
    }
}

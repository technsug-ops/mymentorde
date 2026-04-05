<?php

namespace App\Services\Marketing\ExternalMetrics\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class InstagramMetricsProvider extends AbstractMetricsProvider
{
    public function fetch(Carbon $start, Carbon $end, int $companyId, array $cfg): array
    {
        $igUserId = trim((string) ($cfg['ig_user_id'] ?? ''));
        // Instagram için önce kendi token, yoksa Meta token fallback (cfg['meta_fallback_token'])
        $token = trim((string) ($cfg['access_token'] ?? ''));
        if ($token === '') {
            $token = trim((string) ($cfg['meta_fallback_token'] ?? ''));
        }

        if ($token === '' || $igUserId === '') {
            throw new InvalidArgumentException('instagram config missing: ig_user_id or access_token');
        }

        $version = 'v21.0';

        $insightsResp = Http::timeout(30)
            ->get("https://graph.facebook.com/{$version}/{$igUserId}/insights", [
                'metric'       => 'reach,impressions,profile_views,website_clicks',
                'period'       => 'day',
                'since'        => $start->timestamp,
                'until'        => $end->copy()->addDay()->timestamp,
                'access_token' => $token,
            ])
            ->throw()
            ->json();

        $byDate = [];
        foreach (Arr::get($insightsResp, 'data', []) as $metric) {
            $metricName = (string) ($metric['name'] ?? '');
            foreach ((array) ($metric['values'] ?? []) as $point) {
                $dateKey = substr((string) ($point['end_time'] ?? $start->toDateString()), 0, 10);
                $byDate[$dateKey][$metricName] = $this->toInt($point['value'] ?? 0);
            }
        }

        $mediaResp = Http::timeout(30)
            ->get("https://graph.facebook.com/{$version}/{$igUserId}/media", [
                'fields'       => 'id,like_count,comments_count,timestamp',
                'since'        => $start->toDateString(),
                'until'        => $end->toDateString(),
                'limit'        => 100,
                'access_token' => $token,
            ])
            ->throw()
            ->json();

        $totalLikes    = 0;
        $totalComments = 0;
        foreach (Arr::get($mediaResp, 'data', []) as $media) {
            $totalLikes    += $this->toInt($media['like_count'] ?? 0);
            $totalComments += $this->toInt($media['comments_count'] ?? 0);
        }

        $rows = [];
        foreach ($byDate as $dateKey => $metrics) {
            $rows[] = $this->normalizeRow([
                'company_id'    => $companyId,
                'provider'      => 'instagram',
                'account_ref'   => $igUserId,
                'metric_date'   => $dateKey,
                'campaign_key'  => 'organic',
                'campaign_name' => 'Instagram Organic',
                'source'        => 'instagram',
                'medium'        => 'social',
                'impressions'   => $this->toInt($metrics['impressions'] ?? 0),
                'clicks'        => $this->toInt($metrics['website_clicks'] ?? 0),
                'spend'         => 0,
                'leads'         => $this->toInt($metrics['profile_views'] ?? 0),
                'conversions'   => 0,
                'raw_payload'   => ['date' => $dateKey, 'metrics' => $metrics, 'media_likes' => $totalLikes, 'media_comments' => $totalComments],
            ]);
        }

        return $rows;
    }
}

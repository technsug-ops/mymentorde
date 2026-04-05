<?php

namespace App\Services\Marketing\ExternalMetrics;

use App\Models\Company;
use App\Models\MarketingAdminSetting;
use App\Models\MarketingExternalMetric;
use App\Models\MarketingIntegrationConnection;
use App\Services\Marketing\ExternalMetrics\Contracts\ExternalMetricsProviderInterface;
use App\Services\Marketing\ExternalMetrics\Providers\Ga4MetricsProvider;
use App\Services\Marketing\ExternalMetrics\Providers\GoogleAdsMetricsProvider;
use App\Services\Marketing\ExternalMetrics\Providers\InstagramMetricsProvider;
use App\Services\Marketing\ExternalMetrics\Providers\LinkedInMetricsProvider;
use App\Services\Marketing\ExternalMetrics\Providers\MetaMetricsProvider;
use App\Services\Marketing\ExternalMetrics\Providers\TikTokMetricsProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class ExternalMetricsSyncService
{
    /** @var array<string, ExternalMetricsProviderInterface> */
    private array $providers;

    public function __construct()
    {
        $this->providers = [
            'meta'         => new MetaMetricsProvider(),
            'ga4'          => new Ga4MetricsProvider(),
            'google_ads'   => new GoogleAdsMetricsProvider(),
            'tiktok_ads'   => new TikTokMetricsProvider(),
            'linkedin_ads' => new LinkedInMetricsProvider(),
            'instagram'    => new InstagramMetricsProvider(),
        ];
    }

    public function sync(array $providers = [], ?int $days = null, bool $dryRun = false): array
    {
        $days = $days ?: (int) config('marketing_external.default_days', 7);
        $days = max(1, min(31, $days));

        $end   = now()->startOfDay();
        $start = $end->copy()->subDays($days - 1);

        $selected   = $this->resolveProviders($providers);
        $companyIds = $this->resolveCompanyIds();
        $allRows    = [];
        $providerResults = [];

        foreach ($companyIds as $companyId) {
            foreach ($selected as $providerKey) {
                $resultKey = $providerKey . '@company:' . $companyId;
                try {
                    if (in_array($providerKey, ['ga4', 'google_ads', 'linkedin_ads'], true)) {
                        $this->refreshTokenIfNeeded($providerKey, $companyId);
                    }

                    $providerConfig = $this->providerConfig($providerKey, $companyId);
                    if (! $this->isProviderEnabled($providerConfig)) {
                        $providerResults[$resultKey] = ['status' => 'skipped', 'message' => 'provider disabled', 'rows' => 0];
                        continue;
                    }

                    // Instagram: inject meta token fallback if own token is empty
                    if ($providerKey === 'instagram' && trim((string) ($providerConfig['access_token'] ?? '')) === '') {
                        $metaCfg = $this->providerConfig('meta', $companyId);
                        $providerConfig['meta_fallback_token'] = trim((string) ($metaCfg['access_token'] ?? ''));
                    }

                    $provider = $this->providers[$providerKey];
                    $rows     = $provider->fetch($start, $end, $companyId, $providerConfig);

                    $providerResults[$resultKey] = ['status' => 'ok', 'message' => null, 'rows' => count($rows)];
                    $allRows = array_merge($allRows, $rows);
                } catch (Throwable $e) {
                    $providerResults[$resultKey] = ['status' => 'error', 'message' => $e->getMessage(), 'rows' => 0];
                }
            }
        }

        $savedRows = 0;
        if (! $dryRun && ! empty($allRows)) {
            $savedRows = $this->saveRows($allRows);
        }

        return [
            'window'       => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString(), 'days' => $days],
            'dry_run'      => $dryRun,
            'providers'    => $providerResults,
            'fetched_rows' => count($allRows),
            'saved_rows'   => $savedRows,
        ];
    }

    private function resolveProviders(array $providers): array
    {
        $all = array_keys($this->providers);
        if (empty($providers)) {
            return $all;
        }

        $selected = collect($providers)
            ->map(fn ($v) => strtolower(trim((string) $v)))
            ->filter(fn ($v) => in_array($v, $all, true))
            ->values()
            ->all();

        return empty($selected) ? $all : $selected;
    }

    private function isProviderEnabled(array $providerConfig): bool
    {
        return (bool) ($providerConfig['enabled'] ?? false);
    }

    private function saveRows(array $rows): int
    {
        $now     = now();
        $payload = collect($rows)
            ->map(fn (array $row) => [
                'row_hash'      => (string) $row['row_hash'],
                'company_id'    => max(1, (int) ($row['company_id'] ?? 0)),
                'provider'      => (string) $row['provider'],
                'account_ref'   => $row['account_ref'],
                'metric_date'   => (string) $row['metric_date'],
                'campaign_key'  => $row['campaign_key'],
                'campaign_name' => $row['campaign_name'],
                'source'        => $row['source'],
                'medium'        => $row['medium'],
                'impressions'   => (int) $row['impressions'],
                'clicks'        => (int) $row['clicks'],
                'spend'         => (float) $row['spend'],
                'leads'         => (int) $row['leads'],
                'conversions'   => (int) $row['conversions'],
                'raw_payload'   => $row['raw_payload'],
                'synced_at'     => $now,
                'created_at'    => $now,
                'updated_at'    => $now,
            ])
            ->values()
            ->all();

        if (empty($payload)) {
            return 0;
        }

        MarketingExternalMetric::query()->upsert(
            $payload,
            ['row_hash'],
            [
                'provider', 'company_id', 'account_ref', 'metric_date', 'campaign_key',
                'campaign_name', 'source', 'medium', 'impressions', 'clicks', 'spend',
                'leads', 'conversions', 'raw_payload', 'synced_at', 'updated_at',
            ]
        );

        return count($payload);
    }

    private function refreshTokenIfNeeded(string $provider, int $companyId): void
    {
        $conn = MarketingIntegrationConnection::query()
            ->where('company_id', $companyId)
            ->where('provider', $provider)
            ->first();

        if (! $conn || ! $conn->refresh_token) {
            return;
        }

        $expiresAt = $conn->token_expires_at;
        if ($expiresAt && $expiresAt->isAfter(now()->addMinutes(30))) {
            return;
        }

        $meta         = is_array($conn->meta) ? $conn->meta : [];
        $clientId     = trim((string) ($meta['client_id'] ?? ''));
        $clientSecret = trim((string) ($meta['client_secret'] ?? ''));

        if ($clientId === '' || $clientSecret === '') {
            return;
        }

        try {
            $tokenUrl = $provider === 'linkedin_ads'
                ? 'https://www.linkedin.com/oauth/v2/accessToken'
                : 'https://oauth2.googleapis.com/token';

            $resp = Http::asForm()->timeout(15)->post($tokenUrl, [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $conn->refresh_token,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if (! $resp->successful()) {
                return;
            }

            $newToken  = (string) ($resp->json('access_token') ?? '');
            $expiresIn = (int) ($resp->json('expires_in') ?? 3600);

            if ($newToken === '') {
                return;
            }

            $conn->update([
                'access_token'     => $newToken,
                'token_expires_at' => now()->addSeconds($expiresIn),
                'status'           => 'connected',
                'last_error'       => null,
                'last_checked_at'  => now(),
            ]);

            $settingKey = 'ext_' . $provider . '_access_token';
            MarketingAdminSetting::query()
                ->where('company_id', $companyId)
                ->where('setting_key', $settingKey)
                ->update(['setting_value' => ['value' => $newToken]]);
        } catch (Throwable) {
            // Sessizce geç — mevcut token ile devam edilir
        }
    }

    private function resolveCompanyId(): int
    {
        if (app()->bound('current_company_id')) {
            return max(1, (int) app('current_company_id'));
        }

        return max(1, (int) Company::query()->where('is_active', true)->orderBy('id')->value('id'));
    }

    /** @return array<int, int> */
    private function resolveCompanyIds(): array
    {
        if (app()->bound('current_company_id')) {
            return [max(1, (int) app('current_company_id'))];
        }

        $ids = Company::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->values()
            ->all();

        return $ids !== [] ? $ids : [max(1, $this->resolveCompanyId())];
    }

    private function providerConfig(string $provider, int $companyId): array
    {
        $base = (array) data_get(config('marketing_external.providers'), $provider, []);
        $db   = $this->providerConfigFromDb($provider, $companyId);

        return array_merge($base, $db);
    }

    private function providerConfigFromDb(string $provider, int $companyId): array
    {
        $rows = MarketingAdminSetting::query()
            ->forCompany($companyId)
            ->where('setting_key', 'like', 'ext_' . $provider . '_%')
            ->get(['setting_key', 'setting_value']);

        $result = [];
        foreach ($rows as $row) {
            $key   = (string) $row->setting_key;
            $value = data_get($row->setting_value, 'value');
            $prop  = str_replace('ext_' . $provider . '_', '', $key);
            if ($prop === '') {
                continue;
            }
            $result[$prop] = $prop === 'enabled'
                ? filter_var($value, FILTER_VALIDATE_BOOLEAN)
                : (is_scalar($value) ? trim((string) $value) : $value);
        }

        return $result;
    }
}

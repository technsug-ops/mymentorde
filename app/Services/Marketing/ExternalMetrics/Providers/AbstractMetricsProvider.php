<?php

namespace App\Services\Marketing\ExternalMetrics\Providers;

use App\Services\Marketing\ExternalMetrics\Contracts\ExternalMetricsProviderInterface;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

abstract class AbstractMetricsProvider implements ExternalMetricsProviderInterface
{
    abstract public function fetch(Carbon $start, Carbon $end, int $companyId, array $cfg): array;

    protected function normalizeRow(array $row): array
    {
        $metricDate  = Carbon::parse((string) ($row['metric_date'] ?? now()->toDateString()))->toDateString();
        $accountRef  = $this->trimOrNull($row['account_ref'] ?? null);
        $campaignKey = $this->trimOrNull($row['campaign_key'] ?? null);
        $source      = $this->trimOrNull($row['source'] ?? null);
        $medium      = $this->trimOrNull($row['medium'] ?? null);

        return [
            'company_id'    => max(0, (int) ($row['company_id'] ?? 0)),
            'provider'      => strtolower(trim((string) ($row['provider'] ?? 'unknown'))),
            'account_ref'   => $accountRef,
            'metric_date'   => $metricDate,
            'campaign_key'  => $campaignKey,
            'campaign_name' => $this->trimOrNull($row['campaign_name'] ?? null),
            'source'        => $source,
            'medium'        => $medium,
            'impressions'   => max(0, $this->toInt($row['impressions'] ?? 0)),
            'clicks'        => max(0, $this->toInt($row['clicks'] ?? 0)),
            'spend'         => max(0, round($this->toFloat($row['spend'] ?? 0), 2)),
            'leads'         => max(0, $this->toInt($row['leads'] ?? 0)),
            'conversions'   => max(0, $this->toInt($row['conversions'] ?? 0)),
            'raw_payload'   => is_array($row['raw_payload'] ?? null) ? $row['raw_payload'] : null,
            'row_hash'      => $this->rowHash([
                'company_id'   => max(0, (int) ($row['company_id'] ?? 0)),
                'provider'     => strtolower(trim((string) ($row['provider'] ?? 'unknown'))),
                'account_ref'  => $accountRef,
                'metric_date'  => $metricDate,
                'campaign_key' => $campaignKey,
                'source'       => $source,
                'medium'       => $medium,
            ]),
        ];
    }

    protected function rowHash(array $parts): string
    {
        $raw = implode('|', [
            trim((string) ($parts['company_id'] ?? '0')),
            trim((string) ($parts['provider'] ?? '')),
            trim((string) ($parts['account_ref'] ?? '-')),
            trim((string) ($parts['metric_date'] ?? '')),
            trim((string) ($parts['campaign_key'] ?? '-')),
            trim((string) ($parts['source'] ?? '-')),
            trim((string) ($parts['medium'] ?? '-')),
        ]);

        return hash('sha256', $raw);
    }

    protected function extractMetaActionCount(array $actions, array $types): int
    {
        $lookup = collect($types)->map(fn ($v) => strtolower(trim((string) $v)))->all();
        $total = 0;
        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }
            $type = strtolower(trim((string) ($action['action_type'] ?? '')));
            if (! in_array($type, $lookup, true)) {
                continue;
            }
            $total += (int) round($this->toFloat($action['value'] ?? 0));
        }

        return max(0, $total);
    }

    protected function normalizeGa4Date(string $value): string
    {
        $clean = preg_replace('/[^0-9]/', '', $value) ?: '';
        if (strlen($clean) === 8) {
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 2) . '-' . substr($clean, 6, 2);
        }

        return now()->toDateString();
    }

    protected function googleAccessToken(string $credentialsPath, array $scopes): string
    {
        $credentials = new ServiceAccountCredentials($scopes, $credentialsPath);
        $token = $credentials->fetchAuthToken(HttpHandlerFactory::build());
        $accessToken = trim((string) ($token['access_token'] ?? ''));
        if ($accessToken === '') {
            throw new InvalidArgumentException('google access token could not be generated');
        }

        return $accessToken;
    }

    protected function trimOrNull(mixed $value): ?string
    {
        $clean = trim((string) ($value ?? ''));

        return $clean === '' ? null : $clean;
    }

    protected function toInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        return (int) round($this->toFloat($value));
    }

    protected function toFloat(mixed $value): float
    {
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }
        $clean = str_replace([',', ' '], ['', ''], (string) $value);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}

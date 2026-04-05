<?php

namespace App\Console\Commands;

use App\Models\Marketing\SocialMediaAccount;
use App\Models\Marketing\SocialMediaMonthlyMetric;
use App\Services\EventLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SocialMetricsSyncCommand extends Command
{
    protected $signature = 'social:sync-metrics
                            {--account= : Belirli bir account_id senkronize et}
                            {--dry-run  : DB güncellemesi yapmadan sonuçları listele}';

    protected $description = 'Aktif sosyal medya hesapları için platform API\'sinden metrik senkronizasyonu yapar.';

    public function handle(EventLogService $eventLog): int
    {
        $dryRun    = (bool) $this->option('dry-run');
        $accountId = $this->option('account') ? (int) $this->option('account') : null;

        $query = SocialMediaAccount::query()
            ->where('is_active', true)
            ->where('api_connected', true);

        if ($accountId !== null) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->info('Senkronize edilecek aktif ve bağlı hesap bulunamadı.');
            return self::SUCCESS;
        }

        $this->info("Toplam {$accounts->count()} hesap işlenecek." . ($dryRun ? ' [DRY-RUN]' : ''));

        $synced  = 0;
        $skipped = 0;
        $errors  = 0;

        foreach ($accounts as $account) {
            try {
                $metrics = $this->fetchMetrics($account);

                if ($metrics === null) {
                    $this->line("  [SKIP] {$account->platform} / {$account->account_name} — token yok veya desteklenmiyor");
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [DRY]  {$account->platform} / {$account->account_name}: " . json_encode($metrics));
                    $synced++;
                    continue;
                }

                // Upsert monthly metric
                $now = now();
                SocialMediaMonthlyMetric::query()->updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'month'      => (int) $now->month,
                        'year'       => (int) $now->year,
                    ],
                    [
                        'follower_count'       => $metrics['followers']      ?? $account->followers ?? 0,
                        'post_count'           => $metrics['posts']          ?? $account->total_posts ?? 0,
                        'total_views'          => $metrics['views']          ?? 0,
                        'avg_engagement_rate'  => $metrics['engagement']     ?? 0.0,
                        'profile_reach'        => $metrics['reach']          ?? 0,
                    ]
                );

                // Update account
                $account->update([
                    'followers'                  => $metrics['followers'] ?? $account->followers,
                    'followers_growth_this_month'=> $metrics['followers_growth'] ?? $account->followers_growth_this_month,
                    'total_posts'                => $metrics['posts'] ?? $account->total_posts,
                    'metrics_last_updated_at'    => $now,
                    'last_synced_at'             => $now,
                ]);

                $this->line("  [OK]   {$account->platform} / {$account->account_name}");
                $synced++;
            } catch (Throwable $e) {
                $this->error("  [ERR]  {$account->platform} / {$account->account_name}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->info("Tamamlandi: synced={$synced}, skipped={$skipped}, errors={$errors}");

        if (! $dryRun && ($synced > 0 || $errors > 0)) {
            $eventLog->log('social.metrics_sync', [
                'synced'  => $synced,
                'skipped' => $skipped,
                'errors'  => $errors,
                'dry_run' => false,
            ]);
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Fetch platform metrics via API.
     * Returns null if token is unavailable or platform unsupported.
     *
     * @return array<string,mixed>|null
     */
    private function fetchMetrics(SocialMediaAccount $account): ?array
    {
        $token    = $account->api_access_token ?? null;
        $platform = strtolower((string) $account->platform);
        $extId    = $account->external_account_id ?? null;

        switch ($platform) {
            case 'instagram':
                return $this->fetchInstagram($token, $extId);
            case 'facebook':
                return $this->fetchFacebook($token, $extId);
            case 'youtube':
                return $this->fetchYoutube($token);
            case 'linkedin':
                return $this->fetchLinkedIn($token, $extId);
            case 'tiktok':
                return $this->fetchTikTok($token);
            case 'twitter':
            case 'x':
                return $this->fetchTwitter($token, $extId);
            default:
                return null;
        }
    }

    /** @return array<string,mixed>|null */
    private function fetchInstagram(?string $token, ?string $igUserId): ?array
    {
        if (! $token || ! $igUserId) return null;
        try {
            $resp = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$igUserId}", [
                'fields'       => 'followers_count,media_count',
                'access_token' => $token,
            ]);
            if (! $resp->successful()) return null;
            $j = $resp->json();
            return [
                'followers' => (int) ($j['followers_count'] ?? 0),
                'posts'     => (int) ($j['media_count'] ?? 0),
            ];
        } catch (Throwable) { return null; }
    }

    /** @return array<string,mixed>|null */
    private function fetchFacebook(?string $token, ?string $pageId): ?array
    {
        if (! $token || ! $pageId) return null;
        try {
            $resp = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                'fields'       => 'fan_count,followers_count',
                'access_token' => $token,
            ]);
            if (! $resp->successful()) return null;
            $j = $resp->json();
            return [
                'followers' => (int) ($j['fan_count'] ?? $j['followers_count'] ?? 0),
            ];
        } catch (Throwable) { return null; }
    }

    /** @return array<string,mixed>|null */
    private function fetchYoutube(?string $token): ?array
    {
        if (! $token) return null;
        try {
            $resp = Http::withToken($token)->timeout(15)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'statistics',
                    'mine' => 'true',
                ]);
            if (! $resp->successful()) return null;
            $items = $resp->json('items') ?? [];
            $stats = $items[0]['statistics'] ?? [];
            return [
                'followers' => (int) ($stats['subscriberCount'] ?? 0),
                'posts'     => (int) ($stats['videoCount'] ?? 0),
                'views'     => (int) ($stats['viewCount'] ?? 0),
            ];
        } catch (Throwable) { return null; }
    }

    /** @return array<string,mixed>|null */
    private function fetchLinkedIn(?string $token, ?string $orgUrn): ?array
    {
        if (! $token || ! $orgUrn) return null;
        try {
            $resp = Http::withToken($token)->timeout(15)
                ->get('https://api.linkedin.com/v2/organizationFollowerStatistics', [
                    'q' => 'organizationalEntity',
                    'organizationalEntity' => $orgUrn,
                ]);
            if (! $resp->successful()) return null;
            $elements = $resp->json('elements') ?? [];
            $latest   = $elements[0] ?? [];
            return [
                'followers' => (int) ($latest['followerGains']['organicFollowerGain'] ?? 0),
            ];
        } catch (Throwable) { return null; }
    }

    /** @return array<string,mixed>|null */
    private function fetchTikTok(?string $token): ?array
    {
        if (! $token) return null;
        try {
            $resp = Http::timeout(15)
                ->withHeaders(['Access-Token' => $token])
                ->get('https://business-api.tiktok.com/open_api/v1.3/business/get/', [
                    'fields' => '["follower_count","total_video","profile_views"]',
                ]);
            if (! $resp->successful()) return null;
            $data = $resp->json('data') ?? [];
            return [
                'followers' => (int) ($data['follower_count'] ?? 0),
                'posts'     => (int) ($data['total_video'] ?? 0),
                'views'     => (int) ($data['profile_views'] ?? 0),
            ];
        } catch (Throwable) { return null; }
    }

    /** @return array<string,mixed>|null */
    private function fetchTwitter(?string $token, ?string $userId): ?array
    {
        if (! $token || ! $userId) return null;
        try {
            $resp = Http::withToken($token)->timeout(15)
                ->get("https://api.twitter.com/2/users/{$userId}", [
                    'user.fields' => 'public_metrics',
                ]);
            if (! $resp->successful()) return null;
            $metrics = $resp->json('data.public_metrics') ?? [];
            return [
                'followers' => (int) ($metrics['followers_count'] ?? 0),
                'posts'     => (int) ($metrics['tweet_count'] ?? 0),
            ];
        } catch (Throwable) { return null; }
    }
}

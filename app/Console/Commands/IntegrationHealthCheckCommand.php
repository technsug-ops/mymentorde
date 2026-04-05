<?php

namespace App\Console\Commands;

use App\Models\MarketingIntegrationConnection;
use App\Services\EventLogService;
use App\Services\Marketing\ExternalMetrics\ExternalMetricsSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class IntegrationHealthCheckCommand extends Command
{
    protected $signature = 'integrations:health-check
                            {--provider= : Belirli bir provider\'ı kontrol et (meta, ga4, calendly vb.)}
                            {--company=  : Belirli bir company_id\'yi kontrol et}
                            {--dry-run   : Güncelleme yapmadan sonuçları listele}';

    protected $description = 'Tüm aktif entegrasyon bağlantılarını kontrol eder; yaklaşan token süre dolumlarını yeniler.';

    public function handle(
        ExternalMetricsSyncService $syncService,
        EventLogService            $eventLog,
    ): int {
        $dryRun     = (bool) $this->option('dry-run');
        $filterProv = $this->option('provider') ? strtolower(trim((string) $this->option('provider'))) : null;
        $filterComp = $this->option('company')  ? (int) $this->option('company')  : null;

        $query = MarketingIntegrationConnection::query();

        if ($filterProv !== null) {
            $query->where('provider', $filterProv);
        }
        if ($filterComp !== null) {
            $query->where('company_id', $filterComp);
        }

        $connections = $query->get();

        if ($connections->isEmpty()) {
            $this->info('Kontrol edilecek bağlantı bulunamadı.');
            return self::SUCCESS;
        }

        $this->info("Toplam {$connections->count()} bağlantı kontrol ediliyor" . ($dryRun ? ' [DRY-RUN]' : '') . '...');

        $results = ['ok' => 0, 'error' => 0, 'refreshed' => 0, 'skipped' => 0];

        foreach ($connections as $conn) {
            $provider  = (string) $conn->provider;
            $companyId = (int)    ($conn->company_id ?? 0);
            $label     = "[{$provider}@company:{$companyId}]";

            // ── Token yenileme ────────────────────────────────────────────────
            $refreshed = false;
            if (!$dryRun && $conn->refresh_token) {
                $expiresAt = $conn->token_expires_at;
                if (!$expiresAt || $expiresAt->isBefore(now()->addDays(7))) {
                    $refreshed = $this->tryRefreshToken($conn, $provider, $label);
                    if ($refreshed) {
                        $results['refreshed']++;
                    }
                }
            }

            // ── Sağlık kontrolü ping ──────────────────────────────────────────
            $token = $conn->fresh()->access_token ?? '';
            [$ok, $errMsg] = $this->pingProvider($provider, $token, $companyId, $syncService);

            if (!$dryRun) {
                $conn->update([
                    'status'          => $ok ? 'connected' : 'error',
                    'last_checked_at' => now(),
                    'last_error'      => $ok ? null : mb_substr((string) $errMsg, 0, 400),
                ]);
            }

            if ($ok) {
                $results['ok']++;
                $this->line("  <fg=green>✓</> {$label}" . ($refreshed ? ' [token yenilendi]' : ''));
            } else {
                $results['error']++;
                $this->line("  <fg=red>✗</> {$label} — {$errMsg}");
            }
        }

        // ── Özet logu ────────────────────────────────────────────────────────
        $this->newLine();
        $this->info("Sonuç: {$results['ok']} OK · {$results['error']} hata · {$results['refreshed']} token yenilendi");

        if (!$dryRun) {
            try {
                app(EventLogService::class)->log(
                    'integration.health_check',
                    'system',
                    'scheduler',
                    'Entegrasyon sağlık kontrolü tamamlandı.',
                    $results,
                    'system',
                );
            } catch (Throwable) {}
        }

        return $results['error'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ── Token Yenileme ────────────────────────────────────────────────────────

    private function tryRefreshToken(MarketingIntegrationConnection $conn, string $provider, string $label): bool
    {
        $meta         = is_array($conn->meta) ? $conn->meta : [];
        $clientId     = trim((string) ($meta['client_id']     ?? ''));
        $clientSecret = trim((string) ($meta['client_secret'] ?? ''));

        if ($clientId === '' || $clientSecret === '') {
            return false;
        }

        // Şu an yalnızca Google OAuth destekleniyor (GA4, Google Ads)
        $googleProviders = ['ga4', 'google_ads'];
        if (!in_array($provider, $googleProviders, true)) {
            return false;
        }

        try {
            $resp = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $conn->refresh_token,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if (!$resp->successful()) {
                $this->warn("  {$label} token yenileme başarısız: HTTP " . $resp->status());
                return false;
            }

            $newToken  = (string) ($resp->json('access_token') ?? '');
            $expiresIn = (int)   ($resp->json('expires_in')   ?? 3600);

            if ($newToken === '') {
                return false;
            }

            $conn->update([
                'access_token'     => $newToken,
                'token_expires_at' => now()->addSeconds($expiresIn),
                'status'           => 'connected',
                'last_error'       => null,
            ]);

            return true;
        } catch (Throwable $e) {
            $this->warn("  {$label} token yenileme hatası: " . $e->getMessage());
            return false;
        }
    }

    // ── Provider Ping ─────────────────────────────────────────────────────────

    /**
     * @return array{bool, string|null}
     */
    private function pingProvider(
        string                     $provider,
        string                     $token,
        int                        $companyId,
        ExternalMetricsSyncService $syncService,
    ): array {
        if ($token === '') {
            return [false, 'access_token boş'];
        }

        try {
            return match (true) {
                in_array($provider, ['ga4', 'google_ads'], true) => $this->pingGoogle($provider, $token, $companyId, $syncService),
                $provider === 'meta'       => $this->pingMeta($token),
                $provider === 'calendly'   => $this->pingCalendly($token),
                $provider === 'mailchimp'  => $this->pingMailchimp($token, $companyId),
                $provider === 'zoom'       => $this->pingZoom($token),
                default                    => [true, null],   // bilinmeyen: token var, yeter
            };
        } catch (Throwable $e) {
            return [false, $e->getMessage()];
        }
    }

    private function pingGoogle(string $provider, string $token, int $companyId, ExternalMetricsSyncService $syncService): array
    {
        $result = $syncService->sync([$provider], 1, true);
        $status = data_get($result, "providers.{$provider}@company:{$companyId}.status", 'error');
        if ($status === 'ok' || $status === 'skipped') {
            return [true, null];
        }
        return [false, (string) data_get($result, "providers.{$provider}@company:{$companyId}.message", 'sync hatası')];
    }

    private function pingMeta(string $token): array
    {
        $resp = Http::timeout(10)->get('https://graph.facebook.com/me', ['access_token' => $token]);
        if ($resp->successful() && $resp->json('id')) {
            return [true, null];
        }
        return [false, 'Meta /me: ' . ($resp->json('error.message') ?? 'HTTP ' . $resp->status())];
    }

    private function pingCalendly(string $token): array
    {
        $resp = Http::withToken($token)->timeout(10)->get('https://api.calendly.com/users/me');
        if ($resp->successful()) {
            return [true, null];
        }
        return [false, 'Calendly /users/me: HTTP ' . $resp->status()];
    }

    private function pingMailchimp(string $token, int $companyId): array
    {
        // Mailchimp API key: "key-us1" formatında datacenter prefix içerir
        $dc = 'us1';
        if (str_contains($token, '-')) {
            $dc = substr($token, strrpos($token, '-') + 1) ?: 'us1';
        }
        $resp = Http::withBasicAuth('anystring', $token)
            ->timeout(10)
            ->get("https://{$dc}.api.mailchimp.com/3.0/ping");
        if ($resp->successful() && $resp->json('health_status') === 'Everything\'s Chimpy!') {
            return [true, null];
        }
        return [false, 'Mailchimp /ping: HTTP ' . $resp->status()];
    }

    private function pingZoom(string $token): array
    {
        $resp = Http::withToken($token)->timeout(10)->get('https://api.zoom.us/v2/users/me');
        if ($resp->successful()) {
            return [true, null];
        }
        return [false, 'Zoom /users/me: HTTP ' . $resp->status()];
    }
}

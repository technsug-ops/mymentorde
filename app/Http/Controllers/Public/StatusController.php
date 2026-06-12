<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public Status Page.
 *
 * Public URL: /durum  (TR), /status (EN alias)
 *
 * Çekirdek altyapı servislerinin durumunu real-time gösterir:
 *   - Database  : SELECT 1 ping + latency
 *   - Cache     : write + read round-trip
 *   - Storage   : local disk write + delete
 *   - Mail      : config'ten driver/host kontrolü
 *   - Realtime  : Pusher BROADCAST_DRIVER kontrolü
 *
 * 60sn cache (DoS koruması) + JSON çıktı (?format=json) için ayrı.
 */
class StatusController extends Controller
{
    public function show(Request $request)
    {
        $report = $this->buildReport();

        if ($request->query('format') === 'json' || $request->wantsJson()) {
            return $this->jsonResponse($report);
        }

        return view('public.status', [
            'report'       => $report,
            'brandName'    => config('brand.name', 'MentorDE'),
            'overallState' => $this->resolveOverall($report),
            'lastChecked'  => Carbon::now(),
        ]);
    }

    private function jsonResponse(array $report): JsonResponse
    {
        $overall = $this->resolveOverall($report);
        return response()->json([
            'status'     => $overall,
            'message'    => $overall === 'operational' ? 'Tüm sistemler çalışıyor' : ($overall === 'degraded' ? 'Bazı servisler düşük performansta' : 'Bazı servisler çalışmıyor'),
            'checked_at' => Carbon::now()->toIso8601String(),
            'services'   => $report,
        ], $overall === 'operational' ? 200 : ($overall === 'degraded' ? 200 : 503));
    }

    private function buildReport(): array
    {
        // 30 saniyelik in-memory cache — sayfa refresh DoS engeli
        return Cache::remember('public.status.report', 30, function () {
            return [
                'database' => $this->checkDatabase(),
                'cache'    => $this->checkCache(),
                'storage'  => $this->checkStorage(),
                'mail'     => $this->checkMail(),
                'realtime' => $this->checkRealtime(),
            ];
        });
    }

    private function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::select('SELECT 1 AS ok');
            $latencyMs = (int) round((microtime(true) - $start) * 1000);
            return [
                'name'   => 'Veritabanı (MySQL)',
                'state'  => $latencyMs > 500 ? 'degraded' : 'operational',
                'latency_ms' => $latencyMs,
                'note'   => $latencyMs > 500 ? 'Yüksek gecikme' : null,
            ];
        } catch (\Throwable $e) {
            return [
                'name'  => 'Veritabanı (MySQL)',
                'state' => 'outage',
                'note'  => 'Bağlantı hatası',
            ];
        }
    }

    private function checkCache(): array
    {
        $start = microtime(true);
        try {
            $key = 'status:probe:' . Str::random(8);
            $val = 'OK_' . microtime(true);
            Cache::put($key, $val, 30);
            $read = Cache::get($key);
            Cache::forget($key);
            $latencyMs = (int) round((microtime(true) - $start) * 1000);
            if ($read !== $val) {
                return ['name' => 'Cache', 'state' => 'outage', 'note' => 'Read/write mismatch'];
            }
            return [
                'name'  => 'Cache',
                'state' => $latencyMs > 200 ? 'degraded' : 'operational',
                'latency_ms' => $latencyMs,
                'note'  => $latencyMs > 200 ? 'Yavaş cevap' : null,
            ];
        } catch (\Throwable $e) {
            return ['name' => 'Cache', 'state' => 'outage', 'note' => 'Erişim hatası'];
        }
    }

    private function checkStorage(): array
    {
        $start = microtime(true);
        try {
            $disk = Storage::disk('local');
            $path = 'status-probe/' . Str::random(10) . '.txt';
            $disk->put($path, 'OK ' . microtime(true));
            $exists = $disk->exists($path);
            $disk->delete($path);
            $latencyMs = (int) round((microtime(true) - $start) * 1000);
            if (!$exists) {
                return ['name' => 'Dosya Depolama', 'state' => 'outage', 'note' => 'Yazma sonrası dosya bulunamadı'];
            }
            return [
                'name'  => 'Dosya Depolama',
                'state' => 'operational',
                'latency_ms' => $latencyMs,
            ];
        } catch (\Throwable $e) {
            return ['name' => 'Dosya Depolama', 'state' => 'outage', 'note' => 'Disk yazma hatası'];
        }
    }

    private function checkMail(): array
    {
        $driver = config('mail.default', 'log');
        if ($driver === 'log') {
            // 'log' mode prod'da yanlış konfigürasyon — degraded say
            return [
                'name'  => 'E-posta Gönderim',
                'state' => 'degraded',
                'note'  => 'Mail driver "log" — mailler gönderilmiyor',
            ];
        }

        $host = (string) config("mail.mailers.{$driver}.host", '');
        $apiKey = config('services.resend.key') ?? config("mail.mailers.{$driver}.token", null);
        $ok = $host !== '' || !empty($apiKey);

        return [
            'name'  => 'E-posta Gönderim',
            'state' => $ok ? 'operational' : 'degraded',
            'note'  => $ok ? 'Provider: ' . strtoupper($driver) : 'Provider konfigürasyonu eksik',
        ];
    }

    private function checkRealtime(): array
    {
        $driver = config('broadcasting.default', 'log');
        if ($driver === 'log' || $driver === 'null') {
            return [
                'name'  => 'Anlık Bildirim (Pusher)',
                'state' => 'degraded',
                'note'  => 'Driver: ' . strtoupper($driver) . ' — real-time aktif değil',
            ];
        }

        $key = (string) config("broadcasting.connections.{$driver}.key", '');
        $secret = (string) config("broadcasting.connections.{$driver}.secret", '');

        if ($key === '' || $secret === '') {
            return [
                'name'  => 'Anlık Bildirim (Pusher)',
                'state' => 'outage',
                'note'  => 'API credentials eksik',
            ];
        }

        return [
            'name'  => 'Anlık Bildirim (Pusher)',
            'state' => 'operational',
            'note'  => 'Driver: ' . strtoupper($driver),
        ];
    }

    /**
     * Genel duruma karar ver:
     *   - outage > 0 → outage
     *   - degraded > 0 → degraded
     *   - hepsi operational → operational
     */
    private function resolveOverall(array $report): string
    {
        $states = array_column($report, 'state');
        if (in_array('outage', $states, true))   return 'outage';
        if (in_array('degraded', $states, true)) return 'degraded';
        return 'operational';
    }
}

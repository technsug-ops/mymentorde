<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cron'suz kuyruk boşaltma (#27).
 *
 * KASSERVER paketinde cronjob HAKKI YOK (0 möglich) ve queue worker da yok.
 * Bu yüzden 'jobs' tablosundaki mail/bildirim job'ları kendiliğinden işlenmez.
 * Bu middleware gerçek panel trafiğine binerek, yanıt tarayıcıya gönderildikten
 * SONRA (terminate fazı — kullanıcı BEKLEMEZ) bekleyen job'ları kısa bir bütçeyle
 * işler. Kilit + throttle ile en fazla ~50sn'de bir tetiklenir; aynı anda iki
 * worker çalışmaz. Personel panelde gezindikçe kuyruk ~1 dakika içinde boşalır.
 *
 * Tasarım gereği güvenli: tamamen try/catch içinde, terminate'te (yanıttan sonra)
 * çalışır → hata olsa bile kullanıcı isteği etkilenmez (addon bağımsızlığı).
 */
class DrainQueueOnTraffic
{
    /** Aynı pencere içinde tek tetik + tek worker garantisi (saniye). */
    private const THROTTLE_SECONDS = 50;

    /** Tek tetikte en fazla bu kadar saniye job işle (sonra durur). */
    private const MAX_RUN_SECONDS = 15;

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        try {
            // Sadece database queue'da anlamlı (sync/redis'te gerek yok).
            if ((string) config('queue.default') !== 'database') {
                return;
            }

            // Throttle + lock tek atomik adımda: 50sn içinde yalnızca İLK istek
            // bu satırı geçer (Cache::add = SETNX). Diğerleri sessizce döner.
            if (!Cache::add('mentorde_queue_drain_lock', 1, self::THROTTLE_SECONDS)) {
                return;
            }

            // Boşsa worker bootstrap etme (gereksiz yük).
            if ((int) DB::table('jobs')->count() === 0) {
                return;
            }

            Artisan::call('queue:work', [
                '--queue'           => 'notifications,default',
                '--stop-when-empty' => true,
                '--max-time'        => self::MAX_RUN_SECONDS,
                '--tries'           => 3,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

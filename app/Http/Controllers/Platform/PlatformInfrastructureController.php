<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Platform Owner — Altyapı / Infrastructure sayfası.
 *
 * Sunucu sağlığı, DB durumu, cache/queue config, son migration'lar,
 * composer paketler ve sistem metrikleri.
 */
class PlatformInfrastructureController extends Controller
{
    public function index(): View
    {
        // PHP
        $php = [
            'version'    => PHP_VERSION,
            'sapi'       => PHP_SAPI,
            'memory'     => $this->fmtBytes(memory_get_usage(true)),
            'memory_peak'=> $this->fmtBytes(memory_get_peak_usage(true)),
            'extensions' => array_slice(array_map('strtolower', get_loaded_extensions()), 0, 40),
        ];

        // MySQL
        $mysql = [
            'connected' => false,
            'version'   => null,
            'driver'    => config('database.default'),
            'database'  => config('database.connections.' . config('database.default') . '.database'),
            'error'     => null,
        ];
        try {
            $row = DB::selectOne('SELECT VERSION() AS v');
            $mysql['version']   = $row->v ?? 'unknown';
            $mysql['connected'] = true;
        } catch (\Throwable $e) {
            $mysql['error'] = $e->getMessage();
        }

        // Cache
        $cache = [
            'driver' => config('cache.default'),
            'status' => 'unknown',
            'detail' => null,
        ];
        try {
            $probeKey = 'platform_infra_probe_' . substr(md5((string) microtime(true)), 0, 8);
            Cache::put($probeKey, '1', 30);
            $ok = Cache::get($probeKey) === '1';
            Cache::forget($probeKey);
            $cache['status'] = $ok ? 'ok' : 'fail';
        } catch (\Throwable $e) {
            $cache['status'] = 'fail';
            $cache['detail'] = $e->getMessage();
        }

        // Redis (opsiyonel)
        $redis = ['enabled' => false, 'status' => null];
        if (extension_loaded('redis') || class_exists(\Predis\Client::class)) {
            $redis['enabled'] = true;
            try {
                $redis['status'] = \Illuminate\Support\Facades\Redis::connection()->ping() ? 'ok' : 'fail';
            } catch (\Throwable $e) {
                $redis['status'] = 'fail';
            }
        }

        // Queue
        $queue = [
            'driver'  => config('queue.default'),
            'pending' => null,
            'failed'  => null,
        ];
        try {
            if (Schema::hasTable('jobs')) {
                $queue['pending'] = (int) DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $queue['failed'] = (int) DB::table('failed_jobs')->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Disk usage
        $disk = [];
        try {
            $path = storage_path();
            $disk = [
                'path'    => $path,
                'free'    => @disk_free_space($path),
                'total'   => @disk_total_space($path),
            ];
            if ($disk['free'] && $disk['total']) {
                $disk['used'] = $disk['total'] - $disk['free'];
                $disk['pct']  = round(($disk['used'] / $disk['total']) * 100, 1);
                $disk['free_h']  = $this->fmtBytes((int) $disk['free']);
                $disk['used_h']  = $this->fmtBytes((int) $disk['used']);
                $disk['total_h'] = $this->fmtBytes((int) $disk['total']);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Opcache
        $opcache = ['enabled' => false];
        if (function_exists('opcache_get_status')) {
            try {
                $st = @opcache_get_status(false);
                if (is_array($st)) {
                    $opcache['enabled']       = (bool) ($st['opcache_enabled'] ?? false);
                    $opcache['hit_rate']      = isset($st['opcache_statistics']['opcache_hit_rate'])
                        ? round((float) $st['opcache_statistics']['opcache_hit_rate'], 2) : null;
                    $opcache['cached_scripts']= $st['opcache_statistics']['num_cached_scripts'] ?? null;
                    $opcache['memory_used']   = isset($st['memory_usage']['used_memory'])
                        ? $this->fmtBytes((int) $st['memory_usage']['used_memory']) : null;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Server load (Windows desteklemez — sys_getloadavg yoksa null)
        $load = function_exists('sys_getloadavg') ? @sys_getloadavg() : null;

        // Composer paketler (top 20, installed.json üzerinden)
        $packages = $this->readInstalledPackages(20);

        // Recent migrations
        $migrations = [];
        try {
            if (Schema::hasTable('migrations')) {
                $migrations = DB::table('migrations')
                    ->orderByDesc('batch')
                    ->orderByDesc('id')
                    ->limit(15)
                    ->get();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return view('platform.infrastructure.index', [
            'php'        => $php,
            'mysql'      => $mysql,
            'cache'      => $cache,
            'redis'      => $redis,
            'queue'      => $queue,
            'disk'       => $disk,
            'opcache'    => $opcache,
            'load'       => $load,
            'packages'   => $packages,
            'migrations' => $migrations,
            'laravel'    => app()->version(),
            'env'        => app()->environment(),
        ]);
    }

    public function flushCache(Request $request): RedirectResponse
    {
        try {
            Cache::flush();
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Log::info('[platform-infra] cache flushed', ['user_id' => auth()->id()]);
            return back()->with('success', 'Tüm cache temizlendi (cache + config + route + view).');
        } catch (\Throwable $e) {
            Log::warning('[platform-infra] cache flush failed', ['err' => $e->getMessage()]);
            return back()->with('error', 'Cache temizlenirken hata: ' . $e->getMessage());
        }
    }

    public function runMigrations(Request $request): RedirectResponse
    {
        try {
            $exit = Artisan::call('migrate', ['--force' => true]);
            $out  = trim((string) Artisan::output());
            Log::info('[platform-infra] migrate run', ['user_id' => auth()->id(), 'exit' => $exit, 'output' => substr($out, 0, 2000)]);
            if ($exit === 0) {
                return back()->with('success', 'Migration başarılı: ' . (str_contains($out, 'Nothing to migrate') ? 'değişiklik yok' : 'çalıştırıldı'));
            }
            return back()->with('error', 'Migration başarısız: çıkış kodu ' . $exit);
        } catch (\Throwable $e) {
            Log::warning('[platform-infra] migrate failed', ['err' => $e->getMessage()]);
            return back()->with('error', 'Migration hatası: ' . $e->getMessage());
        }
    }

    public function dumpAutoload(Request $request): RedirectResponse
    {
        // composer binary yolu — KAS gibi shared hosting'de yoksa try/catch ile sessiz fail.
        $composer = $this->findComposerBinary();
        if (!$composer) {
            return back()->with('error', 'composer binary bulunamadı. SSH ile sunucuya bağlanıp manuel "composer dump-autoload" çalıştırın.');
        }

        try {
            $output = [];
            $code = 0;
            @exec(escapeshellarg($composer) . ' dump-autoload --optimize 2>&1', $output, $code);
            Log::info('[platform-infra] composer dump-autoload', [
                'user_id' => auth()->id(),
                'exit'    => $code,
                'output'  => substr(implode("\n", $output), 0, 2000),
            ]);
            if ($code === 0) {
                return back()->with('success', 'Autoload yenilendi.');
            }
            return back()->with('error', 'Autoload yenileme başarısız (çıkış ' . $code . ').');
        } catch (\Throwable $e) {
            return back()->with('error', 'Autoload hatası: ' . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function fmtBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $b = (float) $bytes;
        while ($b >= 1024 && $i < count($units) - 1) {
            $b /= 1024;
            $i++;
        }
        return number_format($b, $b >= 100 ? 0 : 1) . ' ' . $units[$i];
    }

    private function readInstalledPackages(int $limit): array
    {
        $path = base_path('vendor/composer/installed.json');
        if (!is_readable($path)) {
            return [];
        }
        try {
            $json = json_decode((string) file_get_contents($path), true);
            $list = $json['packages'] ?? $json ?? [];
            $rows = [];
            foreach ($list as $pkg) {
                if (!isset($pkg['name'])) {
                    continue;
                }
                $rows[] = [
                    'name'    => $pkg['name'],
                    'version' => $pkg['version'] ?? '?',
                    'type'    => $pkg['type'] ?? 'library',
                ];
            }
            usort($rows, fn($a, $b) => strcmp($a['name'], $b['name']));
            return array_slice($rows, 0, $limit);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function findComposerBinary(): ?string
    {
        $candidates = [
            base_path('composer.phar'),
            '/usr/local/bin/composer',
            '/usr/bin/composer',
        ];
        foreach ($candidates as $c) {
            if (is_executable($c)) {
                return $c;
            }
        }
        // PATH'ten ara (Windows + Linux)
        $which = @shell_exec((PHP_OS_FAMILY === 'Windows' ? 'where composer' : 'which composer') . ' 2>&1');
        if (is_string($which) && $which !== '') {
            $first = trim(strtok($which, "\n") ?: '');
            if ($first !== '' && (is_executable($first) || PHP_OS_FAMILY === 'Windows')) {
                return $first;
            }
        }
        return null;
    }
}

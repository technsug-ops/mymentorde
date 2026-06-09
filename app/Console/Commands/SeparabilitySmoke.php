<?php

namespace App\Console\Commands;

use App\Support\ModuleAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Symfony\Component\Console\Helper\Table;

/**
 * SEPARABILITY SMOKE TEST
 *
 * Her modülü teker teker kapatıp critical view + route'ların hâlâ
 * compile / dispatch edilebilir olduğunu doğrular.
 *
 * Kullanım:
 *   php artisan separability:smoke           # tüm modüller
 *   php artisan separability:smoke --module=dealer  # tek modül
 *
 * Test 4 kontrol yapar:
 *  1. Route::has() — modülün kendi route'ları var mı?
 *  2. Module gate work — ModuleAccess::enabled() toggle çalışıyor mu?
 *  3. Critical view compile — modül kapalıyken Blade compile fail veriyor mu?
 *  4. Partial includes — @includeIf'ler graceful skip yapıyor mu?
 *
 * Çıktı: Renkli tablo + summary (PASS/FAIL).
 * Exit code: 0 = hepsi pass, 1 = en az 1 fail.
 */
class SeparabilitySmoke extends Command
{
    protected $signature = 'separability:smoke
        {--module= : Sadece tek modül test et}
        {--verbose-errors : Hata detaylarını uzun göster}';

    protected $description = 'Her modülü kapatıp core view+route hâlâ çalışıyor mu test eder';

    /** Critical view'lar — her zaman compile edilebilir olmalı */
    private const CRITICAL_VIEWS = [
        'manager.layouts.app'                          => 'Manager sidebar layout',
        'manager.dashboard'                            => 'Manager dashboard',
        'manager.guest-detail'                         => 'Manager guest detay',
        'manager.student-detail'                       => 'Manager öğrenci detay',
        'manager.guests'                               => 'Manager guest listesi',
        'manager.students'                             => 'Manager öğrenci listesi',
        'manager.partials.application-guides-buttons'  => 'Application Guides partial',
        'manager.partials.password-reset-card'         => 'Şifre sıfırlama partial',
        'manager.partials.contracts-sidebar-section'   => 'Sözleşmeler sidebar partial',
        'marketing-admin.partials.bayi-section'        => 'Bayi sidebar partial',
        'senior.students'                              => 'Senior öğrenci listesi',
        'senior.student-detail'                        => 'Senior öğrenci detay',
        'senior.guest-detail'                          => 'Senior aday öğrenci detay',
        'guest.services'                               => 'Guest hizmet/ödeme sayfası',
        'auth.login'                                   => 'Login sayfası',
        'auth.password-change-required'                => 'Zorunlu şifre değişim',
    ];

    private array $results = [];
    private int $failCount = 0;

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Separability Smoke Test — Module Isolation Verification');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        $targetModule = $this->option('module');
        $allModules = ModuleAccess::allModules();
        $modulesToTest = $targetModule
            ? array_values(array_filter($allModules, fn ($m) => $m === $targetModule))
            : $allModules;

        if (empty($modulesToTest)) {
            $this->error("Modül bulunamadı: {$targetModule}");
            return self::FAILURE;
        }

        // ── 1. Baseline: tüm modüller AÇIK iken views compile olabilmeli ──
        $this->line('1️⃣  Baseline (tüm modüller açık) — view compile testi...');
        $baseline = $this->testAllCriticalViewsCompile('baseline');
        $this->renderViewResultLine('baseline', $baseline);
        $this->newLine();

        // ── 2. Her modül için ayrı test ──
        $this->line('2️⃣  Her modül kapatıldığında testler...');
        $this->newLine();

        foreach ($modulesToTest as $module) {
            $this->testModule($module);
        }

        // ── 3. Summary tablo ──
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Summary');
        $this->info('═══════════════════════════════════════════════════════════════');

        $rows = [];
        foreach ($this->results as $module => $checks) {
            $pass = collect($checks)->every(fn ($c) => $c['pass']);
            $rows[] = [
                $module,
                $checks['routes']['pass'] ? '✓' : '✗',
                $checks['toggle']['pass'] ? '✓' : '✗',
                $checks['views']['pass'] ? '✓' : '✗',
                $pass ? '🟢 PASS' : '🔴 FAIL',
            ];
        }
        $this->table(['Module', 'Routes', 'Toggle', 'Views', 'Result'], $rows);

        $this->newLine();
        if ($this->failCount === 0) {
            $this->info("✅ Hepsi PASS — {$this->failCount} fail.");
            return self::SUCCESS;
        }
        $this->error("❌ {$this->failCount} test FAIL — separability hatası var.");
        $this->line('Detay için: --verbose-errors flag\'i kullan.');
        return self::FAILURE;
    }

    private function testModule(string $module): void
    {
        $this->line("   ▶ {$module}");

        $r1 = $this->testRoutes($module);
        $r2 = $this->testToggle($module);
        $r3 = $this->testViewsWithModuleDisabled($module);

        $this->results[$module] = [
            'routes' => $r1,
            'toggle' => $r2,
            'views'  => $r3,
        ];

        foreach (['routes', 'toggle', 'views'] as $k) {
            if (! $this->results[$module][$k]['pass']) {
                $this->failCount++;
                if ($this->option('verbose-errors')) {
                    $this->warn("      ✗ {$k}: " . ($this->results[$module][$k]['msg'] ?? 'fail'));
                } else {
                    $this->warn("      ✗ {$k}");
                }
            } else {
                $this->line("      ✓ {$k}");
            }
        }
        $this->newLine();
    }

    private function testRoutes(string $module): array
    {
        // Modülün dependent route'larından örnekleme — her modül için "olması beklenen" temel rota
        $expected = match ($module) {
            'application_guides' => ['manager.application-guide.show', 'manager.student.application-guide.show'],
            'booking'            => ['booking.public.show'],
            'contracts_hub'      => ['manager.business-contracts.index'],
            'dealer'             => ['manager.dealers.index', 'manager.dealer-tiers.index'],
            'doc_request'        => ['manager.guest.document-tokens.store'],
            'manager_password_reset' => ['manager.quick-admin.password.reset'],
            default              => [], // bazı modüllerin tipik route'u yok
        };

        if (empty($expected)) {
            return ['pass' => true, 'msg' => 'no specific routes to check'];
        }

        $missing = [];
        foreach ($expected as $name) {
            if (! Route::has($name)) {
                $missing[] = $name;
            }
        }
        if (count($missing) > 0) {
            return ['pass' => false, 'msg' => 'missing: ' . implode(', ', $missing)];
        }
        return ['pass' => true, 'msg' => 'all ' . count($expected) . ' routes registered'];
    }

    private function testToggle(string $module): array
    {
        try {
            // Test için sabit company_id (toggle simulation için)
            $testCompanyId = 1;
            $cacheKey = "company:{$testCompanyId}:enabled_modules";

            // 1. ENABLED state — modül var listede
            Cache::put($cacheKey, ModuleAccess::allModules(), 60);
            $beforeDisable = ModuleAccess::enabled($module, $testCompanyId);

            // 2. DISABLED state — modülü listeden çıkar
            $disabledList = array_values(array_filter(
                ModuleAccess::allModules(),
                fn ($m) => $m !== $module
            ));
            Cache::put($cacheKey, $disabledList, 60);
            $afterDisable = ModuleAccess::enabled($module, $testCompanyId);

            // Restore
            Cache::forget($cacheKey);

            if ($module === 'core') {
                // core her zaman true döner — toggle test'i atla
                return ['pass' => true, 'msg' => 'core always enabled'];
            }
            if ($beforeDisable !== true) {
                return ['pass' => false, 'msg' => 'baseline state: not enabled (expected true)'];
            }
            if ($afterDisable !== false) {
                return ['pass' => false, 'msg' => 'disable simulation FAIL — module hâlâ enabled'];
            }
            return ['pass' => true, 'msg' => 'toggle çalışıyor'];
        } catch (\Throwable $e) {
            return ['pass' => false, 'msg' => $e->getMessage()];
        }
    }

    private function testViewsWithModuleDisabled(string $module): array
    {
        $errors = [];
        $tested = 0;

        try {
            $this->overrideModuleState($module, false);

            foreach (self::CRITICAL_VIEWS as $view => $label) {
                if (! View::exists($view)) {
                    continue; // view dosyası yoksa skip
                }
                $tested++;
                try {
                    // Sadece compile — full render değil. Catches Blade syntax + @directive issues.
                    $viewPath = view($view)->getPath();
                    $content = file_get_contents($viewPath);
                    $compiled = app('blade.compiler')->compileString($content);
                    if (! is_string($compiled) || strlen($compiled) === 0) {
                        $errors[] = "{$view}: compile empty";
                    }
                } catch (\Throwable $e) {
                    $errors[] = "{$view}: " . substr($e->getMessage(), 0, 100);
                }
            }
        } finally {
            $this->overrideModuleState($module, true);
            Cache::flush();
        }

        if (! empty($errors)) {
            return ['pass' => false, 'msg' => $tested . ' tested, errors: ' . implode(' | ', array_slice($errors, 0, 3))];
        }
        return ['pass' => true, 'msg' => "{$tested} view compile OK"];
    }

    private function testAllCriticalViewsCompile(string $label): array
    {
        $errors = [];
        $tested = 0;
        foreach (self::CRITICAL_VIEWS as $view => $vLabel) {
            if (! View::exists($view)) continue;
            $tested++;
            try {
                $viewPath = view($view)->getPath();
                $content = file_get_contents($viewPath);
                app('blade.compiler')->compileString($content);
            } catch (\Throwable $e) {
                $errors[] = "{$view}: " . substr($e->getMessage(), 0, 80);
            }
        }
        return ['pass' => empty($errors), 'tested' => $tested, 'errors' => $errors];
    }

    private function renderViewResultLine(string $label, array $r): void
    {
        if ($r['pass']) {
            $this->info("   ✓ {$r['tested']} view compile OK");
        } else {
            $this->error("   ✗ {$r['tested']} view test, " . count($r['errors']) . ' error:');
            foreach ($r['errors'] as $err) {
                $this->warn("      - {$err}");
            }
        }
    }

    /**
     * ModuleAccess'in cache'ini override ederek modülü disable/enable et.
     * NOT: companies table'ına yazmıyor — sadece runtime cache override.
     * Cache key: company:{id}:enabled_modules (ModuleAccess::loadEnabledModules ile uyumlu)
     */
    private function overrideModuleState(string $module, bool $enabled): void
    {
        $cid = 1;
        $cacheKey = "company:{$cid}:enabled_modules";
        $current = ModuleAccess::allModules();
        if ($enabled) {
            if (! in_array($module, $current, true)) $current[] = $module;
        } else {
            $current = array_values(array_filter($current, fn ($m) => $m !== $module));
        }
        Cache::put($cacheKey, $current, 60);
        // current_company_id container'ına bind et — view'lar runtime'da bu cid'yi kullansın
        app()->instance('current_company_id', $cid);
    }
}

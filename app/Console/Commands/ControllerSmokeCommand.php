<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Finder\Finder;

/**
 * CONTROLLER SMOKE TEST
 *
 * Tum controller siniflari instantiate edilebilir mi dogrulanir. Laravel 12
 * gecisinde __construct() icindeki $this->middleware(...) calismadi ve 5
 * controller dispatch'te 500 verdi (ae4e35b commit). Bu smoke test bu kategori
 * regression'lari yakalar.
 *
 * Kontroller:
 *  1. Reflection ile constructor parametreleri service container'dan resolve
 *     edilebilir mi
 *  2. Constructor calistirken Throwable firlatiyor mu (eski middleware pattern,
 *     yanlis tip-hint, eksik service binding, vb.)
 *  3. Aksiyon method'lari mevcut mu (sadece reflection check, dispatch yok)
 *
 * Kullanim:
 *   php artisan smoke:controllers
 *   php artisan smoke:controllers --filter=Manager
 *   php artisan smoke:controllers --verbose-errors
 *
 * Exit: 0 hepsi PASS, 1 en az bir FAIL.
 */
class ControllerSmokeCommand extends Command
{
    protected $signature = 'smoke:controllers
        {--filter= : Sadece namespace icinde X olanlari test et (orn. Manager)}
        {--verbose-errors : Hata detaylarini uzun goster}';

    protected $description = 'Tum controller siniflari instantiate edilebilir mi dogrula (Laravel 12 __construct regression koruma)';

    public function handle(): int
    {
        $base = app_path('Http/Controllers');
        $filter = (string) $this->option('filter');

        $finder = new Finder();
        $finder->files()->in($base)->name('*Controller.php');

        $rows   = [];
        $passed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($finder as $file) {
            $relative = ltrim(str_replace([$base, '\\'], ['', '/'], $file->getRealPath()), '/');
            $class    = $this->pathToClass($relative);

            if ($filter !== '' && stripos($class, $filter) === false) {
                continue;
            }

            $result = $this->testController($class);

            if ($result['status'] === 'SKIP') {
                $skipped++;
                continue;
            }

            $rows[] = [
                $result['status'] === 'PASS' ? '<info>✓ PASS</info>' : '<error>✗ FAIL</error>',
                $this->shortClass($class),
                $result['actions_count'],
                $result['error'] ? $this->truncateError($result['error']) : '—',
            ];

            if ($result['status'] === 'PASS') {
                $passed++;
            } else {
                $failed++;
                if ($this->option('verbose-errors')) {
                    $this->error("\n--- FAIL: {$class} ---");
                    $this->line($result['error']);
                    $this->line("Stack: " . ($result['trace'] ?? ''));
                }
            }
        }

        $table = new Table($this->output);
        $table->setHeaders(['Status', 'Controller', 'Actions', 'Error']);
        $table->setRows($rows);
        $table->render();

        $total = $passed + $failed;
        $this->line('');
        $this->info("=== SUMMARY ===");
        $this->line("Tested:  {$total} ({$passed} pass, {$failed} fail)");
        if ($skipped > 0) {
            $this->line("Skipped: {$skipped} (filter eslesmedi veya abstract/trait)");
        }

        if ($failed > 0) {
            $this->error("\n{$failed} controller FAIL. Detay icin: --verbose-errors");
            return self::FAILURE;
        }

        $this->info("\nTum controller'lar instantiate edilebilir. Laravel 12 regression yok.");
        return self::SUCCESS;
    }

    private function testController(string $class): array
    {
        if (!class_exists($class)) {
            return ['status' => 'SKIP', 'actions_count' => 0, 'error' => 'class_exists false'];
        }

        try {
            $ref = new ReflectionClass($class);
        } catch (\Throwable $e) {
            return ['status' => 'FAIL', 'actions_count' => 0, 'error' => 'Reflection: ' . $e->getMessage()];
        }

        if ($ref->isAbstract() || $ref->isInterface() || $ref->isTrait()) {
            return ['status' => 'SKIP', 'actions_count' => 0, 'error' => 'abstract/interface/trait'];
        }

        // Action method'lari say (public, non-magic, declared in class)
        $actionsCount = 0;
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
            if ($m->isConstructor() || $m->isDestructor()) {
                continue;
            }
            if (strpos($m->getName(), '__') === 0) {
                continue;
            }
            if ($m->getDeclaringClass()->getName() !== $class) {
                continue;
            }
            $actionsCount++;
        }

        try {
            // Container'a instantiate ettir (DI resolve dahil)
            app()->make($class);
        } catch (\Throwable $e) {
            return [
                'status'        => 'FAIL',
                'actions_count' => $actionsCount,
                'error'         => get_class($e) . ': ' . $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ];
        }

        return [
            'status'        => 'PASS',
            'actions_count' => $actionsCount,
            'error'         => null,
        ];
    }

    private function pathToClass(string $relative): string
    {
        $withoutPhp = preg_replace('/\.php$/', '', $relative);
        return 'App\\Http\\Controllers\\' . str_replace('/', '\\', $withoutPhp);
    }

    private function shortClass(string $class): string
    {
        return str_replace('App\\Http\\Controllers\\', '', $class);
    }

    private function truncateError(?string $error, int $max = 70): string
    {
        if (!$error) return '—';
        $error = preg_replace('/\s+/', ' ', $error);
        return mb_strlen($error) > $max ? mb_substr($error, 0, $max - 3) . '...' : $error;
    }
}

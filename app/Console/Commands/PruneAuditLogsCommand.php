<?php

namespace App\Console\Commands;

use App\Models\PlatformAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * GDPR retention — platform_audit_logs tablosunu temizle.
 *
 * Varsayilan: 365+ gun (1 yil) onceki kayitlari sil.
 *
 *   php artisan audit:prune                  → 365 gun
 *   php artisan audit:prune --days=180       → 180 gun
 *   php artisan audit:prune --dry            → kuru kosu (silmez)
 *
 * Cron: monthlyOn(1, '04:00') — her ayin 1'inde 04:00.
 */
class PruneAuditLogsCommand extends Command
{
    protected $signature = 'audit:prune
                            {--days=365 : Retention suresi (gun)}
                            {--dry : Silmeden sadece sayisini goster}';

    protected $description = 'Platform audit log tablosunu GDPR retention politikasina gore temizle';

    public function handle(): int
    {
        if (!Schema::hasTable('platform_audit_logs')) {
            $this->warn('platform_audit_logs tablosu yok — atlandi.');
            return self::SUCCESS;
        }

        $days = max(1, (int) $this->option('days'));
        $dry  = (bool) $this->option('dry');

        $cutoff = now()->subDays($days);

        $query = PlatformAuditLog::query()->where('created_at', '<', $cutoff);
        $count = (int) $query->count();

        $this->line("Cutoff: <fg=cyan>{$cutoff->toDateTimeString()}</> ({$days} gun)");
        $this->line("Silinecek: <fg=yellow>{$count}</> kayit");

        if ($count === 0) {
            $this->info('Silinecek kayit yok.');
            return self::SUCCESS;
        }

        if ($dry) {
            $this->comment('--dry — silme yapilmadi.');
            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("OK — {$deleted} kayit silindi.");

        return self::SUCCESS;
    }
}

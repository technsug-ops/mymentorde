<?php

namespace App\Console\Commands;

use App\Models\DataRetentionPolicy;
use App\Models\GuestApplication;
use App\Models\User;
use App\Services\AnonymizationService;
use App\Services\EventLogService;
use Illuminate\Console\Command;

/**
 * GDPR Veri Saklama Politikası Uygulayıcı
 *
 * Her gece çalışır. data_retention_policies tablosundaki kurallara göre
 * süresi dolmuş kayıtları anonimleştirir.
 *
 * Kullanım:
 *   php artisan gdpr:enforce-retention
 *   php artisan gdpr:enforce-retention --dry-run   (değişiklik yapmadan raporlar)
 */
class EnforceDataRetentionCommand extends Command
{
    protected $signature = 'gdpr:enforce-retention {--dry-run : Değişiklik yapmadan kaç kayıt etkileneceğini göster}';
    protected $description = 'GDPR veri saklama politikalarını uygular — süresi dolmuş kayıtları anonimleştirir.';

    public function __construct(
        private readonly AnonymizationService $anonymizer,
        private readonly EventLogService $eventLog,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('[DRY-RUN] Gerçek değişiklik yapılmayacak.');
        }

        $policies = DataRetentionPolicy::query()->where('is_active', true)->get();

        if ($policies->isEmpty()) {
            $this->info('Aktif veri saklama politikası bulunamadı.');
            return self::SUCCESS;
        }

        foreach ($policies as $policy) {
            $this->processPolicy($policy, $isDryRun);
        }

        return self::SUCCESS;
    }

    private function processPolicy(DataRetentionPolicy $policy, bool $isDryRun): void
    {
        $cutoff = now()->subDays($policy->anonymize_after_days);
        $this->info("Politika: [{$policy->entity_type}] — {$policy->anonymize_after_days} gün ({$cutoff->toDateString()}) öncesi işleniyor.");

        match ($policy->entity_type) {
            'guest_application' => $this->processGuestApplications($cutoff, $isDryRun),
            'user'              => $this->processUsers($cutoff, $isDryRun),
            default             => $this->warn("  Bilinmeyen entity_type: {$policy->entity_type} — atlanıyor."),
        };
    }

    private function processGuestApplications(\Carbon\Carbon $cutoff, bool $isDryRun): void
    {
        // Arşivlenmiş ve süresi dolmuş başvurular
        $query = GuestApplication::query()
            ->where('is_archived', true)
            ->where('archived_at', '<=', $cutoff)
            ->whereNull('deleted_at');

        $count = $query->count();
        $this->line("  Etkilenecek guest_application: {$count}");

        if ($isDryRun || $count === 0) {
            return;
        }

        $query->each(function (GuestApplication $app): void {
            $this->anonymizer->anonymizeGuestApplication($app);
            $this->eventLog->log(
                'gdpr.retention_anonymized',
                'guest_application',
                (string) $app->id,
                'Veri saklama politikası gereği guest başvurusu anonimleştirildi.',
                ['policy' => 'guest_application'],
                'system',
            );
        });

        $this->info("  ✓ {$count} guest_application anonimleştirildi.");
    }

    private function processUsers(\Carbon\Carbon $cutoff, bool $isDryRun): void
    {
        // GDPR silme talebi işlenmiş ve süresi dolmuş kullanıcılar
        // (soft-deleted ve üzerinden retention süresi geçmiş)
        $query = User::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->where('email', 'not like', 'anon-user-%@deleted.local'); // zaten anonimleştirilmişleri atla

        $count = $query->count();
        $this->line("  Etkilenecek user: {$count}");

        if ($isDryRun || $count === 0) {
            return;
        }

        $query->each(function (User $user): void {
            $this->anonymizer->anonymizeUser($user);
            $this->eventLog->log(
                'gdpr.retention_anonymized',
                'user',
                (string) $user->id,
                'Veri saklama politikası gereği kullanıcı kaydı anonimleştirildi.',
                ['policy' => 'user'],
                'system',
            );
        });

        $this->info("  ✓ {$count} kullanıcı anonimleştirildi.");
    }
}

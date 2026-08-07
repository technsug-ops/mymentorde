<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant dönüşümü hazırlık raporu.
 *
 * ── NE İÇİN ─────────────────────────────────────────────────────────────
 * Bir modele `BelongsToCompany` eklemek, okumayı `company_id`'ye göre
 * filtrelemeye başlar. `company_id` boş kalmış satır varsa o kayıtlar
 * ekranlardan sessizce kaybolur.
 *
 * Bu komut, trait eklemeden ÖNCE her tabloda kaç satırın sahipsiz kaldığını
 * söyler. Sayı sıfır değilse o modele trait EKLENMEZ — önce geri-doldurma
 * (bkz. 2026_08_08_090000_backfill_company_id_for_tenant_conversion)
 * çalıştırılır, sonra bu rapor tekrar alınır.
 *
 *     php artisan tenant:scope-report
 *
 * Çıkış kodu: sahipsiz satır varsa 1 — dağıtım betiğinde kapı olarak
 * kullanılabilir.
 */
class TenantScopeReportCommand extends Command
{
    protected $signature = 'tenant:scope-report {--table= : Yalnızca bu tabloyu raporla}';

    protected $description = 'company_id boş kalmış satırları tablo tablo raporlar (tenant dönüşümü öncesi kontrol)';

    /**
     * Dönüşüm sırası bekleyen tablolar — bekçi testindeki listeyle aynı iş.
     *
     * @var list<string>
     */
    private const PENDING_TABLES = [
        'student_accommodations', 'student_appointments', 'student_checklists',
        'student_feedback', 'student_institution_documents', 'student_language_courses',
        'student_material_reads', 'student_payments', 'student_shipments',
        'student_university_applications', 'student_visa_applications',
        'dm_threads', 'conversations', 'notification_dispatches', 'notification_preferences',
        'guest_feedback', 'audit_trails', 'business_contracts', 'business_contract_templates',
        'consent_records', 'digital_asset_activity_log', 'marketing_teams', 'staff_kpi_targets',
        'company_bulletins', 'company_finance_entries', 'payout_settings',
        'ab_tests', 'automation_workflows', 'promo_popups', 'promo_code_redemptions',
        'scheduled_notifications', 'manager_alert_rules', 'manager_reports',
        'manager_scheduled_reports', 'manager_performance_targets',
        'senior_performance_targets', 'senior_response_templates',
        'ai_labs_settings', 'ai_labs_response_cache', 'document_builder_templates',
        'data_retention_policies', 'ip_access_rules', 'task_templates', 'webhook_logs',
    ];

    public function handle(): int
    {
        $only = $this->option('table');
        $tables = $only ? [$only] : self::PENDING_TABLES;

        $rows = [];
        $totalOrphans = 0;
        $skipped = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
                $skipped[] = $table;
                continue;
            }

            $total = (int) DB::table($table)->count();

            $orphans = (int) DB::table($table)
                ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', 0))
                ->count();

            $totalOrphans += $orphans;

            $rows[] = [
                $table,
                number_format($total, 0, ',', '.'),
                $orphans > 0 ? number_format($orphans, 0, ',', '.') : '—',
                $orphans === 0 ? 'HAZIR' : 'BEKLE',
            ];
        }

        // Sorunlu olanlar üstte — uzun listede gözden kaçmasın.
        usort($rows, fn ($a, $b) => ($a[3] === 'BEKLE' ? 0 : 1) <=> ($b[3] === 'BEKLE' ? 0 : 1));

        $this->table(['Tablo', 'Satır', 'Sahipsiz', 'Durum'], $rows);

        if ($skipped !== []) {
            $this->line('Atlandı (tablo ya da kolon yok): ' . implode(', ', $skipped));
        }

        if ($totalOrphans === 0) {
            $this->info('Sahipsiz satır yok — bu tablolara BelongsToCompany eklenebilir.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error(sprintf(
            '%s sahipsiz satır var. Trait EKLEMEYİN — eklenirse bu kayıtlar ekranlardan kaybolur.',
            number_format($totalOrphans, 0, ',', '.')
        ));
        $this->line('Önce: php artisan migrate --force   (geri-doldurma migration\'ı)');
        $this->line('Sonra bu raporu tekrar alın.');

        return self::FAILURE;
    }
}

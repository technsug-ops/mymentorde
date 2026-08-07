<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant dönüşümü hazırlık ölçümü — TEK KAYNAK.
 *
 * ── NE İÇİN ─────────────────────────────────────────────────────────────
 * Bir modele `BelongsToCompany` eklemek, okumayı `company_id`'ye göre
 * filtrelemeye başlar. Sahibi belli olmayan satırlar o anda ekranlardan
 * SESSİZCE kaybolur — hata bile vermeden.
 *
 * Bu sınıf, trait eklemeden ÖNCE her tabloda kaç satırın sahipsiz kaldığını
 * söyler. Hem konsol komutu (`tenant:scope-report`) hem panel ekranı
 * (`/platform/tenant-scope`) bunu kullanır; canlıda SSH olmadığı için asıl
 * ölçüm panelden alınıyor.
 *
 * ── SAHİPSİZ ≠ FABRİKA ──────────────────────────────────────────────────
 * `company_id = 0` bu projede sahipsizlik DEĞİL, bilinçli "fabrika şablonu"
 * işaretidir: form tanımı ve hizmet kataloğu tam olarak böyle çalışır
 * (firmanın kendi satırı → üst firma → fabrika). İkisini aynı kefeye koymak
 * yanlış alarm üretirdi.
 *
 *   NULL → sahibi BİLİNMİYOR. Gerçek risk; trait eklenmeden doldurulmalı.
 *   0    → fabrika satırı. Kapsam yine gizler, ama modelin miras yolu varsa
 *          okuma oradan geçer. Bu yüzden ayrı sayılıyor ve tablo listesinde
 *          açıkça beyan ediliyor (FACTORY_TABLES).
 */
final class TenantScopeReport
{
    /**
     * Dönüşüm sırası bekleyen tablolar — bekçi testindeki listeyle aynı iş.
     *
     * @var list<string>
     */
    public const PENDING_TABLES = [
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

    /**
     * `company_id = 0` satırı BEKLENEN tablolar — fabrika şablonu tutuyorlar.
     *
     * Buraya bir tablo eklemek "kapsam bunları gizleyecek, okuma miras
     * yolundan geçiyor" beyanıdır. Beyan edilmemiş bir tabloda 0 görülürse
     * rapor onu sahipsiz sayar — sessiz kaybolma böyle yakalanır.
     *
     * @var list<string>
     */
    public const FACTORY_TABLES = [
        'business_contract_templates',
        'senior_response_templates',
        'automation_workflows',
        'data_retention_policies',
        'document_builder_templates',
    ];

    public const STATUS_READY   = 'HAZIR';
    public const STATUS_BLOCKED = 'BEKLE';
    public const STATUS_FACTORY = 'FABRIKA';

    /**
     * @param  list<string>|null  $tables  null → PENDING_TABLES
     * @return array{
     *     rows: list<array{table:string,total:int,unowned:int,factory:int,status:string}>,
     *     unowned: int,
     *     factory: int,
     *     skipped: list<string>
     * }
     */
    public function run(?array $tables = null): array
    {
        $rows = [];
        $skipped = [];
        $unownedTotal = 0;
        $factoryTotal = 0;

        foreach ($tables ?? self::PENDING_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
                $skipped[] = $table;
                continue;
            }

            $total   = (int) DB::table($table)->count();
            $nulls   = (int) DB::table($table)->whereNull('company_id')->count();
            $zeros   = (int) DB::table($table)->where('company_id', 0)->count();

            $declaredFactory = in_array($table, self::FACTORY_TABLES, true);

            // Beyan edilmemiş tabloda 0, NULL kadar tehlikeli: kapsam onu da gizler
            // ve kimse miras yolunun kurulduğunu söylememiş.
            $unowned = $declaredFactory ? $nulls : $nulls + $zeros;
            $factory = $declaredFactory ? $zeros : 0;

            $unownedTotal += $unowned;
            $factoryTotal += $factory;

            $rows[] = [
                'table'   => $table,
                'total'   => $total,
                'unowned' => $unowned,
                'factory' => $factory,
                'status'  => match (true) {
                    $unowned > 0 => self::STATUS_BLOCKED,
                    $factory > 0 => self::STATUS_FACTORY,
                    default      => self::STATUS_READY,
                },
            ];
        }

        // Sorunlu olanlar üstte — uzun listede gözden kaçmasın.
        usort($rows, function (array $a, array $b): int {
            $rank = static fn (array $r): int => match ($r['status']) {
                self::STATUS_BLOCKED => 0,
                self::STATUS_FACTORY => 1,
                default              => 2,
            };

            return [$rank($a), $a['table']] <=> [$rank($b), $b['table']];
        });

        return [
            'rows'    => $rows,
            'unowned' => $unownedTotal,
            'factory' => $factoryTotal,
            'skipped' => $skipped,
        ];
    }
}

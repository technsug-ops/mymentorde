<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
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
 *   0    → fabrika satırı. Modelin kapsamı onları bilerek görünür tutuyor.
 *          Bu yüzden ayrı sayılıyor — ama yalnızca model AÇIKÇA beyan
 *          etmişse (`tenantIncludesFactoryRows()`). Beyan edilmemiş tabloda
 *          0 görülürse rapor onu sahipsiz sayar.
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
        $factoryTables = $this->declaredFactoryTables();

        foreach ($tables ?? self::PENDING_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
                $skipped[] = $table;
                continue;
            }

            $total   = (int) DB::table($table)->count();
            $nulls   = (int) DB::table($table)->whereNull('company_id')->count();
            $zeros   = (int) DB::table($table)->where('company_id', 0)->count();

            $declaredFactory = in_array($table, $factoryTables, true);

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

    /**
     * Fabrika satırı tutan tablolar — MODELLERDEN türetiliyor.
     *
     * Ayrı bir liste tutulsaydı iki gerçek olurdu: modelin kapsamı 0'ı
     * gösterirken raporun listesi güncellenmemiş olabilirdi (ya da tersi).
     * Tek beyan noktası modelin `tenantIncludesFactoryRows()` metodudur.
     *
     * @return list<string>
     */
    private function declaredFactoryTables(): array
    {
        $tables = [];

        foreach (glob(app_path('Models/*.php')) ?: [] as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');

            if (! class_exists($class) || ! method_exists($class, 'tenantIncludesFactoryRows')) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            if ($class::tenantIncludesFactoryRows()) {
                $tables[] = (new $class())->getTable();
            }
        }

        return $tables;
    }
}

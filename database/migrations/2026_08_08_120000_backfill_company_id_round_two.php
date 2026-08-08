<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Geri-doldurma — 2. tur.
 *
 * 1. tur (2026_08_08_090000) her kaydın firmasını ÜST KAYDINDAN türetti ama
 * 5 tabloda satır sahipsiz kaldı. Ölçüm o satırların ne olduğunu gösterdi ve
 * hepsinin cevabı aynı değil:
 *
 *   • Bazılarının sahibi BAŞKA bir kolonda duruyordu (senior_response_
 *     templates.owner_user_id, automation_workflows.created_by,
 *     notification_dispatches.guest_id / source_id / user_id). 1. tur yalnızca
 *     tek bir üst kayda bakıyordu, bu zincirleri denemiyordu.
 *
 *   • Geri kalanların sahibi HİÇ olmamış: tohumlanmış şablon satırları.
 *     Onlar için doğru cevap "varsayılan firmaya yaz" DEĞİL — o, veriyi
 *     rastgele bir firmanın malı yapardı. Bu projede yerleşik cevap
 *     `company_id = 0` + miras zinciri: form tanımı ve hizmet kataloğu tam
 *     olarak böyle çalışıyor.
 *
 * ⚠ 0'a çekilen tablolar modelde `tenantIncludesFactoryRows()` ile AÇIKÇA
 * beyan edilmiş olmalı; beyan yoksa kapsam o satırları gizler ve kayıt
 * ekrandan sessizce kaybolur. Beyanı olmayan tabloya burada dokunulmuyor.
 *
 * ── GERİ ALMA ───────────────────────────────────────────────────────────
 * down() bilerek boş: doldurulan değer DOĞRU değerdir. Migration tekrar
 * çalışsa da zararsız — yalnızca hâlâ boş olan satırlara dokunuyor.
 */
return new class extends Migration
{
    /**
     * Sahiplik zincirleri: sırayla denenir, ilk dolduran kazanır.
     *
     * tablo => list<[kendi kolonu, üst tablo, üst tablodaki eşleşme kolonu]>
     *
     * @var array<string, list<array{0:string,1:string,2:string}>>
     */
    private const CHAINS = [
        // Senior'ın kendi şablonu → onu yazan kullanıcının firması.
        // owner_user_id boşsa şablon global; aşağıda fabrikaya çekiliyor.
        'senior_response_templates' => [
            ['owner_user_id', 'users', 'id'],
        ],

        // Otomasyonu kuran kullanıcının firması.
        'automation_workflows' => [
            ['created_by', 'users', 'id'],
            ['approved_by', 'users', 'id'],
        ],

        // Bildirim: önce konusu (aday), sonra alıcısı.
        // ⚠ Sıra önemli — alıcı MentorDE personeli olabilir; bildirim onun
        // kutusuna düşerse partner kendi adayına giden maili göremez.
        'notification_dispatches' => [
            ['guest_id', 'guest_applications', 'id'],
            ['user_id', 'users', 'id'],
        ],
    ];

    /**
     * Sahibi hiç olmayan satırların fabrika şablonu sayılacağı tablolar.
     *
     * Model tarafında `tenantIncludesFactoryRows()` beyanı ŞART — burada
     * ayrıca doğrulanıyor.
     *
     * @var array<string, class-string>
     */
    private const FACTORY_TABLES = [
        'senior_response_templates'   => \App\Models\SeniorResponseTemplate::class,
        'data_retention_policies'     => \App\Models\DataRetentionPolicy::class,
        'business_contract_templates' => \App\Models\BusinessContractTemplate::class,
        'document_builder_templates'  => \App\Models\DocumentBuilderTemplate::class,
    ];

    /**
     * Sahibi belirlenemeyen satırların ANA FİRMAYA yazılacağı tablolar.
     *
     * ⚠ Bu, 1. turun bilinçle kaçındığı "varsayılan firmaya toptan yaz"
     * davranışı DEĞİL. Burada tek bir tablo var ve gerekçesi kendine özgü:
     * otomasyon akışı şablon değil, durumu ve kayıtlı adayları olan işleyen
     * bir nesne. Paylaşımlı yapılamaz (bir firmanın "aktifleştir"i diğerlerini
     * etkilerdi) ve sahipsiz de bırakılamaz (ekrandan kaybolur). Tohumlanmış
     * akışları kuran taraf operasyonu yürüten ana firmadır.
     *
     * @var list<string>
     */
    private const PRIMARY_OWNED_TABLES = [
        'automation_workflows',
    ];

    public function up(): void
    {
        foreach (self::CHAINS as $table => $chains) {
            foreach ($chains as [$ownColumn, $parentTable, $parentColumn]) {
                if (! $this->usable($table, $ownColumn, $parentTable, $parentColumn)) {
                    continue;
                }

                // ⚠ EXISTS ŞART — gerekçe 090000'deki ile aynı: eşleşme yoksa
                // alt sorgu NULL döner, NOT NULL kolonda migration patlar.
                DB::statement(sprintf(
                    'UPDATE %1$s SET company_id = ('
                    . ' SELECT p.company_id FROM %2$s p'
                    . ' WHERE p.%3$s = %1$s.%4$s AND p.company_id IS NOT NULL AND p.company_id > 0'
                    . ' LIMIT 1'
                    . ') WHERE (company_id IS NULL OR company_id = 0) AND %4$s IS NOT NULL'
                    . ' AND EXISTS ('
                    . ' SELECT 1 FROM %2$s q'
                    . ' WHERE q.%3$s = %1$s.%4$s AND q.company_id IS NOT NULL AND q.company_id > 0'
                    . ')',
                    $table,
                    $parentTable,
                    $parentColumn,
                    $ownColumn
                ));
            }
        }

        $this->backfillPolymorphicDispatches();

        foreach (self::FACTORY_TABLES as $table => $model) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            // Beyan yoksa dokunma: 0'a çekilen satır kapsam tarafından
            // gizlenir ve ekrandan kaybolur. Sessiz veri kaybındansa
            // raporun "BEKLE" demesi iyidir.
            if (! method_exists($model, 'tenantIncludesFactoryRows') || ! $model::tenantIncludesFactoryRows()) {
                continue;
            }

            DB::table($table)->whereNull('company_id')->update(['company_id' => 0]);
        }

        $this->assignLeftoversToPrimaryCompany();
    }

    /**
     * Ana firma = `companies.code` === config('app.primary_company_code').
     * Bulunamazsa hiçbir şey yapılmıyor: yanlış firmaya bağlanan kayıt,
     * boş kalan kayıttan kötüdür ve rapor zaten "BEKLE" diyecek.
     */
    private function assignLeftoversToPrimaryCompany(): void
    {
        $primaryCode = strtolower(trim((string) config('app.primary_company_code', 'mentorde')));

        if ($primaryCode === '' || ! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'code')) {
            return;
        }

        $primaryId = (int) DB::table('companies')
            ->whereRaw('lower(code) = ?', [$primaryCode])
            ->orderBy('id')
            ->value('id');

        if ($primaryId <= 0) {
            return;
        }

        foreach (self::PRIMARY_OWNED_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            DB::table($table)
                ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', 0))
                ->update(['company_id' => $primaryId]);
        }
    }

    public function down(): void
    {
        // Bilerek boş — bkz. sınıf başlığı.
    }

    /**
     * Bildirimin konusu polimorfik kolonlarda saklanıyor:
     * `source_type = 'guest_application'` + `source_id`.
     *
     * Bunu CHAINS ile ifade edemiyoruz: eşleşme iki kolona bağlı ve
     * `source_id` metin olarak tutuluyor.
     */
    private function backfillPolymorphicDispatches(): void
    {
        if (! $this->usable('notification_dispatches', 'source_id', 'guest_applications', 'id')) {
            return;
        }

        if (! Schema::hasColumn('notification_dispatches', 'source_type')) {
            return;
        }

        // ⚠ EXISTS ŞART — gerekçe yukarıdakiyle aynı.
        DB::statement(
            'UPDATE notification_dispatches SET company_id = ('
            . ' SELECT g.company_id FROM guest_applications g'
            . ' WHERE CAST(g.id AS CHAR) = notification_dispatches.source_id'
            . ' AND g.company_id IS NOT NULL AND g.company_id > 0'
            . ' LIMIT 1'
            . ') WHERE (company_id IS NULL OR company_id = 0)'
            . " AND source_type = 'guest_application' AND source_id IS NOT NULL"
            . ' AND EXISTS ('
            . ' SELECT 1 FROM guest_applications h'
            . ' WHERE CAST(h.id AS CHAR) = notification_dispatches.source_id'
            . ' AND h.company_id IS NOT NULL AND h.company_id > 0'
            . ')'
        );
    }

    /** Tablo ve kolonların hepsi var mı? (eski kurulumlarda eksik olabilir) */
    private function usable(string $table, string $ownColumn, string $parentTable, string $parentColumn): bool
    {
        return Schema::hasTable($table)
            && Schema::hasTable($parentTable)
            && Schema::hasColumn($table, 'company_id')
            && Schema::hasColumn($table, $ownColumn)
            && Schema::hasColumn($parentTable, 'company_id')
            && Schema::hasColumn($parentTable, $parentColumn);
    }
};

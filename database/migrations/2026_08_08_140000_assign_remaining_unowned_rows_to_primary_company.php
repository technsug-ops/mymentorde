<?php

use App\Support\TenantScopeReport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Geri-doldurma — son süpürme.
 *
 * ── NEDEN ŞİMDİ "ANA FİRMAYA YAZ" DOĞRU ─────────────────────────────────
 * 1. tur bunu bilerek yapmamıştı ve gerekçesi hâlâ geçerliydi: sahibi
 * BAŞKASI OLABİLECEK bir satırı varsayılan firmaya yazmak, yanlış firmaya
 * bağlanmış kayıt üretir — boş kalandan kötüdür, orada gerçek sızıntı olur.
 *
 * Ama koşul değişti. Bugüne kadarki tüm operasyon MentorDE olarak yürütüldü;
 * partner firmalar tarafında **henüz aktif iş yok** (2026-08-08, iş sahibinin
 * beyanı). Yani sahibi türetilemeyen bir satır partnere ait OLAMAZ — partner
 * hiç kayıt üretmedi. Geriye tek aday kalıyor: operasyonu yürüten ana firma.
 *
 * Bu, tahmin değil takvim bilgisi. "Kime ait olabilir?" sorusunun cevap
 * kümesi tek elemanlı.
 *
 * ── SIRA ÖNEMLİ ─────────────────────────────────────────────────────────
 * Bu migration en SONA konumlandı (140000). Önce türetme zincirleri çalışır
 * (090000 + 120000), sahibi gerçekten bilinen satırlar doğru firmaya gider.
 * Burada yalnızca ARTAKALAN dokunulur.
 *
 * ── NEYE DOKUNULMUYOR ───────────────────────────────────────────────────
 * `company_id = 0` (fabrika şablonu) satırlarına ASLA. O 0 bilinçli bir
 * değer: "herkesin miras aldığı şablon". Ana firmaya çekilseydi diğer
 * firmalar merkezî şablonu göremez, sabit kataloğa düşerlerdi.
 * Fabrika beyanı olan tablolar (`tenantIncludesFactoryRows()`) tümüyle
 * atlanıyor.
 *
 * ── GERİ ALMA ───────────────────────────────────────────────────────────
 * down() bilerek boş: doldurulan değer doğru değerdir, geri almak satırları
 * yeniden görünmez yapmak olurdu.
 */
return new class extends Migration
{
    public function up(): void
    {
        $primaryId = $this->primaryCompanyId();

        if ($primaryId === null) {
            // Ana firma bulunamadı. Sessizce hiçbir şey yapma — yanlış firmaya
            // yazmaktansa rapor "BEKLE" desin.
            return;
        }

        $factoryTables = $this->factoryTables();

        foreach (TenantScopeReport::PENDING_TABLES as $table) {
            if (in_array($table, $factoryTables, true)) {
                continue;
            }

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            // NULL ve 0'ın İKİSİ de süpürülüyor.
            //
            // ⚠ Bu tablolarda sahipsizlik her zaman NULL ile gösterilmiyor:
            // 16 tabloda `company_id` NOT NULL, çoğunda DEFAULT 0. Orada
            // "sahibi belirlenmemiş" satırın değeri 0'dır. Yalnızca NULL
            // süpürülseydi o satırlar sahipsiz kalır, izolasyon açıkken
            // ekranlardan kaybolmaya devam ederdi.
            //
            // 0'ın "fabrika şablonu" anlamı YALNIZCA beyan eden modellerde
            // geçerli; o tablolar yukarıda tümüyle atlandı. Kapsam raporu da
            // tam olarak bu ayrımı yapıyor (TenantScopeReport).
            DB::table($table)
                ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', 0))
                ->update(['company_id' => $primaryId]);
        }
    }

    public function down(): void
    {
        // Bilerek boş — bkz. sınıf başlığı.
    }

    /** Ana firma: `companies.code` === config('app.primary_company_code'). */
    private function primaryCompanyId(): ?int
    {
        $primaryCode = strtolower(trim((string) config('app.primary_company_code', 'mentorde')));

        if ($primaryCode === '' || ! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'code')) {
            return null;
        }

        $id = (int) DB::table('companies')
            ->whereRaw('lower(code) = ?', [$primaryCode])
            ->orderBy('id')
            ->value('id');

        return $id > 0 ? $id : null;
    }

    /**
     * Fabrika satırı tutan tablolar — modellerden türetiliyor, ayrı liste yok.
     *
     * @return list<string>
     */
    private function factoryTables(): array
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
};

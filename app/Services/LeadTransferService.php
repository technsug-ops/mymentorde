<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use App\Support\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Adayı (aday öğrenciyi) başka bir firmaya devret.
 *
 * NEDEN GEREKLİ: normalde firma kendi başvuru linkini (/apply/{slug}) öğrencisine
 * verir ve kayıt doğrudan o firmaya düşer. Ama link verilemediği durumlar olur —
 * öğrenci düz yourgermanuni.com/apply'dan gelir ve kayıt B2C havuzuna düşer.
 * O zaman platform sahibi adayı elle doğru firmaya taşır.
 *
 * ── YARIM DEVİR EN KÖTÜSÜ ───────────────────────────────────────────────────
 * Bir adayın verisi tek satır değil: rıza kaydı, lead kaynağı, bildirim tercihi,
 * destek talebi, ödeme talebi, randevu... Bunlardan biri eski şirkette kalırsa
 * tenant filtresi arkasında görünmez olur — ne eski ne yeni firma erişebilir.
 * Bu yüzden bağlı tablolar ŞEMADAN KEŞFEDİLİR (sabit liste zamanla eskir) ve
 * hepsi tek transaction içinde taşınır.
 *
 * ── SINIR ───────────────────────────────────────────────────────────────────
 * Yalnızca DÖNÜŞMEMİŞ adaylar. Öğrenciye dönmüş kayıtta sözleşme, ödeme, belge,
 * danışman ataması ve arşiv zinciri devreye girer; o çok daha büyük bir iş ve
 * sessizce yarım yapılırsa para/belge kaybına yol açar. Bilinçli olarak reddedilir.
 */
class LeadTransferService
{
    /**
     * Adayla ilişkili satırı işaret eden kolon adları.
     *
     * `guest_id` string kolondur ve aday başvurusunun ID'sini tutar
     * (bkz. WorkflowEngineService: 'guest_id' => (string) $enrollment->guest_application_id).
     */
    private const LINK_COLUMNS = ['guest_application_id', 'application_id', 'guest_id'];

    private const TABLE_MAP_CACHE_KEY = 'lead_transfer:linked_tables';

    /**
     * @return array{company_from:int,company_to:int,tables:array<string,int>,senior_cleared:bool}
     *
     * @throws RuntimeException devredilemez durumlarda
     */
    public function transfer(GuestApplication $lead, Company $target): array
    {
        $sourceCompanyId = (int) $lead->company_id;
        $targetCompanyId = (int) $target->id;

        $this->assertTransferable($lead, $target, $sourceCompanyId, $targetCompanyId);

        $affected = [];
        $seniorCleared = false;

        DB::transaction(function () use ($lead, $targetCompanyId, &$affected, &$seniorCleared): void {
            // 1) Adayın kendi satırı
            GuestApplication::withoutGlobalScope('company')
                ->where('id', $lead->id)
                ->update(['company_id' => $targetCompanyId]);
            $affected['guest_applications'] = 1;

            // 2) Öğrencinin portal hesabı — yoksa öğrenci giriş yaptığında
            //    eski firmanın bağlamına düşerdi.
            if ($lead->guest_user_id) {
                $affected['users'] = User::withoutGlobalScope('company')
                    ->where('id', $lead->guest_user_id)
                    ->update(['company_id' => $targetCompanyId]);
            }

            // 3) Bağlı her tablo
            foreach ($this->linkedTables() as $table => $column) {
                // ID hem int hem string kolonlarda aranır: `guest_id` string,
                // `guest_application_id` int. Tek sorguda ikisini de yakala.
                $count = DB::table($table)
                    ->whereIn($column, [(int) $lead->id, (string) $lead->id])
                    ->update(['company_id' => $targetCompanyId]);

                if ($count > 0) {
                    $affected[$table] = $count;
                }
            }

            // 4) Atanmış danışman eski firmanın personeli — yeni firmada
            //    görünmez. Bayat atama bırakmak sessiz hata olurdu.
            if (!empty($lead->assigned_senior_email)) {
                GuestApplication::withoutGlobalScope('company')
                    ->where('id', $lead->id)
                    ->update(['assigned_senior_email' => null]);
                $seniorCleared = true;
            }
        });

        $this->flushCaches($sourceCompanyId, $targetCompanyId);

        return [
            'company_from'   => $sourceCompanyId,
            'company_to'     => $targetCompanyId,
            'tables'         => $affected,
            'senior_cleared' => $seniorCleared,
        ];
    }

    /** @throws RuntimeException */
    private function assertTransferable(GuestApplication $lead, Company $target, int $from, int $to): void
    {
        if ($from === $to) {
            throw new RuntimeException('Aday zaten bu firmada.');
        }

        if (!$target->is_active) {
            throw new RuntimeException('Hedef firma pasif — devir yapılamaz.');
        }

        if ($lead->converted_to_student) {
            throw new RuntimeException(
                'Bu aday öğrenciye dönüşmüş. Öğrenci devri sözleşme, ödeme ve belge '
                . 'zincirini de kapsadığı için buradan yapılamaz.'
            );
        }
    }

    /**
     * `company_id` taşıyan ve adaya bağlanabilen tablolar.
     *
     * Şemadan keşfedilir: yeni bir tablo eklendiğinde bu servisi güncellemeyi
     * unutmak = o tablonun satırlarının eski şirkette kalması = görünmez veri.
     *
     * @return array<string,string> tablo => bağlantı kolonu
     */
    private function linkedTables(): array
    {
        return Cache::remember(self::TABLE_MAP_CACHE_KEY, 86400, function (): array {
            $map = [];

            foreach ($this->allTables() as $table) {
                // guest_applications ve users ayrı ele alınıyor (kendi ID'leriyle)
                if (in_array($table, ['guest_applications', 'users'], true)) {
                    continue;
                }

                if (!Schema::hasColumn($table, 'company_id')) {
                    continue;
                }

                foreach (self::LINK_COLUMNS as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $map[$table] = $column;
                        break;
                    }
                }
            }

            return $map;
        });
    }

    /** @return list<string> */
    private function allTables(): array
    {
        $names = Schema::getTableListing();

        return array_values(array_map(
            // Bazı sürücüler "schema.tablo" döndürür — sade adı al.
            static fn (string $name): string => str_contains($name, '.')
                ? substr($name, (int) strrpos($name, '.') + 1)
                : $name,
            $names
        ));
    }

    private function flushCaches(int $from, int $to): void
    {
        Cache::forget('platform:portfolio:lead_totals');
        Cache::forget('platform:portfolio:student_totals');

        foreach ([$from, $to] as $companyId) {
            Brand::flushCache($companyId);
        }
    }

    /** Şema değiştiğinde (migration sonrası) çağrılabilir. */
    public static function flushTableMap(): void
    {
        Cache::forget(self::TABLE_MAP_CACHE_KEY);
    }
}

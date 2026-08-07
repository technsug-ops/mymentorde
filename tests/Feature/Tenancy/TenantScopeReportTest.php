<?php

namespace Tests\Feature\Tenancy;

use App\Support\TenantScopeReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kapsam raporunun asıl işi: izolasyon açılmadan önce hangi satırın
 * ekrandan kaybolacağını söylemek.
 *
 * Kritik ayrım — `company_id = 0` bu projede sahipsizlik DEĞİL, bilinçli
 * fabrika şablonu işareti. Ama bu yalnızca tablo AÇIKÇA beyan edilmişse
 * geçerli; beyan edilmemiş bir tabloda 0 görülürse sessiz kaybolma demektir
 * ve rapor onu sahipsiz saymalı.
 */
class TenantScopeReportTest extends TestCase
{
    use RefreshDatabase;

    /** Beyan edilmiş fabrika tablosu — 0'lar sahipsiz sayılmamalı. */
    private const FACTORY_TABLE = 'business_contract_templates';

    /** Beyan EDİLMEMİŞ tablo — 0 burada sessiz kaybolmadır. */
    private const PLAIN_TABLE = 'task_templates';

    public function test_null_company_id_is_always_reported_as_unowned(): void
    {
        DB::table(self::PLAIN_TABLE)->insert($this->taskTemplateRow(null));

        $row = $this->rowFor(self::PLAIN_TABLE);

        $this->assertSame(1, $row['unowned']);
        $this->assertSame(TenantScopeReport::STATUS_BLOCKED, $row['status']);
    }

    public function test_zero_in_a_declared_factory_table_is_not_unowned(): void
    {
        // Tablo migration'la birlikte fabrika satırı taşıyor olabilir; mutlak
        // sayıya değil FARKA bakıyoruz.
        $before = $this->rowFor(self::FACTORY_TABLE);

        DB::table(self::FACTORY_TABLE)->insert([
            'company_id'    => 0,
            'contract_type' => 'staff',
            'template_code' => 'test-factory',
            'name'          => 'Fabrika şablonu',
            'body_text'     => 'gövde',
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $row = $this->rowFor(self::FACTORY_TABLE);

        $this->assertSame(0, $row['unowned'], 'Fabrika satırı sahipsiz sayıldı — yanlış alarm.');
        $this->assertSame($before['factory'] + 1, $row['factory']);
        $this->assertSame(TenantScopeReport::STATUS_FACTORY, $row['status']);
    }

    /**
     * Beyan edilmemiş tabloda 0, NULL kadar tehlikeli: kapsam onu da gizler
     * ve kimse miras yolunun kurulduğunu söylememiş.
     */
    public function test_zero_in_an_undeclared_table_counts_as_unowned(): void
    {
        DB::table(self::PLAIN_TABLE)->insert($this->taskTemplateRow(0));

        $row = $this->rowFor(self::PLAIN_TABLE);

        $this->assertSame(1, $row['unowned'], 'Beyan edilmemiş tabloda company_id = 0 sessizce kabul edildi.');
        $this->assertSame(0, $row['factory']);
    }

    public function test_owned_rows_are_ready(): void
    {
        DB::table(self::PLAIN_TABLE)->insert($this->taskTemplateRow(1));

        $row = $this->rowFor(self::PLAIN_TABLE);

        $this->assertSame(0, $row['unowned']);
        $this->assertSame(TenantScopeReport::STATUS_READY, $row['status']);
    }

    public function test_missing_table_is_skipped_not_counted(): void
    {
        $result = (new TenantScopeReport())->run(['bu_tablo_yok']);

        $this->assertSame([], $result['rows']);
        $this->assertSame(['bu_tablo_yok'], $result['skipped']);
        $this->assertSame(0, $result['unowned']);
    }

    /** @return array{table:string,total:int,unowned:int,factory:int,status:string} */
    private function rowFor(string $table): array
    {
        $result = (new TenantScopeReport())->run([$table]);

        $this->assertCount(1, $result['rows'], "$table raporlanmadı — kolon ya da tablo eksik olabilir.");

        return $result['rows'][0];
    }

    /** @return array<string,mixed> */
    private function taskTemplateRow(?int $companyId): array
    {
        return [
            'company_id' => $companyId,
            'name'       => 'Test şablonu',
            'department' => 'operations',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

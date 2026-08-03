<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\SystemEventLog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\Support\Jobs\WritesTenantScopedRecordJob;
use Tests\TestCase;

/**
 * Faz 1 — tenant çekirdeği.
 *
 * Doğrulanan davranış:
 *   • Okuma görünür kümeyle sınırlanır (tek şirket → where, çok şirket → whereIn)
 *   • Yazma her zaman TEK şirkete gider
 *   • Bağlam yoksa filtre uygulanmaz (migration/seeder eski davranışı)
 *   • Kuyruk işi, dispatch anındaki şirkete yazar — çalıştığı andaki bağlama DEĞİL
 */
class TenantContextTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    public function test_read_is_limited_to_the_visible_company(): void
    {
        $this->seedLogFor($this->companyA, 'a-kaydi');
        $this->seedLogFor($this->companyB, 'b-kaydi');

        TenantContext::bind($this->companyA->id, [$this->companyA->id]);

        $messages = SystemEventLog::query()->pluck('message')->all();

        $this->assertContains('a-kaydi', $messages);
        $this->assertNotContains('b-kaydi', $messages, 'A bağlamında B şirketinin kaydı görünüyor.');
    }

    public function test_visible_set_can_span_multiple_companies(): void
    {
        $this->seedLogFor($this->companyA, 'a-kaydi');
        $this->seedLogFor($this->companyB, 'b-kaydi');

        // Platform sahibi / çok şirkete atanmış personel senaryosu
        TenantContext::bind($this->companyA->id, [$this->companyA->id, $this->companyB->id]);

        $messages = SystemEventLog::query()->pluck('message')->all();

        $this->assertContains('a-kaydi', $messages);
        $this->assertContains('b-kaydi', $messages);
    }

    public function test_unrestricted_context_sees_everything(): void
    {
        $this->seedLogFor($this->companyA, 'a-kaydi');
        $this->seedLogFor($this->companyB, 'b-kaydi');

        TenantContext::bind($this->companyA->id, null);

        $this->assertCount(2, SystemEventLog::query()->get());
    }

    public function test_write_always_targets_the_single_write_company(): void
    {
        // Okuma kümesi geniş olsa bile yazma tek şirkete gitmeli — belirsizlik olamaz.
        TenantContext::bind($this->companyA->id, [$this->companyA->id, $this->companyB->id]);

        $log = SystemEventLog::create(['event_type' => 'x', 'message' => 'yazma-testi']);

        $this->assertSame((int) $this->companyA->id, (int) $log->company_id);
    }

    public function test_explicit_company_id_is_not_overwritten(): void
    {
        TenantContext::bind($this->companyA->id, [$this->companyA->id]);

        $log = SystemEventLog::withoutGlobalScope('company')->create([
            'company_id' => $this->companyB->id,
            'event_type' => 'x',
            'message' => 'acik-atama',
        ]);

        $this->assertSame((int) $this->companyB->id, (int) $log->company_id);
    }

    public function test_without_context_no_filter_is_applied(): void
    {
        $this->seedLogFor($this->companyA, 'a-kaydi');
        $this->seedLogFor($this->companyB, 'b-kaydi');

        // Migration / seeder durumu: bağlam kurulmamış → eski davranış (filtre yok)
        TenantContext::forget();

        $this->assertCount(2, SystemEventLog::query()->get());
    }

    public function test_run_for_restores_the_previous_context(): void
    {
        TenantContext::bind($this->companyA->id, [$this->companyA->id]);

        $inner = TenantContext::runFor($this->companyB->id, fn (): ?int => TenantContext::writeId());

        $this->assertSame((int) $this->companyB->id, $inner);
        $this->assertSame((int) $this->companyA->id, TenantContext::writeId(), 'runFor sonrası bağlam iade edilmedi.');
    }

    /**
     * KRİTİK REGRESYON — kuyruk kontaminasyonu.
     *
     * KAS'ta cron hakkı olmadığı için DrainQueueOnTraffic kuyruğu web isteğinin
     * içinde boşaltıyor. Bağlam taşınmazsa A firmasının maili, o an panelde gezinen
     * B firmasının bağlamında işlenir ve kayıt B'ye yazılır.
     */
    public function test_queued_job_writes_to_the_company_it_was_dispatched_from(): void
    {
        config(['queue.default' => 'database']);

        // A bağlamında kuyruğa at
        TenantContext::bind($this->companyA->id, [$this->companyA->id]);
        WritesTenantScopedRecordJob::dispatch('kuyruk-kaydi');

        $this->assertSame(1, (int) DB::table('jobs')->count(), 'Job kuyruğa girmedi.');

        // Bağlamı B'ye çevir — "başka firmanın kullanıcısı panelde geziniyor" durumu
        TenantContext::bind($this->companyB->id, [$this->companyB->id]);

        Artisan::call('queue:work', ['--once' => true, '--no-interaction' => true]);

        $log = SystemEventLog::withoutGlobalScope('company')
            ->where('message', 'kuyruk-kaydi')
            ->first();

        $this->assertNotNull($log, 'Job çalışmadı.');
        $this->assertSame(
            (int) $this->companyA->id,
            (int) $log->company_id,
            'Kuyruk işi yanlış şirkete yazdı — tenant kontaminasyonu.'
        );
    }

    private function seedLogFor(Company $company, string $message): void
    {
        SystemEventLog::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'event_type' => 'seed',
            'message' => $message,
        ]);
    }
}

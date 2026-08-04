<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\SystemEventLog;
use App\Models\User;
use App\Services\EventLogService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Partner firma kendi öğrencisinin ANA GELİŞMELERİNİ görebilmeli.
 *
 * Referans: bayinin gördüğü operasyon özeti (DealerLeadController::leadTimeline).
 * Süreci MentorDE yürütse bile firma ilerlemeyi izleyebilmeli.
 *
 * İki mekanizma:
 *   1. Olay akışı, işlemi YAPANIN değil işlemin YAPILDIĞI kaydın şirketine yazılır.
 *   2. Üst firma personeli alt firmanın BAĞLAMINA geçebilir; böylece açtığı
 *      ticket/görev de partnerin kutusuna düşer.
 */
class PartnerProgressVisibilityTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /** Şirket bağlamı değiştirme uç noktası (routes/api.php, v1/config grubu). */
    private const SWITCH_URL = '/api/v1/config/companies/switch';

    /** companyA = MentorDE rolünde üst firma, companyB = partner firma. */
    private function makeHierarchy(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();
    }

    private function partnerLead(): GuestApplication
    {
        return TenantContext::runFor((int) $this->companyB->id, fn (): GuestApplication => GuestApplication::create([
            'tracking_token' => 'tok-' . uniqid(),
            'first_name' => 'Partner',
            'last_name' => 'Ogrencisi',
            'email' => 'partner-ogrenci@example.test',
            'application_type' => 'bachelor',
        ]));
    }

    // ── 1. Olay akışı doğru tarafa yazılır ──────────────────────────────────

    /**
     * MentorDE personeli partnerin öğrencisi üzerinde çalışıyor. Olay
     * PARTNERİN kutusuna yazılmalı — aksi halde firma gelişmeyi göremez.
     */
    public function test_event_about_a_partner_student_belongs_to_the_partner(): void
    {
        $this->makeHierarchy();
        $lead = $this->partnerLead();

        // Aktör MentorDE bağlamında çalışıyor
        TenantContext::bind((int) $this->companyA->id, [(int) $this->companyA->id, (int) $this->companyB->id]);

        app(EventLogService::class)->log(
            'application.stage_changed',
            'guest_application',
            (string) $lead->id,
            'Uni-assist basvurusu gonderildi',
            null,
            'danisman@mentorde.test'
        );

        $event = SystemEventLog::withoutGlobalScope('company')
            ->where('entity_id', (string) $lead->id)
            ->firstOrFail();

        $this->assertSame(
            (int) $this->companyB->id,
            (int) $event->company_id,
            'Gelisme MentorDE kutusuna yazildi — partner firma goremez.'
        );
    }

    public function test_partner_can_see_the_progress_event(): void
    {
        $this->makeHierarchy();
        $lead = $this->partnerLead();

        TenantContext::bind((int) $this->companyA->id, [(int) $this->companyA->id, (int) $this->companyB->id]);

        app(EventLogService::class)->log(
            'application.stage_changed',
            'guest_application',
            (string) $lead->id,
            'Vize randevusu alindi'
        );

        $seenByPartner = TenantContext::runFor(
            (int) $this->companyB->id,
            fn () => SystemEventLog::query()->where('entity_id', (string) $lead->id)->first()
        );

        $this->assertNotNull($seenByPartner, 'Partner firma kendi ogrencisinin gelismesini goremiyor.');
        $this->assertSame('Vize randevusu alindi', $seenByPartner->message);
    }

    /** Üst firma da aynı olayı görür — süreci o yürütüyor. */
    public function test_parent_also_sees_the_event(): void
    {
        $this->makeHierarchy();
        $lead = $this->partnerLead();

        app(EventLogService::class)->log(
            'application.stage_changed',
            'guest_application',
            (string) $lead->id,
            'Kabul mektubu geldi'
        );

        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $previous = TenantContext::snapshot();

        try {
            TenantContext::bind((int) $manager->company_id, $manager->visibleCompanyIds());
            $seen = SystemEventLog::query()->where('entity_id', (string) $lead->id)->first();
        } finally {
            TenantContext::restore($previous);
        }

        $this->assertNotNull($seen);
    }

    /** Konusu olmayan olay eski davranışta kalır: aktörün şirketi. */
    public function test_event_without_a_subject_falls_back_to_the_actor_company(): void
    {
        TenantContext::bind((int) $this->companyA->id, [(int) $this->companyA->id]);

        app(EventLogService::class)->log('system.maintenance', null, null, 'Bakim tamamlandi');

        $event = SystemEventLog::withoutGlobalScope('company')
            ->where('event_type', 'system.maintenance')
            ->firstOrFail();

        $this->assertSame((int) $this->companyA->id, (int) $event->company_id);
    }

    // ── 2. Bağlam değiştirme ────────────────────────────────────────────────

    public function test_parent_staff_can_switch_into_a_child_company_context(): void
    {
        $this->makeHierarchy();
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->actingAs($manager)
            ->postJson(self::SWITCH_URL, ['company_id' => $this->companyB->id])
            ->assertOk()
            ->assertJson(['ok' => true, 'current_company_id' => $this->companyB->id]);
    }

    public function test_partner_staff_cannot_switch_into_the_parent(): void
    {
        $this->makeHierarchy();
        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        // 403 bekleniyor: rota erişilebilir, yetki reddediyor. Geniş aralık kabul
        // etmek rotanın hiç bulunamamasını (404) "güvenli" saymak olurdu.
        $this->actingAs($partnerManager)
            ->postJson(self::SWITCH_URL, ['company_id' => $this->companyA->id])
            ->assertForbidden();

        $this->assertNotSame(
            (int) $this->companyA->id,
            (int) session('current_company_id', 0),
            'Alt firma ust firmanin baglamina gecti — yetki acigi.'
        );
    }

    public function test_student_cannot_switch_company_context(): void
    {
        $this->makeHierarchy();
        $student = $this->userFor($this->companyA, User::ROLE_STUDENT);

        $response = $this->actingAs($student)
            ->postJson(self::SWITCH_URL, ['company_id' => $this->companyB->id]);

        $this->assertContains($response->getStatusCode(), [401, 403, 404, 422]);
    }
}

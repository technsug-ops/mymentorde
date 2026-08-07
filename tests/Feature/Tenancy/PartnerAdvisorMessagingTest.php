<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\ConversationService;
use App\Support\MessagingDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Partner, öğrencisine atanmış danışmanla yazışabilmeli.
 *
 * ── SORUN ───────────────────────────────────────────────────────────────
 * Mesajlaşma tek firmalıydı. Danışmanı ÜST firma atıyor, yani başka
 * şirkette: partner adını ve e-postasını görüyor ama panelden ulaşamıyordu.
 * Kullanıcının istediği "iletişim" maddesinin yarısı eksikti.
 *
 * ── İSTİSNANIN SINIRI ───────────────────────────────────────────────────
 * Firma sınırı kaldırılmadı. Yalnızca BU firmanın öğrencisine atanmış
 * danışman erişilebilir; üst firmanın geri kalanı görünmez. Partner
 * firmalar birbirinden habersiz kalmalı.
 */
class PartnerAdvisorMessagingTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    /** companyA = operasyon (danışmanlar burada), companyB = partner. */
    private function linkPartner(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();
    }

    /** Partnerin öğrencisine companyA'dan bir danışman atar. */
    private function assignAdvisor(string $studentId = 'STU-1'): User
    {
        $advisor = $this->userFor($this->companyA, User::ROLE_SENIOR);

        StudentAssignment::create([
            'company_id'   => $this->companyB->id,
            'student_id'   => $studentId,
            'senior_email' => $advisor->email,
            'is_archived'  => false,
        ]);

        return $advisor;
    }

    // ── Rehber ──────────────────────────────────────────────────────────────

    public function test_assigned_advisor_is_reachable(): void
    {
        $this->linkPartner();
        $advisor = $this->assignAdvisor();

        $ids = MessagingDirectory::reachableOutsideIds((int) $this->companyB->id, User::ROLE_MANAGER);

        $this->assertContains((int) $advisor->id, $ids, 'Atanmis danisman rehberde yok.');
    }

    /**
     * Üst firmanın ATANMAMIŞ personeli görünmez.
     *
     * Sınır bu: partner "üst firmanın herkesine" değil, yalnızca kendi
     * öğrencisiyle ilgilenen kişiye ulaşabilir.
     */
    public function test_unassigned_staff_stays_invisible(): void
    {
        $this->linkPartner();
        $this->assignAdvisor();

        $stranger = $this->userFor($this->companyA, User::ROLE_SENIOR);

        $ids = MessagingDirectory::reachableOutsideIds((int) $this->companyB->id, User::ROLE_MANAGER);

        $this->assertNotContains((int) $stranger->id, $ids, 'Atanmamis personel partnere gorunuyor.');
    }

    /** Başka partnerin danışmanı da görünmez — firmalar birbirinden habersiz. */
    public function test_another_partners_advisor_is_invisible(): void
    {
        $this->linkPartner();

        $otherPartner = Company::create(['name' => 'Diger Partner', 'code' => 'diger', 'is_active' => true]);
        $otherAdvisor = $this->userFor($this->companyA, User::ROLE_SENIOR);

        StudentAssignment::create([
            'company_id'   => $otherPartner->id,
            'student_id'   => 'STU-DIGER',
            'senior_email' => $otherAdvisor->email,
            'is_archived'  => false,
        ]);

        $ids = MessagingDirectory::reachableOutsideIds((int) $this->companyB->id, User::ROLE_MANAGER);

        $this->assertNotContains((int) $otherAdvisor->id, $ids);
    }

    // ── Yönetici ↔ yönetici (dikey) ─────────────────────────────────────────

    /** Alt firmanın yöneticisi üst firmanınkini görür. */
    public function test_partner_manager_sees_the_parent_manager(): void
    {
        $this->linkPartner();
        $parentManager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $ids = MessagingDirectory::reachableOutsideIds((int) $this->companyB->id, User::ROLE_MANAGER);

        $this->assertContains((int) $parentManager->id, $ids, 'Ust firmanin yoneticisi gorunmuyor.');
    }

    /** Üst firmanın yöneticisi alt firmanınkini görür — ilişki iki yönlü. */
    public function test_parent_manager_sees_the_partner_manager(): void
    {
        $this->linkPartner();
        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $ids = MessagingDirectory::reachableOutsideIds((int) $this->companyA->id, User::ROLE_MANAGER);

        $this->assertContains((int) $partnerManager->id, $ids, 'Alt firmanin yoneticisi gorunmuyor.');
    }

    /**
     * Yönetici olmayan, karşı firmanın yöneticisini GÖRMEZ.
     *
     * İstisna yönetici↔yönetici. İlk sürümde yalnızca hedefe bakıyordum;
     * o hâlde üst firmanın danışmanı da alt firmanın yöneticisine
     * yazabiliyordu — istisna niyetlenenden genişti.
     */
    public function test_non_manager_does_not_see_the_other_companys_manager(): void
    {
        $this->linkPartner();
        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $ids = MessagingDirectory::reachableOutsideIds((int) $this->companyA->id, User::ROLE_SENIOR);

        $this->assertNotContains((int) $partnerManager->id, $ids);
    }

    /**
     * YATAY İLİŞKİ KAPALI: iki partner birbirini görmez.
     *
     * Aynı üst firmaya bağlı olmaları onları birbirinin muhatabı yapmaz.
     * Partner firmalar birbirinden habersiz kalmalı.
     */
    public function test_sibling_partners_cannot_see_each_other(): void
    {
        $this->linkPartner();

        $sibling = Company::create([
            'name' => 'Kardes Partner', 'code' => 'kardes', 'is_active' => true,
            'parent_company_id' => $this->companyA->id,
        ]);
        Company::flushHierarchyCache();

        $siblingManager = $this->userFor($sibling, User::ROLE_MANAGER);

        $ids = MessagingDirectory::reachableOutsideIds((int) $this->companyB->id, User::ROLE_MANAGER);

        $this->assertNotContains((int) $siblingManager->id, $ids, 'Kardes partnerin yoneticisi gorunuyor.');
    }

    /** Yatay ilişkide DM de açılamaz — rehberde olmasa bile adres denenebilir. */
    public function test_sibling_partners_cannot_start_a_dm(): void
    {
        $this->linkPartner();

        $sibling = Company::create([
            'name' => 'Kardes Partner', 'code' => 'kardes2', 'is_active' => true,
            'parent_company_id' => $this->companyA->id,
        ]);
        Company::flushHierarchyCache();

        $mine    = $this->userFor($this->companyB, User::ROLE_MANAGER);
        $theirs  = $this->userFor($sibling, User::ROLE_MANAGER);

        $this->assertFalse(
            app(ConversationService::class)->canStartDmWith($mine, $theirs),
            'Kardes partnerler yazisabiliyor.'
        );
    }

    /** Üst–alt yöneticiler gerçekten yazışabilmeli. */
    public function test_vertical_managers_can_message_each_other(): void
    {
        $this->linkPartner();

        $parentManager  = $this->userFor($this->companyA, User::ROLE_MANAGER);
        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $service = app(ConversationService::class);

        $this->assertTrue($service->canStartDmWith($partnerManager, $parentManager));
        $this->assertTrue($service->canStartDmWith($parentManager, $partnerManager));
    }

    // ── DM izni ─────────────────────────────────────────────────────────────

    public function test_partner_manager_can_start_dm_with_the_advisor(): void
    {
        $this->linkPartner();
        $advisor = $this->assignAdvisor();
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $service = app(ConversationService::class);

        $this->assertTrue($service->canStartDmWith($manager, $advisor), 'Partner danismana yazamiyor.');
        // Ters yön de açık olmalı: danışman da partnere dönebilmeli.
        $this->assertTrue($service->canStartDmWith($advisor, $manager), 'Danisman partnere donemiyor.');
    }

    public function test_partner_manager_cannot_dm_unassigned_staff(): void
    {
        $this->linkPartner();
        $this->assignAdvisor();

        $stranger = $this->userFor($this->companyA, User::ROLE_SENIOR);
        $manager  = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->assertFalse(
            app(ConversationService::class)->canStartDmWith($manager, $stranger),
            'Partner, atanmamis personele yazabiliyor.'
        );
    }

    // ── Uçtan uca ───────────────────────────────────────────────────────────

    /**
     * ASIL GARANTİ: konuşma AÇILIYOR ve İKİ TARAFTA DA görünüyor.
     *
     * Konuşma listesi katılımcı bazlı ama üstüne firma filtresi biniyordu;
     * firmalar arası yazışma iki tarafta da görünmezdi. Bu test onu ölçüyor.
     */
    public function test_conversation_is_visible_to_both_sides(): void
    {
        $this->linkPartner();
        $advisor = $this->assignAdvisor();
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->asStaff($manager)->post("/im/dm/{$advisor->id}")->assertRedirect();

        $conv = Conversation::query()->latest('id')->first();
        $this->assertNotNull($conv, 'Konusma olusmadi.');

        // Partner tarafı
        $this->assertTrue(
            Conversation::query()->forUser((int) $manager->id)->whereKey($conv->id)->exists(),
            'Konusma partnerde gorunmuyor.'
        );

        // Danışman tarafı — başka şirkette
        $this->assertTrue(
            Conversation::query()->forUser((int) $advisor->id)->whereKey($conv->id)->exists(),
            'Konusma danismanda gorunmuyor.'
        );
    }

    /** Yabancı, konuşmayı hiç göremez. */
    public function test_outsider_cannot_see_the_conversation(): void
    {
        $this->linkPartner();
        $advisor = $this->assignAdvisor();
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->asStaff($manager)->post("/im/dm/{$advisor->id}");

        $conv     = Conversation::query()->latest('id')->firstOrFail();
        $outsider = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->assertFalse(
            Conversation::query()->forUser((int) $outsider->id)->whereKey($conv->id)->exists()
        );
    }
}


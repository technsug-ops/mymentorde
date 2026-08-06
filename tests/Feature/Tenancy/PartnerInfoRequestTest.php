<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\DocumentCategory;
use App\Models\DocumentUploadToken;
use App\Models\GuestApplication;
use App\Models\PartnerInfoRequest;
use App\Models\PartnerInfoRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Bilgi/belge talep zinciri: Operasyon → Partner → Öğrenci.
 *
 * Eksik belge doğrudan öğrenciden isteniyordu. Oysa öğrenciyi partner
 * getiriyor ve müşteri ilişkisi onda: operasyon eksiği PARTNERDEN ister,
 * partner de kendi öğrencisinden. Bu test zincirin üç halkasını da yürütür.
 *
 * ⚠ `PartnerInfoRequest` bilerek firma kapsamı KULLANMIYOR — kaydın iki
 * tarafı var ve tek firmalı global kapsam taraflardan birini her zaman kör
 * ederdi. Sınır sorgularda açıkça çiziliyor, o yüzden burada hem "doğru
 * taraf görüyor mu" hem "yabancı göremiyor mu" ölçülüyor.
 */
class PartnerInfoRequestTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    /** companyB'yi companyA'nın partneri yap. */
    private function linkPartner(): void
    {
        $this->companyB->update([
            'parent_company_id' => $this->companyA->id,
            'panel_mode'        => Company::PANEL_PARTNER,
        ]);

        Company::flushHierarchyCache();
        Company::flushPanelModeCache();
    }

    private function partnerGuest(): GuestApplication
    {
        return GuestApplication::create([
            'company_id'       => $this->companyB->id,
            'first_name'       => 'Aday',
            'last_name'        => 'Ogrenci',
            'email'            => 'aday-' . uniqid() . '@example.test',
            'tracking_token'   => strtoupper(uniqid()),
            'application_type' => 'bachelor',
        ]);
    }

    private function category(): DocumentCategory
    {
        return DocumentCategory::create([
            'code'              => 'pasaport',
            'name_tr'           => 'Pasaport',
            'name_de'           => 'Reisepass',
            'top_category_code' => 'uni_assist',
            'is_active'         => true,
            'sort_order'        => 1,
        ]);
    }

    // ── 1. halka: operasyon → partner ───────────────────────────────────────

    public function test_operating_company_requests_from_the_partner(): void
    {
        $this->linkPartner();
        $this->category();

        $guest   = $this->partnerGuest();
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->post('/manager/partner-requests', [
                'partner_company_id' => $this->companyB->id,
                'subject'            => 'guest:' . $guest->id,
                'category_codes'     => ['pasaport'],
                'info_items'         => "Lise diploma notu\nKalacagi adres",
                'note'               => 'Uni-Assist icin acil.',
            ])
            ->assertRedirect('/manager/partner-requests');

        $req = PartnerInfoRequest::firstOrFail();

        $this->assertSame($this->companyA->id, (int) $req->company_id);
        $this->assertSame($this->companyB->id, (int) $req->partner_company_id);
        $this->assertSame(PartnerInfoRequest::STATUS_OPEN, $req->status);

        // Bir belge + iki bilgi kalemi
        $this->assertSame(3, $req->items()->count());
        $this->assertSame(1, $req->items()->where('kind', 'document')->count());
        $this->assertSame(2, $req->items()->where('kind', 'info')->count());
    }

    /** Yalnızca kendi alt firmasına talep açılabilir. */
    public function test_cannot_request_from_an_unrelated_company(): void
    {
        $this->category();
        $guest   = $this->partnerGuest();
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        // companyB, companyA'nın altında DEĞİL (linkPartner çağrılmadı)
        $this->asStaff($manager)
            ->post('/manager/partner-requests', [
                'partner_company_id' => $this->companyB->id,
                'subject'            => 'guest:' . $guest->id,
                'category_codes'     => ['pasaport'],
            ])
            ->assertNotFound();
    }

    // ── 2. halka: partner talebi görür ve yanıtlar ──────────────────────────

    public function test_partner_sees_and_answers_the_request(): void
    {
        $this->linkPartner();
        $req = $this->makeRequest();

        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $html = $this->asStaff($partnerManager)
            ->get('/manager/partner-requests/incoming')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Aday Ogrenci', $html, 'Partner kendisine gelen talebi gormuyor.');

        $item = $req->items()->where('kind', PartnerInfoRequestItem::KIND_INFO)->firstOrFail();

        $this->asStaff($partnerManager)
            ->post("/manager/partner-requests/{$req->id}/items/{$item->id}/respond", [
                'response_text' => 'Diploma notu 82.',
            ])
            ->assertRedirect();

        $this->assertSame(PartnerInfoRequestItem::STATUS_PROVIDED, $item->fresh()->status);
    }

    /**
     * Talebi AÇAN taraf kendi talebini kendi kapatamaz.
     *
     * Aksi halde operasyon "geldi" işaretleyip aslında gelmemiş bir belgeyi
     * tamamlanmış sayardı; talebin anlamı kalmazdı.
     */
    public function test_requesting_company_cannot_answer_its_own_request(): void
    {
        $this->linkPartner();
        $req  = $this->makeRequest();
        $item = $req->items()->firstOrFail();

        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->post("/manager/partner-requests/{$req->id}/items/{$item->id}/respond", [
                'response_text' => 'kendim kapatiyorum',
            ])
            ->assertNotFound();
    }

    /** Tarafı olmayan firma talebi hiç göremez. */
    public function test_outsider_cannot_open_the_request(): void
    {
        $this->linkPartner();
        $req = $this->makeRequest();

        $outsider = Company::create(['name' => 'Yabanci', 'code' => 'yabanci', 'is_active' => true]);
        $manager  = $this->userFor($outsider, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->get('/manager/partner-requests/' . $req->id)
            ->assertNotFound();
    }

    // ── 3. halka: partner → öğrenci ─────────────────────────────────────────

    /**
     * Zincirin son halkası: partner belgeyi kendi öğrencisinden ister.
     *
     * Üretilen yükleme jetonu PARTNERIN firmasına yazılmalı — belgeyi o
     * topluyor. Operasyonun firmasına yazılsaydı partner kendi gönderdiği
     * talebi kendi ekranında göremezdi.
     */
    public function test_partner_forwards_the_document_request_to_its_student(): void
    {
        $this->linkPartner();
        $req = $this->makeRequest();

        $item = $req->items()->where('kind', PartnerInfoRequestItem::KIND_DOCUMENT)->firstOrFail();
        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->asStaff($partnerManager)
            ->post("/manager/partner-requests/{$req->id}/items/{$item->id}/forward")
            ->assertRedirect();

        $token = DocumentUploadToken::withoutGlobalScope('company')->latest('id')->firstOrFail();

        $this->assertSame($this->companyB->id, (int) $token->company_id, 'Jeton yanlis firmaya yazildi.');
        $this->assertSame(DocumentUploadToken::TARGET_GUEST, $token->target_type);
        $this->assertSame((string) $req->subject_id, (string) $token->target_id);
        $this->assertNotNull($item->fresh()->forwarded_at);
    }

    // ── Başlık durumu ───────────────────────────────────────────────────────

    /** Son kalem de gelince talep kendiliğinden kapanmalı. */
    public function test_request_closes_when_every_item_is_provided(): void
    {
        $this->linkPartner();
        $req = $this->makeRequest();

        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        foreach ($req->items as $item) {
            $this->asStaff($partnerManager)
                ->post("/manager/partner-requests/{$req->id}/items/{$item->id}/respond", ['response_text' => 'ok']);
        }

        $req->refresh();

        $this->assertSame(PartnerInfoRequest::STATUS_FULFILLED, $req->status);
        $this->assertNotNull($req->fulfilled_at);
    }

    // ── Yardımcı ────────────────────────────────────────────────────────────

    private function makeRequest(): PartnerInfoRequest
    {
        $this->category();
        $guest = $this->partnerGuest();

        $req = PartnerInfoRequest::create([
            'company_id'         => $this->companyA->id,
            'partner_company_id' => $this->companyB->id,
            'subject_type'       => PartnerInfoRequest::SUBJECT_GUEST,
            'subject_id'         => (string) $guest->id,
            'subject_name'       => 'Aday Ogrenci',
            'status'             => PartnerInfoRequest::STATUS_OPEN,
        ]);

        $req->items()->createMany([
            ['kind' => PartnerInfoRequestItem::KIND_DOCUMENT, 'category_code' => 'pasaport', 'label' => 'Pasaport', 'status' => 'pending'],
            ['kind' => PartnerInfoRequestItem::KIND_INFO, 'label' => 'Lise diploma notu', 'status' => 'pending'],
        ]);

        return $req->fresh('items');
    }
}

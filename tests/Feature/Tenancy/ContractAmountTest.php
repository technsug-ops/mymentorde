<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Sözleşme tutarı — finansın saydığı TEK rakam.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * `contract_amount_eur` kolonu vardı ve finans ekranı onu topluyordu ama
 * uygulamada onu YAZAN hiçbir kod yoktu — finans rakamları boş geliyordu.
 * Paket fiyatı ayrı bir alanda metin olarak duruyor; pazarlıkla değişen
 * gerçek tutarın yeri yoktu.
 *
 * ── SABİTLEME NEDEN AYRI ADIM ───────────────────────────────────────────
 * Tutar bir PARA kararı. Sabitlenmemiş rakamın finansa girmesi, henüz
 * anlaşılmamış bir fiyatın ciroya yazılması olurdu.
 */
class ContractAmountTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function manager(?Company $company = null): User
    {
        return $this->userFor($company ?? $this->companyA, User::ROLE_MANAGER);
    }

    private function guestFor(Company $company): GuestApplication
    {
        return GuestApplication::create([
            'company_id'             => $company->id,
            'first_name'             => 'Aday',
            'last_name'              => 'Ogrenci',
            'email'                  => 'aday-' . uniqid() . '@example.test',
            'tracking_token'         => strtoupper(uniqid()),
            'application_type'       => 'bachelor',
            'selected_package_price' => '2000',
            'contract_status'        => 'signed',
            'contract_signed_at'     => now(),
        ]);
    }

    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    // ── Sabitleme ───────────────────────────────────────────────────────────

    public function test_amount_can_be_set_and_locked(): void
    {
        $guest = $this->guestFor($this->companyA);

        $this->asStaff($this->manager())
            ->post("/manager/guests/{$guest->id}/contract-amount", [
                'contract_amount_eur'  => 1750.50,
                'contract_amount_note' => 'iki taksit',
            ])
            ->assertRedirect();

        $fresh = $guest->fresh();

        $this->assertSame(1750.50, (float) $fresh->contract_amount_eur);
        $this->assertNotNull($fresh->contract_amount_locked_at, 'Tutar sabitlenmedi.');
        $this->assertSame('iki taksit', $fresh->contract_amount_note);
        $this->assertNotEmpty($fresh->contract_amount_set_by, 'Kimin belirledigi kaydedilmedi.');
    }

    /** Sabitlenmiş tutar doğrudan değiştirilemez — önce kilit kalkmalı. */
    public function test_locked_amount_cannot_be_changed_directly(): void
    {
        $guest = $this->guestFor($this->companyA);
        $manager = $this->manager();

        $this->asStaff($manager)->post("/manager/guests/{$guest->id}/contract-amount", [
            'contract_amount_eur' => 2000,
        ]);

        $this->asStaff($manager)
            ->post("/manager/guests/{$guest->id}/contract-amount", ['contract_amount_eur' => 1])
            ->assertSessionHasErrors('contract_amount');

        $this->assertSame(2000.0, (float) $guest->fresh()->contract_amount_eur);
    }

    /**
     * Kilit kalkınca TUTAR SİLİNMEZ.
     *
     * Yanlışlıkla çözülürse rakam kaybolmamalı; yalnızca finans onu saymayı
     * bırakır.
     */
    public function test_unlock_keeps_the_amount(): void
    {
        $guest = $this->guestFor($this->companyA);
        $manager = $this->manager();

        $this->asStaff($manager)->post("/manager/guests/{$guest->id}/contract-amount", [
            'contract_amount_eur' => 2000,
        ]);

        $this->asStaff($manager)->post("/manager/guests/{$guest->id}/contract-amount/unlock")->assertRedirect();

        $fresh = $guest->fresh();

        $this->assertNull($fresh->contract_amount_locked_at);
        $this->assertSame(2000.0, (float) $fresh->contract_amount_eur, 'Kilit kalkinca tutar silindi.');
    }

    // ── Finans ──────────────────────────────────────────────────────────────

    /**
     * ASIL GARANTİ: finans YALNIZCA sabitlenmiş tutarı sayar.
     *
     * Sabitlenmemiş rakam ciroya girerse, henüz anlaşılmamış bir fiyat
     * gelir olarak raporlanır.
     */
    public function test_finance_counts_only_locked_amounts(): void
    {
        $manager = $this->manager();

        $locked   = $this->guestFor($this->companyA);
        $unlocked = $this->guestFor($this->companyA);

        $this->asStaff($manager)->post("/manager/guests/{$locked->id}/contract-amount", [
            'contract_amount_eur' => 2000,
        ]);

        // Sabitlenmemis: dogrudan yaziliyor, kilit yok.
        $unlocked->forceFill(['contract_amount_eur' => 9999])->save();

        $html = $this->asStaff($manager)->get('/manager/finance')->assertOk()->getContent();

        $this->assertStringNotContainsString('9.999', $html, 'Sabitlenmemis tutar finansa girdi.');
    }

    // ── Yetki ───────────────────────────────────────────────────────────────

    /** Fiyat kararı operasyonun — partner tutarı belirleyemez. */
    public function test_partner_cannot_set_the_amount(): void
    {
        $this->companyB->update([
            'panel_mode'        => Company::PANEL_PARTNER,
            'parent_company_id' => $this->companyA->id,
        ]);
        Company::flushPanelModeCache();
        Company::flushHierarchyCache();

        $guest = $this->guestFor($this->companyB);

        $this->asStaff($this->manager($this->companyB))
            ->post("/manager/guests/{$guest->id}/contract-amount", ['contract_amount_eur' => 1])
            ->assertNotFound();

        $this->assertNull($guest->fresh()->contract_amount_locked_at);
    }

    /** Başka firmanın adayına tutar yazılamaz. */
    public function test_cannot_set_amount_for_another_companys_guest(): void
    {
        $guest = $this->guestFor($this->companyB);

        $this->asStaff($this->manager($this->companyA))
            ->post("/manager/guests/{$guest->id}/contract-amount", ['contract_amount_eur' => 1])
            ->assertNotFound();
    }
}

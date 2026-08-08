<?php

namespace App\Models\Contracts;

/**
 * Kaydın şirketini KONUSUNDAN türeten modeller.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Varsayılan davranış "kaydı yazan kimse onun şirketine yaz". Bu, işi
 * kendi müşterisi için yapan tek firmalı dünyada doğru. Burada değil:
 * MentorDE personeli bir PARTNER firmanın öğrencisi üzerinde çalışıyor.
 * Kayıt aktörün kutusuna düşerse partner kendi öğrencisinin vizesini,
 * ödemesini, konaklamasını göremez — hata da almaz, ekran boş gelir.
 *
 * Aynı hata olay akışında yaşandı ve konu (subject) esas alınarak çözüldü;
 * bkz. App\Services\EventLogService::companyOfSubject ve
 * tests/Feature/Tenancy/PartnerProgressVisibilityTest.
 *
 * ── SÖZLEŞME ────────────────────────────────────────────────────────────
 * Yalnızca INSERT sırasında, `company_id` boşken sorulur. Türetilemezse
 * null döner ve eski davranışa (aktörün şirketi) düşülür.
 */
interface ResolvesOwnCompany
{
    /** Kaydın konusunun şirketi; türetilemezse null. */
    public function tenantOwnerCompanyId(): ?int;
}

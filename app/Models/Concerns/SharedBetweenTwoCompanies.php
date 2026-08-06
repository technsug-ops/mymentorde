<?php

namespace App\Models\Concerns;

/**
 * İŞARET (marker) TRAIT — davranışı yoktur.
 *
 * "Bu kaydın İKİ tarafı var; tek firmalı global kapsam uygulanamaz" demektir.
 *
 * Örnek: operasyon firmasının partnerden istediği bilgi/belge talebi. Kayıt
 * hem talebi AÇAN firmaya (`company_id`) hem talep EDİLEN firmaya
 * (`partner_company_id`) ait. `BelongsToCompany` eklenirse global kapsam tek
 * sütuna bakar ve taraflardan birini her zaman kör eder — partner kendisine
 * gelen talebi göremez.
 *
 * ⚠ BU TRAIT KAPSAM UYGULAMAZ. Muafiyet DEĞİL, "kapsam sorgularda elle
 * kuruluyor" beyanıdır. Böyle bir modelde her sorgu iki taraftan birini
 * açıkça belirtmek zorundadır (bkz. PartnerInfoRequest::scopeVisibleTo) ve
 * bu sınır testle korunmalıdır — yoksa filtresiz bir sorgu tüm firmaların
 * kaydını döker.
 *
 * `SharedAcrossCompanies` ile karıştırma: orada veri gerçekten herkese
 * açıktır (katalog, şehir listesi). Burada veri gizlidir, yalnızca sahibi
 * tek değildir.
 */
trait SharedBetweenTwoCompanies
{
    //
}

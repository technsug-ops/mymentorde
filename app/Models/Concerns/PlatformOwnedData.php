<?php

namespace App\Models\Concerns;

/**
 * İŞARET (marker) TRAIT — davranışı yoktur.
 *
 * "Bu kayıt PLATFORM SAHİBİNE aittir; `company_id` sahipliği değil KONUYU
 * gösterir" demektir. Örnek: bir firmanın abonelik faturası, deneme süresi
 * uzatması, müşteri sağlık skoru. Fatura firmanın değil, firmaya kesilmiştir.
 *
 * ── NEDEN SharedAcrossCompanies DEĞİL ───────────────────────────────────
 * O trait "bu veriyi herkes kullanır" demek (üniversite kataloğu, şehir
 * listesi). Buradaki veri ise TAM TERSİ: yalnızca platform sahibi görür,
 * hiçbir firma görmez. İkisini aynı işaretle etiketlemek, ileride
 * `PlatformInvoice`'ta `SharedAcrossCompanies` görüp "demek partner firmalar
 * da görebiliyor" diye okunmasına yol açardı.
 *
 * ── İZOLASYON NASIL SAĞLANIYOR ──────────────────────────────────────────
 * Global kapsamla değil, ROTAYLA: bu modeller yalnızca `/platform/*`
 * altından okunuyor ve o rotalar platform sahibine kapalı.
 *
 * ⚠ Bir modele bu trait'i eklerken doğrula: gerçekten YALNIZCA Platform
 * namespace'inden mi sorgulanıyor? Firma panelinden de okunuyorsa yeri
 * burası değil, App\Models\Concerns\BelongsToCompany.
 */
trait PlatformOwnedData
{
    //
}

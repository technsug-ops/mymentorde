<?php

namespace App\Models\Concerns;

/**
 * İŞARET (marker) TRAIT — davranışı yoktur.
 *
 * "Bu modelin verisi bilinçli olarak TÜM ŞİRKETLER ARASINDA PAYLAŞILIR" demektir.
 * Örnek: program/üniversite katalogu, şehir-il listeleri, izin tanımları — bunlar
 * MentorDE'ye ya da bir partner firmaya ait değildir, herkes aynı veriyi kullanır.
 *
 * Amacı: `ModelScopeCoverageTest` "bu modelde şirket filtresi yok" dediğinde
 * "unutuldu mu, bilerek mi?" sorusunu kalıcı olarak cevaplamak. Bir modele bu
 * trait'i eklemek, tenant izolasyonundan muaf tutulduğuna dair açık bir karardır.
 *
 * ⚠ Kişisel veri (öğrenci, aday, mesaj, belge, ödeme) içeren bir modele ASLA
 * eklenmemeli — onların yeri App\Models\Concerns\BelongsToCompany.
 */
trait SharedAcrossCompanies
{
    //
}

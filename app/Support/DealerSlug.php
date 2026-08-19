<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Mini-site adres parçasını (slug) kullanıcının yazdığı halden kanonik hale getirir.
 *
 * ── NEDEN VAR ───────────────────────────────────────────────────────────
 * Alanın etiketi "Slug (/p/...)" olduğu için kullanıcı gayet makul biçimde
 * `/p/yigitdanismanlik` yazıyor — ve `pattern` niteliği yüzünden tarayıcının
 * kendi dilinde ("Deine Eingabe muss...") anlamsız bir hata alıyordu. Türkçe
 * harf yazınca da aynı duvar.
 *
 * Kabul et, sonra düzelt: `/p/` öneki, tam URL, büyük harf, boşluk ve Türkçe
 * harfler sessizce temizlenir. Reddetmek yerine anlamak — kullanıcı adresin
 * hangi parçasını yazacağını ezberlemek zorunda değil.
 *
 * ⚠ Doğrulama KALKMADI: normalize edilmiş değer hâlâ benzersizlik, uzunluk ve
 * rezerve-yol denetimlerinden geçer. Bu sınıf yalnızca girdiyi hizalar.
 */
class DealerSlug
{
    /** Boş/anlamsız girdide null döner (çağıran "değiştirme" olarak yorumlar). */
    public static function normalize(?string $raw): ?string
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        // Tam adres yapıştırıldıysa şema + alan adını at: https://firma.com/p/x → /p/x
        $value = preg_replace('#^https?://[^/]+#i', '', $value) ?? $value;

        // Yolun kendisi: "/p/x" ya da "p/x" → "x"
        $value = preg_replace('#^/*p/#i', '', $value) ?? $value;

        // Sorgu/çapa artıkları: "x?preview=1" → "x"
        $value = preg_split('/[?#]/', $value)[0] ?? $value;

        // Str::slug Türkçe harfleri çevirir (ı→i, ş→s, ğ→g), küçültür, boşluğu tireler.
        $value = Str::slug(trim($value, " \t\n\r\0\x0B/"));

        return $value === '' ? null : $value;
    }
}

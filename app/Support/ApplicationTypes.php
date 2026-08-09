<?php

namespace App\Support;

/**
 * Başvuru türleri ve "bu alan bu türde görünür mü" kuralı — TEK KAYNAK.
 *
 * ── BOŞ = HEPSİ ─────────────────────────────────────────────────────────
 * Etiketsiz alan her türde görünür. Kural bu yönde kuruldu çünkü tersi
 * (etiketsiz = hiçbir yerde) etiket sistemi eklendiği anda formu boşaltırdı.
 * Aynı kalıp sistemde zaten var: FieldRuleEngine, boş
 * `applicable_student_types` listesini "kısıt yok" sayıyor.
 *
 * ⚠ TANINMAYAN ETİKET GÖRMEZDEN GELİNİR. Elle girilmiş ya da eskimiş bir
 * değer ("yükseklisans" gibi) alanı gizleseydi, kimsenin fark etmeyeceği
 * bir veri kaybı olurdu: form alanı sessizce yok olur, öğrenci o bilgiyi
 * hiç vermez. Bilinmeyen etiket temizlenir; hepsi temizlenirse alan
 * "etiketsiz" sayılır ve görünür kalır.
 */
final class ApplicationTypes
{
    public const BACHELOR   = 'bachelor';
    public const MASTER     = 'master';
    public const AUSBILDUNG = 'ausbildung';

    /** @var array<string,string> kod => ekranda görünen ad */
    public const LABELS = [
        self::BACHELOR   => 'Bachelor (Lisans)',
        self::MASTER     => 'Master (Yüksek Lisans)',
        self::AUSBILDUNG => 'Ausbildung (Mesleki Eğitim)',
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    /** Ham değeri bilinen bir türe indirger; tanımadıysa null. */
    public static function normalize(mixed $value): ?string
    {
        $code = strtolower(trim((string) $value));

        return isset(self::LABELS[$code]) ? $code : null;
    }

    /**
     * Etiket listesini temizler: bilinmeyenler atılır, tekrarlar teklenir.
     *
     * @return list<string>
     */
    public static function sanitizeList(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        if (! is_array($value)) {
            return [];
        }

        $codes = [];

        foreach ($value as $item) {
            $code = self::normalize($item);

            if ($code !== null && ! in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        // Üçü de seçiliyse kısıt yok demektir; boş saklamak hem daha dürüst
        // hem de ileride tür eklendiğinde alanı kendiliğinden kapsar.
        return count($codes) === count(self::LABELS) ? [] : $codes;
    }

    /**
     * Bu etiketle işaretli bir alan, verilen başvuru türünde görünür mü?
     *
     * @param mixed       $tags            alanın etiketi (null/[] = hepsi)
     * @param string|null $applicationType bağlam bilinmiyorsa null
     */
    public static function applies(mixed $tags, ?string $applicationType): bool
    {
        $allowed = self::sanitizeList($tags);

        // Etiketsiz alan her zaman görünür.
        if ($allowed === []) {
            return true;
        }

        $type = self::normalize($applicationType);

        // ⚠ Tür bilinmiyorsa GİZLEME. Yönetim ekranları, PDF çıktısı ve
        // şablon karşılaştırması türsüz çalışıyor; orada filtre uygulamak
        // alanların "kaybolduğu" izlenimi verirdi. Filtre yalnızca tür
        // gerçekten bilindiğinde daraltır.
        if ($type === null) {
            return true;
        }

        return in_array($type, $allowed, true);
    }
}

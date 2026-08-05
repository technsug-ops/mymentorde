<?php

namespace App\Support;

/**
 * Üst firmanın alt firmaya koyabileceği yetki kısıtları.
 *
 * MODEL: varsayılan TAM yetki, kısıt EKLENEREK daraltılır.
 * Boş liste = hiçbir kısıt yok.
 *
 * Neden sabit rol değil: partner firmalar aynı değil. Biri öğrenciyi devredip
 * yalnızca izlemek ister, biri belge de yükler, biri kendi operasyonunu yürütür.
 * Kısıtı ağacın üstündeki firma kendisi ayarlar.
 *
 * Kısıtlar aşağı doğru BİRİKİR (bkz. Company::effectiveDeniedPermissions).
 */
final class PermissionCeiling
{
    /**
     * Kısıtlanabilir yetkiler — panelde gösterilecek etiketleriyle.
     *
     * Yalnızca ANLAMLI olanlar listeleniyor: `config.view` ya da
     * `student.card.view` gibi "görmek" yetkilerini kapatmak partneri
     * kendi öğrencisine kör eder, o yüzden burada yok.
     *
     * @var array<string,array{label:string,desc:string,group:string}>
     */
    public const RESTRICTABLE = [
        // ── Operasyon ────────────────────────────────────────────────────
        'student.assignment.manage' => [
            'label' => 'Adayı öğrenciye dönüştürme',
            'desc'  => 'Partner öğrenciyi kendi tarafında imzalayıp devrediyorsa AÇIK bırakın. '
                     . 'Danışman seçimi bu yetkiye dahil değildir — o her hâlükârda operasyon şirketindedir.',
            'group' => 'Operasyon',
        ],
        'approval.manage' => [
            'label' => 'Onay süreçlerini yürütme',
            'desc'  => 'Onay bekleyen adımları geçme yetkisi.',
            'group' => 'Operasyon',
        ],
        'ticket.center.route' => [
            'label' => 'Destek talebi yönlendirme',
            'desc'  => 'Talebi başka birime aktarma. Kapatılsa da talepleri görür ve yanıtlar.',
            'group' => 'Operasyon',
        ],

        // ── Para ─────────────────────────────────────────────────────────
        'revenue.manage' => [
            'label' => 'Gelir ve komisyon yönetimi',
            'desc'  => 'Ödeme, hakediş ve komisyon kayıtlarını değiştirme.',
            'group' => 'Para',
        ],

        // ── Yönetim ──────────────────────────────────────────────────────
        'role.template.manage' => [
            'label' => 'Rol ve yetki tanımlama',
            'desc'  => 'Kapatılması önerilir: açıkken firma kendi içinde yeni yetkiler üretebilir.',
            'group' => 'Yönetim',
        ],
        'config.manage' => [
            'label' => 'Yapılandırma değiştirme',
            'desc'  => 'Şirket ayarları. Kapatılsa da ayarları görüntüleyebilir.',
            'group' => 'Yönetim',
        ],
        'notification.manage' => [
            'label' => 'Bildirim şablonlarını değiştirme',
            'desc'  => 'Öğrenciye giden otomatik mesajların içeriği.',
            'group' => 'Yönetim',
        ],

        // ── Dosya ────────────────────────────────────────────────────────
        'dam.delete' => [
            'label' => 'Dosya silme',
            'desc'  => 'Marka kütüphanesinden dosya silme.',
            'group' => 'Dosya',
        ],
        'dam.update' => [
            'label' => 'Dosya değiştirme',
            'desc'  => 'Yüklenmiş dosyayı düzenleme, yeniden adlandırma.',
            'group' => 'Dosya',
        ],
        'dam.upload' => [
            'label' => 'Dosya yükleme',
            'desc'  => 'Kapatılırsa firma yalnızca indirebilir.',
            'group' => 'Dosya',
        ],
        'dam.folder.manage' => [
            'label' => 'Klasör yönetimi',
            'desc'  => 'Klasör oluşturma, taşıma, silme.',
            'group' => 'Dosya',
        ],
        'doc_request.use' => [
            'label' => 'Öğrenciden belge talep etme',
            'desc'  => 'Tek kullanımlık belge talep linki oluşturma.',
            'group' => 'Dosya',
        ],

        // ── Pazarlama ────────────────────────────────────────────────────
        'marketing.campaign.manage' => [
            'label' => 'Kampanya yönetimi',
            'desc'  => 'Pazarlama kampanyası oluşturma ve düzenleme.',
            'group' => 'Pazarlama',
        ],
    ];

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::RESTRICTABLE);
    }

    /**
     * Panelde gruplanmış gösterim için.
     *
     * @return array<string,array<string,array{label:string,desc:string,group:string}>>
     */
    public static function grouped(): array
    {
        $out = [];

        foreach (self::RESTRICTABLE as $code => $meta) {
            $out[$meta['group']][$code] = $meta;
        }

        return $out;
    }

    /**
     * Gelen listeyi tanınan kodlara indirge.
     *
     * @param  mixed  $codes
     * @return list<string>
     */
    public static function sanitize(mixed $codes): array
    {
        if (!is_array($codes)) {
            return [];
        }

        return array_values(array_intersect(
            array_map('strval', $codes),
            self::codes()
        ));
    }
}

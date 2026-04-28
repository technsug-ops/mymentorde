<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    /**
     * 5 ana kategori — bir belge birden fazla kategoride etiketlenebilir
     * (örn. Pasaport: uni_assist + vize + yurt). Her etiket ayrı bir
     * guest_required_documents satırı olarak saklanır, kendi sort_order'ı ile.
     */
    public const TOP_CATEGORIES = [
        'uni_assist' => 'Uni Asist',
        'vize'       => 'Vize',
        'dil_okulu'  => 'Dil Okulu',
        'uni_kayit'  => 'Üniversite Kayıt',
        'yurt'       => 'İkamet',
        'diger'      => 'Diğer',
    ];

    /**
     * Eski top_category_code → yeni 5'li harita (geriye uyum için).
     */
    public const LEGACY_TOP_CATEGORY_MAP = [
        'uni_assist_dokumanlari'         => 'uni_assist',
        'kisisel_dokumanlar'             => 'uni_assist',
        'vize_dokumanlari'               => 'vize',
        'dil_okulu_dokumanlari'          => 'dil_okulu',
        'ikamet_kaydi_dokumanlari'       => 'yurt',
        'almanya_burokrasi_dokumanlari'  => 'diger',
        'partner_dokumanlari'            => 'diger',
        'diger_dokumanlar'               => 'diger',
        'egitim_belgeleri'               => 'uni_assist',
        'dil_belgeleri'                  => 'dil_okulu',
        'mali_belgeler'                  => 'vize',
        'basvuru_dosyasi'                => 'uni_assist',
        'tercumeler'                     => 'uni_assist',
        'kimlik'                         => 'uni_assist',
    ];

    protected $fillable = [
        'code',
        'name_tr',
        'name_de',
        'name_en',
        'top_category_code',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function topCategoryOptions(): array
    {
        return self::TOP_CATEGORIES;
    }

    public static function defaultTopCategoryCode(): string
    {
        return 'diger';
    }

    /**
     * Eski top_category_code'u yeni 5'li sisteme normalize et.
     */
    public static function normalizeTopCategoryCode(?string $code): string
    {
        $code = (string) ($code ?? '');
        if ($code === '') return self::defaultTopCategoryCode();
        if (isset(self::TOP_CATEGORIES[$code])) return $code;
        return self::LEGACY_TOP_CATEGORY_MAP[$code] ?? self::defaultTopCategoryCode();
    }
}

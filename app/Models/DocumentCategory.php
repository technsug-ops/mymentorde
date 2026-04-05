<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    public const TOP_CATEGORIES = [
        'kisisel_dokumanlar' => 'Kişisel Dokümanlar',
        'uni_assist_dokumanlari' => 'Uni Assist Dokümanları',
        'vize_dokumanlari' => 'Vize Dokümanları',
        'dil_okulu_dokumanlari' => 'Dil Okulu Dokümanları',
        'ikamet_kaydi_dokumanlari' => 'İkamet Kaydı Dokümanları',
        'almanya_burokrasi_dokumanlari' => 'Almanya Bürokrasi Dokümanları',
        'diger_dokumanlar' => 'Diğer Dokümanlar',
        'partner_dokumanlari' => 'Partner Dokümanları',
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
        return 'diger_dokumanlar';
    }
}

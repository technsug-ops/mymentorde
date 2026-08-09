<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Kayıt formu alan tanımı.
 *
 * `company_id = 0` bu tabloda FABRİKA ŞABLONU demek: merkezî tanım, tüm
 * firmalar miras alır (bkz. GuestRegistrationFieldSchemaService::rowsFor —
 * firmanın kendi satırı → üst firmalar → fabrika).
 *
 * ⚠ Beyan edilmeseydi `BelongsToCompany::creating` içindeki `empty()`
 * kontrolü 0'ı "boş" sayar ve varsayılan firmanın id'siyle EZERDİ: fabrika
 * satırı sessizce tek bir firmanın malı olur, merkezî değişiklik
 * diğerlerine hiç ulaşmazdı. Tohumlama ham insert kullandığı için bu
 * tuzak bugüne kadar görünmedi.
 */
class GuestRegistrationField extends Model
{
    use BelongsToCompany;

    /** Fabrika (merkezî) satırlar her firmada görünür. */
    public static function tenantIncludesFactoryRows(): bool
    {
        return true;
    }

    protected $fillable = [
        'company_id',
        'section_key',
        'section_title',
        'section_order',
        'field_key',
        'label',
        'type',
        'is_required',
        'sort_order',
        'max_length',
        'placeholder',
        'help_text',
        'options_json',
        'is_active',
        'is_system',
        // Hangi başvuru türlerinde görünür. Boş/null = hepsi.
        // bkz. App\Support\ApplicationTypes
        'applicable_types',
    ];

    protected $casts = [
        'applicable_types' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'section_order' => 'integer',
        'sort_order' => 'integer',
        'max_length' => 'integer',
        'options_json' => 'array',
    ];
}


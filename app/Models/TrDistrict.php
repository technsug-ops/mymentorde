<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Türkiye ilçesi (~973 toplam). province_id ile il'e bağlı.
 * Form'da cascading dropdown için kullanılır — il seçilince ilçe filtrelenir.
 */
class TrDistrict extends Model
{
    protected $table = 'tr_districts';

    protected $fillable = ['province_id', 'slug', 'name', 'is_central'];

    protected $casts = [
        'province_id' => 'integer',
        'is_central'  => 'boolean',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(TrProvince::class, 'province_id');
    }

    /**
     * Tüm ilçeleri tek seferde döndürür — Blade'de cascading select
     * için inline data-province attribute ile basılır.
     *
     * @return array<int, array{value:string,label:string,province_slug:string}>
     */
    public static function allWithProvince(): array
    {
        return self::query()
            ->join('tr_provinces', 'tr_provinces.id', '=', 'tr_districts.province_id')
            ->orderBy('tr_provinces.name')
            ->orderBy('tr_districts.name')
            ->get(['tr_districts.slug', 'tr_districts.name', 'tr_provinces.slug as province_slug'])
            ->map(fn ($d) => [
                'value'         => $d->slug,
                'label'         => $d->name,
                'province_slug' => $d->province_slug,
            ])
            ->all();
    }
}

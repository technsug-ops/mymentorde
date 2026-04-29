<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Türkiye ili (81 il). Plaka koduyla unique. Form province dropdown'ı
 * ve cascading district select için kaynak.
 *
 * Multi-tenant scope YOK — Türkiye il listesi tüm company'ler için ortak.
 */
class TrProvince extends Model
{
    protected $table = 'tr_provinces';

    protected $fillable = ['plate_code', 'slug', 'name', 'region', 'is_metropolitan'];

    protected $casts = [
        'plate_code'      => 'integer',
        'is_metropolitan' => 'boolean',
    ];

    public function districts(): HasMany
    {
        return $this->hasMany(TrDistrict::class, 'province_id');
    }

    /** Form dropdown için label sıralı seçenek listesi. */
    public static function options(): array
    {
        return self::query()
            ->orderBy('name')
            ->get(['slug', 'name', 'plate_code'])
            ->map(fn (TrProvince $p) => ['value' => $p->slug, 'label' => $p->name])
            ->all();
    }
}

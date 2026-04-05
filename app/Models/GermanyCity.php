<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GermanyCity extends Model
{
    protected $fillable = ['slug', 'name', 'state', 'emoji', 'cost_index', 'data', 'is_active'];

    protected $casts = [
        'data'      => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Tüm aktif şehirleri slug => merged_array formatında döner.
     * 24 saat cache'lenir — config('germany_cities') gibi kullanılır.
     */
    public static function allAsConfig(): array
    {
        return Cache::remember('germany_cities_all', now()->addDay(), function (): array {
            return self::query()
                ->where('is_active', true)
                ->get(['slug', 'name', 'state', 'emoji', 'cost_index', 'data'])
                ->keyBy('slug')
                ->map(fn ($city) => array_merge(
                    ['slug' => $city->slug, 'name' => $city->name, 'state' => $city->state,
                     'emoji' => $city->emoji, 'cost_index' => $city->cost_index],
                    $city->data ?? []
                ))
                ->all();
        });
    }

    /** Cache'i temizler — admin bir şehri güncellediğinde çağrılır. */
    public static function clearCache(): void
    {
        Cache::forget('germany_cities_all');
    }
}

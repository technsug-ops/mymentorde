<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MarketingAdminSetting extends Model
{
    use BelongsToCompany;

    protected static function booted(): void
    {
        static::saved(static function (self $model): void {
            if ($model->company_id) {
                Cache::forget("brand_settings_{$model->company_id}");
            }
        });
    }

    protected $fillable = [
        'company_id',
        'setting_key',
        'setting_value',
        'updated_by_user_id',
    ];

    protected $casts = [
        'setting_value' => 'array',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::where('setting_key', $key)->first();
        return $row?->setting_value['value'] ?? $default;
    }

    public static function setValue(string $key, string $value, ?int $userId = null): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => ['value' => $value], 'updated_by_user_id' => $userId]
        );
    }
}

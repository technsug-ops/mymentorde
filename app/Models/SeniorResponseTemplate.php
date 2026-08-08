<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Danışmanın hazır yanıtları.
 *
 * İki tür satır var: firmanın/danışmanın kendi şablonu ve herkesin miras
 * aldığı fabrika şablonu (`company_id = 0`). Eskiden fabrika işareti NULL'du;
 * NULL "sahibi bilinmiyor" ile aynı değer olduğu için ayırt edilemiyordu —
 * 2026_08_08_120000 migration'ı onları 0'a çekti.
 */
class SeniorResponseTemplate extends Model
{
    use BelongsToCompany;

    protected $table = 'senior_response_templates';

    protected $fillable = [
        'company_id',
        'owner_user_id',
        'category',
        'title',
        'body',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'usage_count' => 'integer',
    ];

    /** Fabrika şablonları her firmada görünür — bkz. BelongsToCompany. */
    public static function tenantIncludesFactoryRows(): bool
    {
        return true;
    }

    /** Fabrika satırı: hiçbir firmaya ait değil, düzenlenemez/silinemez. */
    public function isFactoryRow(): bool
    {
        return (int) $this->company_id === 0;
    }
}

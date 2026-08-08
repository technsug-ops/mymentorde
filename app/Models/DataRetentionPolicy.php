<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class DataRetentionPolicy extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'entity_type',
        'anonymize_after_days',
        'is_active',
        'notes',
    ];

    /**
     * Platformun varsayılan saklama süreleri (`company_id = 0`) her firmada
     * görünür; firma kendi politikasını yazdığında o satır kendi şirketine
     * ait olur.
     */
    public static function tenantIncludesFactoryRows(): bool
    {
        return true;
    }

    /** Fabrika satırı: hiçbir firmaya ait değil, düzenlenemez/silinemez. */
    public function isFactoryRow(): bool
    {
        return (int) $this->company_id === 0;
    }

    protected function casts(): array
    {
        return [
            'company_id'           => 'integer',
            'anonymize_after_days' => 'integer',
            'is_active'            => 'boolean',
        ];
    }
}

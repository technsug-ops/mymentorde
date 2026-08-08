<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BusinessContractTemplate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'contract_type',
        'template_code',
        'name',
        'body_text',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Fabrika sözleşme metinleri (`company_id = 0`) her firmada görünür —
     * bayi ve personel sözleşmelerinin standart metni buradan geliyor.
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
}

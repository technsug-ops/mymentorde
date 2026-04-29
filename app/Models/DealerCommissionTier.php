<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Bayi komisyon kademesi.
 *
 * Lifetime kümülatif kayıt sayısına göre tier belirlenir.
 * Pazarlama (/satis-ortagi) tablosu ile birebir eşleşir.
 */
class DealerCommissionTier extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'tier_code',
        'tier_name',
        'tier_emoji',
        'dealer_type_code',
        'min_count',
        'max_count',
        'commission_rate_eur',
        'commission_rate_percent',
        'advantages_text',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'min_count'                => 'integer',
        'max_count'                => 'integer',
        'commission_rate_eur'      => 'decimal:2',
        'commission_rate_percent'  => 'decimal:2',
        'display_order'            => 'integer',
        'is_active'                => 'boolean',
    ];

    public function rateLabel(): string
    {
        if ($this->commission_rate_percent !== null && (float) $this->commission_rate_percent > 0) {
            return '%' . rtrim(rtrim(number_format((float) $this->commission_rate_percent, 2, '.', ''), '0'), '.');
        }
        if ($this->commission_rate_eur !== null && (float) $this->commission_rate_eur > 0) {
            return '€' . number_format((float) $this->commission_rate_eur, 0, ',', '.');
        }
        return '—';
    }

    public function rangeLabel(): string
    {
        if ($this->max_count === null) return $this->min_count . '+';
        return $this->min_count . '–' . $this->max_count;
    }
}

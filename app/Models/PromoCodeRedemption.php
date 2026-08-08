<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform Owner — Promo Code Redemption.
 *
 * Bir company'in kodu uygulamasi: hangi kod, hangi tarihte, kac EUR indirim,
 * hangi platform_invoices'lara etkili oldu (JSON id listesi).
 *
 * Audit ve analytics icin: getStats() bu tablodan toplam redemption + total
 * discount given hesaplar.
 */
class PromoCodeRedemption extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'promo_code_id',
        'company_id',
        'applied_at',
        'discount_applied_eur',
        'invoice_ids',
    ];

    protected $casts = [
        'applied_at'           => 'datetime',
        'discount_applied_eur' => 'decimal:2',
        'invoice_ids'          => 'array',
    ];

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

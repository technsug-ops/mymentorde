<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * İndirim kodu kullanım kaydı.
 *
 * Polymorphic — şu an sadece 'guest_payment_request' kullanılıyor;
 * ileride 'student_payment', 'booking_payment' de aynı tabloya yazılır.
 */
class DiscountCodeRedemption extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'discount_code_id',
        'redeemable_type',
        'redeemable_id',
        'guest_application_id',
        'user_id',
        'original_amount_eur',
        'discount_amount_eur',
        'final_amount_eur',
        'redeemed_at',
        'meta',
    ];

    protected $casts = [
        'redeemed_at'         => 'datetime',
        'original_amount_eur' => 'decimal:2',
        'discount_amount_eur' => 'decimal:2',
        'final_amount_eur'    => 'decimal:2',
        'meta'                => 'array',
    ];

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function redeemable(): MorphTo
    {
        return $this->morphTo();
    }

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class);
    }
}

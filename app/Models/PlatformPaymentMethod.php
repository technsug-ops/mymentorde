<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformPaymentMethod extends Model
{
    protected $fillable = [
        'company_id',
        'stripe_payment_method_id',
        'type',
        'brand',
        'last4',
        'exp_month',
        'exp_year',
        'is_default',
    ];

    protected $casts = [
        'exp_month'  => 'integer',
        'exp_year'   => 'integer',
        'is_default' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** "Visa **** 4242" tarzi kisa etiket. */
    public function label(): string
    {
        $brand = $this->brand ? ucfirst($this->brand) : ucfirst((string) $this->type);
        $tail  = $this->last4 ? ' **** ' . $this->last4 : '';
        return $brand . $tail;
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CompanyPaymentSetting extends Model
{
    use BelongsToCompany;

    protected $table = 'company_payment_settings';

    protected $fillable = [
        'company_id',
        'is_payment_enabled',
        'payout_day_of_month',
        'payout_minimum_cents',
        'allow_on_demand_payout',
        'default_commission_pct',
        'refund_window_hours',
        'stripe_mode',
        'stripe_public_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
    ];

    protected $casts = [
        'is_payment_enabled'     => 'boolean',
        'allow_on_demand_payout' => 'boolean',
        'default_commission_pct' => 'decimal:2',
        'payout_day_of_month'    => 'integer',
        'payout_minimum_cents'   => 'integer',
        'refund_window_hours'    => 'integer',
    ];

    protected $hidden = [
        'stripe_secret_key',
        'stripe_webhook_secret',
    ];
}

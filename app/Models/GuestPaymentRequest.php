<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class GuestPaymentRequest extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'guest_application_id',
        'package_code',
        'package_title',
        'amount_eur',
        'payment_method',
        'status',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'paid_at'    => 'datetime',
        'amount_eur' => 'decimal:2',
    ];

    public function guestApplication()
    {
        return $this->belongsTo(GuestApplication::class, 'guest_application_id');
    }
}

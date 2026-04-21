<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeniorEarning extends Model
{
    use BelongsToCompany;

    protected $table = 'senior_earnings';

    protected $fillable = [
        'company_id',
        'senior_user_id',
        'public_booking_id',
        'student_appointment_id',
        'amount_net_cents',
        'tax_rate_pct_applied',
        'tax_amount_cents',
        'amount_gross_cents',
        'commission_pct_applied',
        'commission_cents',
        'senior_payout_cents',
        'currency',
        'status',
        'payout_id',
        'recorded_at',
    ];

    protected $casts = [
        'amount_net_cents'       => 'integer',
        'tax_rate_pct_applied'   => 'decimal:2',
        'tax_amount_cents'       => 'integer',
        'amount_gross_cents'     => 'integer',
        'commission_pct_applied' => 'decimal:2',
        'commission_cents'       => 'integer',
        'senior_payout_cents'    => 'integer',
        'recorded_at'            => 'datetime',
    ];

    public function senior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senior_user_id');
    }

    public function publicBooking(): BelongsTo
    {
        return $this->belongsTo(PublicBooking::class, 'public_booking_id');
    }

    public function studentAppointment(): BelongsTo
    {
        return $this->belongsTo(StudentAppointment::class, 'student_appointment_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(SeniorPayout::class, 'payout_id');
    }
}

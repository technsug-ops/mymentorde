<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PublicBooking extends Model
{
    use BelongsToCompany;

    protected $table = 'public_bookings';

    protected $fillable = [
        'company_id',
        'senior_user_id',
        'booked_by_user_id',
        'student_user_id',
        'guest_application_id',
        'invitee_name',
        'invitee_email',
        'invitee_phone',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'senior_notes',
        'booking_token',
        'student_appointment_id',
        'canceled_at',
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'canceled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PublicBooking $pb): void {
            if (empty($pb->booking_token)) {
                $pb->booking_token = self::generateToken();
            }
        });
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::query()->withoutGlobalScopes()->where('booking_token', $token)->exists());
        return $token;
    }

    public function senior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senior_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class, 'guest_application_id');
    }

    public function studentAppointment(): BelongsTo
    {
        return $this->belongsTo(StudentAppointment::class, 'student_appointment_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending_confirm', 'confirmed'], true);
    }

    public function scopeActive($q)
    {
        return $q->whereIn('status', ['pending_confirm', 'confirmed']);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class StudentAssignment extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'student_id',
        'display_name',
        'internal_sequence',
        'senior_email',
        'branch',
        'risk_level',
        'payment_status',
        'dealer_id',
        'student_type',
        'is_archived',
        'archived_by',
        'archived_at',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    /** Atanmış senior kullanıcısı (email FK üzerinden) */
    public function senior(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'senior_email', 'email');
    }

    /** Bağlı GuestApplication kaydı (student_id → converted_student_id) */
    public function guestApplication(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GuestApplication::class, 'student_id', 'converted_student_id');
    }
}

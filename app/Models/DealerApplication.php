<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satış ortağı (dealer) başvuru kaydı — public landing'den gelir.
 * Manager onay/red işleyene kadar "pending" statüsünde bekler.
 */
class DealerApplication extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'first_name', 'last_name', 'email', 'phone', 'city', 'country',
        'company_name', 'tax_number', 'business_type',
        'preferred_plan', 'expected_monthly_volume', 'education_experience', 'experience_details',
        'heard_from', 'referrer_email', 'motivation',
        'utm_source', 'utm_medium', 'utm_campaign',
        'status', 'reviewed_by', 'reviewed_at', 'review_note', 'rejected_reason',
        'approved_dealer_id', 'approved_user_id',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'education_experience' => 'boolean',
        'expected_monthly_volume' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public const STATUSES = ['pending', 'in_review', 'approved', 'rejected', 'waitlist'];
    public const PLANS = ['lead_generation', 'freelance', 'unsure'];

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeInReview($query) { return $query->where('status', 'in_review'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
}

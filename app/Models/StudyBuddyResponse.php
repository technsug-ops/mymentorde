<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Discovery Wizard cevap kaydı (Faz 2).
 *
 * İZOLASYON: COMPANY-SCOPED (BelongsToCompany trait).
 * - Bayi A'nın wizard cevapları bayi B ile paylaşılmaz
 * - Anonymous funnel: session_token (UUID) ile browser ↔ DB
 * - Tamamlandığında recommendations alanına ranked program listesi yazılır
 * - "Kayıt ol" CTA → guest_application'a dönüşür (converted_to_guest_id)
 */
class StudyBuddyResponse extends Model
{
    use BelongsToCompany;

    protected $table = 'study_buddy_responses';

    protected $fillable = [
        'company_id', 'session_token',
        'answers', 'current_step', 'total_steps',
        'recommendations',
        'started_at', 'completed_at', 'last_active_at',
        'converted_to_guest_id', 'converted_at',
        'source', 'referrer', 'ip', 'user_agent',
    ];

    protected $casts = [
        'answers'         => 'array',
        'recommendations' => 'array',
        'current_step'    => 'integer',
        'total_steps'     => 'integer',
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
        'last_active_at'  => 'datetime',
        'converted_at'    => 'datetime',
    ];

    public function convertedGuest(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class, 'converted_to_guest_id');
    }

    /** Tek bir cevap key'ini güncelle ve kaydet. */
    public function setAnswer(string $key, mixed $value): self
    {
        $answers = is_array($this->answers) ? $this->answers : [];
        $answers[$key] = $value;
        $this->answers = $answers;
        $this->last_active_at = now();
        return $this;
    }

    /** Cevap getter — yoksa default. */
    public function getAnswer(string $key, mixed $default = null): mixed
    {
        $answers = is_array($this->answers) ? $this->answers : [];
        return $answers[$key] ?? $default;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}

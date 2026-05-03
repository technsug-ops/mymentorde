<?php

namespace App\Models\Marketing;

use App\Models\GuestApplication;
use App\Models\UniMatchResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDripEnrollment extends Model
{
    public $timestamps = false;

    protected $table = 'email_drip_enrollments';

    protected $fillable = [
        'drip_sequence_id',
        'guest_application_id',
        'uni_match_response_id',
        'current_step',
        'status',
        'next_send_at',
        'enrolled_at',
        'completed_at',
    ];

    protected $casts = [
        'current_step' => 'integer',
        'next_send_at' => 'datetime',
        'enrolled_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(EmailDripSequence::class, 'drip_sequence_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class, 'guest_application_id');
    }

    public function uniMatchResponse(): BelongsTo
    {
        return $this->belongsTo(UniMatchResponse::class, 'uni_match_response_id');
    }

    /**
     * Recipient email — guest_application varsa email, yoksa UniMatch lead_email.
     */
    public function getRecipientEmail(): ?string
    {
        if ($this->guest_application_id && $this->guest) {
            return $this->guest->email;
        }
        if ($this->uni_match_response_id && $this->uniMatchResponse) {
            return $this->uniMatchResponse->lead_email;
        }
        return null;
    }

    /**
     * Recipient name — guest_application varsa "first last", yoksa UniMatch lead_first_name.
     */
    public function getRecipientName(): ?string
    {
        if ($this->guest_application_id && $this->guest) {
            return trim(($this->guest->first_name ?? '') . ' ' . ($this->guest->last_name ?? ''));
        }
        if ($this->uni_match_response_id && $this->uniMatchResponse) {
            return $this->uniMatchResponse->lead_first_name;
        }
        return null;
    }

    /**
     * Genel template variables — guest veya UniMatch response'tan üret.
     * Drip step template'leri bu context'le render edilir.
     */
    public function getTemplateContext(): array
    {
        $ctx = [
            'firstName' => $this->getRecipientName() ?: 'Merhaba',
            'email'     => $this->getRecipientEmail(),
        ];

        if ($this->uni_match_response_id && $this->uniMatchResponse) {
            $r = $this->uniMatchResponse;
            $ctx['response'] = $r;
            $ctx['recommendations'] = array_slice($r->recommendations ?? [], 0, 3);
            $ctx['returnUrl'] = url('/uni-match/result?t=' . $r->session_token);
            $ctx['resumeUrl'] = url('/uni-match/step/' . $r->current_step . '?t=' . $r->session_token);
            $ctx['currentStep'] = $r->current_step;
            $ctx['progressPct'] = (int) round(($r->current_step / max(1, $r->total_steps ?: 19)) * 100);
        }

        return $ctx;
    }
}

<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'name', 'type', 'category',
        'trigger_event', 'trigger_delay_minutes', 'trigger_conditions', 'trigger_is_active',
        'subject_tr', 'subject_de', 'subject_en',
        'body_tr', 'body_de', 'body_en',
        'available_placeholders', 'from_name', 'from_email', 'reply_to',
        'zoho_template_id', 'zoho_synced', 'zoho_last_sync_at',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'available_placeholders' => 'array',
        'trigger_is_active' => 'boolean',
        'zoho_synced' => 'boolean',
        'is_active' => 'boolean',
        'zoho_last_sync_at' => 'datetime',
        'stat_last_sent_at' => 'datetime',
    ];

    public function campaigns()
    {
        return $this->hasMany(EmailCampaign::class, 'template_id');
    }

    public function sendLogs()
    {
        return $this->hasMany(EmailSendLog::class, 'template_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeAutomated($q)
    {
        return $q->where('type', 'automated');
    }

    public function scopeManual($q)
    {
        return $q->where('type', 'manual');
    }

    public function subject(string $l = 'tr')
    {
        return $this->{"subject_{$l}"} ?? $this->subject_tr;
    }
}

<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class EmailSendLog extends Model
{
    public $timestamps = false;

    protected $table = 'email_send_log';

    protected $fillable = [
        'email_campaign_id', 'template_id', 'recipient_user_id', 'recipient_email',
        'subject', 'language', 'trigger_event', 'status', 'opened_at', 'clicked_at',
        'clicked_links', 'bounce_reason', 'sent_at', 'created_at',
    ];

    protected $casts = [
        'clicked_links' => 'array',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function scopeOpened($q)
    {
        return $q->whereNotNull('opened_at');
    }

    public function scopeBounced($q)
    {
        return $q->where('status', 'bounced');
    }
}

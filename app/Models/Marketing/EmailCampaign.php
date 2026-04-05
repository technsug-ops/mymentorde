<?php

namespace App\Models\Marketing;

use App\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    protected $fillable = [
        'name', 'template_id', 'segment_ids', 'linked_marketing_campaign_id',
        'scheduled_at', 'sent_at', 'status', 'total_recipients', 'recipient_snapshot',
        'zoho_campaign_id', 'created_by',
    ];

    protected $casts = [
        'segment_ids' => 'array',
        'recipient_snapshot' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function marketingCampaign()
    {
        return $this->belongsTo(MarketingCampaign::class, 'linked_marketing_campaign_id');
    }

    public function sendLogs()
    {
        return $this->hasMany(EmailSendLog::class, 'email_campaign_id');
    }

    public function scopeSent($q)
    {
        return $q->where('status', 'sent');
    }

    public function scopeDraft($q)
    {
        return $q->where('status', 'draft');
    }
}

<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class CampaignChannelPlan extends Model
{
    public $timestamps = false;

    protected $table = 'campaign_channel_plans';

    protected $fillable = [
        'campaign_id',
        'channel',
        'scheduled_at',
        'content_id',
        'content_type',
        'status',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sort_order'   => 'integer',
        'content_id'   => 'integer',
        'created_at'   => 'datetime',
    ];

    public static array $CHANNEL_LABELS = [
        'email'            => 'Email',
        'social_facebook'  => 'Facebook',
        'social_instagram' => 'Instagram',
        'social_linkedin'  => 'LinkedIn',
        'whatsapp'         => 'WhatsApp',
        'event'            => 'Etkinlik',
        'sms'              => 'SMS',
    ];

    public static array $STATUS_LABELS = [
        'planned'   => 'Planlandı',
        'scheduled' => 'Zamanlandı',
        'sent'      => 'Gönderildi',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal',
    ];
}

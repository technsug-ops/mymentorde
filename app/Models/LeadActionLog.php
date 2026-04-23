<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Manager/senior aksiyon logu — dashboard'tan alınan her "Ara/WhatsApp/Email/Not"
 * aksiyonu buraya kaydedilir. Takip + analytics için.
 */
class LeadActionLog extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'actor_user_id',
        'target_type',
        'target_id',
        'action_type',
        'template_id',
        'channel',
        'notes',
        'meta',
        'follow_up_at',
        'follow_up_sent',
    ];

    protected $casts = [
        'meta'            => 'array',
        'follow_up_at'    => 'datetime',
        'follow_up_sent'  => 'boolean',
    ];

    public const ACTION_TYPES = [
        'call', 'whatsapp', 'email', 'note', 'assign_senior',
        'payment_reminder', 'book_appointment', 'status_change', 'custom',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ActionTemplate::class);
    }
}

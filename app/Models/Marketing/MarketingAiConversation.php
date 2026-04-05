<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class MarketingAiConversation extends Model
{
    public $timestamps = false;

    protected $table = 'marketing_ai_conversations';

    protected $fillable = [
        'user_id',
        'context_type',
        'question',
        'answer',
        'tokens_used',
        'created_at',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'created_at'  => 'datetime',
    ];
}

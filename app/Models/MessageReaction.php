<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReaction extends Model
{
    public $timestamps = false;

    protected $fillable = ['message_id', 'user_id', 'emoji', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /** Geçerli emoji listesi */
    public const ALLOWED = ['👍', '❤️', '😂', '😮', '😢', '🎉'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

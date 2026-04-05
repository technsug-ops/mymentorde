<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $table = 'messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'reply_to_message_id',
        'attachment_path',
        'attachment_name',
        'attachment_size',
        'attachment_mime',
        'is_system',
        'is_edited',
        'edited_at',
        'created_at',
    ];

    protected $dates = ['created_at', 'edited_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'is_system'  => 'boolean',
            'is_edited'  => 'boolean',
            'created_at' => 'datetime',
            'edited_at'  => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id')->withTrashed();
    }

    /** Silinmiş mesajlar için görüntü metni */
    public function getDisplayBody(): string
    {
        if ($this->trashed()) {
            return '🚫 Bu mesaj silindi.';
        }
        return (string) $this->body;
    }

    public function hasAttachment(): bool
    {
        return (string) ($this->attachment_path ?? '') !== '';
    }
}

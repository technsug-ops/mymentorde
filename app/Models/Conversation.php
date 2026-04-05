<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    protected $table = 'conversations';

    protected $fillable = [
        'company_id',
        'type',
        'title',
        'created_by_user_id',
        'context_type',
        'context_id',
        'is_archived',
        'last_message_at',
        'last_message_preview',
    ];

    protected function casts(): array
    {
        return [
            'is_archived'     => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id');
    }

    public function participantUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'is_muted', 'is_pinned']);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id')->orderBy('created_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('participants', fn ($q) => $q->where('user_id', $userId));
    }

    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    public function isParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    /** DM konuşmasında diğer kişinin adını döner; grup konuşmalarında title */
    public function getDisplayTitle(int $currentUserId): string
    {
        if ($this->type !== 'direct') {
            return (string) ($this->title ?: 'Grup Konuşması');
        }

        $other = $this->participantUsers->firstWhere('id', '!=', $currentUserId);
        return $other ? (string) ($other->name ?? 'Bilinmeyen') : 'Direkt Mesaj';
    }
}

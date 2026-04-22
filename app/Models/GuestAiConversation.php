<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestAiConversation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'guest_application_id',
        'question',
        'answer',
        'context',
        'tokens_used',
        'tokens_input',
        'tokens_output',
        'response_mode',
        'cited_sources',
        'provider',
        'model',
        'role',
        'created_at',
    ];

    protected $casts = [
        'context'       => 'array',
        'cited_sources' => 'array',
        'tokens_used'   => 'integer',
        'tokens_input'  => 'integer',
        'tokens_output' => 'integer',
        'created_at'    => 'datetime',
    ];

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class);
    }

    public static function dailyCount(int $guestId): int
    {
        return static::where('guest_application_id', $guestId)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }
}

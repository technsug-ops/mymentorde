<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeniorAiConversation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'student_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function dailyCount(int $userId): int
    {
        return static::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }
}

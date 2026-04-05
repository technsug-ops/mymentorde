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
        'created_at',
    ];

    protected $casts = [
        'context'    => 'array',
        'created_at' => 'datetime',
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

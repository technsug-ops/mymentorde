<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class StaffAiConversation extends Model
{
    use BelongsToCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'role',
        'question',
        'answer',
        'context',
        'response_mode',
        'cited_sources',
        'tokens_input',
        'tokens_output',
        'tokens_used',
        'provider',
        'model',
        'created_at',
    ];

    protected $casts = [
        'context'       => 'array',
        'cited_sources' => 'array',
        'tokens_input'  => 'integer',
        'tokens_output' => 'integer',
        'tokens_used'   => 'integer',
        'created_at'    => 'datetime',
    ];

    public static function dailyCount(int $userId): int
    {
        return self::query()
            ->withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }
}

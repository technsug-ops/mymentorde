<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskActivityLog extends Model
{
    public const UPDATED_AT = null; // sadece created_at

    protected $fillable = [
        'task_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'task_id'  => 'integer',
            'user_id'  => 'integer',
            'metadata' => 'array',
        ];
    }

    public function task()
    {
        return $this->belongsTo(MarketingTask::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Aktivite log kaydı oluşturma yardımcısı */
    public static function record(
        int $taskId,
        ?int $userId,
        string $action,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'task_id'   => $taskId,
            'user_id'   => $userId,
            'action'    => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'metadata'  => $metadata,
        ]);
    }
}

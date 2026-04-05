<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'task_id',
        'user_id',
        'body',
        'attachment_path',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'task_id'     => 'integer',
            'user_id'     => 'integer',
            'is_internal' => 'boolean',
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
}

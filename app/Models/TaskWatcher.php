<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskWatcher extends Model
{
    public $timestamps = false;

    protected $table = 'task_watchers';

    protected $fillable = ['task_id', 'user_id', 'watched_at'];

    protected function casts(): array
    {
        return [
            'task_id'    => 'integer',
            'user_id'    => 'integer',
            'watched_at' => 'datetime',
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

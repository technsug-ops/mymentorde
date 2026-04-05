<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskChecklist extends Model
{
    protected $table = 'task_checklists';

    protected $fillable = [
        'task_id',
        'title',
        'is_done',
        'done_by_user_id',
        'done_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'task_id'          => 'integer',
            'is_done'          => 'boolean',
            'done_by_user_id'  => 'integer',
            'done_at'          => 'datetime',
            'sort_order'       => 'integer',
        ];
    }

    public function task()
    {
        return $this->belongsTo(MarketingTask::class, 'task_id');
    }

    public function doneBy()
    {
        return $this->belongsTo(User::class, 'done_by_user_id');
    }
}

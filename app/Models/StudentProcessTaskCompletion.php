<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProcessTaskCompletion extends Model
{
    protected $fillable = [
        'student_id',
        'task_id',
        'completed_at',
        'completed_by',
        'note',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProcessStepTask::class, 'task_id');
    }
}

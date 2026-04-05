<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessStepTask extends Model
{
    protected $fillable = [
        'process_definition_id',
        'label_tr',
        'label_de',
        'sort_order',
        'is_active',
        'is_required',
        'added_by',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_required' => 'boolean',
        'sort_order'  => 'integer',
    ];

    public function processDefinition(): BelongsTo
    {
        return $this->belongsTo(ProcessDefinition::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(StudentProcessTaskCompletion::class, 'task_id');
    }

    public function isCompletedByStudent(string $studentId): bool
    {
        return $this->completions()->where('student_id', $studentId)->whereNotNull('completed_at')->exists();
    }
}

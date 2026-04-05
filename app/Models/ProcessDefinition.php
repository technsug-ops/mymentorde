<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessDefinition extends Model
{
    protected $fillable = [
        'external_id',
        'code',
        'name_tr',
        'name_de',
        'name_en',
        'description_tr',
        'description_de',
        'description_en',
        'sort_order',
        'is_active',
        'is_mandatory',
        'applicable_student_types',
        'default_checklist',
        'revenue_milestone_id',
        'color',
        'icon',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
        'applicable_student_types' => 'array',
        'default_checklist' => 'array',
    ];

    public function stepTasks(): HasMany
    {
        return $this->hasMany(ProcessStepTask::class);
    }
}

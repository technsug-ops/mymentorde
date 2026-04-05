<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutomationWorkflow extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'description', 'status',
        'trigger_type', 'trigger_config', 'is_recurring',
        'enrollment_limit', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'trigger_config'   => 'array',
        'is_recurring'     => 'boolean',
        'approved_at'      => 'datetime',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(AutomationWorkflowNode::class, 'workflow_id')->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(AutomationEnrollment::class, 'workflow_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

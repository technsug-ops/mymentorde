<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationEnrollment extends Model
{
    protected $fillable = [
        'workflow_id', 'guest_application_id', 'current_node_id',
        'status', 'enrolled_at', 'next_check_at', 'completed_at',
        'exit_reason', 'metadata',
    ];

    protected $casts = [
        'metadata'      => 'array',
        'enrolled_at'   => 'datetime',
        'next_check_at' => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflow::class, 'workflow_id');
    }

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationEnrollmentLog::class, 'enrollment_id');
    }
}

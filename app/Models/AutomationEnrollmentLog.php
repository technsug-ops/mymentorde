<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationEnrollmentLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'enrollment_id', 'node_id', 'action', 'result', 'executed_at',
    ];

    protected $casts = [
        'result'      => 'array',
        'executed_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(AutomationEnrollment::class, 'enrollment_id');
    }
}

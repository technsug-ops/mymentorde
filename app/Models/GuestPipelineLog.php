<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestPipelineLog extends Model
{
    protected $fillable = [
        'guest_application_id',
        'from_stage',
        'to_stage',
        'moved_by_name',
        'moved_by_email',
        'contact_method',
        'contact_result',
        'lost_reason',
        'follow_up_date',
        'notes',
        'meta',
    ];

    protected $casts = [
        'meta'           => 'array',
        'follow_up_date' => 'date',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class, 'guest_application_id');
    }
}

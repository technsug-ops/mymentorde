<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDripStep extends Model
{
    public $timestamps = false;

    protected $table = 'email_drip_steps';

    protected $fillable = [
        'drip_sequence_id',
        'step_order',
        'delay_hours',
        'template_id',
        'subject_override',
        'is_active',
    ];

    protected $casts = [
        'step_order'  => 'integer',
        'delay_hours' => 'integer',
        'is_active'   => 'boolean',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(EmailDripSequence::class, 'drip_sequence_id');
    }
}

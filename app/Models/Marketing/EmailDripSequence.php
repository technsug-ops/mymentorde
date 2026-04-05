<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailDripSequence extends Model
{
    public $timestamps = false;

    protected $table = 'email_drip_sequences';

    protected $fillable = [
        'name',
        'description',
        'trigger_event',
        'segment_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(EmailDripStep::class, 'drip_sequence_id')->orderBy('step_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(EmailDripEnrollment::class, 'drip_sequence_id');
    }
}

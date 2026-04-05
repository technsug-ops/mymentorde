<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessOutcome extends Model
{
    protected $fillable = [
        'student_id',
        'application_id',
        'process_step',
        'outcome_type',
        'university',
        'program',
        'document_id',
        'details_tr',
        'details_de',
        'details_en',
        'conditions',
        'deadline',
        'is_visible_to_student',
        'made_visible_at',
        'made_visible_by',
        'student_notified',
        'notified_at',
        'added_by',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'is_visible_to_student' => 'boolean',
        'made_visible_at' => 'datetime',
        'student_notified' => 'boolean',
        'notified_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}

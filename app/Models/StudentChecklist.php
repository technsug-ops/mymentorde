<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentChecklist extends Model
{
    protected $fillable = [
        'student_id',
        'company_id',
        'label',
        'description',
        'category',
        'is_done',
        'done_at',
        'due_date',
        'sort_order',
        'created_by_email',
    ];

    protected $casts = [
        'is_done'  => 'boolean',
        'done_at'  => 'datetime',
        'due_date' => 'date',
    ];

    public const CATEGORIES = [
        'registration' => 'Kayıt',
        'document'     => 'Belge',
        'visa'         => 'Vize',
        'housing'      => 'Konut',
        'language'     => 'Dil',
        'general'      => 'Genel',
    ];
}

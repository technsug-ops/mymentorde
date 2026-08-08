<?php

namespace App\Models;

use App\Models\Concerns\OwnedBySubjectCompany;
use App\Models\Contracts\ResolvesOwnCompany;
use Illuminate\Database\Eloquent\Model;

class StudentChecklist extends Model implements ResolvesOwnCompany
{
    use OwnedBySubjectCompany;

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

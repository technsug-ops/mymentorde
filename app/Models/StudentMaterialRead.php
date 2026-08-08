<?php

namespace App\Models;

use App\Models\Concerns\OwnedBySubjectCompany;
use App\Models\Contracts\ResolvesOwnCompany;
use Illuminate\Database\Eloquent\Model;

class StudentMaterialRead extends Model implements ResolvesOwnCompany
{
    use OwnedBySubjectCompany;

    protected $fillable = [
        'company_id',
        'student_id',
        'knowledge_base_article_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];
}


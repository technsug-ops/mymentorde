<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessContractTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'contract_type',
        'template_code',
        'name',
        'body_text',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

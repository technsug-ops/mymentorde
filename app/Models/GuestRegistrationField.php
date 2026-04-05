<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class GuestRegistrationField extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'section_key',
        'section_title',
        'section_order',
        'field_key',
        'label',
        'type',
        'is_required',
        'sort_order',
        'max_length',
        'placeholder',
        'help_text',
        'options_json',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'section_order' => 'integer',
        'sort_order' => 'integer',
        'max_length' => 'integer',
        'options_json' => 'array',
    ];
}


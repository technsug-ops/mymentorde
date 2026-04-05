<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataRetentionPolicy extends Model
{
    protected $fillable = [
        'company_id',
        'entity_type',
        'anonymize_after_days',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'company_id'           => 'integer',
            'anonymize_after_days' => 'integer',
            'is_active'            => 'boolean',
        ];
    }
}

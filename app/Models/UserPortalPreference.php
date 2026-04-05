<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPortalPreference extends Model
{
    protected $fillable = [
        'user_id',
        'portal_key',
        'preferences_json',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'preferences_json' => 'array',
        ];
    }
}

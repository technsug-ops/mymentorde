<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpAccessRule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'rule_type', 'ip_range', 'description',
        'applies_to_roles', 'is_active', 'created_by',
    ];

    protected $casts = [
        'applies_to_roles' => 'array',
        'is_active'        => 'boolean',
    ];
}

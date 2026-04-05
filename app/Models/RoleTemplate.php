<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'parent_role',
        'version',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'version' => 'integer',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_template_permissions')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->hasMany(UserRoleAssignment::class, 'role_template_id');
    }
}

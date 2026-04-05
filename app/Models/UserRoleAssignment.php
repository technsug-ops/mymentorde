<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'role_template_id',
        'assigned_by_user_id',
        'version_applied',
        'is_active',
        'assigned_at',
        'revoked_at',
    ];

    protected $casts = [
        'version_applied' => 'integer',
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(RoleTemplate::class, 'role_template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

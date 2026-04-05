<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountVault extends Model
{
    protected $table = 'account_vaults';

    protected $fillable = [
        'student_id',
        'service_name',
        'service_label',
        'account_url',
        'account_email',
        'account_username',
        'account_password_encrypted',
        'application_id',
        'notes',
        'status',
        'is_visible_to_student',
        'created_by',
    ];

    protected $casts = [
        'is_visible_to_student' => 'boolean',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountAccessLog extends Model
{
    protected $fillable = [
        'account_id',
        'student_id',
        'accessed_by',
        'access_type',
        'ip_address',
        'accessed_at',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];
}

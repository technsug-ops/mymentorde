<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerTypeHistory extends Model
{
    protected $fillable = [
        'dealer_id',
        'dealer_code',
        'old_type_code',
        'new_type_code',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];
}

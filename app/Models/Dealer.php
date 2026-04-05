<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dealer extends Model
{
    use BelongsToCompany, SoftDeletes;
    protected $fillable = [
        'company_id',
        'code',
        'internal_sequence',
        'name',
        'email',
        'phone',
        'whatsapp',
        'dealer_type_code',
        'is_active',
        'is_archived',
        'archived_by',
        'archived_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];
}

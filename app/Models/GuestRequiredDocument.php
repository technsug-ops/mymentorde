<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class GuestRequiredDocument extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'application_type',
        'document_code',
        'category_code',
        'top_category_code',
        'name',
        'name_de',
        'uni_assist_category',
        'description',
        'is_required',
        'accepted',
        'max_mb',
        'sort_order',
        'is_active',
        'stage',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'max_mb' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}

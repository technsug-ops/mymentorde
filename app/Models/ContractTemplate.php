<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'version',
        'parent_version_id',
        'change_log',
        'is_active',
        'body_text',
        'annex_kvkk_text',
        'annex_commitment_text',
        'annex_payment_text',
        'print_header_html',
        'print_footer_html',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
    ];
}


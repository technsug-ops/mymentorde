<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessingActivity extends Model
{
    use BelongsToCompany, SoftDeletes;

    public const LEGAL_BASIS = [
        'art_6_1_a' => 'Art. 6(1)(a) — Açık rıza',
        'art_6_1_b' => 'Art. 6(1)(b) — Sözleşme ifası',
        'art_6_1_c' => 'Art. 6(1)(c) — Yasal yükümlülük',
        'art_6_1_d' => 'Art. 6(1)(d) — Hayati menfaat',
        'art_6_1_e' => 'Art. 6(1)(e) — Kamu görevi',
        'art_6_1_f' => 'Art. 6(1)(f) — Meşru menfaat',
        'art_9_2_a' => 'Art. 9(2)(a) — Hassas veri açık rızası',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'responsible',
        'purpose',
        'data_categories',
        'subject_categories',
        'recipients',
        'legal_basis',
        'third_country_transfer',
        'third_country_country',
        'retention_days',
        'security_measures',
        'notes',
        'is_active',
        'reviewed_at',
        'reviewed_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'data_categories'        => 'array',
        'subject_categories'     => 'array',
        'recipients'             => 'array',
        'third_country_transfer' => 'boolean',
        'is_active'              => 'boolean',
        'reviewed_at'            => 'datetime',
        'retention_days'         => 'integer',
    ];
}

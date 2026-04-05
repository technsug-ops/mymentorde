<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ABTestVariant extends Model
{
    protected $table = 'ab_test_variants';

    protected $fillable = [
        'ab_test_id', 'variant_code', 'variant_config',
        'impressions', 'conversions', 'conversion_rate',
    ];

    protected $casts = [
        'variant_config'  => 'array',
        'conversion_rate' => 'float',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(ABTest::class, 'ab_test_id');
    }
}

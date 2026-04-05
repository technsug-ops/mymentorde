<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ABTest extends Model
{
    use SoftDeletes;

    protected $table = 'ab_tests';

    protected $fillable = [
        'company_id', 'name', 'test_type', 'status', 'traffic_split',
        'primary_metric', 'min_sample_size', 'confidence_level',
        'auto_winner', 'winner_variant', 'created_by', 'approved_by',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'traffic_split'    => 'array',
        'confidence_level' => 'float',
        'auto_winner'      => 'boolean',
        'started_at'       => 'datetime',
        'completed_at'     => 'datetime',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ABTestVariant::class, 'ab_test_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ABTestAssignment::class, 'ab_test_id');
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function getTotalSampleAttribute(): int
    {
        return $this->variants->sum('impressions');
    }
}

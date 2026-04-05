<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadScoringRule extends Model
{
    protected $fillable = [
        'action_code', 'category', 'label', 'points',
        'max_per_day', 'is_one_time', 'is_active', 'updated_by',
    ];

    protected $casts = [
        'is_one_time' => 'boolean',
        'is_active'   => 'boolean',
        'points'      => 'integer',
        'max_per_day' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}

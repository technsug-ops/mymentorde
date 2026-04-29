<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Expatrio Study Buddy program kataloğu (~13,318 Almanya programı).
 * Form'da "hedef program" autocomplete + manager rehberlik için kaynak.
 *
 * Önemli alanlar:
 *  - id              : Expatrio UUID (form value olarak saklanır)
 *  - course_name     : program adı (örn. "Agrobioinformatics")
 *  - university_name : denormalize (sorgu hızı için)
 *  - languages       : JSON ["English", "German"]
 *  - data            : tam Expatrio JSON — deadline, requirements, costs vb.
 */
class ExpatrioProgram extends Model
{
    protected $table = 'expatrio_programs';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'university_id', 'university_name', 'course_name',
        'degree_specification', 'location',
        'languages', 'study_fields', 'subjects',
        'semester_count', 'tuition_fees_per_semester',
        'data', 'synced_at',
    ];

    protected $casts = [
        'languages'                  => 'array',
        'study_fields'               => 'array',
        'subjects'                   => 'array',
        'data'                       => 'array',
        'semester_count'             => 'integer',
        'tuition_fees_per_semester'  => 'integer',
        'synced_at'                  => 'datetime',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(ExpatrioUniversity::class, 'university_id');
    }

    /** Search scope — autocomplete için (LIKE university_name OR course_name). */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') return $query;
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
        return $query->where(function (Builder $q) use ($like) {
            $q->where('university_name', 'like', $like)
              ->orWhere('course_name', 'like', $like);
        });
    }
}

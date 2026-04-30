<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Canonical Almanya program kataloğu (kaynak-bağımsız).
 *
 * İZOLASYON: SHARED — company_id YOK. Referans veri.
 *
 * Stratejik yapı:
 *  - Programs tablosu canonical schema (Expatrio/HK farketmez)
 *  - Source linkleri program_source_links'te (1:N)
 *  - Manuel curation öncelikli — is_manually_curated=true ise re-sync override etmez
 *  - quality_score field doluluk oranını gösterir (0-100)
 */
class Program extends Model
{
    use HasUuids;

    protected $table = 'programs';

    protected $fillable = [
        'id', 'university_id', 'university_name_cached',
        'course_name', 'degree_type', 'degree_specification',
        'language', 'languages_raw', 'location',
        'duration_semesters', 'tuition_eur_per_semester',
        'application_deadline_summer', 'application_deadline_winter',
        'admission_type', 'nc_value',
        'study_fields', 'subjects',
        'quality_score', 'metadata',
        'is_manually_curated', 'is_active',
    ];

    protected $casts = [
        'languages_raw'                => 'array',
        'study_fields'                 => 'array',
        'subjects'                     => 'array',
        'metadata'                     => 'array',
        'application_deadline_summer'  => 'date',
        'application_deadline_winter'  => 'date',
        'duration_semesters'           => 'integer',
        'tuition_eur_per_semester'     => 'integer',
        'quality_score'                => 'integer',
        'is_manually_curated'          => 'boolean',
        'is_active'                    => 'boolean',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'university_id');
    }

    public function sourceLinks(): HasMany
    {
        return $this->hasMany(ProgramSourceLink::class, 'program_id');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(ProgramChangeLog::class, 'program_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** LIKE search scope — autocomplete için. */
    public function scopeSearch(Builder $q, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') return $q;
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
        return $q->where(function (Builder $sub) use ($like) {
            $sub->where('university_name_cached', 'like', $like)
                ->orWhere('course_name', 'like', $like);
        });
    }

    /** Quality score otomatik hesap — kaç field dolu? */
    public function recomputeQualityScore(): int
    {
        $fields = [
            'course_name', 'university_name_cached', 'degree_type', 'language',
            'location', 'duration_semesters', 'tuition_eur_per_semester',
            'application_deadline_winter', 'admission_type',
        ];
        $filled = 0;
        foreach ($fields as $f) {
            $v = $this->{$f};
            if ($v !== null && $v !== '' && $v !== []) $filled++;
        }
        $score = (int) round(($filled / count($fields)) * 100);
        $this->quality_score = $score;
        return $score;
    }
}

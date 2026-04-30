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
        'duration_semesters',
        'tuition_eur_per_semester', 'application_fee_eur', 'cost_per_semester_eur',
        'application_deadline_summer', 'application_deadline_winter',
        'admission_type', 'nc_value',
        'study_fields', 'subjects',
        'description', 'qualification_requirements', 'language_requirements', 'required_documents',
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
        'application_fee_eur'          => 'integer',
        'cost_per_semester_eur'        => 'integer',
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
            // Core (search'te de gelir, hep dolu)
            'course_name', 'university_name_cached', 'degree_type', 'language',
            // Detail-only (--details flag ile dolar)
            'location', 'duration_semesters',
            'application_deadline_winter', 'application_deadline_summer',
            'description', 'qualification_requirements',
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

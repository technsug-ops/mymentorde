<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentInstitutionDocument extends Model
{
    use SoftDeletes;

    protected $table = 'student_institution_documents';

    protected $fillable = [
        'company_id',
        'student_id',
        'institution_category',
        'document_type_code',
        'document_type_label',
        'institution_name',
        'received_date',
        'status',
        'notes',
        'file_id',
        'is_visible_to_student',
        'is_visible_to_dealer',
        'made_visible_at',
        'made_visible_by',
        'added_by',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'is_visible_to_student' => 'boolean',
        'is_visible_to_dealer'  => 'boolean',
        'received_date'         => 'date',
        'made_visible_at'       => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function file(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'file_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeVisibleToStudent(Builder $query): Builder
    {
        return $query->where('is_visible_to_student', true);
    }

    public function scopeVisibleToDealer(Builder $query): Builder
    {
        return $query->where('is_visible_to_dealer', true);
    }

    public function scopeForStudent(Builder $query, string $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Durum badgesi CSS sınıfı */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'received'        => 'ok',
            'completed'       => 'ok',
            'expected'        => 'info',
            'action_required' => 'warn',
            'archived'        => 'pending',
            default           => 'info',
        };
    }
}

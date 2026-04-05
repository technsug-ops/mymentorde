<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentUniversityApplication extends Model
{
    use SoftDeletes;

    protected $table = 'student_university_applications';

    protected $fillable = [
        'company_id',
        'student_id',
        'university_code',
        'university_name',
        'city',
        'state',
        'department_code',
        'department_name',
        'degree_type',
        'semester',
        'application_portal',
        'application_number',
        'status',
        'priority',
        'deadline',
        'submitted_at',
        'result_at',
        'notes',
        'is_visible_to_student',
        'is_visible_to_dealer',
        'added_by',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'is_visible_to_student' => 'boolean',
        'is_visible_to_dealer'  => 'boolean',
        'deadline'              => 'date',
        'submitted_at'          => 'date',
        'result_at'             => 'date',
        'priority'              => 'integer',
    ];

    // ── Sabitler ──────────────────────────────────────────────────────────────

    public const STATUS_LABELS = [
        'planned'               => 'Planlandı',
        'submitted'             => 'Gönderildi',
        'under_review'          => 'İnceleniyor',
        'accepted'              => 'Kabul',
        'conditional_accepted'  => 'Şartlı Kabul',
        'rejected'              => 'Ret',
        'withdrawn'             => 'Geri Çekildi',
    ];

    public const STATUS_BADGE = [
        'planned'               => 'info',
        'submitted'             => 'info',
        'under_review'          => 'warn',
        'accepted'              => 'ok',
        'conditional_accepted'  => 'warn',
        'rejected'              => 'danger',
        'withdrawn'             => '',
    ];

    public const DEGREE_LABELS = [
        'bachelor'      => 'Lisans (B.Sc./B.A.)',
        'master'        => 'Yüksek Lisans (M.Sc./M.A.)',
        'phd'           => 'Doktora',
        'ausbildung'    => 'Ausbildung',
        'weiterbildung' => 'Weiterbildung',
    ];

    public const PORTAL_LABELS = [
        'uni_assist'     => 'Uni-Assist',
        'hochschulstart' => 'Hochschulstart',
        'direct'         => 'Direkt Başvuru',
        'other'          => 'Diğer',
    ];

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['planned', 'submitted', 'under_review', 'conditional_accepted']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return self::STATUS_BADGE[$this->status] ?? 'info';
    }

    public function degreeLabel(): string
    {
        return self::DEGREE_LABELS[$this->degree_type] ?? $this->degree_type;
    }

    public function portalLabel(): string
    {
        return self::PORTAL_LABELS[$this->application_portal ?? ''] ?? ($this->application_portal ?? '–');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['planned', 'submitted', 'under_review', 'conditional_accepted']);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

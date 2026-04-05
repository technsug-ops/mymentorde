<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversityRequirementMap extends Model
{
    protected $table = 'university_requirement_maps';

    protected $fillable = [
        'university_code',
        'department_code',
        'degree_type',
        'semester',
        'portal_name',
        'deadline_month_ws',
        'deadline_day_ws',
        'deadline_month_ss',
        'deadline_day_ss',
        'required_document_codes',
        'recommended_document_codes',
        'language_requirement',
        'min_gpa',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'required_document_codes'     => 'array',
        'recommended_document_codes'  => 'array',
        'is_active'                   => 'boolean',
        'min_gpa'                     => 'float',
        'deadline_month_ws'           => 'integer',
        'deadline_day_ws'             => 'integer',
        'deadline_month_ss'           => 'integer',
        'deadline_day_ss'             => 'integer',
    ];

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Tarih stringi: "15 Ocak" gibi */
    public function deadlineWsLabel(): string
    {
        if (!$this->deadline_month_ws) {
            return '–';
        }
        $months = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
                   'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        $day = $this->deadline_day_ws ? $this->deadline_day_ws . ' ' : '';
        return $day . ($months[$this->deadline_month_ws] ?? '');
    }

    public function deadlineSsLabel(): string
    {
        if (!$this->deadline_month_ss) {
            return '–';
        }
        $months = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
                   'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        $day = $this->deadline_day_ss ? $this->deadline_day_ss . ' ' : '';
        return $day . ($months[$this->deadline_month_ss] ?? '');
    }

    /** Üniversite adını katalogdan çek */
    public function universityNameTr(): string
    {
        $catalog = config('university_catalog.universities', []);
        return $catalog[$this->university_code]['name_tr'] ?? $this->university_code;
    }

    /** Bölüm adını katalogdan çek */
    public function departmentNameTr(): string
    {
        if (!$this->department_code) {
            return 'Genel (Tüm Bölümler)';
        }
        $catalog = config('university_catalog.universities', []);
        return $catalog[$this->university_code]['departments'][$this->department_code]['name_tr']
            ?? $this->department_code;
    }

    /** Belge etiketini APP-* katalogdan çek */
    public static function docLabel(string $code): string
    {
        $docs = config('university_application_documents.documents', []);
        return $docs[$code]['label_tr'] ?? $code;
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scope ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUniversity($query, string $uniCode)
    {
        return $query->where('university_code', $uniCode);
    }

    public function scopeForDepartment($query, ?string $deptCode)
    {
        return $query->where('department_code', $deptCode);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLanguageCourse extends Model
{
    protected $fillable = [
        'student_id',
        'school_name',
        'city',
        'country',
        'course_type',
        'level_target',
        'level_achieved',
        'start_date',
        'end_date',
        'certificate_status',
        'notes',
        'is_visible_to_student',
        'company_id',
        'added_by',
    ];

    protected $casts = [
        'start_date'            => 'date',
        'end_date'              => 'date',
        'is_visible_to_student' => 'boolean',
    ];

    public const COURSE_TYPE_LABELS = [
        'DSH'     => 'DSH',
        'TestDaF' => 'TestDaF',
        'Goethe'  => 'Goethe-Zertifikat',
        'other'   => 'Diğer',
    ];

    public const CERT_STATUS_LABELS = [
        'none'      => 'Henüz yok',
        'pending'   => 'Bekleniyor',
        'received'  => 'Alındı',
        'submitted' => 'Teslim Edildi',
    ];

    public const CERT_STATUS_BADGE = [
        'none'      => 'pending',
        'pending'   => 'warn',
        'received'  => 'info',
        'submitted' => 'ok',
    ];
}

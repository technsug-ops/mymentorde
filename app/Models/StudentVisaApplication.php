<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentVisaApplication extends Model
{
    use SoftDeletes;

    protected $table = 'student_visa_applications';

    protected $fillable = [
        'company_id',
        'student_id',
        'visa_type',
        'status',
        'application_date',
        'appointment_date',
        'decision_date',
        'valid_from',
        'valid_until',
        'visa_number',
        'consulate_city',
        'submitted_documents',
        'notes',
        'rejection_reason',
        'is_visible_to_student',
        'added_by',
    ];

    protected $casts = [
        'application_date'       => 'date',
        'appointment_date'       => 'date',
        'decision_date'          => 'date',
        'valid_from'             => 'date',
        'valid_until'            => 'date',
        'submitted_documents'    => 'array',
        'is_visible_to_student'  => 'boolean',
    ];

    public const VISA_TYPE_LABELS = [
        'national_d'     => 'Ulusal Vize (D tipi)',
        'student_visa'   => 'Öğrenci Vizesi',
        'language_course'=> 'Dil Kursu Vizesi',
        'other'          => 'Diğer',
    ];

    public const STATUS_LABELS = [
        'not_started' => 'Başlanmadı',
        'preparing'   => 'Hazırlık',
        'submitted'   => 'Gönderildi',
        'in_review'   => 'İnceleniyor',
        'approved'    => 'Onaylandı',
        'rejected'    => 'Reddedildi',
        'expired'     => 'Süresi Doldu',
    ];

    public const STATUS_BADGE = [
        'not_started' => '',
        'preparing'   => 'info',
        'submitted'   => 'info',
        'in_review'   => 'warn',
        'approved'    => 'ok',
        'rejected'    => 'danger',
        'expired'     => 'danger',
    ];

    public const CONSULATE_CITIES = [
        'Istanbul' => 'İstanbul',
        'Ankara'   => 'Ankara',
        'Izmir'    => 'İzmir',
        'Other'    => 'Diğer',
    ];

    public const COMMON_DOCUMENTS = [
        'passport'         => 'Pasaport',
        'photo'            => 'Biyometrik Fotoğraf',
        'enrollment_proof' => 'Kayıt Belgesi',
        'financial_proof'  => 'Maddi Yeterlilik Belgesi',
        'health_insurance' => 'Sağlık Sigortası',
        'housing_contract' => 'Kira Sözleşmesi',
        'cv'               => 'Özgeçmiş (CV)',
        'language_cert'    => 'Dil Belgesi',
        'birth_cert'       => 'Nüfus Cüzdanı / Doğum Belgesi',
        'transcript'       => 'Transkript',
    ];

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGE[$this->status] ?? '';
    }

    public function visaTypeLabel(): string
    {
        return self::VISA_TYPE_LABELS[$this->visa_type] ?? $this->visa_type;
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

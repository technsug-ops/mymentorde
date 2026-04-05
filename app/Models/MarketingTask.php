<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingTask extends Model
{
    use BelongsToCompany, SoftDeletes;

    /** Geçerli durum geçişleri — [mevcut => izin verilen hedefler] */
    public const ALLOWED_TRANSITIONS = [
        'todo'        => ['in_progress', 'cancelled'],
        'in_progress' => ['in_review', 'on_hold', 'blocked', 'cancelled'],
        'in_review'   => ['done', 'in_progress'],          // done: sadece admin/super
        'on_hold'     => ['in_progress', 'cancelled'],
        'blocked'     => ['in_progress', 'cancelled'],
        'done'        => ['todo'],                          // reopen
        'cancelled'   => ['todo'],                          // reopen
    ];

    /** SLA sayacı aktif olan durumlar */
    public const SLA_ACTIVE_STATUSES = ['todo', 'in_progress'];

    /** Süreç tipleri — ClickUp CRM alanlarına karşılık gelir */
    public const PROCESS_TYPES = [
        'guest_intake'        => 'Danışan Kabul',
        'document_management' => 'Evrak Yönetimi',
        'language_course'     => 'Dil Kursu',
        'uni_assist'          => 'Uni Assist',
        'visa_application'    => 'Vize Başvurusu',
        'residence_permit'    => 'Oturum ve İkamet',
    ];

    /** Süreç aşamaları — process_type'a bağlı dinamik filtreleme için */
    public const WORKFLOW_STAGES = [
        'guest_intake'        => [
            'intake_received'   => 'Başvuru Alındı',
            'meeting_scheduled' => 'Görüşme Planlandı',
            'needs_assessment'  => 'İhtiyaç Analizi',
            'contract_prep'     => 'Sözleşme Hazırlığı',
            'onboarding'        => 'Onboarding',
        ],
        'document_management' => [
            'doc_collection' => 'Evrak Toplama',
            'doc_review'     => 'İnceleme',
            'doc_correction' => 'Düzeltme',
            'doc_submission' => 'Teslim',
            'doc_received'   => 'Alındı',
        ],
        'language_course'     => [
            'lang_enrollment'  => 'Kayıt',
            'lang_payment'     => 'Ödeme',
            'lang_in_progress' => 'Devam Ediyor',
            'lang_exam'        => 'Sınav',
            'lang_certified'   => 'Sertifika',
        ],
        'uni_assist'          => [
            'ua_prep'         => 'Hazırlık',
            'ua_submitted'    => 'Gönderildi',
            'ua_under_review' => 'İncelemede',
            'ua_result'       => 'Sonuç Alındı',
            'ua_next_steps'   => 'Sonraki Adım',
        ],
        'visa_application'    => [
            'visa_appointment'   => 'Randevu',
            'visa_docs_prepared' => 'Evrak Hazır',
            'visa_submitted'     => 'Başvuru Yapıldı',
            'visa_biometrics'    => 'Biyometrik',
            'visa_approved'      => 'Onaylandı',
            'visa_rejected'      => 'Reddedildi',
        ],
        'residence_permit'    => [
            'res_registration' => 'Tescil',
            'res_appointment'  => 'Randevu',
            'res_documents'    => 'Evraklar',
            'res_result'       => 'Sonuç',
            'res_completed'    => 'Tamamlandı',
        ],
    ];

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'status',
        'priority',
        'department',
        'process_type',
        'workflow_stage',
        'due_date',
        'assigned_user_id',
        'created_by_user_id',
        'completed_at',
        'hold_reason',
        'review_requested_at',
        'cancelled_at',
        'cancelled_by_user_id',
        'checklist_total',
        'checklist_done',
        'template_id',
        'estimated_hours',
        'actual_hours',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_interval_days',
        'next_run_at',
        'escalate_after_hours',
        'last_escalated_at',
        'escalation_level',
        'parent_task_id',
        'depends_on_task_id',
        'is_auto_generated',
        'source_type',
        'source_id',
        'related_student_id',
        'column_order',
        'start_date',
        'mentioned_user_ids',
        'progress',
    ];

    protected function casts(): array
    {
        return [
            'company_id'               => 'integer',
            'assigned_user_id'         => 'integer',
            'created_by_user_id'       => 'integer',
            'cancelled_by_user_id'     => 'integer',
            'checklist_total'          => 'integer',
            'checklist_done'           => 'integer',
            'template_id'              => 'integer',
            'estimated_hours'          => 'decimal:2',
            'actual_hours'             => 'decimal:2',
            'department'               => 'string',
            'due_date'                 => 'date',
            'completed_at'             => 'datetime',
            'review_requested_at'      => 'datetime',
            'cancelled_at'             => 'datetime',
            'is_recurring'             => 'boolean',
            'recurrence_interval_days' => 'integer',
            'next_run_at'              => 'datetime',
            'escalate_after_hours'     => 'integer',
            'last_escalated_at'        => 'datetime',
            'escalation_level'         => 'integer',
            'parent_task_id'           => 'integer',
            'depends_on_task_id'       => 'integer',
            'is_auto_generated'        => 'boolean',
            'column_order'             => 'integer',
            'start_date'               => 'date',
            'mentioned_user_ids'       => 'array',
            'progress'                 => 'integer',
        ];
    }

    /** Verilen duruma geçiş geçerli mi? */
    public function canTransitionTo(string $newStatus): bool
    {
        $current = (string) $this->status;
        return in_array($newStatus, self::ALLOWED_TRANSITIONS[$current] ?? [], true);
    }

    /** Priority bazlı varsayılan SLA saatleri */
    public static function defaultSlaHours(string $priority): int
    {
        return match ($priority) {
            'urgent' => 4,
            'high'   => 12,
            'normal' => 24,
            'low'    => 72,
            default  => 24,
        };
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function parentTask()
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function dependsOn()
    {
        return $this->belongsTo(self::class, 'depends_on_task_id');
    }

    public function dependents()
    {
        return $this->hasMany(self::class, 'depends_on_task_id');
    }

    public function subtasks()
    {
        return $this->hasMany(self::class, 'parent_task_id')
            ->orderByRaw("CASE WHEN status='done' THEN 1 ELSE 0 END")
            ->orderBy('id');
    }

    public function template()
    {
        return $this->belongsTo(TaskTemplate::class, 'template_id');
    }

    public function watchers()
    {
        return $this->hasMany(TaskWatcher::class, 'task_id');
    }

    public function watcherUsers()
    {
        return $this->belongsToMany(User::class, 'task_watchers', 'task_id', 'user_id')
            ->withPivot('watched_at');
    }

    public function checklists()
    {
        return $this->hasMany(TaskChecklist::class, 'task_id')->orderBy('sort_order')->orderBy('id');
    }

    /** Checklist ilerleme yüzdesi (0–100) */
    public function getChecklistProgressAttribute(): int
    {
        if ((int) $this->checklist_total === 0) {
            return 0;
        }
        return (int) round(((int) $this->checklist_done / (int) $this->checklist_total) * 100);
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id')->orderBy('created_at');
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id')->orderBy('id');
    }

    public function activityLogs()
    {
        return $this->hasMany(TaskActivityLog::class, 'task_id')->orderByDesc('created_at');
    }

    public function isSlaBreached(): bool
    {
        if (in_array($this->status, ['done', 'cancelled'], true)) {
            return false;
        }
        if (! $this->due_date) {
            return false;
        }
        return $this->due_date->isPast();
    }

    public function slaBadge(): string
    {
        if (in_array($this->status, ['done', 'cancelled'], true)) {
            return 'ok';
        }
        if (! $this->due_date) {
            return 'info';
        }
        if ($this->due_date->isToday()) {
            return 'warn';
        }
        if ($this->due_date->isPast()) {
            return 'danger';
        }
        return 'info';
    }
}

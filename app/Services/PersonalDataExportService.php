<?php

namespace App\Services;

use App\Models\ConsentRecord;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\StudentAppointment;
use App\Models\StudentAssignment;
use App\Models\User;

/**
 * GDPR Madde 20 — Veri Taşınabilirliği
 *
 * Kullanıcının veya başvuru sahibinin kişisel verilerini
 * makine okunabilir JSON formatında dışa aktarır.
 */
class PersonalDataExportService
{
    /**
     * Student rolü için kişisel veri paketi oluşturur.
     */
    public function exportForStudent(User $user): array
    {
        $studentId = (string) ($user->student_id ?? '');

        $assignment = StudentAssignment::query()
            ->where('student_id', $studentId)
            ->first();

        $guestApp = GuestApplication::withTrashed()
            ->where('converted_student_id', $studentId)
            ->first();

        $documents = Document::query()
            ->where('student_id', $studentId)
            ->get(['id', 'document_category_code', 'original_filename', 'status', 'created_at']);

        $appointments = StudentAppointment::query()
            ->where('student_id', $studentId)
            ->get(['id', 'title', 'scheduled_at', 'status', 'created_at']);

        $consents = ConsentRecord::query()
            ->where('user_id', $user->id)
            ->orWhere('application_id', $guestApp?->id)
            ->orderBy('accepted_at')
            ->get(['consent_type', 'version', 'accepted_at']);

        return [
            'meta' => [
                'export_date'   => now()->toIso8601String(),
                'subject'       => 'MentorDE — Kişisel Veri Dışa Aktarma',
                'gdpr_article'  => 'Madde 20 — Veri Taşınabilirliği',
                'data_format'   => 'application/json',
            ],
            'user_profile' => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'role'             => $user->role,
                'student_id'       => $user->student_id,
                'is_active'        => $user->is_active,
                'created_at'       => $user->created_at?->toIso8601String(),
            ],
            'student_assignment' => $assignment ? [
                'student_id'     => $assignment->student_id,
                'senior_email'   => $assignment->senior_email,
                'branch'         => $assignment->branch,
                'risk_level'     => $assignment->risk_level,
                'payment_status' => $assignment->payment_status,
                'student_type'   => $assignment->student_type,
                'is_archived'    => $assignment->is_archived,
                'created_at'     => $assignment->created_at?->toIso8601String(),
            ] : null,
            'original_application' => $guestApp ? $this->mapGuestApp($guestApp) : null,
            'documents' => $documents->map(fn ($d) => [
                'category'       => $d->document_category_code,
                'filename'       => $d->original_filename,
                'status'         => $d->status,
                'uploaded_at'    => $d->created_at?->toIso8601String(),
            ])->values()->all(),
            'appointments' => $appointments->map(fn ($a) => [
                'title'        => $a->title,
                'scheduled_at' => $a->scheduled_at?->toIso8601String(),
                'status'       => $a->status,
                'created_at'   => $a->created_at?->toIso8601String(),
            ])->values()->all(),
            'consent_records' => $consents->map(fn ($c) => [
                'consent_type' => $c->consent_type,
                'version'      => $c->version,
                'accepted_at'  => $c->accepted_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * Guest başvuru sahibi için kişisel veri paketi oluşturur.
     */
    public function exportForGuest(User $user, GuestApplication $app): array
    {
        $tickets = GuestTicket::query()
            ->where('guest_application_id', $app->id)
            ->get(['id', 'subject', 'status', 'priority', 'created_at']);

        $documents = Document::query()
            ->where('guest_application_id', $app->id)
            ->get(['id', 'document_category_code', 'original_filename', 'status', 'created_at']);

        $consents = ConsentRecord::query()
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('application_id', $app->id))
            ->orderBy('accepted_at')
            ->get(['consent_type', 'version', 'accepted_at']);

        return [
            'meta' => [
                'export_date'   => now()->toIso8601String(),
                'subject'       => 'MentorDE — Kişisel Veri Dışa Aktarma',
                'gdpr_article'  => 'Madde 20 — Veri Taşınabilirliği',
                'data_format'   => 'application/json',
            ],
            'user_profile' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'application' => $this->mapGuestApp($app),
            'documents' => $documents->map(fn ($d) => [
                'category'    => $d->document_category_code,
                'filename'    => $d->original_filename,
                'status'      => $d->status,
                'uploaded_at' => $d->created_at?->toIso8601String(),
            ])->values()->all(),
            'support_tickets' => $tickets->map(fn ($t) => [
                'subject'    => $t->subject,
                'status'     => $t->status,
                'priority'   => $t->priority,
                'created_at' => $t->created_at?->toIso8601String(),
            ])->values()->all(),
            'consent_records' => $consents->map(fn ($c) => [
                'consent_type' => $c->consent_type,
                'version'      => $c->version,
                'accepted_at'  => $c->accepted_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    private function mapGuestApp(GuestApplication $app): array
    {
        return [
            'tracking_token'        => $app->tracking_token,
            'first_name'            => $app->first_name,
            'last_name'             => $app->last_name,
            'email'                 => $app->email,
            'phone'                 => $app->phone,
            'gender'                => $app->gender,
            'application_country'   => $app->application_country,
            'application_type'      => $app->application_type,
            'target_term'           => $app->target_term,
            'target_city'           => $app->target_city,
            'language_level'        => $app->language_level,
            'lead_status'           => $app->lead_status,
            'lead_source'           => $app->lead_source,
            'selected_package_code' => $app->selected_package_code,
            'contract_status'       => $app->contract_status,
            'kvkk_consent'          => $app->kvkk_consent,
            'is_archived'           => $app->is_archived,
            'created_at'            => $app->created_at?->toIso8601String(),
            'assigned_at'           => $app->assigned_at?->toIso8601String(),
        ];
    }
}

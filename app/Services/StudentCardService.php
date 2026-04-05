<?php

namespace App\Services;

use App\Models\AccountVault;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\InternalNote;
use App\Models\NotificationDispatch;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentRevenue;
use App\Models\StudentRiskScore;

class StudentCardService
{
    public function build(string $studentId): array
    {
        $studentId = trim($studentId);
        if ($studentId === '') {
            abort(404, 'Student bulunamadi.');
        }

        $assignment = StudentAssignment::query()
            ->where('student_id', $studentId)
            ->first();

        $risk = StudentRiskScore::query()
            ->where('student_id', $studentId)
            ->first();

        $revenue = StudentRevenue::query()
            ->where('student_id', $studentId)
            ->first();

        $guest = GuestApplication::query()
            ->where('converted_student_id', $studentId)
            ->latest('id')
            ->first();

        $documents = Document::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'document_id',
                'standard_file_name',
                'status',
                'process_tags',
                'updated_at',
            ]);

        $outcomes = ProcessOutcome::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'process_step',
                'outcome_type',
                'is_visible_to_student',
                'created_at',
            ]);

        $notes = InternalNote::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'category',
                'priority',
                'is_pinned',
                'created_by',
                'created_at',
            ]);

        $notifications = NotificationDispatch::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'channel',
                'category',
                'status',
                'queued_at',
                'sent_at',
                'failed_at',
            ]);

        $accountVaultSummary = [
            'count' => AccountVault::query()->where('student_id', $studentId)->count(),
            'services' => AccountVault::query()
                ->where('student_id', $studentId)
                ->orderBy('service_name')
                ->limit(8)
                ->pluck('service_name')
                ->values()
                ->all(),
        ];

        return [
            'student_id' => $studentId,
            'profile' => [
                'full_name' => trim((string) (($guest?->first_name ?? '').' '.($guest?->last_name ?? ''))),
                'email' => $guest?->email,
                'phone' => $guest?->phone,
                'application_type' => $guest?->application_type,
                'target_city' => $guest?->target_city,
                'target_term' => $guest?->target_term,
            ],
            'contract' => [
                'status' => (string) ($guest?->contract_status ?? 'not_requested'),
                'requested_at' => optional($guest?->contract_requested_at)?->toDateTimeString(),
                'signed_at' => optional($guest?->contract_signed_at)?->toDateTimeString(),
                'approved_at' => optional($guest?->contract_approved_at)?->toDateTimeString(),
                'signed_file_path' => (string) ($guest?->contract_signed_file_path ?? ''),
                'template_code' => (string) ($guest?->contract_template_code ?? ''),
                'generated_at' => optional($guest?->contract_generated_at)?->toDateTimeString(),
                'package_title' => (string) ($guest?->selected_package_title ?? ''),
                'package_price' => (string) ($guest?->selected_package_price ?? ''),
                'extra_services' => collect(is_array($guest?->selected_extra_services) ? $guest->selected_extra_services : [])
                    ->map(fn ($x) => trim((string) ($x['title'] ?? '')))->filter()->values()->all(),
            ],
            'assignment' => [
                'senior_email' => $assignment?->senior_email,
                'branch' => $assignment?->branch,
                'dealer_id' => $assignment?->dealer_id,
                'risk_level' => $assignment?->risk_level,
                'payment_status' => $assignment?->payment_status,
                'is_archived' => (bool) ($assignment?->is_archived ?? false),
            ],
            'financial' => [
                'package_id' => $revenue?->package_id,
                'package_total_price' => $revenue?->package_total_price,
                'package_currency' => $revenue?->package_currency,
                'total_earned' => $revenue?->total_earned,
                'total_pending' => $revenue?->total_pending,
                'total_remaining' => $revenue?->total_remaining,
            ],
            'risk' => [
                'level' => $risk?->risk_level,
                'score' => $risk?->current_score,
                'last_calculated_at' => optional($risk?->last_calculated_at)?->toDateTimeString(),
            ],
            'documents' => $documents,
            'outcomes' => $outcomes,
            'notes' => $notes,
            'notifications' => $notifications,
            'account_vault' => $accountVaultSummary,
        ];
    }
}


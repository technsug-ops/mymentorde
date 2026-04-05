<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use App\Models\DealerStudentRevenue;
use App\Models\Document;
use App\Models\FieldRuleApproval;
use App\Models\GuestApplication;
use App\Models\InternalNote;
use App\Models\NotificationDispatch;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentRevenue;
use App\Models\StudentRiskScore;
use App\Models\User;

class ManagerPortalPreviewController extends Controller
{
    public function student(string $studentId)
    {
        $studentId = trim($studentId);

        $assignment = StudentAssignment::query()->where('student_id', $studentId)->first();
        $risk = StudentRiskScore::query()->where('student_id', $studentId)->first();
        $revenue = StudentRevenue::query()->where('student_id', $studentId)->first();
        $outcomes = ProcessOutcome::query()
            ->where('student_id', $studentId)
            ->where('is_visible_to_student', true)
            ->latest()
            ->limit(10)
            ->get(['process_step', 'outcome_type', 'details_tr', 'created_at']);
        $documents = Document::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(10)
            ->get(['document_code', 'title', 'status', 'updated_at']);
        $notifications = NotificationDispatch::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(10)
            ->get(['channel', 'category', 'status', 'queued_at', 'sent_at', 'failed_at']);
        $guestApplication = GuestApplication::query()
            ->where('converted_student_id', $studentId)
            ->latest('id')
            ->first();
        $hasFirstProcess = $outcomes->isNotEmpty() || $documents->isNotEmpty();
        $hasSentNotification = $notifications->contains(fn ($n) => $n->status === 'sent' || !empty($n->sent_at));
        $progressSteps = [
            ['label' => 'Basvuru alindi', 'done' => (bool) $guestApplication],
            ['label' => 'Donusturuldu', 'done' => !empty($studentId)],
            ['label' => 'Ilk surec', 'done' => $hasFirstProcess],
            ['label' => 'Bildirim gonderildi', 'done' => $hasSentNotification],
        ];

        return view('student.dashboard', [
            'studentId'          => $studentId,
            'assignment'         => $assignment,
            'risk'               => $risk,
            'revenue'            => $revenue,
            'outcomes'           => $outcomes,
            'documents'          => $documents,
            'notifications'      => $notifications,
            'progressSteps'      => $progressSteps,
            'guestApplication'   => $guestApplication,
            'previewMode'        => true,
            'previewLabel'       => "Manager preview | student: {$studentId}",
            // safe defaults for preview
            'user'               => null,
            'onboardingTasks'    => collect(),
            'docSummary'         => [],
            'requiredChecklist'  => collect(),
            'notificationSummary'=> [],
            'outcomeByStep'      => collect(),
            'dmThread'           => null,
            'dmUnread'           => 0,
            'banners'            => collect(),
            'greeting'           => 'Merhaba!',
            'greetingSub'        => '',
            'countdowns'         => collect(),
            'alerts'             => collect(),
            'weekActivity'       => null,
            'checklistSummary'   => ['total' => 0, 'done' => 0, 'percent' => 0, 'overdue' => 0],
            'checklistItems'     => collect(),
            'achievements'       => collect(),
            'achievementPoints'  => 0,
            'onboardingPending'  => 0,
            'onboardingSteps'    => [],
        ]);
    }

    public function dealer(string $dealerCode)
    {
        $dealerCode = strtoupper(trim($dealerCode));

        $dealer = Dealer::query()->where('code', $dealerCode)->first();
        $students = StudentAssignment::query()
            ->where('dealer_id', $dealerCode)
            ->latest('updated_at')
            ->limit(12)
            ->get(['student_id', 'senior_email', 'branch', 'risk_level', 'payment_status', 'updated_at']);
        $revenues = DealerStudentRevenue::query()
            ->where('dealer_id', $dealerCode)
            ->latest('updated_at')
            ->limit(12)
            ->get(['student_id', 'dealer_type', 'milestones', 'updated_at']);

        return view('dealer.dashboard', [
            'dealerCode'          => $dealerCode,
            'dealer'              => $dealer,
            'studentCount'        => $students->count(),
            'students'            => $students,
            'revenues'            => $revenues,
            'previewMode'         => true,
            'previewLabel'        => "Manager preview | dealer: {$dealerCode}",
            // safe defaults for preview
            'dealerLink'          => $dealerCode !== '' ? url('/apply').'?ref='.urlencode($dealerCode) : null,
            'guestLeads'          => collect(),
            'kpis'                => [
                'lead_total'      => 0,
                'converted_total' => 0,
                'conversion_rate' => 0,
                'student_total'   => $students->pluck('student_id')->filter()->unique()->count(),
                'revenue_total'   => 0,
                'revenue_pending' => 0,
                'revenue_month'   => 0,
            ],
            'typeDistribution'    => collect(),
            'channelDistribution' => collect(),
            'recentActivity'      => collect(),
            'monthlyRevenue'      => collect(),
            'motivationCard'      => null,
            'earningsHero'        => ['total' => 0, 'month' => 0, 'pending' => 0, 'next_milestone' => null, 'month_label' => now()->format('F Y')],
            'leadPipeline'        => ['new' => 0, 'contacted' => 0, 'docs_pending' => 0, 'contract_stage' => 0, 'converted' => 0],
        ]);
    }

    public function senior(string $email)
    {
        $seniorEmail = strtolower(trim($email));
        $senior = User::query()
            ->whereIn('role', ['senior', 'mentor'])
            ->where('email', strtolower($seniorEmail))
            ->firstOrFail();

        $base = StudentAssignment::query()
            ->whereRaw('lower(senior_email) = ?', [$seniorEmail]);

        $activeStudentCount = (clone $base)->where('is_archived', false)->count();
        $archivedStudentCount = (clone $base)->where('is_archived', true)->count();
        $studentIds = (clone $base)->pluck('student_id')->filter()->unique()->values();
        $pendingApprovalCount = $studentIds->isEmpty()
            ? 0
            : FieldRuleApproval::query()
                ->whereIn('student_id', $studentIds->all())
                ->where('status', 'pending')
                ->count();

        $recentStudents = (clone $base)->latest('updated_at')->limit(10)->get([
            'student_id',
            'branch',
            'dealer_id',
            'risk_level',
            'payment_status',
            'is_archived',
            'updated_at',
        ]);
        $recentOutcomes = $studentIds->isEmpty()
            ? collect()
            : ProcessOutcome::query()
                ->whereIn('student_id', $studentIds->all())
                ->latest()
                ->limit(8)
                ->get(['student_id', 'process_step', 'outcome_type', 'details_tr', 'created_at']);
        $recentNotes = $studentIds->isEmpty()
            ? collect()
            : InternalNote::query()
                ->whereIn('student_id', $studentIds->all())
                ->latest()
                ->limit(8)
                ->get(['student_id', 'category', 'priority', 'is_pinned', 'created_at']);
        $recentNotifications = $studentIds->isEmpty()
            ? collect()
            : NotificationDispatch::query()
                ->whereIn('student_id', $studentIds->all())
                ->latest()
                ->limit(8)
                ->get(['student_id', 'channel', 'category', 'status', 'queued_at', 'sent_at', 'failed_at']);

        return view('senior.dashboard', [
            'activeStudentCount'   => $activeStudentCount,
            'archivedStudentCount' => $archivedStudentCount,
            'pendingApprovalCount' => $pendingApprovalCount,
            'recentStudents'       => $recentStudents,
            'recentOutcomes'       => $recentOutcomes,
            'recentNotes'          => $recentNotes,
            'recentNotifications'  => $recentNotifications,
            'previewMode'          => true,
            'previewLabel'         => "Manager preview | {$senior->role}: {$senior->email}",
            // safe defaults for preview
            'todayAppointments'    => collect(),
            'todayTasks'           => collect(),
            'pendingTickets'       => collect(),
            'riskRadar'            => collect(),
            'criticalActions'      => collect(),
            'weeklyPerformance'    => ['docs_approved' => 0, 'outcomes' => 0, 'notes' => 0],
            'taskSummary'          => ['todo' => 0, 'in_progress' => 0, 'done_this_week' => 0],
            'recentTasks'          => collect(),
            'pendingContracts'     => collect(),
            'dmSummary'            => ['unread' => 0, 'open_threads' => 0],
            'banners'              => collect(),
        ]);
    }
}

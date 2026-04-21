<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentWorkflowTrait;
use App\Models\ProcessOutcome;
use App\Models\StudentAppointment;
use App\Models\StudentChecklist;
use App\Models\StudentFeedback;
use App\Services\StudentAchievementService;
use App\Services\StudentGuestResolver;
use Illuminate\Http\Request;

class StudentInteractionController extends Controller
{
    use StudentWorkflowTrait;

    public function storeAppointment(Request $request)
    {
        $guest     = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        abort_if($studentId === '', 422, 'Student ID bulunamadi.');

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:190'],
            'note'             => ['nullable', 'string', 'max:5000'],
            'scheduled_at'     => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:180'],
            'channel'          => ['nullable', 'in:online,office,phone'],
        ]);

        StudentAppointment::query()->create([
            'company_id'    => (int) ($guest->company_id ?: 1),
            'student_id'    => $studentId,
            'student_email' => (string) ($request->user()->email ?? ''),
            'senior_email'  => (string) ($guest->assigned_senior_email ?? ''),
            'title'         => trim((string) $data['title']),
            'note'          => trim((string) ($data['note'] ?? '')) ?: null,
            'requested_at'  => now(),
            'scheduled_at'  => $data['scheduled_at'],
            'duration_minutes' => (int) ($data['duration_minutes'] ?? 30),
            'channel'       => (string) ($data['channel'] ?? 'online'),
            'status'        => 'requested',
        ]);

        return redirect('/student/appointments')->with('status', 'Randevu talebi olusturuldu.');
    }

    public function cancelAppointment(Request $request, StudentAppointment $appointment)
    {
        $guest     = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        abort_if((string) $appointment->student_id !== $studentId, 403, 'Bu randevu size ait degil.');

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $appointment->forceFill([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => trim((string) ($data['reason'] ?? '')) ?: null,
        ])->save();

        return redirect('/student/appointments')->with('status', 'Randevu iptal edildi.');
    }

    public function globalSearch(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim($request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['error' => 'Minimum 2 karakter.'], 422);
        }

        $guest     = $this->resolveStudentGuest($request);
        $studentId = (string) ($request->user()?->student_id ?? $guest?->converted_student_id ?? '');
        $ownerIds  = $guest ? $this->resolveDocumentOwnerIds($guest) : collect();
        $needle    = '%' . $q . '%';
        $results   = collect();

        // 1. Belgeler
        if ($ownerIds->isNotEmpty()) {
            \App\Models\Document::whereIn('student_id', $ownerIds)
                ->where(fn ($w) => $w->where('original_file_name', 'like', $needle)
                    ->orWhere('title', 'like', $needle))
                ->limit(5)
                ->get(['id', 'original_file_name', 'status', 'updated_at'])
                ->each(fn ($d) => $results->push([
                    'type'  => 'document',
                    'icon'  => '📄',
                    'title' => $d->original_file_name,
                    'sub'   => 'Belge — ' . $d->status,
                    'url'   => '/student/registration/documents',
                    'date'  => $d->updated_at?->format('d.m.Y'),
                ]));
        }

        // 2. Biletler
        if ($guest) {
            \App\Models\GuestTicket::where('guest_application_id', $guest->id)
                ->where(fn ($w) => $w->where('subject', 'like', $needle)
                    ->orWhere('body', 'like', $needle))
                ->limit(5)
                ->get(['id', 'subject', 'status', 'created_at'])
                ->each(fn ($t) => $results->push([
                    'type'  => 'ticket',
                    'icon'  => '🎫',
                    'title' => $t->subject,
                    'sub'   => 'Bilet — ' . $t->status,
                    'url'   => '/student/tickets?highlight=' . $t->id,
                    'date'  => $t->created_at?->format('d.m.Y'),
                ]));
        }

        // 3. Süreç sonuçları
        if ($studentId !== '') {
            ProcessOutcome::where('student_id', $studentId)
                ->where('is_visible_to_student', true)
                ->where(fn ($w) => $w->where('university', 'like', $needle)
                    ->orWhere('program', 'like', $needle)
                    ->orWhere('details_tr', 'like', $needle))
                ->limit(5)
                ->get(['id', 'university', 'program', 'outcome_type', 'created_at'])
                ->each(fn ($o) => $results->push([
                    'type'  => 'outcome',
                    'icon'  => '📊',
                    'title' => trim(($o->university ?: '') . ' ' . ($o->program ?: '')),
                    'sub'   => 'Süreç — ' . $o->outcome_type,
                    'url'   => '/student/process-tracking',
                    'date'  => $o->created_at?->format('d.m.Y'),
                ]));
        }

        // 4. Materyaller
        \App\Models\KnowledgeBaseArticle::where('is_published', true)
            ->where(fn ($w) => $w->where('title_tr', 'like', $needle)
                ->orWhere('body_tr', 'like', $needle))
            ->limit(5)
            ->get(['id', 'title_tr', 'category', 'updated_at'])
            ->each(fn ($m) => $results->push([
                'type'  => 'material',
                'icon'  => '📚',
                'title' => $m->title_tr,
                'sub'   => 'Materyal — ' . $m->category,
                'url'   => '/student/materials',
                'date'  => $m->updated_at?->format('d.m.Y'),
            ]));

        return response()->json([
            'query'   => $q,
            'results' => $results->take(20)->values(),
            'total'   => $results->count(),
        ]);
    }

    public function calendarEvents(Request $request): \Illuminate\Http\JsonResponse
    {
        $user      = $request->user();
        $studentId = trim((string) ($user->student_id ?? ''));
        if ($studentId === '') {
            $guest     = app(StudentGuestResolver::class)->resolveForUser($user);
            $studentId = trim((string) ($guest?->converted_student_id ?? ''));
        }

        $events = collect();

        if ($studentId !== '') {
            // Randevular
            StudentAppointment::where('student_id', $studentId)
                ->whereNotNull('scheduled_at')
                ->whereIn('status', ['pending', 'requested', 'scheduled', 'confirmed'])
                ->limit(50)
                ->get(['id', 'scheduled_at', 'duration_minutes', 'title', 'status', 'meeting_url'])
                ->each(function ($a) use (&$events) {
                    $end = $a->scheduled_at->copy()->addMinutes((int) ($a->duration_minutes ?? 45));
                    $events->push([
                        'id'    => 'apt-' . $a->id,
                        'title' => '📅 ' . (($a->title ?? '') ?: 'Randevu'),
                        'start' => $a->scheduled_at->toIso8601String(),
                        'end'   => $end->toIso8601String(),
                        'color' => in_array($a->status, ['scheduled', 'confirmed'], true) ? '#22c55e' : '#f59e0b',
                        'url'   => $a->meeting_url ?: '/student/appointments',
                    ]);
                });

            // Süreç deadline'ları
            ProcessOutcome::where('student_id', $studentId)
                ->where('is_visible_to_student', true)
                ->whereNotNull('deadline')
                ->limit(30)
                ->get(['id', 'process_step', 'university', 'program', 'deadline'])
                ->each(function ($o) use (&$events) {
                    $label = trim(($o->university ?: '') . ' ' . ($o->program ?: '')) ?: ($o->process_step ?? 'Deadline');
                    $events->push([
                        'id'      => 'dl-' . $o->id,
                        'title'   => '⏰ ' . $label,
                        'start'   => $o->deadline->toDateString(),
                        'allDay'  => true,
                        'color'   => '#ef4444',
                        'url'     => '/student/process-tracking',
                        'display' => 'background',
                    ]);
                });

            // Checklist görev tarihleri
            StudentChecklist::where('student_id', $studentId)
                ->where('is_done', false)
                ->whereNotNull('due_date')
                ->limit(20)
                ->get(['id', 'label', 'due_date'])
                ->each(function ($c) use (&$events) {
                    $events->push([
                        'id'    => 'cl-' . $c->id,
                        'title' => '✅ ' . $c->label,
                        'start' => $c->due_date->toDateString(),
                        'allDay'=> true,
                        'color' => '#6366f1',
                        'url'   => '/student/checklist',
                    ]);
                });
        }

        return response()->json($events->values());
    }

    public function storeFeedback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'feedback_type' => ['required', 'string', 'in:general,process,senior,portal'],
            'process_step'  => ['nullable', 'string', 'max:80'],
            'rating'        => ['required', 'integer', 'min:1', 'max:5'],
            'comment'       => ['nullable', 'string', 'max:2000'],
        ]);

        $user      = $request->user();
        $studentId = trim((string) ($user->student_id ?? ''));
        if ($studentId === '') {
            $guest     = app(StudentGuestResolver::class)->resolveForUser($user);
            $studentId = trim((string) ($guest?->converted_student_id ?? ''));
        }
        abort_if($studentId === '', 422, 'Öğrenci kaydı bulunamadı.');

        $companyId = (int) (app()->bound('current_company_id') ? app('current_company_id') : 1);

        StudentFeedback::create([
            'student_id'    => $studentId,
            'company_id'    => $companyId,
            'feedback_type' => $data['feedback_type'],
            'process_step'  => $data['process_step'] ?? null,
            'rating'        => (int) $data['rating'],
            'comment'       => $data['comment'] ?? null,
        ]);

        app(StudentAchievementService::class)->checkAndAward($studentId, (string) $companyId);

        return redirect()->route('student.feedback')->with('success', 'Geri bildiriminiz alındı. Teşekkürler!');
    }

    public function storeNps(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'nps_score' => ['required', 'integer', 'min:0', 'max:10'],
            'comment'   => ['nullable', 'string', 'max:1000'],
        ]);

        $user      = $request->user();
        $studentId = trim((string) ($user->student_id ?? ''));
        if ($studentId === '') {
            $guest     = app(StudentGuestResolver::class)->resolveForUser($user);
            $studentId = trim((string) ($guest?->converted_student_id ?? ''));
        }
        abort_if($studentId === '', 422);

        $companyId = (int) (app()->bound('current_company_id') ? app('current_company_id') : 1);

        StudentFeedback::create([
            'student_id'    => $studentId,
            'company_id'    => $companyId,
            'feedback_type' => 'nps',
            'nps_score'     => (int) $data['nps_score'],
            'comment'       => $data['comment'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }
}

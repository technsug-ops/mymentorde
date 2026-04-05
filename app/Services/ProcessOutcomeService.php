<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\MessageTemplate;
use App\Models\NotificationDispatch;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;

class ProcessOutcomeService
{
    public function __construct(private readonly InternalNoteService $internalNoteService)
    {
    }

    public function makeVisibleToStudent(ProcessOutcome $outcome, string $actor): ProcessOutcome
    {
        $queueInfo = $this->queueOutcomeNotification($outcome, $actor);

        $outcome->update([
            'is_visible_to_student' => true,
            'made_visible_at' => now(),
            'made_visible_by' => $actor,
            'student_notified' => (bool) $queueInfo['queued'],
            'notified_at' => $queueInfo['queued'] ? now() : null,
        ]);

        $this->internalNoteService->createSystemNote(
            (string) $outcome->student_id,
            (string) $queueInfo['message'],
            $actor !== '' ? $actor : 'system',
            'manager'
        );

        // 2.4 Automation hook — üniversite kabulü
        if (in_array($outcome->outcome_type, ['acceptance', 'conditional_acceptance'], true)) {
            app(\App\Services\SeniorAutomationService::class)->onEvent(
                'process_outcome.acceptance',
                (string) $outcome->student_id,
                [
                    'student_name' => (string) $outcome->student_id,
                    'university'   => (string) ($outcome->university ?? ''),
                ]
            );
        }

        return $outcome->fresh();
    }

    private function queueOutcomeNotification(ProcessOutcome $outcome, string $actor): array
    {
        $studentId = (string) $outcome->student_id;

        $guest = GuestApplication::query()
            ->where('converted_student_id', $studentId)
            ->latest('id')
            ->first();

        $assignment = StudentAssignment::query()
            ->where('student_id', $studentId)
            ->first();

        $recipientEmail = (string) ($guest->email ?? $assignment->senior_email ?? '');
        $recipientPhone = (string) ($guest->phone ?? '');
        $recipientName = trim((string) (($guest->first_name ?? '').' '.($guest->last_name ?? '')));
        if ($recipientName === '') {
            $recipientName = $studentId;
        }

        if ($recipientEmail === '' && $recipientPhone === '') {
            return [
                'queued' => false,
                'message' => "Outcome #{$outcome->id} gorunur yapildi ancak bildirim kuyruga alinmadi: recipient yok",
            ];
        }

        $templates = MessageTemplate::query()
            ->where('is_active', true)
            ->whereIn('category', ['status_update', 'process_outcome'])
            ->orderBy('id')
            ->get();

        $vars = [
            'student_name' => $recipientName,
            'student_id' => $studentId,
            'outcome_type' => (string) $outcome->outcome_type,
            'process_step' => (string) $outcome->process_step,
            'university' => (string) ($outcome->university ?? ''),
            'program' => (string) ($outcome->program ?? ''),
            'details' => (string) ($outcome->details_tr ?? ''),
        ];

        $queued = 0;
        if ($templates->isNotEmpty()) {
            foreach ($templates as $template) {
                NotificationDispatch::query()->create([
                    'template_id' => $template->id,
                    'channel' => (string) $template->channel,
                    'category' => (string) $template->category,
                    'student_id' => $studentId,
                    'recipient_email' => $recipientEmail !== '' ? $recipientEmail : null,
                    'recipient_phone' => $recipientPhone !== '' ? $recipientPhone : null,
                    'recipient_name' => $recipientName,
                    'subject' => $this->renderTemplate((string) ($template->subject_tr ?? ''), $vars) ?: null,
                    'body' => $this->renderTemplate((string) $template->body_tr, $vars),
                    'variables' => $vars,
                    'status' => 'queued',
                    'queued_at' => now(),
                    'source_type' => 'process_outcome',
                    'source_id' => (string) $outcome->id,
                    'triggered_by' => $actor !== '' ? $actor : 'system',
                ]);
                $queued++;
            }
        } else {
            NotificationDispatch::query()->create([
                'template_id' => null,
                'channel' => 'email',
                'category' => 'process_outcome',
                'student_id' => $studentId,
                'recipient_email' => $recipientEmail !== '' ? $recipientEmail : null,
                'recipient_phone' => $recipientPhone !== '' ? $recipientPhone : null,
                'recipient_name' => $recipientName,
                'subject' => "Surec sonucu guncellendi ({$outcome->outcome_type})",
                'body' => "Merhaba {$recipientName}, surec sonucunuz guncellendi. Adim: {$outcome->process_step}. Detay: ".((string) ($outcome->details_tr ?? '')),
                'variables' => $vars,
                'status' => 'queued',
                'queued_at' => now(),
                'source_type' => 'process_outcome',
                'source_id' => (string) $outcome->id,
                'triggered_by' => $actor !== '' ? $actor : 'system',
            ]);
            $queued = 1;
        }

        return [
            'queued' => true,
            'message' => "Outcome #{$outcome->id} gorunur yapildi | bildirim kuyrugu: {$queued}",
        ];
    }

    private function renderTemplate(string $text, array $vars): string
    {
        $out = $text;
        foreach ($vars as $key => $value) {
            $out = str_replace('{{'.$key.'}}', (string) $value, $out);
        }

        return trim($out);
    }
}

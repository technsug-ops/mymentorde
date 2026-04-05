<?php

namespace App\Services;

use App\Models\InternalNote;
use App\Models\MarketingTask;
use App\Models\NotificationDispatch;
use App\Models\StudentChecklist;

class SeniorAutomationService
{
    /**
     * Fire automations matching $trigger for a given student.
     */
    public function onEvent(string $trigger, string $studentId, array $context = []): void
    {
        $automations = collect(config('senior_automations', []))
            ->filter(fn ($a) => ($a['trigger'] ?? '') === $trigger && ($a['is_active'] ?? false));

        foreach ($automations as $automation) {
            foreach ($automation['actions'] as $action) {
                $this->executeAction($action, $studentId, $context);
            }
        }
    }

    private function executeAction(array $action, string $studentId, array $context): void
    {
        $replace = fn (string $text): string => strtr($text, [
            '{{student_name}}' => $context['student_name'] ?? '',
            '{{university}}'   => $context['university'] ?? '',
            '{{deadline}}'     => $context['deadline'] ?? '',
        ]);

        try {
            match ($action['type'] ?? '') {
                'notify_student' => $this->notifyStudent($studentId, $replace($action['template'] ?? ''), $context),
                'create_checklist' => $this->createChecklist($studentId, $action['items'] ?? []),
                'create_task' => $this->createTask($replace($action['title'] ?? ''), $action['priority'] ?? 'normal', (int) ($action['due_days'] ?? 3), $studentId),
                'add_note' => $this->addNote($studentId, $replace($action['content'] ?? ''), $action['priority'] ?? 'medium'),
                'make_visible', 'update_pipeline' => null, // handled at call-site if needed
                default => null,
            };
        } catch (\Throwable) {
            // Automation failures are non-fatal — silently continue
        }
    }

    private function notifyStudent(string $studentId, string $message, array $context): void
    {
        NotificationDispatch::create([
            'student_id'  => $studentId,
            'user_id'     => $context['student_user_id'] ?? null,
            'channel'     => 'in_app',
            'category'    => 'senior_automation',
            'subject'     => 'Bilgilendirme',
            'body'        => $message,
            'source_type' => 'senior_automation',
        ]);
    }

    private function createChecklist(string $studentId, array $items): void
    {
        foreach ($items as $label) {
            StudentChecklist::create([
                'student_id' => $studentId,
                'label'      => $label,
            ]);
        }
    }

    private function createTask(string $title, string $priority, int $dueDays, string $studentId): void
    {
        MarketingTask::create([
            'title'              => $title,
            'priority'           => $priority,
            'due_date'           => now()->addDays($dueDays)->toDateString(),
            'status'             => 'pending',
            'source_type'        => 'senior_automation',
            'related_student_id' => $studentId,
        ]);
    }

    private function addNote(string $studentId, string $content, string $priority): void
    {
        InternalNote::create([
            'student_id'      => $studentId,
            'content'         => $content,
            'category'        => 'general',
            'priority'        => $priority,
            'created_by_role' => 'senior_automation',
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\EscalationEvent;
use App\Models\EscalationRule;
use App\Models\FieldRuleApproval;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\User;
use Carbon\Carbon;

class EscalationService
{
    public function process(?int $limit = null): array
    {
        $limit = max(1, (int) ($limit ?? 100));
        $queued = 0;
        $events = 0;

        $rules = EscalationRule::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            $targets = $this->getTargets($rule, $limit);
            foreach ($targets as $entity) {
                $ageHours = Carbon::parse($entity->created_at)->diffInHours(now());
                $steps = collect($rule->escalation_steps ?? [])
                    ->sortBy('after_hours')
                    ->values();

                foreach ($steps as $step) {
                    $stepNo = (int) ($step['step'] ?? 0);
                    $afterHours = (int) ($step['after_hours'] ?? 0);
                    if ($stepNo <= 0 || $ageHours < $afterHours) {
                        continue;
                    }

                    $exists = EscalationEvent::query()
                        ->where('escalation_rule_id', $rule->id)
                        ->where('entity_type', $rule->entity_type)
                        ->where('entity_id', $entity->id)
                        ->where('step_no', $stepNo)
                        ->exists();
                    if ($exists) {
                        continue;
                    }

                    $targetRoles = collect($step['target_roles'] ?? [])->map(fn ($v) => (string) $v)->all();
                    $channels = collect($step['channels'] ?? ['in_app'])->map(fn ($v) => (string) $v)->all();
                    $recipientEmails = $this->resolveRecipientEmails($rule->entity_type, $entity, $targetRoles);
                    if (empty($recipientEmails)) {
                        continue;
                    }
                    // Kural başına alıcı patlamasını önle (max 50 bildirim per kural-entity-adım)
                    $recipientEmails = array_slice($recipientEmails, 0, 50);

                    $channel = in_array('email', $channels, true) ? 'email' : 'in_app';
                    $body    = $this->buildMessage($rule, $entity, $stepNo, $ageHours);
                    $notifSvc = app(NotificationService::class);

                    foreach ($recipientEmails as $email) {
                        $recipientUser = User::where('email', $email)->first();
                        $notifSvc->send([
                            'channel'         => $channel,
                            'category'        => 'task_escalation_level' . $stepNo,
                            'user_id'         => $recipientUser?->id,
                            'recipient_email' => $email,
                            'subject'         => "Eskalasyon ({$rule->name}) — Adım {$stepNo}",
                            'body'            => $body,
                            'source_type'     => 'escalation_rule',
                            'source_id'       => (string) $rule->id,
                            'triggered_by'    => 'system:escalation',
                        ]);
                        $queued++;
                    }

                    EscalationEvent::query()->create([
                        'escalation_rule_id' => $rule->id,
                        'entity_type' => $rule->entity_type,
                        'entity_id' => $entity->id,
                        'step_no' => $stepNo,
                        'action' => (string) ($step['action'] ?? 'remind'),
                        'targets' => $targetRoles,
                        'channels' => $channels,
                        'triggered_at' => now(),
                        'status' => 'queued',
                    ]);
                    $events++;
                }
            }
        }

        return [
            'rules' => $rules->count(),
            'events_created' => $events,
            'notifications_queued' => $queued,
        ];
    }

    private function getTargets(EscalationRule $rule, int $limit)
    {
        if ($rule->entity_type === 'process_outcome') {
            return ProcessOutcome::query()
                ->where('is_visible_to_student', false)
                ->where(function ($q): void {
                    $q->whereNull('deadline')
                        ->orWhere('deadline', '<', now());
                })
                ->orderBy('created_at')
                ->limit($limit)
                ->get();
        }

        return FieldRuleApproval::query()
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    private function resolveRecipientEmails(string $entityType, $entity, array $targetRoles): array
    {
        $emails = [];

        if (in_array('manager', $targetRoles, true)) {
            $managerEmails = User::query()
                ->where('role', 'manager')
                ->where('is_active', true)
                ->pluck('email')
                ->filter()
                ->all();
            $emails = array_merge($emails, $managerEmails);
        }

        if (in_array('senior', $targetRoles, true)) {
            $studentId = (string) ($entity->student_id ?? '');
            if ($studentId !== '') {
                $seniorEmail = StudentAssignment::query()
                    ->where('student_id', $studentId)
                    ->value('senior_email');
                if (!empty($seniorEmail)) {
                    $emails[] = (string) $seniorEmail;
                }
            }
        }

        return array_values(array_unique(array_filter($emails)));
    }

    private function buildMessage(EscalationRule $rule, $entity, int $stepNo, int $ageHours): string
    {
        $studentId = (string) ($entity->student_id ?? '-');
        return "Escalation Step {$stepNo} | rule: {$rule->name} | entity: {$rule->entity_type}#{$entity->id} | student: {$studentId} | age_hours: {$ageHours}";
    }
}


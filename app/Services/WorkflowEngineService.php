<?php

namespace App\Services;

use App\Models\AutomationEnrollment;
use App\Models\AutomationEnrollmentLog;
use App\Models\AutomationWorkflow;
use App\Models\AutomationWorkflowNode;
use App\Models\GuestApplication;
use App\Models\MarketingTask;
use Illuminate\Support\Facades\Log;

class WorkflowEngineService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly LeadScoreService $leadScoreService,
    ) {}

    public function enroll(int|string $guestId, int $workflowId): ?AutomationEnrollment
    {
        $workflow = AutomationWorkflow::find($workflowId);
        $guest    = GuestApplication::find($guestId);

        if (! $workflow || ! $guest || ! $workflow->isActive()) {
            return null;
        }

        // Check if already enrolled (non-recurring)
        if (! $workflow->is_recurring) {
            $existing = AutomationEnrollment::where('workflow_id', $workflowId)
                ->where('guest_application_id', $guestId)
                ->whereIn('status', ['active', 'waiting', 'completed'])
                ->exists();
            if ($existing) {
                return null;
            }
        }

        $firstNode = $workflow->nodes()->orderBy('sort_order')->first();

        $enrollment = AutomationEnrollment::create([
            'workflow_id'          => $workflowId,
            'guest_application_id' => $guestId,
            'current_node_id'      => $firstNode?->id,
            'status'               => 'active',
            'enrolled_at'          => now(),
            'next_check_at'        => now(),
        ]);

        $this->logAction($enrollment->id, $firstNode?->id, 'entered');

        return $enrollment;
    }

    public function processWaitingEnrollments(): int
    {
        $processed = 0;

        AutomationEnrollment::query()
            ->whereIn('status', ['active', 'waiting'])
            ->where('next_check_at', '<=', now())
            ->with(['workflow.nodes'])
            ->chunk(50, function ($enrollments) use (&$processed): void {
                foreach ($enrollments as $enrollment) {
                    try {
                        $this->processEnrollment($enrollment);
                        $processed++;
                    } catch (\Throwable $e) {
                        Log::error('Workflow enrollment error', [
                            'enrollment_id' => $enrollment->id,
                            'error'         => $e->getMessage(),
                        ]);
                        $enrollment->update(['status' => 'errored']);
                    }
                }
            });

        return $processed;
    }

    public function processEnrollment(AutomationEnrollment $enrollment): void
    {
        if (! $enrollment->current_node_id) {
            $this->exitEnrollment($enrollment->id, 'no_node');
            return;
        }

        $node = AutomationWorkflowNode::find($enrollment->current_node_id);
        if (! $node) {
            $this->exitEnrollment($enrollment->id, 'node_not_found');
            return;
        }

        $this->executeNode($enrollment, $node);
    }

    private function executeNode(AutomationEnrollment $enrollment, AutomationWorkflowNode $node): void
    {
        $config = $node->node_config ?? [];

        switch ($node->node_type) {
            case 'wait':
                $hours = (int) ($config['duration'] ?? 24);
                $unit  = $config['unit'] ?? 'hours';
                $delay = $unit === 'days' ? $hours * 24 : $hours;
                $enrollment->update([
                    'status'        => 'waiting',
                    'next_check_at' => now()->addHours($delay),
                ]);
                $this->logAction($enrollment->id, $node->id, 'waiting', ['wait_hours' => $delay]);
                $this->advanceToNextNode($enrollment, $node);
                break;

            case 'exit':
                $this->exitEnrollment($enrollment->id, 'reached_exit_node');
                break;

            case 'send_email':
                $guest = $enrollment->guestApplication;
                if ($guest && !empty($guest->email)) {
                    $this->notificationService->send([
                        'channel'         => 'email',
                        'category'        => 'workflow_email',
                        'guest_id'        => (string) $enrollment->guest_application_id,
                        'recipient_email' => $guest->email,
                        'recipient_name'  => trim($guest->first_name . ' ' . $guest->last_name),
                        'subject'         => $config['subject_tr'] ?? ($config['label'] ?? 'MentorDE Bildirimi'),
                        'body'            => $config['body'] ?? ($config['message'] ?? ''),
                        'template_id'     => isset($config['template_id']) ? (int) $config['template_id'] : null,
                        'variables'       => $config['variables'] ?? [],
                        'source_type'     => 'workflow_automation',
                        'source_id'       => (string) $enrollment->workflow_id,
                        'triggered_by'    => 'system',
                    ]);
                }
                $this->logAction($enrollment->id, $node->id, 'executed', $config);
                $this->advanceToNextNode($enrollment, $node);
                break;

            case 'send_notification':
                $guest = $enrollment->guestApplication;
                if ($guest) {
                    $this->notificationService->send([
                        'channel'      => $config['channel'] ?? 'in_app',
                        'category'     => $config['category'] ?? 'workflow_notification',
                        'guest_id'     => (string) $enrollment->guest_application_id,
                        'body'         => $config['message'] ?? '',
                        'source_type'  => 'workflow_automation',
                        'source_id'    => (string) $enrollment->workflow_id,
                        'triggered_by' => 'system',
                    ]);
                }
                $this->logAction($enrollment->id, $node->id, 'executed', $config);
                $this->advanceToNextNode($enrollment, $node);
                break;

            case 'create_task':
                $guest = $enrollment->guestApplication;
                MarketingTask::create([
                    'company_id'  => $guest?->company_id,
                    'title'       => $config['title'] ?? 'Otomasyon görevi',
                    'department'  => $config['department'] ?? 'marketing',
                    'priority'    => $config['priority'] ?? 'normal',
                    'status'      => 'todo',
                    'source_type' => 'workflow_automation',
                    'source_id'   => (string) $enrollment->workflow_id,
                ]);
                $this->logAction($enrollment->id, $node->id, 'executed', $config);
                $this->advanceToNextNode($enrollment, $node);
                break;

            case 'add_score':
                $this->leadScoreService->addScore(
                    $enrollment->guest_application_id,
                    $config['action_code'] ?? 'workflow_bonus',
                    ['workflow_id' => $enrollment->workflow_id]
                );
                $this->logAction($enrollment->id, $node->id, 'executed', $config);
                $this->advanceToNextNode($enrollment, $node);
                break;

            case 'update_field':
                $guest = $enrollment->guestApplication;
                $field = $config['field'] ?? null;
                if ($guest && $field && array_key_exists('value', $config)) {
                    $guest->update([$field => $config['value']]);
                }
                $this->logAction($enrollment->id, $node->id, 'executed', $config);
                $this->advanceToNextNode($enrollment, $node);
                break;

            case 'ab_split':
                $splitA   = (int) ($config['split_a'] ?? 50); // % A branch weight
                $rand     = random_int(1, 100);
                $branch   = $rand <= $splitA ? 'a' : 'b';
                $this->logAction($enrollment->id, $node->id, 'ab_split_' . $branch, [
                    'split_a'    => $splitA,
                    'split_b'    => 100 - $splitA,
                    'rand'       => $rand,
                    'chosen'     => $branch,
                ]);
                $this->advanceToNextNode($enrollment, $node, $branch);
                break;

            case 'condition':
            case 'goal_check':
                $result = $this->evaluateCondition($enrollment, $config);
                $branch = $result ? 'condition_true' : 'condition_false';
                $this->logAction($enrollment->id, $node->id, $branch);
                $this->advanceToNextNode($enrollment, $node, $result ? 'yes' : 'no');
                break;

            default:
                $this->logAction($enrollment->id, $node->id, 'executed');
                $this->advanceToNextNode($enrollment, $node);
        }
    }

    public function exitEnrollment(int $enrollmentId, string $reason): void
    {
        AutomationEnrollment::where('id', $enrollmentId)->update([
            'status'       => 'exited',
            'exit_reason'  => $reason,
            'completed_at' => now(),
        ]);
        $this->logAction($enrollmentId, null, 'exited', ['reason' => $reason]);
    }

    public function evaluateCondition(AutomationEnrollment $enrollment, array $config): bool
    {
        $guest = $enrollment->guestApplication;
        if (! $guest) {
            return false;
        }

        $field    = $config['field']    ?? '';
        $operator = $config['operator'] ?? '==';
        $value    = $config['value']    ?? null;

        $actual = match ($field) {
            'lead_score'        => $guest->lead_score,
            'lead_score_tier'   => $guest->lead_score_tier,
            'contract_status'   => $guest->contract_status,
            'lead_status'       => $guest->lead_status,
            default             => null,
        };

        return match ($operator) {
            '>='    => $actual >= $value,
            '<='    => $actual <= $value,
            '>'     => $actual > $value,
            '<'     => $actual < $value,
            '=='    => $actual == $value,
            '!='    => $actual != $value,
            default => false,
        };
    }

    private function advanceToNextNode(AutomationEnrollment $enrollment, AutomationWorkflowNode $node, string $branch = 'default'): void
    {
        $connections = $node->connections ?? [];
        $nextNodeId  = null;

        foreach ($connections as $conn) {
            $cond = $conn['condition'] ?? 'default';
            if ($cond === $branch || $cond === 'default') {
                $nextNodeId = $conn['target_node_id'] ?? null;
                break;
            }
        }

        if ($nextNodeId) {
            $enrollment->update([
                'current_node_id' => $nextNodeId,
                'status'          => 'active',
                'next_check_at'   => now(),
            ]);
        } else {
            // No more nodes
            $enrollment->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
            $this->logAction($enrollment->id, null, 'completed');
        }
    }

    private function logAction(int $enrollmentId, ?int $nodeId, string $action, array $result = []): void
    {
        AutomationEnrollmentLog::create([
            'enrollment_id' => $enrollmentId,
            'node_id'       => $nodeId,
            'action'        => $action,
            'result'        => $result ?: null,
            'executed_at'   => now(),
        ]);
    }
}

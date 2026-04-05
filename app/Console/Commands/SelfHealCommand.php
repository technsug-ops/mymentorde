<?php

namespace App\Console\Commands;

use App\Models\NotificationDispatch;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class SelfHealCommand extends Command
{
    protected $signature = 'ops:self-heal {--limit=100 : Max records to process}';
    protected $description = 'Retry failed notifications, dispatch queue, and auto-assign unassigned active students';

    public function handle(): int
    {
        $this->line('ops:self-heal basladi...');

        $limit   = max(1, (int) $this->option('limit'));
        $manager = User::query()->where('role', 'manager')->where('is_active', true)->orderBy('id')->first();
        if (!$manager) {
            $this->error('FAIL: aktif manager bulunamadi.');
            return 1;
        }

        // 1) Retry failed notifications
        $retried    = 0;
        $failedRows = NotificationDispatch::query()
            ->where('status', 'failed')
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get();

        foreach ($failedRows as $row) {
            $row->update(['status' => 'queued', 'queued_at' => now(), 'failed_at' => null, 'fail_reason' => null, 'sent_at' => null]);
            $retried++;
        }

        // 2) Dispatch queue
        auth()->loginUsingId((int) $manager->id);
        $dispatchReq = Request::create('/', 'POST', ['limit' => $limit]);
        $dispatchReq->setUserResolver(fn () => $manager);
        $dispatch = app(\App\Http\Controllers\Api\NotificationDispatchController::class)
            ->dispatchNow($dispatchReq)
            ->getData(true);

        // 3) Auto-assign unassigned students
        $unassignedIds = StudentAssignment::query()
            ->where('is_archived', false)
            ->whereNull('senior_email')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('student_id')
            ->filter()
            ->values()
            ->all();

        $autoAssign = ['affected' => 0, 'newly_assigned' => [], 'already_assigned' => [], 'unassigned' => []];

        if (!empty($unassignedIds)) {
            try {
                $autoReq = Request::create('/', 'POST', ['student_ids' => $unassignedIds]);
                $autoReq->setUserResolver(fn () => $manager);
                $autoAssignResponse = app()->call(
                    [app(\App\Http\Controllers\Api\StudentAssignmentController::class), 'autoAssign'],
                    ['request' => $autoReq]
                );
                $autoAssign = method_exists($autoAssignResponse, 'getData')
                    ? $autoAssignResponse->getData(true)
                    : (array) $autoAssignResponse;
            } catch (\Throwable $e) {
                $this->warn('WARN: autoAssign calisamadi: ' . $e->getMessage());
            }

            $stillUnassignedIds = StudentAssignment::query()
                ->whereIn('student_id', $unassignedIds)
                ->where('is_archived', false)
                ->whereNull('senior_email')
                ->pluck('student_id')
                ->values()
                ->all();

            if (!empty($stillUnassignedIds)) {
                $fallbackSenior = User::query()
                    ->whereIn('role', ['senior', 'mentor'])
                    ->where('is_active', true)
                    ->orderByDesc('auto_assign_enabled')
                    ->orderBy('id')
                    ->value('email');

                if (!empty($fallbackSenior)) {
                    StudentAssignment::query()
                        ->whereIn('student_id', $stillUnassignedIds)
                        ->where('is_archived', false)
                        ->whereNull('senior_email')
                        ->update(['senior_email' => (string) $fallbackSenior]);

                    $newlyAssignedFallback = StudentAssignment::query()
                        ->whereIn('student_id', $stillUnassignedIds)
                        ->where('senior_email', (string) $fallbackSenior)
                        ->pluck('student_id')
                        ->values()
                        ->all();

                    $autoAssign['affected']       = (int) ($autoAssign['affected'] ?? 0) + count($newlyAssignedFallback);
                    $autoAssign['newly_assigned']  = array_values(array_unique(array_merge($autoAssign['newly_assigned'] ?? [], $newlyAssignedFallback)));
                    $autoAssign['unassigned']      = array_values(array_diff($autoAssign['unassigned'] ?? [], $newlyAssignedFallback));
                }
            }
        }

        $this->info('--- self-heal ozeti ---');
        $this->line("retried_failed_notifications: {$retried}");
        $this->line('dispatch_processed: ' . ($dispatch['processed'] ?? 0) . ', sent: ' . ($dispatch['sent'] ?? 0) . ', failed: ' . ($dispatch['failed'] ?? 0));
        $this->line('auto_assign_affected: ' . ($autoAssign['affected'] ?? 0));
        $this->line('auto_assign_newly_assigned: ' . count($autoAssign['newly_assigned'] ?? []));
        $this->line('auto_assign_still_unassigned: ' . count($autoAssign['unassigned'] ?? []));
        $this->info('ops:self-heal SONUC: PASS');

        return 0;
    }
}

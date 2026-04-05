<?php

namespace App\Console\Commands;

use App\Models\NotificationDispatch;
use Illuminate\Console\Command;

class DispatchNotificationsCommand extends Command
{
    protected $signature = 'notifications:dispatch
                            {--limit=50 : Max records to process}
                            {--dry-run : Simulate without dispatching}';

    protected $description = 'Push queued notification dispatches to the job queue (SendNotificationJob)';

    public function handle(): int
    {
        $limit  = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $rows = NotificationDispatch::query()
            ->where('status', 'queued')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Queue bos. Islenecek kayit yok.');
            return 0;
        }

        $dispatched = 0;
        $skipped    = 0;

        foreach ($rows as $row) {
            $hasRecipient = !empty($row->recipient_email) || !empty($row->recipient_phone);
            if (!$hasRecipient) {
                $skipped++;
                if (!$dryRun) {
                    $row->update([
                        'status'      => 'failed',
                        'failed_at'   => now(),
                        'fail_reason' => 'recipient missing',
                        'sent_at'     => null,
                    ]);
                }
                continue;
            }

            if (!$dryRun) {
                \App\Jobs\SendNotificationJob::dispatch((int) $row->id)->onQueue('notifications');
            }
            $dispatched++;
        }

        $mode = $dryRun ? 'dry-run' : 'apply';
        $this->info("notifications:dispatch ({$mode}) | dispatched: {$dispatched} | skipped (no recipient): {$skipped}");

        return 0;
    }
}

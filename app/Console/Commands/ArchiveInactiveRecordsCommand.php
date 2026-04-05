<?php

namespace App\Console\Commands;

use App\Models\GuestApplication;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ArchiveInactiveRecordsCommand extends Command
{
    protected $signature = 'archive:inactive-records
                            {--guest-days=180 : Days of inactivity before archiving}
                            {--dry-run : Simulate without writing}';

    protected $description = 'Archive stale guest records';

    public function handle(): int
    {
        $guestDays = max(30, (int) $this->option('guest-days'));
        $dryRun    = (bool) $this->option('dry-run');
        $cutoff    = Carbon::now()->subDays($guestDays);

        $query = GuestApplication::query()
            ->where('is_archived', false)
            ->where('converted_to_student', false)
            ->where('created_at', '<', $cutoff);

        $count = (int) (clone $query)->count();
        if ($count === 0) {
            $this->info('Arsivlenecek stale guest kaydi yok.');
            return 0;
        }

        if (!$dryRun) {
            $query->update([
                'is_archived'    => true,
                'archived_at'    => now(),
                'archived_by'    => 'system:artisan',
                'archive_reason' => "stale_{$guestDays}_days",
            ]);
        }

        $mode = $dryRun ? 'dry-run' : 'apply';
        $this->info("archive:inactive-records ({$mode}) tamamlandi | stale guests: {$count}");

        return 0;
    }
}

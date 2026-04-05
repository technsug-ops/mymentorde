<?php

namespace App\Console\Commands;

use App\Models\GuestApplication;
use App\Models\LeadSourceDatum;
use App\Services\LeadSourceTrackingService;
use Illuminate\Console\Command;

class SyncLeadSourceCommand extends Command
{
    protected $signature = 'lead-source:sync-guests
                            {--limit=1000 : Max guests to process}
                            {--only-missing : Skip guests that already have lead source data}';

    protected $description = 'Backfill lead_source_data from guest applications';

    public function handle(LeadSourceTrackingService $trackingService): int
    {
        $limit       = max(1, (int) $this->option('limit'));
        $onlyMissing = (bool) $this->option('only-missing');

        $query = GuestApplication::query()->orderBy('id');
        if ($onlyMissing) {
            $existingGuestIds = LeadSourceDatum::query()
                ->pluck('guest_id')
                ->map(fn ($v) => (string) $v)
                ->filter()
                ->all();
            if (!empty($existingGuestIds)) {
                $query->whereNotIn('id', $existingGuestIds);
            }
        }

        $rows = $query->limit($limit)->get();
        if ($rows->isEmpty()) {
            $this->info('Senkronize edilecek guest kaydi yok.');
            return 0;
        }

        $synced    = 0;
        $converted = 0;
        $failed    = 0;

        foreach ($rows as $guest) {
            try {
                $trackingService->captureFromGuestApplication($guest);
                if ((bool) $guest->converted_to_student) {
                    $trackingService->markConverted($guest);
                    $converted++;
                }
                $synced++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("guest#{$guest->id} sync error: " . $e->getMessage());
            }
        }

        $this->info("lead-source:sync-guests tamamlandi | synced: {$synced} | converted_marked: {$converted} | failed: {$failed}");

        return 0;
    }
}

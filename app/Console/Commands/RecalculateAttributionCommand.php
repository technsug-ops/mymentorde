<?php

namespace App\Console\Commands;

use App\Models\GuestApplication;
use App\Models\LeadTouchpoint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateAttributionCommand extends Command
{
    protected $signature   = 'attribution:recalculate-daily {--date= : Yeniden hesaplanacak tarih (Y-m-d)}';
    protected $description = 'Önceki günün dönüşümleri için attribution touchpoint\'lerini güncelle';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->subDay()->toDateString();

        $this->info("Attribution yeniden hesaplama: {$date}");

        // Mark converting touchpoints for guests that converted on $date
        $convertedGuests = GuestApplication::withoutGlobalScope('company')
            ->where('contract_status', 'approved')
            ->whereDate('updated_at', $date)
            ->pluck('id');

        foreach ($convertedGuests as $guestId) {
            // Mark the last touchpoint as converting
            $lastTouchpoint = LeadTouchpoint::where('guest_application_id', $guestId)
                ->orderByDesc('touched_at')
                ->first();

            if ($lastTouchpoint) {
                // Reset all and mark last
                DB::table('lead_touchpoints')
                    ->where('guest_application_id', $guestId)
                    ->update(['is_converting_touch' => false]);

                $lastTouchpoint->update(['is_converting_touch' => true]);
            }
        }

        $this->info("İşlenen dönüşüm: {$convertedGuests->count()}");
        return 0;
    }
}

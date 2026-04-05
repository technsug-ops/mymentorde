<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Marketing-specific → canonical senior stage mapping
    private const MAP = [
        'verified'    => 'contacted',
        'follow_up'   => 'contacted',
        'interested'  => 'in_progress',
        'qualified'   => 'evaluating',
        'sales_ready' => 'contract_signed',
        'champion'    => 'contract_signed',
    ];

    public function up(): void
    {
        foreach (self::MAP as $from => $to) {
            DB::table('guest_applications')
                ->where('lead_status', $from)
                ->update(['lead_status' => $to]);
        }
    }

    public function down(): void
    {
        // Not reversible — data already existed in mixed state
    }
};

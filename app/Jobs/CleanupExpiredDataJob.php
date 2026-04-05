<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CleanupExpiredDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function handle(): void
    {
        // Süresi dolmuş bulletin okuma kayıtlarını temizle (30 günden eski)
        if (\Illuminate\Support\Facades\Schema::hasTable('bulletin_reads')) {
            DB::table('bulletin_reads')
                ->where('read_at', '<', now()->subDays(30))
                ->delete();
        }

        // Süresi dolmuş password_reset_tokens (24 saatten eski)
        if (\Illuminate\Support\Facades\Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')
                ->where('created_at', '<', now()->subHours(24))
                ->delete();
        }

        // 90 günden eski failed_jobs kayıtları
        if (\Illuminate\Support\Facades\Schema::hasTable('failed_jobs')) {
            DB::table('failed_jobs')
                ->where('failed_at', '<', now()->subDays(90))
                ->delete();
        }
    }
}

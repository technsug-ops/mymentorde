<?php

namespace App\Console\Commands;

use App\Models\GoogleCalendarConnection;
use App\Services\GoogleCalendarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PullGoogleCalendarEventsCommand extends Command
{
    protected $signature = 'calendar:pull-google
                            {--connection= : Sadece bu connection ID için çalıştır}
                            {--dry : Sonuçları yaz ama DB\'yi değiştirme (debug)}';

    protected $description = 'Tüm bağlı Google Calendar hesaplarından değişen event\'leri portal\'a çeker';

    public function handle(GoogleCalendarService $service): int
    {
        $query = GoogleCalendarConnection::query()->where('sync_pull', true);

        if ($id = $this->option('connection')) {
            $query->where('id', (int) $id);
        }

        $connections = $query->get();

        if ($connections->isEmpty()) {
            $this->info('sync_pull=true olan bağlantı bulunamadı.');
            return self::SUCCESS;
        }

        $grand = ['processed' => 0, 'updated' => 0, 'cancelled' => 0, 'errors' => 0, 'connections' => 0];

        foreach ($connections as $conn) {
            try {
                $this->line("→ Pull başlıyor: user_id={$conn->user_id} ({$conn->google_email})");
                $stats = $service->pullForConnection($conn);
                $grand['connections']++;
                foreach (['processed', 'updated', 'cancelled', 'errors'] as $k) {
                    $grand[$k] += $stats[$k] ?? 0;
                }
                $this->line("  processed={$stats['processed']} updated={$stats['updated']} cancelled={$stats['cancelled']} errors={$stats['errors']}");
            } catch (\Throwable $e) {
                Log::error('Google Calendar pull komutu hatası', [
                    'connection_id' => $conn->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ERR user_id={$conn->user_id}: {$e->getMessage()}");
                $grand['errors']++;
            }
        }

        $this->info("Toplam: {$grand['connections']} bağlantı, {$grand['processed']} event işlendi, {$grand['updated']} güncellendi, {$grand['cancelled']} iptal, {$grand['errors']} hata");

        return self::SUCCESS;
    }
}

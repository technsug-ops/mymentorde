<?php

namespace Tests\Support\Jobs;

use App\Models\SystemEventLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Test yardımcısı: çalıştığı andaki ÇÖZÜLMÜŞ MARKA adını kaydeder.
 *
 * Mail şablonları markayı `config('brand')`'den okuduğu için, kuyruk işinin
 * hangi markayla render edildiğini ölçmenin en doğrudan yolu bu.
 */
class CapturesBrandJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $marker)
    {
    }

    public function handle(): void
    {
        SystemEventLog::create([
            'event_type' => 'tenant.queue.brand',
            'message' => $this->marker . '|' . (string) config('brand.name', ''),
        ]);
    }
}

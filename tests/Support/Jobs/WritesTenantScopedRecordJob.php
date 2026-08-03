<?php

namespace Tests\Support\Jobs;

use App\Models\SystemEventLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Test yardımcısı: çalıştığı andaki şirket bağlamına bir kayıt yazar.
 *
 * `company_id` BİLEREK verilmiyor — BelongsToCompany::creating hook'unun
 * TenantContext::writeId()'den hangi şirketi aldığını ölçmek için.
 */
class WritesTenantScopedRecordJob implements ShouldQueue
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
            'event_type' => 'tenant.queue.test',
            'message' => $this->marker,
        ]);
    }
}

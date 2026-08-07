<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Support\TenantScopeReport;
use Illuminate\Contracts\View\View;

/**
 * Tenant kapsam raporu — canlı ölçüm ekranı.
 *
 * ── NEDEN PANELDE ───────────────────────────────────────────────────────
 * Aynı ölçümü `php artisan tenant:scope-report` de veriyor, ama KAS'ta SSH
 * yok: canlıda konsol çalıştırılamıyor. Asıl sayı canlı veride; yereldeki
 * tablolar neredeyse boş olduğu için oradaki rapor bu kararı veremez.
 *
 * Salt okunur. Hiçbir şeyi düzeltmiyor — kararı (trait eklenir mi) insan
 * veriyor, ekran yalnızca ölçüyor.
 */
class TenantScopeController extends Controller
{
    public function index(TenantScopeReport $report): View
    {
        return view('platform.tenant-scope.index', [
            'report' => $report->run(),
        ]);
    }
}

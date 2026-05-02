<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\UniMatchResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * UniMatch wizard funnel analytics — manager için drop-off görünümü.
 *
 * Soru: "Wizard'da nerede insanları kaybediyoruz?"
 *
 * Cevap: 19 step boyunca kullanıcı sayısının azalışı + lead capture +
 * sonuç + convert dönüş oranları.
 */
class UniMatchFunnelController extends Controller
{
    public function index(Request $request): View
    {
        // Time range — varsayılan son 30 gün
        $days = (int) $request->query('days', 30);
        $since = now()->subDays($days);

        $base = UniMatchResponse::query()->where('started_at', '>=', $since);

        // Toplam başlatan
        $totalStarted = (clone $base)->count();

        // Step bazlı: current_step >= N olan kullanıcı sayısı
        $stepFunnel = [];
        $criticalSteps = [1, 6, 12, 13, 17, 19];
        foreach ($criticalSteps as $step) {
            $count = (clone $base)->where('current_step', '>=', $step)->count();
            $stepFunnel[$step] = [
                'count'    => $count,
                'pct'      => $totalStarted > 0 ? round(($count / $totalStarted) * 100, 1) : 0,
                'dropoff'  => $totalStarted > 0 ? round((($totalStarted - $count) / $totalStarted) * 100, 1) : 0,
            ];
        }

        // Lead capture metrics
        $leadCaptured  = (clone $base)->whereNotNull('lead_captured_at')->count();
        $leadConsented = (clone $base)->where('lead_consent_marketing', true)->count();

        // Result + Convert
        $resultViewed = (clone $base)->whereNotNull('result_viewed_at')->count();
        $converted    = (clone $base)->whereNotNull('converted_at')->count();

        // Lead → convert rate
        $leadToConvert = $leadCaptured > 0 ? round(($converted / $leadCaptured) * 100, 1) : 0;

        // Wizard tamamlama avg süresi (saniye)
        $avgDuration = (clone $base)
            ->whereNotNull('completed_at')
            ->whereNotNull('started_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as s')
            ->value('s');
        $avgDurationMin = $avgDuration ? round($avgDuration / 60, 1) : null;

        // Son 10 lead (yöneticinin görmesi için)
        $recentLeads = (clone $base)
            ->whereNotNull('lead_captured_at')
            ->orderByDesc('lead_captured_at')
            ->limit(10)
            ->get(['id', 'lead_first_name', 'lead_email', 'lead_phone', 'lead_consent_marketing', 'lead_captured_at', 'completed_at', 'converted_at', 'current_step', 'recommendations', 'source', 'utm_campaign']);

        // UTM source breakdown — hangi marketing kanalı kaç lead/kayıt getiriyor
        $utmBreakdown = (clone $base)
            ->selectRaw("
                COALESCE(NULLIF(source, ''), 'organic') as channel,
                COUNT(*) as started,
                SUM(CASE WHEN lead_captured_at IS NOT NULL THEN 1 ELSE 0 END) as leads,
                SUM(CASE WHEN converted_at IS NOT NULL THEN 1 ELSE 0 END) as converts
            ")
            ->groupBy('channel')
            ->orderByDesc('started')
            ->limit(10)
            ->get();

        return view('manager.unimatch-funnel.index', [
            'days'            => $days,
            'totalStarted'    => $totalStarted,
            'stepFunnel'      => $stepFunnel,
            'leadCaptured'    => $leadCaptured,
            'leadConsented'   => $leadConsented,
            'resultViewed'    => $resultViewed,
            'converted'       => $converted,
            'leadToConvert'   => $leadToConvert,
            'avgDurationMin'  => $avgDurationMin,
            'recentLeads'     => $recentLeads,
            'utmBreakdown'    => $utmBreakdown,
        ]);
    }
}

<?php

namespace App\Services\Marketing;

use App\Models\Document;
use App\Models\DmMessage;
use App\Models\DmThread;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\StudentRevenue;
use Illuminate\Support\Carbon;

class PredictiveService
{
    /**
     * Lead conversion olasılığı (0-100).
     * Basit weighted feature score — gerçek ML için AI API kullanılabilir.
     */
    public function conversionProbability(int $guestId): array
    {
        $guest = GuestApplication::findOrFail($guestId);

        $gstId = 'GST-' . str_pad($guest->id, 8, '0', STR_PAD_LEFT);

        $features = [
            'lead_score'          => (int) ($guest->lead_score ?? 0),
            'docs_uploaded'       => Document::where('student_id', $gstId)->count(),
            'days_since_register' => $guest->created_at ? (int) now()->diffInDays($guest->created_at) : 999,
            'has_senior'          => ! empty($guest->assigned_senior_email) ? 1 : 0,
            'has_package'         => ! empty($guest->selected_package_code) ? 1 : 0,
            'dm_messages'         => DmMessage::whereIn('thread_id',
                DmThread::where('guest_application_id', $guest->id)->pluck('id')
            )->count(),
            'ticket_count'        => GuestTicket::where('guest_application_id', $guest->id)->count(),
        ];

        $weights = [
            'lead_score'          => 0.35,
            'docs_uploaded'       => 0.15,
            'has_senior'          => 0.15,
            'has_package'         => 0.10,
            'dm_messages'         => 0.10,
            'ticket_count'        => 0.05,
            'days_since_register' => -0.10,
        ];

        $rawScore = collect($weights)->sum(fn ($w, $k) => $w * ($features[$k] ?? 0));
        $probability = max(0, min(100, (int) round(50 + $rawScore)));

        return [
            'guest_id'    => $guestId,
            'guest_name'  => trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')),
            'probability' => $probability,
            'features'    => $features,
            'risk_level'  => $probability >= 70 ? 'high_convert' : ($probability >= 40 ? 'medium' : 'low_convert'),
            'risk_label'  => $probability >= 70 ? 'Yüksek Potansiyel' : ($probability >= 40 ? 'Orta Potansiyel' : 'Düşük Potansiyel'),
        ];
    }

    /**
     * Gelir projeksiyonu — son 12 ay trend + pipeline bazlı beklenti.
     */
    public function revenueProjection(int $months = 3): array
    {
        $history = collect(range(11, 0))->map(fn ($ago) => [
            'month'   => now()->subMonths($ago)->format('Y-m'),
            'revenue' => (float) StudentRevenue::whereBetween('updated_at', [
                now()->subMonths($ago)->startOfMonth(),
                now()->subMonths($ago)->endOfMonth(),
            ])->sum('total_earned'),
        ]);

        $packages = config('service_packages.packages', []);
        $stageWeights = config('pipeline_mapping.stage_weights', [
            'new' => 0.15, 'contacted' => 0.25, 'docs_pending' => 0.40,
            'evaluating' => 0.55, 'offer_sent' => 0.70, 'contract_signed' => 0.90,
        ]);

        $pipelineExpected = GuestApplication::whereIn('contract_status', ['requested', 'pending_manager', 'signed_uploaded'])
            ->get()
            ->sum(function ($g) use ($packages, $stageWeights) {
                $pkg = collect($packages)->firstWhere('code', $g->selected_package_code);
                $weight = $stageWeights[$g->lead_status ?? 'new'] ?? 0.15;
                return ((float) ($pkg['price_amount'] ?? 0)) * $weight;
            });

        $recentAvg = $history->take(-3)->avg('revenue') ?? 0;
        $projection = collect(range(1, $months))->map(fn ($m) => [
            'month'     => now()->addMonths($m)->format('Y-m'),
            'projected' => round($recentAvg * (1 + 0.02 * $m), 2),
        ]);

        return [
            'history'           => $history->values(),
            'projection'        => $projection->values(),
            'pipeline_expected' => round($pipelineExpected, 2),
            'recent_avg'        => round($recentAvg, 2),
        ];
    }

    /**
     * Churn riski — 30+ gün inaktif guest'ler.
     */
    public function churnRisk(): array
    {
        $threshold = now()->subDays(30);

        $atRisk = GuestApplication::whereNull('converted_to_student')
            ->where(fn ($q) => $q->whereNull('last_senior_action_at')->orWhere('last_senior_action_at', '<', $threshold))
            ->whereNotIn('contract_status', ['approved', 'cancelled'])
            ->select(['id', 'first_name', 'last_name', 'email', 'lead_score', 'lead_status', 'last_senior_action_at', 'created_at'])
            ->orderBy('lead_score', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($g) => [
                'guest_id'           => $g->id,
                'name'               => trim(($g->first_name ?? '') . ' ' . ($g->last_name ?? '')),
                'email'              => $g->email,
                'lead_score'         => $g->lead_score,
                'lead_status'        => $g->lead_status,
                'days_inactive'      => $g->last_senior_action_at
                    ? (int) now()->diffInDays(Carbon::parse($g->last_senior_action_at))
                    : (int) now()->diffInDays($g->created_at),
                'risk'               => 'high',
            ]);

        return [
            'total_at_risk' => $atRisk->count(),
            'leads'         => $atRisk->values(),
            'threshold_days' => 30,
        ];
    }
}

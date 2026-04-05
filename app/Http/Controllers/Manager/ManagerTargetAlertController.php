<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\ManagerDashboardTrait;
use App\Models\GuestApplication;
use App\Models\ManagerAlertRule;
use App\Models\ManagerPerformanceTarget;
use App\Models\User;
use App\Services\DashboardKPIService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManagerTargetAlertController extends Controller
{
    use ManagerDashboardTrait;

    public function __construct(private readonly DashboardKPIService $kpi) {}

    // =========================================================================
    // Hedef Yönetimi
    // =========================================================================

    public function targets(Request $request)
    {
        $cid    = $this->companyId();
        $period = (string) $request->query('period', now()->format('Y-m'));

        $targets = ManagerPerformanceTarget::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->where('period', $period)
            ->get();

        $periods = ManagerPerformanceTarget::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->distinct()->orderByDesc('period')->pluck('period');

        $seniors = User::query()
            ->whereIn('role', ['senior', 'mentor'])
            ->orderBy('name')
            ->get(['name', 'email']);

        return view('manager.targets', compact('targets', 'period', 'periods', 'seniors'));
    }

    public function targetStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'period'                  => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'target_type'             => ['required', 'in:company_wide,senior_specific'],
            'senior_email'            => ['nullable', 'email'],
            'target_revenue'          => ['nullable', 'numeric', 'min:0'],
            'target_conversions'      => ['nullable', 'integer', 'min:0'],
            'target_new_guests'       => ['nullable', 'integer', 'min:0'],
            'target_doc_reviews'      => ['nullable', 'integer', 'min:0'],
            'target_contracts_signed' => ['nullable', 'integer', 'min:0'],
            'notes'                   => ['nullable', 'string', 'max:1000'],
        ]);

        $cid = $this->companyId();

        ManagerPerformanceTarget::updateOrCreate(
            [
                'company_id'   => $cid,
                'period'       => $data['period'],
                'target_type'  => $data['target_type'],
                'senior_email' => $data['senior_email'] ?? null,
            ],
            [
                'target_revenue'          => (float) ($data['target_revenue'] ?? 0),
                'target_conversions'      => (int) ($data['target_conversions'] ?? 0),
                'target_new_guests'       => (int) ($data['target_new_guests'] ?? 0),
                'target_doc_reviews'      => (int) ($data['target_doc_reviews'] ?? 0),
                'target_contracts_signed' => (int) ($data['target_contracts_signed'] ?? 0),
                'set_by_user_id'          => optional($request->user())->id,
                'notes'                   => $data['notes'] ?? null,
            ]
        );

        return redirect('/manager/targets?period=' . urlencode($data['period']))->with('status', 'Hedef kaydedildi.');
    }

    public function targetsReport(Request $request)
    {
        $cid    = $this->companyId();
        $period = (string) $request->query('period', now()->format('Y-m'));

        [$start, $end] = [
            Carbon::parse($period . '-01')->startOfMonth(),
            Carbon::parse($period . '-01')->endOfMonth(),
        ];

        ['stats' => $stats, 'funnel' => $funnel] = $this->kpi->managerStatsAndFunnel($start, $end, '');

        $companyTarget = ManagerPerformanceTarget::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->where('period', $period)
            ->where('target_type', 'company_wide')
            ->first();

        $targetVsActual = null;
        if ($companyTarget) {
            $kpiNewGuests = GuestApplication::query()
                ->whereBetween('created_at', [$start, $end])
                ->whereNull('archived_at')
                ->count();

            $targetVsActual = [
                'revenue'    => [
                    'target' => $companyTarget->target_revenue,
                    'actual' => $stats['monthly_revenue'],
                    'pct'    => $companyTarget->target_revenue > 0 ? round($stats['monthly_revenue'] / $companyTarget->target_revenue * 100) : 0,
                ],
                'conversions'=> [
                    'target' => $companyTarget->target_conversions,
                    'actual' => $funnel[2]['count'] ?? 0,
                    'pct'    => $companyTarget->target_conversions > 0 ? round(($funnel[2]['count'] ?? 0) / $companyTarget->target_conversions * 100) : 0,
                ],
                'new_guests' => [
                    'target' => $companyTarget->target_new_guests,
                    'actual' => $kpiNewGuests,
                    'pct'    => $companyTarget->target_new_guests > 0 ? round($kpiNewGuests / $companyTarget->target_new_guests * 100) : 0,
                ],
            ];
        }

        $seniorTargets = ManagerPerformanceTarget::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->where('period', $period)
            ->where('target_type', 'senior_specific')
            ->get();

        $periods = ManagerPerformanceTarget::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->distinct()->orderByDesc('period')->pluck('period');

        return view('manager.targets-report', compact(
            'period', 'periods', 'companyTarget', 'targetVsActual', 'seniorTargets', 'stats'
        ));
    }

    // =========================================================================
    // Alert Kuralları
    // =========================================================================

    public function alertRules(Request $request)
    {
        $cid   = $this->companyId();
        $rules = ManagerAlertRule::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('name')
            ->get();

        return view('manager.alert-rules', [
            'rules'           => $rules,
            'conditionLabels' => ManagerAlertRule::CONDITION_LABELS,
            'frequencyLabels' => ManagerAlertRule::FREQUENCY_LABELS,
        ]);
    }

    public function alertRuleStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:180'],
            'condition_type'  => ['required', 'in:risk_score_above,revenue_below,inactive_students,pending_docs_above,overdue_outcomes'],
            'threshold_value' => ['required', 'numeric'],
            'check_frequency' => ['required', 'in:hourly,daily,weekly'],
            'notify_emails'   => ['nullable', 'string', 'max:2000'],
        ]);

        $cid = $this->companyId();

        $emails = $data['notify_emails']
            ? collect(explode(',', (string) $data['notify_emails']))
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL))
                ->values()->all()
            : null;

        ManagerAlertRule::create([
            'company_id'      => $cid,
            'name'            => $data['name'],
            'condition_type'  => $data['condition_type'],
            'threshold_value' => (float) $data['threshold_value'],
            'check_frequency' => $data['check_frequency'],
            'notify_channels' => $emails ? ['in_app', 'email'] : ['in_app'],
            'notify_emails'   => $emails,
            'is_active'       => true,
            'created_by'      => optional($request->user())->email,
        ]);

        return redirect('/manager/alert-rules')->with('status', 'Alert kuralı oluşturuldu.');
    }

    public function alertRuleUpdate(Request $request, ManagerAlertRule $alertRule): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'name'            => ['sometimes', 'string', 'max:180'],
            'threshold_value' => ['sometimes', 'numeric'],
            'check_frequency' => ['sometimes', 'in:hourly,daily,weekly'],
            'is_active'       => ['sometimes', 'boolean'],
        ]);

        $alertRule->update($data);

        return response()->json(['ok' => true]);
    }

    public function alertRuleDestroy(ManagerAlertRule $alertRule): \Illuminate\Http\RedirectResponse
    {
        $alertRule->delete();
        return redirect('/manager/alert-rules')->with('status', 'Alert kuralı silindi.');
    }
}

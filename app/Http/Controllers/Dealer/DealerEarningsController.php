<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dealer\Concerns\DealerPortalTrait;
use App\Models\DealerPayoutAccount;
use App\Models\DealerPayoutRequest;
use App\Models\DealerStudentRevenue;
use App\Models\RevenueMilestone;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;

class DealerEarningsController extends Controller
{
    use DealerPortalTrait;

    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function earnings(Request $request)
    {
        $data           = $this->baseData($request);
        $rows           = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
        $pendingCount   = 0;
        $completedCount = 0;
        $summary        = ['earned' => 0.0, 'pending' => 0.0, 'paid' => 0.0, 'month' => 0.0, 'students' => 0];

        $filterStatus  = trim((string) $request->query('status', ''));
        $filterFrom    = trim((string) $request->query('from', ''));
        $filterTo      = trim((string) $request->query('to', ''));
        $filterStudent = trim((string) $request->query('student', ''));

        if (!empty($data['dealerCode'])) {
            $baseQuery = DealerStudentRevenue::query()
                ->where('dealer_id', $data['dealerCode'])
                ->when($filterStudent !== '', fn ($q) => $q->where('student_id', 'like', "%{$filterStudent}%"))
                ->when($filterFrom !== '', fn ($q) => $q->whereDate('updated_at', '>=', $filterFrom))
                ->when($filterTo !== '', fn ($q) => $q->whereDate('updated_at', '<=', $filterTo))
                ->when($filterStatus === 'pending', fn ($q) => $q->where('total_pending', '>', 0))
                ->when($filterStatus === 'earned',  fn ($q) => $q->where('total_earned', '>', 0)->where('total_pending', '<=', 0))
                ->when($filterStatus === 'empty',   fn ($q) => $q->where('total_earned', '<=', 0)->where('total_pending', '<=', 0));

            $summaryAgg = (clone $baseQuery)->selectRaw(
                'COALESCE(SUM(total_earned),0) as earned, COALESCE(SUM(total_pending),0) as pending, COUNT(DISTINCT student_id) as students'
            )->first();
            $summary['earned']   = (float) ($summaryAgg->earned  ?? 0);
            $summary['pending']  = (float) ($summaryAgg->pending ?? 0);
            $summary['students'] = (int)   ($summaryAgg->students ?? 0);
            $summary['paid']     = max(0.0, round($summary['earned'] - $summary['pending'], 2));
            $summary['month']    = (float) DealerStudentRevenue::query()
                ->where('dealer_id', $data['dealerCode'])
                ->where('updated_at', '>=', now()->startOfMonth())
                ->sum('total_earned');

            $pendingCount   = (clone $baseQuery)->where('total_pending', '>', 0)->count();
            $completedCount = (clone $baseQuery)->where('total_earned', '>', 0)->where('total_pending', '<=', 0)->count();
            $rows           = $baseQuery->latest('updated_at')->paginate(25);
        }

        $allRevenues = DealerStudentRevenue::query()
            ->where('dealer_id', $data['dealerCode'] ?? '')
            ->get(['student_id', 'milestone_progress', 'total_earned']);

        $milestoneKeys    = ['DM-001' => 'Kayıt', 'DM-002' => 'Üniversite Kabul', 'DM-003' => 'Vize', 'DM-004' => 'Tamamlandı'];
        $milestoneTracker = collect($milestoneKeys)->map(function ($label, $key) use ($allRevenues) {
            $reached = $allRevenues->filter(fn ($r) => !empty((array) ($r->milestone_progress ?? []))
                && !empty(((array) ($r->milestone_progress ?? []))[$key]))->count();
            return ['key' => $key, 'label' => $label, 'reached' => $reached, 'total' => $allRevenues->count(),
                    'pct' => $allRevenues->count() > 0 ? round($reached / $allRevenues->count() * 100) : 0];
        })->values();

        $activeMilestone    = RevenueMilestone::query()->where('is_active', true)->orderByDesc('sort_order')->first();
        $currentRate        = (float) ($activeMilestone?->percentage ?? 0);
        $commissionBreakdown = $allRevenues->map(fn ($r) => [
            'student_id'        => $r->student_id,
            'total_earned'      => (float) ($r->total_earned ?? 0),
            'commission_amount' => round((float) ($r->total_earned ?? 0) * $currentRate / 100, 2),
            'milestone_count'   => count(array_filter((array) ($r->milestone_progress ?? []))),
        ]);

        return view('dealer.earnings', $data + compact(
            'rows', 'summary', 'filterStatus', 'filterFrom', 'filterTo', 'filterStudent',
            'milestoneTracker', 'currentRate', 'commissionBreakdown', 'pendingCount', 'completedCount'
        ));
    }

    public function earningsExport(Request $request)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing');

        $rows = DealerStudentRevenue::query()
            ->where('dealer_id', $data['dealerCode'])
            ->latest('updated_at')->limit(1000)
            ->get(['student_id', 'dealer_type', 'total_earned', 'total_pending', 'updated_at']);

        $csv = "student_id,dealer_type,total_earned,total_pending,guncelleme\n";
        foreach ($rows as $r) {
            $csv .= implode(',', [
                $r->student_id, $r->dealer_type ?: '-',
                number_format((float) ($r->total_earned ?? 0), 2, '.', ''),
                number_format((float) ($r->total_pending ?? 0), 2, '.', ''),
                optional($r->updated_at)->format('Y-m-d H:i') ?: '-',
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="kazanclar_'.date('Y-m-d').'.csv"',
        ]);
    }

    public function payments(Request $request)
    {
        $data              = $this->baseData($request);
        $accounts          = collect();
        $payoutRequests    = collect();
        $totalPending      = 0.0;
        $pendingRequestTotal = 0.0;

        if (!empty($data['dealerCode'])) {
            $accounts = DealerPayoutAccount::query()
                ->where('dealer_code', $data['dealerCode'])
                ->orderByDesc('is_default')->get();

            $payoutRequests = DealerPayoutRequest::query()
                ->where('dealer_code', $data['dealerCode'])
                ->with('account')->latest()->limit(50)->get();

            $totalPending = (float) DealerStudentRevenue::query()
                ->where('dealer_id', $data['dealerCode'])->sum('total_pending');

            $pendingRequestTotal = (float) DealerPayoutRequest::query()
                ->where('dealer_code', $data['dealerCode'])
                ->whereIn('status', ['requested', 'approved'])->sum('amount');
        }

        // Bonus: unlocked ise çekilebilir bakiyeye ekle
        $bonusAdd = 0.0;
        if ($data['dealer'] instanceof \App\Models\Dealer && $data['dealer']->isBonusUnlocked()) {
            $bonusAdd = (float) ($data['dealer']->signup_bonus_amount ?? 0);
        }
        $netAvailable = max(0.0, $totalPending - $pendingRequestTotal + $bonusAdd);

        return view('dealer.payments', $data + compact('accounts', 'payoutRequests', 'totalPending', 'pendingRequestTotal', 'netAvailable', 'bonusAdd'));
    }

    public function addPayoutAccount(Request $request)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing');

        $validated = $request->validate([
            'bank_name'      => ['required', 'string', 'max:120'],
            'iban'           => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:160'],
            'is_default'     => ['nullable', 'boolean'],
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            DealerPayoutAccount::query()->where('dealer_code', $data['dealerCode'])->update(['is_default' => false]);
        }

        DealerPayoutAccount::query()->create([
            'dealer_code'    => $data['dealerCode'],
            'bank_name'      => trim((string) $validated['bank_name']),
            'iban'           => strtoupper(trim((string) $validated['iban'])),
            'account_holder' => trim((string) $validated['account_holder']),
            'is_default'     => $isDefault,
        ]);

        return redirect('/dealer/payments')->with('status', 'Banka hesabı eklendi.');
    }

    public function deletePayoutAccount(Request $request, int $id)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403);

        $account = DealerPayoutAccount::query()
            ->where('id', $id)->where('dealer_code', $data['dealerCode'])->firstOrFail();
        $account->delete();

        return redirect('/dealer/payments')->with('status', 'Hesap silindi.');
    }

    public function requestPayout(Request $request)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing');

        $validated = $request->validate([
            'amount'            => ['required', 'numeric', 'min:100'],
            'payout_account_id' => ['required', 'integer'],
        ]);

        $account = DealerPayoutAccount::query()
            ->where('id', (int) $validated['payout_account_id'])
            ->where('dealer_code', $data['dealerCode'])->firstOrFail();

        DealerPayoutRequest::query()->create([
            'dealer_code'         => $data['dealerCode'],
            'payout_account_id'   => $account->id,
            'amount'              => (float) $validated['amount'],
            'currency'            => 'EUR',
            'status'              => 'requested',
            'requested_by_email'  => (string) ($request->user()?->email ?? ''),
        ]);

        return redirect('/dealer/payments')->with('status', 'Ödeme talebiniz iletildi. Manager onayı bekleniyor.');
    }
}

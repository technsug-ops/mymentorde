<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\SeniorPayout;
use App\Models\User;
use App\Services\Booking\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Marketplace Phase 6 — Senior Payout listesi, detay ve retry.
 */
class PayoutsController extends Controller
{
    public function index(Request $request): View
    {
        $status      = (string) $request->query('status', '');
        $seniorId    = (int) $request->query('senior', 0);
        $period      = (string) $request->query('period', ''); // YYYY-MM

        $query = SeniorPayout::query()->with('senior')->orderByDesc('created_at');

        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($seniorId > 0) {
            $query->where('senior_user_id', $seniorId);
        }
        if ($period !== '' && preg_match('/^\d{4}-\d{2}$/', $period)) {
            $query->where('period_start', '<=', $period . '-31')
                  ->where('period_end', '>=', $period . '-01');
        }

        $payouts = $query->paginate(25)->withQueryString();

        // KPI
        $kpis = [
            'pending'    => SeniorPayout::query()->where('status', 'pending')->count(),
            'processing' => SeniorPayout::query()->where('status', 'processing')->count(),
            'paid'       => SeniorPayout::query()->where('status', 'paid')->count(),
            'failed'     => SeniorPayout::query()->where('status', 'failed')->count(),
            'total_paid_cents' => (int) SeniorPayout::query()->where('status', 'paid')->sum('amount_cents'),
        ];

        $seniors = User::query()
            ->where('role', 'senior')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('manager.payouts.index', [
            'payouts'  => $payouts,
            'kpis'     => $kpis,
            'seniors'  => $seniors,
            'filters'  => compact('status', 'seniorId', 'period'),
        ]);
    }

    public function show(int $id): View
    {
        $payout = SeniorPayout::query()->with(['senior', 'earnings'])->findOrFail($id);

        return view('manager.payouts.show', [
            'payout' => $payout,
        ]);
    }

    public function retry(int $id, PayoutService $service): RedirectResponse
    {
        $payout = SeniorPayout::query()->findOrFail($id);
        $result = $service->retryPayout($payout);

        $msg = sprintf(
            'Retry tamamlandı: %d başarılı, %d hata.',
            $result['success'] ?? 0,
            $result['failed'] ?? 0
        );

        return redirect()->route('manager.payouts.show', $id)
            ->with(($result['success'] ?? 0) > 0 ? 'success' : 'error', $msg);
    }
}

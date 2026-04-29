<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DealerCommissionTier;
use App\Services\EventLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Manager — bayi komisyon kademesi (tier) yönetimi.
 *
 * Pazarlama (/satis-ortagi) sayfasındaki tier matrisini buradan düzenler:
 * eşik (min/max kayıt), komisyon oranı (€ veya %), avantaj metni.
 *
 * NOT: Genişletilebilir altyapı — yeni tier eklemek için sadece
 * "+ Yeni Tier" tıklayıp form doldurmak yeterli. UI ve resolver otomatik
 * çalışır (config/cron yok).
 */
class DealerCommissionTierController extends Controller
{
    public function __construct(private readonly EventLogService $eventLogService) {}

    public function index(): View
    {
        $tiers = DealerCommissionTier::query()
            ->orderBy('dealer_type_code')
            ->orderBy('display_order')
            ->orderBy('min_count')
            ->get()
            ->groupBy('dealer_type_code');

        return view('manager.dealer-tiers.index', [
            'tiersByType' => $tiers,
        ]);
    }

    public function edit(DealerCommissionTier $dealerCommissionTier): View
    {
        return view('manager.dealer-tiers.form', [
            'mode' => 'edit',
            'tier' => $dealerCommissionTier,
        ]);
    }

    public function update(Request $request, DealerCommissionTier $dealerCommissionTier): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $dealerCommissionTier->update($data);

        $this->eventLogService->log(
            eventType: 'dealer_tier_updated',
            entityType: 'dealer_commission_tier',
            entityId: (string) $dealerCommissionTier->id,
            message: 'Manager #' . (Auth::id() ?? '?') . ' tier güncelledi: ' . $dealerCommissionTier->tier_code,
        );

        return redirect()->route('manager.dealer-tiers.index')->with('success', 'Tier güncellendi.');
    }

    public function create(): View
    {
        return view('manager.dealer-tiers.form', [
            'mode' => 'create',
            'tier' => new DealerCommissionTier(['is_active' => true, 'display_order' => 99]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $companyId = (int) (Auth::user()?->company_id ?? 0);
        DealerCommissionTier::create($data + ['company_id' => $companyId]);

        return redirect()->route('manager.dealer-tiers.index')->with('success', 'Yeni tier oluşturuldu.');
    }

    public function toggleActive(DealerCommissionTier $dealerCommissionTier): RedirectResponse
    {
        $dealerCommissionTier->update(['is_active' => ! $dealerCommissionTier->is_active]);
        return back()->with('success', $dealerCommissionTier->is_active ? 'Tier aktif edildi.' : 'Tier pasif edildi.');
    }

    /** @return array<string,mixed> */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'tier_code'                => 'required|string|max:32|regex:/^[a-z0-9_]+$/',
            'tier_name'                => 'required|string|max:64',
            'tier_emoji'               => 'nullable|string|max:8',
            'dealer_type_code'         => 'required|string|max:32',
            'min_count'                => 'required|integer|min:0',
            'max_count'                => 'nullable|integer|gte:min_count',
            'commission_rate_eur'      => 'nullable|numeric|min:0',
            'commission_rate_percent'  => 'nullable|numeric|min:0|max:100',
            'advantages_text'          => 'nullable|string|max:500',
            'display_order'            => 'required|integer|min:0|max:9999',
            'is_active'                => 'sometimes|boolean',
        ]);
    }
}

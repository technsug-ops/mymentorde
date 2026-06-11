<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\CommissionRule;
use App\Services\Booking\CommissionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Marketplace Phase 6 — Manager Komisyon Kuralları CRUD
 *
 * Komisyon kuralları priority-based lookup matrix (tier × hizmet türü).
 * Priority düşük = yüksek öncelik.
 */
class CommissionRulesController extends Controller
{
    public const TIERS = [
        'junior'  => 'Junior',
        'mid'     => 'Mid',
        'senior'  => 'Senior',
        'expert'  => 'Expert',
    ];

    public const SERVICE_TYPES = [
        'consultation'      => 'Danışma Görüşmesi',
        'document_review'   => 'Belge İncelemesi',
        'mock_interview'    => 'Mülakat Provası',
        'pathway_planning'  => 'Yol Haritası Planlama',
    ];

    public function index(): View
    {
        $rules = CommissionRule::query()
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('manager.commission-rules.index', [
            'rules'         => $rules,
            'tiers'         => self::TIERS,
            'serviceTypes'  => self::SERVICE_TYPES,
            'defaultPct'    => CommissionResolver::DEFAULT_PCT,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        CommissionRule::query()->create($data);
        $this->flushCache($data['company_id'] ?? null);

        return redirect()->route('manager.commission-rules.index')
            ->with('success', 'Komisyon kuralı eklendi.');
    }

    public function update(Request $request, CommissionRule $commissionRule): RedirectResponse
    {
        $data = $this->validateData($request, $commissionRule);
        $commissionRule->update($data);
        $this->flushCache((int) $commissionRule->company_id);

        return redirect()->route('manager.commission-rules.index')
            ->with('success', 'Komisyon kuralı güncellendi.');
    }

    public function destroy(CommissionRule $commissionRule): RedirectResponse
    {
        $companyId = (int) $commissionRule->company_id;
        $commissionRule->delete();
        $this->flushCache($companyId);

        return redirect()->route('manager.commission-rules.index')
            ->with('success', 'Komisyon kuralı silindi.');
    }

    private function validateData(Request $request, ?CommissionRule $existing = null): array
    {
        $validated = $request->validate([
            'rule_name'               => ['required', 'string', 'max:120'],
            'applies_to_tier'         => ['nullable', 'string', 'in:junior,mid,senior,expert'],
            'applies_to_service_type' => ['nullable', 'string', 'in:consultation,document_review,mock_interview,pathway_planning'],
            'commission_pct'          => ['required', 'numeric', 'min:0', 'max:100'],
            'priority'                => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active'               => ['nullable'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['priority']  = (int) ($validated['priority'] ?? 100);

        // company_id global scope creating event'inde otomatik atanıyor — yine de aktif şirketi referansta tutuyoruz.
        return $validated;
    }

    private function flushCache(?int $companyId): void
    {
        if ($companyId) {
            CommissionResolver::flushCacheForCompany($companyId);
        }
    }
}

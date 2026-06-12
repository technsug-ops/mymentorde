<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PromoCode;
use App\Models\PromoCodeRedemption;
use App\Services\Platform\PromoCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Platform Owner — Promo Code / Discount Yonetimi.
 *
 * Indirim kodu CRUD + redemption analizi. Cross-tenant — sadece Platform
 * Owner erisebilir (route grubu 'platform.owner' middleware ile korumalı).
 *
 * destroy() soft delete YERINE is_active=false set eder; veri korumak icin.
 */
class PromoCodeController extends Controller
{
    public function __construct(private readonly PromoCodeService $promoService)
    {
    }

    // ────────────────────────────────────────────────────────────────────────
    // INDEX — Kod listesi + KPI
    // ────────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $activeFilter = trim((string) $request->query('active', ''));
        $typeFilter   = trim((string) $request->query('type', ''));

        $query = PromoCode::query();

        if ($activeFilter === '1' || $activeFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($activeFilter === '0' || $activeFilter === 'inactive') {
            $query->where('is_active', false);
        }
        if ($typeFilter !== '' && in_array($typeFilter, PromoCode::TYPES, true)) {
            $query->where('type', $typeFilter);
        }

        $codes = $query->orderByDesc('id')->paginate(20)->withQueryString();

        // ── KPI'lar ────────────────────────────────────────────────────────
        $activeCount = PromoCode::query()->where('is_active', true)->count();
        $totalRedemptions = PromoCodeRedemption::query()->count();
        $totalDiscount = (float) PromoCodeRedemption::query()->sum('discount_applied_eur');
        $avgDiscount = $totalRedemptions > 0 ? round($totalDiscount / $totalRedemptions, 2) : 0.0;

        return view('platform.promo-codes.index', [
            'codes'             => $codes,
            'filters'           => [
                'active' => $activeFilter,
                'type'   => $typeFilter,
            ],
            'types'             => PromoCode::TYPES,
            'activeCount'       => $activeCount,
            'totalRedemptions'  => $totalRedemptions,
            'totalDiscount'     => $totalDiscount,
            'avgDiscount'       => $avgDiscount,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // CREATE — Form
    // ────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('platform.promo-codes.create', [
            'types' => PromoCode::TYPES,
            'tiers' => [
                ['value' => '',                       'label' => 'Tüm tier\'lar'],
                ['value' => Company::TIER_BASIC,      'label' => 'Basic'],
                ['value' => Company::TIER_GOLD,      'label' => 'Gold'],
                ['value' => Company::TIER_PREMIUM,   'label' => 'Premium'],
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // STORE
    // ────────────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        try {
            $code = $this->promoService->createCode([
                'code'               => $request->input('code'),
                'type'               => $request->input('type'),
                'value'              => $request->input('value'),
                'duration_months'    => $request->input('duration_months'),
                'applies_to_tier'    => $request->input('applies_to_tier'),
                'max_uses'           => $request->input('max_uses'),
                'valid_from'         => $request->input('valid_from'),
                'valid_until'        => $request->input('valid_until'),
                'description'        => $request->input('description'),
                'is_active'          => $request->boolean('is_active', true),
                'created_by_user_id' => auth()->id(),
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('platform.promo-codes.show', $code->id)
            ->with('success', "İndirim kodu oluşturuldu: {$code->code}");
    }

    // ────────────────────────────────────────────────────────────────────────
    // SHOW — Detay + redemption listesi
    // ────────────────────────────────────────────────────────────────────────

    public function show(int $id): View
    {
        /** @var PromoCode $code */
        $code = PromoCode::query()->findOrFail($id);

        $redemptions = PromoCodeRedemption::query()
            ->with('company')
            ->where('promo_code_id', $code->id)
            ->orderByDesc('applied_at')
            ->paginate(20);

        $stats = $this->promoService->getStats($code);

        return view('platform.promo-codes.show', [
            'code'        => $code,
            'redemptions' => $redemptions,
            'stats'       => $stats,
            'tiers'       => [
                ['value' => '',                     'label' => 'Tüm tier\'lar'],
                ['value' => Company::TIER_BASIC,    'label' => 'Basic'],
                ['value' => Company::TIER_GOLD,     'label' => 'Gold'],
                ['value' => Company::TIER_PREMIUM,  'label' => 'Premium'],
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // UPDATE
    // ────────────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var PromoCode $code */
        $code = PromoCode::query()->findOrFail($id);

        $validated = $request->validate([
            'description'     => ['nullable', 'string', 'max:300'],
            'max_uses'        => ['nullable', 'integer', 'min:1'],
            'valid_from'      => ['required', 'date'],
            'valid_until'     => ['required', 'date', 'after_or_equal:valid_from'],
            'is_active'       => ['nullable', 'boolean'],
            'applies_to_tier' => ['nullable', 'string', 'in:basic,gold,premium'],
        ]);

        $code->fill([
            'description'     => $validated['description'] ?? null,
            'max_uses'        => $validated['max_uses'] ?? null,
            'valid_from'      => $validated['valid_from'],
            'valid_until'     => $validated['valid_until'],
            'is_active'       => $request->boolean('is_active'),
            'applies_to_tier' => $validated['applies_to_tier'] ?? null,
        ]);

        // current_uses, max_uses'i asacaksa engelle
        if ($code->max_uses !== null && $code->current_uses > $code->max_uses) {
            return back()
                ->with('error', "Max kullanım ({$code->max_uses}), mevcut kullanımdan ({$code->current_uses}) küçük olamaz.")
                ->withInput();
        }

        $code->save();

        return redirect()
            ->route('platform.promo-codes.show', $code->id)
            ->with('success', "İndirim kodu güncellendi: {$code->code}");
    }

    // ────────────────────────────────────────────────────────────────────────
    // DESTROY — Soft deactivate (DB'den silmiyor; audit gerek)
    // ────────────────────────────────────────────────────────────────────────

    public function destroy(int $id): RedirectResponse
    {
        /** @var PromoCode $code */
        $code = PromoCode::query()->findOrFail($id);

        $code->is_active = false;
        $code->save();

        return redirect()
            ->route('platform.promo-codes')
            ->with('success', "İndirim kodu devre dışı bırakıldı: {$code->code}");
    }
}

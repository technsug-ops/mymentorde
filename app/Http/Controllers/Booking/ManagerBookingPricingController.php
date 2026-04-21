<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\CommissionRule;
use App\Models\CompanyBookingPricing;
use App\Models\CompanyPaymentSetting;
use App\Models\TaxRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manager booking konfigürasyon kokpiti:
 *   - Fiyatlandırma (süre → net tutar tablosu)
 *   - KDV kuralları
 *   - Ödeme ayarları (is_payment_enabled toggle, komisyon default, payout)
 *   - Komisyon kuralları (senior tier + service type matrix)
 *
 * Route: /manager/booking-pricing (manager.role)
 */
class ManagerBookingPricingController extends Controller
{
    public function index(Request $request): View
    {
        $cid = $this->companyId();
        $pricing        = $this->ensurePricing($cid);
        $paymentSetting = $this->ensurePaymentSettings($cid);
        $taxRules       = TaxRule::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->orderByDesc('priority')
            ->orderBy('rule_name')
            ->get();
        $commissionRules = CommissionRule::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->orderByDesc('priority')
            ->get();

        return view('booking.manager.pricing', [
            'pricing'         => $pricing,
            'paymentSetting'  => $paymentSetting,
            'taxRules'        => $taxRules,
            'commissionRules' => $commissionRules,
            'defaultRules'    => CompanyBookingPricing::defaultRules(),
        ]);
    }

    public function updatePricing(Request $request): RedirectResponse
    {
        $cid = $this->companyId();
        $data = $request->validate([
            'is_free'                   => 'nullable|boolean',
            'currency'                  => 'required|string|size:3',
            'cancellation_window_hours' => 'required|integer|min:0|max:168',
            'rules'                     => 'required|array|min:1',
            'rules.*.duration'          => 'required|integer|min:15|max:240',
            'rules.*.price_net'         => 'required|numeric|min:0|max:9999',
            'rules.*.enabled'           => 'nullable|boolean',
        ]);

        $rules = collect($data['rules'])
            ->map(fn ($r) => [
                'duration'  => (int) $r['duration'],
                'price_net' => round((float) $r['price_net'], 2),
                'enabled'   => (bool) ($r['enabled'] ?? false),
            ])
            ->values()
            ->all();

        $pricing = $this->ensurePricing($cid);
        $pricing->update([
            'is_free'                   => (bool) ($data['is_free'] ?? false),
            'currency'                  => strtoupper($data['currency']),
            'cancellation_window_hours' => (int) $data['cancellation_window_hours'],
            'pricing_rules'             => $rules,
        ]);

        return back()->with('status', 'Fiyatlandırma güncellendi.');
    }

    public function updatePaymentSettings(Request $request): RedirectResponse
    {
        $cid = $this->companyId();
        $data = $request->validate([
            'is_payment_enabled'     => 'nullable|boolean',
            'payout_day_of_month'    => 'required|integer|min:1|max:28',
            'payout_minimum_eur'     => 'required|numeric|min:0|max:10000',
            'allow_on_demand_payout' => 'nullable|boolean',
            'default_commission_pct' => 'required|numeric|min:0|max:100',
            'refund_window_hours'    => 'required|integer|min:0|max:168',
        ]);

        $settings = $this->ensurePaymentSettings($cid);
        $settings->update([
            'is_payment_enabled'     => (bool) ($data['is_payment_enabled'] ?? false),
            'payout_day_of_month'    => (int) $data['payout_day_of_month'],
            'payout_minimum_cents'   => (int) round(((float) $data['payout_minimum_eur']) * 100),
            'allow_on_demand_payout' => (bool) ($data['allow_on_demand_payout'] ?? false),
            'default_commission_pct' => round((float) $data['default_commission_pct'], 2),
            'refund_window_hours'    => (int) $data['refund_window_hours'],
        ]);

        return back()->with('status', 'Ödeme ayarları güncellendi.');
    }

    public function storeTaxRule(Request $request): RedirectResponse
    {
        $cid = $this->companyId();
        $data = $request->validate([
            'rule_name'           => 'required|string|max:120',
            'match_country_code'  => 'nullable|string|size:2',
            'match_customer_type' => 'nullable|in:b2c,b2b',
            'tax_rate_pct'        => 'required|numeric|min:0|max:100',
            'tax_code'            => 'required|in:standard,reduced,exempt,reverse_charge',
            'invoice_note'        => 'nullable|string|max:500',
            'priority'            => 'required|integer|min:1|max:100',
            'is_active'           => 'nullable|boolean',
        ]);

        TaxRule::create([
            'company_id'          => $cid,
            'rule_name'           => $data['rule_name'],
            'match_country_code'  => $data['match_country_code'] ? strtoupper($data['match_country_code']) : null,
            'match_customer_type' => $data['match_customer_type'] ?: null,
            'tax_rate_pct'        => round((float) $data['tax_rate_pct'], 2),
            'tax_code'            => $data['tax_code'],
            'invoice_note'        => $data['invoice_note'] ?: null,
            'priority'            => (int) $data['priority'],
            'is_active'           => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('status', 'KDV kuralı eklendi.');
    }

    public function toggleTaxRule(Request $request, int $rule): RedirectResponse
    {
        $cid = $this->companyId();
        $r = TaxRule::query()
            ->withoutGlobalScopes()
            ->where('id', $rule)
            ->where('company_id', $cid)
            ->firstOrFail();
        $r->update(['is_active' => !$r->is_active]);
        return back()->with('status', 'KDV kuralı durumu değişti.');
    }

    public function destroyTaxRule(Request $request, int $rule): RedirectResponse
    {
        $cid = $this->companyId();
        TaxRule::query()
            ->withoutGlobalScopes()
            ->where('id', $rule)
            ->where('company_id', $cid)
            ->delete();
        return back()->with('status', 'KDV kuralı silindi.');
    }

    public function storeCommissionRule(Request $request): RedirectResponse
    {
        $cid = $this->companyId();
        $data = $request->validate([
            'rule_name'               => 'required|string|max:120',
            'applies_to_tier'         => 'nullable|string|max:32',
            'applies_to_service_type' => 'nullable|string|max:32',
            'commission_pct'          => 'required|numeric|min:0|max:100',
            'priority'                => 'required|integer|min:1|max:100',
            'is_active'               => 'nullable|boolean',
        ]);

        CommissionRule::create([
            'company_id'              => $cid,
            'rule_name'               => $data['rule_name'],
            'applies_to_tier'         => $data['applies_to_tier'] ?: null,
            'applies_to_service_type' => $data['applies_to_service_type'] ?: null,
            'commission_pct'          => round((float) $data['commission_pct'], 2),
            'priority'                => (int) $data['priority'],
            'is_active'               => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('status', 'Komisyon kuralı eklendi.');
    }

    public function destroyCommissionRule(Request $request, int $rule): RedirectResponse
    {
        $cid = $this->companyId();
        CommissionRule::query()
            ->withoutGlobalScopes()
            ->where('id', $rule)
            ->where('company_id', $cid)
            ->delete();
        return back()->with('status', 'Komisyon kuralı silindi.');
    }

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    private function ensurePricing(int $cid): CompanyBookingPricing
    {
        return CompanyBookingPricing::firstOrCreate(
            ['company_id' => $cid],
            [
                'is_free'                   => true,
                'currency'                  => 'EUR',
                'cancellation_window_hours' => 24,
                'pricing_rules'             => CompanyBookingPricing::defaultRules(),
            ]
        );
    }

    private function ensurePaymentSettings(int $cid): CompanyPaymentSetting
    {
        return CompanyPaymentSetting::firstOrCreate(
            ['company_id' => $cid],
            [
                'is_payment_enabled'     => false,
                'payout_day_of_month'    => 5,
                'payout_minimum_cents'   => 10000,
                'allow_on_demand_payout' => true,
                'default_commission_pct' => 20.00,
                'refund_window_hours'    => 24,
                'stripe_mode'            => 'test',
            ]
        );
    }
}

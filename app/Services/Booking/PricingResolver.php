<?php

namespace App\Services\Booking;

use App\Models\CommissionRule;
use App\Models\CompanyBookingPricing;
use App\Models\CompanyPaymentSetting;
use App\Models\TaxRule;
use App\Models\User;

/**
 * Booking ödeme hesaplamasının merkezi:
 *   - Net fiyatı bul (CompanyBookingPricing)
 *   - KDV kuralını uygula (TaxRule, müşteri ülkesi + tipi)
 *   - Komisyon kuralı uygula (CommissionRule, senior tier + service type)
 *
 * Döndürür: her şey cent cinsinden integer + kullanılan oranlar.
 *
 * ÖNEMLİ: is_payment_enabled=false veya is_free=true ise tüm değerler 0 olur —
 * ödeme akışı devreye girmez.
 */
class PricingResolver
{
    /**
     * @return array{
     *   is_free: bool,
     *   payment_enabled: bool,
     *   currency: string,
     *   amount_net_cents: int,
     *   tax_rate_pct: float,
     *   tax_code: string,
     *   tax_amount_cents: int,
     *   amount_gross_cents: int,
     *   commission_pct: float,
     *   commission_cents: int,
     *   senior_payout_cents: int,
     * }
     */
    public function resolve(
        int $companyId,
        int $durationMinutes,
        ?string $customerCountryCode = null,
        string $customerType = 'b2c',
        ?int $seniorUserId = null,
        ?string $serviceType = null,
        bool $isContractedUser = false
    ): array {
        $paymentSettings = $this->loadPaymentSettings($companyId);
        $pricing         = $this->loadPricing($companyId);

        // Sözleşmeli user → ücretsiz, payment disable
        if ($isContractedUser) {
            return $this->freeResult($pricing?->currency ?? 'EUR');
        }

        // Ödeme modülü kapalıysa → 0
        if (!$paymentSettings || !$paymentSettings->is_payment_enabled) {
            return $this->freeResult($pricing?->currency ?? 'EUR');
        }

        // is_free → 0
        if (!$pricing || $pricing->is_free) {
            return $this->freeResult($pricing?->currency ?? 'EUR');
        }

        // Net fiyat
        $netCents = $pricing->priceNetCentsFor($durationMinutes);
        if ($netCents === null || $netCents <= 0) {
            // O süre etkin değil veya fiyatı yok → ücretsiz fallback
            return $this->freeResult($pricing->currency);
        }

        // KDV
        $taxRule       = $this->resolveTaxRule($companyId, $customerCountryCode, $customerType);
        $taxPct        = (float) ($taxRule?->tax_rate_pct ?? 0);
        $taxCode       = (string) ($taxRule?->tax_code ?? 'exempt');
        $taxCents      = (int) round($netCents * $taxPct / 100);
        $grossCents    = $netCents + $taxCents;

        // Komisyon
        $commissionRule = $this->resolveCommissionRule($companyId, $seniorUserId, $serviceType);
        $commissionPct  = $commissionRule
            ? (float) $commissionRule->commission_pct
            : (float) $paymentSettings->default_commission_pct;
        $commissionCents = (int) round($netCents * $commissionPct / 100);
        $seniorPayout    = $netCents - $commissionCents;

        return [
            'is_free'            => false,
            'payment_enabled'    => true,
            'currency'           => (string) $pricing->currency,
            'amount_net_cents'   => $netCents,
            'tax_rate_pct'       => $taxPct,
            'tax_code'           => $taxCode,
            'tax_amount_cents'   => $taxCents,
            'amount_gross_cents' => $grossCents,
            'commission_pct'     => $commissionPct,
            'commission_cents'   => $commissionCents,
            'senior_payout_cents'=> $seniorPayout,
        ];
    }

    private function freeResult(string $currency): array
    {
        return [
            'is_free'            => true,
            'payment_enabled'    => false,
            'currency'           => $currency ?: 'EUR',
            'amount_net_cents'   => 0,
            'tax_rate_pct'       => 0.0,
            'tax_code'           => 'exempt',
            'tax_amount_cents'   => 0,
            'amount_gross_cents' => 0,
            'commission_pct'     => 0.0,
            'commission_cents'   => 0,
            'senior_payout_cents'=> 0,
        ];
    }

    private function loadPricing(int $companyId): ?CompanyBookingPricing
    {
        return CompanyBookingPricing::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->first();
    }

    private function loadPaymentSettings(int $companyId): ?CompanyPaymentSetting
    {
        return CompanyPaymentSetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->first();
    }

    private function resolveTaxRule(int $companyId, ?string $country, string $customerType): ?TaxRule
    {
        $country = $country ? strtoupper(trim($country)) : null;
        $rules = TaxRule::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($rule->match_country_code && $country && strtoupper($rule->match_country_code) !== $country) {
                continue;
            }
            if ($rule->match_country_code && !$country) {
                continue;
            }
            if ($rule->match_customer_type && $rule->match_customer_type !== $customerType) {
                continue;
            }
            return $rule;
        }
        return null;
    }

    private function resolveCommissionRule(int $companyId, ?int $seniorUserId, ?string $serviceType): ?CommissionRule
    {
        $seniorTier = null;
        if ($seniorUserId) {
            $seniorTier = User::query()
                ->withoutGlobalScopes()
                ->where('id', $seniorUserId)
                ->value('senior_type'); // Mevcut User modelinde senior_type field'ı var
        }

        $rules = CommissionRule::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($rule->applies_to_tier && $rule->applies_to_tier !== $seniorTier) {
                continue;
            }
            if ($rule->applies_to_service_type && $rule->applies_to_service_type !== $serviceType) {
                continue;
            }
            return $rule;
        }
        return null;
    }
}

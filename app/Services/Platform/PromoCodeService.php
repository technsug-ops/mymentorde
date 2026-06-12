<?php

namespace App\Services\Platform;

use App\Models\Company;
use App\Models\PromoCode;
use App\Models\PromoCodeRedemption;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Platform Owner — Promo Code Service.
 *
 * Sorumluluk: kod olusturma, redeem (kullanma), discount hesaplama, stats.
 *
 * Discount calculation:
 *   - percentage : amount * (value/100)
 *   - fixed      : min(value, amount)       (negatife dusmesin)
 *   - free_months: amount * 1.0             (tum tutar)  (caller duration_months kontrol etmeli)
 *
 * redeem() idempotent:
 *   - kod yoksa null
 *   - isValid() degilse null
 *   - company zaten kullandiysa null
 *   - basariliysa PromoCodeRedemption doner + current_uses arttirir
 */
class PromoCodeService
{
    // ────────────────────────────────────────────────────────────────────────
    // CREATE
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Yeni promo kodu olustur.
     * Validation: code unique, type whitelist, value > 0, duration_months > 0
     * (free_months tipinde zorunlu), tarih sirasi.
     *
     * @throws ValidationException
     */
    public function createCode(array $data): PromoCode
    {
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $type = (string) ($data['type'] ?? '');
        $value = (float) ($data['value'] ?? 0);
        $durationMonths = isset($data['duration_months']) && $data['duration_months'] !== ''
            ? (int) $data['duration_months']
            : null;
        $appliesTier = isset($data['applies_to_tier']) && $data['applies_to_tier'] !== ''
            ? (string) $data['applies_to_tier']
            : null;
        $maxUses = isset($data['max_uses']) && $data['max_uses'] !== ''
            ? (int) $data['max_uses']
            : null;
        $validFrom = (string) ($data['valid_from'] ?? CarbonImmutable::now()->toDateString());
        $validUntil = (string) ($data['valid_until'] ?? CarbonImmutable::now()->addMonths(3)->toDateString());
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $createdBy = isset($data['created_by_user_id']) ? (int) $data['created_by_user_id'] : null;

        $errors = [];

        if ($code === '' || !preg_match('/^[A-Z0-9_\-]{3,50}$/i', $code)) {
            $errors['code'] = ['Kod 3-50 karakter, sadece harf/rakam/_/- içerebilir.'];
        }
        if (PromoCode::query()->where('code', $code)->exists()) {
            $errors['code'] = ['Bu kod zaten mevcut.'];
        }
        if (!in_array($type, PromoCode::TYPES, true)) {
            $errors['type'] = ['Geçersiz indirim tipi.'];
        }
        if ($value <= 0) {
            $errors['value'] = ['Değer 0\'dan büyük olmalı.'];
        }
        if ($type === PromoCode::TYPE_PERCENTAGE && $value > 100) {
            $errors['value'] = ['Yüzde indirim 100\'ü geçemez.'];
        }
        if ($type === PromoCode::TYPE_FREE_MONTHS && ($durationMonths === null || $durationMonths < 1)) {
            $errors['duration_months'] = ['İlk N ay ücretsiz için süre (ay) gerekli ve > 0 olmalı.'];
        }
        if ($appliesTier !== null && !in_array($appliesTier, [Company::TIER_BASIC, Company::TIER_GOLD, Company::TIER_PREMIUM], true)) {
            $errors['applies_to_tier'] = ['Geçersiz tier.'];
        }
        if ($maxUses !== null && $maxUses < 1) {
            $errors['max_uses'] = ['Max kullanım en az 1 olmalı (veya boş bırak).'];
        }

        try {
            $from = CarbonImmutable::createFromFormat('Y-m-d', $validFrom);
            $until = CarbonImmutable::createFromFormat('Y-m-d', $validUntil);
            if ($from && $until && $until->lt($from)) {
                $errors['valid_until'] = ['Bitiş tarihi başlangıçtan önce olamaz.'];
            }
        } catch (Throwable) {
            $errors['valid_from'] = ['Geçerli bir tarih formatı girin (YYYY-MM-DD).'];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return PromoCode::create([
            'code'               => $code,
            'type'               => $type,
            'value'              => $value,
            'duration_months'    => $durationMonths,
            'applies_to_tier'    => $appliesTier,
            'max_uses'           => $maxUses,
            'current_uses'       => 0,
            'valid_from'         => $validFrom,
            'valid_until'        => $validUntil,
            'is_active'          => (bool) ($data['is_active'] ?? true),
            'description'        => $description,
            'created_by_user_id' => $createdBy,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // REDEEM
    // ────────────────────────────────────────────────────────────────────────

    /**
     * String kodla redeem et. Geçersiz/kullanılmış/expired ise null.
     *
     * NOT: Bu cagri'da PROAKTIF DISCOUNT HESAPLANMAZ — discount_applied_eur=0
     * kayitla acilir. Faturalama tarafi (PlatformBillingController::generate)
     * gercek invoice uretirken calculateDiscount + redemption.update yapar.
     */
    public function redeem(string $code, Company $company): ?PromoCodeRedemption
    {
        $code = strtoupper(trim($code));
        if ($code === '') return null;

        return DB::transaction(function () use ($code, $company): ?PromoCodeRedemption {
            $promo = PromoCode::query()
                ->where('code', $code)
                ->lockForUpdate()
                ->first();

            if (!$promo) return null;
            if (!$promo->canBeUsedBy($company)) return null;

            try {
                return $promo->apply($company, 0.0, []);
            } catch (Throwable $e) {
                Log::warning('promo_code.redeem.failed', [
                    'code'       => $code,
                    'company_id' => $company->id,
                    'error'      => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    // ────────────────────────────────────────────────────────────────────────
    // CALCULATE
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Indirim tutarini EUR olarak hesapla.
     * Sonuc 0'dan kucuk olmaz, $amountEur'dan buyuk olmaz.
     */
    public function calculateDiscount(PromoCode $code, float $amountEur): float
    {
        if ($amountEur <= 0) return 0.0;

        $discount = match ($code->type) {
            PromoCode::TYPE_PERCENTAGE  => $amountEur * ((float) $code->value / 100),
            PromoCode::TYPE_FIXED       => (float) $code->value,
            PromoCode::TYPE_FREE_MONTHS => $amountEur, // %100 indirim, caller duration_months bilir
            default                     => 0.0,
        };

        $discount = max(0.0, min($discount, $amountEur));
        return round($discount, 2);
    }

    // ────────────────────────────────────────────────────────────────────────
    // QUERY HELPERS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Bir company icin su anda aktif (suresi gecmemis, max'a takilmayan,
     * tier filtresine uyan) ve company tarafindan henuz kullanilmamis kodlar.
     */
    public function getActiveCodesFor(Company $company): Collection
    {
        $now = CarbonImmutable::now()->toDateString();

        $usedCodeIds = PromoCodeRedemption::query()
            ->where('company_id', $company->id)
            ->pluck('promo_code_id')
            ->all();

        return PromoCode::query()
            ->where('is_active', true)
            ->whereDate('valid_from', '<=', $now)
            ->whereDate('valid_until', '>=', $now)
            ->where(function ($q) {
                $q->whereNull('max_uses')
                  ->orWhereColumn('current_uses', '<', 'max_uses');
            })
            ->where(function ($q) use ($company) {
                $q->whereNull('applies_to_tier')
                  ->orWhere('applies_to_tier', '=', $company->subscription_tier);
            })
            ->when(!empty($usedCodeIds), fn($q) => $q->whereNotIn('id', $usedCodeIds))
            ->orderByDesc('id')
            ->get();
    }

    // ────────────────────────────────────────────────────────────────────────
    // STATS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Bir kod icin istatistik:
     *   - redemption_count : redemption sayisi
     *   - total_discount   : toplam verilen indirim EUR
     *   - revenue_lost     : su an icin = total_discount (gelecekte recurring vs one-off ayrilabilir)
     *   - companies_count  : kac unique company kullanmis
     *   - last_redeemed_at : son uygulama
     */
    public function getStats(PromoCode $code): array
    {
        $row = PromoCodeRedemption::query()
            ->where('promo_code_id', $code->id)
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(discount_applied_eur),0) AS total_disc, COUNT(DISTINCT company_id) AS companies_cnt, MAX(applied_at) AS last_at')
            ->first();

        $count = (int) ($row->cnt ?? 0);
        $total = (float) ($row->total_disc ?? 0);
        $companies = (int) ($row->companies_cnt ?? 0);
        $lastAt = $row && $row->last_at ? CarbonImmutable::parse($row->last_at) : null;

        return [
            'redemption_count' => $count,
            'total_discount'   => round($total, 2),
            'revenue_lost'     => round($total, 2),
            'companies_count'  => $companies,
            'last_redeemed_at' => $lastAt,
            'avg_discount'     => $count > 0 ? round($total / $count, 2) : 0.0,
        ];
    }
}

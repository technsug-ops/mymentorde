<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\DiscountCodeRedemption;
use App\Models\GuestApplication;
use App\Models\GuestPaymentRequest;
use Illuminate\Support\Facades\DB;

/**
 * İndirim kodu doğrulama + uygulama.
 *
 * MVP'de sadece guest payment akışı destekleniyor; servis sınıfı kendi içinde
 * polymorphic redemption yazdığı için ileride StudentPayment / BookingPayment
 * için ek metod eklemek yeterli olur (şema aynı).
 */
class DiscountCodeService
{
    /**
     * Aday için kod doğrula. Hata durumunda 'ok' = false + 'error' döner;
     * geçerli ise indirimli tutar ve ilgili DiscountCode dahil edilir.
     *
     * @return array{ok:bool, code:?DiscountCode, original:float, discount:float, final:float, error:?string}
     */
    public function validateForGuest(string $codeStr, GuestApplication $guest, float $amount): array
    {
        $codeStr = trim(strtoupper($codeStr));
        $base = ['ok' => false, 'code' => null, 'original' => $amount, 'discount' => 0.0, 'final' => $amount, 'error' => null];

        if ($codeStr === '') {
            return ['error' => 'Kupon kodu boş olamaz.'] + $base;
        }

        $code = DiscountCode::query()
            ->where('company_id', $guest->company_id)
            ->whereRaw('UPPER(code) = ?', [$codeStr])
            ->first();

        if (! $code) {
            return ['error' => 'Geçersiz kupon kodu.'] + $base;
        }
        if (! $code->isCurrentlyActive()) {
            $reason = $this->inactivityReason($code);
            return ['error' => $reason] + $base;
        }

        // Future hook: package-spesifik kontrolü (UI'da MVP yok ama altyapı çalışır)
        if (is_array($code->applies_to_package_codes) && count($code->applies_to_package_codes) > 0) {
            $selectedPkg = (string) ($guest->selected_package_code ?? '');
            if ($selectedPkg === '' || ! in_array($selectedPkg, $code->applies_to_package_codes, true)) {
                return ['error' => 'Bu kupon seçili paketinizde geçerli değil.'] + $base;
            }
        }

        // Future hook: minimum tutar
        if ($code->min_purchase_amount_eur !== null && (float) $code->min_purchase_amount_eur > $amount) {
            $min = number_format((float) $code->min_purchase_amount_eur, 0, ',', '.');
            return ['error' => "Bu kupon için minimum tutar: {$min} EUR."] + $base;
        }

        // Kişi başına kullanım limiti
        if ($code->max_per_user > 0) {
            $userUsage = DiscountCodeRedemption::query()
                ->where('discount_code_id', $code->id)
                ->where('guest_application_id', $guest->id)
                ->count();
            if ($userUsage >= $code->max_per_user) {
                return ['error' => 'Bu kuponu daha önce kullandınız.'] + $base;
            }
        }

        $discount = $code->computeDiscount($amount);
        $final = max(0.0, round($amount - $discount, 2));

        return [
            'ok'       => true,
            'code'     => $code,
            'original' => $amount,
            'discount' => $discount,
            'final'    => $final,
            'error'    => null,
        ];
    }

    /**
     * Doğrulanmış bir kodu GuestPaymentRequest'e uygula:
     *  - redemption kaydı oluştur
     *  - DiscountCode.redemption_count atomik artır
     *
     * `validateForGuest` BAŞARILI sonrası çağrılmalı.
     */
    public function applyToPaymentRequest(
        DiscountCode $code,
        GuestPaymentRequest $req,
        GuestApplication $guest,
        float $original,
        float $discount,
        float $final,
    ): DiscountCodeRedemption {
        return DB::transaction(function () use ($code, $req, $guest, $original, $discount, $final) {
            $redemption = DiscountCodeRedemption::create([
                'company_id'           => (int) $guest->company_id,
                'discount_code_id'     => $code->id,
                'redeemable_type'      => 'guest_payment_request',
                'redeemable_id'        => $req->id,
                'guest_application_id' => $guest->id,
                'user_id'              => $guest->guest_user_id,
                'original_amount_eur'  => $original,
                'discount_amount_eur'  => $discount,
                'final_amount_eur'     => $final,
                'redeemed_at'          => now(),
                'meta'                 => [
                    'package_code' => $guest->selected_package_code,
                    'code_used'    => $code->code,
                ],
            ]);

            // Atomik sayaç
            DiscountCode::where('id', $code->id)->increment('redemption_count');

            return $redemption;
        });
    }

    private function inactivityReason(DiscountCode $code): string
    {
        if (! $code->is_active) return 'Bu kupon devre dışı.';
        $now = now();
        if ($code->valid_from && $now->lt($code->valid_from)) {
            return 'Bu kupon henüz aktif değil (' . $code->valid_from->format('d.m.Y') . ' itibarıyla).';
        }
        if ($code->valid_until && $now->gt($code->valid_until)) return 'Bu kuponun süresi doldu.';
        if ($code->max_redemptions !== null && $code->redemption_count >= $code->max_redemptions) {
            return 'Bu kuponun kullanım kotası dolmuş.';
        }
        return 'Bu kupon şu an kullanılamaz.';
    }
}

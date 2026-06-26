<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Dealer;
use App\Models\DealerApplication;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Onaylanan dealer başvurusundan bayi hesabı + login kullanıcısı üretir
 * + şifre belirleme daveti gönderir.
 *
 * Tek kaynak: hem Manager\DealerApplicationController (onay anında) hem de
 * backfill (geçmişte onaylanıp provision edilmemiş başvurular) bunu kullanır.
 *
 * Idempotent: approved_dealer_id doluysa tekrar çalışmaz.
 */
class DealerProvisioningService
{
    /**
     * @return array{ok:bool, skipped:bool, message:string, dealer_code:?string}
     */
    public function fromApplication(DealerApplication $app): array
    {
        if (!empty($app->approved_dealer_id)) {
            return ['ok' => true, 'skipped' => true, 'message' => 'zaten provision edilmiş', 'dealer_code' => null];
        }

        $email = strtolower(trim((string) $app->email));
        if ($email === '') {
            return ['ok' => false, 'skipped' => false, 'message' => 'e-posta yok, bayi hesabı oluşturulamadı', 'dealer_code' => null];
        }

        // Roller (çoklu) → primary dealer_type_code. roles boşsa plan'dan türet.
        $roles    = $app->rolesList();
        $typeCode = Dealer::primaryTypeForRoles($roles);

        $companyId = (int) ($app->company_id ?: (Company::query()->where('is_active', true)->orderBy('id')->value('id') ?? 1));
        $name = trim(($app->first_name ?? '') . ' ' . ($app->last_name ?? '')) ?: ($app->company_name ?: 'Bayi');

        $existingUser = User::query()->where('email', $email)->first();

        $dealer = Dealer::query()->create([
            'company_id'        => $companyId,
            'code'              => $this->generateDealerCodeForType($typeCode),
            'internal_sequence' => ((int) Dealer::query()->max('internal_sequence')) + 1,
            'name'              => $name,
            'email'             => $email,
            'phone'             => $app->phone ?: null,
            'dealer_type_code'  => $typeCode,
            'roles'             => $roles,
            'is_active'         => true,
            'is_archived'       => false,
        ]);

        $userId = null;
        if ($existingUser) {
            $existingUser->forceFill(['dealer_code' => $dealer->code])->save();
            $userId = $existingUser->id;
        } else {
            $user = User::query()->create([
                'company_id'  => $companyId,
                'name'        => $name,
                'email'       => $email,
                'role'        => User::ROLE_DEALER,
                'dealer_code' => $dealer->code,
                'is_active'   => true,
                'password'    => Hash::make(Str::random(40)),
            ]);
            $userId = $user->id;
            // Yeni hesap → "hoş geldiniz, şifrenizi belirleyin" (reset değil).
            // Aynı token mekanizması, doğru metin. (DealerWelcomeNotification)
            try {
                $token = Password::broker()->createToken($user);
                $user->notify(new \App\Notifications\DealerWelcomeNotification($token));
            } catch (\Throwable) {
            }
        }

        $app->forceFill([
            'approved_dealer_id' => $dealer->id,
            'approved_user_id'   => $userId,
        ])->save();

        return ['ok' => true, 'skipped' => false, 'message' => 'bayi hesabı oluşturuldu, davet gönderildi', 'dealer_code' => $dealer->code];
    }

    /** dealer_type bazlı benzersiz bayi kodu. */
    public function generateDealerCodeForType(string $typeCode): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $typeCode) ?: 'DLR', 0, 3));
        $base   = "{$prefix}-" . now()->format('y') . '-' . now()->format('m');
        $seq    = ((int) Dealer::query()->max('internal_sequence')) + 1;
        do {
            $token     = strtoupper(substr(hash('crc32b', "{$base}-{$seq}"), 0, 4));
            $candidate = "{$base}-{$token}";
            if (!Dealer::query()->where('code', $candidate)->exists()) {
                return $candidate;
            }
            $seq++;
        } while (true);
    }
}

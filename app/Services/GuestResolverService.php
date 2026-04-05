<?php

namespace App\Services;

use App\Models\GuestApplication;
use Illuminate\Http\Request;

/**
 * Kimlik dogrulama yapilmis kullanici icin GuestApplication kaydini tek noktadan cozer.
 * PortalController, WorkflowController, EnsureGuestOwnsDocument ve
 * EnsureGuestOwnsTicket tarafindan paylasilan mantigi merkezlestirir.
 */
class GuestResolverService
{
    public function resolve(Request $request): ?GuestApplication
    {
        $user = $request->user();
        return $this->resolveByEmail((string) ($user->email ?? ''));
    }

    public function resolveByEmail(string $email): ?GuestApplication
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $companyId = $this->currentCompanyId();

        if ($companyId > 0) {
            $found = GuestApplication::query()
                ->where('company_id', $companyId)
                ->where('email', $email)
                ->latest('id')
                ->first();

            if ($found) {
                return $found;
            }

            // Fallback: company_id'si NULL veya 0 olan kayıtlar için (başvuru henüz atanmamış)
            $found = GuestApplication::withoutGlobalScope('company')
                ->where('email', $email)
                ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', 0))
                ->latest('id')
                ->first();

            // Bulunan kaydın company_id'sini güncelle (bir kez düzeltir)
            if ($found) {
                $found->company_id = $companyId;
                $found->save();
            }

            return $found;
        }

        return GuestApplication::query()
            ->where('email', $email)
            ->latest('id')
            ->first();
    }

    private function currentCompanyId(): int
    {
        if (!app()->bound('current_company_id')) {
            return 0;
        }
        return (int) app('current_company_id');
    }
}

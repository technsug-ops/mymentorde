<?php

namespace App\Http\Middleware;

use App\Models\Dealer;
use App\Models\DealerType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDealerTypePermission
{
    public function handle(Request $request, Closure $next, string $permissionKey = 'canViewStudentDetails'): Response
    {
        $user = $request->user();
        if (!$user || $user->role !== 'dealer') {
            abort(Response::HTTP_FORBIDDEN, 'Bu alana erisim izniniz yok.');
        }

        $dealerCode = strtoupper(trim((string) ($user->dealer_code ?? '')));
        if ($dealerCode === '') {
            abort(Response::HTTP_FORBIDDEN, 'Dealer hesabi icin dealer_code gerekli.');
        }

        $dealer = Dealer::query()
            ->where('code', $dealerCode)
            ->where('is_active', true)
            ->where('is_archived', false)
            ->first();

        if (!$dealer) {
            abort(Response::HTTP_FORBIDDEN, 'Dealer kaydi aktif degil veya bulunamadi.');
        }

        $dealerType = DealerType::query()
            ->where('code', (string) $dealer->dealer_type_code)
            ->where('is_active', true)
            ->first();

        if (!$dealerType) {
            abort(Response::HTTP_FORBIDDEN, 'Dealer tipi aktif degil veya bulunamadi.');
        }

        $permissions = (array) ($dealerType->permissions ?? []);
        $allowed = (bool) ($permissions[$permissionKey] ?? false);

        if (!$allowed) {
            abort(Response::HTTP_FORBIDDEN, 'Dealer tipi yetkiniz bu alani gormeye uygun degil.');
        }

        $request->attributes->set('dealer_record', $dealer);
        $request->attributes->set('dealer_type', $dealerType);
        $request->attributes->set('dealer_permissions', $permissions);

        return $next($request);
    }
}


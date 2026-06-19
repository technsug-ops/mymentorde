<?php

namespace App\Http\Middleware;

use App\Models\Dealer;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sadece BÖLGE bayisi (parent_dealer_id null) erişebilir.
 * Alt bayi (sub) kendi altına bayi açamaz — 2 seviye sabit.
 * 'dealer.role' middleware'inden SONRA çalıştırılmalı.
 */
class EnsureRegionalDealer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || (string) $user->role !== User::ROLE_DEALER) {
            abort(Response::HTTP_FORBIDDEN, 'Bu alana erisim izniniz yok.');
        }

        $code   = strtoupper(trim((string) ($user->dealer_code ?? '')));
        $dealer = $code !== '' ? Dealer::query()->where('code', $code)->first() : null;

        if (!$dealer || !$dealer->isRegional()) {
            abort(Response::HTTP_FORBIDDEN, 'Bu alan yalnizca bolge bayilerine aciktir.');
        }

        return $next($request);
    }
}

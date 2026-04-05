<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(Response::HTTP_FORBIDDEN, 'Bu alana erisim izniniz yok.');
        }

        // Sözleşme onaylanarak student'a terfi eden kullanıcıyı tebrik sayfasına yönlendir
        if ((string) $user->role === User::ROLE_STUDENT) {
            return redirect()->route('guest.promoted-to-student');
        }

        if ((string) $user->role !== User::ROLE_GUEST) {
            abort(Response::HTTP_FORBIDDEN, 'Bu alana erisim izniniz yok.');
        }

        return $next($request);
    }
}


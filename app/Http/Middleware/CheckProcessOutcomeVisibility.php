<?php

namespace App\Http\Middleware;

use App\Models\GuestApplication;
use App\Models\ProcessOutcome;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProcessOutcomeVisibility
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || $user->role !== 'student') {
            abort(Response::HTTP_FORBIDDEN, 'Bu alana erisim izniniz yok.');
        }

        /** @var ProcessOutcome|null $outcome */
        $outcome = $request->route('processOutcome');
        if (!$outcome instanceof ProcessOutcome) {
            abort(Response::HTTP_FORBIDDEN, 'Outcome erisimi gecersiz.');
        }

        if (!$outcome->is_visible_to_student) {
            abort(Response::HTTP_FORBIDDEN, 'Bu surec sonucu ogrenciye acik degil.');
        }

        $studentId = trim((string) ($user->student_id ?? ''));
        if ($studentId === '') {
            $studentId = (string) GuestApplication::query()
                ->where('email', strtolower((string) $user->email))
                ->where('converted_to_student', true)
                ->whereNotNull('converted_student_id')
                ->latest('id')
                ->value('converted_student_id');
        }

        if ($studentId === '' || $outcome->student_id !== $studentId) {
            abort(Response::HTTP_FORBIDDEN, 'Bu surec sonucuna erisim izniniz yok.');
        }

        return $next($request);
    }
}


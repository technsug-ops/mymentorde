<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\StudentGuestResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || (string) $user->role !== User::ROLE_STUDENT) {
            abort(Response::HTTP_FORBIDDEN, 'Bu alana erisim izniniz yok.');
        }

        $studentId = trim((string) ($user->student_id ?? ''));
        $email = strtolower(trim((string) ($user->email ?? '')));
        if ($studentId === '' && $email === '') {
            abort(Response::HTTP_FORBIDDEN, 'Student kaydi bulunamadi.');
        }

        $linked = app(StudentGuestResolver::class)->resolveForUser($user);

        if (!$linked) {
            abort(Response::HTTP_FORBIDDEN, 'Student hesabi guest donusum kaydi ile eslesmiyor.');
        }

        return $next($request);
    }
}

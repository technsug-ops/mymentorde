<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTaskAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = (string) optional($request->user())->role;
        if (!in_array($role, User::TASK_ACCESS_ROLES, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Task board erisimi yok.');
        }

        return $next($request);
    }
}


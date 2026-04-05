<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EnsureManagerKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('app.manager_api_key');
        $provided = (string) $request->header('X-Manager-Key');

        if ($expected === '' || !hash_equals($expected, $provided)) {
            return new JsonResponse([
                'message' => 'Manager key gecersiz veya eksik.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

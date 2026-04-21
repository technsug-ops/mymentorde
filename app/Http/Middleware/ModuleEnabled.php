<?php

namespace App\Http\Middleware;

use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: bir modül current company için kapalıysa 404.
 *
 * Kullanım:
 *   Route::middleware('module:booking')->group(...);
 */
class ModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        ModuleAccess::assertEnabled($module);
        return $next($request);
    }
}

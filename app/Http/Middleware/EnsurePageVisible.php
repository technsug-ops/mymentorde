<?php

namespace App\Http\Middleware;

use App\Support\PageAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page visibility middleware — Premium 'page_visibility' modülü için route guard.
 *
 * Kullanım:
 *   Route::get(...)->middleware('page.visible:visa_guide');
 *
 * - Sayfa key'i `PageAccess::PAGES` içinde tanımlı olmalı
 * - Mevcut kullanıcının rolüne göre kontrol edilir; rol yoksa 404
 * - Kapalıysa abort(404) — kullanıcıya sayfanın varlığını bile sızdırmaz
 * - Sidebar'da @pageVisible directive ile uyumlu (UI gizleme + route guard
 *   beraber çalışır)
 */
class EnsurePageVisible
{
    public function handle(Request $request, Closure $next, string $pageKey): Response
    {
        $role = (string) optional($request->user())->role ?? '';
        if ($role === '') {
            abort(404);
        }

        if (! PageAccess::visible($pageKey, $role)) {
            abort(404, 'Bu sayfa planınızda mevcut değil.');
        }

        return $next($request);
    }
}

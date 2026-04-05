<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', 'tr');

        // Giriş yapmış kullanıcının tercihini önceliklendir
        if (auth()->check()) {
            $pref = \App\Models\UserPortalPreference::where('user_id', auth()->id())
                ->where('portal_key', 'guest')->first();
            if ($pref) {
                $locale = ($pref->preferences_json['locale'] ?? $locale);
            }
        }

        if (!in_array($locale, ['tr', 'de', 'en'])) {
            $locale = 'tr';
        }

        app()->setLocale($locale);
        return $next($request);
    }
}

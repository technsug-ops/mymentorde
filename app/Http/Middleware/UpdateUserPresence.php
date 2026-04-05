<?php

namespace App\Http\Middleware;

use App\Services\PresenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Her authenticated istekte kullanıcının presence'ını günceller.
 * Heartbeat 30 saniyede bir DB'ye yazılır; cache her sorguda sıfırlanır.
 */
class UpdateUserPresence
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Sadece authenticated, web kanalı, GET + POST (api/websocket hariç)
        if ($user && !$request->is('api/*') && !$request->is('im/*/poll')) {
            PresenceService::heartbeat($user);
        }

        return $next($request);
    }
}

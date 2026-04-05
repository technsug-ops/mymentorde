<?php

namespace App\Http\Middleware;

use App\Models\GuestTicket;
use App\Services\GuestResolverService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestOwnsTicket
{
    public function __construct(private readonly GuestResolverService $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ticket = $request->route('ticket');
        if (!$ticket instanceof GuestTicket) {
            abort(Response::HTTP_FORBIDDEN, 'Ticket erisimi dogrulanamadi.');
        }

        $guest = $this->resolver->resolve($request);
        if (!$guest || (int) $ticket->guest_application_id !== (int) $guest->id) {
            abort(Response::HTTP_FORBIDDEN, 'Bu ticket kaydi size ait degil.');
        }

        return $next($request);
    }
}

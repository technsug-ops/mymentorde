<?php

namespace App\Http\Middleware;

use App\Models\Document;
use App\Models\GuestApplication;
use App\Services\GuestResolverService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestOwnsDocument
{
    public function __construct(private readonly GuestResolverService $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $document = $request->route('document');
        if (!$document instanceof Document) {
            abort(Response::HTTP_FORBIDDEN, 'Dokuman erisimi dogrulanamadi.');
        }

        $guest = $this->resolver->resolve($request);
        if (!$guest) {
            abort(Response::HTTP_FORBIDDEN, 'Guest kaydi bulunamadi.');
        }

        $ownerId = $this->resolveDocumentOwnerId($guest);
        if ((string) ($document->student_id ?? '') !== $ownerId) {
            abort(Response::HTTP_FORBIDDEN, 'Bu dokuman kaydi size ait degil.');
        }

        return $next($request);
    }

    private function resolveDocumentOwnerId(GuestApplication $guest): string
    {
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId !== '') {
            return $studentId;
        }

        return 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
    }
}

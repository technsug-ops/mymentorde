<?php

namespace App\Http\Middleware;

use App\Models\Document;
use App\Services\StudentGuestResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentOwnsDocument
{
    public function __construct(private readonly StudentGuestResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $document = $request->route('document');
        if (!$document instanceof Document) {
            abort(Response::HTTP_FORBIDDEN, 'Dokuman erisimi dogrulanamadi.');
        }

        $user = $request->user();
        if (!$user) {
            abort(Response::HTTP_FORBIDDEN, 'Kullanici bulunamadi.');
        }

        $guest = $this->resolver->resolveForUser($user);
        if (!$guest) {
            abort(Response::HTTP_FORBIDDEN, 'Ogrenci kaydi bulunamadi.');
        }

        $ownerIds = $this->resolveOwnerIds($guest->converted_student_id, $guest->id);

        if (!$ownerIds->contains((string) ($document->student_id ?? ''))) {
            abort(Response::HTTP_FORBIDDEN, 'Bu dokuman size ait degil.');
        }

        return $next($request);
    }

    private function resolveOwnerIds(?string $convertedStudentId, int $guestId): \Illuminate\Support\Collection
    {
        $ids = collect();

        $studentId = trim((string) ($convertedStudentId ?? ''));
        if ($studentId !== '') {
            $ids->push($studentId);
        }

        $ids->push('GST-' . str_pad((string) $guestId, 8, '0', STR_PAD_LEFT));

        return $ids;
    }
}

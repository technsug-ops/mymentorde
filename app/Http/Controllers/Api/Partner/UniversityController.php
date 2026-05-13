<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Partner\ProgramSummaryResource;
use App\Http\Resources\Api\Partner\UniversityResource;
use App\Http\Resources\Api\Partner\UniversitySummaryResource;
use App\Models\Program;
use App\Models\University;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Partner API — Üniversite listesi + detay (programları ile).
 *
 * GET /api/v1/partner/universities
 *   Filters: q (name LIKE), state, city, top_uni (top10|20|40)
 *   Paging: page, per_page (max 100)
 *
 * GET /api/v1/partner/universities/{id}
 *   Üniversite detayı + ait olduğu programlar (paginated içinde nested).
 */
class UniversityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 30), 1), 100);

        $query = University::query()->where('is_active', true);

        $term = trim((string) $request->query('q', ''));
        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
            $query->where('name', 'like', $like);
        }

        // University tablosunda city/state boş — programs tablosundan filtre
        $state = (string) $request->query('state', '');
        if ($state !== '') {
            $cityToState = (array) config('germany_geo.city_to_state', []);
            $cities = array_keys(array_filter($cityToState, fn ($s) => $s === $state));
            $uniIds = Program::query()->active()->whereIn('location', $cities ?: ['__none__'])
                ->distinct('university_id')->pluck('university_id');
            $query->whereIn('id', $uniIds);
        }

        $city = trim((string) $request->query('city', ''));
        if ($city !== '') {
            $aliases = [$city];
            foreach ((array) config('germany_geo.city_alias_pairs', []) as $pair) {
                if (in_array($city, $pair, true)) { $aliases = $pair; break; }
            }
            $uniIds = Program::query()->active()->whereIn('location', $aliases)
                ->distinct('university_id')->pluck('university_id');
            $query->whereIn('id', $uniIds);
        }

        $topUni = (string) $request->query('top_uni', '');
        if (in_array($topUni, ['top10', 'top20', 'top40'], true)) {
            $key = match ($topUni) { 'top10' => 'top_10', 'top20' => 'top_20', 'top40' => 'top_40' };
            $names = (array) config("germany_geo.{$key}", []);
            $query->whereIn('name', $names);
        }

        $query->orderBy('name');
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => UniversitySummaryResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
            'links' => [
                'self'  => $paginator->url($paginator->currentPage()),
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'next'  => $paginator->nextPageUrl(),
                'prev'  => $paginator->previousPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, string $universityId): JsonResponse
    {
        $university = University::query()->where('is_active', true)->whereKey($universityId)->first();
        if (! $university) {
            return response()->json(['error' => 'not_found', 'message' => 'Universite bulunamadi.'], 404);
        }

        // Ait olduğu programları getir (paginated)
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $programs = Program::query()
            ->active()
            ->where('university_id', $universityId)
            ->orderByDesc('quality_score')
            ->orderBy('course_name')
            ->paginate($perPage);

        return response()->json([
            'data' => (new UniversityResource($university))->resolve(),
            'programs' => [
                'data' => ProgramSummaryResource::collection($programs->items())->resolve(),
                'meta' => [
                    'current_page' => $programs->currentPage(),
                    'per_page'     => $programs->perPage(),
                    'total'        => $programs->total(),
                    'last_page'    => $programs->lastPage(),
                ],
            ],
        ]);
    }
}

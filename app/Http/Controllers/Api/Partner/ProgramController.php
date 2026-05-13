<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Partner\ProgramResource;
use App\Http\Resources\Api\Partner\ProgramSummaryResource;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Partner API — Program listesi + detay.
 *
 * GET /api/v1/partner/programs
 *   Filters: q, university, state, city, degree (bachelor|master|phd),
 *            language (de|en|both), subject, top_uni (top10|top20|top40),
 *            fields[] (study_fields kategorisi, multi)
 *   Paging: page, per_page (max 100)
 *   Sort: sort (relevance|name|recent)
 *
 * GET /api/v1/partner/programs/{program}
 *   UUID, tam detay (internal field'lar hariç).
 */
class ProgramController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 30), 100);
        $perPage = max($perPage, 1);

        $query = $this->buildQuery($request);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => ProgramSummaryResource::collection($paginator->items())->resolve(),
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

    public function show(Request $request, string $programId): JsonResponse
    {
        $program = Program::query()->active()->whereKey($programId)->first();
        if (! $program) {
            return response()->json(['error' => 'not_found', 'message' => 'Program bulunamadi.'], 404);
        }

        return response()->json([
            'data' => (new ProgramResource($program))->resolve(),
        ]);
    }

    private function buildQuery(Request $request): Builder
    {
        $q = Program::query()->active();

        // q (free-text)
        $term = trim((string) $request->query('q', ''));
        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
            $q->where(function (Builder $sub) use ($like) {
                $sub->where('course_name', 'like', $like)
                    ->orWhere('university_name_cached', 'like', $like)
                    ->orWhere('description_tr', 'like', $like)
                    ->orWhere('study_fields', 'like', $like)
                    ->orWhere('subjects', 'like', $like);
            });
        }

        // university (exact)
        $university = trim((string) $request->query('university', ''));
        if ($university !== '') {
            $q->where('university_name_cached', $university);
        }

        // state (Bundesland — config'deki city mapping ile)
        $state = (string) $request->query('state', '');
        if ($state !== '') {
            $cityToState = (array) config('germany_geo.city_to_state', []);
            $citiesInState = array_keys(array_filter($cityToState, fn ($s) => $s === $state));
            $q->whereIn('location', $citiesInState ?: ['__none__']);
        }

        // city (exact, alias-aware)
        $city = trim((string) $request->query('city', ''));
        if ($city !== '') {
            $aliases = [$city];
            foreach ((array) config('germany_geo.city_alias_pairs', []) as $pair) {
                if (in_array($city, $pair, true)) { $aliases = $pair; break; }
            }
            $q->whereIn('location', $aliases);
        }

        // degree
        $degree = (string) $request->query('degree', '');
        if (in_array($degree, ['bachelor', 'master', 'phd'], true)) {
            $q->where('degree_type', $degree);
        }

        // language
        $language = (string) $request->query('language', '');
        if (in_array($language, ['de', 'en', 'both'], true)) {
            $q->where('language', $language);
        }

        // subject (LIKE)
        $subject = trim((string) $request->query('subject', ''));
        if ($subject !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $subject) . '%';
            $q->where(function (Builder $w) use ($like) {
                $w->where('study_fields', 'like', $like)
                  ->orWhere('subjects', 'like', $like)
                  ->orWhere('course_name', 'like', $like)
                  ->orWhere('description_tr', 'like', $like);
            });
        }

        // top_uni
        $topUni = (string) $request->query('top_uni', '');
        if (in_array($topUni, ['top10', 'top20', 'top40'], true)) {
            $key = match ($topUni) { 'top10' => 'top_10', 'top20' => 'top_20', 'top40' => 'top_40' };
            $topList = (array) config("germany_geo.{$key}", []);
            $q->whereIn('university_name_cached', $topList);
        }

        // fields[] (multi-select OR)
        $fields = $request->query('fields', []);
        if (is_array($fields) && $fields !== []) {
            $q->where(function (Builder $sub) use ($fields) {
                foreach ($fields as $field) {
                    if (! is_string($field) || $field === '') continue;
                    $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $field);
                    $sub->orWhere('study_fields', 'like', '%"' . $escaped . '"%');
                }
            });
        }

        // sort
        $sort = (string) $request->query('sort', 'relevance');
        return match ($sort) {
            'name'    => $q->orderBy('course_name'),
            'recent'  => $q->orderByDesc('updated_at'),
            default   => $q->orderByDesc('quality_score')->orderByDesc('is_manually_curated')->orderBy('course_name'),
        };
    }
}

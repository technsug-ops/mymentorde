<?php

namespace App\Http\Controllers\UniMatch;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Internal kullanıcılar (manager/senior/mentor/admin_staff/operations_*) için
 * uni-assist tarzı program arama sayfası. Guest UniMatch wizard'ı bypass eder.
 *
 * URL: GET /program-search
 * Auth: middleware('auth') + custom role check
 *
 * Filters:
 *   - q (free-text: course_name + university_name_cached + study_fields LIKE)
 *   - degree (bachelor/master/phd)
 *   - language (de/en/both)
 *   - subject (typeahead: study_fields[*] / subjects[*] JSON LIKE)
 *   - city (location LIKE)
 *   - tuition_max (0 = free; 500 / 1000 / unlimited)
 *   - sort (relevance / name / quality / recent)
 */
class ProgramSearchController extends Controller
{
    /** Hangi roller bu sayfaya erişebilir. */
    private const ALLOWED_ROLES = [
        'manager', 'senior', 'mentor',
        'admin_staff', 'operations_admin', 'operations_staff',
        'system_admin', 'system_staff',
        'marketing_admin',
    ];

    public function index(Request $request): View
    {
        $this->authorize();

        $filters = $this->extractFilters($request);
        $rows = $this->buildQuery($filters)->paginate(30)->withQueryString();

        // Facet sayıları — boş query'de bile gözüksün (filtre dropdown'ları doldur)
        $facets = [
            'degrees'   => $this->facetCounts('degree_type'),
            'languages' => $this->facetCounts('language'),
            'cities'    => $this->topCities(40),
        ];

        return view('program-search.index', [
            'rows'      => $rows,
            'filters'   => $filters,
            'facets'    => $facets,
            'totalAll'  => Program::active()->count(),
        ]);
    }

    private function authorize(): void
    {
        $user = auth()->user();
        abort_unless($user && in_array($user->role, self::ALLOWED_ROLES, true), 403, 'Bu sayfaya erişim yetkin yok.');
    }

    private function extractFilters(Request $request): array
    {
        return [
            'q'           => trim((string) $request->query('q', '')),
            'degree'      => (string) $request->query('degree', ''),
            'language'    => (string) $request->query('language', ''),
            'subject'     => trim((string) $request->query('subject', '')),
            'city'        => trim((string) $request->query('city', '')),
            'tuition_max' => (string) $request->query('tuition_max', ''),
            'sort'        => (string) $request->query('sort', 'relevance'),
        ];
    }

    private function buildQuery(array $f): \Illuminate\Database\Eloquent\Builder
    {
        $q = Program::query()->active();

        if ($f['q'] !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $f['q']) . '%';
            $q->where(function ($sub) use ($like) {
                $sub->where('course_name', 'like', $like)
                    ->orWhere('university_name_cached', 'like', $like)
                    ->orWhere('description_tr', 'like', $like)
                    ->orWhere('study_fields', 'like', $like)
                    ->orWhere('subjects', 'like', $like);
            });
        }

        if ($f['degree'] !== '') {
            $q->where('degree_type', $f['degree']);
        }

        if ($f['language'] !== '') {
            // Exact match — kullanıcı seçtiği değeri net görür.
            // 'both' programlar için explicit 3. seçenek var (uni-assist tarzı).
            $q->where('language', $f['language']);
        }

        if ($f['subject'] !== '') {
            $sub = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $f['subject']) . '%';
            $q->where(function ($w) use ($sub) {
                $w->where('study_fields', 'like', $sub)
                  ->orWhere('subjects', 'like', $sub)
                  ->orWhere('course_name', 'like', $sub);
            });
        }

        if ($f['city'] !== '') {
            $city = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $f['city']) . '%';
            $q->where('location', 'like', $city);
        }

        if ($f['tuition_max'] !== '') {
            $max = (int) $f['tuition_max'];
            if ($max === 0) {
                // Sadece ücretsiz programlar
                $q->where(function ($sub) {
                    $sub->whereNull('tuition_eur_per_semester')->orWhere('tuition_eur_per_semester', 0);
                });
            } else {
                $q->where(function ($sub) use ($max) {
                    $sub->whereNull('tuition_eur_per_semester')->orWhere('tuition_eur_per_semester', '<=', $max);
                });
            }
        }

        return match ($f['sort']) {
            'name'    => $q->orderBy('course_name'),
            'quality' => $q->orderByDesc('quality_score')->orderBy('course_name'),
            'recent'  => $q->orderByDesc('updated_at'),
            default   => $q->orderByDesc('quality_score')->orderByDesc('is_manually_curated')->orderBy('course_name'),
        };
    }

    /** @return array<string,int> */
    private function facetCounts(string $column): array
    {
        return Program::query()
            ->active()
            ->selectRaw("{$column} as val, COUNT(*) as cnt")
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->orderByDesc('cnt')
            ->pluck('cnt', 'val')
            ->all();
    }

    /** @return array<string,int> */
    private function topCities(int $limit): array
    {
        return Program::query()
            ->active()
            ->selectRaw('location as val, COUNT(*) as cnt')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->pluck('cnt', 'val')
            ->all();
    }
}

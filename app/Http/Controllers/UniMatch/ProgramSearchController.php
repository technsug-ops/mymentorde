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
        return $this->renderSearch($request, publicMode: false);
    }

    /**
     * Guest / public versiyonu — auth gerekmez, uni-match.layout kullanır.
     * Route: GET /uni-match/programs
     */
    public function publicIndex(Request $request): View
    {
        return $this->renderSearch($request, publicMode: true);
    }

    private function renderSearch(Request $request, bool $publicMode): View
    {
        $filters = $this->extractFilters($request);
        // university eager load — card'da image_path için, N+1 önler
        $rows = $this->buildQuery($filters)
            ->with(['university:id,image_path'])
            ->paginate(30)
            ->withQueryString();

        $geo = config('germany_geo');

        // Şehirleri büyük/küçük diye ayır
        $allCities = $this->allCities();
        $bigCitySet = array_flip($geo['big_cities']);
        $bigCities = [];
        $smallCities = [];
        foreach ($allCities as $name => $cnt) {
            if (isset($bigCitySet[$name])) {
                $bigCities[$name] = $cnt;
            } else {
                $smallCities[$name] = $cnt;
            }
        }

        // Facet sayıları — boş query'de bile gözüksün (filtre dropdown'ları doldur)
        $facets = [
            'degrees'      => $this->facetCounts('degree_type'),
            'languages'    => $this->facetCounts('language'),
            'cities'       => $allCities,
            'big_cities'   => $bigCities,
            'small_cities' => $smallCities,
            'universities' => $this->topUniversities(600),
            'states'       => $geo['states'],
            'fields'       => $this->topStudyFields(),
        ];

        return view('program-search.index', [
            'rows'       => $rows,
            'filters'    => $filters,
            'facets'     => $facets,
            'totalAll'   => Program::active()->count(),
            'publicMode' => $publicMode,
        ]);
    }

    private function authorize(): void
    {
        $user = auth()->user();
        abort_unless($user && in_array($user->role, self::ALLOWED_ROLES, true), 403, 'Bu sayfaya erişim yetkin yok.');
    }

    private function extractFilters(Request $request): array
    {
        $fields = $request->query('fields', []);
        if (!is_array($fields)) {
            $fields = [];
        }

        return [
            'q'           => trim((string) $request->query('q', '')),
            'state'       => (string) $request->query('state', ''),
            'big_city'    => trim((string) $request->query('big_city', '')),
            'small_city'  => trim((string) $request->query('small_city', '')),
            'university'  => trim((string) $request->query('university', '')),
            'top_uni'     => (string) $request->query('top_uni', ''),
            'fields'      => array_values(array_filter(array_map('strval', $fields), fn ($v) => $v !== '')),
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

        // Eyalet filtresi — config'deki city_to_state mapping ile
        if ($f['state'] !== '') {
            $cityToState = (array) config('germany_geo.city_to_state', []);
            $citiesInState = array_keys(array_filter($cityToState, fn ($s) => $s === $f['state']));
            if ($citiesInState !== []) {
                $q->whereIn('location', $citiesInState);
            } else {
                $q->whereRaw('1=0'); // bilinmeyen eyalet → 0 sonuç
            }
        }

        // Büyük şehir filtresi — alias-aware exact match
        if ($f['big_city'] !== '') {
            $aliases = self::cityAliases($f['big_city']);
            $q->whereIn('location', $aliases);
        }

        // Küçük şehir filtresi — alias-aware exact match
        if ($f['small_city'] !== '') {
            $aliases = self::cityAliases($f['small_city']);
            $q->whereIn('location', $aliases);
        }

        // Üniversite filtresi — datalist'ten seçilen tam isim. Exact match.
        if ($f['university'] !== '') {
            $q->where('university_name_cached', $f['university']);
        }

        // Top üniversite filtresi — config'deki top_10/20/40 listelerinden whereIn
        if ($f['top_uni'] !== '') {
            $topKey = match ($f['top_uni']) {
                'top10' => 'top_10',
                'top20' => 'top_20',
                'top40' => 'top_40',
                default => null,
            };
            if ($topKey) {
                $topList = (array) config("germany_geo.{$topKey}", []);
                $q->whereIn('university_name_cached', $topList);
            }
        }

        // Bölüm kategorisi filtreleri — sol sidebar checkbox'lar (multi-select).
        // study_fields JSON array, içinde tam-eşleşen entry varsa o program seçilir.
        // OR mantığı: seçilen kategorilerden HERHANGİ BİRİNDE eşleşen programlar gelir.
        if (!empty($f['fields'])) {
            $q->where(function ($sub) use ($f) {
                foreach ($f['fields'] as $field) {
                    // JSON array içinde "Engineering" → LIKE '%"Engineering"%' (quote'lu)
                    $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $field);
                    $like = '%"' . $escaped . '"%';
                    $sub->orWhere('study_fields', 'like', $like);
                }
            });
        }

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
            // DB'deki bölümler genelde DE/EN — kullanıcı TR yazarsa description_tr'de bulunur.
            $q->where(function ($w) use ($sub) {
                $w->where('study_fields', 'like', $sub)
                  ->orWhere('subjects', 'like', $sub)
                  ->orWhere('course_name', 'like', $sub)
                  ->orWhere('description_tr', 'like', $sub);
            });
        }

        if ($f['city'] !== '') {
            // EN ↔ DE şehir adı eşdeğerleri — kaynakta tutarsız yazım var.
            // Kullanıcı datalist'ten "München" seçince hem München hem Munich exact-match'lenir.
            $cityAliases = self::cityAliases($f['city']);
            $q->whereIn('location', $cityAliases);
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

    /**
     * EN/DE şehir adı eşdeğerleri — kaynak veride duplikasyon var.
     * Kullanıcı "München" seçince hem "München" hem "Munich" kayıtları aransın.
     */
    /**
     * Canonical isim = pair'in SON elemanı (allCities birleştirmesinde tercih).
     * Şehir alfabetik listede daha yaygın yazımı görmek istiyoruz.
     */
    private const CITY_ALIAS_PAIRS = [
        ['Munich', 'München'],
        ['Cologne', 'Köln'],
        ['Hanover', 'Hannover'],
        ['Brunswick', 'Braunschweig'],
        ['Nuremberg', 'Nürnberg'],
        ['Constance', 'Konstanz'],
        ['Frankfurt/Main', 'Frankfurt a.M.', 'Frankfurt am Main'],
        ['Aix-la-Chapelle', 'Aachen'],
        ['Freiburg i. Br.', 'Freiburg', 'Freiburg im Breisgau'],
    ];

    /** @return list<string> */
    private static function cityAliases(string $city): array
    {
        foreach (self::CITY_ALIAS_PAIRS as $pair) {
            if (in_array($city, $pair, true)) {
                return $pair;
            }
        }
        return [$city];
    }

    /**
     * Tüm şehirler (275) — datalist autocomplete için.
     * EN/DE duplikasyonları tek satırda birleştirilir (canonical = DE adı).
     *
     * @return array<string,int>
     */
    private function allCities(): array
    {
        $rows = Program::query()
            ->active()
            ->selectRaw('location as val, COUNT(*) as cnt')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->pluck('cnt', 'val')
            ->all();

        // EN/DE duplikasyonları birleştir — canonical isim = pair'in son elemanı (DE)
        $merged = [];
        $seen = [];
        foreach ($rows as $name => $cnt) {
            $canonical = $name;
            foreach (self::CITY_ALIAS_PAIRS as $pair) {
                if (in_array($name, $pair, true)) {
                    $canonical = end($pair); // DE adı tercih
                    break;
                }
            }
            $merged[$canonical] = ($merged[$canonical] ?? 0) + $cnt;
            $seen[$name] = true;
        }

        // Alfabetik sırala
        ksort($merged, SORT_NATURAL | SORT_FLAG_CASE);
        return $merged;
    }

    /**
     * Bölüm kategorileri (study_fields JSON array'inden toplanır).
     * Boş ve tek-program'lı (gürültü) kategoriler filtrelenir.
     *
     * @return array<string,int>
     */
    private function topStudyFields(): array
    {
        $counts = [];
        foreach (Program::query()->active()->whereNotNull('study_fields')->pluck('study_fields') as $fields) {
            foreach ((array) $fields as $f) {
                $f = trim((string) $f);
                if ($f === '') continue;
                $counts[$f] = ($counts[$f] ?? 0) + 1;
            }
        }
        // 2'den az olan duplikasyon/typo kategorileri at
        $counts = array_filter($counts, fn ($c) => $c >= 2);
        arsort($counts);
        return $counts;
    }

    /**
     * Tüm üniversiteler — datalist autocomplete için.
     * Alfabetik sıralı (kullanıcı kolay bulsun).
     *
     * @return array<string,int>
     */
    private function topUniversities(int $limit): array
    {
        return Program::query()
            ->active()
            ->selectRaw('university_name_cached as val, COUNT(*) as cnt')
            ->whereNotNull('university_name_cached')
            ->where('university_name_cached', '!=', '')
            ->groupBy('university_name_cached')
            ->orderBy('university_name_cached')
            ->limit($limit)
            ->pluck('cnt', 'val')
            ->all();
    }
}

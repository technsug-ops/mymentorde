<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\GermanyCity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class CityVideosController extends Controller
{
    private const CATEGORIES = ['şehir', 'üniversite', 'yaşam', 'kariyer', 'genel'];

    public function index(Request $request)
    {
        $cities = GermanyCity::query()
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'emoji', 'data']);

        $citySlug = (string) $request->query('city', $cities->first()?->slug ?? 'berlin');
        $selected = $cities->firstWhere('slug', $citySlug) ?? $cities->first();
        $videos   = $this->videosOf($selected);

        $citySummary = $cities->map(function ($c) {
            $list = is_array($c->data) ? ($c->data['videos'] ?? []) : [];
            return [
                'slug'  => $c->slug,
                'name'  => $c->name,
                'emoji' => $c->emoji,
                'count' => count(array_filter($list, fn ($v) => !empty($v['youtube_id']) && $v['youtube_id'] !== 'LzLOhMsjpsw')),
                'total' => count($list),
            ];
        });

        return view('marketing-admin.city-videos.index', [
            'pageTitle'   => 'Şehir Videoları',
            'cities'      => $cities,
            'citySummary' => $citySummary,
            'selected'    => $selected,
            'videos'      => $videos,
            'categories'  => self::CATEGORIES,
        ]);
    }

    public function store(Request $request, string $citySlug)
    {
        $city = $this->findCity($citySlug);
        $data = $this->validatePayload($request);

        $videos   = $this->videosOf($city);
        $videos[] = $data;

        $this->saveVideos($city, $videos);

        return $this->responseFor($request, $citySlug, 'Video eklendi.');
    }

    public function update(Request $request, string $citySlug, int $idx)
    {
        $city   = $this->findCity($citySlug);
        $videos = $this->videosOf($city);
        abort_if(!isset($videos[$idx]), 404);

        $data         = $this->validatePayload($request);
        $videos[$idx] = $data;

        $this->saveVideos($city, $videos);

        return $this->responseFor($request, $citySlug, 'Video güncellendi.');
    }

    public function destroy(Request $request, string $citySlug, int $idx)
    {
        $city   = $this->findCity($citySlug);
        $videos = $this->videosOf($city);
        abort_if(!isset($videos[$idx]), 404);

        array_splice($videos, $idx, 1);
        $this->saveVideos($city, $videos);

        return $this->responseFor($request, $citySlug, 'Video silindi.');
    }

    public function move(Request $request, string $citySlug, int $idx)
    {
        $city   = $this->findCity($citySlug);
        $videos = $this->videosOf($city);
        abort_if(!isset($videos[$idx]), 404);

        $direction = $request->input('direction') === 'down' ? 1 : -1;
        $target    = $idx + $direction;
        if ($target < 0 || $target >= count($videos)) {
            return $this->responseFor($request, $citySlug, 'Sıra değişmedi.');
        }

        [$videos[$idx], $videos[$target]] = [$videos[$target], $videos[$idx]];
        $this->saveVideos($city, $videos);

        return $this->responseFor($request, $citySlug, 'Sıra güncellendi.');
    }

    private function findCity(string $slug): GermanyCity
    {
        $city = GermanyCity::where('slug', $slug)->first();
        abort_if(!$city, 404, "Şehir bulunamadı: {$slug}");
        return $city;
    }

    private function videosOf(?GermanyCity $city): array
    {
        if (!$city) {
            return [];
        }
        $data = is_array($city->data) ? $city->data : [];
        return $data['videos'] ?? [];
    }

    private function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'youtube_url' => ['required', 'string', 'max:300'],
            'title'       => ['required', 'string', 'max:200'],
            'category'    => ['required', 'string', Rule::in(self::CATEGORIES)],
            'duration'    => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        return [
            'youtube_id'  => $this->extractYouTubeId($data['youtube_url']),
            'title'       => $data['title'],
            'category'    => $data['category'],
            'duration'    => $data['duration'] ?? '',
            'description' => $data['description'] ?? '',
        ];
    }

    private function extractYouTubeId(string $url): string
    {
        $url = trim($url);
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|v/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }
        if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url)) {
            return $url;
        }
        abort(422, 'Geçersiz YouTube URL\'si veya video ID\'si.');
    }

    private function saveVideos(GermanyCity $city, array $videos): void
    {
        $data           = is_array($city->data) ? $city->data : [];
        $data['videos'] = array_values($videos);

        DB::table('germany_cities')
            ->where('id', $city->id)
            ->update([
                'data'       => json_encode($data, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        try {
            Cache::forget('germany_cities_all');
        } catch (\Throwable $e) {
        }
    }

    private function responseFor(Request $request, string $citySlug, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }
        return redirect('/mktg-admin/city-videos?city=' . urlencode($citySlug))->with('status', $message);
    }
}

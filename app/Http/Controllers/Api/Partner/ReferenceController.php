<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Referans veriler — eyalet listesi, bölüm kategorisi listesi.
 * Cache'lenebilir, sayım için kullanışlı.
 */
class ReferenceController extends Controller
{
    /**
     * GET /api/v1/partner/states
     * 16 Bundesländer + her birinde kaç program/üniversite var.
     */
    public function states(): JsonResponse
    {
        $data = Cache::remember('api.partner.states', 3600, function () {
            $states = (array) config('germany_geo.states', []);
            $cityToState = (array) config('germany_geo.city_to_state', []);

            // Eyalet bazında program sayısı
            $counts = [];
            foreach ($states as $key => $name) {
                $cities = array_keys(array_filter($cityToState, fn ($s) => $s === $key));
                $counts[$key] = $cities
                    ? Program::query()->active()->whereIn('location', $cities)->count()
                    : 0;
            }

            $out = [];
            foreach ($states as $key => $name) {
                $out[] = [
                    'key'           => $key,
                    'name'          => $name,
                    'program_count' => $counts[$key],
                ];
            }
            return $out;
        });

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/v1/partner/study-fields
     * Tüm bölüm kategorileri (en az 2 program olan) + sayıları.
     */
    public function studyFields(): JsonResponse
    {
        $data = Cache::remember('api.partner.study_fields', 3600, function () {
            $counts = [];
            foreach (Program::query()->active()->whereNotNull('study_fields')->pluck('study_fields') as $fields) {
                foreach ((array) $fields as $f) {
                    $f = trim((string) $f);
                    if ($f === '') continue;
                    $counts[$f] = ($counts[$f] ?? 0) + 1;
                }
            }
            $counts = array_filter($counts, fn ($c) => $c >= 2);
            arsort($counts);

            $out = [];
            foreach ($counts as $name => $count) {
                $out[] = ['name' => $name, 'program_count' => $count];
            }
            return $out;
        });

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/v1/partner/meta
     * Hesap durumu — kalan quota, kullanılan request sayısı, last_used.
     * Partner kendi quota'sını izleyebilir.
     */
    public function meta(\Illuminate\Http\Request $request): JsonResponse
    {
        /** @var \App\Models\ApiPartner $partner */
        $partner = $request->attributes->get('api_partner');

        return response()->json([
            'data' => [
                'partner_name'        => $partner->name,
                'partner_slug'        => $partner->slug,
                'rate_limit_per_hour' => $partner->rate_limit_per_hour,
                'total_requests'      => $partner->total_requests,
                'last_used_at'        => optional($partner->last_used_at)->toIso8601String(),
                'api_version'         => 'v1',
            ],
        ]);
    }
}

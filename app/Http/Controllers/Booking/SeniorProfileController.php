<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Services\Booking\SeniorProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Marketplace Phase 7 — public senior profil sayfasi (/uzman/{slug}).
 *
 * - GET /uzman/{slug}            -> public.senior-profile view (SEO + JSON-LD)
 * - GET /uzman/{slug}/reviews    -> JSON paginated review listesi (load more icin)
 *
 * Hata fallback'i 404 — bayat link / kapali senior icin Laravel default page.
 */
class SeniorProfileController extends Controller
{
    public function __construct(
        private readonly SeniorProfileService $service = new SeniorProfileService()
    ) {
    }

    public function show(string $slug): View
    {
        $profile = $this->service->getProfile($slug);
        if (!$profile) {
            abort(404);
        }

        // SEO meta
        $seoTitle = $profile['display_name'] . ' — ' . ($profile['tagline'] ?: 'Mentörde Uzman Danışmanı');
        $seoDesc  = trim(mb_substr($profile['bio'] ?: $profile['tagline'] ?: 'Almanya yolunda birebir danışmanlık. Ücretsiz tanışma görüşmesi al.', 0, 200));

        return view('public.senior-profile', [
            'p'        => $profile,
            'seoTitle' => $seoTitle,
            'seoDesc'  => $seoDesc,
        ]);
    }

    /**
     * AJAX load-more — JSON yorum listesi.
     * Query params: page (default 1), per (10..30)
     */
    public function reviews(string $slug, Request $request): JsonResponse
    {
        $profile = $this->service->getProfile($slug);
        if (!$profile) {
            return response()->json(['ok' => false, 'error' => 'not_found'], 404);
        }

        $page = max(1, (int) $request->query('page', 1));
        $per  = min(30, max(5, (int) $request->query('per', 10)));
        $offset = ($page - 1) * $per;

        try {
            $reviews = $this->service->getReviews((int) $profile['senior_user_id'], $per, $offset);

            $items = $reviews->map(function ($r) {
                return [
                    'id'           => (int) $r->id,
                    'reviewer'     => (string) $r->reviewer_name,
                    'rating'       => (int) $r->rating,
                    'title'        => (string) ($r->title ?? ''),
                    'body'         => (string) ($r->body ?? ''),
                    'is_verified'  => (bool) $r->is_verified,
                    'submitted_at' => optional($r->submitted_at ?: $r->created_at)->format('Y-m-d'),
                    'submitted_human' => optional($r->submitted_at ?: $r->created_at)->diffForHumans(),
                ];
            })->all();

            return response()->json([
                'ok'       => true,
                'items'    => $items,
                'page'     => $page,
                'per'      => $per,
                'has_more' => count($items) === $per,
            ]);
        } catch (\Throwable $e) {
            Log::warning('senior_profile.reviews_load_failed', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'error' => 'load_failed'], 500);
        }
    }
}

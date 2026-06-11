<?php

namespace App\Services\Booking;

use App\Models\PublicBooking;
use App\Models\SeniorAvailabilityPattern;
use App\Models\SeniorBookingSetting;
use App\Models\SeniorReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Marketplace Phase 7 — public /uzman/{slug} sayfasi icin senior profile bundle'i.
 *
 * Profile sayfasinin tum verisini tek call'da hazirlar:
 *   - Senior meta (display_name, tagline, bio, avatar, languages, topics)
 *   - Rating + review istatistikleri (cache'li senior_booking_settings kolonlarindan)
 *   - Son N onayli public review
 *   - Topic dagilimi (en cok hangi konuda booking gelmis)
 *   - Topic + rating breakdown
 *
 * Bu service kontrolcu'den sade tek call ile kullanilir.
 */
class SeniorProfileService
{
    /**
     * Public slug -> profile bundle.
     *
     * @return array<string,mixed>|null  Senior bulunmaz / public degilse null doner.
     */
    public function getProfile(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $setting = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('public_slug', $slug)
            ->where('is_active', true)
            ->where('is_public', true)
            ->first();

        if (!$setting) {
            return null;
        }

        $senior = User::query()
            ->withoutGlobalScopes()
            ->where('id', $setting->senior_user_id)
            ->first();

        if (!$senior) {
            return null;
        }

        $displayName = $setting->display_name ?: ($senior->name ?? 'Uzman Danışman');
        $tagline     = (string) ($setting->tagline ?? '');
        $bio         = (string) ($setting->bio ?? $senior->bio ?? '');
        $avatar      = $setting->avatar_url ?: ($senior->photo_url ?? null);
        $languages   = is_array($setting->languages) ? array_values($setting->languages) : [];
        $topics      = is_array($setting->topics)    ? array_values($setting->topics)    : [];

        // Stats — cache kolonlari (SeniorReview model event'leriyle dolar)
        $avgRating       = $setting->avg_rating !== null ? (float) $setting->avg_rating : null;
        $totalReviews    = (int) ($setting->total_reviews ?? 0);
        $totalCompleted  = (int) ($setting->total_completed_bookings ?? 0);

        // Top 3 testimonial — yuksek rating + body dolu
        $testimonials = SeniorReview::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $setting->senior_user_id)
            ->where('is_public', true)
            ->where('moderation_status', 'approved')
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->orderByDesc('rating')
            ->orderByDesc('submitted_at')
            ->limit(3)
            ->get();

        // Son 10 review (tum public + approved)
        $reviews = $this->getReviews((int) $setting->senior_user_id, 10);

        // Yildiz dagilimi (1-5)
        $breakdown = $this->getRatingBreakdown((int) $setting->senior_user_id);

        // Topic dagilimi — booking tablosundan en sik 5 konu (notes alaninda kategori yok,
        // bu sebep ile setting'in topics dizisini sirayla doneriz; ileride ayri kolon eklenirse genisler)
        $topicBreakdown = array_map(
            fn (string $t) => ['key' => $t, 'label' => $this->topicLabel($t)],
            array_slice($topics, 0, 6)
        );

        // Saat patternleri preview (haftanin gunlerine gore)
        $patterns = SeniorAvailabilityPattern::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $setting->senior_user_id)
            ->where('is_active', true)
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        return [
            'slug'            => $setting->public_slug,
            'senior_user_id'  => (int) $setting->senior_user_id,
            'company_id'      => (int) $setting->company_id,
            'display_name'    => $displayName,
            'tagline'         => $tagline,
            'bio'             => $bio,
            'avatar'          => $avatar,
            'initials'        => $this->initials($displayName),
            'languages'       => $languages,
            'topics'          => $topics,
            'expertise'       => $senior->expertiseTags(),
            'slot_minutes'    => (int) $setting->slot_duration,
            'timezone'        => (string) ($setting->timezone ?: 'Europe/Berlin'),
            'welcome_message' => (string) ($setting->welcome_message ?? ''),
            'stats' => [
                'avg_rating'      => $avgRating,
                'total_reviews'   => $totalReviews,
                'total_completed' => $totalCompleted,
            ],
            'rating_breakdown' => $breakdown,
            'topic_breakdown'  => $topicBreakdown,
            'testimonials'     => $testimonials,
            'reviews'          => $reviews,
            'availability'     => $this->summarizeAvailability($patterns),
            'booking_url'      => route('booking.public.show', ['slug' => $setting->public_slug]),
        ];
    }

    /**
     * Son N onayli + public review'i doner.
     */
    public function getReviews(int $seniorUserId, int $limit = 20, int $offset = 0): Collection
    {
        return SeniorReview::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $seniorUserId)
            ->where('is_public', true)
            ->where('moderation_status', 'approved')
            ->orderByDesc('submitted_at')
            ->skip(max(0, $offset))
            ->take(min(50, max(1, $limit)))
            ->get();
    }

    /**
     * Recompute cache (delegasyon — istek uzerine).
     */
    public function recomputeStats(int $seniorUserId): void
    {
        SeniorReview::recomputeStats($seniorUserId);
    }

    /**
     * 1-5 yildiz dagilimi.
     *
     * @return array<int,int>
     */
    private function getRatingBreakdown(int $seniorUserId): array
    {
        $rows = SeniorReview::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $seniorUserId)
            ->where('is_public', true)
            ->where('moderation_status', 'approved')
            ->selectRaw('rating, COUNT(*) as cnt')
            ->groupBy('rating')
            ->pluck('cnt', 'rating')
            ->all();

        $out = [];
        for ($i = 5; $i >= 1; $i--) {
            $out[$i] = (int) ($rows[$i] ?? 0);
        }
        return $out;
    }

    private function summarizeAvailability($patterns): array
    {
        $days = SeniorAvailabilityPattern::WEEKDAY_LABELS_TR;
        $out = [];
        foreach ($patterns as $p) {
            $dayLabel = $days[(int) $p->weekday] ?? '—';
            $startStr = substr((string) $p->start_time, 0, 5);
            $endStr   = substr((string) $p->end_time, 0, 5);
            $out[] = [
                'day'   => $dayLabel,
                'start' => $startStr,
                'end'   => $endStr,
            ];
        }
        return $out;
    }

    private function topicLabel(string $key): string
    {
        return match ($key) {
            'uni-applications' => 'Üniversite Başvurusu',
            'visa'             => 'Vize Süreci',
            'document'         => 'Belge & APS',
            'career'           => 'Kariyer & Hayat',
            default            => ucfirst(str_replace('-', ' ', $key)),
        };
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = mb_substr($parts[count($parts) - 1] ?? '', 0, 1);
        $out = mb_strtoupper(trim($first . $last));
        return $out !== '' ? $out : 'M';
    }
}

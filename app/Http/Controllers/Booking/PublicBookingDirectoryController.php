<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\PublicBooking;
use App\Models\SeniorBookingSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Marketplace Phase 4 — public uzman listesi (/randevu).
 *
 * Modern Skillshare/Superpeer tarzı listing:
 *   - Hero + filtre bar (search, dil, konu)
 *   - 12'lik sayfalanmış senior kartları
 *   - Tıklayınca /book/{slug} widget'ına gider (mevcut akış)
 *
 * Hiçbir yan etki yok: BookingLandingController eski URL'lerini bozmaz; bu yeni
 * controller GET /randevu için bağlanır, eski landing.blade.php opsiyonel
 * yedek olarak kalır.
 */
class PublicBookingDirectoryController extends Controller
{
    /**
     * Desteklenen filtre değerleri — UI dropdown'larıyla senkron tutulur.
     *
     * @var array<string,string>
     */
    private const LANGUAGES = [
        'tr' => 'Türkçe',
        'en' => 'İngilizce',
        'de' => 'Almanca',
    ];

    /**
     * @var array<string,string>
     */
    private const TOPICS = [
        'uni-applications' => 'Üniversite Başvurusu',
        'visa'             => 'Vize Süreci',
        'document'         => 'Belge & APS',
        'career'           => 'Kariyer & Hayat',
    ];

    public function index(Request $request): View
    {
        $q     = trim((string) $request->query('q', ''));
        $lang  = strtolower(trim((string) $request->query('lang', '')));
        $topic = strtolower(trim((string) $request->query('topic', '')));

        // Whitelist kontrolü — bilinmeyen değer = filtresiz
        if (!array_key_exists($lang, self::LANGUAGES)) {
            $lang = '';
        }
        if (!array_key_exists($topic, self::TOPICS)) {
            $topic = '';
        }

        $query = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->where('is_public', true)
            ->whereNotNull('public_slug');

        if ($q !== '') {
            $query->where(function (Builder $sub) use ($q): void {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $sub->where('display_name', 'like', $like)
                    ->orWhere('tagline', 'like', $like)
                    ->orWhere('bio', 'like', $like);
            });
        }

        // JSON LIKE — dialect-agnostic (MySQL, SQLite, MariaDB hepsi destekler)
        if ($lang !== '') {
            $query->where('languages', 'like', '%"' . $lang . '"%');
        }
        if ($topic !== '') {
            $query->where('topics', 'like', '%"' . $topic . '"%');
        }

        $paginator = $query
            ->orderBy('directory_order')
            ->orderBy('display_name')
            ->paginate(12)
            ->withQueryString();

        // İlgili senior user kayıtları + her birinin tamamlanmış randevu sayısı
        $seniorIds = collect($paginator->items())->pluck('senior_user_id')->all();

        $seniors = User::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $seniorIds)
            ->get(['id', 'name', 'email', 'bio', 'photo_url', 'expertise_tags'])
            ->keyBy('id');

        // Seans sayısı — tamamlanmış (started) randevular
        $sessionCounts = [];
        if (!empty($seniorIds)) {
            try {
                $sessionCounts = PublicBooking::query()
                    ->withoutGlobalScopes()
                    ->whereIn('senior_user_id', $seniorIds)
                    // Tamamlanmış görüşme sayısı: confirmed + completed (eğer enum genişlerse).
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->groupBy('senior_user_id')
                    ->select('senior_user_id', DB::raw('COUNT(*) as cnt'))
                    ->pluck('cnt', 'senior_user_id')
                    ->all();
            } catch (\Throwable $e) {
                $sessionCounts = [];
            }
        }

        $items = collect($paginator->items())->map(function (SeniorBookingSetting $s) use ($seniors, $sessionCounts): array {
            $u = $seniors->get($s->senior_user_id);
            $name = $s->display_name ?: ($u?->name ?? 'Uzman Danışman');
            $expertise = $u ? $u->expertiseTags() : [];
            $languages = is_array($s->languages) ? array_values($s->languages) : [];
            $topics    = is_array($s->topics)    ? array_values($s->topics)    : [];

            return [
                'slug'         => $s->public_slug,
                'name'         => $name,
                'tagline'      => (string) ($s->tagline ?: ''),
                'bio'          => $this->shortBio($s->bio ?: ($u?->bio ?? '')),
                'avatar'       => $s->avatar_url ?: ($u?->photo_url ?? null),
                'initials'     => $this->initials($name),
                'languages'    => $languages,
                'topics'       => $topics,
                'expertise'    => array_slice($expertise, 0, 4),
                'slot_minutes' => (int) $s->slot_duration,
                'sessions'     => (int) ($sessionCounts[$s->senior_user_id] ?? 0),
                // Tier / fiyat — Stripe entegrasyonu sonrası gerçek değer; şimdilik "Ücretsiz görüşme"
                'price_label'  => 'Ücretsiz tanışma',
            ];
        })->values()->all();

        return view('public.booking-directory', [
            'items'      => $items,
            'paginator'  => $paginator,
            'filters'    => [
                'q'     => $q,
                'lang'  => $lang,
                'topic' => $topic,
            ],
            'languages'  => self::LANGUAGES,
            'topics'     => self::TOPICS,
            'totalCount' => $paginator->total(),
        ]);
    }

    private function shortBio(?string $bio): string
    {
        $bio = trim((string) $bio);
        if ($bio === '') {
            return '';
        }
        if (mb_strlen($bio) <= 180) {
            return $bio;
        }
        return rtrim(mb_substr($bio, 0, 177)) . '…';
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

<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\GuestPortalTrait;
use App\Models\Marketing\CmsContent;
use App\Models\UserContentReaction;
use App\Models\UserSavedContent;
use App\Services\GuestResolverService;
use App\Services\GuestViewDataService;
use Illuminate\Http\Request;

class GuestContentController extends Controller
{
    use GuestPortalTrait;

    public function __construct(
        private readonly GuestResolverService $guestResolver,
        private readonly GuestViewDataService $viewData,
    ) {}

    // ── Content Hub (Keşfet) ─────────────────────────────────────────────────

    public function discoverPage(Request $request)
    {
        $guest    = $this->resolveGuest($request);
        $data     = $this->buildViewData($request, $guest);
        $category = $request->get('cat');
        $type     = $request->get('type');
        $search   = trim((string) $request->get('q', ''));

        $query = CmsContent::query()
            ->where('status', 'published')
            ->where(function ($q) {
                $q->where('target_audience', 'all')->orWhere('target_audience', 'guests');
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('featured_order')
            ->orderByDesc('published_at');

        if ($category) $query->where('category', $category);
        if ($type)     $query->where('type', $type);
        if ($search)   $query->where('title_tr', 'like', '%' . $search . '%');

        $items    = $query->paginate(12);
        $featured = CmsContent::where('is_featured', true)->where('status', 'published')
            ->where(fn ($q) => $q->where('target_audience', 'all')->orWhere('target_audience', 'guests'))
            ->orderByDesc('featured_order')->limit(3)->get();
        $popular  = CmsContent::where('status', 'published')
            ->where(fn ($q) => $q->where('target_audience', 'all')->orWhere('target_audience', 'guests'))
            ->orderByDesc('metric_total_views')->limit(5)->get(['id', 'slug', 'title_tr', 'type', 'category']);

        $data['items']    = $items;
        $data['featured'] = $featured;
        $data['popular']  = $popular;
        $data['cat']      = $category;
        $data['type']     = $type;
        $data['search']   = $search;

        return view('guest.discover', $data);
    }

    public function discoverMore(Request $request): \Illuminate\Http\JsonResponse
    {
        $category = $request->get('cat');
        $type     = $request->get('type');

        $query = CmsContent::query()->where('status', 'published')
            ->where(fn ($q) => $q->where('target_audience', 'all')->orWhere('target_audience', 'guests'))
            ->orderByDesc('published_at');

        if ($category) $query->where('category', $category);
        if ($type)     $query->where('type', $type);

        $items = $query->paginate(12);

        return response()->json([
            'ok'        => true,
            'items'     => $items->items(),
            'next_page' => $items->nextPageUrl(),
        ]);
    }

    public function contentDetail(Request $request, string $slug)
    {
        $item = CmsContent::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $item->incrementViews(unique: true);

        if (!$item->metric_avg_read_time_seconds && $item->content_tr) {
            $words = str_word_count(strip_tags($item->content_tr));
            $item->metric_avg_read_time_seconds = max(60, (int) round($words / 200) * 60);
        }

        $related  = CmsContent::where('status', 'published')->where('category', $item->category)->where('id', '!=', $item->id)->orderByDesc('published_at')->limit(4)->get();
        $prevItem = CmsContent::where('status', 'published')->where('category', $item->category)->where('published_at', '<', $item->published_at)->orderByDesc('published_at')->first(['slug', 'title_tr', 'type']);
        $nextItem = CmsContent::where('status', 'published')->where('category', $item->category)->where('published_at', '>', $item->published_at)->orderBy('published_at')->first(['slug', 'title_tr', 'type']);

        $guest  = $this->resolveGuest($request);
        $data   = $this->buildViewData($request, $guest);
        $userId = auth()->id();

        $data['item']      = $item;
        $data['related']   = $related;
        $data['prevItem']  = $prevItem;
        $data['nextItem']  = $nextItem;
        $data['isSaved']   = $userId ? UserSavedContent::where('user_id', $userId)->where('cms_content_id', $item->id)->exists() : false;
        $data['isLiked']   = $userId ? UserContentReaction::where('user_id', $userId)->where('cms_content_id', $item->id)->where('type', 'like')->exists() : false;
        $data['likeCount'] = UserContentReaction::where('cms_content_id', $item->id)->where('type', 'like')->count();

        return view('guest.content-detail', $data);
    }

    // ── Favorilerim / Reactions ──────────────────────────────────────────────

    public function toggleSave(Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        $item   = CmsContent::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['ok' => false, 'message' => 'Giriş yapmalısınız.'], 401);
        }
        $existing = UserSavedContent::where('user_id', $userId)->where('cms_content_id', $item->id)->first();
        if ($existing) {
            $existing->delete();
            return response()->json(['ok' => true, 'saved' => false]);
        }
        UserSavedContent::create(['user_id' => $userId, 'cms_content_id' => $item->id]);

        return response()->json(['ok' => true, 'saved' => true]);
    }

    public function savedList(Request $request)
    {
        $guest    = $this->resolveGuest($request);
        $data     = $this->buildViewData($request, $guest);
        $userId   = auth()->id();
        $savedIds = $userId ? UserSavedContent::where('user_id', $userId)->pluck('cms_content_id') : collect();
        $items    = CmsContent::whereIn('id', $savedIds)->where('status', 'published')->orderByDesc('id')->paginate(12);
        $data['items'] = $items;

        return view('guest.saved', $data);
    }

    public function toggleReaction(Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        $item   = CmsContent::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['ok' => false, 'message' => 'Giriş yapmalısınız.'], 401);
        }
        $existing = UserContentReaction::where('user_id', $userId)->where('cms_content_id', $item->id)->where('type', 'like')->first();
        if ($existing) {
            $existing->delete();
            $reacted = false;
        } else {
            UserContentReaction::create(['user_id' => $userId, 'cms_content_id' => $item->id, 'type' => 'like']);
            $reacted = true;
        }
        $count = UserContentReaction::where('cms_content_id', $item->id)->where('type', 'like')->count();

        return response()->json(['ok' => true, 'reacted' => $reacted, 'count' => $count]);
    }

    // ── Global Search ────────────────────────────────────────────────────────

    public function globalSearch(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim($request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['error' => 'Minimum 2 karakter.'], 422);
        }

        $guest   = $this->resolveGuest($request);
        $needle  = '%' . $q . '%';
        $results = collect();

        if ($guest) {
            $studentId = trim((string) ($guest->converted_student_id ?? ''));
            $ownerId   = $studentId !== '' ? $studentId : ('GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT));
            \App\Models\Document::where('student_id', $ownerId)
                ->where(fn ($w) => $w->where('original_file_name', 'like', $needle))
                ->limit(4)
                ->get(['id', 'original_file_name', 'status', 'updated_at'])
                ->each(fn ($d) => $results->push([
                    'type'  => 'document',
                    'icon'  => '📄',
                    'title' => $d->original_file_name,
                    'sub'   => 'Belge — ' . $d->status,
                    'url'   => '/guest/registration/documents',
                    'date'  => $d->updated_at?->format('d.m.Y'),
                ]));

            \App\Models\GuestTicket::where('guest_application_id', $guest->id)
                ->where(fn ($w) => $w->where('subject', 'like', $needle)->orWhere('body', 'like', $needle))
                ->limit(4)
                ->get(['id', 'subject', 'status', 'created_at'])
                ->each(fn ($t) => $results->push([
                    'type'  => 'ticket',
                    'icon'  => '🎫',
                    'title' => $t->subject,
                    'sub'   => 'Destek Talebi — ' . $t->status,
                    'url'   => '/guest/tickets',
                    'date'  => $t->created_at?->format('d.m.Y'),
                ]));
        }

        CmsContent::where('status', 'published')
            ->where(fn ($w) => $w->where('target_audience', 'all')->orWhere('target_audience', 'guests'))
            ->where(fn ($w) => $w->where('title_tr', 'like', $needle)->orWhere('summary_tr', 'like', $needle))
            ->limit(4)
            ->get(['slug', 'title_tr', 'type', 'category'])
            ->each(fn ($c) => $results->push([
                'type'  => 'content',
                'icon'  => '📖',
                'title' => $c->title_tr,
                'sub'   => 'İçerik — ' . $c->type,
                'url'   => '/guest/content/' . $c->slug,
                'date'  => '',
            ]));

        return response()->json(['results' => $results->take(10)->values()]);
    }
}

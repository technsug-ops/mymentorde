<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\CmsContent;
use App\Models\Marketing\SocialMediaAccount;
use App\Models\Marketing\SocialMediaPost;
use App\Models\MarketingCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SocialPostController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'platform' => (string) $request->query('platform', 'all'),
            'status' => (string) $request->query('status', 'all'),
            'account_id' => (string) $request->query('account_id', 'all'),
        ];

        $query = SocialMediaPost::query()
            ->with('account:id,account_name')
            ->orderByDesc('id');
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('caption', 'like', "%{$q}%")
                    ->orWhere('post_url', 'like', "%{$q}%");
            });
        }
        if ($filters['platform'] !== 'all') {
            $query->where('platform', $filters['platform']);
        }
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if ($filters['account_id'] !== 'all') {
            $query->where('account_id', (int) $filters['account_id']);
        }

        $rows = $query->paginate(20)->withQueryString();
        $editId = (int) $request->query('edit_id', 0);
        $editing = $editId > 0 ? SocialMediaPost::query()->find($editId) : null;

        return view('marketing-admin.social.posts', [
            'pageTitle' => 'Sosyal Gonderiler',
            'title' => 'Post Listesi',
            'rows' => $rows,
            'filters' => $filters,
            'editing' => $editing,
            'statusOptions' => $this->statusOptions(),
            'postTypeOptions' => $this->postTypeOptions(),
            'platformOptions' => $this->platformOptions(),
            'accountOptions' => SocialMediaAccount::query()->where('is_active', true)->orderBy('account_name')->get(['id', 'account_name', 'platform']),
            'campaignOptions' => MarketingCampaign::query()->orderByDesc('id')->limit(150)->get(['id', 'name']),
            'contentOptions' => CmsContent::query()->orderByDesc('id')->limit(200)->get(['id', 'title_tr']),
            'stats' => (function (): array {
                $agg = SocialMediaPost::query()
                    ->selectRaw("
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                        COALESCE(SUM(metric_likes + metric_comments + metric_shares + metric_saves), 0) as engagement
                    ")
                    ->first();
                return [
                    'total'      => (int) ($agg->total ?? 0),
                    'published'  => (int) ($agg->published ?? 0),
                    'scheduled'  => (int) ($agg->scheduled ?? 0),
                    'engagement' => (int) ($agg->engagement ?? 0),
                ];
            })(),
        ]);
    }

    public function create()
    {
        return redirect('/mktg-admin/social/posts');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, true);

        // Gelecek tarihli scheduled_at varsa status otomatik 'scheduled' olsun
        $resolvedStatus = $data['status'] ?? 'idea';
        if (!empty($data['scheduled_at']) && Carbon::parse($data['scheduled_at'])->isFuture()) {
            $resolvedStatus = 'scheduled';
        }

        $row = SocialMediaPost::query()->create([
            'account_id' => (int) $data['account_id'],
            'platform' => strtolower((string) $data['platform']),
            'caption' => $data['caption'] ?? null,
            'media_urls' => $this->normalizeCsv($request->input('media_urls', '')),
            'post_type' => $data['post_type'],
            'post_url' => $data['post_url'] ?? null,
            'status' => $resolvedStatus,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'published_at' => $resolvedStatus === 'published' ? now() : null,
            'metric_views' => (int) ($data['metric_views'] ?? 0),
            'metric_likes' => (int) ($data['metric_likes'] ?? 0),
            'metric_comments' => (int) ($data['metric_comments'] ?? 0),
            'metric_shares' => (int) ($data['metric_shares'] ?? 0),
            'metric_saves' => (int) ($data['metric_saves'] ?? 0),
            'metric_reach' => (int) ($data['metric_reach'] ?? 0),
            'metric_impressions' => (int) ($data['metric_impressions'] ?? 0),
            'metric_engagement_rate' => (float) ($data['metric_engagement_rate'] ?? 0),
            'metric_click_through' => (int) ($data['metric_click_through'] ?? 0),
            'metric_guest_registrations' => (int) ($data['metric_guest_registrations'] ?? 0),
            'tags' => $this->normalizeCsv($request->input('tags', '')),
            'linked_campaign_id' => $data['linked_campaign_id'] ?? null,
            'linked_content_id' => $data['linked_content_id'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => (int) $request->user()->id,
        ]);

        $this->refreshAccountCounters((int) $row->account_id);
        return $this->responseFor($request, ['ok' => true, 'id' => $row->id], 'Sosyal post eklendi.', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        return redirect('/mktg-admin/social/posts?edit_id='.$id);
    }

    public function edit(string $id)
    {
        return redirect('/mktg-admin/social/posts?edit_id='.$id);
    }

    public function update(Request $request, string $id)
    {
        $row = SocialMediaPost::query()->findOrFail($id);
        $data = $this->validatePayload($request, false);

        $payload = array_filter([
            'account_id' => $data['account_id'] ?? null,
            'platform' => isset($data['platform']) ? strtolower((string) $data['platform']) : null,
            'caption' => $data['caption'] ?? null,
            'post_type' => $data['post_type'] ?? null,
            'post_url' => $data['post_url'] ?? null,
            'status' => $data['status'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'metric_views' => $data['metric_views'] ?? null,
            'metric_likes' => $data['metric_likes'] ?? null,
            'metric_comments' => $data['metric_comments'] ?? null,
            'metric_shares' => $data['metric_shares'] ?? null,
            'metric_saves' => $data['metric_saves'] ?? null,
            'metric_reach' => $data['metric_reach'] ?? null,
            'metric_impressions' => $data['metric_impressions'] ?? null,
            'metric_engagement_rate' => $data['metric_engagement_rate'] ?? null,
            'metric_click_through' => $data['metric_click_through'] ?? null,
            'metric_guest_registrations' => $data['metric_guest_registrations'] ?? null,
            'linked_campaign_id' => $data['linked_campaign_id'] ?? null,
            'linked_content_id' => $data['linked_content_id'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
        ], fn ($v) => $v !== null);

        if ($request->has('media_urls')) {
            $payload['media_urls'] = $this->normalizeCsv($request->input('media_urls', ''));
        }
        if ($request->has('tags')) {
            $payload['tags'] = $this->normalizeCsv($request->input('tags', ''));
        }
        // Gelecek tarihli scheduled_at varsa status otomatik 'scheduled' olsun
        if (!empty($payload['scheduled_at']) && Carbon::parse($payload['scheduled_at'])->isFuture()) {
            $payload['status'] = 'scheduled';
        }

        if (($payload['status'] ?? null) === 'published' && $row->published_at === null) {
            $payload['published_at'] = now();
        }

        if ($payload !== []) {
            $row->update($payload);
        }
        $this->refreshAccountCounters((int) $row->account_id);

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Sosyal post guncellendi.');
    }

    public function destroy(Request $request, string $id)
    {
        $row = SocialMediaPost::query()->findOrFail($id);
        $accountId = (int) $row->account_id;
        $row->delete();
        $this->refreshAccountCounters($accountId);

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Sosyal post silindi.');
    }

    public function markPublished(Request $request, string $id)
    {
        $row = SocialMediaPost::query()->findOrFail($id);
        $row->update([
            'status' => 'published',
            'published_at' => $row->published_at ?? now(),
        ]);
        $this->refreshAccountCounters((int) $row->account_id);

        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'status' => 'published'], 'Post publish edildi.');
    }

    public function updateMetrics(Request $request, string $id)
    {
        $data = $request->validate([
            'metric_views' => ['nullable', 'integer', 'min:0'],
            'metric_likes' => ['nullable', 'integer', 'min:0'],
            'metric_comments' => ['nullable', 'integer', 'min:0'],
            'metric_shares' => ['nullable', 'integer', 'min:0'],
            'metric_saves' => ['nullable', 'integer', 'min:0'],
            'metric_reach' => ['nullable', 'integer', 'min:0'],
            'metric_impressions' => ['nullable', 'integer', 'min:0'],
            'metric_engagement_rate' => ['nullable', 'numeric', 'min:0'],
            'metric_click_through' => ['nullable', 'integer', 'min:0'],
            'metric_guest_registrations' => ['nullable', 'integer', 'min:0'],
        ]);
        $row = SocialMediaPost::query()->findOrFail($id);
        $payload = array_filter($data, fn ($v) => $v !== null);
        if ($payload !== []) {
            $row->update($payload);
        }

        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'metrics_updated' => true], 'Post metrikleri guncellendi.');
    }

    public function calendar(Request $request)
    {
        $month = (string) $request->query('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $month.'-01 00:00:00')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = SocialMediaPost::query()
            ->with('account:id,account_name,platform')
            ->where(function ($w) use ($start, $end): void {
                $w->whereBetween('scheduled_at', [$start, $end])
                    ->orWhereBetween('published_at', [$start, $end])
                    ->orWhereBetween('created_at', [$start, $end]);
            })
            ->orderByRaw('COALESCE(published_at, scheduled_at, created_at) asc')
            ->get();

        $grouped = $rows->groupBy(function (SocialMediaPost $row): string {
            $d = $row->published_at ?? $row->scheduled_at ?? $row->created_at;
            return Carbon::parse($d)->format('Y-m-d');
        });

        return view('marketing-admin.social.calendar', [
            'pageTitle' => 'Icerik Takvimi',
            'title' => 'Sosyal Medya Takvimi',
            'month' => $month,
            'rows' => $rows,
            'grouped' => $grouped,
        ]);
    }

    private function validatePayload(Request $request, bool $isCreate): array
    {
        return $request->validate([
            'account_id' => [$isCreate ? 'required' : 'sometimes', 'integer', 'exists:social_media_accounts,id'],
            'platform' => [$isCreate ? 'required' : 'sometimes', Rule::in($this->platformOptions())],
            'caption' => ['nullable', 'string'],
            'media_urls' => ['nullable', 'string'],
            'post_type' => [$isCreate ? 'required' : 'sometimes', Rule::in($this->postTypeOptions())],
            'post_url' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in($this->statusOptions())],
            'scheduled_at' => ['nullable', 'date'],
            'metric_views' => ['nullable', 'integer', 'min:0'],
            'metric_likes' => ['nullable', 'integer', 'min:0'],
            'metric_comments' => ['nullable', 'integer', 'min:0'],
            'metric_shares' => ['nullable', 'integer', 'min:0'],
            'metric_saves' => ['nullable', 'integer', 'min:0'],
            'metric_reach' => ['nullable', 'integer', 'min:0'],
            'metric_impressions' => ['nullable', 'integer', 'min:0'],
            'metric_engagement_rate' => ['nullable', 'numeric', 'min:0'],
            'metric_click_through' => ['nullable', 'integer', 'min:0'],
            'metric_guest_registrations' => ['nullable', 'integer', 'min:0'],
            'tags' => ['nullable', 'string'],
            'linked_campaign_id' => ['nullable', 'integer', 'exists:marketing_campaigns,id'],
            'linked_content_id' => ['nullable', 'integer', 'exists:cms_contents,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }

    private function normalizeCsv(mixed $raw): array
    {
        $txt = trim((string) $raw);
        if ($txt === '') {
            return [];
        }
        return collect(explode(',', $txt))
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function refreshAccountCounters(int $accountId): void
    {
        $account = SocialMediaAccount::query()->find($accountId);
        if (!$account) {
            return;
        }
        $account->update([
            'total_posts' => (int) SocialMediaPost::query()->where('account_id', $accountId)->count(),
            'metrics_last_updated_at' => now(),
        ]);
    }

    private function postTypeOptions(): array
    {
        return ['feed', 'story', 'reel', 'short', 'video', 'carousel'];
    }

    private function statusOptions(): array
    {
        return ['idea', 'draft', 'scheduled', 'published', 'archived'];
    }

    private function platformOptions(): array
    {
        return ['instagram', 'youtube', 'tiktok', 'facebook', 'linkedin', 'x', 'telegram'];
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }
        return redirect('/mktg-admin/social/posts')->with('status', $statusMessage);
    }

    // ─── 2.3 Sosyal Medya Planlayıcı — Toplu Planlama ───────────────────────

    /**
     * POST /mktg-admin/social/posts/schedule-batch
     * Birden fazla platformda çoklu post planla.
     */
    public function schedulePosts(Request $request): \Illuminate\Http\JsonResponse
    {
        $platformList = implode(',', $this->platformOptions());

        $data = $request->validate([
            'posts'                => 'required|array|min:1',
            'posts.*.caption'      => 'required|string|max:5000',
            'posts.*.platform'     => "required|in:{$platformList}",
            'posts.*.account_id'   => 'required|integer|exists:social_media_accounts,id',
            'posts.*.post_type'    => 'nullable|in:feed,story,reel,short,video,carousel',
            'posts.*.scheduled_at' => 'required|date|after:now',
            'posts.*.media_ids'    => 'nullable|array',
            'posts.*.media_ids.*'  => 'integer|exists:cms_media_library,id',
            'posts.*.tags'         => 'nullable|string|max:500',
        ]);

        $created = collect();
        $userId  = (int) $request->user()->id;

        foreach ($data['posts'] as $postData) {
            $mediaUrls = [];
            if (!empty($postData['media_ids'])) {
                $mediaUrls = \App\Models\Marketing\CmsMedia::whereIn('id', $postData['media_ids'])
                    ->pluck('file_url')
                    ->toArray();
            }

            $post = SocialMediaPost::create([
                'account_id'   => (int) $postData['account_id'],
                'platform'     => strtolower((string) $postData['platform']),
                'caption'      => $postData['caption'],
                'post_type'    => $postData['post_type'] ?? 'feed',
                'media_urls'   => $mediaUrls ?: [],
                'tags'         => $this->normalizeCsv($postData['tags'] ?? ''),
                'scheduled_at' => $postData['scheduled_at'],
                'status'       => 'scheduled',
                'created_by'   => $userId,
            ]);
            $this->refreshAccountCounters((int) $post->account_id);
            $created->push($post->id);
        }

        return response()->json(['ok' => true, 'created' => $created->count(), 'post_ids' => $created->all()]);
    }
}

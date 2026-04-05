<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\LeadSourceDatum;
use App\Models\Marketing\CmsCategory;
use App\Models\Marketing\CmsContent;
use App\Models\Marketing\CmsContentRevision;
use App\Models\MarketingCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CMSContentController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', 'all'),
            'type' => (string) $request->query('type', 'all'),
        ];

        $query = CmsContent::query()->orderByDesc('id');
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('slug', 'like', "%{$q}%")
                    ->orWhere('title_tr', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%");
            });
        }
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if ($filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        $rows = $query->paginate(15)->withQueryString();
        $editId = (int) $request->query('edit_id', 0);
        $editing = $editId > 0 ? CmsContent::query()->find($editId) : null;

        return view('marketing-admin.content.index', [
            'pageTitle' => 'CMS Icerik Yonetimi',
            'title' => 'Icerik Listesi',
            'rows' => $rows,
            'filters' => $filters,
            'editing' => $editing,
            'statusOptions' => $this->statusOptions(),
            'typeOptions' => $this->typeOptions(),
            'categories' => CmsCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['code', 'name_tr']),
            'campaignOptions' => MarketingCampaign::query()->orderByDesc('id')->limit(150)->get(['id', 'name']),
            'stats' => [
                'total' => CmsContent::query()->count(),
                'published' => CmsContent::query()->where('status', 'published')->count(),
                'scheduled' => CmsContent::query()->where('status', 'scheduled')->count(),
                'featured' => CmsContent::query()->where('is_featured', true)->count(),
            ],
        ]);
    }

    public function create()
    {
        return redirect('/mktg-admin/content');
    }

    private function sanitizeBody(string $body): string
    {
        // Script tag'larini ve inline event handler'lari temizle (HTML korunsun)
        $body = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $body);
        $body = preg_replace('/\son\w+\s*=\s*["\'][^"\']*["\']/i', '', (string) $body);
        return (string) $body;
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, true);
        $userId = (int) $request->user()->id;

        // Auto-generate slug from title_tr if not provided
        if (empty($data['slug'])) {
            $base = Str::slug($data['title_tr']);
            $slug = $base;
            $i = 2;
            while (CmsContent::query()->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $data['slug'] = $slug;
        }

        $row = CmsContent::query()->create([
            'type' => $data['type'],
            'slug' => $data['slug'],
            'title_tr' => $data['title_tr'],
            'title_de' => Arr::get($data, 'title_de'),
            'title_en' => Arr::get($data, 'title_en'),
            'summary_tr' => Arr::get($data, 'summary_tr'),
            'summary_de' => Arr::get($data, 'summary_de'),
            'summary_en' => Arr::get($data, 'summary_en'),
            'content_tr' => $this->sanitizeBody($data['content_tr']),
            'content_de' => Arr::get($data, 'content_de'),
            'content_en' => Arr::get($data, 'content_en'),
            'cover_image_url' => Arr::get($data, 'cover_image_url'),
            'cover_image_alt' => Arr::get($data, 'cover_image_alt'),
            'gallery_urls' => $this->normalizeCsv($request->input('gallery_urls', '')),
            'video_url' => Arr::get($data, 'video_url'),
            'video_thumbnail_url' => Arr::get($data, 'video_thumbnail_url'),
            'seo_meta_title_tr' => Arr::get($data, 'seo_meta_title_tr'),
            'seo_meta_description_tr' => Arr::get($data, 'seo_meta_description_tr'),
            'seo_keywords' => $this->normalizeCsv($request->input('seo_keywords', '')),
            'seo_canonical_url' => Arr::get($data, 'seo_canonical_url'),
            'seo_og_image_url' => Arr::get($data, 'seo_og_image_url'),
            'status' => Arr::get($data, 'status', 'draft'),
            'published_at' => Arr::get($data, 'status') === 'published' ? now() : null,
            'scheduled_at' => Arr::get($data, 'scheduled_at'),
            'is_featured' => $request->boolean('is_featured', false),
            'featured_order' => Arr::get($data, 'featured_order'),
            'target_audience' => Arr::get($data, 'target_audience', 'all'),
            'target_student_types' => $this->normalizeCsv($request->input('target_student_types', '')),
            'linked_campaign_id' => Arr::get($data, 'linked_campaign_id'),
            'category' => Arr::get($data, 'category'),
            'tags' => $this->normalizeCsv($request->input('tags', '')),
            'current_revision' => 1,
            'created_by' => $userId,
            'last_edited_by' => $userId,
            'approved_by' => Arr::get($data, 'status') === 'published' ? $userId : null,
        ]);

        $this->createRevision($row, $userId, (string) ($data['change_note'] ?? 'initial create'));

        return $this->responseFor($request, ['ok' => true, 'id' => $row->id], 'Icerik olusturuldu.', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        return redirect('/mktg-admin/content?edit_id='.$id);
    }

    public function edit(string $id)
    {
        return redirect('/mktg-admin/content?edit_id='.$id);
    }

    public function update(Request $request, string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $data = $this->validatePayload($request, false, $row->id);
        $payload = array_filter([
            'type' => Arr::get($data, 'type'),
            'slug' => Arr::get($data, 'slug'),
            'title_tr' => Arr::get($data, 'title_tr'),
            'title_de' => Arr::get($data, 'title_de'),
            'title_en' => Arr::get($data, 'title_en'),
            'summary_tr' => Arr::get($data, 'summary_tr'),
            'summary_de' => Arr::get($data, 'summary_de'),
            'summary_en' => Arr::get($data, 'summary_en'),
            'content_tr' => isset($data['content_tr']) ? $this->sanitizeBody($data['content_tr']) : null,
            'content_de' => Arr::get($data, 'content_de'),
            'content_en' => Arr::get($data, 'content_en'),
            'cover_image_url' => Arr::get($data, 'cover_image_url'),
            'cover_image_alt' => Arr::get($data, 'cover_image_alt'),
            'video_url' => Arr::get($data, 'video_url'),
            'video_thumbnail_url' => Arr::get($data, 'video_thumbnail_url'),
            'seo_meta_title_tr' => Arr::get($data, 'seo_meta_title_tr'),
            'seo_meta_description_tr' => Arr::get($data, 'seo_meta_description_tr'),
            'seo_canonical_url' => Arr::get($data, 'seo_canonical_url'),
            'seo_og_image_url' => Arr::get($data, 'seo_og_image_url'),
            'status' => Arr::get($data, 'status'),
            'scheduled_at' => Arr::get($data, 'scheduled_at'),
            'featured_order' => Arr::get($data, 'featured_order'),
            'target_audience' => Arr::get($data, 'target_audience'),
            'linked_campaign_id' => Arr::get($data, 'linked_campaign_id'),
            'category' => Arr::get($data, 'category'),
        ], fn ($v) => $v !== null);

        if ($request->has('is_featured')) {
            $payload['is_featured'] = $request->boolean('is_featured');
        }
        if ($request->has('gallery_urls')) {
            $payload['gallery_urls'] = $this->normalizeCsv($request->input('gallery_urls', ''));
        }
        if ($request->has('seo_keywords')) {
            $payload['seo_keywords'] = $this->normalizeCsv($request->input('seo_keywords', ''));
        }
        if ($request->has('target_student_types')) {
            $payload['target_student_types'] = $this->normalizeCsv($request->input('target_student_types', ''));
        }
        if ($request->has('tags')) {
            $payload['tags'] = $this->normalizeCsv($request->input('tags', ''));
        }
        if (($payload['status'] ?? null) === 'published') {
            $payload['published_at'] = now();
            $payload['approved_by'] = (int) $request->user()->id;
        }

        $payload['last_edited_by'] = (int) $request->user()->id;

        if ($payload !== []) {
            $row->fill($payload);
            $row->current_revision = (int) $row->current_revision + 1;
            $row->save();
            $this->createRevision($row, (int) $request->user()->id, (string) ($data['change_note'] ?? 'manual update'));
        }

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Icerik guncellendi.');
    }

    public function destroy(Request $request, string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $row->delete();
        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Icerik silindi.');
    }

    public function publish(Request $request, string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $row->update([
            'status' => 'published',
            'published_at' => now(),
            'approved_by' => (int) $request->user()->id,
        ]);
        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'status' => 'published'], 'Icerik publish edildi.');
    }

    public function unpublish(Request $request, string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $row->update([
            'status' => 'draft',
            'published_at' => null,
            'approved_by' => null,
        ]);
        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'status' => 'draft'], 'Icerik drafta cekildi.');
    }

    public function schedule(Request $request, string $id)
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);
        $row = CmsContent::query()->findOrFail($id);
        $row->update([
            'status' => 'scheduled',
            'scheduled_at' => Carbon::parse((string) $data['scheduled_at']),
        ]);
        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'status' => 'scheduled'], 'Icerik schedule edildi.');
    }

    public function toggleFeatured(Request $request, string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $featured = !((bool) $row->is_featured);
        $row->update([
            'is_featured' => $featured,
            'featured_order' => $featured ? ((int) ($request->input('featured_order') ?: ($row->featured_order ?: 999))) : null,
        ]);
        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'featured' => $featured], $featured ? 'Featured yapildi.' : 'Featured kaldirildi.');
    }

    public function stats(string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $leadCount = (int) LeadSourceDatum::query()->where('cms_content_id', $row->id)->count();
        $converted = (int) LeadSourceDatum::query()->where('cms_content_id', $row->id)->where('funnel_converted', true)->count();
        $conversionRate = $leadCount > 0 ? round(($converted / $leadCount) * 100, 2) : 0;

        return view('marketing-admin.content.stats', [
            'pageTitle' => 'Icerik Istatistikleri',
            'title' => 'Icerik #'.$id.' istatistikleri',
            'content' => $row,
            'summary' => [
                'views' => (int) $row->metric_total_views,
                'unique_views' => (int) $row->metric_unique_views,
                'avg_read' => (int) $row->metric_avg_read_time_seconds,
                'bounce' => (float) $row->metric_bounce_rate,
                'shares' => (int) $row->metric_shares,
                'lead_count' => $leadCount,
                'lead_converted' => $converted,
                'lead_conversion_rate' => $conversionRate,
            ],
        ]);
    }

    public function revisions(string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $revisions = CmsContentRevision::query()
            ->where('cms_content_id', $row->id)
            ->orderByDesc('revision_number')
            ->paginate(20);

        return view('marketing-admin.content.revisions', [
            'pageTitle' => 'Revizyon Gecmisi',
            'title' => 'Icerik #'.$id.' revizyonlari',
            'content' => $row,
            'revisions' => $revisions,
        ]);
    }

    private function validatePayload(Request $request, bool $isCreate, ?int $currentId = null): array
    {
        $rules = [
            'type' => [$isCreate ? 'required' : 'sometimes', Rule::in($this->typeOptions())],
            'slug' => array_filter([
                'nullable',
                'string',
                'max:190',
                Rule::unique('cms_contents', 'slug')->ignore($currentId),
            ]),
            'title_tr' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'title_de' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'summary_tr' => ['nullable', 'string'],
            'summary_de' => ['nullable', 'string'],
            'summary_en' => ['nullable', 'string'],
            'content_tr' => [$isCreate ? 'required' : 'sometimes', 'string'],
            'content_de' => ['nullable', 'string'],
            'content_en' => ['nullable', 'string'],
            'cover_image_url' => ['nullable', 'string', 'max:500'],
            'cover_image_alt' => ['nullable', 'string', 'max:190'],
            'gallery_urls' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_thumbnail_url' => ['nullable', 'string', 'max:500'],
            'seo_meta_title_tr' => ['nullable', 'string', 'max:255'],
            'seo_meta_description_tr' => ['nullable', 'string', 'max:300'],
            'seo_keywords' => ['nullable', 'string'],
            'seo_canonical_url' => ['nullable', 'string', 'max:500'],
            'seo_og_image_url' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in($this->statusOptions())],
            'scheduled_at' => ['nullable', 'date'],
            'is_featured' => ['nullable'],
            'featured_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'target_audience' => ['nullable', 'string', 'max:80'],
            'target_student_types' => ['nullable', 'string'],
            'linked_campaign_id' => ['nullable', 'integer', 'exists:marketing_campaigns,id'],
            'category' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'string'],
            'change_note' => ['nullable', 'string', 'max:255'],
        ];

        return $request->validate($rules);
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

    private function createRevision(CmsContent $row, int $editorId, string $changeNote = ''): void
    {
        CmsContentRevision::query()->create([
            'cms_content_id' => $row->id,
            'revision_number' => (int) $row->current_revision,
            'edited_by' => $editorId,
            'change_note' => trim($changeNote) !== '' ? trim($changeNote) : 'manual update',
            'snapshot_data' => [
                'title_tr' => $row->title_tr,
                'summary_tr' => $row->summary_tr,
                'content_tr' => $row->content_tr,
                'status' => $row->status,
                'category' => $row->category,
                'tags' => $row->tags,
            ],
            'created_at' => now(),
        ]);
    }

    private function typeOptions(): array
    {
        return ['blog', 'landing', 'guide', 'faq', 'event', 'video_feature', 'podcast', 'presentation', 'experience', 'career_guide', 'tip'];
    }

    private function statusOptions(): array
    {
        return ['draft', 'published', 'scheduled', 'archived'];
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }
        return redirect('/mktg-admin/content')->with('status', $statusMessage);
    }
}

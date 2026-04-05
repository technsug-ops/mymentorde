<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\EventRegistration;
use App\Models\Marketing\MarketingEvent;
use App\Models\MarketingCampaign;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', 'all'),
            'type' => (string) $request->query('type', 'all'),
        ];

        $query = MarketingEvent::query()->orderByDesc('id');
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('title_tr', 'like', "%{$q}%")
                    ->orWhere('type', 'like', "%{$q}%")
                    ->orWhere('venue_city', 'like', "%{$q}%");
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
        $editing = $editId > 0 ? MarketingEvent::query()->find($editId) : null;

        return view('marketing-admin.events.index', [
            'pageTitle' => 'Etkinlik Yonetimi',
            'title' => 'Etkinlik Listesi',
            'rows' => $rows,
            'filters' => $filters,
            'editing' => $editing,
            'stats' => $this->summaryStats(),
            'statusOptions' => $this->statusOptions(),
            'typeOptions' => $this->typeOptions(),
            'formatOptions' => ['online', 'offline', 'hybrid'],
            'campaignOptions' => MarketingCampaign::query()->orderByDesc('id')->limit(100)->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return redirect('/mktg-admin/events');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, true);

        $row = MarketingEvent::query()->create([
            'title_tr' => $data['title_tr'],
            'title_de' => Arr::get($data, 'title_de'),
            'title_en' => Arr::get($data, 'title_en'),
            'description_tr' => $data['description_tr'],
            'description_de' => Arr::get($data, 'description_de'),
            'description_en' => Arr::get($data, 'description_en'),
            'start_date' => $data['start_date'],
            'end_date' => Arr::get($data, 'end_date'),
            'timezone' => Arr::get($data, 'timezone', 'Europe/Berlin'),
            'type' => $data['type'],
            'format' => $data['format'],
            'online_platform' => Arr::get($data, 'online_platform'),
            'online_meeting_url' => Arr::get($data, 'online_meeting_url'),
            'online_meeting_id' => Arr::get($data, 'online_meeting_id'),
            'online_meeting_password' => Arr::get($data, 'online_meeting_password'),
            'online_recording_url' => Arr::get($data, 'online_recording_url'),
            'venue_name' => Arr::get($data, 'venue_name'),
            'venue_address' => Arr::get($data, 'venue_address'),
            'venue_city' => Arr::get($data, 'venue_city'),
            'venue_country' => Arr::get($data, 'venue_country'),
            'venue_map_url' => Arr::get($data, 'venue_map_url'),
            'capacity' => Arr::get($data, 'capacity'),
            'current_registrations' => 0,
            'waitlist_enabled' => $request->boolean('waitlist_enabled', false),
            'target_audience' => Arr::get($data, 'target_audience', 'all'),
            'target_student_types' => $this->normalizeCsv($request->input('target_student_types', '')),
            'cover_image_url' => Arr::get($data, 'cover_image_url'),
            'gallery_urls' => $this->normalizeCsv($request->input('gallery_urls', '')),
            'linked_campaign_id' => Arr::get($data, 'linked_campaign_id'),
            'cms_content_id' => null,
            'reminders' => $this->normalizeJsonArray($request->input('reminders_json', null), []),
            'post_event_survey_enabled' => $request->boolean('post_event_survey_enabled', false),
            'post_event_survey_url' => Arr::get($data, 'post_event_survey_url'),
            'status' => Arr::get($data, 'status', 'draft'),
            'created_by' => (int) $request->user()->id,
        ]);

        $this->refreshEventMetrics($row);

        return $this->responseFor($request, ['ok' => true, 'id' => $row->id], 'Etkinlik olusturuldu.', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        return redirect('/mktg-admin/events?edit_id='.$id);
    }

    public function edit(string $id)
    {
        return redirect('/mktg-admin/events?edit_id='.$id);
    }

    public function update(Request $request, string $id)
    {
        $row = MarketingEvent::query()->findOrFail($id);
        $data = $this->validatePayload($request, false);

        $payload = array_filter([
            'title_tr' => Arr::get($data, 'title_tr'),
            'title_de' => Arr::get($data, 'title_de'),
            'title_en' => Arr::get($data, 'title_en'),
            'description_tr' => Arr::get($data, 'description_tr'),
            'description_de' => Arr::get($data, 'description_de'),
            'description_en' => Arr::get($data, 'description_en'),
            'start_date' => Arr::get($data, 'start_date'),
            'end_date' => Arr::get($data, 'end_date'),
            'timezone' => Arr::get($data, 'timezone'),
            'type' => Arr::get($data, 'type'),
            'format' => Arr::get($data, 'format'),
            'online_platform' => Arr::get($data, 'online_platform'),
            'online_meeting_url' => Arr::get($data, 'online_meeting_url'),
            'online_meeting_id' => Arr::get($data, 'online_meeting_id'),
            'online_meeting_password' => Arr::get($data, 'online_meeting_password'),
            'online_recording_url' => Arr::get($data, 'online_recording_url'),
            'venue_name' => Arr::get($data, 'venue_name'),
            'venue_address' => Arr::get($data, 'venue_address'),
            'venue_city' => Arr::get($data, 'venue_city'),
            'venue_country' => Arr::get($data, 'venue_country'),
            'venue_map_url' => Arr::get($data, 'venue_map_url'),
            'capacity' => Arr::get($data, 'capacity'),
            'target_audience' => Arr::get($data, 'target_audience'),
            'cover_image_url' => Arr::get($data, 'cover_image_url'),
            'linked_campaign_id' => Arr::get($data, 'linked_campaign_id'),
            'post_event_survey_url' => Arr::get($data, 'post_event_survey_url'),
            'status' => Arr::get($data, 'status'),
        ], fn ($v) => $v !== null);

        if ($request->has('waitlist_enabled')) {
            $payload['waitlist_enabled'] = $request->boolean('waitlist_enabled');
        }
        if ($request->has('post_event_survey_enabled')) {
            $payload['post_event_survey_enabled'] = $request->boolean('post_event_survey_enabled');
        }
        if ($request->has('target_student_types')) {
            $payload['target_student_types'] = $this->normalizeCsv($request->input('target_student_types', ''));
        }
        if ($request->has('gallery_urls')) {
            $payload['gallery_urls'] = $this->normalizeCsv($request->input('gallery_urls', ''));
        }
        if ($request->has('reminders_json')) {
            $payload['reminders'] = $this->normalizeJsonArray($request->input('reminders_json', null), (array) ($row->reminders ?? []));
        }

        if ($payload !== []) {
            $row->update($payload);
        }
        $this->refreshEventMetrics($row->fresh());

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Etkinlik guncellendi.');
    }

    public function destroy(Request $request, string $id)
    {
        $row = MarketingEvent::query()->findOrFail($id);
        $row->delete();
        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Etkinlik silindi.');
    }

    public function publish(Request $request, string $id)
    {
        $row = MarketingEvent::query()->findOrFail($id);
        $row->update(['status' => 'published']);
        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'status' => 'published'], 'Etkinlik publish edildi.');
    }

    public function cancel(Request $request, string $id)
    {
        $row = MarketingEvent::query()->findOrFail($id);
        $row->update(['status' => 'cancelled']);
        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'status' => 'cancelled'], 'Etkinlik iptal edildi.');
    }

    public function report(string $id)
    {
        $row = MarketingEvent::query()->findOrFail($id);
        $regs = EventRegistration::query()->where('event_id', $row->id);
        $total = (int) $regs->count();
        $attended = (int) (clone $regs)->where('status', 'attended')->count();
        $cancelled = (int) (clone $regs)->where('status', 'cancelled')->count();
        $waitlist = (int) (clone $regs)->where('status', 'waitlist')->count();
        $noShow = (int) (clone $regs)->where('status', 'no_show')->count();
        $attendanceRate = $total > 0 ? round(($attended / $total) * 100, 2) : 0;
        $surveyCount = (int) (clone $regs)->where('survey_completed', true)->count();
        $surveyScore = (float) ((clone $regs)->whereNotNull('survey_score')->avg('survey_score') ?: 0);

        return view('marketing-admin.events.report', [
            'pageTitle' => 'Etkinlik Raporu',
            'title' => 'Etkinlik #'.$id.' raporu',
            'event' => $row,
            'summary' => [
                'total' => $total,
                'attended' => $attended,
                'cancelled' => $cancelled,
                'waitlist' => $waitlist,
                'no_show' => $noShow,
                'attendance_rate' => $attendanceRate,
                'survey_count' => $surveyCount,
                'survey_score' => $surveyScore,
            ],
            'statusRows' => EventRegistration::query()
                ->where('event_id', $row->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get(),
        ]);
    }

    public function sendReminder(Request $request, string $id)
    {
        $row = MarketingEvent::query()->findOrFail($id);
        $regs = EventRegistration::query()
            ->where('event_id', $row->id)
            ->whereIn('status', ['registered', 'confirmed', 'waitlist'])
            ->get();

        $queued = 0;
        foreach ($regs as $reg) {
            if (trim((string) $reg->email) === '') {
                continue;
            }
            $this->notificationService->send([
                'channel'         => 'email',
                'category'        => 'event_reminder',
                'student_id'      => $reg->mentorde_id,
                'recipient_email' => $reg->email,
                'recipient_phone' => $reg->phone,
                'recipient_name'  => trim((string) $reg->first_name.' '.$reg->last_name),
                'subject'         => 'Etkinlik Hatirlatma: '.$row->title_tr,
                'body'            => 'Etkinlik tarihi yaklasti. Baslangic: '.$row->start_date,
                'variables'       => [
                    'event_id'    => (int) $row->id,
                    'event_title' => $row->title_tr,
                    'start_date'  => (string) $row->start_date,
                ],
                'source_type'  => 'marketing_event',
                'source_id'    => (string) $row->id,
                'triggered_by' => (string) ($request->user()->email ?? 'system'),
            ]);
            $queued++;
        }

        return $this->responseFor(
            $request,
            ['ok' => true, 'id' => $id, 'reminder' => 'queued', 'count' => $queued],
            "{$queued} katilimci icin reminder kuyruga alindi."
        );
    }

    public function surveyResults(string $id)
    {
        $row = MarketingEvent::query()->findOrFail($id);
        $rows = EventRegistration::query()
            ->where('event_id', $row->id)
            ->where(function ($w): void {
                $w->where('survey_completed', true)->orWhereNotNull('survey_score');
            })
            ->orderByDesc('registered_at')
            ->paginate(30);

        return view('marketing-admin.events.survey-results', [
            'pageTitle' => 'Anket Sonuclari',
            'title' => 'Etkinlik #'.$id.' anketi',
            'event' => $row,
            'rows' => $rows,
            'avgScore' => (float) (EventRegistration::query()
                ->where('event_id', $row->id)
                ->whereNotNull('survey_score')
                ->avg('survey_score') ?: 0),
            'surveyCount' => (int) EventRegistration::query()
                ->where('event_id', $row->id)
                ->where('survey_completed', true)
                ->count(),
        ]);
    }

    private function validatePayload(Request $request, bool $isCreate): array
    {
        $rules = [
            'title_tr' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'title_de' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_tr' => [$isCreate ? 'required' : 'sometimes', 'string'],
            'description_de' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'start_date' => [$isCreate ? 'required' : 'sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'type' => [$isCreate ? 'required' : 'sometimes', Rule::in($this->typeOptions())],
            'format' => [$isCreate ? 'required' : 'sometimes', Rule::in(['online', 'offline', 'hybrid'])],
            'online_platform' => ['nullable', 'string', 'max:80'],
            'online_meeting_url' => ['nullable', 'string', 'max:500'],
            'online_meeting_id' => ['nullable', 'string', 'max:190'],
            'online_meeting_password' => ['nullable', 'string', 'max:190'],
            'online_recording_url' => ['nullable', 'string', 'max:500'],
            'venue_name' => ['nullable', 'string', 'max:190'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'venue_city' => ['nullable', 'string', 'max:120'],
            'venue_country' => ['nullable', 'string', 'max:120'],
            'venue_map_url' => ['nullable', 'string', 'max:500'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'waitlist_enabled' => ['nullable'],
            'target_audience' => ['nullable', 'string', 'max:120'],
            'target_student_types' => ['nullable', 'string'],
            'cover_image_url' => ['nullable', 'string', 'max:500'],
            'gallery_urls' => ['nullable', 'string'],
            'linked_campaign_id' => ['nullable', 'integer', 'exists:marketing_campaigns,id'],
            'reminders_json' => ['nullable', 'string'],
            'post_event_survey_enabled' => ['nullable'],
            'post_event_survey_url' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in($this->statusOptions())],
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

    private function normalizeJsonArray(mixed $raw, array $default = []): array
    {
        $txt = trim((string) $raw);
        if ($txt === '') {
            return $default;
        }
        $decoded = json_decode($txt, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw ValidationException::withMessages([
                'reminders_json' => 'reminders_json gecerli JSON dizi olmalidir.',
            ]);
        }
        return $decoded;
    }

    private function refreshEventMetrics(MarketingEvent $event): void
    {
        $total = (int) EventRegistration::query()->where('event_id', $event->id)->count();
        $attended = (int) EventRegistration::query()->where('event_id', $event->id)->where('status', 'attended')->count();
        $surveyScore = (float) (EventRegistration::query()
            ->where('event_id', $event->id)
            ->whereNotNull('survey_score')
            ->avg('survey_score') ?: 0);
        $rate = $total > 0 ? round(($attended / $total) * 100, 2) : 0.0;

        $event->forceFill([
            'current_registrations' => $total,
            'metric_total_registrations' => $total,
            'metric_total_attendees' => $attended,
            'metric_attendance_rate' => $rate,
            'metric_satisfaction_score' => $surveyScore > 0 ? $surveyScore : null,
        ])->save();
    }

    private function summaryStats(): array
    {
        return [
            'total' => MarketingEvent::query()->count(),
            'published' => MarketingEvent::query()->where('status', 'published')->count(),
            'draft' => MarketingEvent::query()->where('status', 'draft')->count(),
            'upcoming' => MarketingEvent::query()->where('start_date', '>=', now())->count(),
        ];
    }

    private function typeOptions(): array
    {
        return ['webinar', 'seminar', 'workshop', 'fair', 'live'];
    }

    private function statusOptions(): array
    {
        return ['draft', 'published', 'cancelled', 'completed'];
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }
        return redirect('/mktg-admin/events')->with('status', $statusMessage);
    }
}

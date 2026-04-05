<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailCampaignJob;
use App\Models\Marketing\EmailCampaign;
use App\Models\Marketing\EmailSegment;
use App\Models\Marketing\EmailSendLog;
use App\Models\Marketing\EmailTemplate;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class EmailCampaignController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', 'all'),
            'template_id' => (int) $request->query('template_id', 0),
        ];

        $query = EmailCampaign::query()
            ->with(['template:id,name'])
            ->orderByDesc('id');

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('zoho_campaign_id', 'like', "%{$q}%");
            });
        }
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if ($filters['template_id'] > 0) {
            $query->where('template_id', $filters['template_id']);
        }

        $rows = $query->paginate(15)->withQueryString();
        $editing = null;
        $editId = (int) $request->query('edit_id', 0);
        if ($editId > 0) {
            $editing = EmailCampaign::query()->find($editId);
        }

        return view('marketing-admin.email.campaigns.index', [
            'pageTitle' => 'E-posta Kampanyalari',
            'title' => 'Kampanya Listesi',
            'rows' => $rows,
            'filters' => $filters,
            'editing' => $editing,
            'stats' => $this->summaryStats(),
            'templates' => EmailTemplate::query()->orderBy('name')->get(['id', 'name']),
            'segments' => EmailSegment::query()->orderBy('name')->get(['id', 'name', 'estimated_size', 'is_active']),
            'marketingCampaigns' => MarketingCampaign::query()->orderByDesc('id')->limit(100)->get(['id', 'name', 'status']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create()
    {
        return redirect('/mktg-admin/email/campaigns');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, true);
        $segmentIds = (array) ($data['segment_ids'] ?? []);
        $snapshot = $this->buildRecipientSnapshot($segmentIds);

        $row = EmailCampaign::query()->create([
            'name' => $data['name'],
            'template_id' => (int) $data['template_id'],
            'segment_ids' => $segmentIds,
            'linked_marketing_campaign_id' => Arr::get($data, 'linked_marketing_campaign_id'),
            'scheduled_at' => Arr::get($data, 'scheduled_at'),
            'sent_at' => null,
            'status' => Arr::get($data, 'status', 'draft'),
            'total_recipients' => count($snapshot),
            'recipient_snapshot' => $snapshot,
            'zoho_campaign_id' => Arr::get($data, 'zoho_campaign_id'),
            'created_by' => (int) $request->user()->id,
        ]);

        $row->forceFill([
            'stat_sent' => 0,
            'stat_delivered' => 0,
            'stat_opened' => 0,
            'stat_open_rate' => 0,
            'stat_clicked' => 0,
            'stat_click_rate' => 0,
            'stat_bounced' => 0,
            'stat_unsubscribed' => 0,
            'stat_guest_registrations' => 0,
        ])->save();

        return $this->responseFor($request, ['ok' => true, 'id' => $row->id], 'E-posta kampanyasi olusturuldu.', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        return redirect('/mktg-admin/email/campaigns?edit_id='.$id);
    }

    public function edit(string $id)
    {
        return redirect('/mktg-admin/email/campaigns?edit_id='.$id);
    }

    public function update(Request $request, string $id)
    {
        $row = EmailCampaign::query()->findOrFail($id);
        $data = $this->validatePayload($request, false);

        $segmentIds = array_key_exists('segment_ids', $data)
            ? (array) $data['segment_ids']
            : (array) ($row->segment_ids ?? []);
        $snapshot = $this->buildRecipientSnapshot($segmentIds);

        $payload = [
            'name' => Arr::get($data, 'name', $row->name),
            'template_id' => (int) Arr::get($data, 'template_id', $row->template_id),
            'segment_ids' => $segmentIds,
            'linked_marketing_campaign_id' => Arr::get($data, 'linked_marketing_campaign_id', $row->linked_marketing_campaign_id),
            'scheduled_at' => Arr::get($data, 'scheduled_at', $row->scheduled_at),
            'status' => Arr::get($data, 'status', $row->status),
            'total_recipients' => count($snapshot),
            'recipient_snapshot' => $snapshot,
            'zoho_campaign_id' => Arr::get($data, 'zoho_campaign_id', $row->zoho_campaign_id),
        ];

        $row->update($payload);

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Kampanya guncellendi.');
    }

    public function destroy(Request $request, string $id)
    {
        $row = EmailCampaign::query()->findOrFail($id);
        $row->delete();
        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Kampanya silindi.');
    }

    public function send(Request $request, string $id)
    {
        $row = EmailCampaign::query()->findOrFail($id);

        if (in_array((string) $row->status, ['sent', 'sending'], true)) {
            return $this->responseFor(
                $request,
                ['ok' => false, 'id' => $id, 'status' => $row->status],
                'Kampanya zaten gönderilmiş veya gönderiliyor.'
            );
        }

        // Alıcı listesi yoksa şimdi oluştur
        $snapshot = (array) ($row->recipient_snapshot ?? []);
        if ($snapshot === []) {
            $snapshot = $this->buildRecipientSnapshot((array) ($row->segment_ids ?? []));
            $row->update([
                'recipient_snapshot' => $snapshot,
                'total_recipients' => count($snapshot),
            ]);
        }

        SendEmailCampaignJob::dispatch((int) $row->id)->onQueue('emails');

        return $this->responseFor(
            $request,
            ['ok' => true, 'id' => $id, 'status' => 'sending', 'recipients' => count($snapshot)],
            count($snapshot).' alıcıya gönderim kuyruğa alındı.'
        );
    }

    public function schedule(Request $request, string $id)
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);
        $row = EmailCampaign::query()->findOrFail($id);
        $row->update([
            'status' => 'scheduled',
            'scheduled_at' => $data['scheduled_at'],
        ]);

        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'status' => 'scheduled'], 'Kampanya planlandi.');
    }

    public function stats(string $id)
    {
        $row = EmailCampaign::query()->with('template')->findOrFail($id);
        $logs = EmailSendLog::query()
            ->where('email_campaign_id', $row->id)
            ->orderByDesc('sent_at')
            ->limit(80)
            ->get();

        $sentCount = (int) $logs->where('status', 'sent')->count();
        $openedCount = (int) $logs->whereNotNull('opened_at')->count();
        $clickedCount = (int) $logs->whereNotNull('clicked_at')->count();
        $openRate = $sentCount > 0 ? round(($openedCount / $sentCount) * 100, 2) : 0;
        $clickRate = $sentCount > 0 ? round(($clickedCount / $sentCount) * 100, 2) : 0;

        return view('marketing-admin.email.campaigns.stats', [
            'pageTitle' => 'E-posta Kampanya Istatistikleri',
            'title' => 'Kampanya #'.$id.' stats',
            'campaign' => $row,
            'logs' => $logs,
            'kpi' => [
                'sent' => $sentCount,
                'opened' => $openedCount,
                'clicked' => $clickedCount,
                'open_rate' => $openRate,
                'click_rate' => $clickRate,
            ],
        ]);
    }

    public function sendLog(Request $request)
    {
        $filters = [
            'campaign_id' => (int) $request->query('campaign_id', 0),
            'status' => (string) $request->query('status', 'all'),
            'q' => trim((string) $request->query('q', '')),
        ];

        $query = EmailSendLog::query()
            ->with([
                'campaign:id,name',
                'template:id,name',
            ])
            ->orderByDesc('sent_at');

        if ($filters['campaign_id'] > 0) {
            $query->where('email_campaign_id', $filters['campaign_id']);
        }
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('recipient_email', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");
            });
        }

        $rows = $query->paginate(20)->withQueryString();

        return view('marketing-admin.email.campaigns.send-log', [
            'pageTitle' => 'E-posta Send Log',
            'title' => 'Gonderim Kayitlari',
            'rows' => $rows,
            'filters' => $filters,
            'campaignOptions' => EmailCampaign::query()->orderByDesc('id')->get(['id', 'name']),
        ]);
    }

    private function validatePayload(Request $request, bool $isCreate): array
    {
        $rules = [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'template_id' => [$isCreate ? 'required' : 'sometimes', 'integer', 'exists:email_templates,id'],
            'segment_ids' => ['nullable', 'array'],
            'segment_ids.*' => ['integer', 'exists:email_segments,id'],
            'linked_marketing_campaign_id' => ['nullable', 'integer', 'exists:marketing_campaigns,id'],
            'status' => ['nullable', Rule::in($this->statusOptions())],
            'scheduled_at' => ['nullable', 'date'],
            'zoho_campaign_id' => ['nullable', 'string', 'max:190'],
        ];

        $data = $request->validate($rules);
        if (array_key_exists('segment_ids', $data)) {
            $data['segment_ids'] = collect((array) $data['segment_ids'])
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->unique()
                ->values()
                ->all();
        }

        return $data;
    }

    private function buildRecipientSnapshot(array $segmentIds): array
    {
        if ($segmentIds === []) {
            return [];
        }

        $userIds = EmailSegment::query()
            ->whereIn('id', $segmentIds)
            ->get(['member_user_ids'])
            ->flatMap(function ($seg) {
                $ids = is_array($seg->member_user_ids) ? $seg->member_user_ids : [];
                return collect($ids)->map(fn ($v) => (int) $v);
            })
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => [
                'user_id' => (int) $u->id,
                'name' => (string) $u->name,
                'email' => (string) $u->email,
            ])
            ->values()
            ->all();
    }

    private function summaryStats(): array
    {
        return [
            'total' => EmailCampaign::query()->count(),
            'draft' => EmailCampaign::query()->where('status', 'draft')->count(),
            'scheduled' => EmailCampaign::query()->where('status', 'scheduled')->count(),
            'sent' => EmailCampaign::query()->where('status', 'sent')->count(),
        ];
    }

    private function statusOptions(): array
    {
        return ['draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled'];
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }
        return redirect('/mktg-admin/email/campaigns')->with('status', $statusMessage);
    }
}

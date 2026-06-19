<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PlatformBroadcast;
use App\Services\Platform\BroadcastService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Platform Owner — Broadcast / Duyuru yonetimi.
 *
 *   index()   : liste + KPI + filtre (status, channel)
 *   create()  : composer form
 *   store()   : draft veya scheduled kaydet (immediate=1 ile direkt gonderim)
 *   show()    : detay + recipient table + per-channel stats
 *   send()    : "Simdi gonder" — service.send(immediate)
 *   cancel()  : scheduled iptal
 *   preview() : Markdown render JSON
 */
class BroadcastController extends Controller
{
    public function __construct(private readonly BroadcastService $broadcasts) {}

    // ────────────────────────────────────────────────────────────────────────
    // INDEX
    // ────────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $statusFilter  = trim((string) $request->query('status', ''));
        $channelFilter = trim((string) $request->query('channel', ''));

        $query = PlatformBroadcast::query();

        $validStatuses = [
            PlatformBroadcast::STATUS_DRAFT,
            PlatformBroadcast::STATUS_SCHEDULED,
            PlatformBroadcast::STATUS_SENDING,
            PlatformBroadcast::STATUS_SENT,
            PlatformBroadcast::STATUS_CANCELLED,
        ];
        if ($statusFilter !== '' && in_array($statusFilter, $validStatuses, true)) {
            $query->where('status', $statusFilter);
        }

        $validChannels = [
            PlatformBroadcast::CHANNEL_EMAIL,
            PlatformBroadcast::CHANNEL_IN_APP,
            PlatformBroadcast::CHANNEL_BOTH,
        ];
        if ($channelFilter !== '' && in_array($channelFilter, $validChannels, true)) {
            $query->where('channel', $channelFilter);
        }

        $broadcasts = $query->orderByDesc('id')->paginate(20)->withQueryString();

        // KPI'lar
        $totalSent = (int) PlatformBroadcast::query()
            ->where('status', PlatformBroadcast::STATUS_SENT)
            ->sum('sent_count');

        $sentBroadcasts = PlatformBroadcast::query()
            ->where('status', PlatformBroadcast::STATUS_SENT)
            ->where('sent_count', '>', 0)
            ->get(['sent_count', 'opened_count', 'clicked_count']);

        $avgOpenRate  = 0.0;
        $avgClickRate = 0.0;
        if ($sentBroadcasts->isNotEmpty()) {
            $rates = $sentBroadcasts->map(fn ($b) => [
                'open'  => $b->sent_count > 0 ? ($b->opened_count  / $b->sent_count) * 100 : 0,
                'click' => $b->sent_count > 0 ? ($b->clicked_count / $b->sent_count) * 100 : 0,
            ]);
            $avgOpenRate  = round($rates->avg('open'),  1);
            $avgClickRate = round($rates->avg('click'), 1);
        }

        $monthStart = CarbonImmutable::now()->startOfMonth();
        $thisMonthSent = (int) PlatformBroadcast::query()
            ->where('status', PlatformBroadcast::STATUS_SENT)
            ->whereDate('sent_at', '>=', $monthStart->toDateString())
            ->count();

        return view('platform.broadcasts.index', [
            'broadcasts'    => $broadcasts,
            'filters'       => ['status' => $statusFilter, 'channel' => $channelFilter],
            'totalSent'     => $totalSent,
            'avgOpenRate'   => $avgOpenRate,
            'avgClickRate'  => $avgClickRate,
            'thisMonthSent' => $thisMonthSent,
            'statuses'      => $validStatuses,
            'channels'      => $validChannels,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // CREATE
    // ────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'subscription_tier']);

        return view('platform.broadcasts.create', [
            'companies' => $companies,
            'tiers'     => Company::TIERS,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // STORE
    // ────────────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'title'              => ['required', 'string', 'max:200'],
            'body'               => ['required', 'string'],
            'channel'            => ['required', 'in:email,in_app,both'],
            'target_segment'     => ['required', 'in:all,trial,paid,specific'],
            'target_tiers'       => ['nullable', 'array'],
            'target_tiers.*'     => ['string', 'in:trial,basic,gold,premium'],
            'target_company_ids' => ['nullable', 'array'],
            'target_company_ids.*' => ['integer', 'exists:companies,id'],
            'scheduled_for'      => ['nullable', 'date'],
            'cta_label'          => ['nullable', 'string', 'max:100'],
            'cta_url'            => ['nullable', 'url', 'max:500'],
            'action'             => ['nullable', 'in:draft,schedule,send_now'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $action = $data['action'] ?? 'draft';

        // Specific seçilmediyse company id'leri at
        if ($data['target_segment'] !== PlatformBroadcast::SEGMENT_SPECIFIC) {
            $data['target_company_ids'] = null;
        }

        // "Send now" senaryosunda scheduled_for'u temizle
        if ($action === 'send_now') {
            $data['scheduled_for'] = null;
        }

        $data['created_by_user_id'] = auth()->id();
        $broadcast = $this->broadcasts->compose($data);

        if ($action === 'send_now') {
            $count = $this->broadcasts->send($broadcast);
            return redirect()
                ->route('platform.broadcasts.show', $broadcast->id)
                ->with('success', "Duyuru gonderildi: {$count} alici.");
        }

        if ($action === 'schedule' && $broadcast->scheduled_for) {
            return redirect()
                ->route('platform.broadcasts.show', $broadcast->id)
                ->with('success', 'Duyuru zamanlandi: ' . $broadcast->scheduled_for->format('d.m.Y H:i'));
        }

        return redirect()
            ->route('platform.broadcasts.show', $broadcast->id)
            ->with('success', 'Taslak kaydedildi.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // SHOW
    // ────────────────────────────────────────────────────────────────────────

    public function show(int $id): View
    {
        $broadcast = PlatformBroadcast::with('createdBy')->findOrFail($id);

        // Tenant izolasyonu: alici bireysel kimligi (isim/email) Platform Owner'a
        // gosterilmez — sadece sirket + teslim durumu. Kisisel veri sizmaz.
        $recipients = $broadcast->recipients()
            ->with(['company:id,name'])
            ->orderByDesc('id')
            ->paginate(30);

        // Stats
        $stats = [
            'total'   => $broadcast->recipients()->count(),
            'sent'    => $broadcast->recipients()->whereIn('status', ['sent', 'opened', 'clicked'])->count(),
            'opened'  => $broadcast->recipients()->whereNotNull('opened_at')->count(),
            'clicked' => $broadcast->recipients()->whereNotNull('clicked_at')->count(),
            'failed'  => $broadcast->recipients()->where('status', 'failed')->count(),
        ];

        return view('platform.broadcasts.show', [
            'broadcast'  => $broadcast,
            'recipients' => $recipients,
            'stats'      => $stats,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SEND (manuel "Simdi gonder")
    // ────────────────────────────────────────────────────────────────────────

    public function send(int $id): RedirectResponse
    {
        $broadcast = PlatformBroadcast::findOrFail($id);

        if (in_array($broadcast->status, [PlatformBroadcast::STATUS_SENT, PlatformBroadcast::STATUS_CANCELLED], true)) {
            return back()->with('error', 'Bu duyuru zaten ' . $broadcast->statusLabel() . ' statusunde.');
        }

        $count = $this->broadcasts->send($broadcast);
        return back()->with('success', "Duyuru gonderildi: {$count} alici.");
    }

    // ────────────────────────────────────────────────────────────────────────
    // CANCEL
    // ────────────────────────────────────────────────────────────────────────

    public function cancel(int $id): RedirectResponse
    {
        $ok = $this->broadcasts->cancel($id);
        if (!$ok) {
            return back()->with('error', 'Duyuru iptal edilemedi.');
        }
        return back()->with('success', 'Duyuru iptal edildi.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // PREVIEW — Markdown render JSON (composer'da live preview icin)
    // ────────────────────────────────────────────────────────────────────────

    public function preview(int $id): JsonResponse
    {
        $b = PlatformBroadcast::findOrFail($id);

        $body = $b->body;
        $html = '';

        if (class_exists(\League\CommonMark\CommonMarkConverter::class)) {
            try {
                $conv = new \League\CommonMark\CommonMarkConverter([
                    'html_input'         => 'escape',
                    'allow_unsafe_links' => false,
                ]);
                $html = (string) $conv->convert($body);
            } catch (\Throwable $e) {
                $html = '<pre>' . e($body) . '</pre>';
            }
        } else {
            $html = '<pre>' . e($body) . '</pre>';
        }

        return response()->json([
            'title' => $b->title,
            'html'  => $html,
            'cta'   => $b->cta_label ? ['label' => $b->cta_label, 'url' => $b->cta_url] : null,
        ]);
    }
}

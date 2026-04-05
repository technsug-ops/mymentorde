<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\DealerRevenueMilestone;
use App\Models\DealerStudentRevenue;
use App\Models\DealerType;
use App\Models\LeadSourceDatum;
use App\Models\MarketingTrackingLink;
use App\Models\StudentAssignment;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class DealerRelationsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', 'all'),
            'active' => (string) $request->query('active', 'all'),
        ];

        $query = Dealer::query()->orderByDesc('id');
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }
        if ($filters['type'] !== 'all') {
            $query->where('dealer_type_code', $filters['type']);
        }
        if ($filters['active'] === 'active') {
            $query->where('is_active', true);
        } elseif ($filters['active'] === 'inactive') {
            $query->where('is_active', false);
        }

        $rows = $query->paginate(15)->withQueryString();
        $codes = $rows->pluck('code')->filter()->values()->all();

        $assignmentMap = StudentAssignment::query()
            ->selectRaw('dealer_id, count(*) as total, sum(case when is_archived = 0 then 1 else 0 end) as active_total')
            ->whereIn('dealer_id', $codes)
            ->groupBy('dealer_id')
            ->get()
            ->keyBy('dealer_id');

        $revenueMap = DealerStudentRevenue::query()
            ->selectRaw('dealer_id, sum(total_earned) as earned, sum(total_pending) as pending')
            ->whereIn('dealer_id', $codes)
            ->groupBy('dealer_id')
            ->get()
            ->keyBy('dealer_id');

        $conversionMap = LeadSourceDatum::query()
            ->selectRaw('dealer_id, count(*) as converted_total')
            ->whereIn('dealer_id', $codes)
            ->where('funnel_converted', true)
            ->groupBy('dealer_id')
            ->get()
            ->keyBy('dealer_id');

        $clickMap = MarketingTrackingLink::query()
            ->selectRaw('dealer_code, sum(click_count) as clicks')
            ->whereIn('dealer_code', $codes)
            ->groupBy('dealer_code')
            ->get()
            ->keyBy('dealer_code');

        $typeNames = DealerType::query()
            ->pluck('name_tr', 'code');

        $statusRows = $rows->getCollection()->map(function (Dealer $dealer) use ($assignmentMap, $revenueMap, $conversionMap, $clickMap, $typeNames): array {
            $a = $assignmentMap->get($dealer->code);
            $r = $revenueMap->get($dealer->code);
            $c = $conversionMap->get($dealer->code);
            $clk = $clickMap->get($dealer->code);

            return [
                'id' => $dealer->id,
                'code' => $dealer->code,
                'name' => $dealer->name,
                'type_code' => $dealer->dealer_type_code,
                'type_name' => $dealer->dealer_type_code ? ($typeNames[$dealer->dealer_type_code] ?? $dealer->dealer_type_code) : '-',
                'is_active' => (bool) $dealer->is_active,
                'is_archived' => (bool) $dealer->is_archived,
                'students_total' => (int) ($a->total ?? 0),
                'students_active' => (int) ($a->active_total ?? 0),
                'total_earned' => (float) ($r->earned ?? 0),
                'total_pending' => (float) ($r->pending ?? 0),
                'converted_total' => (int) ($c->converted_total ?? 0),
                'tracking_clicks' => (int) ($clk->clicks ?? 0),
            ];
        })->all();

        return view('marketing-admin.dealers.index', [
            'pageTitle' => 'Bayi Iliskileri',
            'title' => 'Bayi Performans Tablosu',
            'rows' => $rows,
            'statusRows' => $statusRows,
            'filters' => $filters,
            'typeOptions' => DealerType::query()->orderBy('sort_order')->orderBy('id')->get(['code', 'name_tr']),
            'stats' => (function (): array {
                $d = Dealer::query()
                    ->selectRaw("COUNT(*) as total, SUM(CASE WHEN is_active=1 AND is_archived=0 THEN 1 ELSE 0 END) as active")
                    ->first();
                $r = DealerStudentRevenue::query()
                    ->selectRaw('COALESCE(SUM(total_earned),0) as earned, COALESCE(SUM(total_pending),0) as pending')
                    ->first();
                return [
                    'total'           => (int) ($d->total ?? 0),
                    'active'          => (int) ($d->active ?? 0),
                    'students'        => (int) StudentAssignment::query()->whereNotNull('dealer_id')->where('dealer_id', '!=', '')->count(),
                    'revenue_earned'  => (float) ($r->earned ?? 0),
                    'revenue_pending' => (float) ($r->pending ?? 0),
                ];
            })(),
            'dealerSuggestions' => Dealer::query()->orderBy('code')->limit(200)->pluck('code'),
        ]);
    }

    public function performance(string $id)
    {
        $dealer = Dealer::query()
            ->where('code', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        $assignments = StudentAssignment::query()
            ->where('dealer_id', $dealer->code)
            ->orderByDesc('updated_at')
            ->paginate(20);

        $revenues = DealerStudentRevenue::query()
            ->where('dealer_id', $dealer->code)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $linkRows = MarketingTrackingLink::query()
            ->where('dealer_code', $dealer->code)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'code', 'title', 'status', 'click_count', 'last_clicked_at']);

        $leadRows = LeadSourceDatum::query()
            ->where('dealer_id', $dealer->code)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'guest_id', 'utm_source', 'utm_medium', 'utm_campaign', 'funnel_converted', 'created_at']);

        $totalStudents  = (int) StudentAssignment::query()->where('dealer_id', $dealer->code)->count();
        $activeStudents = (int) StudentAssignment::query()->where('dealer_id', $dealer->code)->where('is_archived', false)->count();
        $totalEarned    = (float) DealerStudentRevenue::query()->where('dealer_id', $dealer->code)->sum('total_earned');
        $totalPending   = (float) DealerStudentRevenue::query()->where('dealer_id', $dealer->code)->sum('total_pending');
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEarned = (float) DealerStudentRevenue::query()
            ->where('dealer_id', $dealer->code)
            ->where('updated_at', '>=', $thisMonthStart)
            ->sum('total_earned');
        $convertedTotal = (int) LeadSourceDatum::query()->where('dealer_id', $dealer->code)->where('funnel_converted', true)->count();
        $leadTotal      = (int) LeadSourceDatum::query()->where('dealer_id', $dealer->code)->count();
        $convRate       = $leadTotal > 0 ? round($convertedTotal / $leadTotal * 100, 1) : 0.0;

        // Active milestones for this dealer's type
        $milestones = DealerRevenueMilestone::query()
            ->where('is_active', true)
            ->when($dealer->dealer_type_code, function ($q) use ($dealer): void {
                $q->where(function ($w) use ($dealer): void {
                    $w->whereNull('applicable_dealer_types')
                      ->orWhereJsonContains('applicable_dealer_types', $dealer->dealer_type_code);
                });
            })
            ->orderBy('sort_order')
            ->get(['id', 'name_tr', 'trigger_type', 'percentage', 'fixed_amount', 'fixed_currency']);

        // Milestone progress: paid out vs total
        $paidTotal   = $totalEarned;
        $grandTotal  = $totalEarned + $totalPending;
        $paidPct     = $grandTotal > 0 ? min(100, round($paidTotal / $grandTotal * 100)) : 0;

        return view('marketing-admin.dealers.performance', [
            'pageTitle' => 'Bayi Performansi',
            'title' => 'Bayi '.$dealer->code.' performansi',
            'dealer' => $dealer,
            'assignments' => $assignments,
            'revenues' => $revenues,
            'links' => $linkRows,
            'leads' => $leadRows,
            'milestones' => $milestones,
            'summary' => [
                'students_total'    => $totalStudents,
                'students_active'   => $activeStudents,
                'revenue_earned'    => $totalEarned,
                'revenue_pending'   => $totalPending,
                'revenue_this_month' => $thisMonthEarned,
                'tracking_clicks'   => (int) MarketingTrackingLink::query()->where('dealer_code', $dealer->code)->sum('click_count'),
                'converted_total'   => $convertedTotal,
                'conversion_rate'   => $convRate,
                'paid_pct'          => $paidPct,
                'grand_total'       => $grandTotal,
            ],
        ]);
    }

    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'dealer_codes' => ['required', 'string', 'max:1000'],
            'channel' => ['nullable', Rule::in(['email', 'whatsapp', 'inApp'])],
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
            'triggered_by' => ['nullable', 'string', 'max:190'],
        ]);

        $codes = collect(explode(',', (string) $data['dealer_codes']))
            ->map(fn ($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->unique()
            ->values();

        $dealerMap = Dealer::query()
            ->whereIn('code', $codes->all())
            ->get(['code', 'email', 'phone', 'whatsapp'])
            ->keyBy('code');

        if ($dealerMap->isEmpty()) {
            if ($request->expectsJson()) {
                return ApiResponse::error(ApiResponse::ERR_DEALER_NO_CODES, 'Gecerli dealer code bulunamadi.');
            }
            return redirect('/mktg-admin/dealers')->with('status', 'Gecerli dealer code bulunamadi. (ERR_DEALER_422_NO_CODES)');
        }

        $channel  = (string) ($data['channel'] ?? 'inApp');
        $queued   = 0;
        foreach ($dealerMap as $code => $dealer) {
            $recipientEmail = $channel === 'email' ? ($dealer->email ?: null) : null;
            $recipientPhone = in_array($channel, ['whatsapp', 'sms'], true)
                ? ($dealer->whatsapp ?: $dealer->phone ?: null)
                : null;

            $this->notificationService->send([
                'channel'         => $channel,
                'category'        => 'dealer_broadcast',
                'recipient_email' => $recipientEmail,
                'recipient_phone' => $recipientPhone,
                'recipient_name'  => 'dealer:'.$code,
                'subject'         => $data['subject'],
                'body'            => $data['message'],
                'variables'       => ['dealer_code' => $code],
                'source_type'     => 'marketing_dealer',
                'source_id'       => $code,
                'triggered_by'    => (string) ($data['triggered_by'] ?? ($request->user()->email ?? 'system')),
            ]);
            $queued++;
        }

        return $this->responseFor(
            $request,
            ['ok' => true, 'queued' => $queued],
            "{$queued} bayi icin duyuru kuyruga alindi.",
            Response::HTTP_CREATED
        );
    }

    public function shareMaterial(Request $request)
    {
        $data = $request->validate([
            'dealer_codes' => ['required', 'string', 'max:1000'],
            'material_title' => ['required', 'string', 'max:190'],
            'material_url' => ['required', 'string', 'max:500'],
            'material_type' => ['nullable', Rule::in(['pdf', 'image', 'video', 'doc', 'link'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $codes = collect(explode(',', (string) $data['dealer_codes']))
            ->map(fn ($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->unique()
            ->values();
        $dealerMap = Dealer::query()->whereIn('code', $codes->all())->get(['code', 'email'])->keyBy('code');
        if ($dealerMap->isEmpty()) {
            if ($request->expectsJson()) {
                return ApiResponse::error(ApiResponse::ERR_DEALER_NO_CODES, 'Materyal paylasimi icin gecerli dealer code bulunamadi.');
            }
            return redirect('/mktg-admin/dealers')->with('status', 'Gecerli dealer code bulunamadi. (ERR_DEALER_422_NO_CODES)');
        }

        $queued = 0;
        foreach ($dealerMap as $code => $dealer) {
            try {
                $this->notificationService->send([
                    'channel'          => 'email',
                    'category'         => 'dealer_material',
                    'recipient_email'  => $dealer->email ?: null,
                    'recipient_name'   => 'dealer:'.$code,
                    'subject'          => $data['material_title'],
                    'body'             => trim(($data['note'] ?? '').' '.$data['material_url']),
                    'variables'        => [
                        'dealer_code'   => $code,
                        'material_type' => (string) ($data['material_type'] ?? 'link'),
                        'material_url'  => $data['material_url'],
                    ],
                    'source_type'  => 'marketing_material',
                    'source_id'    => $code,
                    'triggered_by' => (string) ($request->user()->email ?? 'system'),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
            $queued++;
        }

        return $this->responseFor(
            $request,
            ['ok' => true, 'queued' => $queued],
            "{$queued} bayi icin materyal paylasimi kuyruklandi.",
            Response::HTTP_CREATED
        );
    }

    public function broadcastOne(Request $request, string $code)
    {
        $dealer = Dealer::query()->where('code', strtoupper(trim($code)))->firstOrFail();

        $data = $request->validate([
            'channel' => ['nullable', Rule::in(['email', 'whatsapp', 'inApp'])],
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $channel = (string) ($data['channel'] ?? 'inApp');

        // Resolve real recipient contact from dealer record
        $recipientEmail = $channel === 'email' ? ($dealer->email ?: null) : null;
        $recipientPhone = in_array($channel, ['whatsapp', 'sms'], true)
            ? ($dealer->whatsapp ?: $dealer->phone ?: null)
            : null;

        $this->notificationService->send([
            'channel'         => $channel,
            'category'        => 'dealer_broadcast',
            'recipient_email' => $recipientEmail,
            'recipient_phone' => $recipientPhone,
            'recipient_name'  => 'dealer:'.$dealer->code,
            'subject'         => $data['subject'],
            'body'            => $data['message'],
            'variables'       => ['dealer_code' => $dealer->code],
            'source_type'     => 'marketing_dealer',
            'source_id'       => $dealer->code,
            'triggered_by'    => $request->user()->email ?? 'system',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'channel' => $channel]);
        }

        return redirect("/mktg-admin/dealers/{$dealer->code}/performance")
            ->with('status', 'Mesaj kuyruklandi.');
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }

        $to = '/mktg-admin/dealers';
        $dealerCode = Arr::get($payload, 'dealer_code');
        if (is_string($dealerCode) && $dealerCode !== '') {
            $to = '/mktg-admin/dealers/'.$dealerCode.'/performance';
        }

        return redirect($to)->with('status', $statusMessage);
    }
}

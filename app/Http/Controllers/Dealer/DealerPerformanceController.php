<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dealer\Concerns\DealerPortalTrait;
use App\Models\Dealer;
use App\Models\DealerMaterialRead;
use App\Models\DealerPayoutRequest;
use App\Models\DealerRevenueMilestone;
use App\Models\DealerStudentRevenue;
use App\Models\DealerUtmLink;
use App\Models\GuestApplication;
use App\Models\KnowledgeBaseArticle;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DealerPerformanceController extends Controller
{
    use DealerPortalTrait;

    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function performanceReport(Request $request)
    {
        $data   = $this->baseData($request);
        $months = max(1, min(12, (int) $request->query('months', 6)));

        $trend = collect(range($months - 1, 0))->map(function (int $ago) use ($data) {
            $m         = now()->subMonths($ago);
            $from      = $m->copy()->startOfMonth();
            $to        = $m->copy()->endOfMonth();
            $leads     = GuestApplication::where('dealer_code', $data['dealerCode'] ?? '')->whereBetween('created_at', [$from, $to])->count();
            $converted = GuestApplication::where('dealer_code', $data['dealerCode'] ?? '')->where('converted_to_student', true)->whereBetween('created_at', [$from, $to])->count();
            $earned    = (float) DealerStudentRevenue::where('dealer_id', $data['dealerCode'] ?? '')->whereBetween('updated_at', [$from, $to])->sum('total_earned');

            return ['month' => $m->format('Y-m'), 'label' => $m->format('M Y'), 'leads' => $leads, 'converted' => $converted,
                    'conv_rate' => $leads > 0 ? round($converted / $leads * 100, 1) : 0.0, 'earned' => $earned];
        });

        return view('dealer.performance-report', $data + compact('trend', 'months'));
    }

    public function performanceExport(Request $request)
    {
        $data   = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing');
        $months = max(1, min(12, (int) $request->query('months', 6)));

        $csv = "ay,lead,donusum,donusum_orani,kazanilan_eur\n";
        for ($ago = $months - 1; $ago >= 0; $ago--) {
            $m         = now()->subMonths($ago);
            $from      = $m->copy()->startOfMonth();
            $to        = $m->copy()->endOfMonth();
            $leads     = GuestApplication::where('dealer_code', $data['dealerCode'])->whereBetween('created_at', [$from, $to])->count();
            $converted = GuestApplication::where('dealer_code', $data['dealerCode'])->where('converted_to_student', true)->whereBetween('created_at', [$from, $to])->count();
            $earned    = (float) DealerStudentRevenue::where('dealer_id', $data['dealerCode'])->whereBetween('updated_at', [$from, $to])->sum('total_earned');
            $csv .= implode(',', [$m->format('Y-m'), $leads, $converted, $leads > 0 ? round($converted / $leads * 100, 1) : 0, number_format($earned, 2, '.', '')])."\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="performans_'.date('Y-m-d').'.csv"',
        ]);
    }

    public function training(Request $request)
    {
        $data = $this->baseData($request);
        $user = $request->user();

        $articles = KnowledgeBaseArticle::query()
            ->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('target_roles')->orWhereJsonContains('target_roles', 'dealer'))
            ->orderBy('category')->orderBy('id')->get();

        $readIds  = DealerMaterialRead::query()->where('dealer_user_id', $user?->id ?? 0)->pluck('article_id')->flip();
        $total    = $articles->count();
        $readCnt  = $readIds->count();

        $trainingProgress = [
            'total'     => $total,
            'read'      => $readCnt,
            'percent'   => $total > 0 ? round($readCnt / $total * 100) : 0,
            'certified' => $total > 0 && $readCnt >= $total,
        ];

        return view('dealer.training', $data + compact('articles', 'readIds', 'trainingProgress'));
    }

    public function markRead(Request $request, KnowledgeBaseArticle $article): JsonResponse
    {
        $user = $request->user();
        abort_if(!$user, 401);

        DealerMaterialRead::query()->updateOrCreate(
            ['dealer_user_id' => $user->id, 'article_id' => $article->id],
            ['read_at' => now()]
        );

        $total   = KnowledgeBaseArticle::query()->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('target_roles')->orWhereJsonContains('target_roles', 'dealer'))->count();
        $readCnt = DealerMaterialRead::query()->where('dealer_user_id', $user->id)->count();

        return response()->json([
            'ok'        => true,
            'read'      => $readCnt,
            'total'     => $total,
            'percent'   => $total > 0 ? round($readCnt / $total * 100) : 0,
            'certified' => $total > 0 && $readCnt >= $total,
        ]);
    }

    public function trainingProgress(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $total   = KnowledgeBaseArticle::query()->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('target_roles')->orWhereJsonContains('target_roles', 'dealer'))->count();
        $readCnt = DealerMaterialRead::query()->where('dealer_user_id', $user->id)->count();

        return response()->json([
            'total'     => $total,
            'read'      => $readCnt,
            'percent'   => $total > 0 ? round($readCnt / $total * 100) : 0,
            'certified' => $total > 0 && $readCnt >= $total,
        ]);
    }

    public function calendar(Request $request)
    {
        return view('dealer.calendar', $this->baseData($request));
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403);

        $start = Carbon::parse($request->query('start', now()->startOfMonth()->toDateString()));
        $end   = Carbon::parse($request->query('end',   now()->endOfMonth()->toDateString()));

        $events = collect();

        GuestApplication::where('dealer_code', $data['dealerCode'])->whereBetween('created_at', [$start, $end])
            ->get(['id', 'first_name', 'last_name', 'created_at'])
            ->each(fn ($g) => $events->push(['title' => '📋 '.$g->first_name.' '.$g->last_name, 'start' => $g->created_at->toDateString(), 'color' => '#3b82f6', 'type' => 'lead_created', 'url' => '/dealer/leads/'.$g->id]));

        GuestApplication::where('dealer_code', $data['dealerCode'])->where('converted_to_student', true)
            ->whereNotNull('converted_at')->whereBetween('converted_at', [$start, $end])
            ->get(['id', 'first_name', 'last_name', 'converted_at'])
            ->each(fn ($g) => $events->push(['title' => '🎓 '.$g->first_name.' '.$g->last_name.' — Dönüşüm', 'start' => $g->converted_at->toDateString(), 'color' => '#22c55e', 'type' => 'conversion']));

        DealerPayoutRequest::where('dealer_code', $data['dealerCode'])->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->get(['id', 'amount', 'paid_at'])
            ->each(fn ($p) => $events->push(['title' => '💰 '.number_format((float) $p->amount, 0).' EUR ödeme', 'start' => $p->paid_at->toDateString(), 'color' => '#f97316', 'type' => 'payment']));

        return response()->json(['events' => $events->values()]);
    }

    public function referralLinks(Request $request)
    {
        $data     = $this->baseData($request);
        $stats    = ['total' => 0, 'form_channel' => 0, 'link_channel' => 0];
        $recent   = collect();
        $utmLinks = collect();
        $utmPerf  = collect();

        if (!empty($data['dealerCode'])) {
            $baseQuery = fn () => GuestApplication::query()->where('dealer_code', $data['dealerCode']);

            $channelCounts = $baseQuery()->selectRaw("COUNT(*) as total, SUM(CASE WHEN lead_source = 'dealer_form' THEN 1 ELSE 0 END) as form_channel, SUM(CASE WHEN lead_source != 'dealer_form' THEN 1 ELSE 0 END) as link_channel")->first();
            $stats['total']        = (int) ($channelCounts->total ?? 0);
            $stats['form_channel'] = (int) ($channelCounts->form_channel ?? 0);
            $stats['link_channel'] = (int) ($channelCounts->link_channel ?? 0);

            $recent   = $baseQuery()->latest()->limit(30)->get(['id', 'tracking_token', 'first_name', 'last_name', 'lead_source', 'utm_source', 'utm_campaign', 'created_at']);
            $utmLinks = DealerUtmLink::query()->where('dealer_code', $data['dealerCode'])->where('is_active', true)->latest()->get();
            $utmPerf  = $baseQuery()->whereNotNull('utm_campaign')
                ->selectRaw("utm_campaign, COUNT(*) as leads_total, SUM(CASE WHEN converted_student_id IS NOT NULL THEN 1 ELSE 0 END) as leads_converted, MAX(created_at) as last_lead_at")
                ->groupBy('utm_campaign')->get()->keyBy('utm_campaign')
                ->map(fn ($r) => ['leads_total' => (int) $r->leads_total, 'leads_converted' => (int) $r->leads_converted, 'conv_rate' => $r->leads_total > 0 ? round($r->leads_converted / $r->leads_total * 100, 1) : 0.0, 'last_lead_at' => $r->last_lead_at ? \Carbon\Carbon::parse($r->last_lead_at)->format('d.m.Y') : null]);
        }

        return view('dealer.referral-links', $data + compact('stats', 'recent', 'utmLinks', 'utmPerf'));
    }

    public function storeUtmLink(Request $request)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing');

        $validated = $request->validate([
            'label'        => ['required', 'string', 'max:80'],
            'utm_campaign' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_\-]+$/'],
            'utm_source'   => ['nullable', 'string', 'max:60'],
            'utm_medium'   => ['nullable', 'string', 'max:60'],
        ]);

        DealerUtmLink::query()->create([
            'dealer_code'  => $data['dealerCode'],
            'label'        => trim((string) $validated['label']),
            'utm_campaign' => trim((string) $validated['utm_campaign']),
            'utm_source'   => trim((string) ($validated['utm_source'] ?? 'dealer')),
            'utm_medium'   => trim((string) ($validated['utm_medium'] ?? 'referral')),
        ]);

        return redirect('/dealer/referral-links')->with('status', 'Kampanya linki oluşturuldu.');
    }

    public function deleteUtmLink(Request $request, DealerUtmLink $utmLink)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing');
        abort_if($utmLink->dealer_code !== $data['dealerCode'], 403, 'Forbidden');

        $utmLink->delete();

        return redirect('/dealer/referral-links')->with('status', 'Kampanya linki silindi.');
    }

    public function calculator(Request $request)
    {
        $user       = $request->user();
        $dealerCode = strtoupper(trim((string) ($user->dealer_code ?? '')));
        $dealer     = $dealerCode !== '' ? Dealer::query()->where('code', $dealerCode)->first() : null;

        $milestones      = DealerRevenueMilestone::where('is_active', true)->orderBy('sort_order')->get();
        $studentRevenues = DealerStudentRevenue::where('dealer_id', $dealerCode)->orderByDesc('created_at')->limit(20)->get();

        return view('dealer.calculator', ['milestones' => $milestones, 'studentRevenues' => $studentRevenues, 'dealer' => $dealer]);
    }
}

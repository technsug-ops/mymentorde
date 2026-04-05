<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\LeadScoreLog;
use App\Models\LeadScoringRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScoringController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        // Score tier distribution
        $tierOrder = ['champion' => 0, 'sales_ready' => 1, 'hot' => 2, 'warm' => 3, 'cold' => 4];
        $tierDistribution = GuestApplication::query()
            ->withoutGlobalScope('company')
            ->selectRaw('lead_score_tier, COUNT(*) as total')
            ->whereNotNull('lead_score_tier')
            ->groupBy('lead_score_tier')
            ->get()
            ->sortBy(fn ($r) => $tierOrder[$r->lead_score_tier] ?? 99)
            ->keyBy('lead_score_tier')
            ->map(fn ($r) => $r->total);

        // Average score by tier
        $avgByTier = GuestApplication::query()
            ->withoutGlobalScope('company')
            ->selectRaw('lead_score_tier, AVG(lead_score) as avg_score, COUNT(*) as total')
            ->groupBy('lead_score_tier')
            ->get();

        // Recent score activity (last 7 days)
        $recentActivity = LeadScoreLog::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as events, SUM(points) as total_points')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top conversion by tier
        $tierConversion = GuestApplication::query()
            ->withoutGlobalScope('company')
            ->selectRaw("lead_score_tier, COUNT(*) as total, SUM(CASE WHEN contract_status = 'approved' THEN 1 ELSE 0 END) as converted")
            ->groupBy('lead_score_tier')
            ->get()
            ->map(fn ($r) => [
                'tier'       => $r->lead_score_tier,
                'total'      => $r->total,
                'converted'  => $r->converted,
                'conv_rate'  => $r->total > 0 ? round($r->converted / $r->total * 100, 1) : 0,
            ]);

        $tierLabels = [
            'cold'        => 'Cold (0-19)',
            'warm'        => 'Warm (20-49)',
            'hot'         => 'Hot (50-79)',
            'sales_ready' => 'Sales Ready (80-99)',
            'champion'    => 'Champion (100+)',
        ];
        $tierColors = [
            'cold'        => 'var(--u-muted)',
            'warm'        => '#0369a1',
            'hot'         => 'var(--u-warn)',
            'sales_ready' => '#7c3aed',
            'champion'    => 'var(--u-ok)',
        ];

        return view('marketing-admin.scoring.index', compact(
            'tierDistribution', 'avgByTier', 'recentActivity', 'tierConversion', 'tierLabels', 'tierColors'
        ));
    }

    public function leaderboard(Request $request): \Illuminate\View\View
    {
        $tier  = $request->query('tier');
        $limit = 50;

        $query = GuestApplication::query()
            ->withoutGlobalScope('company')
            ->orderByDesc('lead_score')
            ->limit($limit);

        if ($tier) {
            $query->where('lead_score_tier', $tier);
        }

        $leads = $query->get([
            'id', 'first_name', 'last_name', 'lead_score', 'lead_score_tier',
            'contract_status', 'lead_score_updated_at', 'created_at',
        ]);

        $tierLabels = [
            'cold' => 'Cold', 'warm' => 'Warm', 'hot' => 'Hot',
            'sales_ready' => 'Sales Ready', 'champion' => 'Champion',
        ];

        return view('marketing-admin.scoring.leaderboard', compact('leads', 'tier', 'tierLabels'));
    }

    public function config(): \Illuminate\View\View
    {
        $rules = LeadScoringRule::orderBy('category')->orderBy('action_code')->get();

        $categories = [
            'behavioral'  => 'Davranış',
            'demographic' => 'Demografik',
            'decay'       => 'Bozunma',
        ];

        return view('marketing-admin.scoring.config', compact('rules', 'categories'));
    }

    public function updateRule(Request $request, LeadScoringRule $rule): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'points'      => 'required|integer|between:-100,100',
            'max_per_day' => 'nullable|integer|min:1|max:100',
            'is_active'   => 'boolean',
        ]);

        $rule->update([
            'points'      => $data['points'],
            'max_per_day' => $data['max_per_day'] ?? null,
            'is_active'   => $request->boolean('is_active'),
            'updated_by'  => auth()->id(),
        ]);

        return back()->with('success', "Kural güncellendi: {$rule->action_code}");
    }

    public function scoreHistory(Request $request, int $guestId): \Illuminate\View\View
    {
        $guest = GuestApplication::withoutGlobalScope('company')->findOrFail($guestId);

        $logs = LeadScoreLog::where('guest_application_id', $guestId)
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('marketing-admin.scoring.history', compact('guest', 'logs'));
    }
}

<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\SocialMediaAccount;
use App\Models\Marketing\SocialMediaMonthlyMetric;
use App\Models\Marketing\SocialMediaPost;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SocialMetricsController extends Controller
{
    public function index(Request $request)
    {
        $period = (string) $request->query('period', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = now()->format('Y-m');
        }

        $rows = $this->syncAndLoadPeriod($period);

        return view('marketing-admin.social.metrics', [
            'pageTitle' => 'Sosyal Medya Metrikleri',
            'title' => 'Platform Karsilastirma',
            'period' => $period,
            'rows' => $rows,
            'summary' => [
                'followers_end' => (int) $rows->sum('followers_end'),
                'followers_growth' => (int) $rows->sum('followers_growth'),
                'total_posts' => (int) $rows->sum('total_posts'),
                'total_views' => (int) $rows->sum('total_views'),
                'total_engagement' => (int) ($rows->sum('total_likes') + $rows->sum('total_comments') + $rows->sum('total_shares')),
                'total_guest_registrations' => (int) $rows->sum('total_guest_registrations'),
            ],
        ]);
    }

    public function monthly(string $period)
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            abort(404);
        }
        $rows = $this->syncAndLoadPeriod($period);

        return view('marketing-admin.social.monthly', [
            'pageTitle' => 'Aylik Sosyal Medya',
            'title' => 'Donem: '.$period,
            'period' => $period,
            'rows' => $rows,
        ]);
    }

    private function syncAndLoadPeriod(string $period)
    {
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $period.'-01 00:00:00')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $accounts = SocialMediaAccount::query()->orderBy('platform')->orderBy('account_name')->get();
        foreach ($accounts as $account) {
            $posts = SocialMediaPost::query()
                ->where('account_id', $account->id)
                ->where(function ($w) use ($start, $end): void {
                    $w->whereBetween('published_at', [$start, $end])
                        ->orWhereBetween('scheduled_at', [$start, $end])
                        ->orWhereBetween('created_at', [$start, $end]);
                })
                ->get();

            $totalPosts = (int) $posts->count();
            $totalViews = (int) $posts->sum('metric_views');
            $totalLikes = (int) $posts->sum('metric_likes');
            $totalComments = (int) $posts->sum('metric_comments');
            $totalShares = (int) $posts->sum('metric_shares');
            $totalClicks = (int) $posts->sum('metric_click_through');
            $totalGuestRegs = (int) $posts->sum('metric_guest_registrations');
            $avgEng = $totalPosts > 0 ? round((float) $posts->avg('metric_engagement_rate'), 2) : 0.0;

            $followersEnd = (int) $account->followers;
            $followersGrowth = (int) $account->followers_growth_this_month;
            $followersStart = max(0, $followersEnd - $followersGrowth);
            $growthRate = $followersStart > 0 ? round(($followersGrowth / $followersStart) * 100, 2) : 0.0;

            $topPost = $posts
                ->sortByDesc(fn (SocialMediaPost $p) => ((int) $p->metric_reach + (int) $p->metric_likes + (int) $p->metric_comments))
                ->first();

            SocialMediaMonthlyMetric::query()->updateOrCreate(
                ['period' => $period, 'account_id' => $account->id],
                [
                    'platform' => $account->platform,
                    'followers_start' => $followersStart,
                    'followers_end' => $followersEnd,
                    'followers_growth' => $followersGrowth,
                    'followers_growth_rate' => $growthRate,
                    'total_posts' => $totalPosts,
                    'total_views' => $totalViews,
                    'total_likes' => $totalLikes,
                    'total_comments' => $totalComments,
                    'total_shares' => $totalShares,
                    'avg_engagement_rate' => $avgEng,
                    'total_click_through' => $totalClicks,
                    'total_guest_registrations' => $totalGuestRegs,
                    'top_post_id' => $topPost?->id,
                    'top_post_metric' => $topPost ? 'reach+engagement' : null,
                    'calculated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return SocialMediaMonthlyMetric::query()
            ->with(['account:id,platform,account_name', 'topPost:id,caption,post_url'])
            ->where('period', $period)
            ->orderBy('platform')
            ->orderBy('account_id')
            ->get();
    }
}

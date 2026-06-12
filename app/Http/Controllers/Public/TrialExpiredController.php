<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trial bitince payment wall sayfası.
 *
 * Customer Manager trial dolunca paneline girince EnsureTrialActive middleware
 * bu sayfaya yönlendirir. Kullanıcı buradan plan seçer (manager.my-plan'a gider)
 * veya destek ile iletişime geçer.
 */
class TrialExpiredController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $company = null;
        $expiredDaysAgo = null;

        if ($user && $user->company_id) {
            $company = Company::find($user->company_id);
            if ($company && $company->trial_ends_at) {
                $expiredDaysAgo = max(0, (int) round(
                    $company->trial_ends_at->diffInDays(now(), false)
                ));
            }
        }

        return view('public.trial-expired', [
            'user'           => $user,
            'company'        => $company,
            'expiredDaysAgo' => $expiredDaysAgo,
            'brandName'      => config('brand.name', 'MentorDE'),
            'planUrl'        => route('manager.my-plan'),
            'supportEmail'   => 'destek@mentorde.com',
        ]);
    }

    /**
     * Trial banner kapatma (info banner yalnızca — 4-7 gün arası).
     * Session'a flag set eder, o gün boyunca tekrar gösterilmez.
     */
    public function dismissBanner(Request $request): RedirectResponse
    {
        $days = (int) $request->input('days', 0);
        $user = $request->user();
        if (!$user || !$user->company_id || $days < 1) {
            return back();
        }

        $key = 'tb_dismissed_' . (int) $user->company_id . '_' . $days;
        session()->put($key, true);

        return back();
    }
}

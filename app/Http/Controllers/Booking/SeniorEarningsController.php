<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\SeniorEarning;
use App\Models\SeniorPayout;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Senior'un kazanç + payout dashboard'u — /senior/earnings
 * Ödeme modülü kapalı oldukça tüm değerler 0 gösterir.
 */
class SeniorEarningsController extends Controller
{
    public function index(Request $request): View
    {
        $userId = (int) $request->user()->id;

        $thisMonth = now()->startOfMonth();
        $earnings = SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $userId)
            ->where('recorded_at', '>=', $thisMonth)
            ->get();

        $yearStart = now()->startOfYear();
        $yearlyEarnings = SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $userId)
            ->where('recorded_at', '>=', $yearStart)
            ->get();

        $lifetimePaid = SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $userId)
            ->where('status', 'paid_out')
            ->sum('senior_payout_cents');

        $lifetimePending = SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $userId)
            ->where('status', 'recorded')
            ->sum('senior_payout_cents');

        $recentEarnings = SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $userId)
            ->with('publicBooking')
            ->latest('recorded_at')
            ->limit(50)
            ->get();

        $payouts = SeniorPayout::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $userId)
            ->latest('created_at')
            ->limit(24)
            ->get();

        return view('booking.senior.earnings', [
            'monthCount'         => $earnings->count(),
            'monthEarningsCents' => (int) $earnings->sum('senior_payout_cents'),
            'yearCount'          => $yearlyEarnings->count(),
            'yearEarningsCents'  => (int) $yearlyEarnings->sum('senior_payout_cents'),
            'lifetimePaidCents'  => (int) $lifetimePaid,
            'lifetimePendingCents' => (int) $lifetimePending,
            'recentEarnings'     => $recentEarnings,
            'payouts'            => $payouts,
        ]);
    }
}

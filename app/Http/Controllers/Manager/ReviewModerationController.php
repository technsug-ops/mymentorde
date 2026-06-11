<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\SeniorReview;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Marketplace Phase 7 — Manager review moderation cockpit.
 *
 *   GET  /manager/reviews                   -> liste (filter: status)
 *   POST /manager/reviews/{review}/approve  -> approved + is_public true
 *   POST /manager/reviews/{review}/reject   -> rejected + is_public false
 *   POST /manager/reviews/{review}/toggle   -> is_public flip
 *
 * BelongsToCompany scope otomatik filtreler.
 */
class ReviewModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = null;
        }

        $query = SeniorReview::query()->orderByDesc('created_at');
        if ($status) {
            $query->where('moderation_status', $status);
        }

        $reviews = $query->paginate(20)->withQueryString();

        // Senior isim eşleme (companya scope yok — withoutGlobalScopes)
        $seniorIds = $reviews->pluck('senior_user_id')->all();
        $seniors = !empty($seniorIds)
            ? User::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $seniorIds)
                ->get(['id', 'name', 'email'])
                ->keyBy('id')
            : collect();

        // Statü sayıları (badge)
        $counts = [
            'pending'  => SeniorReview::query()->where('moderation_status', 'pending')->count(),
            'approved' => SeniorReview::query()->where('moderation_status', 'approved')->count(),
            'rejected' => SeniorReview::query()->where('moderation_status', 'rejected')->count(),
        ];

        return view('booking.manager.reviews', [
            'reviews' => $reviews,
            'seniors' => $seniors,
            'status'  => $status,
            'counts'  => $counts,
        ]);
    }

    public function approve(Request $request, SeniorReview $review): RedirectResponse
    {
        $review->update([
            'moderation_status' => 'approved',
            'is_public'         => true,
            'moderated_at'      => now(),
            'moderation_note'   => $request->input('note'),
        ]);

        return back()->with('success', 'Yorum onaylandı ve yayınlandı.');
    }

    public function reject(Request $request, SeniorReview $review): RedirectResponse
    {
        $review->update([
            'moderation_status' => 'rejected',
            'is_public'         => false,
            'moderated_at'      => now(),
            'moderation_note'   => $request->input('note'),
        ]);

        return back()->with('success', 'Yorum reddedildi ve gizlendi.');
    }

    public function togglePublic(SeniorReview $review): RedirectResponse
    {
        $review->update([
            'is_public'    => !$review->is_public,
            'moderated_at' => now(),
        ]);
        return back()->with('success', $review->is_public ? 'Yorum yayınlandı.' : 'Yorum gizlendi.');
    }

    public function destroy(SeniorReview $review): RedirectResponse
    {
        $review->delete();
        return back()->with('success', 'Yorum silindi.');
    }
}

<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DealerApplication;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manager panel — dealer başvurularını onaylama/reddetme.
 *
 * Route prefix: /manager/dealer-applications
 * Access: manager, system_admin, operations_admin, marketing_admin, sales_admin
 */
class DealerApplicationController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $status = $request->input('status', 'pending');
        $allowedStatuses = ['pending', 'in_review', 'approved', 'rejected', 'waitlist', 'all'];
        if (!in_array($status, $allowedStatuses, true)) $status = 'pending';

        $query = DealerApplication::query()
            ->withoutGlobalScopes()
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $applications = $query->paginate(25)->withQueryString();

        $counts = [
            'pending'   => DealerApplication::withoutGlobalScopes()->where('status', 'pending')->count(),
            'in_review' => DealerApplication::withoutGlobalScopes()->where('status', 'in_review')->count(),
            'approved'  => DealerApplication::withoutGlobalScopes()->where('status', 'approved')->count(),
            'rejected'  => DealerApplication::withoutGlobalScopes()->where('status', 'rejected')->count(),
            'waitlist'  => DealerApplication::withoutGlobalScopes()->where('status', 'waitlist')->count(),
            'all'       => DealerApplication::withoutGlobalScopes()->count(),
        ];

        return view('manager.dealer-applications.index', [
            'applications'     => $applications,
            'counts'           => $counts,
            'currentStatus'    => $status,
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $this->ensureAdmin($request);
        $app = DealerApplication::withoutGlobalScopes()->findOrFail($id);
        return view('manager.dealer-applications.show', compact('app'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'status'  => ['required', 'in:in_review,approved,rejected,waitlist'],
            'note'    => ['nullable', 'string', 'max:2000'],
            'rejected_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $app = DealerApplication::withoutGlobalScopes()->findOrFail($id);

        $app->update([
            'status'           => $data['status'],
            'reviewed_by'      => $request->user()->id,
            'reviewed_at'      => now(),
            'review_note'      => $data['note'] ?? null,
            'rejected_reason'  => $data['status'] === 'rejected' ? ($data['rejected_reason'] ?? null) : null,
        ]);

        // PostHog event
        try {
            $this->analytics->capture('dealer_application_' . $data['status'], [
                'application_id' => $app->id,
                'preferred_plan' => $app->preferred_plan,
                'reviewer_id'    => $request->user()->id,
                'expected_volume'=> $app->expected_monthly_volume,
            ], 'dealer_app_' . $app->id);
        } catch (\Throwable) {}

        return redirect()->route('manager.dealer-applications.show', $id)
            ->with('success', 'Başvuru durumu güncellendi: ' . $data['status']);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        $allowed = array_unique(array_merge(
            \App\Models\User::ADMIN_PANEL_ROLES,
            \App\Models\User::MARKETING_ACCESS_ROLES
        ));
        if (!$user || !in_array((string) $user->role, $allowed, true)) {
            abort(403);
        }
    }
}

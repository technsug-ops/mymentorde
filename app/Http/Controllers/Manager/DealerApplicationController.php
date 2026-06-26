<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\DealerApplication;
use App\Models\User;
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

    /** Addon gate — her method başında çağrılır (Laravel 12 constructor middleware kaldırıldı) */
    private function ensureModuleEnabled(): void
    {
        \App\Support\ModuleAccess::assertEnabled('dealer');
    }

    public function index(Request $request): View
    {
        $this->ensureModuleEnabled();
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
        $this->ensureModuleEnabled();
        $this->ensureAdmin($request);
        $app = DealerApplication::withoutGlobalScopes()->findOrFail($id);
        return view('manager.dealer-applications.show', compact('app'));
    }

    /**
     * Çalışma rollerini güncelle (lead-gen / freelance — çoklu seçim).
     * preferred_plan primary olarak senkronlanır; başvuru zaten onaylıysa
     * bağlı bayi hesabının roles + primary dealer_type_code'u da güncellenir.
     */
    public function updateRoles(Request $request, int $id)
    {
        $this->ensureModuleEnabled();
        $this->ensureAdmin($request);

        $data = $request->validate([
            'roles'   => ['required', 'array', 'min:1'],
            'roles.*' => ['in:lead_generation,freelance'],
        ]);

        $app   = DealerApplication::withoutGlobalScopes()->findOrFail($id);
        $roles = array_values(array_unique($data['roles']));

        $app->update([
            'roles'          => $roles,
            // primary: freelance varsa freelance, yoksa lead_generation
            'preferred_plan' => in_array('freelance', $roles, true) ? 'freelance' : 'lead_generation',
        ]);

        // Onaylı başvuruysa bağlı bayiyi de senkronla (idempotent).
        if ($app->approved_dealer_id) {
            $dealer = Dealer::withoutGlobalScopes()->find($app->approved_dealer_id);
            if ($dealer) {
                $dealer->update([
                    'roles'            => $roles,
                    'dealer_type_code' => Dealer::primaryTypeForRoles($roles),
                ]);
            }
        }

        return back()->with('success', 'Çalışma rolleri güncellendi: ' . implode(', ', array_map(
            fn ($r) => Dealer::ROLE_LABELS[$r] ?? $r,
            $roles
        )));
    }

    public function updateStatus(Request $request, int $id)
    {
        $this->ensureModuleEnabled();
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

        // #11: Onaylandığında bayi hesabı + login kullanıcısı oluştur + davet maili.
        // Daha önce sadece statü güncelleniyordu; onaylanan başvuru Bayiler'de
        // görünmüyor ve giriş bilgisi gitmiyordu.
        $provisionMsg = '';
        if ($data['status'] === 'approved' && empty($app->approved_dealer_id)) {
            $provisionMsg = $this->provisionDealerFromApplication($app);
        }

        // PostHog event
        try {
            $this->analytics->capture('dealer_application_' . $data['status'], [
                'application_id' => $app->id,
                'preferred_plan' => $app->preferred_plan,
                'reviewer_id'    => $request->user()->id,
                'expected_volume'=> $app->expected_monthly_volume,
            ], 'dealer_app_' . $app->id);
        } catch (\Throwable) {}

        return redirect(\App\Support\PanelRouting::url('dealer-applications', 'show', $id))
            ->with('success', 'Başvuru durumu güncellendi: ' . $data['status'] . $provisionMsg);
    }

    /**
     * Onaylanan başvurudan bayi hesabı üret (DealerProvisioningService).
     * Idempotent. updateStatus success mesajına eklenecek string döner.
     */
    private function provisionDealerFromApplication(DealerApplication $app): string
    {
        $result = app(\App\Services\DealerProvisioningService::class)->fromApplication($app);

        if (!$result['ok']) {
            return ' — (uyarı: ' . $result['message'] . ')';
        }
        if ($result['skipped']) {
            return '';
        }
        return ' — bayi hesabı oluşturuldu (' . $result['dealer_code'] . '), davet e-postası gönderildi';
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

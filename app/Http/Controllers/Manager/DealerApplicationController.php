<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\DealerApplication;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
     * Onaylanan başvurudan bayi hesabı + login kullanıcısı üret + şifre belirleme
     * daveti gönder. Idempotent: approved_dealer_id doluysa tekrar çalışmaz.
     */
    private function provisionDealerFromApplication(DealerApplication $app): string
    {
        $email = strtolower(trim((string) $app->email));
        if ($email === '') {
            return ' — (uyarı: e-posta yok, bayi hesabı oluşturulamadı)';
        }

        // preferred_plan → dealer_type_code eşlemesi
        $typeCode = match ((string) $app->preferred_plan) {
            'freelance' => 'freelance_danisman',
            'lead_generation' => 'lead_generation',
            default => 'lead_generation', // 'unsure' vb.
        };

        $companyId = (int) ($app->company_id ?: (\App\Models\Company::query()->where('is_active', true)->orderBy('id')->value('id') ?? 1));
        $name = trim(($app->first_name ?? '') . ' ' . ($app->last_name ?? '')) ?: ($app->company_name ?: 'Bayi');

        // Aynı e-posta zaten kullanıcıysa: çakışmayı önle
        $existingUser = User::query()->where('email', $email)->first();

        $dealer = Dealer::query()->create([
            'company_id'        => $companyId,
            'code'              => $this->generateDealerCodeForType($typeCode),
            'internal_sequence' => ((int) Dealer::query()->max('internal_sequence')) + 1,
            'name'              => $name,
            'email'             => $email,
            'phone'             => $app->phone ?: null,
            'dealer_type_code'  => $typeCode,
            'is_active'         => true,
            'is_archived'       => false,
        ]);

        $userId = null;
        if ($existingUser) {
            // Mevcut kullanıcıyı bayiye bağla (rolü değiştirmeden dealer_code ata)
            $existingUser->forceFill(['dealer_code' => $dealer->code])->save();
            $userId = $existingUser->id;
        } else {
            $user = User::query()->create([
                'company_id'  => $companyId,
                'name'        => $name,
                'email'       => $email,
                'role'        => User::ROLE_DEALER,
                'dealer_code' => $dealer->code,
                'is_active'   => true,
                'password'    => Hash::make(Str::random(40)),
            ]);
            $userId = $user->id;
            // Şifre belirleme / aktivasyon daveti
            try {
                Password::sendResetLink(['email' => $email]);
            } catch (\Throwable $e) {
            }
        }

        $app->forceFill([
            'approved_dealer_id' => $dealer->id,
            'approved_user_id'   => $userId,
        ])->save();

        return ' — bayi hesabı oluşturuldu (' . $dealer->code . '), davet e-postası gönderildi';
    }

    /** dealer_type bazlı benzersiz bayi kodu (Api\DealerController deseni). */
    private function generateDealerCodeForType(string $typeCode): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $typeCode) ?: 'DLR', 0, 3));
        $base   = "{$prefix}-" . now()->format('y') . '-' . now()->format('m');
        $seq    = ((int) Dealer::query()->max('internal_sequence')) + 1;
        do {
            $token     = strtoupper(substr(hash('crc32b', "{$base}-{$seq}"), 0, 4));
            $candidate = "{$base}-{$token}";
            if (!Dealer::query()->where('code', $candidate)->exists()) {
                return $candidate;
            }
            $seq++;
        } while (true);
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

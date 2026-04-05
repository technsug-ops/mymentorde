<?php

namespace App\Http\Controllers\Dealer\Concerns;

use App\Models\Dealer;
use App\Models\DealerStudentRevenue;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\UserPortalPreference;
use App\Support\DealerTierPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Shared helpers for Dealer portal sub-controllers.
 * Controllers using this trait must inject TaskAutomationService,
 * EventLogService, and NotificationService in their constructor.
 */
trait DealerPortalTrait
{
    protected function baseData(Request $request): array
    {
        $user       = $request->user();
        $dealerCode = strtoupper(trim((string) ($user->dealer_code ?? '')));
        $dealer     = $dealerCode !== '' ? Dealer::query()->where('code', $dealerCode)->first() : null;

        $dealerStats = Cache::remember("dealer_stats_{$dealerCode}", 300, function () use ($dealerCode) {
            if ($dealerCode === '') {
                return ['guest_total' => 0, 'student_total' => 0, 'converted_total' => 0,
                        'conversion_rate' => 0, 'total_earned' => 0.0, 'total_pending' => 0.0, 'month_earned' => 0.0];
            }

            $studentAssignments = StudentAssignment::query()
                ->where('dealer_id', $dealerCode)
                ->latest('updated_at')
                ->limit(500)
                ->get(['student_id', 'updated_at']);

            $revenues = DealerStudentRevenue::query()
                ->where('dealer_id', $dealerCode)
                ->latest('updated_at')
                ->limit(500)
                ->get(['student_id', 'total_earned', 'total_pending', 'updated_at']);

            $guestLeads = GuestApplication::query()
                ->where('dealer_code', $dealerCode)
                ->latest()
                ->limit(500)
                ->get(['id', 'converted_student_id', 'created_at']);

            $totalEarned    = (float) $revenues->sum(fn ($r) => (float) ($r->total_earned ?? 0));
            $totalPending   = (float) $revenues->sum(fn ($r) => (float) ($r->total_pending ?? 0));
            $monthRevenue   = (float) $revenues
                ->filter(fn ($r) => optional($r->updated_at)?->greaterThanOrEqualTo(now()->startOfMonth()))
                ->sum(fn ($r) => (float) ($r->total_earned ?? 0));
            $convertedCount = $guestLeads->filter(fn ($g) => trim((string) ($g->converted_student_id ?? '')) !== '')->count();
            $total          = $guestLeads->count();

            return [
                'guest_total'     => $total,
                'student_total'   => $studentAssignments->pluck('student_id')->filter()->unique()->count(),
                'converted_total' => $convertedCount,
                'conversion_rate' => $total > 0 ? round(($convertedCount / $total) * 100, 1) : 0,
                'total_earned'    => round($totalEarned, 2),
                'total_pending'   => round($totalPending, 2),
                'month_earned'    => round($monthRevenue, 2),
            ];
        });

        $tierPerms = DealerTierPermissions::for($dealer);

        return [
            'dealerCode'  => $dealerCode,
            'dealer'      => $dealer,
            'dealerStats' => $dealerStats,
            'dealerLink'  => $dealerCode !== '' ? url('/apply').'?ref='.urlencode($dealerCode) : null,
            'tierPerms'   => $tierPerms,
        ];
    }

    protected function generateTrackingToken(): string
    {
        do {
            $token = strtoupper(Str::random(12));
            $token = preg_replace('/[^A-Z0-9]/', 'X', $token) ?: strtoupper(Str::random(12));
        } while (GuestApplication::query()->where('tracking_token', $token)->exists());

        return $token;
    }

    protected function prefs($user, string $portalKey): array
    {
        if (!$user) {
            return [];
        }

        return (array) (UserPortalPreference::query()
            ->where('user_id', $user->id)
            ->where('portal_key', $portalKey)
            ->value('preferences_json') ?? []);
    }

    protected function savePrefs(int $userId, string $portalKey, array $data): void
    {
        UserPortalPreference::query()->updateOrCreate(
            ['user_id' => $userId, 'portal_key' => $portalKey],
            ['preferences_json' => $data]
        );
    }

    protected function queueDealerLeadNotifications(GuestApplication $guest, string $dealerUserEmail = ''): void
    {
        $managerEmails = \App\Models\User::query()
            ->where('role', 'manager')
            ->where('is_active', true)
            ->pluck('email')
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values();

        $managerIds = \App\Models\User::query()
            ->whereIn('email', $managerEmails->all())
            ->where('is_active', true)
            ->pluck('id');

        foreach ($managerIds as $managerId) {
            $this->notificationService->send([
                'channel'      => 'in_app',
                'category'     => 'guest_new_lead',
                'user_id'      => (int) $managerId,
                'subject'      => 'Yeni dealer yonlendirmesi',
                'body'         => 'Dealer panelinden yeni yonlendirme olusturuldu.',
                'variables'    => [
                    'guest_id'          => (int) $guest->id,
                    'tracking_token'    => (string) $guest->tracking_token,
                    'lead_source'       => (string) ($guest->lead_source ?? ''),
                    'dealer_code'       => (string) ($guest->dealer_code ?? ''),
                    'dealer_user_email' => $dealerUserEmail,
                ],
                'source_type'  => 'dealer_lead',
                'source_id'    => (string) $guest->id,
                'triggered_by' => $dealerUserEmail !== '' ? $dealerUserEmail : 'dealer',
            ]);
        }
    }

    protected function calculateLeadProgress(GuestApplication $lead): array
    {
        return [
            ['label' => 'Kayıt',    'done' => true,                                                                            'icon' => '📋'],
            ['label' => 'İletişim', 'done' => !in_array($lead->lead_status, ['new'], true),                                    'icon' => '📞'],
            ['label' => 'Belgeler', 'done' => (bool) $lead->docs_ready,                                                        'icon' => '📄'],
            ['label' => 'Sözleşme','done' => in_array($lead->contract_status, ['approved', 'signed_uploaded'], true),          'icon' => '📝'],
            ['label' => 'Dönüşüm', 'done' => filled($lead->converted_student_id),                                              'icon' => '🎓'],
        ];
    }

    protected function nextMilestone(string $dealerCode, float $currentEarned): ?array
    {
        return null;
    }
}

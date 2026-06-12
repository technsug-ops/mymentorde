<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\PlatformSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Platform Owner — Güvenlik sayfası.
 *
 * Cross-tenant güvenlik politikası: session timeout, password min length,
 * 2FA zorunluluğu, max login attempts. Failed login / aktif session özet.
 */
class PlatformSecurityController extends Controller
{
    public function index(): View
    {
        $now    = CarbonImmutable::now();
        $today  = $now->startOfDay();
        $last24 = $now->subDay();

        // ── KPI'lar ──
        $activeSessions = $this->countActiveSessions();
        $todayLogins    = $this->countAuditAction('login', $today);
        $todayFailed    = $this->countAuditAction('failed_login', $today);

        $totalPlatformOwners = (int) User::query()
            ->withoutGlobalScopes()
            ->where('role', User::ROLE_PLATFORM_OWNER)
            ->count();

        $twoFaUsersCount = 0;
        if (Schema::hasTable('user_two_factor')) {
            try {
                $twoFaUsersCount = (int) DB::table('user_two_factor')
                    ->whereNotNull('enabled_at')
                    ->count();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $activeIpRulesCount = 0;
        $ipRulesAvailable = Schema::hasTable('ip_rules');
        if ($ipRulesAvailable) {
            try {
                $q = DB::table('ip_rules');
                if (Schema::hasColumn('ip_rules', 'is_active')) {
                    $q->where('is_active', true);
                }
                $activeIpRulesCount = (int) $q->count();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // ── Recent login activity ──
        $recentLogins = collect();
        if (Schema::hasTable('audit_trails')) {
            try {
                $recentLogins = DB::table('audit_trails')
                    ->whereIn('action', ['login', 'failed_login', 'logout'])
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // ── Failed login son 24 saat detaylı ──
        $failedLogins24h = 0;
        if (Schema::hasTable('audit_trails')) {
            try {
                $failedLogins24h = (int) DB::table('audit_trails')
                    ->where('action', 'failed_login')
                    ->where('created_at', '>=', $last24)
                    ->count();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // ── Aktif security settings ──
        $settings = [
            'session_timeout_minutes'        => (int) PlatformSetting::get('security.session_timeout_minutes', 60),
            'password_min_length'            => (int) PlatformSetting::get('security.password_min_length', 12),
            'require_2fa_for_platform_owner' => (bool) PlatformSetting::get('security.require_2fa_for_platform_owner', true),
            'max_login_attempts'             => (int) PlatformSetting::get('security.max_login_attempts', 5),
        ];

        return view('platform.security.index', [
            'kpis' => [
                'active_sessions'  => $activeSessions,
                'today_logins'     => $todayLogins,
                'today_failed'     => $todayFailed,
                'platform_owners'  => $totalPlatformOwners,
                'two_fa_users'     => $twoFaUsersCount,
                'active_ip_rules'  => $activeIpRulesCount,
            ],
            'settings'         => $settings,
            'recentLogins'     => $recentLogins,
            'failedLogins24h'  => $failedLogins24h,
            'ipRulesAvailable' => $ipRulesAvailable,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'session_timeout_minutes'        => 'required|integer|min:5|max:1440',
            'password_min_length'            => 'required|integer|min:6|max:64',
            'require_2fa_for_platform_owner' => 'nullable|boolean',
            'max_login_attempts'             => 'required|integer|min:1|max:50',
        ]);

        $require2fa = $request->boolean('require_2fa_for_platform_owner');

        $old = [
            'session_timeout_minutes'        => PlatformSetting::get('security.session_timeout_minutes'),
            'password_min_length'            => PlatformSetting::get('security.password_min_length'),
            'require_2fa_for_platform_owner' => PlatformSetting::get('security.require_2fa_for_platform_owner'),
            'max_login_attempts'             => PlatformSetting::get('security.max_login_attempts'),
        ];

        PlatformSetting::set('security.session_timeout_minutes',        (int) $data['session_timeout_minutes'],     'security');
        PlatformSetting::set('security.password_min_length',            (int) $data['password_min_length'],         'security');
        PlatformSetting::set('security.require_2fa_for_platform_owner', $require2fa,                                'security');
        PlatformSetting::set('security.max_login_attempts',             (int) $data['max_login_attempts'],          'security');

        $new = [
            'session_timeout_minutes'        => (int) $data['session_timeout_minutes'],
            'password_min_length'            => (int) $data['password_min_length'],
            'require_2fa_for_platform_owner' => $require2fa,
            'max_login_attempts'             => (int) $data['max_login_attempts'],
        ];

        $this->logAudit('platform.security.update', $old, $new, $request);

        return redirect()->route('platform.security')
            ->with('success', 'Güvenlik ayarları güncellendi.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function countActiveSessions(): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }
        if (!Schema::hasTable('sessions')) {
            return 0;
        }
        try {
            $threshold = time() - (int) (config('session.lifetime', 120) * 60);
            return (int) DB::table('sessions')
                ->where('last_activity', '>=', $threshold)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countAuditAction(string $action, \DateTimeInterface $since): int
    {
        if (!Schema::hasTable('audit_trails')) {
            return 0;
        }
        try {
            return (int) DB::table('audit_trails')
                ->where('action', $action)
                ->where('created_at', '>=', $since)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function logAudit(string $action, array $old, array $new, Request $request): void
    {
        // 1) Yeni platform audit log (platform owner panel)
        try {
            \App\Models\PlatformAuditLog::record(
                $action,
                [
                    'target_type' => 'platform_security',
                    'old'         => $old,
                    'new'         => $new,
                ],
                \App\Models\PlatformAuditLog::SEVERITY_CRITICAL
            );
        } catch (\Throwable $e) {
            // ignore
        }

        // 2) Eski audit_trails (kanonik denetim)
        try {
            if (Schema::hasTable('audit_trails')) {
                AuditTrail::create([
                    'company_id'  => null,
                    'user_id'     => auth()->id(),
                    'action'      => 'update',
                    'entity_type' => 'platform_security',
                    'entity_id'   => $action,
                    'old_values'  => $old,
                    'new_values'  => $new,
                    'ip_address'  => $request->ip(),
                    'user_agent'  => substr((string) $request->userAgent(), 0, 500),
                    'request_url' => substr($request->fullUrl(), 0, 500),
                ]);
                return;
            }
        } catch (\Throwable $e) {
            // fall through
        }
        Log::info("[platform-audit] {$action}", [
            'user_id' => auth()->id(),
            'old'     => $old,
            'new'     => $new,
            'ip'      => $request->ip(),
        ]);
    }
}

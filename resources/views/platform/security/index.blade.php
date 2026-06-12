@extends('platform.layouts.app')

@section('title', 'Güvenlik — DGmarkt Platform')

@push('styles')
<style>
    .psec-kpi-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 24px; }
    @media (max-width: 1200px) { .psec-kpi-row { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 640px)  { .psec-kpi-row { grid-template-columns: repeat(2, 1fr); } }

    .psec-warn-box {
        background: rgba(248,113,113,.08);
        border: 1px solid rgba(248,113,113,.30);
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--plat-danger);
    }
    .psec-warn-box.warn-yellow {
        background: rgba(251,191,36,.08);
        border-color: rgba(251,191,36,.30);
        color: var(--plat-warn);
    }
    .psec-warn-icon { flex-shrink: 0; }
    .psec-warn-content { font-size: 13px; line-height: 1.5; }
    .psec-warn-content strong { color: #fff; }

    .psec-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px; }
    @media (max-width: 700px) { .psec-form-row { grid-template-columns: 1fr; } }

    .psec-toggle-row { display: flex; align-items: center; justify-content: space-between; background: var(--plat-panel-2); border: 1px solid var(--plat-border); border-radius: 10px; padding: 14px 16px; margin-bottom: 14px; }
    .psec-toggle-title { font-size: 13px; font-weight: 700; color: #fff; }
    .psec-toggle-desc  { font-size: 11px; color: var(--plat-muted); margin-top: 3px; }

    .psec-log-action { display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
    .psec-log-action-login  { background: rgba(74,222,128,.14); color: var(--plat-ok); }
    .psec-log-action-failed { background: rgba(248,113,113,.14); color: var(--plat-danger); }
    .psec-log-action-logout { background: rgba(96,165,250,.14); color: var(--plat-info); }
</style>
@endpush

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Güvenlik</h1>
        <p class="plat-page-sub">Platform-geneli güvenlik politikası, login aktivitesi ve erişim kontrolleri.</p>
    </div>
    @if ($ipRulesAvailable)
        <a href="/manager/system/ip-rules" class="plat-btn plat-btn-ghost">
            <x-icon name="lock" size="14" /> IP allowlist (manager)
        </a>
    @endif
</div>

{{-- ── 6 KPI ─────────────────────────────────────────────────────── --}}
<div class="psec-kpi-row">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="users" size="12" /> Aktif Session</div>
        <div class="plat-kpi-value">{{ number_format((int) $kpis['active_sessions']) }}</div>
        <div class="plat-kpi-sub">Son {{ config('session.lifetime', 120) }} dakika</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="log-out" size="12" /> Bugün Login</div>
        <div class="plat-kpi-value">{{ number_format((int) $kpis['today_logins']) }}</div>
        <div class="plat-kpi-sub">Başarılı giriş</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="alert-triangle" size="12" /> Bugün Failed</div>
        <div class="plat-kpi-value" style="color: {{ (int) $kpis['today_failed'] > 0 ? 'var(--plat-danger)' : '#fff' }};">
            {{ number_format((int) $kpis['today_failed']) }}
        </div>
        <div class="plat-kpi-sub">Başarısız giriş</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="crown" size="12" /> Platform Owner</div>
        <div class="plat-kpi-value">{{ number_format((int) $kpis['platform_owners']) }}</div>
        <div class="plat-kpi-sub">Toplam kullanıcı</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="key-round" size="12" /> 2FA Aktif</div>
        <div class="plat-kpi-value">{{ number_format((int) $kpis['two_fa_users']) }}</div>
        <div class="plat-kpi-sub">user_two_factor</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="lock" size="12" /> Aktif IP Rule</div>
        <div class="plat-kpi-value">{{ number_format((int) $kpis['active_ip_rules']) }}</div>
        <div class="plat-kpi-sub">{{ $ipRulesAvailable ? 'Allowlist/blocklist' : 'tablo yok' }}</div>
    </div>
</div>

{{-- ── Failed login uyarısı ──────────────────────────────────────── --}}
@if ($failedLogins24h > 0)
    <div class="psec-warn-box {{ $failedLogins24h > 20 ? '' : 'warn-yellow' }}">
        <x-icon name="alert-triangle" size="20" class="psec-warn-icon" />
        <div class="psec-warn-content">
            <strong>Son 24 saatte {{ $failedLogins24h }} başarısız giriş denemesi.</strong>
            @if ($failedLogins24h > 20)
                Bu sayı yüksek — IP allowlist veya rate-limit'i sıkılaştırmayı değerlendirin.
            @else
                Normal aralıkta görünüyor; yine de loglar incelenmelidir.
            @endif
        </div>
    </div>
@endif

{{-- ── Güvenlik settings formu ───────────────────────────────────── --}}
<div class="plat-grid plat-grid-2">

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="shield" size="16" /> Güvenlik Politikası</h3>

        <form method="POST" action="{{ route('platform.security.update') }}">
            @csrf

            <div class="psec-form-row">
                <div class="plat-form-group">
                    <label class="plat-form-label" for="session_timeout_minutes">Session timeout (dakika)</label>
                    <input type="number" id="session_timeout_minutes" name="session_timeout_minutes"
                           class="plat-input" min="5" max="1440"
                           value="{{ $settings['session_timeout_minutes'] }}" required>
                </div>
                <div class="plat-form-group">
                    <label class="plat-form-label" for="max_login_attempts">Max login deneme</label>
                    <input type="number" id="max_login_attempts" name="max_login_attempts"
                           class="plat-input" min="1" max="50"
                           value="{{ $settings['max_login_attempts'] }}" required>
                </div>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="password_min_length">Şifre minimum uzunluk</label>
                <input type="number" id="password_min_length" name="password_min_length"
                       class="plat-input" min="6" max="64"
                       value="{{ $settings['password_min_length'] }}" required>
            </div>

            <div class="psec-toggle-row">
                <div>
                    <div class="psec-toggle-title">Platform Owner için 2FA zorunlu</div>
                    <div class="psec-toggle-desc">Platform Owner rolündeki tüm kullanıcılar TOTP 2FA kurmalıdır.</div>
                </div>
                <label class="plat-switch">
                    <input type="hidden" name="require_2fa_for_platform_owner" value="0">
                    <input type="checkbox" name="require_2fa_for_platform_owner" value="1"
                           @checked((bool) $settings['require_2fa_for_platform_owner'])>
                    <span class="plat-switch-slider"></span>
                </label>
            </div>

            <div style="text-align: right; margin-top: 18px;">
                <button type="submit" class="plat-btn plat-btn-primary">
                    <x-icon name="check" size="14" /> Güvenlik politikasını kaydet
                </button>
            </div>
        </form>
    </div>

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="key-round" size="16" /> Son Login Aktivitesi (10)</h3>

        @if ($recentLogins->count() > 0)
            <table class="plat-table">
                <thead>
                    <tr>
                        <th>Aksiyon</th>
                        <th>User</th>
                        <th>IP</th>
                        <th>Zaman</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentLogins as $row)
                        <tr>
                            <td>
                                @php $cls = 'login'; if ($row->action === 'failed_login') $cls = 'failed'; elseif ($row->action === 'logout') $cls = 'logout'; @endphp
                                <span class="psec-log-action psec-log-action-{{ $cls }}">{{ $row->action }}</span>
                            </td>
                            <td style="font-family: monospace; font-size: 11px;">#{{ $row->user_id ?? '—' }}</td>
                            <td style="font-family: monospace; font-size: 11px;">{{ $row->ip_address ?? '—' }}</td>
                            <td style="font-size: 11px; color: var(--plat-muted);">
                                {{ \Carbon\Carbon::parse($row->created_at)->diffForHumans() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="plat-card-sub">audit_trails tablosunda login aksiyonu henüz kaydedilmemiş.</p>
        @endif
    </div>

</div>

{{-- ── Bonus: hızlı sistem linkleri ───────────────────────────────── --}}
<div class="plat-card" style="margin-top: 22px;">
    <h3 class="plat-card-title"><x-icon name="lock" size="16" /> Hızlı erişim — Manager Sistem Modülleri</h3>
    <p class="plat-card-sub" style="margin-bottom: 14px;">
        Platform Owner aşağıdaki Manager sistem panellerine doğrudan girebilir.
    </p>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="/manager/system" class="plat-btn plat-btn-ghost">
            <x-icon name="settings" size="14" /> Sistem Dashboard
        </a>
        @if ($ipRulesAvailable)
            <a href="/manager/system/ip-rules" class="plat-btn plat-btn-ghost">
                <x-icon name="lock" size="14" /> IP Rules
            </a>
        @endif
        <a href="/manager/system/security" class="plat-btn plat-btn-ghost">
            <x-icon name="shield" size="14" /> Manager Güvenlik
        </a>
        <a href="/manager/system/roles" class="plat-btn plat-btn-ghost">
            <x-icon name="users" size="14" /> Rol Yönetimi
        </a>
    </div>
</div>

@endsection

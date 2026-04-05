@extends('manager.layouts.app')
@section('title', 'Sistem Paneli')
@section('page_title', 'Sistem Paneli')

@push('head')
<style>
.sys-quick { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;padding:14px 8px;background:var(--u-card);border:1.5px solid var(--u-line);border-radius:12px;text-decoration:none;transition:all .15s;text-align:center; }
.sys-quick:hover { border-color:#1e40af;background:#eff6ff;transform:translateY(-2px); }
.sys-quick .sq-icon { font-size:20px; }
.sys-quick .sq-label { font-size:10px;font-weight:700;color:var(--u-text);line-height:1.3; }
.ev-badge { display:inline-block;padding:2px 8px;border-radius:5px;font-size:10px;font-weight:700;text-transform:uppercase; }
.role-pill { display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;background:var(--u-bg);border:1px solid var(--u-line); }
</style>
@endpush

@section('content')

@if(session('status'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#15803d;">{{ session('status') }}</div>
@endif

{{-- ─── Ana KPI Satırı ─── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px;">

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #1e40af;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Toplam Kullanıcı</div>
        <div style="font-size:26px;font-weight:800;color:var(--u-text);line-height:1;">{{ $userStats['total'] }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">
            <span style="color:#16a34a;font-weight:700;">{{ $userStats['active'] }} aktif</span>
            @if($userStats['passive'] > 0) · <span style="color:#dc2626;">{{ $userStats['passive'] }} pasif</span>@endif
            · <span style="color:#1e40af;">+{{ $newThisMonth }} bu ay</span>
        </div>
    </div>

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid {{ $twoFaPct >= 80 ? '#16a34a' : ($twoFaPct >= 50 ? '#d97706' : '#dc2626') }};border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">2FA Benimseme</div>
        <div style="font-size:26px;font-weight:800;color:{{ $twoFaPct >= 80 ? '#16a34a' : ($twoFaPct >= 50 ? '#d97706' : '#dc2626') }};line-height:1;">%{{ $twoFaPct }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">{{ $twoFaCount }} / {{ $userStats['total'] }} kullanıcı</div>
    </div>

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #6366f1;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Aktif Oturum</div>
        <div style="font-size:26px;font-weight:800;color:var(--u-text);line-height:1;">{{ $activeSessionCount }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">son 30 dakika</div>
    </div>

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid {{ $criticalCount > 0 ? '#dc2626' : ($warningCount > 0 ? '#d97706' : '#16a34a') }};border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Güvenlik Anomali</div>
        <div style="font-size:26px;font-weight:800;color:{{ $criticalCount > 0 ? '#dc2626' : ($warningCount > 0 ? '#d97706' : '#16a34a') }};line-height:1;">
            {{ $anomalies->count() }}
        </div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">
            @if($criticalCount > 0)<span style="color:#dc2626;font-weight:700;">{{ $criticalCount }} kritik</span>@endif
            @if($warningCount > 0) · <span style="color:#d97706;font-weight:700;">{{ $warningCount }} uyarı</span>@endif
            @if($anomalies->count() === 0)<span style="color:#16a34a;font-weight:700;">temiz</span>@endif
        </div>
    </div>

</div>

{{-- ─── İkinci Satır KPI ─── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px;">

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #0891b2;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Son 24s Olay</div>
        <div style="font-size:26px;font-weight:800;color:var(--u-text);line-height:1;">{{ $recentEventCount }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;"><a href="/manager/audit-log" style="color:#0891b2;font-weight:700;text-decoration:none;">Log Görüntüle →</a></div>
    </div>

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #7c3aed;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Denetim Kaydı (24s)</div>
        <div style="font-size:26px;font-weight:800;color:var(--u-text);line-height:1;">{{ $recentAuditCount }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">audit_trails kaydı</div>
    </div>

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid {{ $failedJobCount > 0 ? '#dc2626' : '#16a34a' }};border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Başarısız İş</div>
        <div style="font-size:26px;font-weight:800;color:{{ $failedJobCount > 0 ? '#dc2626' : '#16a34a' }};line-height:1;">{{ $failedJobCount }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">failed_jobs tablosu</div>
    </div>

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #f59e0b;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">IP Erişim Kuralı</div>
        <div style="font-size:26px;font-weight:800;color:var(--u-text);line-height:1;">{{ $activeIpRules }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">{{ $ipRuleCount }} toplam · <a href="/manager/system/ip-rules" style="color:#f59e0b;font-weight:700;text-decoration:none;">Yönet →</a></div>
    </div>

</div>

{{-- ─── Hızlı Erişim ─── --}}
<div style="display:grid;grid-template-columns:repeat(8,1fr);gap:8px;margin-bottom:14px;">
    <a href="/manager/system/security" class="sys-quick"><span class="sq-icon">🛡</span><span class="sq-label">Güvenlik Paneli</span></a>
    <a href="/manager/system/ip-rules" class="sys-quick"><span class="sq-icon">🌐</span><span class="sq-label">IP Kuralları</span></a>
    <a href="/manager/audit-log"       class="sys-quick"><span class="sq-icon">🔍</span><span class="sq-label">Denetim Kayıtları</span></a>
    <a href="/manager/gdpr-dashboard"  class="sys-quick"><span class="sq-icon">🔒</span><span class="sq-label">GDPR Paneli</span></a>
    <a href="/manager/notification-stats" class="sys-quick"><span class="sq-icon">🔔</span><span class="sq-label">Bildirim İstat.</span></a>
    <a href="/manager/webhooks"        class="sys-quick"><span class="sq-icon">🔗</span><span class="sq-label">Webhook Logları</span></a>
    <a href="/manager/theme"           class="sys-quick"><span class="sq-icon">🎨</span><span class="sq-label">Tema Yönetimi</span></a>
    <a href="/manager/brand"           class="sys-quick"><span class="sq-icon">🏷</span><span class="sq-label">Marka Ayarları</span></a>
</div>

<div class="grid2" style="gap:12px;margin-bottom:12px;">

    {{-- Aktif Oturumlar --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">💻 Aktif Oturumlar (Son 30 dk)</div>
        @if($recentSessions->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Aktif oturum bulunamadı.</div>
        @else
        @foreach($recentSessions as $s)
        <div style="padding:9px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;gap:8px;">
            <div>
                <div style="font-size:12px;font-weight:700;color:var(--u-text);">{{ $s->name ?: $s->email }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ $s->role }} · {{ $s->ip_address }}</div>
            </div>
            <div style="font-size:10px;color:var(--u-muted);">{{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</div>
        </div>
        @endforeach
        @endif
    </section>

    {{-- Başarısız İşler --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:var(--tx-sm);">💥 Başarısız İşler</div>
            @if($failedJobCount > 0)
            <span style="background:#fee2e2;color:#dc2626;font-size:10px;font-weight:800;padding:2px 8px;border-radius:999px;">{{ $failedJobCount }} bekliyor</span>
            @endif
        </div>
        @if($recentFailedJobs->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Başarısız iş yok.</div>
        @else
        @foreach($recentFailedJobs as $job)
        @php
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['displayName'] ?? ($payload['job'] ?? 'Bilinmeyen');
            $jobClass = class_basename($jobClass);
        @endphp
        <div style="padding:9px 16px;border-bottom:1px solid var(--u-line);">
            <div style="font-size:12px;font-weight:700;color:#dc2626;">{{ $jobClass }}</div>
            <div style="font-size:10px;color:var(--u-muted);">Kuyruk: {{ $job->queue }} · {{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}</div>
        </div>
        @endforeach
        @endif
    </section>

</div>

<div class="grid2" style="gap:12px;margin-bottom:12px;">

    {{-- Rol Dağılımı --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">👥 Rol Dağılımı</div>
        <div style="padding:14px 16px;display:flex;flex-wrap:wrap;gap:8px;">
        @foreach($roleCounts as $role => $cnt)
        <div style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:8px;padding:8px 12px;text-align:center;min-width:90px;">
            <div style="font-size:18px;font-weight:800;color:var(--u-text);">{{ $cnt }}</div>
            <div style="font-size:10px;color:var(--u-muted);margin-top:2px;">{{ str_replace('_', ' ', $role) }}</div>
        </div>
        @endforeach
        </div>
    </section>

    {{-- Son Sistem Olayları --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:var(--tx-sm);">📋 Son Sistem Olayları</div>
            <a href="/manager/audit-log" style="font-size:11px;color:#1e40af;font-weight:700;text-decoration:none;">Tümü →</a>
        </div>
        @if($recentEvents->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Kayıt yok.</div>
        @else
        @foreach($recentEvents as $ev)
        @php
            $evColor = str_starts_with($ev->event_type, 'gdpr') ? '#7c3aed'
                     : (str_starts_with($ev->event_type, 'vault') ? '#dc2626'
                     : (str_starts_with($ev->event_type, 'auth') ? '#0891b2' : '#6b7280'));
        @endphp
        <div style="padding:8px 16px;border-bottom:1px solid var(--u-line);display:flex;gap:10px;align-items:flex-start;">
            <span style="background:{{ $evColor }}20;color:{{ $evColor }};font-size:9px;font-weight:800;padding:2px 7px;border-radius:4px;margin-top:2px;white-space:nowrap;">{{ $ev->event_type }}</span>
            <div style="flex:1;min-width:0;">
                <div style="font-size:11px;color:var(--u-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $ev->message }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ $ev->actor_email }} · {{ $ev->created_at->diffForHumans() }}</div>
            </div>
        </div>
        @endforeach
        @endif
    </section>

</div>

{{-- ─── Güvenlik Anomalileri (varsa) ─── --}}
@if($anomalies->isNotEmpty())
<section class="panel" style="padding:0;overflow:hidden;margin-bottom:12px;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
        <div style="font-weight:700;font-size:var(--tx-sm);">⚠ Güvenlik Anomalileri</div>
        <a href="/manager/system/security" style="font-size:11px;color:#1e40af;font-weight:700;text-decoration:none;">Güvenlik Paneli →</a>
    </div>
    @foreach($anomalies as $a)
    @php
        $sevColor = match($a['severity'] ?? 'info') { 'critical'=>'#dc2626', 'warning'=>'#d97706', default=>'#0891b2' };
        $sevBg    = match($a['severity'] ?? 'info') { 'critical'=>'#fee2e2', 'warning'=>'#fef9c3', default=>'#e0f2fe' };
    @endphp
    <div style="padding:10px 16px;border-bottom:1px solid var(--u-line);display:flex;gap:12px;align-items:center;">
        <span style="background:{{ $sevBg }};color:{{ $sevColor }};font-size:10px;font-weight:800;padding:3px 9px;border-radius:5px;white-space:nowrap;">{{ strtoupper($a['severity'] ?? 'info') }}</span>
        <div style="flex:1;">
            <div style="font-size:12px;font-weight:700;color:var(--u-text);">{{ $a['title'] ?? $a['type'] ?? '—' }}</div>
            <div style="font-size:10px;color:var(--u-muted);">{{ $a['description'] ?? '' }}</div>
        </div>
    </div>
    @endforeach
</section>
@endif

@endsection

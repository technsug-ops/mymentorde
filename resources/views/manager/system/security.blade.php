@extends('manager.layouts.app')
@section('title', 'Güvenlik Paneli')
@section('page_title', 'Güvenlik Paneli')

@push('head')
<style>
.sev-badge { display:inline-block;padding:3px 9px;border-radius:5px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em; }
.sev-critical { background:#fee2e2;color:#dc2626; }
.sev-warning  { background:#fef9c3;color:#92400e; }
.sev-info     { background:#e0f2fe;color:#0369a1; }
.tfa-row { display:flex;align-items:center;gap:10px;padding:8px 16px;border-bottom:1px solid var(--u-line); }
.tfa-row:last-child { border-bottom:none; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;gap:6px;align-items:center;margin-bottom:14px;font-size:11px;color:var(--u-muted);">
    <a href="/manager/system" style="color:#1e40af;text-decoration:none;font-weight:700;">Sistem Paneli</a>
    <span>›</span>
    <span>Güvenlik Paneli</span>
</div>

{{-- ─── KPI Özet ─── --}}
@php
    $critical = $anomalies->where('severity', 'critical')->count();
    $warning  = $anomalies->where('severity', 'warning')->count();
    $info     = $anomalies->where('severity', 'info')->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px;">
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid {{ $critical>0 ? '#dc2626' : '#16a34a' }};border-radius:10px;padding:12px 14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:{{ $critical>0 ? '#dc2626' : '#16a34a' }};">{{ $critical }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Kritik Anomali</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid {{ $warning>0 ? '#d97706' : '#16a34a' }};border-radius:10px;padding:12px 14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:{{ $warning>0 ? '#d97706' : '#16a34a' }};">{{ $warning }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Uyarı Anomali</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid {{ $twoFaPct>=80 ? '#16a34a' : ($twoFaPct>=50 ? '#d97706' : '#dc2626') }};border-radius:10px;padding:12px 14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:{{ $twoFaPct>=80 ? '#16a34a' : ($twoFaPct>=50 ? '#d97706' : '#dc2626') }};">%{{ $twoFaPct }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">2FA Benimseme</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #dc2626;border-radius:10px;padding:12px 14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:#dc2626;">{{ $twoFaDisabled }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">2FA Kapalı Kullanıcı</div>
    </div>
</div>

{{-- ─── Anomaliler + 2FA ─── --}}
<div class="grid2" style="gap:12px;margin-bottom:12px;">

    {{-- Güvenlik Anomalileri --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">⚠ Güvenlik Anomalileri</div>
        @if($anomalies->isEmpty())
        <div style="padding:40px;text-align:center;color:var(--u-muted);">
            <div style="font-size:28px;margin-bottom:8px;">✅</div>
            <div style="font-size:13px;font-weight:700;color:#16a34a;">Anomali tespit edilmedi.</div>
            <div style="font-size:11px;margin-top:4px;">Sistem normal görünüyor.</div>
        </div>
        @else
        @foreach($anomalies as $a)
        @php
            $sevClass = match($a['severity'] ?? 'info') { 'critical'=>'sev-critical', 'warning'=>'sev-warning', default=>'sev-info' };
        @endphp
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);">
            <div style="display:flex;gap:10px;align-items:center;margin-bottom:6px;">
                <span class="sev-badge {{ $sevClass }}">{{ $a['severity'] ?? 'info' }}</span>
                <span style="font-size:13px;font-weight:700;color:var(--u-text);">{{ $a['title'] ?? ($a['type'] ?? '—') }}</span>
            </div>
            @if(!empty($a['description']))
            <div style="font-size:11px;color:var(--u-muted);margin-bottom:4px;">{{ $a['description'] }}</div>
            @endif
            @if(!empty($a['details']) && is_array($a['details']))
            <div style="background:var(--u-bg);border-radius:6px;padding:8px 10px;font-size:10px;color:var(--u-muted);font-family:monospace;">
                @foreach($a['details'] as $k => $v)
                <div><span style="font-weight:700;">{{ $k }}:</span> {{ is_array($v) ? implode(', ', $v) : $v }}</div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
        @endif
    </section>

    {{-- 2FA Durumu --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:var(--tx-sm);">🔐 2FA Durumu</div>
            <div style="font-size:11px;color:var(--u-muted);">{{ $twoFaEnabled }}/{{ $twoFaRows->count() }} aktif</div>
        </div>
        {{-- Progress Bar --}}
        <div style="padding:10px 16px;border-bottom:1px solid var(--u-line);">
            <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--u-muted);margin-bottom:4px;">
                <span>2FA Benimseme Oranı</span><span>%{{ $twoFaPct }}</span>
            </div>
            <div style="height:8px;background:var(--u-line);border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:{{ $twoFaPct }}%;background:{{ $twoFaPct>=80 ? '#16a34a' : ($twoFaPct>=50 ? '#d97706' : '#dc2626') }};border-radius:999px;transition:width .4s;"></div>
            </div>
        </div>
        <div style="max-height:320px;overflow-y:auto;">
        @foreach($twoFaRows as $row)
        <div class="tfa-row">
            <div style="width:28px;height:28px;border-radius:50%;background:{{ $row->has_2fa ? '#16a34a' : '#dc2626' }};display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;flex-shrink:0;">
                {{ $row->has_2fa ? '✓' : '✕' }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:12px;font-weight:700;color:var(--u-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $row->user->name ?: $row->user->email }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ $row->user->role }}</div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                @if($row->has_2fa)
                <div style="font-size:10px;color:#16a34a;font-weight:700;">Aktif</div>
                @if($row->last_used)
                <div style="font-size:9px;color:var(--u-muted);">Son: {{ $row->last_used->diffForHumans() }}</div>
                @endif
                @else
                <div style="font-size:10px;color:#dc2626;font-weight:700;">Kapalı</div>
                @endif
            </div>
        </div>
        @endforeach
        </div>
    </section>

</div>

{{-- ─── Kritik Olay Geçmişi ─── --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
        <div style="font-weight:700;font-size:var(--tx-sm);">🔍 Kritik Güvenlik Olayları</div>
        <a href="/manager/audit-log" style="font-size:11px;color:#1e40af;font-weight:700;text-decoration:none;">Tüm Loglar →</a>
    </div>
    @if($criticalEvents->isEmpty())
    <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Kritik olay bulunamadı.</div>
    @else
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:var(--u-bg);">
                <th style="padding:8px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;white-space:nowrap;">Tarih</th>
                <th style="padding:8px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Olay Tipi</th>
                <th style="padding:8px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Aktör</th>
                <th style="padding:8px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Mesaj</th>
            </tr>
        </thead>
        <tbody>
        @foreach($criticalEvents as $ev)
        @php
            $evColor = str_starts_with($ev->event_type, 'gdpr') ? '#7c3aed'
                     : (str_starts_with($ev->event_type, 'vault') ? '#dc2626'
                     : (str_starts_with($ev->event_type, 'auth') ? '#0891b2' : '#6b7280'));
        @endphp
        <tr style="border-bottom:1px solid var(--u-line);">
            <td style="padding:8px 14px;color:var(--u-muted);white-space:nowrap;">{{ $ev->created_at->format('d.m.Y H:i') }}</td>
            <td style="padding:8px 14px;">
                <span style="background:{{ $evColor }}20;color:{{ $evColor }};font-size:10px;font-weight:800;padding:2px 7px;border-radius:4px;">{{ $ev->event_type }}</span>
            </td>
            <td style="padding:8px 14px;color:var(--u-text);">{{ $ev->actor_email ?: '—' }}</td>
            <td style="padding:8px 14px;color:var(--u-muted);max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $ev->message }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @endif
</section>

@endsection

@extends('manager.layouts.app')

@section('title', 'Manager – Eğitim Danışmanı Detay')
@section('page_title', 'Eğitim Danışmanı Detay')

@push('head')
<style>
/* Shared detail layout */
.gd-panel { padding:14px 16px !important; margin-bottom:12px !important; }
.gd-panel h2 { font-size:13px !important; font-weight:700 !important; color:var(--u-text,#0f172a); margin:0 0 10px; padding-bottom:8px; border-bottom:1px solid var(--u-line,#e5e9f0); letter-spacing:.2px; }

/* KPI tiles */
.gd-kpi-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin-bottom:12px; }
.gd-kpi { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:8px; padding:12px 14px; display:flex; flex-direction:column; gap:4px; }
.gd-kpi .lbl { font-size:11px; font-weight:600; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; }
.gd-kpi .val { font-size:24px; font-weight:700; color:var(--u-text,#0f172a); line-height:1.1; }
.gd-kpi.danger .val { color:#d33c3c; }
@media(max-width:900px){ .gd-kpi-row { grid-template-columns:repeat(2,1fr); } }

/* Senior header card */
.gd-header { padding:14px 16px !important; margin-bottom:12px !important; display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
.gd-header .avatar { width:48px; height:48px; flex-shrink:0; background:linear-gradient(135deg,#eef4ff,#dbeafe); border:1px solid #cfe0ff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; color:#1d4ed8; }
.gd-header .name { font-size:15px; font-weight:700; color:var(--u-text,#0f172a); }
.gd-header .email { font-size:12px; color:var(--u-muted,#64748b); margin-top:2px; }

/* Capacity bar */
.gd-capacity-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.gd-capacity-label { font-size:11px; font-weight:600; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; }
.gd-capacity-bar { background:var(--u-line,#e5e9f0); border-radius:4px; height:8px; overflow:hidden; }
.gd-capacity-fill { height:100%; transition:width .3s; }

/* Compact data table */
.gd-list-table { width:100%; border-collapse:collapse; font-size:12px; }
.gd-list-table thead th { padding:8px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; background:var(--u-bg,#f5f7fa); border-bottom:1px solid var(--u-line,#e5e9f0); }
.gd-list-table tbody td { padding:8px 10px; border-bottom:1px solid var(--u-line,#e5e9f0); vertical-align:top; }
.gd-list-table tbody tr:last-child td { border-bottom:none; }
.gd-list-table tbody tr:hover { background:#f8fafc; }
.gd-list-table .gd-pri { font-weight:600; color:var(--u-text,#0f172a); }
.gd-list-table .gd-sub { font-size:11px; color:var(--u-muted,#64748b); }
.gd-list-table .btn { font-size:11px !important; padding:4px 10px !important; min-height:28px !important; }

/* Pending guest list */
.gd-pending-list { display:flex; flex-direction:column; }
.gd-pending-item { padding:10px 12px; border-bottom:1px solid var(--u-line,#e5e9f0); display:flex; justify-content:space-between; align-items:flex-start; gap:8px; flex-wrap:wrap; }
.gd-pending-item:last-child { border-bottom:none; }
.gd-pending-item:hover { background:#f8fafc; }
.gd-pending-info { flex:1; min-width:0; }
.gd-pending-name { font-size:12px; font-weight:600; color:var(--u-text,#0f172a); }
.gd-pending-meta { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }
</style>
@endpush

@section('content')

<div style="margin-bottom:10px;">
    <a class="btn" href="/manager/seniors">← Eğitim Danışmanı Listesi</a>
</div>

{{-- Eğitim Danışmanı Başlık --}}
<section class="panel gd-header">
    <div class="avatar">
        {{ strtoupper(substr(preg_replace('/\s+/', '', ($user?->name ?? $email)), 0, 2)) }}
    </div>
    <div>
        <div class="name">{{ $user?->name ?: $email }}</div>
        <div class="email">{{ $email }}</div>
    </div>
</section>

{{-- KPI Çubuğu --}}
<div class="gd-kpi-row">
    <div class="gd-kpi"><div class="lbl">Aktif Öğrenci</div><div class="val">{{ $stats['active'] }}</div></div>
    <div class="gd-kpi"><div class="lbl">Arşiv</div><div class="val">{{ $stats['archived'] }}</div></div>
    <div class="gd-kpi"><div class="lbl">Bekleyen Aday Öğrenci</div><div class="val">{{ $stats['pending'] }}</div></div>
    <div class="gd-kpi {{ $stats['high_risk'] > 0 ? 'danger' : '' }}">
        <div class="lbl">Yüksek Risk</div>
        <div class="val">{{ $stats['high_risk'] }}</div>
    </div>
</div>

{{-- Kapasite Göstergesi --}}
@php
    $pct       = min(100, $stats['active'] > 0 ? round($stats['active'] / 20 * 100) : 0);
    $barColor  = $stats['active'] >= 20 ? '#d33c3c' : ($stats['active'] >= 15 ? '#d97706' : '#21a861');
    $capLabel  = $stats['active'] >= 20 ? 'Dolu' : ($stats['active'] >= 15 ? 'Yoğun' : 'Uygun');
@endphp
<section class="panel gd-panel">
    <div class="gd-capacity-head">
        <span class="gd-capacity-label">Kapasite (20 Öğrenci Limit)</span>
        <span class="badge" style="background:{{ $barColor }};color:#fff;font-size:10px;padding:3px 10px;">{{ $capLabel }} — {{ $stats['active'] }}/20</span>
    </div>
    <div class="gd-capacity-bar">
        <div class="gd-capacity-fill" style="width:{{ $pct }}%;background:{{ $barColor }};"></div>
    </div>
</section>

<div class="grid2">

    {{-- Aktif Öğrenciler --}}
    <section class="card gd-panel">
        <h2>Aktif Öğrenciler ({{ $stats['active'] }})</h2>
        @if($activeStudents->isEmpty())
            <div class="muted" style="padding:12px 0;font-size:12px;">Aktif öğrenci yok.</div>
        @else
            <div style="overflow-x:auto;">
                <table class="gd-list-table">
                    <thead>
                        <tr>
                            <th>Öğrenci</th>
                            <th>Şube</th>
                            <th>Risk</th>
                            <th>Ödeme</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeStudents as $s)
                            @php
                                $rc = match($s->risk_level) { 'high'=>'danger','medium'=>'warn','low'=>'ok',default=>'badge' };
                                $pc = match($s->payment_status) { 'paid'=>'ok','partial'=>'warn','pending'=>'info','overdue'=>'danger',default=>'badge' };
                                $gst = $guestByStudentId[$s->student_id] ?? null;
                            @endphp
                            <tr>
                                <td>
                                    <div class="gd-pri">{{ $s->student_id }}</div>
                                    @if($gst)<div class="gd-sub">{{ trim($gst->first_name.' '.$gst->last_name) }}</div>@endif
                                </td>
                                <td class="gd-sub">{{ $s->branch ?: '–' }}</td>
                                <td>
                                    @if($s->risk_level) <span class="badge {{ $rc }}">{{ ucfirst($s->risk_level) }}</span> @else <span class="gd-sub">–</span> @endif
                                </td>
                                <td>
                                    @if($s->payment_status) <span class="badge {{ $pc }}">{{ ucfirst($s->payment_status) }}</span> @else <span class="gd-sub">–</span> @endif
                                </td>
                                <td style="text-align:right;">
                                    <a class="btn" href="/manager/students/{{ urlencode($s->student_id) }}">Detay</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Bekleyen Aday Öğrenci'ler --}}
    <section class="card gd-panel">
        <h2>Bekleyen Aday Öğrenciler ({{ $stats['pending'] }})</h2>
        @if($pendingGuests->isEmpty())
            <div class="muted" style="padding:12px 0;font-size:12px;">Bekleyen başvuru yok.</div>
        @else
            <div class="gd-pending-list">
                @foreach($pendingGuests as $g)
                    @php
                        $bc = match($g->lead_status) { 'new'=>'info','contacted'=>'warn','qualified'=>'badge','converted'=>'ok','lost'=>'danger',default=>'badge' };
                        $bl = match($g->lead_status ?? '') { 'new'=>'Yeni','contacted'=>'İletişime Geçildi','qualified'=>'Nitelikli','converted'=>'Dönüştü','lost'=>'Kayboldu',default=>($g->lead_status ?: '–') };
                    @endphp
                    <div class="gd-pending-item">
                        <div class="gd-pending-info">
                            <div class="gd-pending-name">
                                #{{ $g->id }} {{ $g->first_name }} {{ $g->last_name }}
                                <span class="badge {{ $bc }}" style="margin-left:4px;font-size:10px;">{{ $bl }}</span>
                            </div>
                            <div class="gd-pending-meta">{{ $g->email }} · {{ optional($g->created_at)->format('d.m.Y') }}</div>
                        </div>
                        <a class="btn" style="font-size:11px;padding:4px 10px;min-height:28px;" href="/manager/guests/{{ $g->id }}">Detay</a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

</div>

{{-- Arşivlenen Öğrenciler (kapalı) --}}
@if($archivedStudents->isNotEmpty())
    <section class="card gd-panel" style="margin-top:12px;">
        <details>
            <summary style="cursor:pointer;font-weight:700;font-size:13px;padding:4px 0;color:var(--u-text,#0f172a);">
                Arşivlenen Öğrenciler ({{ $stats['archived'] }})
            </summary>
            <div style="overflow-x:auto;margin-top:12px;">
                <table class="gd-list-table">
                    <thead>
                        <tr>
                            <th>Öğrenci</th>
                            <th>Şube</th>
                            <th>Arşiv Tarihi</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($archivedStudents as $s)
                            @php $gst = $guestByStudentId[$s->student_id] ?? null; @endphp
                            <tr>
                                <td>
                                    <div class="gd-pri">{{ $s->student_id }}</div>
                                    @if($gst)<div class="gd-sub">{{ trim($gst->first_name.' '.$gst->last_name) }}</div>@endif
                                </td>
                                <td class="gd-sub">{{ $s->branch ?: '–' }}</td>
                                <td class="gd-sub">{{ optional($s->archived_at)->format('d.m.Y') ?: '–' }}</td>
                                <td style="text-align:right;">
                                    <a class="btn" href="/manager/students/{{ urlencode($s->student_id) }}">Detay</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </section>
@endif

@endsection

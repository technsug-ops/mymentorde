@extends('manager.layouts.app')

@section('title', 'Manager – Eğitim Danışmanı Detay')
@section('page_title', 'Eğitim Danışmanı Detay')

@section('content')

<div style="margin-bottom:10px;">
    <a class="btn" href="/manager/seniors">← Eğitim Danışmanı Listesi</a>
</div>

{{-- Eğitim Danışmanı Başlık --}}
<section class="panel" style="margin-bottom:12px;">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <div class="avatar" style="width:52px;height:52px;font-size:var(--tx-xl);flex-shrink:0;">
            <span>{{ strtoupper(substr(preg_replace('/\s+/', '', ($user?->name ?? $email)), 0, 2)) }}</span>
        </div>
        <div>
            <strong style="font-size:var(--tx-lg);">{{ $user?->name ?: $email }}</strong><br>
            <span class="muted">{{ $email }}</span>
        </div>
    </div>
</section>

{{-- KPI Çubuğu --}}
<div class="grid4" style="margin-bottom:12px;">
    <div class="panel"><div class="muted">Aktif Öğrenci</div><div class="kpi">{{ $stats['active'] }}</div></div>
    <div class="panel"><div class="muted">Arşiv</div><div class="kpi">{{ $stats['archived'] }}</div></div>
    <div class="panel"><div class="muted">Bekleyen Aday Öğrenci</div><div class="kpi">{{ $stats['pending'] }}</div></div>
    <div class="panel">
        <div class="muted">Yüksek Risk</div>
        <div class="kpi" style="{{ $stats['high_risk'] > 0 ? 'color:var(--u-danger,#d33c3c);' : '' }}">{{ $stats['high_risk'] }}</div>
    </div>
</div>

{{-- Kapasite Göstergesi --}}
@php
    $pct       = min(100, $stats['active'] > 0 ? round($stats['active'] / 20 * 100) : 0);
    $barColor  = $stats['active'] >= 20 ? 'var(--u-danger,#d33c3c)' : ($stats['active'] >= 15 ? 'var(--u-warn,#d97706)' : 'var(--u-ok,#21a861)');
    $capLabel  = $stats['active'] >= 20 ? 'Dolu' : ($stats['active'] >= 15 ? 'Yoğun' : 'Uygun');
@endphp
<section class="panel" style="margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
        <span class="muted">Kapasite (20 öğrenci limit)</span>
        <span class="badge" style="background:{{ $barColor }};color:#fff;">{{ $capLabel }} – {{ $stats['active'] }}/20</span>
    </div>
    <div style="background:var(--u-line,#e5e9f0);border-radius:4px;height:8px;overflow:hidden;">
        <div style="width:{{ $pct }}%;height:100%;background:{{ $barColor }};transition:width .3s;"></div>
    </div>
</section>

<div class="grid2">

    {{-- Aktif Öğrenciler --}}
    <section class="card">
        <h2>Aktif Öğrenciler ({{ $stats['active'] }})</h2>
        @if($activeStudents->isEmpty())
            <div class="muted" style="padding:12px 0;">Aktif öğrenci yok.</div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:var(--tx-xs);">
                    <thead>
                        <tr style="background:var(--u-bg,#f5f7fa);">
                            <th style="padding:6px 8px;text-align:left;">Öğrenci ID</th>
                            <th style="padding:6px 8px;text-align:left;">Şube</th>
                            <th style="padding:6px 8px;text-align:left;">Risk</th>
                            <th style="padding:6px 8px;text-align:left;">Ödeme</th>
                            <th style="padding:6px 8px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeStudents as $s)
                            @php
                                $rc = match($s->risk_level) { 'high'=>'danger','medium'=>'warn','low'=>'ok',default=>'badge' };
                                $pc = match($s->payment_status) { 'paid'=>'ok','partial'=>'warn','pending'=>'info','overdue'=>'danger',default=>'badge' };
                                $gst = $guestByStudentId[$s->student_id] ?? null;
                            @endphp
                            <tr style="border-bottom:1px solid var(--u-line,#e5e9f0);">
                                <td style="padding:6px 8px;font-weight:500;">
                                    {{ $s->student_id }}
                                    @if($gst)<div style="font-size:var(--tx-xs);color:var(--u-muted);">{{ trim($gst->first_name.' '.$gst->last_name) }}</div>@endif
                                </td>
                                <td style="padding:6px 8px;" class="muted">{{ $s->branch ?: '–' }}</td>
                                <td style="padding:6px 8px;">
                                    @if($s->risk_level) <span class="badge {{ $rc }}">{{ ucfirst($s->risk_level) }}</span> @else <span class="muted">–</span> @endif
                                </td>
                                <td style="padding:6px 8px;">
                                    @if($s->payment_status) <span class="badge {{ $pc }}">{{ ucfirst($s->payment_status) }}</span> @else <span class="muted">–</span> @endif
                                </td>
                                <td style="padding:6px 8px;">
                                    <a class="btn" style="font-size:var(--tx-xs);padding:3px 8px;" href="/manager/students/{{ urlencode($s->student_id) }}">Detay</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Bekleyen Aday Öğrenci'ler --}}
    <section class="card">
        <h2>Bekleyen Aday Öğrenci'ler ({{ $stats['pending'] }})</h2>
        @if($pendingGuests->isEmpty())
            <div class="muted" style="padding:12px 0;">Bekleyen başvuru yok.</div>
        @else
            <div class="list">
                @foreach($pendingGuests as $g)
                    @php
                        $bc = match($g->lead_status) { 'new'=>'info','contacted'=>'warn','qualified'=>'badge','converted'=>'ok','lost'=>'danger',default=>'badge' };
                        $bl = match($g->lead_status ?? '') { 'new'=>'Yeni','contacted'=>'İletişime Geçildi','qualified'=>'Nitelikli','converted'=>'Dönüştü','lost'=>'Kayboldu',default=>($g->lead_status ?: '–') };
                    @endphp
                    <div class="item">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:6px;">
                            <div>
                                <strong>#{{ $g->id }} {{ $g->first_name }} {{ $g->last_name }}</strong>
                                <span class="badge {{ $bc }}" style="margin-left:4px;">{{ $bl }}</span><br>
                                <span class="muted" style="font-size:var(--tx-xs);">{{ $g->email }} | {{ optional($g->created_at)->format('d.m.Y') }}</span>
                            </div>
                            <a class="btn" style="font-size:var(--tx-xs);padding:3px 8px;" href="/manager/guests/{{ $g->id }}">Detay</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

</div>

{{-- Arşivlenen Öğrenciler (kapalı) --}}
@if($archivedStudents->isNotEmpty())
    <section class="card" style="margin-top:12px;">
        <details>
            <summary style="cursor:pointer;font-weight:600;padding:4px 0;">
                Arşivlenen Öğrenciler ({{ $stats['archived'] }})
            </summary>
            <div style="overflow-x:auto;margin-top:10px;">
                <table style="width:100%;border-collapse:collapse;font-size:var(--tx-xs);">
                    <thead>
                        <tr style="background:var(--u-bg,#f5f7fa);">
                            <th style="padding:6px 8px;text-align:left;">Öğrenci ID</th>
                            <th style="padding:6px 8px;text-align:left;">Şube</th>
                            <th style="padding:6px 8px;text-align:left;">Arşiv Tarihi</th>
                            <th style="padding:6px 8px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($archivedStudents as $s)
                            @php $gst = $guestByStudentId[$s->student_id] ?? null; @endphp
                            <tr style="border-bottom:1px solid var(--u-line,#e5e9f0);">
                                <td style="padding:6px 8px;font-weight:500;">
                                    {{ $s->student_id }}
                                    @if($gst)<div style="font-size:var(--tx-xs);color:var(--u-muted);">{{ trim($gst->first_name.' '.$gst->last_name) }}</div>@endif
                                </td>
                                <td style="padding:6px 8px;" class="muted">{{ $s->branch ?: '–' }}</td>
                                <td style="padding:6px 8px;" class="muted">{{ optional($s->archived_at)->format('d.m.Y') ?: '–' }}</td>
                                <td style="padding:6px 8px;">
                                    <a class="btn" style="font-size:var(--tx-xs);padding:3px 8px;" href="/manager/students/{{ urlencode($s->student_id) }}">Detay</a>
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

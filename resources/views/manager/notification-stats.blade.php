@extends('manager.layouts.app')
@section('title','Bildirim İstatistikleri')
@section('page_title', 'Bildirim İstatistikleri')

@push('head')
<style>
/* KPI */
.ns-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
@media(max-width:900px){ .ns-kpi-grid { grid-template-columns:1fr 1fr; } }
.ns-kpi { background:#fff; border:1px solid #e2e8f0; border-top:3px solid #1e40af; border-radius:10px; padding:14px 16px; }
.ns-kpi-val   { font-size:28px; font-weight:900; line-height:1; margin-bottom:4px; }
.ns-kpi-label { font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }

/* Cards grid — min-width:0 prevents grid blowout */
.ns-cards { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
@media(max-width:900px){ .ns-cards { grid-template-columns:1fr; } }
.ns-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; min-width:0; overflow:hidden; }
.ns-card h3 { margin:0 0 12px; font-size:13px; }

/* Row = CSS Grid (3 columns: label | bar | count) — never overflows */
.ns-row {
    display:grid;
    grid-template-columns:130px 1fr 48px;
    gap:8px;
    align-items:center;
    padding:8px 0;
    border-bottom:1px solid #f1f5f9;
}
.ns-row-cat {
    grid-template-columns:160px 1fr 48px;
}
.ns-row:last-child { border-bottom:none; }

.ns-label { display:flex; align-items:center; gap:5px; overflow:hidden; min-width:0; }
.ns-label .badge { flex-shrink:0; font-size:10px; }
.ns-label-ch { font-size:11px; color:#64748b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ns-label-cat { font-size:12px; color:#374151; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

.ns-bar { height:6px; background:#f1f5f9; border-radius:999px; overflow:hidden; width:100%; }
.ns-fill { height:100%; border-radius:999px; }

.ns-cnt { text-align:right; font-size:12px; font-weight:700; color:#0f172a; white-space:nowrap; }
</style>
@endpush

@section('content')
@php
$sent   = (int) $stats['total_sent_30d'];
$failed = (int) $stats['total_failed_30d'];
$total  = $sent + $failed;
$successRate  = $total > 0 ? round($sent / $total * 100, 1) : 0;
$successColor = $successRate >= 90 ? '#16a34a' : ($successRate >= 70 ? '#d97706' : '#dc2626');
$maxCat = $stats['by_category']->max('cnt') ?: 1;
$maxCh  = $stats['by_channel']->max('cnt')  ?: 1;
@endphp

<div class="page-header">
    <div>
        <h1 style="margin:0">Bildirim İstatistikleri</h1>
        <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">Son 30 günlük bildirim akışı</div>
    </div>
    <a href="{{ url('/config') }}" class="btn alt">⚙ Ayarlar</a>
</div>

{{-- KPI --}}
<div class="ns-kpi-grid">
    <div class="ns-kpi">
        <div class="ns-kpi-val" style="color:#1e40af;">{{ number_format($sent) }}</div>
        <div class="ns-kpi-label">Gönderildi</div>
    </div>
    <div class="ns-kpi" style="border-top-color:#dc2626;">
        <div class="ns-kpi-val" style="color:#dc2626;">{{ number_format($failed) }}</div>
        <div class="ns-kpi-label">Başarısız</div>
    </div>
    <div class="ns-kpi" style="border-top-color:#d97706;">
        <div class="ns-kpi-val" style="color:#d97706;">{{ $stats['pending'] }}</div>
        <div class="ns-kpi-label">Bekleyen</div>
    </div>
    <div class="ns-kpi" style="border-top-color:#16a34a;">
        <div class="ns-kpi-val" style="color:#16a34a;">{{ $stats['scheduled_active'] }}</div>
        <div class="ns-kpi-label">Aktif Zamanlanan</div>
    </div>
</div>

{{-- Başarı Oranı --}}
@if($total > 0)
<div class="ns-card" style="display:grid;grid-template-columns:auto 1fr auto auto;align-items:center;gap:12px;margin-bottom:16px;padding:12px 16px;">
    <div style="font-size:var(--tx-xs);font-weight:700;color:#374151;white-space:nowrap;">Başarı Oranı</div>
    <div style="height:10px;background:#f1f5f9;border-radius:999px;overflow:hidden;">
        <div style="width:{{ $successRate }}%;height:100%;background:{{ $successColor }};border-radius:999px;"></div>
    </div>
    <div style="font-size:var(--tx-lg);font-weight:900;color:{{ $successColor }};white-space:nowrap;">%{{ $successRate }}</div>
    <div class="muted" style="font-size:var(--tx-xs);white-space:nowrap;">{{ number_format($sent) }} / {{ number_format($total) }}</div>
</div>
@endif

{{-- İki kart --}}
<div class="ns-cards">

    {{-- Kanal & Durum --}}
    <div class="ns-card">
        <h3>Kanal & Durum Dağılımı</h3>
        @forelse($stats['by_channel'] as $row)
        @php
            $st        = $row->status;
            $fillColor = match($st) { 'sent' => '#16a34a', 'failed' => '#dc2626', 'skipped' => '#94a3b8', default => '#2563eb' };
            $badgeCls  = match($st) { 'sent' => 'ok', 'failed' => 'danger', 'skipped' => '', default => 'info' };
            $pct       = max(2, round($row->cnt / $maxCh * 100));
        @endphp
        <div class="ns-row">
            <div class="ns-label">
                <span class="badge {{ $badgeCls }}" style="font-size:var(--tx-xs);">{{ $st }}</span>
                <span class="ns-label-ch">{{ $row->channel }}</span>
            </div>
            <div class="ns-bar"><div class="ns-fill" style="width:{{ $pct }}%;background:{{ $fillColor }};"></div></div>
            <div class="ns-cnt">{{ number_format($row->cnt) }}</div>
        </div>
        @empty
        <div class="muted" style="font-size:var(--tx-xs);padding:20px 0;text-align:center;">Veri yok.</div>
        @endforelse
    </div>

    {{-- Kategori Top 10 --}}
    <div class="ns-card">
        <h3>Kategori Dağılımı <span class="muted" style="font-size:var(--tx-xs);font-weight:400;">Top 10</span></h3>
        @forelse($stats['by_category'] as $row)
        @php $pct = max(2, round($row->cnt / $maxCat * 100)); @endphp
        <div class="ns-row ns-row-cat">
            <div class="ns-label-cat" title="{{ $row->category ?: '—' }}">{{ $row->category ?: '—' }}</div>
            <div class="ns-bar"><div class="ns-fill" style="width:{{ $pct }}%;background:#1e40af;"></div></div>
            <div class="ns-cnt">{{ number_format($row->cnt) }}</div>
        </div>
        @empty
        <div class="muted" style="font-size:var(--tx-xs);padding:20px 0;text-align:center;">Veri yok.</div>
        @endforelse
    </div>

</div>

@if($stats['scheduled_active'] > 0)
<div class="ns-card" style="display:flex;align-items:center;justify-content:space-between;gap:12px;border-left:3px solid #1e40af;padding:14px 18px;">
    <div>
        <div style="font-size:var(--tx-sm);font-weight:700;color:#0f172a;">⏰ Zamanlanmış Bildirimler</div>
        <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">{{ $stats['scheduled_active'] }} aktif bildirim kuyruğa alınmış.</div>
    </div>
    <a href="{{ url('/config') }}" class="btn alt" style="white-space:nowrap;flex-shrink:0;">Yönet →</a>
</div>
@endif

@endsection

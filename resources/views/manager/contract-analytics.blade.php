@extends('manager.layouts.app')
@section('title','Sözleşme Analitik')
@section('page_title', 'Sözleşme Analitik')

@push('head')
<style>
/* KPI */
.ca-kpi-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:16px; }
@media(max-width:900px){ .ca-kpi-strip { grid-template-columns:1fr 1fr; } }
.ca-kpi { background:#fff; border:1px solid #e2e8f0; border-top:3px solid #1e40af; border-radius:10px; padding:14px 16px; }
.ca-kpi-val   { font-size:30px; font-weight:900; color:#0f172a; line-height:1; margin-bottom:4px; }
.ca-kpi-val.warn { color:#d97706; }
.ca-kpi-val.ok   { color:#16a34a; }
.ca-kpi-label { font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }

/* Two-card grid — min-width:0 prevents overflow */
.ca-cards { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media(max-width:900px){ .ca-cards { grid-template-columns:1fr; } }
.ca-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; min-width:0; overflow:hidden; }
.ca-card h3 { margin:0 0 12px; font-size:13px; }

/* CSS Grid rows — 4 columns: badge | bar | % | count */
.ca-row {
    display:grid;
    grid-template-columns:150px 1fr 34px 32px;
    gap:8px;
    align-items:center;
    padding:8px 0;
    border-bottom:1px solid #f1f5f9;
}
.ca-row:last-child { border-bottom:none; }
.ca-badge-cell { min-width:0; overflow:hidden; }
.ca-bar   { height:6px; background:#f1f5f9; border-radius:999px; overflow:hidden; width:100%; }
.ca-fill  { height:100%; border-radius:999px; }
.ca-pct   { font-size:10px; color:#94a3b8; text-align:right; white-space:nowrap; }
.ca-cnt   { font-size:12px; font-weight:700; color:#0f172a; text-align:right; white-space:nowrap; }

/* Monthly trend */
.ca-month-row  { padding:10px 0; border-bottom:1px solid #f1f5f9; }
.ca-month-row:last-child { border-bottom:none; }
.ca-month-head { font-size:12px; font-weight:700; color:#0f172a; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
.ca-month-dot  { width:8px; height:8px; border-radius:50%; background:#1e40af; flex-shrink:0; }
.ca-month-tags { display:flex; gap:5px; flex-wrap:wrap; padding-left:14px; }
</style>
@endpush

@section('content')
@php
$total = array_sum($statusCounts);
$labels = [
    'not_requested'   => 'Talep Edilmedi',
    'pending_manager' => 'Yönetici Onayı',
    'requested'       => 'Talep Edildi',
    'signed_uploaded' => 'İmzalandı',
    'approved'        => 'Onaylandı',
    'rejected'        => 'Reddedildi',
    'cancelled'       => 'İptal',
    'reopen_requested'=> 'Yeniden Açılma',
];
$badgeCls = [
    'approved'        => 'ok',
    'rejected'        => 'danger',
    'cancelled'       => 'danger',
    'requested'       => 'info',
    'signed_uploaded' => 'warn',
    'pending_manager' => 'warn',
    'reopen_requested'=> 'warn',
];
$barColors = [
    'approved'        => '#16a34a',
    'rejected'        => '#dc2626',
    'cancelled'       => '#dc2626',
    'requested'       => '#2563eb',
    'signed_uploaded' => '#d97706',
    'pending_manager' => '#d97706',
    'not_requested'   => '#94a3b8',
    'reopen_requested'=> '#7c3aed',
];
$approvalRate = $total > 0 ? round(($statusCounts['approved'] ?? 0) / $total * 100, 1) : 0;
$approvalColor = $approvalRate >= 70 ? '#16a34a' : ($approvalRate >= 40 ? '#d97706' : '#dc2626');
@endphp

<div class="page-header">
    <div>
        <h1 style="margin:0">Sözleşme Analitik</h1>
        <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">Sözleşme süreç verileri ve durum dağılımı</div>
    </div>
    <a href="{{ url('/manager/contract-template') }}" class="btn alt">← Sözleşme Yönetimi</a>
</div>

{{-- KPI --}}
<div class="ca-kpi-strip">
    <div class="ca-kpi">
        <div class="ca-kpi-val">{{ $total }}</div>
        <div class="ca-kpi-label">Toplam Sözleşme</div>
    </div>
    <div class="ca-kpi" style="border-top-color:#d97706;">
        <div class="ca-kpi-val warn">{{ $pendingDecision }}</div>
        <div class="ca-kpi-label">Onay Bekleyen</div>
    </div>
    <div class="ca-kpi" style="border-top-color:{{ $avgApprovalDays !== null && (float)$avgApprovalDays <= 3 ? '#16a34a' : '#1e40af' }};">
        <div class="ca-kpi-val {{ $avgApprovalDays !== null && (float)$avgApprovalDays <= 3 ? 'ok' : '' }}">
            {{ $avgApprovalDays !== null ? round((float)$avgApprovalDays,1).' gün' : '—' }}
        </div>
        <div class="ca-kpi-label">Ort. Onay Süresi</div>
    </div>
</div>

{{-- Onay Oranı --}}
@if($total > 0)
<div class="ca-card" style="display:grid;grid-template-columns:auto 1fr auto auto;align-items:center;gap:12px;margin-bottom:16px;padding:12px 16px;">
    <div style="font-size:var(--tx-xs);font-weight:700;color:#374151;white-space:nowrap;">Onay Oranı</div>
    <div style="height:10px;background:#f1f5f9;border-radius:999px;overflow:hidden;">
        <div style="width:{{ $approvalRate }}%;height:100%;background:{{ $approvalColor }};border-radius:999px;"></div>
    </div>
    <div style="font-size:var(--tx-lg);font-weight:900;color:{{ $approvalColor }};white-space:nowrap;">%{{ $approvalRate }}</div>
    <div class="muted" style="font-size:var(--tx-xs);white-space:nowrap;">{{ $statusCounts['approved'] ?? 0 }} / {{ $total }}</div>
</div>
@endif

<div class="ca-cards">

    {{-- Durum Dağılımı --}}
    <div class="ca-card">
        <h3>Durum Dağılımı</h3>
        @foreach($statusCounts as $status => $cnt)
        @php $pct = $total > 0 ? max(1, round($cnt / $total * 100)) : 0; @endphp
        <div class="ca-row">
            <div class="ca-badge-cell">
                <span class="badge {{ $badgeCls[$status] ?? '' }}" style="font-size:var(--tx-xs);">
                    {{ $labels[$status] ?? $status }}
                </span>
            </div>
            <div class="ca-bar">
                <div class="ca-fill" style="width:{{ $pct }}%;background:{{ $barColors[$status] ?? '#94a3b8' }};"></div>
            </div>
            <div class="ca-pct">%{{ $pct }}</div>
            <div class="ca-cnt">{{ $cnt }}</div>
        </div>
        @endforeach
        @if(empty($statusCounts))
        <div class="muted" style="font-size:var(--tx-xs);padding:16px 0;text-align:center;">Henüz veri yok.</div>
        @endif
    </div>

    {{-- Aylık Trend --}}
    <div class="ca-card">
        <h3>Aylık Trend <span class="muted" style="font-size:var(--tx-xs);font-weight:400;">(Son 6 Ay)</span></h3>
        @php $byMonth = $monthlyTrend->groupBy('month'); @endphp
        @forelse($byMonth as $month => $rows)
        <div class="ca-month-row">
            <div class="ca-month-head">
                <span class="ca-month-dot"></span>
                {{ $month }}
                <span class="muted" style="font-size:var(--tx-xs);font-weight:400;">— {{ $rows->sum('cnt') }} kayıt</span>
            </div>
            <div class="ca-month-tags">
                @foreach($rows as $row)
                <span class="badge {{ $badgeCls[$row->contract_status ?? ''] ?? '' }}" style="font-size:var(--tx-xs);">
                    {{ $labels[$row->contract_status ?? ''] ?? $row->contract_status }}: <strong>{{ $row->cnt }}</strong>
                </span>
                @endforeach
            </div>
        </div>
        @empty
        <div class="muted" style="font-size:var(--tx-xs);padding:16px 0;text-align:center;">Henüz veri yok.</div>
        @endforelse
    </div>

</div>
@endsection

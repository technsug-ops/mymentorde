@extends('manager.layouts.app')
@section('title', 'Hedef vs Gerçek')
@section('page_title', 'Hedef vs Gerçek')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1>Hedef vs Gerçek</h1>
        <div class="u-muted" style="font-size:var(--tx-sm);">Dönem: {{ $period }}</div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/manager/targets?period={{ $period }}" class="btn alt">Hedefleri Düzenle</a>
        <form method="GET">
            <select name="period" onchange="this.form.submit()" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:4px;">
                @foreach($periods as $p)
                <option value="{{ $p }}" {{ $p === $period ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

@if(!$companyTarget)
<div class="card">
    <p class="u-muted">Bu dönem için firma hedefi tanımlanmamış. <a href="/manager/targets?period={{ $period }}">Hedef ekle →</a></p>
</div>
@else

{{-- Firma Geneli Hedef vs Gerçek --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-title">Firma Geneli — {{ $period }}</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
        @foreach($targetVsActual as $key => $row)
        @php
            $labels = ['revenue' => 'Gelir (€)', 'conversions' => 'Dönüşüm', 'new_guests' => 'Yeni Başvuru'];
            $pct = $row['pct'];
            $color = $pct >= 100 ? 'ok' : ($pct >= 70 ? 'info' : ($pct >= 40 ? 'warn' : 'danger'));
        @endphp
        <div style="padding:16px;border:1px solid var(--u-line);border-radius:6px;">
            <div class="u-muted" style="font-size:var(--tx-xs);text-transform:uppercase;">{{ $labels[$key] ?? $key }}</div>
            <div style="font-size:var(--tx-xl);font-weight:600;margin:6px 0;">
                @if($key === 'revenue') € {{ number_format($row['actual'], 0, ',', '.') }} @else {{ $row['actual'] }} @endif
            </div>
            <div class="u-muted" style="font-size:var(--tx-xs);">Hedef: @if($key === 'revenue')€ {{ number_format($row['target'], 0, ',', '.') }} @else {{ $row['target'] }} @endif</div>
            <div style="margin-top:8px;background:var(--u-line);height:6px;border-radius:3px;">
                <div style="width:{{ min(100, $pct) }}%;height:6px;border-radius:3px;background:var(--u-{{ $color }});"></div>
            </div>
            <span class="badge {{ $color }}" style="margin-top:6px;display:inline-block;">{{ $pct }}%</span>
        </div>
        @endforeach
    </div>
</div>

@endif

{{-- Senior Hedefleri --}}
@if($seniorTargets->isNotEmpty())
<div class="card">
    <div class="card-title">Senior Hedefleri — {{ $period }}</div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <thead>
                <tr style="border-bottom:2px solid var(--u-line);">
                    <th style="padding:8px 10px;text-align:left;">Senior</th>
                    <th style="padding:8px 10px;text-align:center;">Dönüşüm H.</th>
                    <th style="padding:8px 10px;text-align:center;">Başvuru H.</th>
                    <th style="padding:8px 10px;text-align:center;">Belge H.</th>
                    <th style="padding:8px 10px;text-align:center;">Sözleşme H.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($seniorTargets as $st)
                <tr style="border-bottom:1px solid var(--u-line);">
                    <td style="padding:8px 10px;">{{ $st->senior_email }}</td>
                    <td style="padding:8px 10px;text-align:center;">{{ $st->target_conversions }}</td>
                    <td style="padding:8px 10px;text-align:center;">{{ $st->target_new_guests }}</td>
                    <td style="padding:8px 10px;text-align:center;">{{ $st->target_doc_reviews }}</td>
                    <td style="padding:8px 10px;text-align:center;">{{ $st->target_contracts_signed }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- KPI Özeti --}}
<div class="card" style="margin-top:20px;">
    <div class="card-title">Dönem KPI Özeti</div>
    <div class="grid4">
        <div><div class="u-muted" style="font-size:var(--tx-xs);">AKTİF ÖĞRENCİ</div><div class="kpi">{{ $stats['active_students'] }}</div></div>
        <div><div class="u-muted" style="font-size:var(--tx-xs);">GELİR</div><div class="kpi">€ {{ number_format($stats['monthly_revenue'], 0, ',', '.') }}</div></div>
        <div><div class="u-muted" style="font-size:var(--tx-xs);">DÖNÜŞÜM ORANI</div><div class="kpi">{{ $stats['conversion_rate'] }}%</div></div>
        <div><div class="u-muted" style="font-size:var(--tx-xs);">RİSK SKORU</div><div class="kpi">{{ $stats['risk_score'] }}</div></div>
    </div>
</div>
@endsection

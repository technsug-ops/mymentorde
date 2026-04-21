@extends('manager.layouts.app')
@section('title', 'Gelir Analitik')
@section('page_title', 'Gelir Analitik')

@section('content')

@include('partials.manager-hero', [
    'label' => 'Gelir Analitik',
    'title' => 'Gelir Dashboard',
    'sub'   => 'Paket bazlı gelir, danışman performansı ve tahsilat oranları. Hangi paketler en çok döndürüyor, hangi danışman en çok kazandırıyor?',
    'icon'  => '💶',
    'bg'    => 'https://images.unsplash.com/photo-1579621970590-9d624316904b?w=1400&q=80',
    'tone'  => 'green',
    'stats' => [
        ['icon' => '💰', 'text' => '€' . number_format(($totalEarned ?? 0), 0, ',', '.') . ' toplam'],
        ['icon' => '⏳', 'text' => '€' . number_format(($totalPending ?? 0), 0, ',', '.') . ' bekleyen'],
        ['icon' => '📦', 'text' => '€' . number_format(($totalPackagePrice ?? 0), 0, ',', '.') . ' paket'],
        ['icon' => '📊', 'text' => '%' . ($collectionRate ?? 0) . ' tahsilat'],
    ],
])

<div class="page-header" style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:8px;align-items:center;">
        <input type="date" name="start_date" value="{{ $filters['start_date'] }}" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:4px;">
        <input type="date" name="end_date" value="{{ $filters['end_date'] }}" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:4px;">
        <button class="btn" type="submit">Uygula</button>
    </form>
</div>

<div class="grid4" style="margin-bottom:20px;">
    <div class="card"><div class="u-muted" style="font-size:var(--tx-xs);">TOPLAM GELİR</div><div class="kpi">€ {{ number_format($totalEarned, 0, ',', '.') }}</div></div>
    <div class="card"><div class="u-muted" style="font-size:var(--tx-xs);">BEKLEYEN</div><div class="kpi">€ {{ number_format($totalPending, 0, ',', '.') }}</div></div>
    <div class="card"><div class="u-muted" style="font-size:var(--tx-xs);">PAKET FİYATI</div><div class="kpi">€ {{ number_format($totalPackagePrice, 0, ',', '.') }}</div></div>
    <div class="card"><div class="u-muted" style="font-size:var(--tx-xs);">TAHSİLAT ORANI</div><div class="kpi">{{ $collectionRate }}%</div></div>
</div>

<div class="grid2" style="margin-bottom:20px;">
    {{-- Paket Bazlı --}}
    <div class="card">
        <div class="card-title">Paket Bazlı Gelir</div>
        <div class="list">
            @forelse($byPackage as $row)
            <div class="item">
                <div style="flex:1;">
                    <div style="font-weight:500;">{{ $row->package ?: '(Belirsiz)' }}</div>
                    <div class="u-muted" style="font-size:var(--tx-xs);">{{ $row->student_count }} öğrenci</div>
                </div>
                <div style="text-align:right;">
                    <div>€ {{ number_format($row->earned, 0, ',', '.') }}</div>
                    <div class="u-muted" style="font-size:var(--tx-xs);">Bekleyen: € {{ number_format($row->pending, 0, ',', '.') }}</div>
                </div>
            </div>
            @empty
            <div class="item"><span class="u-muted">Veri yok.</span></div>
            @endforelse
        </div>
    </div>

    {{-- Eğitim Danışmanı Bazlı --}}
    <div class="card">
        <div class="card-title">Eğitim Danışmanı Bazlı Gelir</div>
        <div class="list">
            @forelse($bySenior->sortByDesc('earned') as $row)
            <div class="item">
                <div style="flex:1;">
                    <div style="font-weight:500;">{{ $row->senior_email ?: '(Atanmamış)' }}</div>
                    <div class="u-muted" style="font-size:var(--tx-xs);">{{ $row->student_count }} öğrenci</div>
                </div>
                <div style="text-align:right;">
                    <div>€ {{ number_format($row->earned, 0, ',', '.') }}</div>
                    <div class="u-muted" style="font-size:var(--tx-xs);">Bekleyen: € {{ number_format($row->pending, 0, ',', '.') }}</div>
                </div>
            </div>
            @empty
            <div class="item"><span class="u-muted">Veri yok.</span></div>
            @endforelse
        </div>
    </div>
</div>

{{-- Aylık Trend --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-title">Aylık Gelir Trendi (Son 12 Ay)</div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <thead>
                <tr style="border-bottom:2px solid var(--u-line);">
                    <th style="padding:8px 10px;text-align:left;">Ay</th>
                    <th style="padding:8px 10px;text-align:right;">Tahsil Edilen</th>
                    <th style="padding:8px 10px;text-align:right;">Bekleyen</th>
                    <th style="padding:8px 10px;text-align:right;">Toplam</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyTrend as $row)
                <tr style="border-bottom:1px solid var(--u-line);">
                    <td style="padding:8px 10px;">{{ $row['label'] }}</td>
                    <td style="padding:8px 10px;text-align:right;">€ {{ number_format($row['earned'], 0, ',', '.') }}</td>
                    <td style="padding:8px 10px;text-align:right;">€ {{ number_format($row['pending'], 0, ',', '.') }}</td>
                    <td style="padding:8px 10px;text-align:right;font-weight:500;">€ {{ number_format($row['earned'] + $row['pending'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Dealer Komisyon --}}
@if($dealerCommissions && $dealerCommissions->dealer_count > 0)
<div class="card">
    <div class="card-title">Dealer Komisyon Özeti</div>
    <div class="grid2">
        <div><div class="u-muted" style="font-size:var(--tx-xs);">TOPLAM KOMİSYON</div><div class="kpi" style="font-size:var(--tx-xl);">€ {{ number_format($dealerCommissions->total_commission, 0, ',', '.') }}</div></div>
        <div><div class="u-muted" style="font-size:var(--tx-xs);">AKTİF DEALER</div><div class="kpi" style="font-size:var(--tx-xl);">{{ $dealerCommissions->dealer_count }}</div></div>
    </div>
</div>
@endif
@endsection

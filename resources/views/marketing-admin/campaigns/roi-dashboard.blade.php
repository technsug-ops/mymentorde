@extends('marketing-admin.layouts.app')
@section('title', 'Kampanya ROI Analizi')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1>Kampanya ROI Analizi</h1>
    <form method="GET" style="display:flex;gap:8px;align-items:center;">
        <input type="date" name="start" value="{{ $start->toDateString() }}" class="input-sm">
        <span class="u-muted">–</span>
        <input type="date" name="end" value="{{ $end->toDateString() }}" class="input-sm">
        <button type="submit" class="btn alt">Filtrele</button>
    </form>
</div>

{{-- Kanal Özeti --}}
@if($byChannel->isNotEmpty())
<div class="grid{{ min(4, $byChannel->count()) }}" style="margin-bottom:20px;">
    @foreach($byChannel as $ch)
    <div class="card">
        <div class="u-muted" style="font-size:var(--tx-xs);text-transform:uppercase;">{{ $ch['channel'] ?? 'Bilinmiyor' }}</div>
        <div class="kpi">{{ $ch['campaigns'] }}</div>
        <div style="font-size:var(--tx-xs);">Kampanya</div>
        <div style="margin-top:8px;font-size:var(--tx-sm);">
            <span class="u-muted">Harcama:</span> €{{ number_format($ch['total_spent'], 0) }}<br>
            <span class="u-muted">Gelir:</span> €{{ number_format($ch['total_revenue'], 0) }}<br>
            <span class="u-muted">Ort. ROI:</span>
            <span class="badge {{ $ch['avg_roi'] >= 0 ? 'ok' : 'danger' }}">
                %{{ $ch['avg_roi'] }}
            </span>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Kampanya Tablosu --}}
<div class="card">
    <div class="card-title">Kampanya Detayı (ROI Sıralı)</div>
    @if($roi->isEmpty())
        <p class="u-muted">Bu tarih aralığında kampanya bulunamadı.</p>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <thead>
                <tr style="border-bottom:1px solid var(--u-line);">
                    <th style="text-align:left;padding:8px 10px;">Kampanya</th>
                    <th style="text-align:left;padding:8px 10px;">Kanal</th>
                    <th style="text-align:right;padding:8px 10px;">Harcama</th>
                    <th style="text-align:right;padding:8px 10px;">Lead</th>
                    <th style="text-align:right;padding:8px 10px;">Dönüşüm</th>
                    <th style="text-align:right;padding:8px 10px;">Gelir</th>
                    <th style="text-align:right;padding:8px 10px;">CPL</th>
                    <th style="text-align:right;padding:8px 10px;">ROI</th>
                    <th style="text-align:right;padding:8px 10px;">Dön. Oranı</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roi as $row)
                <tr style="border-bottom:1px solid var(--u-line);">
                    <td style="padding:8px 10px;font-weight:500;">{{ $row['name'] }}</td>
                    <td style="padding:8px 10px;"><span class="badge info">{{ $row['channel'] }}</span></td>
                    <td style="padding:8px 10px;text-align:right;">€{{ number_format($row['spent'], 0) }}</td>
                    <td style="padding:8px 10px;text-align:right;">{{ $row['leads'] }}</td>
                    <td style="padding:8px 10px;text-align:right;">{{ $row['converted'] }}</td>
                    <td style="padding:8px 10px;text-align:right;">€{{ number_format($row['revenue'], 0) }}</td>
                    <td style="padding:8px 10px;text-align:right;">{{ $row['cost_per_lead'] !== null ? '€'.number_format($row['cost_per_lead'],2) : '—' }}</td>
                    <td style="padding:8px 10px;text-align:right;">
                        @if($row['roi_pct'] !== null)
                            <span class="badge {{ $row['roi_pct'] >= 0 ? 'ok' : 'danger' }}">%{{ $row['roi_pct'] }}</span>
                        @else —
                        @endif
                    </td>
                    <td style="padding:8px 10px;text-align:right;">%{{ $row['conversion_rate'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — ROI Dashboard</h3>
        <span class="det-chev">▼</span>
    </summary>
    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
        <li><strong>ROI = (Gelir − Harcama) / Harcama × 100</strong> — pozitif ROI karlı kampanyayı gösterir</li>
        <li>ROI %100+ → kampanya maliyetinin 2 katı gelir üretildi</li>
        <li>Negatif ROI'lı kampanyaları durdur veya hedef kitleyi daralt</li>
        <li>En yüksek ROI'lı kampanya formatını diğer kanallara uygula</li>
    </ul>
</details>

</div>
@endsection

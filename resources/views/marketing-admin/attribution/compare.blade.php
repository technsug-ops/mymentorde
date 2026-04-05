@extends('marketing-admin.layouts.app')

@section('title', 'Attribution Model Karşılaştırma')

@section('content')

<div class="pill-links" style="margin-bottom:16px;">
    <a class="btn alt" href="/mktg-admin/attribution">Attribution Raporu</a>
    <a class="btn" href="/mktg-admin/attribution/compare">Model Karşılaştırma</a>
</div>

<div style="display:flex;gap:8px;align-items:center;margin-bottom:14px;">
    <span style="font-size:var(--tx-sm);font-weight:600;">Dönem:</span>
    <a href="?days=30" class="btn {{ $days == 30 ? '' : 'alt' }}" style="padding:4px 10px;font-size:var(--tx-xs);">30 Gün</a>
    <a href="?days=60" class="btn {{ $days == 60 ? '' : 'alt' }}" style="padding:4px 10px;font-size:var(--tx-xs);">60 Gün</a>
    <a href="?days=90" class="btn {{ $days == 90 ? '' : 'alt' }}" style="padding:4px 10px;font-size:var(--tx-xs);">90 Gün</a>
</div>

<div class="card">
    <div class="label" style="font-weight:600;margin-bottom:14px;">Kanal × Model Karşılaştırma</div>

    @if(empty($channels))
    <div class="muted">Dönüşüm verisi yok.</div>
    @else
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
        <thead>
            <tr style="border-bottom:2px solid var(--u-line);">
                <th style="text-align:left;padding:8px 10px;color:var(--u-muted);font-weight:600;">Kanal</th>
                @foreach($modelLabels as $m => $label)
                <th style="text-align:right;padding:8px 10px;color:var(--u-muted);font-weight:600;">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($channels as $channel)
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:8px 10px;font-weight:500;">{{ $channel }}</td>
                @foreach(array_keys($modelLabels) as $m)
                @php
                $modelData = collect($results[$m] ?? [])->firstWhere('channel', $channel);
                $pct = $modelData ? $modelData['share_pct'] : 0;
                @endphp
                <td style="text-align:right;padding:8px 10px;font-weight:{{ $pct > 0 ? '600' : '400' }};color:{{ $pct > 30 ? 'var(--u-ok)' : ($pct > 10 ? 'var(--u-brand)' : 'var(--u-muted)') }}">
                    {{ $pct > 0 ? $pct . '%' : '—' }}
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @endif

<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Kampanya Karşılaştırma</h3>
        <span class="det-chev">▼</span>
    </summary>
    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
        <li>İki kampanyayı aynı dönemde karşılaştırarak bütçe verimliliğini ölç</li>
        <li>Daha yüksek conversion ama düşük bütçeli kampanya → ölçeklendir</li>
        <li>CPL (Cost per Lead) karşılaştırması en önemli maliyet göstergesidir</li>
        <li>Mevsimsel etkiyi elemek için aynı takvim dönemini seç</li>
    </ul>
</details>

</div>

@endsection

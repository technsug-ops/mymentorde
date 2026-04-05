@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/social/accounts">Hesaplar</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/social/posts">Postlar</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/social/metrics">Metrikler</a>
<a class="btn" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/social/metrics/monthly/{{ $period }}">Aylık Detay</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/social/calendar">Takvim</a>
@endsection

@section('title', 'Aylık Sosyal Medya')
@section('page_subtitle', 'Aylık Detay — {{ $period }} dönemi hesap bazlı büyüme ve performans özeti')

@section('content')
@php
$totalGrowth   = collect($rows ?? [])->sum('followers_growth');
$avgGrowthRate = collect($rows ?? [])->avg('followers_growth_rate');
$totalEnd      = collect($rows ?? [])->sum('followers_end');
$accountCount  = collect($rows ?? [])->count();

// Dönem navigasyonu
$prevPeriod = \Illuminate\Support\Carbon::parse($period.'-01')->subMonth()->format('Y-m');
$nextPeriod = \Illuminate\Support\Carbon::parse($period.'-01')->addMonth()->format('Y-m');
$isCurrentMonth = $period === now()->format('Y-m');
@endphp
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.pl-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.pl-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.pl-stat:last-child { border-right:none; }
.pl-val   { font-size:22px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.pl-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; min-width:860px; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); white-space:nowrap; }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:middle; }
.tl-tbl tr:last-child td { border-bottom:none; }
.tl-tbl tbody tr:hover { background:color-mix(in srgb,var(--u-brand,#1e40af) 3%,var(--u-card,#fff)); }

.growth-bar { height:6px; border-radius:3px; background:var(--u-line,#e2e8f0); overflow:hidden; margin-top:4px; }
.growth-fill { height:100%; border-radius:3px; background:var(--u-ok,#16a34a); }
</style>

<div style="display:grid;gap:12px;">

    {{-- Dönem Navigasyonu --}}
    <div class="card" style="padding:10px 16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <a href="/mktg-admin/social/metrics/monthly/{{ $prevPeriod }}" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;">← {{ $prevPeriod }}</a>
                <span style="font-weight:700;font-size:var(--tx-base);color:var(--u-brand,#1e40af);">{{ $period }}</span>
                @if(!$isCurrentMonth)
                <a href="/mktg-admin/social/metrics/monthly/{{ $nextPeriod }}" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;">{{ $nextPeriod }} →</a>
                @else
                <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);padding:5px 12px;">Güncel ay</span>
                @endif
            </div>
            <a href="/mktg-admin/social/metrics?period={{ $period }}" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;">Genel Metrik Görünümü</a>
        </div>
    </div>

    {{-- KPI Bar --}}
    <div class="pl-stats">
        <div class="pl-stat">
            <div class="pl-val">{{ $accountCount }}</div>
            <div class="pl-lbl">Hesap</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val">{{ number_format($totalEnd, 0, ',', '.') }}</div>
            <div class="pl-lbl">Toplam Takipçi (ay sonu)</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:{{ $totalGrowth >= 0 ? 'var(--u-ok,#16a34a)' : 'var(--u-danger,#dc2626)' }};">
                {{ $totalGrowth >= 0 ? '+' : '' }}{{ number_format($totalGrowth, 0, ',', '.') }}
            </div>
            <div class="pl-lbl">Toplam Büyüme</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="font-size:var(--tx-lg);color:{{ $avgGrowthRate >= 0 ? 'var(--u-ok,#16a34a)' : 'var(--u-danger,#dc2626)' }};">
                {{ $avgGrowthRate >= 0 ? '+' : '' }}{{ number_format($avgGrowthRate, 2, ',', '.') }}%
            </div>
            <div class="pl-lbl">Ort. Büyüme Oranı</div>
        </div>
    </div>

    {{-- Tablo --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            Hesap Bazlı Özet — {{ $period }}
        </div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th>Hesap</th>
                    <th style="width:90px;">Platform</th>
                    <th style="width:110px;text-align:right;">Takipçi (başlangıç)</th>
                    <th style="width:110px;text-align:right;">Takipçi (bitiş)</th>
                    <th style="width:130px;">Büyüme</th>
                    <th>Top Post</th>
                    <th style="width:100px;">Top Metrik</th>
                    <th style="width:120px;">Hesaplanma</th>
                </tr></thead>
                <tbody>
                @forelse(($rows ?? []) as $row)
                @php
                    $growth     = (int)$row->followers_growth;
                    $growthRate = (float)$row->followers_growth_rate;
                    $maxGrowth  = collect($rows)->max('followers_end') ?: 1;
                    $barPct     = min(100, round(($row->followers_end / $maxGrowth) * 100));
                    $platformColors = [
                        'instagram' => '#e1306c',
                        'facebook'  => '#1877f2',
                        'twitter'   => '#1da1f2',
                        'linkedin'  => '#0a66c2',
                        'tiktok'    => '#010101',
                        'youtube'   => '#ff0000',
                    ];
                    $pColor = $platformColors[strtolower($row->platform)] ?? 'var(--u-brand,#1e40af)';
                @endphp
                <tr>
                    <td>
                        <strong>{{ $row->account->account_name ?? '—' }}</strong>
                        @if($row->account->handle ?? null)
                        <br><span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row->account->handle }}</span>
                        @endif
                    </td>
                    <td>
                        <span style="display:inline-block;background:color-mix(in srgb,{{ $pColor }} 10%,var(--u-card,#fff));color:{{ $pColor }};border-radius:6px;padding:2px 8px;font-size:var(--tx-xs);font-weight:700;">
                            {{ ucfirst($row->platform) }}
                        </span>
                    </td>
                    <td style="text-align:right;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                        {{ number_format((int)$row->followers_start, 0, ',', '.') }}
                    </td>
                    <td style="text-align:right;">
                        <strong>{{ number_format((int)$row->followers_end, 0, ',', '.') }}</strong>
                        <div class="growth-bar" style="width:80px;margin-left:auto;">
                            <div class="growth-fill" style="width:{{ $barPct }}%;"></div>
                        </div>
                    </td>
                    <td>
                        <span style="font-weight:700;color:{{ $growth >= 0 ? 'var(--u-ok,#16a34a)' : 'var(--u-danger,#dc2626)' }};">
                            {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 0, ',', '.') }}
                        </span>
                        <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                            ({{ $growthRate >= 0 ? '+' : '' }}{{ number_format($growthRate, 2, ',', '.') }}%)
                        </span>
                    </td>
                    <td style="max-width:200px;">
                        @if($row->topPost)
                        <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">#{{ $row->topPost->id }}</span>
                        <span style="font-size:var(--tx-xs);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;" title="{{ $row->topPost->caption ?? '' }}">
                            {{ \Illuminate\Support\Str::limit((string)($row->topPost->caption ?? ''), 40) }}
                        </span>
                        @else
                        <span style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">—</span>
                        @endif
                    </td>
                    <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row->top_post_metric ?: '—' }}</td>
                    <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                        {{ $row->calculated_at ? \Illuminate\Support\Carbon::parse($row->calculated_at)->format('d.m.Y H:i') : '—' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--u-muted,#64748b);">{{ $period }} için aylık veri bulunamadı.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Aylık Sosyal Medya Özeti</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li>Bu sayfa seçilen ayın hesap bazlı özetini listeler.</li>
            <li><strong>Top Post</strong>, erişim + etkileşim değeri en yüksek kayıttan seçilir.</li>
            <li>Farklı aylara geçmek için üstteki dönem navigasyonunu kullan.</li>
            <li>Büyüme barı, o hesabın ay sonu takipçi sayısının toplam içindeki göreli ağırlığını gösterir.</li>
        </ol>
    </details>

</div>
@endsection

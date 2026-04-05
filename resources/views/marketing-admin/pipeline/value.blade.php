@extends('marketing-admin.layouts.app')

@section('title', 'Pipeline Value')
@section('page_subtitle', 'Pipeline Value — agirlikli potansiyel gelir ve durum bazli deger analizi')

@section('topbar-actions')
@php $pipelineIsAdmin = in_array(auth()->user()?->role, ['marketing_admin','sales_admin','manager','system_admin']); @endphp
<a class="btn {{ request()->is('mktg-admin/pipeline') && !request()->is('mktg-admin/pipeline/*') ? '' : 'alt' }}" href="/mktg-admin/pipeline" style="font-size:var(--tx-xs);padding:6px 12px;">Genel Bakış</a>
@if($pipelineIsAdmin)
<a class="btn {{ request()->is('mktg-admin/pipeline/value') ? '' : 'alt' }}" href="/mktg-admin/pipeline/value" style="font-size:var(--tx-xs);padding:6px 12px;">Pipeline Value</a>
<a class="btn {{ request()->is('mktg-admin/pipeline/loss-analysis') ? '' : 'alt' }}" href="/mktg-admin/pipeline/loss-analysis" style="font-size:var(--tx-xs);padding:6px 12px;">Loss Analysis</a>
<a class="btn {{ request()->is('mktg-admin/pipeline/conversion-time') ? '' : 'alt' }}" href="/mktg-admin/pipeline/conversion-time" style="font-size:var(--tx-xs);padding:6px 12px;">Conversion Time</a>
@endif
<a class="btn {{ request()->is('mktg-admin/pipeline/re-engagement') ? '' : 'alt' }}" href="/mktg-admin/pipeline/re-engagement" style="font-size:var(--tx-xs);padding:6px 12px;">Re-Engagement</a>
@if($pipelineIsAdmin)
<a class="btn {{ request()->is('mktg-admin/pipeline/score-analysis') ? '' : 'alt' }}" href="/mktg-admin/pipeline/score-analysis" style="font-size:var(--tx-xs);padding:6px 12px;">Score Analizi</a>
@endif
@endsection

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.pl-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.pl-stat  { flex:1; padding:14px 18px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.pl-stat:last-child { border-right:none; }
.pl-val   { font-size:22px; font-weight:700; line-height:1.1; color:var(--u-brand,#1e40af); }
.pl-val.ok { color:var(--u-ok,#16a34a); }
.pl-val.warn { color:var(--u-warn,#d97706); }
.pl-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:3px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl tr:last-child td { border-bottom:none; }
</style>

<div style="display:grid;gap:12px;">

    {{-- KPI Bar --}}
    <div class="pl-stats">
        <div class="pl-stat">
            <div class="pl-val">{{ $summary['open_count'] ?? 0 }}</div>
            <div class="pl-lbl">Aktif Lead</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val">{{ number_format((float)($summary['average_package'] ?? 0), 2, '.', ',') }} EUR</div>
            <div class="pl-lbl">Ort. Paket</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val warn">{{ number_format((float)($summary['open_potential_value'] ?? 0), 0, '.', ',') }} EUR</div>
            <div class="pl-lbl">Potansiyel Gelir</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val ok">{{ number_format((float)($summary['realized_revenue'] ?? 0), 0, '.', ',') }} EUR</div>
            <div class="pl-lbl">Gerçekleşen Gelir</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val">{{ number_format((float)($summary['pending_revenue'] ?? 0), 0, '.', ',') }} EUR</div>
            <div class="pl-lbl">Bekleyen Gelir</div>
        </div>
    </div>

    {{-- Durum Bazlı Ağırlıklı Değer --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Durum Bazlı Ağırlıklı Değer</div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th>Durum</th>
                    <th style="width:80px;text-align:right;">Sayı</th>
                    <th style="width:90px;text-align:right;">Ağırlık</th>
                    <th style="width:160px;text-align:right;">Ağırlıklı Değer</th>
                </tr></thead>
                <tbody>
                    @forelse(($statusRows ?? []) as $row)
                    <tr>
                        <td style="font-weight:500;">{{ $row['status'] }}</td>
                        <td style="text-align:right;color:var(--u-muted,#64748b);">{{ $row['count'] }}</td>
                        <td style="text-align:right;color:var(--u-muted,#64748b);">{{ number_format((float)$row['weight'] * 100, 0) }}%</td>
                        <td style="text-align:right;font-weight:700;color:var(--u-brand,#1e40af);">{{ number_format((float)$row['weighted_value'], 2, '.', ',') }} EUR</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rehber --}}
    <details class="card" style="margin-top:12px;">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="padding-top:12px;">
            <h4 style="margin:0 0 10px;font-size:var(--tx-sm);font-weight:700;">Pipeline Değer Analizi</h4>
            <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 12px;">Aktif pipeline'daki potansiyel geliri hesapla ve tahmin et.</p>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li><strong>Ağırlıklı Değer:</strong> Lead × Paket Fiyatı × Aşama Ağırlığı — new %15, contacted %25, qualified %55, sales_ready %70, champion %90</li>
                <li><strong>Potansiyel Gelir:</strong> Tüm açık leadlerin ağırlıklı toplam değeri — "pipeline'da ne kadar para var?"</li>
                <li><strong>Gerçekleşen Gelir:</strong> Tahsil edilen toplam tutar</li>
                <li><strong>Bekleyen Gelir:</strong> Onaylanmış ama henüz tahsil edilmemiş — yüksekse tahsilat takibi gerekir</li>
                <li>Potansiyel Gelir gerçekleşenden çok üzerindeyse → Loss Analysis sekmesinde kayıp noktasını bul</li>
            </ul>
        </div>
    </details>

</div>
@endsection

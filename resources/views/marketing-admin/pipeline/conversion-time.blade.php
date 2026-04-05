@extends('marketing-admin.layouts.app')

@section('title', 'Conversion Time')
@section('page_subtitle', 'Conversion Time — lead girisinden sozlesme onayina kadar gecen sure analizi')

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
.pl-val   { font-size:26px; font-weight:700; line-height:1.1; color:var(--u-brand,#1e40af); }
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
            <div class="pl-val">{{ $summary['count'] ?? 0 }}</div>
            <div class="pl-lbl">Dönüştürülen</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val">{{ number_format((float)($summary['avg_days'] ?? 0), 1, '.', ',') }}</div>
            <div class="pl-lbl">Ort. Gün</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val">{{ number_format((float)($summary['median_days'] ?? 0), 1, '.', ',') }}</div>
            <div class="pl-lbl">Medyan</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val">{{ number_format((float)($summary['p90_days'] ?? 0), 1, '.', ',') }}</div>
            <div class="pl-lbl">P90</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="font-size:var(--tx-lg);">{{ $summary['min_days'] ?? 0 }} / {{ $summary['max_days'] ?? 0 }}</div>
            <div class="pl-lbl">Min / Maks gün</div>
        </div>
    </div>

    {{-- Kaynağa Göre + Aya Göre --}}
    <div class="grid2" style="align-items:stretch;">

        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Kaynağa Göre Dönüşüm Süresi</div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Kaynak</th>
                        <th style="width:70px;text-align:right;">Sayı</th>
                        <th style="width:100px;text-align:right;">Ort. Gün</th>
                    </tr></thead>
                    <tbody>
                        @forelse(($bySource ?? []) as $row)
                        <tr>
                            <td>{{ $row['source'] ?: '(doğrudan)' }}</td>
                            <td style="text-align:right;color:var(--u-muted,#64748b);">{{ $row['count'] }}</td>
                            <td style="text-align:right;font-weight:700;color:var(--u-brand,#1e40af);">{{ number_format((float)$row['avg_days'], 1) }} gün</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Aya Göre Dönüşüm Süresi</div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Ay</th>
                        <th style="width:70px;text-align:right;">Sayı</th>
                        <th style="width:100px;text-align:right;">Ort. Gün</th>
                    </tr></thead>
                    <tbody>
                        @forelse(($byMonth ?? []) as $row)
                        <tr>
                            <td>{{ $row['month'] }}</td>
                            <td style="text-align:right;color:var(--u-muted,#64748b);">{{ $row['count'] }}</td>
                            <td style="text-align:right;font-weight:700;color:var(--u-brand,#1e40af);">{{ number_format((float)$row['avg_days'], 1) }} gün</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Rehber --}}
    <details class="card" style="margin-top:12px;">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="padding-top:12px;">
            <h4 style="margin:0 0 10px;font-size:var(--tx-sm);font-weight:700;">Dönüşüm Süresi Analizi</h4>
            <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 12px;">Bir adayın lead'den kayıtlı öğrenciye dönüşmesi ortalama kaç gün sürüyor?</p>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li><strong>Medyan:</strong> Ortanca değer — ortalamadan daha güvenilir. Ort. ≫ Medyan ise birkaç çok yavaş case ortalamayı bozuyor demektir.</li>
                <li><strong>P90:</strong> Leadlerin %90'ı bu gün sayısı içinde kapanıyor. SLA hedefi belirlemek için kullanılır.</li>
                <li><strong>Huni Aşaması Süreleri:</strong> Her aşamada ortalama bekleme süresi — darboğazı tespit et.</li>
                <li><strong>Kaynağa Göre:</strong> Referral/dealer kanalı genellikle daha hızlı kapanır — bütçe önceliklendirmesi için veri sağlar.</li>
                <li><strong>Aylık Trend:</strong> Ortalama düşüyorsa süreç iyileşiyor; artıyorsa tıkanıklık araştır.</li>
            </ul>
            <div style="margin-top:10px;padding:10px;background:color-mix(in srgb,var(--u-brand,#1e40af) 5%,var(--u-card,#fff));border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                💡 <strong>Benchmark:</strong> Lead → Kayıt için sektör ortalaması 30-60 gündür. 90 gün üstü takılı adaylar için re-engagement workflow'u başlat.
            </div>
        </div>
    </details>

</div>
@endsection

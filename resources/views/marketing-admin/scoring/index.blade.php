@extends('marketing-admin.layouts.app')

@section('title', 'Lead Scoring')

@section('page_subtitle', 'Lead Scoring — tier dağılımı, dönüşüm ilişkisi ve aktivite takibi')

@section('topbar-actions')
<a class="btn {{ request()->is('mktg-admin/scoring') && !request()->is('mktg-admin/scoring/*') ? '' : 'alt' }}" href="/mktg-admin/scoring" style="font-size:var(--tx-xs);padding:6px 12px;">Genel Bakış</a>
<a class="btn {{ request()->is('mktg-admin/scoring/leaderboard') ? '' : 'alt' }}" href="/mktg-admin/scoring/leaderboard" style="font-size:var(--tx-xs);padding:6px 12px;">Liderlik Tablosu</a>
<a class="btn {{ request()->is('mktg-admin/scoring/config') ? '' : 'alt' }}" href="/mktg-admin/scoring/config" style="font-size:var(--tx-xs);padding:6px 12px;">Kural Yapılandırma</a>
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

.sc-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.sc-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.sc-stat:last-child { border-right:none; }
.sc-val   { font-size:22px; font-weight:700; line-height:1.1; }
.sc-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl tr:last-child td { border-bottom:none; }
</style>

<div style="display:grid;gap:12px;">

    {{-- KPI Bar --}}
    <div class="sc-stats">
        @foreach(['champion' => 'Champion', 'sales_ready' => 'Sales Ready', 'hot' => 'Hot', 'warm' => 'Warm', 'cold' => 'Cold'] as $tier => $label)
        <div class="sc-stat">
            <div class="sc-val" style="color:{{ $tierColors[$tier] ?? 'var(--u-muted,#64748b)' }}">{{ $tierDistribution[$tier] ?? 0 }}</div>
            <div class="sc-lbl">{{ $label }}</div>
        </div>
        @endforeach
    </div>

    <div class="grid2">

        {{-- Tier → Dönüşüm İlişkisi --}}
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Tier → Dönüşüm Oranı</div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Tier</th>
                        <th style="width:70px;text-align:right;">Lead</th>
                        <th style="width:80px;text-align:right;">Onaylanan</th>
                        <th style="width:70px;text-align:right;">Oran</th>
                    </tr></thead>
                    <tbody>
                        @forelse($tierConversion as $row)
                        <tr>
                            <td style="font-weight:500;">{{ $tierLabels[$row['tier']] ?? $row['tier'] }}</td>
                            <td style="text-align:right;">{{ $row['total'] }}</td>
                            <td style="text-align:right;color:var(--u-ok,#16a34a);">{{ $row['converted'] }}</td>
                            <td style="text-align:right;">
                                <span class="badge {{ $row['conv_rate'] >= 20 ? 'ok' : ($row['conv_rate'] >= 10 ? 'warn' : 'info') }}">{{ $row['conv_rate'] }}%</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Son 7 Gün Aktivitesi --}}
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Son 7 Gün Scoring Aktivitesi</div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Tarih</th>
                        <th style="width:70px;text-align:right;">Olay</th>
                        <th style="width:100px;text-align:right;">Toplam Puan</th>
                    </tr></thead>
                    <tbody>
                        @forelse($recentActivity as $row)
                        <tr>
                            <td style="font-size:var(--tx-xs);">{{ $row->date }}</td>
                            <td style="text-align:right;">{{ $row->events }}</td>
                            <td style="text-align:right;font-weight:600;color:{{ $row->total_points >= 0 ? 'var(--u-ok,#16a34a)' : 'var(--u-danger,#dc2626)' }}">
                                {{ $row->total_points >= 0 ? '+' : '' }}{{ $row->total_points }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Son 7 günde aktivite yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Ortalama Puan by Tier --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Tier Bazlı Ortalama Puan</div>
        <div class="list">
            @foreach($avgByTier as $row)
            @php $color = $tierColors[$row->lead_score_tier] ?? 'var(--u-muted,#64748b)'; @endphp
            <div class="item">
                <span style="flex:2;font-weight:500;">{{ $tierLabels[$row->lead_score_tier] ?? $row->lead_score_tier }}</span>
                <span style="flex:1;text-align:right;color:var(--u-muted,#64748b);">{{ $row->total }} lead</span>
                <span style="width:100px;text-align:right;font-weight:700;font-size:var(--tx-base);color:{{ $color }}">{{ round($row->avg_score, 1) }} puan</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Lead Scoring</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li><strong>Champion / Sales Ready / Hot / Warm / Cold:</strong> Sistemin otomatik hesapladığı puan aralıklarına göre tier dağılımı.</li>
            <li><strong>Tier → Dönüşüm Oranı:</strong> Hangi tier'daki leadlerin kaçı dönüşüyor? Yüksek tier = yüksek dönüşüm beklentisi.</li>
            <li><strong>Aktivite:</strong> Son 7 günde kaç puan hareketi oldu — artış pozitif momentum gösterir.</li>
            <li><strong>Ortalama Puan:</strong> Her tier'in puan merkezi — tier sınırları config ekranından ayarlanabilir.</li>
            <li>Liderlik Tablosu sekmesinden en yüksek puanlı leadleri görün; Kural Yapılandırma sekmesinden puan kurallarını düzenleyin.</li>
        </ol>
    </details>

</div>
@endsection

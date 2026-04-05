@extends('marketing-admin.layouts.app')

@section('title', 'Re-Engagement')
@section('page_subtitle', 'Re-Engagement — sessiz leadleri yeniden aktivasyona hazir havuz ve tier dagilimi')

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
            <div class="pl-val" style="color:var(--u-warn,#d97706);">{{ $totalPool }}</div>
            <div class="pl-lbl">Re-Engagement Havuzu</div>
        </div>
        @foreach(['champion' => 'Champion', 'sales_ready' => 'Sales Ready', 'hot' => 'Hot', 'warm' => 'Warm', 'cold' => 'Cold'] as $t => $l)
        @php $tier = collect($byTier)->firstWhere('tier', $t); @endphp
        <div class="pl-stat">
            <div class="pl-val">{{ $tier['count'] ?? 0 }}</div>
            <div class="pl-lbl">{{ $l }}</div>
        </div>
        @endforeach
    </div>

    {{-- Kaynak + Tier Dağılımı --}}
    <div class="grid2" style="align-items:stretch;">

        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Kaynak Bazlı Dağılım</div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Kaynak</th>
                        <th style="width:80px;text-align:right;">Sayı</th>
                    </tr></thead>
                    <tbody>
                        @forelse($bySource as $row)
                        <tr>
                            <td>{{ $row['source'] ?: '(doğrudan)' }}</td>
                            <td style="text-align:right;"><span class="badge warn">{{ $row['count'] }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="2" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Tier Bazlı Dağılım</div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Tier</th>
                        <th style="width:80px;text-align:right;">Sayı</th>
                    </tr></thead>
                    <tbody>
                        @forelse($byTier as $row)
                        <tr>
                            <td>{{ $tierLabels[$row['tier']] ?? $row['tier'] }}</td>
                            <td style="text-align:right;"><span class="badge info">{{ $row['count'] }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="2" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Re-Engagement Havuzu Tablosu --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            Re-Engagement Havuzu
            <span style="font-weight:400;font-size:var(--tx-xs);margin-left:6px;">İlk 30 kayıt</span>
        </div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th style="width:50px;">ID</th>
                    <th>Aday</th>
                    <th>Kaynak</th>
                    <th style="width:80px;text-align:right;">Puan</th>
                    <th style="width:110px;text-align:center;">Tier</th>
                    <th style="width:100px;text-align:right;">Hareketsiz</th>
                </tr></thead>
                <tbody>
                    @forelse($poolRows as $row)
                    <tr>
                        <td style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">{{ $row['id'] }}</td>
                        <td style="font-weight:500;">{{ $row['name'] }}</td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row['source'] ?: '—' }}</td>
                        <td style="text-align:right;font-weight:700;">{{ $row['score'] }}</td>
                        <td style="text-align:center;">
                            <span class="badge {{ in_array($row['tier'], ['hot','sales_ready','champion']) ? 'warn' : 'info' }}">
                                {{ $tierLabels[$row['tier']] ?? $row['tier'] }}
                            </span>
                        </td>
                        <td style="text-align:right;font-size:var(--tx-xs);color:var(--u-danger,#dc2626);font-weight:600;">{{ $row['days_inactive'] }} gün</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Re-engagement havuzu boş.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Re-Engagement</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li><strong>Re-Engagement Havuzu:</strong> 14–60 gün arasında sessiz kalmış, henüz arşivlenmemiş leadler. Tamamen soğumadılar — doğru yaklaşımla geri kazanılabilirler.</li>
            <li><strong>Yüksek Tier + Uzun Sessizlik:</strong> Champion/Sales Ready tier'daki sessiz leadler öncelikli hedef — puan yüksek ama temas yok.</li>
            <li><strong>Kaynak Dağılımı:</strong> Hangi kanaldan gelen leadler daha çok sessiz kalıyor? Bu kanal veya mesaj stratejisinde sorun olabilir.</li>
            <li>Haftalık inceleme önerilir. Öncelik sırası: yüksek puan → uzun sessizlik → düşük hareketsizlik gün sayısı.</li>
            <li>Yeniden temas için: kişiselleştirilmiş e-posta, WhatsApp mesajı veya özel kampanya teklifi.</li>
        </ol>
    </details>

</div>
@endsection

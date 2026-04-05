@extends('marketing-admin.layouts.app')

@section('title', 'Sales Pipeline')
@section('page_subtitle', 'Sales Pipeline — lead dağılımı, dönüşüm oranı ve huni özeti')

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
.pl-val.ok { color:var(--u-ok,#16a34a); }
.pl-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:3px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl tr:last-child td { border-bottom:none; }

.dist-row { display:flex; flex-direction:column; gap:5px; padding:8px 0; border-bottom:1px solid var(--u-line,#e2e8f0); }
.dist-row:last-child { border-bottom:none; padding-bottom:0; }
.dist-row:first-child { padding-top:0; }
.dist-meta { display:flex; justify-content:space-between; align-items:center; }
.dist-bar-bg { background:var(--u-line,#e2e8f0); border-radius:4px; height:5px; overflow:hidden; }
.dist-bar-fill { height:100%; border-radius:4px; transition:width .3s ease; }
</style>

@php
$statusTotal = collect($statusRows ?? [])->sum('total');
$sourceTotal = collect($sourceRows ?? [])->sum('total');

$statusColors = [
    'new'                => 'var(--u-brand,#1e40af)',
    'contacted'          => '#7c3aed',
    'qualified'          => 'var(--u-warn,#d97706)',
    'meeting_scheduled'  => '#0891b2',
    'proposal_sent'      => '#d97706',
    'converted'          => 'var(--u-ok,#16a34a)',
    'lost'               => 'var(--u-danger,#dc2626)',
];
@endphp

<div style="display:grid;gap:12px;">

    {{-- KPI Bar --}}
    <div class="pl-stats">
        <div class="pl-stat">
            <div class="pl-val">{{ $summary['total_guests'] ?? 0 }}</div>
            <div class="pl-lbl">Toplam Lead</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val">{{ $summary['open_guests'] ?? 0 }}</div>
            <div class="pl-lbl">Aktif Pipeline</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val ok">{{ $summary['converted_guests'] ?? 0 }}</div>
            <div class="pl-lbl">Dönüştürülen</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val ok">{{ number_format((float)($summary['conversion_rate'] ?? 0), 1, '.', ',') }}%</div>
            <div class="pl-lbl">Dönüşüm Oranı</div>
        </div>
    </div>

    <div class="grid2" style="align-items:stretch;">

        {{-- Lead Durum Dağılımı --}}
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                Lead Durum Dağılımı
                <span style="font-weight:400;font-size:var(--tx-xs);margin-left:6px;">{{ $statusTotal }} toplam</span>
            </div>
            @forelse(($statusRows ?? []) as $row)
            @php
                $pct   = $statusTotal > 0 ? round($row['total'] / $statusTotal * 100) : 0;
                $color = $statusColors[$row['status']] ?? 'var(--u-brand,#1e40af)';
            @endphp
            <div class="dist-row">
                <div class="dist-meta">
                    <span style="font-size:var(--tx-sm);font-weight:500;">{{ $row['status'] }}</span>
                    <span style="font-size:var(--tx-sm);font-weight:700;color:{{ $color }};">
                        {{ $row['total'] }}
                        <span style="font-weight:400;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $pct }}%</span>
                    </span>
                </div>
                <div class="dist-bar-bg">
                    <div class="dist-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                </div>
            </div>
            @empty
            <div style="color:var(--u-muted,#64748b);font-size:var(--tx-sm);">Veri yok.</div>
            @endforelse
        </div>

        {{-- Lead Kaynak Dağılımı --}}
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                Lead Kaynak Dağılımı
                <span style="font-weight:400;font-size:var(--tx-xs);margin-left:6px;">{{ $sourceTotal }} toplam</span>
            </div>
            @forelse(($sourceRows ?? []) as $row)
            @php $pct = $sourceTotal > 0 ? round($row['total'] / $sourceTotal * 100) : 0; @endphp
            <div class="dist-row">
                <div class="dist-meta">
                    <span style="font-size:var(--tx-sm);font-weight:500;">{{ $row['source'] ?: '(doğrudan)' }}</span>
                    <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-brand,#1e40af);">
                        {{ $row['total'] }}
                        <span style="font-weight:400;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $pct }}%</span>
                    </span>
                </div>
                <div class="dist-bar-bg">
                    <div class="dist-bar-fill" style="width:{{ $pct }}%;background:var(--u-brand,#1e40af);"></div>
                </div>
            </div>
            @empty
            <div style="color:var(--u-muted,#64748b);font-size:var(--tx-sm);">Veri yok.</div>
            @endforelse
        </div>

    </div>

    {{-- Son Lead Kayıtları --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Son Açık Lead Kayıtları</div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th style="width:50px;">ID</th>
                    <th>Aday</th>
                    <th>Kaynak</th>
                    <th style="width:140px;">Durum</th>
                    <th style="width:160px;">Kayıt Tarihi</th>
                </tr></thead>
                <tbody>
                    @forelse(($recentOpen ?? []) as $row)
                    @php
                        $stColor = $statusColors[$row['lead_status']] ?? 'var(--u-brand,#1e40af)';
                        $stBadge = $row['lead_status'] === 'converted' ? 'ok' : ($row['lead_status'] === 'lost' ? 'danger' : 'pending');
                    @endphp
                    <tr>
                        <td style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">{{ $row['id'] }}</td>
                        <td style="font-weight:500;">{{ $row['name'] }}</td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row['lead_source'] ?: '—' }}</td>
                        <td><span class="badge {{ $stBadge }}">{{ $row['lead_status'] }}</span></td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row['created_at'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Açık lead kaydı yok.</td></tr>
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
            <h4 style="margin:0 0 10px;font-size:var(--tx-sm);font-weight:700;">Sales Pipeline — Dönüşüm Hunisi</h4>
            <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 12px;">Adayların lead'den kayıtlı öğrenciye dönüşüm sürecini huni formatında izle.</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🔻 Huni Aşamaları</strong>
                    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                        <li><strong>Lead (Ham):</strong> Sisteme ilk giren adaylar</li>
                        <li><strong>Değerlendirmede:</strong> Form dolduruldu, danışman inceliyor</li>
                        <li><strong>Ön Kabul:</strong> İlk değerlendirme geçildi</li>
                        <li><strong>Sözleşme Aşaması:</strong> Sözleşme imzalanıyor</li>
                        <li><strong>Kayıtlı Öğrenci:</strong> Süreç tamamlandı</li>
                    </ul>
                </div>
                <div>
                    <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📈 Ne Zaman Aksiyon Al?</strong>
                    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                        <li>Değerlendirme aşamasında birikim varsa → danışman kapasitesini kontrol et</li>
                        <li>Sözleşme aşamasında takılı kalan varsa → hukuki süreci hızlandır</li>
                        <li>Lead → Ön Kabul oranı %20 altındaysa → nitelik kriterlerini gözden geçir</li>
                        <li>Bar uzunluğu o statüdeki lead oranını gösterir — yığılma = tıkanıklık</li>
                        <li>Dönüşüm Oranı: sektörde %15–25 sağlıklı aralıktır</li>
                    </ul>
                </div>
            </div>
            <div style="margin-top:10px;padding:10px;background:color-mix(in srgb,var(--u-brand,#1e40af) 5%,var(--u-card,#fff));border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                💡 Üstteki sekmelerden <strong>Pipeline Value · Loss Analysis · Conversion Time · Re-Engagement · Score Analizi</strong> bölümlerine geç.
            </div>
        </div>
    </details>

</div>
@endsection

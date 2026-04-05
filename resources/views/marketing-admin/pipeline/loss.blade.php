@extends('marketing-admin.layouts.app')

@section('title', 'Loss Analysis')
@section('page_subtitle', 'Loss Analysis — kayip nedenleri, hareketsiz leadler ve recovery adaylari')

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
            <div class="pl-val" style="color:var(--u-danger,#dc2626);">{{ $summary['explicit_lost'] ?? 0 }}</div>
            <div class="pl-lbl">Açık Kayıp</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-warn,#d97706);">{{ $summary['stale_lost'] ?? 0 }}</div>
            <div class="pl-lbl">Hareketsiz Kayıp</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:#0369a1;">{{ $summary['recovery_candidate_count'] ?? 0 }}</div>
            <div class="pl-lbl">Recovery Adayı</div>
        </div>
    </div>

    {{-- Arşiv Nedenleri + Duruma Göre Hareketsiz --}}
    <div class="grid2" style="align-items:stretch;">

        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Arşiv Nedenleri</div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Neden</th>
                        <th style="width:80px;text-align:right;">Sayı</th>
                    </tr></thead>
                    <tbody>
                        @forelse(($reasonRows ?? []) as $row)
                        <tr>
                            <td>{{ $row['reason'] ?: '(belirtilmemiş)' }}</td>
                            <td style="text-align:right;"><span class="badge danger">{{ $row['total'] }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="2" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Duruma Göre Hareketsiz</div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Durum</th>
                        <th style="width:80px;text-align:right;">Sayı</th>
                    </tr></thead>
                    <tbody>
                        @forelse(($staleByStatus ?? []) as $row)
                        <tr>
                            <td>{{ $row['status'] }}</td>
                            <td style="text-align:right;"><span class="badge warn">{{ $row['total'] }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="2" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Kaynağa Göre Hareketsiz --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Kaynağa Göre Hareketsiz</div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th>Kaynak</th>
                    <th style="width:80px;text-align:right;">Sayı</th>
                </tr></thead>
                <tbody>
                    @forelse(($staleBySource ?? []) as $row)
                    <tr>
                        <td>{{ $row['source'] ?: '(doğrudan)' }}</td>
                        <td style="text-align:right;"><span class="badge warn">{{ $row['total'] }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="2" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recovery Adayları --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            Recovery Adayları
            <span style="font-weight:400;font-size:var(--tx-xs);margin-left:6px;">14–45 gün sessiz</span>
        </div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th style="width:50px;">ID</th>
                    <th>Aday</th>
                    <th>Kaynak</th>
                    <th style="width:120px;">Durum</th>
                    <th style="width:160px;">Kayıt Tarihi</th>
                </tr></thead>
                <tbody>
                    @forelse(($recoveryCandidates ?? []) as $row)
                    <tr>
                        <td style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">{{ $row['id'] }}</td>
                        <td style="font-weight:500;">{{ $row['name'] }}</td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row['lead_source'] ?: '—' }}</td>
                        <td><span class="badge warn">{{ $row['lead_status'] }}</span></td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row['created_at'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Recovery adayı yok.</td></tr>
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
            <h4 style="margin:0 0 10px;font-size:var(--tx-sm);font-weight:700;">Kayıp Analizi (Loss Analysis)</h4>
            <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 12px;">Hangi aşamada, neden aday kaybedildiğini analiz et ve geri kazan.</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🔴 Kayıp Kategorileri</strong>
                    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                        <li><strong>Açık Kayıp (kırmızı):</strong> Bilinçli arşivlenen leadler — fiyat, rakip, ilgi yok</li>
                        <li><strong>Hareketsiz Kayıp (turuncu):</strong> 45+ gün aksiyonsuz — acil takip gerekli</li>
                        <li><strong>Recovery Adayı (mavi):</strong> 14–45 gün sessiz — henüz soğumadı, geri kazanılabilir</li>
                    </ul>
                </div>
                <div>
                    <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">⚡ Aksiyon Rehberi</strong>
                    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                        <li>Recovery listesini haftalık incele — telefon veya özel teklifle temas</li>
                        <li>Hareketsiz kayıp oranı %20+ ise satış sürecinde sistematik sorun var</li>
                        <li>"Fiyat" sebebi %30+ ise paket yapısını gözden geçir</li>
                    </ul>
                </div>
            </div>
            <div style="margin-top:10px;padding:10px;background:color-mix(in srgb,var(--u-danger,#dc2626) 6%,var(--u-card,#fff));border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                ⚠️ <strong>Re-Engagement:</strong> Recovery adayları için Otomasyon → Re-Engagement Kampanyası workflow'unu aktifleştir.
            </div>
        </div>
    </details>

</div>
@endsection

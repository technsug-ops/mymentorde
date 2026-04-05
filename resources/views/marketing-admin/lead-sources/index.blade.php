@extends('marketing-admin.layouts.app')

@section('title', 'Lead Kaynakları')

@section('page_subtitle', 'Lead Kaynakları — kaynak özeti, funnel, UTM ve tracking kod analizi')

@section('topbar-actions')
@php
    $lsActive = fn(string $p) => request()->is($p) ? '' : 'alt';
@endphp
<a class="btn {{ $lsActive('mktg-admin/lead-sources') }}" href="/mktg-admin/lead-sources" style="font-size:var(--tx-xs);padding:6px 12px;">Kaynak Özeti</a>
<a class="btn {{ $lsActive('mktg-admin/lead-sources/funnel') }}" href="/mktg-admin/lead-sources/funnel" style="font-size:var(--tx-xs);padding:6px 12px;">Funnel</a>
<a class="btn {{ $lsActive('mktg-admin/lead-sources/utm') }}" href="/mktg-admin/lead-sources/utm" style="font-size:var(--tx-xs);padding:6px 12px;">UTM</a>
<a class="btn {{ $lsActive('mktg-admin/lead-sources/tracking-codes') }}" href="/mktg-admin/lead-sources/tracking-codes" style="font-size:var(--tx-xs);padding:6px 12px;">Tracking Codes</a>
<a class="btn {{ $lsActive('mktg-admin/lead-sources/dropoff') }}" href="/mktg-admin/lead-sources/dropoff" style="font-size:var(--tx-xs);padding:6px 12px;">Dropoff</a>
<a class="btn {{ $lsActive('mktg-admin/lead-sources/source-verify') }}" href="/mktg-admin/lead-sources/source-verify" style="font-size:var(--tx-xs);padding:6px 12px;">Source Verify</a>
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

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl tr:last-child td { border-bottom:none; }
.mono { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:12px; }

.wf-field { display:flex; flex-direction:column; gap:4px; }
.wf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input { height:36px; padding:0 10px; border:1px solid var(--u-line,#e2e8f0); border-radius:8px; background:var(--u-card,#fff); color:var(--u-text,#0f172a); font-size:13px; outline:none; }
.wf-field input:focus { border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10); }
</style>

<div style="display:grid;gap:12px;">

{{-- ── KAYNAK ÖZETİ ─────────────────────────────── --}}
@if(($mode ?? '') === 'summary')
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">Kaynak Özeti</div>
        <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">Toplam: {{ $total ?? 0 }} lead</span>
    </div>
    <div class="tl-wrap">
        <table class="tl-tbl">
            <thead><tr>
                <th>Source</th>
                <th style="width:80px;text-align:right;">Lead</th>
                <th style="width:80px;text-align:right;">Verified</th>
                <th style="width:80px;text-align:right;">Matched</th>
                <th style="width:110px;text-align:right;">Conversion %</th>
            </tr></thead>
            <tbody>
                @forelse(($rows ?? []) as $row)
                <tr>
                    <td>{{ $row['source'] }}</td>
                    <td style="text-align:right;">{{ $row['lead_count'] }}</td>
                    <td style="text-align:right;">{{ $row['verified_count'] }}</td>
                    <td style="text-align:right;">{{ $row['matched_count'] }}</td>
                    <td style="text-align:right;"><span class="badge {{ (float)$row['conversion_rate'] >= 15 ? 'ok' : ((float)$row['conversion_rate'] >= 5 ? 'warn' : 'info') }}">{{ number_format((float)$row['conversion_rate'], 1, '.', ',') }}%</span></td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── FUNNEL ────────────────────────────────────── --}}
@if(($mode ?? '') === 'funnel')
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">Funnel Analizi</div>
        <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $total ?? 0 }} lead · {{ $dropped ?? 0 }} dropoff</span>
    </div>
    <div class="tl-wrap">
        <table class="tl-tbl">
            <thead><tr>
                <th>Stage</th>
                <th style="width:80px;text-align:right;">Count</th>
                <th style="width:100px;text-align:right;">Rate</th>
            </tr></thead>
            <tbody>
                @forelse(($rows ?? []) as $row)
                <tr>
                    <td style="font-weight:500;">{{ $row['stage'] }}</td>
                    <td style="text-align:right;">{{ $row['count'] }}</td>
                    <td style="text-align:right;"><span class="badge {{ (float)$row['rate'] >= 50 ? 'ok' : ((float)$row['rate'] >= 20 ? 'warn' : 'danger') }}">{{ number_format((float)$row['rate'], 1, '.', ',') }}%</span></td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── UTM ───────────────────────────────────────── --}}
@if(($mode ?? '') === 'utm')
@if(!empty($topCampaigns))
<div class="card">
    <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Top UTM Campaigns</div>
    <div class="list">
        @php $utmMax = max(1, collect($topCampaigns)->max('total')); @endphp
        @foreach($topCampaigns as $item)
        <div class="item">
            <span style="flex:2;font-size:var(--tx-sm);font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $item['campaign'] }}</span>
            <div style="flex:3;height:5px;background:var(--u-bg,#f1f5f9);border-radius:999px;overflow:hidden;margin:0 10px;">
                <div style="width:{{ round($item['total']/$utmMax*100) }}%;height:100%;background:var(--u-brand,#1e40af);border-radius:999px;"></div>
            </div>
            <span style="font-size:var(--tx-xs);font-weight:600;color:var(--u-text,#0f172a);">{{ $item['total'] }} lead</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="card">
    <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">UTM Performans Tablosu</div>
    <div class="tl-wrap">
        <table class="tl-tbl" style="min-width:780px;">
            <thead><tr>
                <th>utm_source</th>
                <th>utm_medium</th>
                <th>utm_campaign</th>
                <th style="width:60px;text-align:right;">Lead</th>
                <th style="width:70px;text-align:right;">Verified</th>
                <th style="width:80px;text-align:right;">Converted</th>
                <th style="width:80px;text-align:right;">Rate</th>
            </tr></thead>
            <tbody>
                @forelse(($rows ?? []) as $row)
                <tr>
                    <td>{{ $row['utm_source'] }}</td>
                    <td style="color:var(--u-muted,#64748b);">{{ $row['utm_medium'] }}</td>
                    <td>{{ $row['utm_campaign'] }}</td>
                    <td style="text-align:right;">{{ $row['lead_count'] }}</td>
                    <td style="text-align:right;">{{ $row['verified_count'] }}</td>
                    <td style="text-align:right;color:var(--u-ok,#16a34a);font-weight:600;">{{ $row['converted_count'] }}</td>
                    <td style="text-align:right;"><span class="badge {{ (float)$row['conversion_rate'] >= 15 ? 'ok' : 'info' }}">{{ number_format((float)$row['conversion_rate'], 1, '.', ',') }}%</span></td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($rows) && method_exists($rows, 'hasPages') && $rows->hasPages())
    <div style="padding:12px 0;">{{ $rows->links() }}</div>
    @endif
</div>
@endif

{{-- ── TRACKING CODES ────────────────────────────── --}}
@if(($mode ?? '') === 'tracking_codes')
@php
    $tf = $trackingFilters ?? ['start_date' => '', 'end_date' => ''];
    $csvQuery = http_build_query(array_filter(['start_date' => $tf['start_date'] ?? '', 'end_date' => $tf['end_date'] ?? '']));
    $csvHref = '/mktg-admin/lead-sources/tracking-codes/csv'.($csvQuery !== '' ? '?'.$csvQuery : '');
@endphp

<div class="card">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
        <form method="GET" action="/mktg-admin/lead-sources/tracking-codes" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
            <div class="wf-field">
                <label>Başlangıç</label>
                <input type="date" name="start_date" value="{{ $tf['start_date'] ?? '' }}">
            </div>
            <div class="wf-field">
                <label>Bitiş</label>
                <input type="date" name="end_date" value="{{ $tf['end_date'] ?? '' }}">
            </div>
            <button type="submit" class="btn" style="height:36px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
            <a href="/mktg-admin/lead-sources/tracking-codes" class="btn alt" style="height:36px;font-size:var(--tx-xs);padding:0 12px;display:flex;align-items:center;">Temizle</a>
        </form>
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $total ?? 0 }} tracking code</span>
            <a class="btn alt" href="{{ $csvHref }}" style="font-size:var(--tx-xs);padding:6px 12px;">⬇ CSV İndir</a>
        </div>
    </div>
    <div class="tl-wrap">
        <table class="tl-tbl" style="min-width:1000px;">
            <thead><tr>
                <th>Code</th>
                <th>Title</th>
                <th style="width:80px;">Status</th>
                <th>Campaign</th>
                <th>Source</th>
                <th style="width:60px;text-align:right;">Click</th>
                <th style="width:60px;text-align:right;">Lead</th>
                <th style="width:90px;text-align:right;">Lead/Click</th>
                <th style="width:80px;text-align:right;">Converted</th>
                <th style="width:80px;text-align:right;">Conv %</th>
            </tr></thead>
            <tbody>
                @forelse(($rows ?? []) as $row)
                <tr>
                    <td><code class="mono">{{ $row['code'] }}</code></td>
                    <td>{{ $row['title'] }}</td>
                    <td><span class="badge {{ $row['status'] === 'active' ? 'ok' : 'pending' }}">{{ $row['status'] }}</span></td>
                    <td style="color:var(--u-muted,#64748b);">{{ $row['campaign_code'] }}</td>
                    <td style="color:var(--u-muted,#64748b);">{{ $row['source_code'] }}</td>
                    <td style="text-align:right;">{{ $row['click_count'] }}</td>
                    <td style="text-align:right;font-weight:600;">{{ $row['lead_count'] }}</td>
                    <td style="text-align:right;">{{ $row['lead_from_click_rate'] === null ? '—' : number_format((float)$row['lead_from_click_rate'], 1, '.', ',').'%' }}</td>
                    <td style="text-align:right;color:var(--u-ok,#16a34a);font-weight:600;">{{ $row['converted_count'] }}</td>
                    <td style="text-align:right;"><span class="badge {{ (float)$row['conversion_rate'] >= 15 ? 'ok' : 'info' }}">{{ number_format((float)$row['conversion_rate'], 1, '.', ',') }}%</span></td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Tracking code verisi yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── DROPOFF ───────────────────────────────────── --}}
@if(($mode ?? '') === 'dropoff')
<div class="grid2">
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Explicit Dropoff</div>
        <div class="list">
            @forelse(($explicitRows ?? []) as $row)
            <div class="item">
                <span style="flex:1;font-weight:500;">{{ $row['stage'] }}</span>
                <span class="badge warn">{{ $row['total'] }}</span>
            </div>
            @empty
            <div class="item" style="color:var(--u-muted,#64748b);">İşaretlenmiş dropoff yok.</div>
            @endforelse
        </div>
    </div>
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Inferred Pending Stage</div>
        <div class="list">
            @forelse(($inferredRows ?? []) as $row)
            <div class="item">
                <span style="flex:1;font-weight:500;">{{ $row['stage'] }}</span>
                <span class="badge pending">{{ $row['total'] }}</span>
            </div>
            @empty
            <div class="item" style="color:var(--u-muted,#64748b);">Bekleyen dropoff yok.</div>
            @endforelse
        </div>
    </div>
</div>
@endif

{{-- ── SOURCE VERIFY ────────────────────────────── --}}
@if(($mode ?? '') === 'verification')
<div class="pl-stats" style="display:flex;gap:0;border:1px solid var(--u-line,#e2e8f0);border-radius:10px;overflow:hidden;background:var(--u-card,#fff);">
    <div style="flex:1;padding:12px 16px;border-right:1px solid var(--u-line,#e2e8f0);">
        <div style="font-size:var(--tx-xl);font-weight:700;color:var(--u-brand,#1e40af);">{{ $total ?? 0 }}</div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-top:2px;">Total Lead</div>
    </div>
    <div style="flex:1;padding:12px 16px;border-right:1px solid var(--u-line,#e2e8f0);">
        <div style="font-size:var(--tx-xl);font-weight:700;color:var(--u-ok,#16a34a);">{{ $verifiedTotal ?? 0 }}</div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-top:2px;">Verified</div>
    </div>
    <div style="flex:1;padding:12px 16px;border-right:1px solid var(--u-line,#e2e8f0);">
        <div style="font-size:var(--tx-xl);font-weight:700;color:var(--u-brand,#1e40af);">{{ $matchedTotal ?? 0 }}</div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-top:2px;">Matched</div>
    </div>
    <div style="flex:1;padding:12px 16px;">
        <div style="font-size:var(--tx-xl);font-weight:700;color:var(--u-danger,#dc2626);">{{ $mismatchTotal ?? 0 }}</div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-top:2px;">Mismatch</div>
    </div>
</div>
<div class="card">
    <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Mismatch Pairs</div>
    <div class="tl-wrap">
        <table class="tl-tbl">
            <thead><tr>
                <th>Initial Source</th>
                <th>Verified Source</th>
                <th style="width:80px;text-align:right;">Count</th>
            </tr></thead>
            <tbody>
                @forelse(($mismatchRows ?? []) as $row)
                <tr>
                    <td>{{ $row['initial_source'] }}</td>
                    <td>{{ $row['verified_source'] }}</td>
                    <td style="text-align:right;"><span class="badge danger">{{ $row['total'] }}</span></td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Mismatch yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Rehber --}}
<details class="card">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Lead Kaynakları</h3>
        <span class="det-chev">▼</span>
    </summary>
    <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
        <li><strong>Kaynak Özeti:</strong> Her kanalın lead sayısı, doğrulama ve dönüşüm oranı — az dönüştüren kanalların kalitesini sorgula.</li>
        <li><strong>Funnel Analizi:</strong> Büyük düşüşün yaşandığı adım darboğazdır — orada süreç iyileştirmesi gerekir.</li>
        <li><strong>UTM Performansı:</strong> Hangi utm_source/medium/campaign kombinasyonu gerçekten lead üretiyor?</li>
        <li><strong>Tracking Codes:</strong> Lead-from-click oranı düşükse landing page veya teşvik yapısı sorunlu olabilir. Tarih filtresi ile CSV indir.</li>
        <li><strong>Dropoff:</strong> Bilinçli bırakanlar + takılı kalanlar — takılı kalan leadler için otomatik hatırlatma kurulabilir.</li>
        <li><strong>Source Verify:</strong> Mismatch yüzdesi yüksekse UTM parametreleri kayboluyordur — form yapısını kontrol et.</li>
    </ol>
</details>

</div>
@endsection

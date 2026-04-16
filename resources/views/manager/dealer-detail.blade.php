@extends('manager.layouts.app')

@section('title', 'Manager – Bayi Detay')
@section('page_title', 'Bayi Detay')

@push('head')
<style>
/* Shared detail layout */
.gd-panel { padding:14px 16px !important; margin-bottom:12px !important; }
.gd-panel h2 { font-size:13px !important; font-weight:700 !important; color:var(--u-text,#0f172a); margin:0 0 10px; padding-bottom:8px; border-bottom:1px solid var(--u-line,#e5e9f0); letter-spacing:.2px; }
.gd-panel h2 .muted { font-weight:400 !important; font-size:11px !important; color:var(--u-muted,#64748b); }

/* KPI tiles */
.gd-kpi-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin-bottom:12px; }
.gd-kpi { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:8px; padding:12px 14px; display:flex; flex-direction:column; gap:4px; }
.gd-kpi .lbl { font-size:11px; font-weight:600; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; }
.gd-kpi .val { font-size:22px; font-weight:700; color:var(--u-text,#0f172a); line-height:1.1; }
.gd-kpi.warn .val { color:#d97706; }
.gd-kpi.ok .val { color:#15803d; }
@media(max-width:900px){ .gd-kpi-row { grid-template-columns:repeat(2,1fr); } }

/* Dealer header */
.gd-dealer-head { padding:14px 16px !important; margin-bottom:12px !important; display:flex; justify-content:space-between; align-items:flex-start; gap:14px; flex-wrap:wrap; }
.gd-dealer-head .name { font-size:16px; font-weight:700; color:var(--u-text,#0f172a); }
.gd-dealer-head .code { font-size:12px; color:var(--u-muted,#64748b); font-family:monospace; margin-left:6px; }
.gd-dealer-head .meta { display:flex; gap:6px; align-items:center; flex-wrap:wrap; margin-top:6px; }

/* Data table */
.gd-list-table { width:100%; border-collapse:collapse; font-size:12px; }
.gd-list-table thead th { padding:8px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; background:var(--u-bg,#f5f7fa); border-bottom:1px solid var(--u-line,#e5e9f0); }
.gd-list-table tbody td { padding:8px 10px; border-bottom:1px solid var(--u-line,#e5e9f0); vertical-align:top; }
.gd-list-table tbody tr:last-child td { border-bottom:none; }
.gd-list-table tbody tr:hover { background:#f8fafc; }
.gd-list-table .gd-pri { font-weight:600; color:var(--u-text,#0f172a); }
.gd-list-table .gd-sub { font-size:11px; color:var(--u-muted,#64748b); }
.gd-list-table td.num { text-align:right; font-variant-numeric:tabular-nums; }
.gd-list-table th.num { text-align:right; }
.gd-list-table .btn { font-size:11px !important; padding:4px 10px !important; min-height:28px !important; }

/* Payout list */
.gd-payout-list { display:flex; flex-direction:column; }
.gd-payout-item { padding:10px 12px; border-bottom:1px solid var(--u-line,#e5e9f0); display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.gd-payout-item:last-child { border-bottom:none; }
.gd-payout-item:hover { background:#f8fafc; }
.gd-payout-info { flex:1; min-width:0; font-size:12px; }
.gd-payout-info strong { color:var(--u-text,#0f172a); }
.gd-payout-info .meta { font-size:11px; color:var(--u-muted,#64748b); margin-top:3px; }
.gd-payout-bank { font-size:11px; color:var(--u-muted,#64748b); text-align:right; }
</style>
@endpush

@section('content')

<div style="margin-bottom:10px;">
    <a class="btn" href="/manager/dealers">← Bayi Listesi</a>
</div>

{{-- Bayi Başlık --}}
<section class="panel gd-dealer-head">
    <div>
        <div>
            <span class="name">{{ $dealer->name }}</span>
            <span class="code">{{ $dealer->code }}</span>
            @if($dealer->dealer_type_code)
                <span class="badge" style="margin-left:6px;font-size:10px;">{{ $dealer->dealer_type_code }}</span>
            @endif
        </div>
        <div class="meta">
            @if($dealer->is_active)
                <span class="badge ok">Aktif</span>
            @else
                <span class="badge">Pasif</span>
            @endif
        </div>
    </div>
    <a class="btn" href="/manager/preview/dealer/{{ $dealer->code }}" target="_blank" style="font-size:12px;padding:6px 14px;">Dealer Önizleme</a>
</section>

{{-- KPI Çubuğu --}}
<div class="gd-kpi-row">
    <div class="gd-kpi"><div class="lbl">Öğrenci</div><div class="val">{{ $revenueStats['students'] }}</div></div>
    <div class="gd-kpi"><div class="lbl">Toplam Lead</div><div class="val">{{ $leads->total() }}</div></div>
    <div class="gd-kpi ok"><div class="lbl">Kazanılan (EUR)</div><div class="val">{{ number_format($revenueStats['total_earned'], 2, ',', '.') }}</div></div>
    <div class="gd-kpi {{ $revenueStats['total_pending'] > 0 ? 'warn' : '' }}">
        <div class="lbl">Bekleyen (EUR)</div>
        <div class="val">{{ number_format($revenueStats['total_pending'], 2, ',', '.') }}</div>
    </div>
</div>

<div class="grid2">

    {{-- Gelir Detayı (student bazlı) --}}
    <section class="card gd-panel">
        <h2>Öğrenci Gelir Detayı</h2>
        @if($revenues->isEmpty())
            <div class="muted" style="padding:12px 0;font-size:12px;">Henüz gelir kaydı yok.</div>
        @else
            <div style="overflow-x:auto;">
                <table class="gd-list-table">
                    <thead>
                        <tr>
                            <th>Öğrenci ID</th>
                            <th class="num">Kazanılan</th>
                            <th class="num">Bekleyen</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revenues as $rev)
                            <tr>
                                <td class="gd-pri">{{ $rev->student_id }}</td>
                                <td class="num">{{ $rev->total_earned > 0 ? number_format((float)$rev->total_earned, 2, ',', '.') : '–' }}</td>
                                <td class="num">
                                    @if($rev->total_pending > 0)
                                        <span style="color:#d97706;">{{ number_format((float)$rev->total_pending, 2, ',', '.') }}</span>
                                    @else <span class="gd-sub">–</span>
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    <a class="btn" href="/manager/students/{{ urlencode($rev->student_id) }}">Detay</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Ödeme Talepleri --}}
    <section class="card gd-panel">
        <h2>Ödeme Talepleri</h2>
        @if($payouts->isEmpty())
            <div class="muted" style="padding:12px 0;font-size:12px;">Ödeme talebi yok.</div>
        @else
            <div class="gd-payout-list">
                @foreach($payouts as $p)
                    @php
                        $sc = match($p->status) { 'requested'=>'warn','approved'=>'info','paid'=>'ok','rejected'=>'danger',default=>'badge' };
                        $sl = match($p->status) { 'requested'=>'Talep Edildi','approved'=>'Onaylandı','paid'=>'Ödendi','rejected'=>'Reddedildi',default=>ucfirst((string)($p->status ?? '–')) };
                    @endphp
                    <div class="gd-payout-item">
                        <div class="gd-payout-info">
                            <strong>#{{ $p->id }}</strong>
                            <span class="badge {{ $sc }}" style="margin-left:4px;font-size:10px;">{{ $sl }}</span>
                            <div class="meta">
                                {{ number_format((float)($p->amount ?? 0), 2, ',', '.') }} {{ $p->currency ?: 'EUR' }}
                                · {{ optional($p->created_at)->format('d.m.Y') }}
                                @if($p->approved_by) · Onaylayan: {{ $p->approved_by }} @endif
                            </div>
                            @if($p->receipt_url)
                                <div class="meta"><a href="{{ $p->receipt_url }}" target="_blank">Dekont</a></div>
                            @endif
                            @if($p->rejection_reason)
                                <div class="meta">Red: {{ $p->rejection_reason }}</div>
                            @endif
                        </div>
                        @if($p->account)
                            <div class="gd-payout-bank">
                                {{ $p->account->bank_name ?? '' }}<br>
                                {{ $p->account->iban ?? '' }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @if($payouts->hasPages())
            <div style="margin-top:12px;">{{ $payouts->links() }}</div>
            @endif
        @endif
    </section>

</div>

{{-- Son Leadler --}}
<section class="card gd-panel" style="margin-top:12px;">
    <h2>Leadler <span class="muted">{{ $leads->total() }} kayıt</span></h2>
    @if($leads->isEmpty())
        <div class="muted" style="padding:12px 0;font-size:12px;">Bu bayiye ait lead bulunamadı.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="gd-list-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Eğitim Danışmanı</th>
                        <th>Durum</th>
                        <th>Dönüşüm</th>
                        <th>Tarih</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                        @php
                            $bc = match($lead->lead_status) { 'new'=>'info','contacted'=>'warn','qualified'=>'badge','converted'=>'ok','lost'=>'danger',default=>'badge' };
                            $bl = match($lead->lead_status ?? '') { 'new'=>'Yeni','contacted'=>'İletişime Geçildi','qualified'=>'Nitelikli','converted'=>'Dönüştü','lost'=>'Kayboldu',default=>($lead->lead_status ?: '–') };
                        @endphp
                        <tr>
                            <td class="gd-sub">#{{ $lead->id }}</td>
                            <td class="gd-pri">{{ $lead->first_name }} {{ $lead->last_name }}</td>
                            <td class="gd-sub">{{ $lead->email }}</td>
                            <td class="gd-sub">{{ $lead->assigned_senior_email ?: '–' }}</td>
                            <td><span class="badge {{ $bc }}">{{ $bl }}</span></td>
                            <td>
                                @if($lead->converted_to_student)
                                    <span class="badge ok">{{ $lead->converted_student_id }}</span>
                                @else
                                    <span class="gd-sub">–</span>
                                @endif
                            </td>
                            <td class="gd-sub">{{ optional($lead->created_at)->format('d.m.Y') }}</td>
                            <td style="text-align:right;">
                                <a class="btn" href="/manager/guests/{{ $lead->id }}">Detay</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($leads->hasPages())
            <div style="margin-top:12px;">{{ $leads->links() }}</div>
        @endif
    @endif
</section>

{{-- UTM / Tracking Link Performansı --}}
<section class="panel gd-panel" style="margin-top:12px;">
    <h2>UTM & Tracking Link Performansı <span class="muted">{{ $utmStats['total_links'] }} link toplam</span></h2>

    {{-- KPI satırı --}}
    <div class="gd-kpi-row">
        <div class="gd-kpi"><div class="lbl">Aktif Link</div><div class="val">{{ $utmStats['active_links'] }}</div></div>
        <div class="gd-kpi"><div class="lbl">Toplam Tıklama</div><div class="val">{{ number_format($utmStats['total_clicks']) }}</div></div>
        <div class="gd-kpi"><div class="lbl">Lead</div><div class="val">{{ number_format($utmStats['total_leads']) }}</div></div>
        <div class="gd-kpi {{ $utmStats['total_converted'] > 0 ? 'ok' : '' }}"><div class="lbl">Dönüşüm</div><div class="val">{{ number_format($utmStats['total_converted']) }}</div></div>
    </div>

    @if($utmLinks->isEmpty())
        <p class="muted" style="font-size:12px;margin:0;">Bu bayiye ait tracking link bulunamadı.</p>
    @else
        <div style="overflow-x:auto;">
            <table class="gd-list-table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Başlık</th>
                        <th>UTM</th>
                        <th style="text-align:center;">Durum</th>
                        <th class="num">Tıklama</th>
                        <th class="num">Lead</th>
                        <th class="num">Dönüşüm</th>
                        <th class="num">CVR</th>
                        <th>Son Tıklama</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($utmLinks as $link)
                        @php
                            $ls = $leadStatsByCode->get($link->code);
                            $leadCount = (int) ($ls?->lead_count ?? 0);
                            $convertedCount = (int) ($ls?->converted_count ?? 0);
                            $cvr = $leadCount > 0 ? round($convertedCount / $leadCount * 100, 1) : null;
                            $statusBadge = match($link->status) { 'active' => 'ok', 'paused' => 'warn', default => 'danger' };
                            $statusLabel = match($link->status) { 'active' => 'Aktif', 'paused' => 'Durduruldu', default => 'Arşiv' };
                            $utmLabel = collect([$link->utm_source, $link->utm_medium, $link->utm_campaign])->filter()->implode(' / ');
                        @endphp
                        <tr>
                            <td style="font-family:monospace;letter-spacing:.5px;">{{ $link->code }}</td>
                            <td class="gd-pri" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $link->title }}">{{ $link->title }}</td>
                            <td class="gd-sub">{{ $utmLabel ?: '–' }}</td>
                            <td style="text-align:center;"><span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></td>
                            <td class="num">{{ number_format($link->click_count ?? 0) }}</td>
                            <td class="num">{{ $leadCount > 0 ? number_format($leadCount) : '–' }}</td>
                            <td class="num">{{ $convertedCount > 0 ? number_format($convertedCount) : '–' }}</td>
                            <td class="num">
                                @if($cvr !== null)
                                    <span class="badge {{ $cvr >= 10 ? 'ok' : ($cvr >= 5 ? 'warn' : 'danger') }}">%{{ $cvr }}</span>
                                @else
                                    <span class="gd-sub">–</span>
                                @endif
                            </td>
                            <td class="gd-sub">{{ $link->last_clicked_at ? \Carbon\Carbon::parse($link->last_clicked_at)->format('d.m.Y') : '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

@endsection

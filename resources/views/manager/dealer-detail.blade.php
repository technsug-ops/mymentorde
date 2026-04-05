@extends('manager.layouts.app')

@section('title', 'Manager – Bayi Detay')
@section('page_title', 'Bayi Detay')

@section('content')

<div style="margin-bottom:10px;">
    <a class="btn" href="/manager/dealers">← Bayi Listesi</a>
</div>

{{-- Bayi Başlık --}}
<section class="panel" style="margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
        <div>
            <strong style="font-size:var(--tx-lg);">{{ $dealer->name }}</strong>
            <span class="muted" style="margin-left:8px;">{{ $dealer->code }}</span>
            @if($dealer->dealer_type_code)
                <span class="badge" style="margin-left:6px;">{{ $dealer->dealer_type_code }}</span>
            @endif
            <br>
            @if($dealer->is_active)
                <span class="badge ok">Aktif</span>
            @else
                <span class="badge">Pasif</span>
            @endif
        </div>
        <a class="btn" href="/manager/preview/dealer/{{ $dealer->code }}" target="_blank">Dealer Önizleme</a>
    </div>
</section>

{{-- KPI Çubuğu --}}
<div class="grid4" style="margin-bottom:12px;">
    <div class="panel"><div class="muted">Öğrenci</div><div class="kpi">{{ $revenueStats['students'] }}</div></div>
    <div class="panel"><div class="muted">Toplam Lead</div><div class="kpi">{{ $leads->total() }}</div></div>
    <div class="panel"><div class="muted">Kazanılan (EUR)</div><div class="kpi">{{ number_format($revenueStats['total_earned'], 2, ',', '.') }}</div></div>
    <div class="panel">
        <div class="muted">Bekleyen (EUR)</div>
        <div class="kpi" style="{{ $revenueStats['total_pending'] > 0 ? 'color:var(--u-warn,#d97706);' : '' }}">
            {{ number_format($revenueStats['total_pending'], 2, ',', '.') }}
        </div>
    </div>
</div>

<div class="grid2">

    {{-- Gelir Detayı (student bazlı) --}}
    <section class="card">
        <h2>Öğrenci Gelir Detayı</h2>
        @if($revenues->isEmpty())
            <div class="muted" style="padding:12px 0;">Henüz gelir kaydı yok.</div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:var(--tx-xs);">
                    <thead>
                        <tr style="background:var(--u-bg,#f5f7fa);">
                            <th style="padding:6px 8px;text-align:left;">Student ID</th>
                            <th style="padding:6px 8px;text-align:right;">Kazanılan</th>
                            <th style="padding:6px 8px;text-align:right;">Bekleyen</th>
                            <th style="padding:6px 8px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revenues as $rev)
                            <tr style="border-bottom:1px solid var(--u-line,#e5e9f0);">
                                <td style="padding:6px 8px;font-weight:500;">{{ $rev->student_id }}</td>
                                <td style="padding:6px 8px;text-align:right;">
                                    {{ $rev->total_earned > 0 ? number_format((float)$rev->total_earned, 2, ',', '.') : '–' }}
                                </td>
                                <td style="padding:6px 8px;text-align:right;">
                                    @if($rev->total_pending > 0)
                                        <span style="color:var(--u-warn,#d97706);">{{ number_format((float)$rev->total_pending, 2, ',', '.') }}</span>
                                    @else –
                                    @endif
                                </td>
                                <td style="padding:6px 8px;">
                                    <a class="btn" style="font-size:var(--tx-xs);padding:3px 8px;" href="/manager/students/{{ urlencode($rev->student_id) }}">Detay</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Ödeme Talepleri --}}
    <section class="card">
        <h2>Ödeme Talepleri</h2>
        @if($payouts->isEmpty())
            <div class="muted" style="padding:12px 0;">Ödeme talebi yok.</div>
        @else
            <div class="list">
                @foreach($payouts as $p)
                    @php
                        $sc = match($p->status) { 'requested'=>'warn','approved'=>'info','paid'=>'ok','rejected'=>'danger',default=>'badge' };
                        $sl = match($p->status) { 'requested'=>'Talep Edildi','approved'=>'Onaylandı','paid'=>'Ödendi','rejected'=>'Reddedildi',default=>ucfirst((string)($p->status ?? '–')) };
                    @endphp
                    <div class="item">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;">
                            <div>
                                <strong>#{{ $p->id }}</strong>
                                <span class="badge {{ $sc }}" style="margin-left:4px;">{{ $sl }}</span><br>
                                <span class="muted" style="font-size:var(--tx-xs);">
                                    {{ number_format((float)($p->amount ?? 0), 2, ',', '.') }} {{ $p->currency ?: 'EUR' }}
                                    | {{ optional($p->created_at)->format('d.m.Y') }}
                                    @if($p->approved_by) | Onaylayan: {{ $p->approved_by }} @endif
                                </span>
                                @if($p->receipt_url)
                                    <br><a href="{{ $p->receipt_url }}" target="_blank" class="muted" style="font-size:var(--tx-xs);">Dekont</a>
                                @endif
                                @if($p->rejection_reason)
                                    <br><span class="muted" style="font-size:var(--tx-xs);">Red: {{ $p->rejection_reason }}</span>
                                @endif
                            </div>
                            @if($p->account)
                                <div class="muted" style="font-size:var(--tx-xs);text-align:right;">
                                    {{ $p->account->bank_name ?? '' }}<br>
                                    {{ $p->account->iban ?? '' }}
                                </div>
                            @endif
                        </div>
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
<section class="card" style="margin-top:12px;">
    <h2>Leadler <span class="muted" style="font-size:var(--tx-sm);font-weight:400;">{{ $leads->total() }} kayıt</span></h2>
    @if($leads->isEmpty())
        <div class="muted" style="padding:12px 0;">Bu bayiye ait lead bulunamadı.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-xs);">
                <thead>
                    <tr style="background:var(--u-bg,#f5f7fa);">
                        <th style="padding:6px 8px;text-align:left;">ID</th>
                        <th style="padding:6px 8px;text-align:left;">Ad Soyad</th>
                        <th style="padding:6px 8px;text-align:left;">E-posta</th>
                        <th style="padding:6px 8px;text-align:left;">Senior</th>
                        <th style="padding:6px 8px;text-align:left;">Durum</th>
                        <th style="padding:6px 8px;text-align:left;">Dönüşüm</th>
                        <th style="padding:6px 8px;text-align:left;">Tarih</th>
                        <th style="padding:6px 8px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                        @php
                            $bc = match($lead->lead_status) { 'new'=>'info','contacted'=>'warn','qualified'=>'badge','converted'=>'ok','lost'=>'danger',default=>'badge' };
                            $bl = match($lead->lead_status ?? '') { 'new'=>'Yeni','contacted'=>'İletişime Geçildi','qualified'=>'Nitelikli','converted'=>'Dönüştü','lost'=>'Kayboldu',default=>($lead->lead_status ?: '–') };
                        @endphp
                        <tr style="border-bottom:1px solid var(--u-line,#e5e9f0);">
                            <td style="padding:6px 8px;">#{{ $lead->id }}</td>
                            <td style="padding:6px 8px;font-weight:500;">{{ $lead->first_name }} {{ $lead->last_name }}</td>
                            <td style="padding:6px 8px;" class="muted">{{ $lead->email }}</td>
                            <td style="padding:6px 8px;" class="muted">{{ $lead->assigned_senior_email ?: '–' }}</td>
                            <td style="padding:6px 8px;">
                                <span class="badge {{ $bc }}">{{ $bl }}</span>
                            </td>
                            <td style="padding:6px 8px;">
                                @if($lead->converted_to_student)
                                    <span class="badge ok">{{ $lead->converted_student_id }}</span>
                                @else
                                    <span class="muted">–</span>
                                @endif
                            </td>
                            <td style="padding:6px 8px;" class="muted">{{ optional($lead->created_at)->format('d.m.Y') }}</td>
                            <td style="padding:6px 8px;">
                                <a class="btn" style="font-size:var(--tx-xs);padding:3px 8px;" href="/manager/guests/{{ $lead->id }}">Detay</a>
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
<section class="panel" style="margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
        <strong>UTM & Tracking Link Performansı</strong>
        <span class="muted" style="font-size:var(--tx-xs);">{{ $utmStats['total_links'] }} link toplam</span>
    </div>

    {{-- KPI satırı --}}
    <div class="grid4" style="margin-bottom:14px;">
        <div class="card" style="text-align:center;padding:10px 8px;">
            <div class="kpi">{{ $utmStats['active_links'] }}</div>
            <div class="muted" style="font-size:var(--tx-xs);margin-top:4px;">Aktif Link</div>
        </div>
        <div class="card" style="text-align:center;padding:10px 8px;">
            <div class="kpi">{{ number_format($utmStats['total_clicks']) }}</div>
            <div class="muted" style="font-size:var(--tx-xs);margin-top:4px;">Toplam Tıklama</div>
        </div>
        <div class="card" style="text-align:center;padding:10px 8px;">
            <div class="kpi">{{ number_format($utmStats['total_leads']) }}</div>
            <div class="muted" style="font-size:var(--tx-xs);margin-top:4px;">Lead</div>
        </div>
        <div class="card" style="text-align:center;padding:10px 8px;">
            <div class="kpi {{ $utmStats['total_converted'] > 0 ? 'ok' : '' }}">{{ number_format($utmStats['total_converted']) }}</div>
            <div class="muted" style="font-size:var(--tx-xs);margin-top:4px;">Dönüşüm</div>
        </div>
    </div>

    @if($utmLinks->isEmpty())
        <p class="muted" style="font-size:var(--tx-xs);">Bu bayiye ait tracking link bulunamadı.</p>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-xs);">
                <thead>
                    <tr style="background:var(--u-bg,#f5f7fa);">
                        <th style="padding:6px 8px;text-align:left;">Kod</th>
                        <th style="padding:6px 8px;text-align:left;">Başlık</th>
                        <th style="padding:6px 8px;text-align:left;">UTM</th>
                        <th style="padding:6px 8px;text-align:center;">Durum</th>
                        <th style="padding:6px 8px;text-align:right;">Tıklama</th>
                        <th style="padding:6px 8px;text-align:right;">Lead</th>
                        <th style="padding:6px 8px;text-align:right;">Dönüşüm</th>
                        <th style="padding:6px 8px;text-align:right;">CVR</th>
                        <th style="padding:6px 8px;text-align:left;">Son Tıklama</th>
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
                        <tr style="border-bottom:1px solid var(--u-line,#e5e9f0);">
                            <td style="padding:6px 8px;font-family:monospace;letter-spacing:.5px;">{{ $link->code }}</td>
                            <td style="padding:6px 8px;font-weight:500;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $link->title }}">{{ $link->title }}</td>
                            <td style="padding:6px 8px;" class="muted">{{ $utmLabel ?: '–' }}</td>
                            <td style="padding:6px 8px;text-align:center;">
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                            <td style="padding:6px 8px;text-align:right;">{{ number_format($link->click_count ?? 0) }}</td>
                            <td style="padding:6px 8px;text-align:right;">{{ $leadCount > 0 ? number_format($leadCount) : '–' }}</td>
                            <td style="padding:6px 8px;text-align:right;">{{ $convertedCount > 0 ? number_format($convertedCount) : '–' }}</td>
                            <td style="padding:6px 8px;text-align:right;">
                                @if($cvr !== null)
                                    <span class="badge {{ $cvr >= 10 ? 'ok' : ($cvr >= 5 ? 'warn' : 'danger') }}">%{{ $cvr }}</span>
                                @else
                                    <span class="muted">–</span>
                                @endif
                            </td>
                            <td style="padding:6px 8px;" class="muted">{{ $link->last_clicked_at ? \Carbon\Carbon::parse($link->last_clicked_at)->format('d.m.Y') : '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

@endsection

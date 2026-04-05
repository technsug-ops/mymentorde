@extends('manager.layouts.app')

@section('title', 'Snapshot #' . $report->id)
@section('page_title', 'Snapshot Detay')

@section('content')

{{-- Başlık & Aksiyonlar --}}
<div style="margin-bottom:12px;">
    <section class="panel" style="margin-bottom:8px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
            <div>
                <strong style="font-size:var(--tx-lg);">Snapshot #{{ $report->id }}</strong>
                <div class="muted" style="margin-top:4px;">
                    Tip: <strong>{{ $report->report_type }}</strong> |
                    Dönem: {{ optional($report->period_start)->toDateString() }} – {{ optional($report->period_end)->toDateString() }} |
                    Advisory: {{ $report->senior_email ?: 'tüm advisoryler' }}
                </div>
                <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">
                    Alıcılar: {{ is_array($report->sent_to) && count($report->sent_to) ? implode(', ', $report->sent_to) : '–' }} |
                    Durum: <strong>{{ $report->send_status ?? 'draft' }}</strong>{{ $report->sent_at ? ' @ ' . $report->sent_at : '' }} |
                    Oluşturan: {{ $report->created_by ?: '–' }} |
                    Tarih: {{ $report->created_at }}
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                <a class="btn" href="/manager/dashboard">← Dashboard</a>
                <a class="btn" href="/manager/dashboard/snapshot/{{ $report->id }}/export-csv">CSV</a>
                <a class="btn btn-primary" target="_blank" href="/manager/dashboard/snapshot/{{ $report->id }}/print">PDF / Yazdır</a>
                @if (($report->send_status ?? 'draft') !== 'sent')
                    <form method="POST" action="/manager/dashboard/snapshot/{{ $report->id }}/mark-sent" style="display:inline;">
                        @csrf
                        <button class="btn" type="submit">Gönderildi İşaretle</button>
                    </form>
                @else
                    <form method="POST" action="/manager/dashboard/snapshot/{{ $report->id }}/mark-draft" style="display:inline;">
                        @csrf
                        <button class="btn" type="submit">Draft'a Geri Al</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
</div>

{{-- KPI --}}
<div class="grid4" style="margin-bottom:12px;">
    <div class="panel">
        <div class="muted">Aylık Gelir</div>
        <div class="kpi" style="font-size:var(--tx-xl);">{{ number_format((float)($stats['monthly_revenue'] ?? 0), 2, ',', '.') }} EUR</div>
    </div>
    <div class="panel">
        <div class="muted">Aktif Öğrenci</div>
        <div class="kpi">{{ (int)($stats['active_students'] ?? 0) }}</div>
    </div>
    <div class="panel">
        <div class="muted">Dönüşüm Oranı</div>
        <div class="kpi">%{{ number_format((float)($stats['conversion_rate'] ?? 0), 1, ',', '.') }}</div>
    </div>
    <div class="panel">
        <div class="muted">Risk Seviyesi</div>
        @php
            $snRiskLvl   = (string)($stats['risk_level'] ?? 'low');
            $snRiskLabel = match($snRiskLvl) { 'low'=>'Düşük', 'good'=>'İyi', 'medium'=>'Orta', 'high'=>'Yüksek', 'critical'=>'Kritik', default=>strtoupper($snRiskLvl) };
            $snRiskClass = match($snRiskLvl) { 'low'=>'ok', 'good'=>'ok', 'medium'=>'warn', 'high'=>'danger', 'critical'=>'danger', default=>'badge' };
        @endphp
        <div class="kpi" style="font-size:var(--tx-lg);">
            <span class="badge {{ $snRiskClass }}">{{ $snRiskLabel }}</span>
            <span class="muted" style="font-size:var(--tx-sm);">({{ (int)($stats['risk_score'] ?? 0) }})</span>
        </div>
    </div>
</div>

{{-- Kıyas --}}
<section class="card" style="margin-bottom:12px;">
    <h2>Kıyas (Önceki Snapshot)</h2>
    @if ($previousReport)
        <div class="muted" style="margin-bottom:10px;font-size:var(--tx-xs);">
            Referans: #{{ $previousReport->id }} |
            {{ optional($previousReport->period_start)->toDateString() }} – {{ optional($previousReport->period_end)->toDateString() }}
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <div class="panel" style="flex:1;min-width:140px;">
                <div class="muted">Gelir Delta</div>
                <div style="font-size:var(--tx-xl);font-weight:700;color:{{ $deltas['monthly_revenue'] >= 0 ? 'var(--u-ok,#21a861)' : 'var(--u-danger,#d33c3c)' }};">
                    {{ $deltas['monthly_revenue'] >= 0 ? '+' : '' }}{{ number_format($deltas['monthly_revenue'], 2, ',', '.') }} EUR
                </div>
            </div>
            <div class="panel" style="flex:1;min-width:140px;">
                <div class="muted">Dönüşüm Delta</div>
                <div style="font-size:var(--tx-xl);font-weight:700;color:{{ $deltas['conversion_rate'] >= 0 ? 'var(--u-ok,#21a861)' : 'var(--u-danger,#d33c3c)' }};">
                    {{ $deltas['conversion_rate'] >= 0 ? '+' : '' }}{{ number_format($deltas['conversion_rate'], 1, ',', '.') }}%
                </div>
            </div>
            <div class="panel" style="flex:1;min-width:140px;">
                <div class="muted">Risk Delta</div>
                <div style="font-size:var(--tx-xl);font-weight:700;color:{{ $deltas['risk_score'] <= 0 ? 'var(--u-ok,#21a861)' : 'var(--u-danger,#d33c3c)' }};">
                    {{ $deltas['risk_score'] >= 0 ? '+' : '' }}{{ number_format($deltas['risk_score'], 1, ',', '.') }}
                </div>
            </div>
        </div>
    @else
        <div class="muted">Bu tip ve filtrede önceki snapshot bulunamadı.</div>
    @endif
</section>

<div class="grid2">

    {{-- Funnel --}}
    <section class="card">
        <h2>Funnel</h2>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                <thead>
                    <tr style="background:var(--u-bg,#f5f7fa);">
                        <th style="padding:6px 8px;text-align:left;font-size:var(--tx-xs);font-weight:600;">Adım</th>
                        <th style="padding:6px 8px;text-align:right;font-size:var(--tx-xs);font-weight:600;">Adet</th>
                        <th style="padding:6px 8px;text-align:right;font-size:var(--tx-xs);font-weight:600;">Oran %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($funnel as $row)
                        <tr style="border-bottom:1px solid var(--u-line,#e5e9f0);">
                            <td style="padding:6px 8px;">{{ $row['label'] ?? '–' }}</td>
                            <td style="padding:6px 8px;text-align:right;font-weight:600;">{{ $row['count'] ?? 0 }}</td>
                            <td style="padding:6px 8px;text-align:right;" class="muted">{{ number_format((float)($row['rate'] ?? 0), 1, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted" style="padding:16px;text-align:center;">Funnel verisi yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Trend --}}
    <section class="card">
        <h2>Trend (Aylık)</h2>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                <thead>
                    <tr style="background:var(--u-bg,#f5f7fa);">
                        <th style="padding:6px 8px;text-align:left;font-size:var(--tx-xs);font-weight:600;">Ay</th>
                        <th style="padding:6px 8px;text-align:right;font-size:var(--tx-xs);font-weight:600;">Gelir</th>
                        <th style="padding:6px 8px;text-align:right;font-size:var(--tx-xs);font-weight:600;">Approval</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trend as $row)
                        <tr style="border-bottom:1px solid var(--u-line,#e5e9f0);">
                            <td style="padding:6px 8px;">{{ $row['label'] ?? '–' }}</td>
                            <td style="padding:6px 8px;text-align:right;font-weight:600;">{{ number_format((float)($row['revenue'] ?? 0), 2, ',', '.') }} EUR</td>
                            <td style="padding:6px 8px;text-align:right;" class="muted">{{ (int)($row['approval_count'] ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted" style="padding:16px;text-align:center;">Trend verisi yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>

@endsection

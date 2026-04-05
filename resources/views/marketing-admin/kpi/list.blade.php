@extends('marketing-admin.layouts.app')

@section('title', 'Oluşturulan Raporlar')

@section('topbar-actions')
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/kpi">← KPI Dashboard</a>
@endsection

@section('page_subtitle', 'Oluşturulan Raporlar — kayıtlı KPI snapshotları')

@section('content')
<style>
.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }
</style>

<div style="display:grid;gap:12px;">

    {{-- Filtre --}}
    <div class="card">
        <form method="GET" action="/mktg-admin/reports" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted,#64748b);">Rapor Tipi</label>
                <input type="text" name="report_type" placeholder="orn: kpi_snapshot" value="{{ $reportType ?? '' }}"
                    style="height:36px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:var(--tx-sm);outline:none;">
            </div>
            <button type="submit" class="btn" style="height:36px;font-size:var(--tx-xs);padding:0 18px;">Filtrele</button>
            <a class="btn alt" style="height:36px;font-size:var(--tx-xs);padding:0 14px;display:flex;align-items:center;" href="/mktg-admin/reports">Temizle</a>
        </form>
    </div>

    {{-- Tablo --}}
    <div class="card">
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th style="width:60px;">#</th>
                    <th>Tip</th>
                    <th>Dönem</th>
                    <th>Oluşturan</th>
                    <th>Tarih</th>
                    <th style="text-align:right;">İndir</th>
                </tr></thead>
                <tbody>
                    @forelse(($reports ?? []) as $report)
                    <tr>
                        <td style="color:var(--u-muted,#64748b);">#{{ $report->id }}</td>
                        <td>{{ $report->report_type }}</td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                            {{ optional($report->period_start)->toDateString() }} – {{ optional($report->period_end)->toDateString() }}
                        </td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $report->created_by ?: '—' }}</td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ optional($report->created_at)->format('Y-m-d H:i') }}</td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:4px;justify-content:flex-end;">
                                <a class="btn alt" style="font-size:var(--tx-xs);padding:3px 9px;" href="/mktg-admin/reports/{{ $report->id }}/download/csv">CSV</a>
                                <a class="btn alt" style="font-size:var(--tx-xs);padding:3px 9px;" href="/mktg-admin/reports/{{ $report->id }}/download/json">JSON</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Rapor yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($reports, 'links'))
        <div style="margin-top:12px;">{{ $reports->links() }}</div>
        @endif
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Oluşturulan Raporlar</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📁 Rapor Arşivi</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>KPI Dashboard'dan oluşturulan tüm snapshot'lar burada arşivlenir</li>
                    <li>Her rapor: dönem aralığı, oluşturan kişi ve tarih içerir</li>
                    <li>Rapor tipi filtresi ile sadece ilgili türü görüntüle</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">⬇️ İndirme & Kullanım</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li><strong>CSV:</strong> Excel/Google Sheets ile analiz için</li>
                    <li><strong>JSON:</strong> API entegrasyonu veya BI araçları için</li>
                    <li>Aylık toplantılardan önce ilgili dönem raporunu indir ve sun</li>
                </ul>
            </div>
        </div>
    </details>

</div>
@endsection

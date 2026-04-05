@extends('marketing-admin.layouts.app')

@section('title', 'Zamanlanmış Raporlar')

@section('page_subtitle', 'Zamanlanmış Raporlar — kayıtlı KPI snapshotları')

@section('topbar-actions')
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/kpi">← KPI Dashboard</a>
@endsection

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

    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Zamanlanmış & Oluşturulan Raporlar</div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th>Rapor Tipi</th>
                    <th>Dönem Başlangıç</th>
                    <th>Dönem Bitiş</th>
                    <th>Oluşturan</th>
                    <th>Tarih</th>
                    <th style="text-align:right;">İndir</th>
                </tr></thead>
                <tbody>
                    @forelse(($reports ?? []) as $report)
                    <tr>
                        <td><span class="badge info">{{ $report->report_type }}</span></td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $report->period_start ? \Carbon\Carbon::parse($report->period_start)->format('d.m.Y') : '—' }}</td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $report->period_end ? \Carbon\Carbon::parse($report->period_end)->format('d.m.Y') : '—' }}</td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $report->created_by ?? '—' }}</td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $report->created_at->format('d.m.Y H:i') }}</td>
                        <td style="text-align:right;">
                            <a class="btn alt" style="padding:3px 10px;font-size:var(--tx-xs);" href="/mktg-admin/reports/{{ $report->id }}/download/pdf">PDF</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Henüz rapor oluşturulmamış.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($reports, 'links'))
        <div style="margin-top:12px;">{{ $reports->links('partials.pagination') }}</div>
        @endif
    </div>


    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Zamanlanmış Raporlar</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
            <li>Zamanlanmış raporlar belirli dönemlerde otomatik oluşturulur ve arşivlenir</li>
            <li>Haftalık/aylık rapor planla → yönetim sunumları için hazır veri</li>
            <li>Tamamlanan raporları Oluşturulan Raporlar menüsünden CSV/JSON indir</li>
            <li>Zamanlama iptal edilirse mevcut arşiv kayıtları korunur</li>
        </ul>
    </details>

</div>
@endsection

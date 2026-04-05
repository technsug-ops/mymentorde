@extends('marketing-admin.layouts.app')

@section('title', 'Etkinlik Raporu')
@section('page_subtitle', 'Etkinlik performans raporu ve katılım analizi')

@section('content')
<style>
    .rp-page { display:grid; gap:12px; }
    .rp-top { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:10px; }
    .rp-kpi .label { color: var(--muted); font-size:12px; }
    .rp-kpi .val { color:#0a67d8; font-size:24px; font-weight:700; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    .table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:620px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--line); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; }
    @media (max-width: 1100px) { .rp-top { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="rp-page">
    <section class="card">
        <h3 style="margin:0 0 8px;">Etkinlik Raporu: {{ $event->title_tr }}</h3>
        <div class="tabs">
            <a class="tab" href="/mktg-admin/events/{{ $event->id }}/registrations">Kayıtlar</a>
            <a class="tab active" href="/mktg-admin/events/{{ $event->id }}/report">Rapor</a>
            <a class="tab" href="/mktg-admin/events/{{ $event->id }}/survey-results">Anket</a>
            <a class="tab" href="/mktg-admin/events">Etkinlikler</a>
        </div>
    </section>

    <section class="rp-top">
        <article class="card rp-kpi"><div class="label">Toplam Kayıt</div><div class="val">{{ $summary['total'] ?? 0 }}</div></article>
        <article class="card rp-kpi"><div class="label">Attended</div><div class="val">{{ $summary['attended'] ?? 0 }}</div></article>
        <article class="card rp-kpi"><div class="label">Attendance %</div><div class="val">{{ number_format((float) ($summary['attendance_rate'] ?? 0), 1, '.', ',') }}%</div></article>
        <article class="card rp-kpi"><div class="label">Survey Avg</div><div class="val">{{ number_format((float) ($summary['survey_score'] ?? 0), 1, '.', ',') }}</div></article>
    </section>

    <section class="card">
        <h4 style="margin:0 0 8px;">Status Dagilimi</h4>
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr><th>Status</th><th>Adet</th></tr></thead>
                <tbody>
                @forelse(($statusRows ?? []) as $r)
                    <tr><td>{{ $r->status }}</td><td>{{ $r->total }}</td></tr>
                @empty
                    <tr><td colspan="2" class="muted">Status verisi yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Etkinlik Raporu</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
            <li>Kayıt dağılımı (registered/attended/no_show) ve devam oranı özeti</li>
            <li>Katılımcı durumlarını güncellemek için Kayıtlar sekmesini kullan</li>
            <li>Anket skoru ortalaması — sadece puan girilen kayıtlardan hesaplanır</li>
            <li>Raporu Ctrl+P ile PDF olarak yönetim sunumuna ekle</li>
        </ul>
    </details>
</div>
@endsection


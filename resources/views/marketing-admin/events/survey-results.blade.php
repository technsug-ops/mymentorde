@extends('marketing-admin.layouts.app')

@section('title', 'Anket Sonuçları')
@section('page_subtitle', 'Etkinlik anket yanıtları ve memnuniyet analizi')

@section('content')
<style>
    .sv-page { display:grid; gap:12px; }
    .sv-top { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:10px; }
    .sv-kpi .label { color: var(--muted); font-size:12px; }
    .sv-kpi .val { color:#0a67d8; font-size:24px; font-weight:700; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    .table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:980px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--line); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; vertical-align:top; }
</style>

<div class="sv-page">
    <section class="card">
        <h3 style="margin:0 0 8px;">Anket Sonuçları: {{ $event->title_tr }}</h3>
        <div class="tabs">
            <a class="tab" href="/mktg-admin/events/{{ $event->id }}/registrations">Kayıtlar</a>
            <a class="tab" href="/mktg-admin/events/{{ $event->id }}/report">Rapor</a>
            <a class="tab active" href="/mktg-admin/events/{{ $event->id }}/survey-results">Anket</a>
            <a class="tab" href="/mktg-admin/events">Etkinlikler</a>
        </div>
    </section>

    <section class="sv-top">
        <article class="card sv-kpi"><div class="label">Tamamlanan Anket</div><div class="val">{{ $surveyCount ?? 0 }}</div></article>
        <article class="card sv-kpi"><div class="label">Ortalama Puan</div><div class="val">{{ number_format((float) ($avgScore ?? 0), 1, '.', ',') }}</div></article>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Katilimci</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Survey</th>
                        <th>Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($rows ?? []) as $row)
                        <tr>
                            <td>#{{ $row->id }}</td>
                            <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                            <td>{{ $row->email }}</td>
                            <td>{{ $row->status }}</td>
                            <td>{{ $row->survey_completed ? 'tamamlandı' : 'hayir' }} | score: {{ $row->survey_score ?: '-' }}</td>
                            <td>{{ $row->survey_feedback ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Anket verisi yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px;">{{ $rows->links() }}</div>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Anket Sonuçları</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
            <li>Sadece anket puanı girilmiş katılımcılar listelenir</li>
            <li>Puan ortalaması → etkinlik kalitesini ve katılımcı memnuniyetini ölçer</li>
            <li>Düşük puanlı geri bildirimleri önceliklendir → bir sonraki etkinliği iyileştir</li>
            <li>4+ puan: iyi · 3 puan: orta · 1–2 puan: iyileştirme gerekiyor</li>
        </ul>
    </details>
</div>
@endsection


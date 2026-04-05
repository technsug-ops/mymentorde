@extends('marketing-admin.layouts.app')

@section('title', 'Kampanya İstatistikleri')
@section('page_subtitle', 'E-posta kampanyası açılma, tıklama ve dönüşüm istatistikleri')

@section('content')
<style>
    .st-page { display:grid; gap:12px; }
    .st-top { display:grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap:10px; }
    .st-kpi .label { color: var(--muted); font-size:12px; }
    .st-kpi .val { color:#0a67d8; font-size:24px; font-weight:700; }
    .table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:860px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--line); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; vertical-align:top; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    @media (max-width: 1100px) { .st-top { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="st-page">
    <section class="card">
        <h3 style="margin:0 0 8px;">Kampanya Istatistikleri: {{ $campaign->name }}</h3>
        <div class="tabs">
            <a class="tab" href="/mktg-admin/email/templates">Templates</a>
            <a class="tab active" href="/mktg-admin/email/campaigns">Campaigns</a>
            <a class="tab" href="/mktg-admin/email/log?campaign_id={{ $campaign->id }}">Send Log</a>
        </div>
        <div class="muted" style="margin-top:8px;">
            campaign_id: {{ $campaign->id }} | template: {{ $campaign->template->name ?? '-' }} | status: {{ $campaign->status }}
        </div>
    </section>

    <section class="st-top">
        <article class="card st-kpi"><div class="label">Sent</div><div class="val">{{ $kpi['sent'] ?? 0 }}</div></article>
        <article class="card st-kpi"><div class="label">Opened</div><div class="val">{{ $kpi['opened'] ?? 0 }}</div></article>
        <article class="card st-kpi"><div class="label">Clicked</div><div class="val">{{ $kpi['clicked'] ?? 0 }}</div></article>
        <article class="card st-kpi"><div class="label">Open Rate</div><div class="val">{{ number_format((float) ($kpi['open_rate'] ?? 0), 1, '.', ',') }}%</div></article>
        <article class="card st-kpi"><div class="label">Click Rate</div><div class="val">{{ number_format((float) ($kpi['click_rate'] ?? 0), 1, '.', ',') }}%</div></article>
    </section>

    <section class="card">
        <h4 style="margin:0 0 8px;">Son Gönderim Kayıtlari</h4>
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th>Opened</th>
                        <th>Clicked</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($logs ?? []) as $row)
                        <tr>
                            <td>#{{ $row->id }}</td>
                            <td>{{ $row->recipient_email }}</td>
                            <td>{{ $row->subject }}</td>
                            <td>{{ $row->status }}</td>
                            <td>{{ $row->sent_at ?: '-' }}</td>
                            <td>{{ $row->opened_at ?: '-' }}</td>
                            <td>{{ $row->clicked_at ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">Send log yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — E-posta Kampanya İstatistikleri</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
            <li>Seçili kampanyanın toplu açılma, tıklanma ve bounce oranları burada özetlenir</li>
            <li>Açılma/Tıklanma oranları Send Log kayıtlarından gerçek zamanlı hesaplanır</li>
            <li>Open rate %20+ iyi · Click rate %3+ iyi · Bounce %2+ dikkat</li>
            <li>Detaylı alıcı analizi için Send Log sekmesine geç</li>
        </ul>
    </details>
</div>
@endsection


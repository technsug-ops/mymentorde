@extends('marketing-admin.layouts.app')

@section('title', 'İçerik İstatistikleri')
@section('page_subtitle', 'CMS içerik görüntülenme ve etkileşim istatistikleri')

@section('content')
<style>
    .cs-page { display:grid; gap:12px; }
    .cs-top { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:10px; }
    .cs-kpi .label { color: var(--muted); font-size:12px; }
    .cs-kpi .val { color:#0a67d8; font-size:22px; font-weight:700; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    @media (max-width: 1100px) { .cs-top { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="cs-page">
    <section class="card">
        <h3 style="margin:0 0 8px;">İçerik Stats: {{ $content->title_tr }}</h3>
        <div class="tabs">
            <a class="tab" href="/mktg-admin/content">İçerik</a>
            <a class="tab active" href="/mktg-admin/content/{{ $content->id }}/stats">Stats</a>
            <a class="tab" href="/mktg-admin/content/{{ $content->id }}/revisions">Revisions</a>
        </div>
    </section>

    <section class="cs-top">
        <article class="card cs-kpi"><div class="label">Total Views</div><div class="val">{{ $summary['views'] ?? 0 }}</div></article>
        <article class="card cs-kpi"><div class="label">Unique Views</div><div class="val">{{ $summary['unique_views'] ?? 0 }}</div></article>
        <article class="card cs-kpi"><div class="label">Avg Read (sn)</div><div class="val">{{ $summary['avg_read'] ?? 0 }}</div></article>
        <article class="card cs-kpi"><div class="label">Bounce %</div><div class="val">{{ number_format((float) ($summary['bounce'] ?? 0), 2, ',', '.') }}</div></article>
    </section>

    <section class="cs-top">
        <article class="card cs-kpi"><div class="label">Shares</div><div class="val">{{ $summary['shares'] ?? 0 }}</div></article>
        <article class="card cs-kpi"><div class="label">Lead Count</div><div class="val">{{ $summary['lead_count'] ?? 0 }}</div></article>
        <article class="card cs-kpi"><div class="label">Lead Converted</div><div class="val">{{ $summary['lead_converted'] ?? 0 }}</div></article>
        <article class="card cs-kpi"><div class="label">Lead Conversion %</div><div class="val">{{ number_format((float) ($summary['lead_conversion_rate'] ?? 0), 2, ',', '.') }}</div></article>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — İçerik İstatistikleri</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
            <li>İçerik görüntülenme, benzersiz ziyaret ve ortalama okuma süresi özetlenir</li>
            <li><strong>Lead Conversion:</strong> Bu içerikten gelen ve başvuruya dönüşen ziyaretçi oranı</li>
            <li>Yüksek bounce → içerik metni veya CTA yapısını iyileştir</li>
            <li>Düşük okuma süresi → başlık ilgi çekiyor ama içerik tutmuyor</li>
        </ul>
    </details>
</div>
@endsection


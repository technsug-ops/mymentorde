@extends('marketing-admin.layouts.app')

@section('title', 'Segment Önizleme')
@section('page_subtitle', 'E-posta segmenti üye listesi önizlemesi')

@section('content')
<style>
    .pv-page { display:grid; gap:12px; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    .table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:980px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--line); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; vertical-align:top; }
    .badge { display:inline-flex; border-radius:999px; padding:2px 8px; font-size:12px; font-weight:700; }
    .b-active { background:#e9f9ef; color:#1f7a3f; border:1px solid #b9e7c8; }
    .b-passive { background:#fff0f0; color:#9f2020; border:1px solid #f2c2c2; }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }
</style>

<div class="pv-page">
    <section class="card">
        <h3 style="margin:0 0 8px;">Segment Onizleme: {{ $segment->name }}</h3>
        <div class="tabs">
            <a class="tab" href="/mktg-admin/email/templates">Templates</a>
            <a class="tab active" href="/mktg-admin/email/segments">Segments</a>
            <a class="tab" href="/mktg-admin/email/campaigns">Campaigns</a>
            <a class="tab" href="/mktg-admin/email/log">Send Log</a>
        </div>
        <div class="muted" style="margin-top:8px;">
            segment_id: {{ $segment->id }} | type: {{ $segment->type }} | uye: {{ $memberCount }} | active: {{ $segment->is_active ? 'evet' : 'hayir' }}
        </div>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Öğrenci/Dealer/Eğitim Danışmanı Kodları</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($members ?? []) as $m)
                        <tr>
                            <td class="mono">#{{ $m->id }}</td>
                            <td>{{ $m->name }}</td>
                            <td>{{ $m->email }}</td>
                            <td>{{ $m->role }}</td>
                            <td><span class="badge {{ $m->is_active ? 'b-active' : 'b-passive' }}">{{ $m->is_active ? 'active' : 'passive' }}</span></td>
                            <td class="mono">
                                st:{{ $m->student_id ?: '-' }} |
                                dl:{{ $m->dealer_code ?: '-' }} |
                                sn:{{ $m->senior_code ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Bu segmente uygun uye yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Segment Önizlemesi</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
            <li>Bu ekran segmentin gerçek üye listesini gösterir — kampanya öncesi son kontrol noktası</li>
            <li>Dynamic segmentte kuralı değiştirdikten sonra listeyi tekrar kontrol et</li>
            <li>Yanlış alıcı riski olmadığından emin olmak için kampanya göndermeden önce doğrula</li>
            <li>Üye sayısı çok azsa segment kuralları çok dar — genişlet veya alternatif segment kullan</li>
        </ul>
    </details>
</div>
@endsection


@extends('marketing-admin.layouts.app')

@section('title', 'İçerik Takvimi')
@section('page_subtitle', 'Sosyal medya içerik takvimi ve planlama')

@section('content')

@php
use Illuminate\Support\Carbon;

$calStart  = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
$calEnd    = $calStart->copy()->endOfMonth();
$prevMonth = $calStart->copy()->subMonth()->format('Y-m');
$nextMonth = $calStart->copy()->addMonth()->format('Y-m');
$today     = Carbon::today()->toDateString();
$dayLabels = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

// Grid: Pazartesi'den başla
$gridStart = $calStart->copy()->startOfWeek(Carbon::MONDAY);
$gridEnd   = $calEnd->copy()->endOfWeek(Carbon::SUNDAY);

// Hafta dizisi oluştur
$weeks  = [];
$cursor = $gridStart->copy();
while ($cursor <= $gridEnd) {
    $week = [];
    for ($i = 0; $i < 7; $i++) {
        $dateKey = $cursor->format('Y-m-d');
        $week[]  = [
            'date'    => $dateKey,
            'day'     => (int) $cursor->format('j'),
            'inMonth' => $cursor->month === $calStart->month,
            'isToday' => $dateKey === $today,
            'posts'   => $grouped->get($dateKey, collect()),
        ];
        $cursor->addDay();
    }
    $weeks[] = $week;
}

$platformColors = [
    'instagram' => ['#e1306c','#fff'],
    'facebook'  => ['#1877f2','#fff'],
    'twitter'   => ['#1da1f2','#fff'],
    'linkedin'  => ['#0a66c2','#fff'],
    'youtube'   => ['#ff0000','#fff'],
    'tiktok'    => ['#010101','#fff'],
];
@endphp

<style>
    .cal-page { display:grid; gap:12px; }

    /* Tabs */
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab  { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }

    /* Navigation */
    .cal-nav { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
    .cal-title { font-size:18px; font-weight:800; color:#173454; }
    .cal-nav-btns { display:flex; gap:6px; align-items:center; }
    .cal-nav-btns a { border:1px solid #d2deea; border-radius:8px; padding:6px 12px; font-size:13px; font-weight:700; color:#204d87; background:#eef4fb; text-decoration:none; }
    .cal-nav-btns a:hover { background:#dce8f7; }
    .cal-nav-btns a.active-month { background:#0a67d8; color:#fff; border-color:#0a67d8; }

    /* Calendar grid */
    .cal-grid { border:1px solid #d2deea; border-radius:12px; overflow:hidden; }

    .cal-header { display:grid; grid-template-columns: repeat(7, minmax(0,1fr)); border-bottom:1px solid #d2deea; }
    .cal-header-cell { padding:8px 6px; text-align:center; font-size:11px; font-weight:700; color:#2b4d74; text-transform:uppercase; letter-spacing:.4px; background:#f0f5fc; }
    .cal-header-cell:nth-child(6),
    .cal-header-cell:nth-child(7) { color:#a01919; }

    .cal-week { display:grid; grid-template-columns: repeat(7, minmax(0,1fr)); border-top:1px solid #e8eef5; }
    .cal-week:first-of-type { border-top:none; }

    .cal-day {
        min-height: 90px;
        padding: 6px 5px 5px;
        border-right: 1px solid #e8eef5;
        background: #fff;
        vertical-align: top;
        font-size: 12px;
        position: relative;
    }
    .cal-day:last-child { border-right: none; }
    .cal-day.out-of-month { background: #f8f9fc; }
    .cal-day.is-today { background: #f0f7ff; }
    .cal-day.is-today .cal-day-num { background:#0a67d8; color:#fff; border-radius:50%; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; }

    .cal-day-num {
        font-size: 12px;
        font-weight: 700;
        color: #2b4d74;
        margin-bottom: 4px;
        display:inline-block;
    }
    .out-of-month .cal-day-num { color: #b0bdd0; font-weight: 400; }

    .cal-event {
        display: block;
        border-radius: 4px;
        padding: 2px 5px;
        margin-bottom: 2px;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        cursor: default;
    }
    .cal-more {
        font-size: 10px;
        color: #5a7a9e;
        font-weight: 700;
        margin-top: 1px;
        cursor: pointer;
        display: inline-block;
    }
    .cal-more:hover { color: #0a67d8; }

    /* Platform legend */
    .legend { display:flex; flex-wrap:wrap; gap:8px; }
    .legend-item { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; }
    .legend-dot { width:10px; height:10px; border-radius:3px; display:inline-block; }

    /* List section */
    .list-item { border:1px solid #e8eef5; border-radius:8px; padding:8px; font-size:13px; background:#fafcff; margin-bottom:6px; }
    .list-item .meta { color:#6b7c93; font-size:12px; }

    @media (max-width: 900px) {
        .cal-day { min-height: 60px; padding: 4px 3px; }
        .cal-event { font-size: 9px; padding: 1px 3px; }
        .cal-day-num { font-size: 11px; }
    }
    @media (max-width: 600px) {
        .cal-day { min-height: 44px; }
        .cal-header-cell { font-size: 10px; padding: 5px 2px; }
    }
</style>

<div class="cal-page">

    {{-- Tabs --}}
    <section class="card">
        <h3 style="margin:0 0 8px;">Sosyal Medya Takvimi</h3>
        <div class="tabs">
            <a class="tab" href="/mktg-admin/social/accounts">Hesaplar</a>
            <a class="tab" href="/mktg-admin/social/posts">Postlar</a>
            <a class="tab" href="/mktg-admin/social/metrics">Metrikler</a>
            <a class="tab active" href="/mktg-admin/social/calendar">Takvim</a>
        </div>
    </section>

    {{-- Navigation --}}
    <section class="card">
        <div class="cal-nav">
            <div class="cal-title">
                {{ $calStart->locale('tr')->isoFormat('MMMM YYYY') }}
                @if ($rows->count() > 0)
                    <span style="font-size:var(--tx-sm);font-weight:400;color:#6b7c93;margin-left:6px;">{{ $rows->count() }} gönderi</span>
                @endif
            </div>
            <div class="cal-nav-btns">
                <a href="?month={{ $prevMonth }}">← Önceki</a>
                <a href="?month={{ now()->format('Y-m') }}" class="{{ $month === now()->format('Y-m') ? 'active-month' : '' }}">Bu Ay</a>
                <a href="?month={{ $nextMonth }}">Sonraki →</a>
            </div>
        </div>
    </section>

    {{-- Platform legend --}}
    @if ($rows->count() > 0)
    <section class="card" style="padding:10px 14px;">
        <div class="legend">
            @foreach ($platformColors as $platform => [$bg, $fg])
                <span class="legend-item">
                    <span class="legend-dot" style="background:{{ $bg }};"></span>
                    {{ ucfirst($platform) }}
                </span>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Calendar grid --}}
    <section class="card" style="padding:0;overflow:hidden;">
        <div class="cal-grid">
            <div class="cal-header">
                @foreach ($dayLabels as $lbl)
                    <div class="cal-header-cell">{{ $lbl }}</div>
                @endforeach
            </div>

            @foreach ($weeks as $week)
                <div class="cal-week">
                    @foreach ($week as $cell)
                        @php
                            $posts    = $cell['posts'];
                            $maxShow  = 3;
                            $overflow = max(0, $posts->count() - $maxShow);
                        @endphp
                        <div class="cal-day {{ $cell['inMonth'] ? '' : 'out-of-month' }} {{ $cell['isToday'] ? 'is-today' : '' }}">
                            <span class="cal-day-num">{{ $cell['day'] }}</span>

                            @foreach ($posts->take($maxShow) as $post)
                                @php
                                    $platform = strtolower((string) ($post->platform ?? 'other'));
                                    [$bg, $fg] = $platformColors[$platform] ?? ['#8a9bb0','#fff'];
                                    $label = \Illuminate\Support\Str::limit((string) ($post->caption ?? $post->post_type ?? $platform), 24) ?: ucfirst($platform);
                                    $statusIcon = match($post->status ?? '') {
                                        'published' => '✓',
                                        'scheduled' => '⏱',
                                        'draft'     => '✏',
                                        'failed'    => '✗',
                                        default     => '',
                                    };
                                    $title = "[#{$post->id}] {$post->status} · {$platform}\n{$post->caption}";
                                @endphp
                                <span class="cal-event"
                                      style="background:{{ $bg }};color:{{ $fg }};"
                                      title="{{ $title }}">
                                    {{ $statusIcon }} {{ $label }}
                                </span>
                            @endforeach

                            @if ($overflow > 0)
                                <span class="cal-more" title="{{ $overflow }} gönderi daha...">+{{ $overflow }} daha</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>

    {{-- Liste görünümü (compact, tüm gönderiler) --}}
    @if ($rows->count() > 0)
    <section class="card">
        <details>
            <summary style="font-size:var(--tx-sm);font-weight:700;color:#0a67d8;cursor:pointer;user-select:none;">
                Gün Bazlı Liste — {{ $rows->count() }} gönderi
            </summary>
            <div style="margin-top:12px;">
                @foreach ($grouped->sortKeys() as $day => $items)
                    <div style="margin-bottom:10px;">
                        <div style="font-size:var(--tx-xs);font-weight:700;color:#5e7492;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">
                            {{ \Carbon\Carbon::parse($day)->locale('tr')->isoFormat('D MMMM YYYY, dddd') }}
                        </div>
                        @foreach ($items as $row)
                            <div class="list-item">
                                @php
                                    $plt = strtolower((string)($row->platform ?? ''));
                                    [$bg2, $fg2] = $platformColors[$plt] ?? ['#8a9bb0','#fff'];
                                @endphp
                                <strong>
                                    <span style="background:{{ $bg2 }};color:{{ $fg2 }};border-radius:4px;padding:1px 6px;font-size:var(--tx-xs);margin-right:4px;">{{ $row->platform }}</span>
                                    #{{ $row->id }} {{ $row->post_type }}
                                </strong>
                                — <span class="badge {{ match($row->status ?? '') { 'published'=>'ok','scheduled'=>'info','failed'=>'danger',default=>'pending' } }}">{{ $row->status }}</span>
                                <div class="meta" style="margin-top:3px;">
                                    {{ $row->account->account_name ?? '-' }}
                                    @if ($row->scheduled_at)
                                        · {{ \Carbon\Carbon::parse($row->scheduled_at)->format('H:i') }}
                                    @endif
                                    @if (trim((string)($row->caption ?? '')) !== '')
                                        · {{ \Illuminate\Support\Str::limit($row->caption, 80) }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </details>
    </section>
    @else
        <section class="card">
            <div class="muted">Bu dönemde gönderi bulunamadı.</div>
        </section>
    @endif


{{-- Rehber --}}
<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — İçerik Takvimi</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📅 Takvim Görünümü</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Haftanın her günü için planlanmış postları görüntüler</li>
                <li>Her kart: platform, hesap, başlık ve durum bilgisi içerir</li>
                <li>Ay/hafta filtresi ile ilerleyen dönemleri planla</li>
                <li>Boş gün yoksa içerik sıklığı yetersiz — haftada min. 3–5 post hedefle</li>
            </ul>
        </div>
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">✏️ İçerik Planlama</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Postlar → Yeni Post Ekle → Yayın tarihini seç → Takvimde görünür</li>
                <li>Büyük etkinlikler öncesi (fuar, başvuru dönemi) yoğun içerik planla</li>
                <li>Hafta sonu: eğitim/ilham içerikleri · İş günü: bilgilendirme ve CTA</li>
                <li>Takvimde boşluk görürsen Postlar menüsünden hızlı gönderi oluştur</li>
            </ul>
        </div>
    </div>
</details>

</div>
@endsection

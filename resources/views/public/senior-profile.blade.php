<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $seoTitle }} · MentörDE</title>
    @include('partials.favicon')
    <meta name="description" content="{{ $seoDesc }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:type" content="profile">
    <meta property="og:url" content="{{ url()->current() }}">
    @if(!empty($p['avatar']))
        <meta property="og:image" content="{{ $p['avatar'] }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="robots" content="index, follow">

    <link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">

    {{-- JSON-LD Person + AggregateRating + Service (SEO) --}}
    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type'    => 'Person',
            'name'     => $p['display_name'],
            'jobTitle' => $p['tagline'] ?: 'Mentörde Uzman Danışmanı',
            'description' => $p['bio'] ?: $p['tagline'],
            'url'      => url()->current(),
        ];
        if (!empty($p['avatar'])) {
            $jsonLd['image'] = $p['avatar'];
        }
        if (!empty($p['languages'])) {
            $jsonLd['knowsLanguage'] = array_values($p['languages']);
        }
        if (($p['stats']['total_reviews'] ?? 0) > 0 && ($p['stats']['avg_rating'] ?? null) !== null) {
            $jsonLd['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => number_format((float) $p['stats']['avg_rating'], 2, '.', ''),
                'reviewCount' => (int) $p['stats']['total_reviews'],
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }
        $jsonLdService = [
            '@context' => 'https://schema.org',
            '@type'    => 'Service',
            'name'     => 'Mentörlük Görüşmesi · ' . $p['display_name'],
            'provider' => [
                '@type' => 'Person',
                'name'  => $p['display_name'],
            ],
            'serviceType'      => 'Mentoring',
            'areaServed'       => 'DE,TR',
            'description'      => $p['tagline'] ?: 'Almanya yolunda birebir danışmanlık.',
            'availableChannel' => [
                '@type' => 'ServiceChannel',
                'serviceUrl' => $p['booking_url'],
            ],
        ];
    @endphp
    <script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! json_encode($jsonLdService, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

    <style nonce="{{ $cspNonce ?? '' }}">
        :root {
            --primary:#7e58bf;
            --primary-dark:#6c47a8;
            --primary-deep:#5a3a8d;
            --primary-mid:#a07ed9;
            --primary-light:#b79ae9;
            --primary-soft:#efe9fb;
            --neutral:#e9e7e2;
            --neutral-soft:#faf9f5;
            --text:#1a1325;
            --muted:#6b6377;
            --line:#e3dcec;
            --line-soft:#f0ecf6;
            --surface:#ffffff;
            --bg:#faf9f5;
            --success-bg:#e8f5ed;
            --success-text:#2d8b55;
            --gold:#f5b400;
            --gold-soft:#fff6dd;
            --font-base:"Space Grotesk", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { box-sizing:border-box; }
        html, body { margin:0; padding:0; }
        body {
            font-family:var(--font-base);
            color:var(--text);
            background:linear-gradient(160deg, #f7f3ff 0%, #faf9f5 55%, #f1edfb 100%);
            line-height:1.6; font-size:15px; -webkit-font-smoothing:antialiased;
        }
        a { color:var(--primary); text-decoration:none; }
        a:hover { text-decoration:underline; }
        .container { max-width:1140px; margin:0 auto; padding:0 22px; }

        /* === NAV === */
        .p-nav {
            position:sticky; top:0; z-index:50;
            background:rgba(255,255,255,.92); backdrop-filter:blur(12px);
            border-bottom:1px solid var(--line);
        }
        .p-nav-inner { max-width:1140px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; gap:16px; }
        .p-logo { font-size:22px; font-weight:700; color:var(--primary); letter-spacing:-.5px; }
        .p-logo span { color:var(--primary-deep); font-style:italic; }
        .p-nav-cta {
            background:var(--primary); color:#fff; padding:9px 18px; border-radius:8px;
            font-weight:600; font-size:13px; transition:.2s;
        }
        .p-nav-cta:hover { background:var(--primary-dark); text-decoration:none !important; transform:translateY(-1px); }
        .p-back {
            color:var(--muted); font-size:13px; display:inline-flex; align-items:center; gap:6px;
        }
        .p-back:hover { color:var(--primary); text-decoration:none; }

        /* === HERO === */
        .p-hero { padding:40px 0 28px; }
        .p-hero-grid {
            display:grid; grid-template-columns:1fr 280px; gap:32px; align-items:flex-start;
        }
        @media(max-width:880px){ .p-hero-grid { grid-template-columns:1fr; } }

        .p-hero-main {
            background:#fff; border:1px solid var(--line); border-radius:18px;
            padding:32px; box-shadow:0 4px 28px rgba(126,88,191,.08);
        }
        .p-identity { display:flex; gap:22px; align-items:center; margin-bottom:18px; }
        .p-avatar {
            width:96px; height:96px; border-radius:50%;
            background:linear-gradient(135deg, var(--primary) 0%, var(--primary-deep) 100%);
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-weight:700; font-size:34px; letter-spacing:-1px;
            flex-shrink:0; overflow:hidden;
        }
        .p-avatar img { width:100%; height:100%; object-fit:cover; }
        .p-name { font-size:28px; font-weight:700; color:var(--text); margin:0 0 4px; letter-spacing:-.02em; }
        .p-tagline { color:var(--muted); font-size:15px; margin:0; line-height:1.5; }

        .p-rating-row {
            display:flex; align-items:center; gap:10px; flex-wrap:wrap;
            margin:6px 0 14px; padding:12px 14px; background:var(--gold-soft);
            border-radius:10px; border:1px solid #f6e3a3;
        }
        .p-stars { display:inline-flex; gap:1px; color:var(--gold); }
        .p-rating-val { font-weight:700; color:var(--text); }
        .p-rating-cnt { color:var(--muted); font-size:13.5px; }
        .p-rating-empty { color:var(--muted); font-size:13.5px; font-style:italic; }

        .p-meta-pills { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
        .p-pill {
            display:inline-flex; align-items:center; gap:6px;
            padding:6px 12px; border-radius:18px;
            background:var(--primary-soft); color:var(--primary-deep);
            font-size:12.5px; font-weight:600;
        }
        .p-pill.muted { background:var(--neutral-soft); color:var(--muted); border:1px solid var(--line); }

        /* Hero sidebar (CTA card) */
        .p-cta-card {
            background:#fff; border:1px solid var(--line); border-radius:16px;
            padding:24px; box-shadow:0 4px 24px rgba(126,88,191,.10);
            position:sticky; top:84px;
        }
        .p-cta-card h3 { margin:0 0 6px; font-size:17px; font-weight:700; }
        .p-cta-card p  { margin:0 0 16px; color:var(--muted); font-size:13.5px; }
        .p-cta-card .p-cta-stats {
            display:flex; gap:14px; margin-bottom:18px;
            padding:14px 0; border-top:1px dashed var(--line); border-bottom:1px dashed var(--line);
        }
        .p-cta-stat { flex:1; text-align:center; }
        .p-cta-stat-val { display:block; font-weight:700; color:var(--text); font-size:18px; }
        .p-cta-stat-lbl { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
        .p-cta-btn {
            display:flex; align-items:center; justify-content:center; gap:8px;
            width:100%; background:var(--primary); color:#fff;
            padding:13px 18px; border-radius:10px; font-weight:600; font-size:14.5px;
            transition:.2s;
        }
        .p-cta-btn:hover { background:var(--primary-dark); text-decoration:none !important; transform:translateY(-1px); }

        /* === TABS === */
        .p-tabs {
            display:flex; gap:0; border-bottom:1px solid var(--line); margin:30px 0 22px;
            overflow-x:auto;
        }
        .p-tab {
            padding:14px 22px; font-weight:600; font-size:14px;
            color:var(--muted); cursor:pointer; background:transparent;
            border:0; border-bottom:3px solid transparent; transition:.2s;
            white-space:nowrap;
        }
        .p-tab:hover { color:var(--primary); }
        .p-tab.active { color:var(--primary); border-bottom-color:var(--primary); }

        .p-panel { display:none; animation:fade .3s ease; }
        .p-panel.active { display:block; }
        @keyframes fade { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:translateY(0); } }

        /* === SECTIONS === */
        .p-section {
            background:#fff; border:1px solid var(--line); border-radius:14px;
            padding:26px; margin-bottom:18px;
        }
        .p-section-h { font-size:17px; font-weight:700; margin:0 0 14px; color:var(--text); }
        .p-bio { color:var(--text); font-size:14.5px; line-height:1.7; white-space:pre-wrap; }

        .p-list { list-style:none; padding:0; margin:0; }
        .p-list li { padding:10px 0; border-bottom:1px solid var(--line-soft); display:flex; gap:12px; align-items:center; }
        .p-list li:last-child { border-bottom:0; }

        /* Rating breakdown bars */
        .p-breakdown { display:flex; flex-direction:column; gap:8px; margin-bottom:20px; }
        .p-bd-row { display:flex; align-items:center; gap:10px; font-size:13px; }
        .p-bd-label { width:42px; color:var(--muted); }
        .p-bd-bar { flex:1; height:8px; background:var(--line-soft); border-radius:4px; overflow:hidden; }
        .p-bd-fill { height:100%; background:var(--gold); border-radius:4px; transition:width .5s ease; }
        .p-bd-count { width:30px; text-align:right; color:var(--muted); font-size:12.5px; }

        /* Review cards */
        .p-review {
            border-top:1px solid var(--line-soft); padding:18px 0;
        }
        .p-review:first-child { border-top:0; padding-top:0; }
        .p-review:last-child  { padding-bottom:0; }
        .p-review-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:6px; flex-wrap:wrap; }
        .p-review-author { font-weight:600; color:var(--text); font-size:14px; }
        .p-review-verified {
            display:inline-flex; align-items:center; gap:4px;
            background:var(--success-bg); color:var(--success-text);
            font-size:11px; font-weight:600; padding:2px 8px; border-radius:10px;
        }
        .p-review-date { color:var(--muted); font-size:12.5px; }
        .p-review-title { font-weight:600; margin:6px 0 4px; font-size:14.5px; }
        .p-review-body { color:var(--text); font-size:14px; line-height:1.65; }
        .p-review-stars { display:inline-flex; gap:1px; color:var(--gold); }

        .p-loadmore {
            margin-top:18px; padding:12px 20px;
            background:transparent; color:var(--primary); border:1px solid var(--primary);
            border-radius:8px; font-weight:600; cursor:pointer; font-size:13.5px;
            transition:.2s;
        }
        .p-loadmore:hover { background:var(--primary-soft); }
        .p-loadmore:disabled { opacity:.5; cursor:not-allowed; }

        /* Topic chips */
        .p-topics { display:flex; gap:8px; flex-wrap:wrap; }
        .p-topic-chip {
            background:var(--primary-soft); color:var(--primary-deep);
            padding:8px 14px; border-radius:18px; font-weight:600; font-size:13px;
        }

        /* Availability table */
        .p-avail { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; }
        .p-avail-item {
            background:var(--neutral-soft); border:1px solid var(--line);
            padding:12px 14px; border-radius:10px;
        }
        .p-avail-day { font-weight:700; font-size:13px; color:var(--text); }
        .p-avail-time { color:var(--muted); font-size:13px; margin-top:2px; }

        /* Testimonials carousel */
        .p-testimonials {
            display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;
            margin:24px 0 30px;
        }
        .p-testimonial {
            background:linear-gradient(150deg,#fff 0%, var(--primary-soft) 100%);
            border:1px solid var(--line); border-radius:14px; padding:22px;
            position:relative;
        }
        .p-testimonial-quote { color:var(--primary); font-size:30px; font-weight:700; line-height:1; }
        .p-testimonial-body { color:var(--text); font-size:14px; line-height:1.65; margin:10px 0 14px; font-style:italic; }
        .p-testimonial-author { font-weight:600; font-size:13px; color:var(--text); }
        .p-testimonial-stars { display:inline-flex; color:var(--gold); margin-bottom:6px; }

        .p-empty {
            text-align:center; padding:28px; color:var(--muted); font-style:italic;
            background:var(--neutral-soft); border-radius:10px;
        }

        /* === FOOTER === */
        .p-footer {
            margin-top:60px; padding:30px 0; border-top:1px solid var(--line);
            color:var(--muted); font-size:13px; text-align:center;
        }
        .p-footer a { color:var(--muted); margin:0 10px; }
    </style>
</head>
<body>

{{-- NAV --}}
<nav class="p-nav">
    <div class="p-nav-inner">
        <a href="/" class="p-logo">mentör<span>DE</span></a>
        <a href="{{ route('booking.public.directory') }}" class="p-back">
            <x-icon name="arrow-left" size="14" /> Tüm Uzmanlar
        </a>
        <a href="{{ $p['booking_url'] }}" class="p-nav-cta">
            <x-icon name="calendar" size="14" /> Randevu Al
        </a>
    </div>
</nav>

{{-- HERO --}}
<section class="p-hero">
    <div class="container">
        <div class="p-hero-grid">
            {{-- Sol: kimlik kartı --}}
            <div class="p-hero-main">
                <div class="p-identity">
                    <div class="p-avatar" aria-hidden="true">
                        @if(!empty($p['avatar']))
                            <img src="{{ $p['avatar'] }}" alt="{{ $p['display_name'] }}">
                        @else
                            <span>{{ $p['initials'] }}</span>
                        @endif
                    </div>
                    <div style="flex:1;min-width:0;">
                        <h1 class="p-name">{{ $p['display_name'] }}</h1>
                        @if($p['tagline'])
                            <p class="p-tagline">{{ $p['tagline'] }}</p>
                        @endif
                    </div>
                </div>

                <div class="p-rating-row">
                    @if(($p['stats']['total_reviews'] ?? 0) > 0)
                        <span class="p-stars" aria-label="Ortalama puan {{ number_format((float) $p['stats']['avg_rating'], 1) }}">
                            @php $avg = (float) $p['stats']['avg_rating']; @endphp
                            @for($i = 1; $i <= 5; $i++)
                                <x-icon :name="$i <= round($avg) ? 'star-filled' : 'star'" size="16" />
                            @endfor
                        </span>
                        <span class="p-rating-val">{{ number_format($avg, 1) }}</span>
                        <span class="p-rating-cnt">({{ $p['stats']['total_reviews'] }} yorum) · {{ $p['stats']['total_completed'] }} görüşme</span>
                    @else
                        <span class="p-stars" aria-hidden="true">
                            @for($i=0; $i<5; $i++) <x-icon name="star" size="16" /> @endfor
                        </span>
                        <span class="p-rating-empty">Henüz yorum yok — ilk geri bildirimi sen ver!</span>
                    @endif
                </div>

                <div class="p-meta-pills">
                    @foreach($p['languages'] as $lng)
                        @php
                            $lngLabel = match(strtolower((string)$lng)) {
                                'tr' => 'Türkçe', 'en' => 'İngilizce', 'de' => 'Almanca',
                                default => strtoupper((string)$lng),
                            };
                        @endphp
                        <span class="p-pill"><x-icon name="message-square" size="12" /> {{ $lngLabel }}</span>
                    @endforeach
                    <span class="p-pill muted"><x-icon name="clock" size="12" /> {{ $p['slot_minutes'] }} dk seans</span>
                </div>
            </div>

            {{-- Sağ: CTA card --}}
            <aside>
                <div class="p-cta-card">
                    <h3>Birebir Görüş</h3>
                    <p>{{ $p['slot_minutes'] }} dakikalık görüşme — Almanya yolunda kişisel danışmanlık.</p>

                    <div class="p-cta-stats">
                        <div class="p-cta-stat">
                            <span class="p-cta-stat-val">{{ $p['stats']['total_completed'] }}+</span>
                            <span class="p-cta-stat-lbl">Görüşme</span>
                        </div>
                        <div class="p-cta-stat">
                            <span class="p-cta-stat-val">
                                @if(($p['stats']['avg_rating'] ?? null) !== null)
                                    {{ number_format((float) $p['stats']['avg_rating'], 1) }}
                                @else
                                    —
                                @endif
                            </span>
                            <span class="p-cta-stat-lbl">Puan</span>
                        </div>
                        <div class="p-cta-stat">
                            <span class="p-cta-stat-val">{{ count($p['languages']) ?: 1 }}</span>
                            <span class="p-cta-stat-lbl">Dil</span>
                        </div>
                    </div>

                    <a href="{{ $p['booking_url'] }}" class="p-cta-btn">
                        <x-icon name="calendar" size="16" /> Randevu Al
                    </a>
                </div>
            </aside>
        </div>

        {{-- TABS --}}
        <div class="p-tabs" role="tablist" id="p-tabs">
            <button class="p-tab active" data-tab="about"        role="tab">Hakkında</button>
            <button class="p-tab"        data-tab="reviews"      role="tab">Yorumlar ({{ $p['stats']['total_reviews'] }})</button>
            <button class="p-tab"        data-tab="topics"       role="tab">Uzmanlık Konuları</button>
            <button class="p-tab"        data-tab="availability" role="tab">Müsaitlik</button>
        </div>

        {{-- HAKKINDA --}}
        <div class="p-panel active" data-panel="about">
            <div class="p-section">
                <h2 class="p-section-h">Hakkımda</h2>
                @if($p['bio'])
                    <div class="p-bio">{{ $p['bio'] }}</div>
                @else
                    <p class="p-empty">Biyografi henüz eklenmemiş.</p>
                @endif
            </div>

            @if(count($p['testimonials']))
                <div class="p-section">
                    <h2 class="p-section-h">Öne Çıkan Yorumlar</h2>
                    <div class="p-testimonials">
                        @foreach($p['testimonials'] as $t)
                            <div class="p-testimonial">
                                <div class="p-testimonial-quote" aria-hidden="true">"</div>
                                <div class="p-testimonial-stars" aria-label="{{ $t->rating }} yıldız">
                                    @for($i=1; $i<=5; $i++)
                                        <x-icon :name="$i <= (int) $t->rating ? 'star-filled' : 'star'" size="14" />
                                    @endfor
                                </div>
                                <div class="p-testimonial-body">{{ $t->body }}</div>
                                <div class="p-testimonial-author">— {{ $t->reviewer_name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($p['expertise']))
                <div class="p-section">
                    <h2 class="p-section-h">Uzmanlık Etiketleri</h2>
                    <div class="p-topics">
                        @foreach(array_slice($p['expertise'], 0, 12) as $tag)
                            <span class="p-topic-chip">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- YORUMLAR --}}
        <div class="p-panel" data-panel="reviews">
            <div class="p-section">
                <h2 class="p-section-h">Yorumlar &amp; Puan Dağılımı</h2>

                @if(($p['stats']['total_reviews'] ?? 0) > 0)
                    <div class="p-breakdown">
                        @php $total = max(1, (int) $p['stats']['total_reviews']); @endphp
                        @foreach($p['rating_breakdown'] as $star => $cnt)
                            @php $pct = round(($cnt / $total) * 100); @endphp
                            <div class="p-bd-row">
                                <span class="p-bd-label">{{ $star }} <x-icon name="star-filled" size="11" /></span>
                                <span class="p-bd-bar"><span class="p-bd-fill" style="width:{{ $pct }}%"></span></span>
                                <span class="p-bd-count">{{ $cnt }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div id="p-reviews-list">
                        @foreach($p['reviews'] as $r)
                            <article class="p-review">
                                <div class="p-review-head">
                                    <div>
                                        <span class="p-review-author">{{ $r->reviewer_name }}</span>
                                        @if($r->is_verified)
                                            <span class="p-review-verified"><x-icon name="check" size="10" /> Doğrulanmış</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="p-review-stars" aria-label="{{ $r->rating }} yıldız">
                                            @for($i=1; $i<=5; $i++)
                                                <x-icon :name="$i <= (int) $r->rating ? 'star-filled' : 'star'" size="13" />
                                            @endfor
                                        </span>
                                        <span class="p-review-date">· {{ optional($r->submitted_at ?: $r->created_at)->format('d.m.Y') }}</span>
                                    </div>
                                </div>
                                @if($r->title)
                                    <div class="p-review-title">{{ $r->title }}</div>
                                @endif
                                @if($r->body)
                                    <div class="p-review-body">{{ $r->body }}</div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    @if(count($p['reviews']) >= 10)
                        <button id="p-loadmore" class="p-loadmore" type="button" data-page="2">Daha Fazla Yükle</button>
                    @endif
                @else
                    <p class="p-empty">Henüz yorum yok. İlk randevuyu alıp deneyimini paylaş!</p>
                @endif
            </div>
        </div>

        {{-- KONULAR --}}
        <div class="p-panel" data-panel="topics">
            <div class="p-section">
                <h2 class="p-section-h">Uzmanlık Konuları</h2>
                @if(count($p['topic_breakdown']))
                    <div class="p-topics">
                        @foreach($p['topic_breakdown'] as $t)
                            <span class="p-topic-chip">{{ $t['label'] }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="p-empty">Konular henüz tanımlanmamış.</p>
                @endif
            </div>
        </div>

        {{-- MÜSAİTLİK --}}
        <div class="p-panel" data-panel="availability">
            <div class="p-section">
                <h2 class="p-section-h">Genel Müsaitlik Planı ({{ $p['timezone'] }})</h2>
                @if(count($p['availability']))
                    <div class="p-avail">
                        @foreach($p['availability'] as $a)
                            <div class="p-avail-item">
                                <div class="p-avail-day">{{ $a['day'] }}</div>
                                <div class="p-avail-time">{{ $a['start'] }} – {{ $a['end'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="p-empty">Müsaitlik planı henüz eklenmemiş. Lütfen randevu sayfasından canlı saatlere bakın.</p>
                @endif
                <div style="margin-top:18px; text-align:center;">
                    <a href="{{ $p['booking_url'] }}" class="p-nav-cta">
                        <x-icon name="calendar" size="14" /> Canlı Slotları Gör
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="p-footer">
    <div class="container">
        <div>© {{ date('Y') }} MentörDE — Almanya yolunda birebir danışmanlık</div>
        <div style="margin-top:8px;">
            <a href="/randevu">Tüm Uzmanlar</a> ·
            <a href="/platform">Platform</a> ·
            <a href="/sss">SSS</a> ·
            @include('partials.vendor-credit')
        </div>
    </div>
</footer>

{{-- CSP-safe JS: tabs + AJAX load more --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // TABS
    var tabs    = document.querySelectorAll('.p-tab');
    var panels  = document.querySelectorAll('.p-panel');
    tabs.forEach(function(btn){
        btn.addEventListener('click', function(){
            var t = btn.getAttribute('data-tab');
            tabs.forEach(function(b){ b.classList.toggle('active', b === btn); });
            panels.forEach(function(p){
                p.classList.toggle('active', p.getAttribute('data-panel') === t);
            });
            // Sayfa zıplamasın diye scroll yok; URL hash'i güncelle
            try { history.replaceState(null, '', '#' + t); } catch(e){}
        });
    });
    // Hash'ten ilk tab
    if(location.hash){
        var initial = location.hash.replace('#','');
        var t = document.querySelector('.p-tab[data-tab="' + initial + '"]');
        if(t){ t.click(); }
    }

    // LOAD MORE
    var btn = document.getElementById('p-loadmore');
    if(btn){
        btn.addEventListener('click', async function(){
            var page = parseInt(btn.getAttribute('data-page') || '2', 10);
            btn.disabled = true;
            btn.textContent = 'Yükleniyor…';
            try {
                var url = "{{ route('booking.public.profile.reviews', ['slug' => $p['slug']]) }}" + '?page=' + page + '&per=10';
                var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                var data = await res.json();
                if(!data.ok){ throw new Error('load_failed'); }
                var list = document.getElementById('p-reviews-list');
                data.items.forEach(function(r){
                    var stars = '';
                    for(var i=1; i<=5; i++){
                        stars += '<span style="color:#f5b400">' + (i <= r.rating ? '★' : '☆') + '</span>';
                    }
                    var verified = r.is_verified
                        ? '<span class="p-review-verified">✓ Doğrulanmış</span>'
                        : '';
                    var titleHtml = r.title ? '<div class="p-review-title">' + escapeHtml(r.title) + '</div>' : '';
                    var bodyHtml  = r.body  ? '<div class="p-review-body">' + escapeHtml(r.body) + '</div>' : '';
                    var html =
                        '<article class="p-review">'
                        + '<div class="p-review-head">'
                        +   '<div><span class="p-review-author">' + escapeHtml(r.reviewer) + '</span>' + verified + '</div>'
                        +   '<div><span class="p-review-stars">' + stars + '</span><span class="p-review-date"> · ' + escapeHtml(r.submitted_at || '') + '</span></div>'
                        + '</div>'
                        + titleHtml + bodyHtml
                        + '</article>';
                    list.insertAdjacentHTML('beforeend', html);
                });
                if(data.has_more){
                    btn.setAttribute('data-page', String(page + 1));
                    btn.disabled = false;
                    btn.textContent = 'Daha Fazla Yükle';
                } else {
                    btn.textContent = 'Hepsi gösterildi';
                }
            } catch(e){
                btn.disabled = false;
                btn.textContent = 'Tekrar Dene';
            }
        });
    }

    function escapeHtml(s){
        return String(s).replace(/[&<>"']/g, function(c){
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
})();
</script>

</body>
</html>

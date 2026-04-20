{{-- ══════════════════════════════════════════════════════════════════════════
  Shared Discover Hub partial — Guest + Student portals.
═══════════════════════════════════════════════════════════════════════════ --}}

@push('head')
<style>
/* ══════ Hero (Option B + bg image) ══════ */
.dc-hero {
    color:#fff; border-radius:14px; margin-bottom:20px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.14); position:relative;
    background:#1e3a8a url('https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=1400&q=80') center/cover;
}
.dc-hero::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, rgba(30,58,138,.92) 0%, rgba(79,70,229,.85) 100%);
}
.dc-hero-body { position:relative; display:flex; align-items:center; gap:24px; padding:24px 28px; }
.dc-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:8px; }
.dc-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.82; }
.dc-hero-marker { display:inline-block; width:5px; height:16px; background:rgba(255,255,255,.75); border-radius:3px; }
.dc-hero-title { font-size:28px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.5px; }
.dc-hero-overview { font-size:13.5px; opacity:.92; line-height:1.5; max-width:580px; }
.dc-hero-stats { display:flex; gap:8px; flex-wrap:wrap; margin-top:6px; }
.dc-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.dc-hero-icon { font-size:52px; line-height:1; flex-shrink:0; opacity:.85; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }

.dc-search { display:flex; gap:8px; margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.dc-search input {
    flex:1; padding:11px 14px; border-radius:10px; border:none;
    font-size:14px; outline:none; background:rgba(255,255,255,.96); color:#1a1a1a;
    box-shadow:0 2px 8px rgba(0,0,0,.1);
}
.dc-search input::placeholder { color:#94a3b8; }
.dc-search button {
    padding:11px 22px; background:#fff; color:#1e3a8a;
    border:none; border-radius:10px; font-weight:700; font-size:13.5px; cursor:pointer;
    box-shadow:0 4px 12px rgba(0,0,0,.18); transition:transform .12s, box-shadow .12s;
}
.dc-search button:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(0,0,0,.24); }

@media (max-width:720px){
    .dc-hero{border-radius:12px;}
    .dc-hero-body{gap:14px; padding:18px; align-items:flex-start;}
    .dc-hero-title{font-size:22px;}
    .dc-hero-overview{font-size:12.5px;}
    .dc-hero-icon{font-size:36px; align-self:flex-start;}
    .dc-hero-label{font-size:10px; letter-spacing:.5px;}
    .dc-hero-marker{height:12px; width:3px;}
    .dc-search input{padding:10px 12px; font-size:13px;}
    .dc-search button{padding:10px 16px; font-size:12.5px;}
    .dc-hero-stat{padding:3px 9px; font-size:10.5px;}
}

/* Breadcrumb */
.dc-crumbs { font-size:12px; margin-bottom:12px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; color:var(--u-muted); }
.dc-crumbs a { color:var(--u-muted); text-decoration:none; }
.dc-crumbs a:hover { color:var(--u-brand); }
.dc-crumbs .dc-crumb-current { color:var(--u-text); font-weight:700; }
.dc-crumbs .dc-crumb-sep { opacity:.5; }

/* Filters */
.dc-filters-card { background:var(--u-card); border:1px solid var(--u-line); border-radius:12px; padding:12px 14px; margin-bottom:20px; display:flex; flex-direction:column; gap:10px; }
.dc-filter-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.dc-filter-label { font-size:10.5px; font-weight:700; color:var(--u-muted); letter-spacing:.5px; text-transform:uppercase; white-space:nowrap; min-width:64px; }
.dc-pill {
    padding:6px 12px; border-radius:18px; border:1px solid var(--u-line);
    background:var(--u-card); color:var(--u-text);
    font-size:12px; font-weight:600; cursor:pointer; text-decoration:none;
    transition:all .15s; white-space:nowrap;
    display:inline-flex; align-items:center; gap:4px;
}
.dc-pill:hover:not(.active) {
    border-color:var(--u-brand); color:var(--u-brand);
    background:color-mix(in srgb, var(--u-brand,#2563eb) 6%, var(--u-card));
}
.dc-pill.active {
    background:var(--u-brand,#2563eb); color:#fff;
    border-color:var(--u-brand,#2563eb); font-weight:700;
    box-shadow:0 2px 8px color-mix(in srgb, var(--u-brand,#2563eb) 35%, transparent);
}
.dc-filter-sep { flex-shrink:0; width:100%; height:1px; background:var(--u-line); }
.dc-filter-clear {
    margin-left:auto; padding:6px 12px; border-radius:18px;
    border:1px solid #ef4444; color:#ef4444; background:transparent;
    font-size:12px; font-weight:600; cursor:pointer; text-decoration:none;
    transition:all .15s;
}
.dc-filter-clear:hover { background:#ef4444; color:#fff; }

/* Section title */
.dc-section-title { font-size:15px; font-weight:700; color:var(--u-text); margin:0 0 14px; display:flex; align-items:center; gap:8px; }
.dc-section-title::before { content:''; display:inline-block; width:4px; height:16px; background:var(--u-brand,#2563eb); border-radius:2px; }
.dc-section-title .dc-section-count { font-size:11.5px; font-weight:600; color:var(--u-muted); background:color-mix(in srgb, var(--u-brand,#2563eb) 8%, transparent); padding:2px 9px; border-radius:12px; margin-left:auto; }

/* Featured */
.dc-featured-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px; }
@media(max-width:900px){ .dc-featured-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:560px){ .dc-featured-grid { grid-template-columns:1fr; } }

.dc-feat-card {
    border-radius:14px; overflow:hidden;
    background:var(--u-card); border:1px solid var(--u-line);
    display:flex; flex-direction:column;
    text-decoration:none; color:inherit;
    transition:transform .18s, box-shadow .18s, border-color .18s;
    position:relative;
}
.dc-feat-card:hover {
    transform:translateY(-4px);
    box-shadow:0 14px 34px rgba(0,0,0,.12);
    border-color:color-mix(in srgb, var(--u-brand,#2563eb) 25%, var(--u-line));
}
.dc-feat-card::before {
    content:'⭐ ÖNE ÇIKAN'; position:absolute; top:12px; left:12px;
    background:rgba(255,255,255,.96); color:#f59e0b;
    font-size:9.5px; font-weight:800; letter-spacing:.5px;
    padding:3px 8px; border-radius:12px; z-index:3;
    box-shadow:0 2px 6px rgba(0,0,0,.12);
}
.dc-feat-img { height:180px; position:relative; overflow:hidden; }
.dc-feat-img img { width:100%; height:100%; object-fit:cover; transition:transform .4s; }
.dc-feat-card:hover .dc-feat-img img { transform:scale(1.04); }
.dc-feat-img-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; }
.dc-feat-img::after {
    content:''; position:absolute; inset:0;
    background:linear-gradient(to bottom, rgba(0,0,0,0) 55%, rgba(0,0,0,.3));
    pointer-events:none;
}
.dc-feat-body { padding:14px 16px; flex:1; display:flex; flex-direction:column; gap:7px; }
.dc-feat-badges { display:flex; gap:5px; flex-wrap:wrap; align-items:center; }
.dc-feat-title { font-size:15px; font-weight:700; color:var(--u-text); line-height:1.35; }
.dc-feat-summary { font-size:12.5px; color:var(--u-muted); flex:1; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

.dc-icon-bubble {
    width:68px; height:68px; background:rgba(255,255,255,.22);
    backdrop-filter:blur(8px); border-radius:18px;
    border:1.5px solid rgba(255,255,255,.4);
    display:flex; align-items:center; justify-content:center;
    font-size:2rem; box-shadow:0 4px 20px rgba(0,0,0,.18);
}
.dc-type-ribbon {
    position:absolute; bottom:10px; right:10px; z-index:2;
    background:rgba(0,0,0,.65); backdrop-filter:blur(6px);
    border-radius:6px; padding:3px 9px;
    font-size:10px; font-weight:700; color:#fff;
    letter-spacing:.3px;
    display:inline-flex; align-items:center; gap:4px;
}
.dc-play-btn {
    position:absolute; top:50%; left:50%;
    transform:translate(-50%,-50%);
    width:52px; height:52px; background:rgba(255,255,255,.94);
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; color:#1e3a8a; z-index:2;
    box-shadow:0 4px 16px rgba(0,0,0,.3);
    transition:transform .2s, box-shadow .2s;
}
.dc-feat-card:hover .dc-play-btn,
.dc-card:hover .dc-play-btn { transform:translate(-50%,-50%) scale(1.1); }

/* Content grid */
.dc-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
@media(max-width:900px){ .dc-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:560px){ .dc-grid { grid-template-columns:1fr; } }

.dc-card {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:12px; overflow:hidden;
    display:flex; flex-direction:column;
    text-decoration:none; color:inherit;
    transition:transform .15s, box-shadow .15s, border-color .15s;
}
.dc-card:hover {
    transform:translateY(-3px);
    box-shadow:0 10px 24px rgba(0,0,0,.1);
    border-color:color-mix(in srgb, var(--u-brand,#2563eb) 22%, var(--u-line));
}
.dc-card-img { height:150px; position:relative; overflow:hidden; }
.dc-card-img img { width:100%; height:100%; object-fit:cover; transition:transform .35s; }
.dc-card:hover .dc-card-img img { transform:scale(1.04); }
.dc-card-img-ph { width:100%; height:100%; display:flex; align-items:center; justify-content:center; }
.dc-card-img::after {
    content:''; position:absolute; inset:0;
    background:linear-gradient(to bottom, rgba(0,0,0,0) 60%, rgba(0,0,0,.25));
    pointer-events:none;
}
.dc-card .dc-play-btn { width:42px; height:42px; font-size:14px; }
.dc-card-body { padding:12px 14px; flex:1; display:flex; flex-direction:column; gap:6px; }
.dc-card-badges { display:flex; gap:5px; flex-wrap:wrap; align-items:center; }
.dc-card-title {
    font-size:13.5px; font-weight:700; color:var(--u-text); line-height:1.35;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.dc-card-summary {
    font-size:12px; color:var(--u-muted); flex:1; line-height:1.5; overflow:hidden;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
}
.dc-card-meta {
    font-size:11px; color:var(--u-muted);
    display:flex; gap:12px; align-items:center;
    margin-top:4px; padding-top:8px; border-top:1px solid var(--u-line);
}
.dc-card-meta span { display:inline-flex; align-items:center; gap:3px; }

/* Layout */
.dc-layout { display:grid; grid-template-columns:1fr 260px; gap:22px; align-items:start; }
@media(max-width:960px){ .dc-layout { grid-template-columns:1fr; } }

/* Sidebar */
.dc-sidebar { display:flex; flex-direction:column; gap:14px; position:sticky; top:76px; }
@media(max-width:960px){ .dc-sidebar { position:static; } }

.dc-widget { background:var(--u-card); border:1px solid var(--u-line); border-radius:12px; padding:14px 16px; }
.dc-widget-title {
    font-size:12.5px; font-weight:700; color:var(--u-text);
    margin:0 0 10px; padding-bottom:8px; border-bottom:1px solid var(--u-line);
    display:flex; align-items:center; gap:6px;
}
.dc-pop-item {
    display:flex; gap:10px; align-items:flex-start;
    padding:8px 0; border-bottom:1px solid var(--u-line);
    text-decoration:none; color:inherit; transition:transform .1s;
}
.dc-pop-item:last-child { border-bottom:none; padding-bottom:0; }
.dc-pop-item:hover { transform:translateX(2px); }
.dc-pop-item:hover .dc-pop-title { color:var(--u-brand); }
.dc-pop-rank {
    flex-shrink:0; width:22px; height:22px; border-radius:6px;
    display:flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:800;
    color:var(--u-brand); background:color-mix(in srgb, var(--u-brand,#2563eb) 10%, transparent);
    margin-top:1px;
}
.dc-pop-title {
    font-size:12px; font-weight:600; color:var(--u-text); line-height:1.3;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    transition:color .12s;
}
.dc-pop-meta {
    font-size:10.5px; color:var(--u-muted); margin-top:2px;
    display:inline-flex; align-items:center; gap:4px;
}

.dc-cat-list { display:flex; flex-direction:column; gap:2px; margin:0 -6px; }
.dc-cat-item {
    display:flex; align-items:center; justify-content:space-between;
    padding:7px 10px; border-radius:8px;
    font-size:12px; color:var(--u-text); font-weight:600;
    text-decoration:none; transition:background .12s;
}
.dc-cat-item:hover { background:color-mix(in srgb, var(--u-brand,#2563eb) 6%, transparent); color:var(--u-brand); }
.dc-cat-item-count {
    font-size:10.5px; font-weight:700; color:var(--u-muted);
    background:color-mix(in srgb, var(--u-text) 5%, transparent);
    padding:1px 7px; border-radius:10px;
}

/* Badge colors */
.badge-blog          { background:#dce8f8; color:#1a56b0; }
.badge-video_feature { background:#e8e0f8; color:#4c2fa0; }
.badge-podcast       { background:#d8eef5; color:#0e5f7a; }
.badge-presentation  { background:#e8f0fe; color:#2a4fa8; }
.badge-experience    { background:#e3e8f4; color:#2d3f6b; }
.badge-career_guide  { background:#d6eed8; color:#1b5e30; }
.badge-tip           { background:#f0e8d8; color:#7a4a10; }

.cat-student-life    { background:#dce8f8; color:#1a4f9e; }
.cat-culture-fun     { background:#e5e0f5; color:#4a2e8a; }
.cat-careers         { background:#d6eed8; color:#1b5e30; }
.cat-tips-tricks     { background:#f0e8d4; color:#7a4710; }
.cat-city-content    { background:#d4eaf5; color:#0d5070; }
.cat-uni-content     { background:#dde3f4; color:#2a3a80; }
.cat-success-stories { background:#dde3f4; color:#1a3a8a; }

/* Empty state */
.dc-empty {
    text-align:center; padding:56px 24px;
    background:var(--u-card); border:1px dashed var(--u-line); border-radius:14px;
}
.dc-empty-icon { font-size:48px; margin-bottom:14px; opacity:.6; }
.dc-empty-title { font-size:15px; font-weight:700; color:var(--u-text); margin-bottom:6px; }
.dc-empty-sub { font-size:12.5px; color:var(--u-muted); margin-bottom:18px; line-height:1.5; }
.dc-empty-actions { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }

.dc-load-more { text-align:center; margin-top:24px; }
</style>
@endpush

@php
// Portal detection
$dcIsStudent = request()->is('student/*');
$dcDiscoverRoute = function ($params = []) use ($dcIsStudent) {
    return $dcIsStudent ? route('student.discover', $params) : route('guest.discover', $params);
};
$dcDashboardRoute = $dcIsStudent ? route('student.dashboard') : route('guest.dashboard');
$dcContentRoute = function ($slug) use ($dcIsStudent) {
    return $dcIsStudent ? route('student.content-detail', $slug) : route('guest.content-detail', $slug);
};

$typeLabels = [
    'blog' => 'Blog', 'video_feature' => 'Video', 'podcast' => 'Podcast',
    'presentation' => 'Sunum', 'experience' => 'Deneyim', 'career_guide' => 'Rehber', 'tip' => 'İpucu',
];
$catLabels = [
    'student-life'    => '🎓 Öğrenci Hayatı',
    'culture-fun'     => '🎭 Kültür & Eğlence',
    'careers'         => '💼 Kariyer',
    'tips-tricks'     => '💡 Pratik İpuçları',
    'city-content'    => '🏙 Şehirler',
    'uni-content'     => '🏛 Üniversiteler',
    'success-stories' => '⭐ Başarı Hikayeleri',
];
$typeIcons = [
    'blog' => '📝', 'video_feature' => '▶️', 'podcast' => '🎙',
    'presentation' => '📊', 'experience' => '💬', 'career_guide' => '🗺', 'tip' => '💡',
];
$gradients = [
    'student-life'    => 'linear-gradient(135deg,#1e3a8a,#3b82f6)',
    'culture-fun'     => 'linear-gradient(135deg,#4c1d95,#8b5cf6)',
    'careers'         => 'linear-gradient(135deg,#064e3b,#10b981)',
    'tips-tricks'     => 'linear-gradient(135deg,#78350f,#f59e0b)',
    'city-content'    => 'linear-gradient(135deg,#0c4a6e,#0ea5e9)',
    'uni-content'     => 'linear-gradient(135deg,#312e81,#6366f1)',
    'success-stories' => 'linear-gradient(135deg,#1e1b4b,#a78bfa)',
];
@endphp

{{-- Breadcrumb --}}
<div class="dc-crumbs">
    <a href="{{ $dcDashboardRoute }}">Ana Sayfa</a>
    <span class="dc-crumb-sep">›</span>
    <a href="{{ $dcDiscoverRoute() }}" class="{{ (!$cat && !$type && !$search) ? 'dc-crumb-current' : '' }}">🧭 Keşfet</a>
    @if($cat)
        <span class="dc-crumb-sep">›</span>
        <a href="{{ $dcDiscoverRoute(['cat'=>$cat]) }}" class="{{ !$type ? 'dc-crumb-current' : '' }}">{{ $catLabels[$cat] ?? $cat }}</a>
    @endif
    @if($type)
        <span class="dc-crumb-sep">›</span>
        <span class="dc-crumb-current">{{ $typeLabels[$type] ?? $type }}</span>
    @endif
    @if($search && !$cat && !$type)
        <span class="dc-crumb-sep">›</span>
        <span class="dc-crumb-current">"{{ $search }}"</span>
    @endif
</div>

{{-- Hero --}}
@php
$heroTitle    = $cat ? ($catLabels[$cat] ?? $cat) : '🧭 Almanya\'ya Hazır Ol';
$heroTitle    = $type ? ($heroTitle . ' — ' . ($typeLabels[$type] ?? $type)) : $heroTitle;
$heroTitle    = $search ? '"'.$search.'" için sonuçlar' : $heroTitle;
$heroSubtitle = $cat
    ? 'Bu kategorideki tüm içerikler. İçerik tipine göre filtreleyebilirsin.'
    : 'Öğrenci hayatı, kariyer, kültür, şehir rehberleri ve daha fazlası.';
$totalCount = $items->total();
@endphp
<div class="dc-hero">
    <div class="dc-hero-body" style="flex-direction:column; align-items:stretch;">
        <div style="display:flex; align-items:center; gap:24px; width:100%;">
            <div class="dc-hero-main">
                <div class="dc-hero-label"><span class="dc-hero-marker"></span>İçerik Merkezi</div>
                <h1 class="dc-hero-title">{{ $heroTitle }}</h1>
                <div class="dc-hero-overview">{{ $heroSubtitle }}</div>
                <div class="dc-hero-stats">
                    <span class="dc-hero-stat">📚 {{ $totalCount }} içerik</span>
                    <span class="dc-hero-stat">📂 {{ count($catLabels) }} kategori</span>
                    <span class="dc-hero-stat">🎬 {{ count($typeLabels) }} format</span>
                </div>
            </div>
            <div class="dc-hero-icon">🧭</div>
        </div>

        <form class="dc-search" method="GET" action="" id="dc-search-form">
            @if($cat)<input type="hidden" name="cat" value="{{ $cat }}">@endif
            @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
            <input type="text" name="q" id="dc-search-input" value="{{ $search }}" placeholder="🔍 İçerik ara (örn: Berlin, staj, sigorta)..." autocomplete="off">
            <button type="submit">Ara</button>
        </form>
    </div>
</div>

{{-- Filtreler --}}
<div class="dc-filters-card">
    <div class="dc-filter-row">
        <span class="dc-filter-label">Kategori</span>
        <a href="{{ $dcDiscoverRoute() . ($type ? '?type='.$type : '') }}"
           class="dc-pill {{ !$cat ? 'active' : '' }}">Tümü</a>
        @foreach($catLabels as $key => $label)
            <a href="{{ $dcDiscoverRoute() . '?cat=' . $key . ($type ? '&type='.$type : '') . ($search ? '&q='.urlencode($search) : '') }}"
               class="dc-pill {{ $cat === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="dc-filter-sep"></div>
    <div class="dc-filter-row">
        <span class="dc-filter-label">Format</span>
        <a href="{{ $dcDiscoverRoute() . ($cat ? '?cat='.$cat : '') . ($search ? ($cat?'&':'?').'q='.urlencode($search) : '') }}"
           class="dc-pill {{ !$type ? 'active' : '' }}">Tümü</a>
        @foreach(['blog'=>'📝 Blog','video_feature'=>'▶ Video','podcast'=>'🎙 Podcast','presentation'=>'📊 Sunum','experience'=>'💬 Deneyim','career_guide'=>'🗺 Rehber','tip'=>'💡 İpucu'] as $key => $label)
            <a href="{{ $dcDiscoverRoute() . '?type=' . $key . ($cat ? '&cat='.$cat : '') . ($search ? '&q='.urlencode($search) : '') }}"
               class="dc-pill {{ $type === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        @if($cat || $type || $search)
            <a href="{{ $dcDiscoverRoute() }}" class="dc-filter-clear">✕ Temizle</a>
        @endif
    </div>
</div>

@if($featured->isNotEmpty() && !$cat && !$type && !$search)
<div class="dc-section-title">⭐ Öne Çıkanlar <span class="dc-section-count">{{ $featured->count() }}</span></div>
<div class="dc-featured-grid">
    @foreach($featured as $f)
    <a href="{{ $dcContentRoute($f->slug) }}" class="dc-feat-card">
        <div class="dc-feat-img" style="background:{{ $gradients[$f->category] ?? 'linear-gradient(135deg,#1e3a8a,#3b82f6)' }}">
            @if($f->cover_image_url)
                <img src="{{ $f->cover_image_url }}" alt="{{ $f->title_tr }}" loading="lazy">
            @elseif($f->type === 'video_feature')
                <div class="dc-play-btn">▶</div>
            @else
                <div class="dc-feat-img-placeholder">
                    <div class="dc-icon-bubble">{{ $typeIcons[$f->type] ?? '📄' }}</div>
                </div>
            @endif
            <div class="dc-type-ribbon">{{ $typeIcons[$f->type] ?? '📄' }} {{ $typeLabels[$f->type] ?? $f->type }}</div>
        </div>
        <div class="dc-feat-body">
            <div class="dc-feat-badges">
                @if($f->category)<span class="badge cat-{{ $f->category }}" style="font-size:10.5px;">{{ $catLabels[$f->category] ?? $f->category }}</span>@endif
            </div>
            <div class="dc-feat-title">{{ $f->title_tr }}</div>
            <div class="dc-feat-summary">{{ Str::limit($f->summary_tr, 110) }}</div>
        </div>
    </a>
    @endforeach
</div>
@endif

<div class="dc-layout">
    <div>
        <div class="dc-section-title">
            @if($cat){{ $catLabels[$cat] ?? $cat }}
            @elseif($type){{ $typeLabels[$type] ?? $type }} İçerikleri
            @elseif($search)"{{ $search }}" için sonuçlar
            @else📚 Tüm İçerikler
            @endif
            <span class="dc-section-count">{{ $items->total() }}</span>
        </div>

        @if($items->isEmpty())
        <div class="dc-empty">
            <div class="dc-empty-icon">🔍</div>
            <div class="dc-empty-title">Sonuç bulunamadı</div>
            <div class="dc-empty-sub">
                @if($cat && $type)
                    <strong>{{ $catLabels[$cat] ?? $cat }}</strong> kategorisinde <strong>{{ $typeLabels[$type] ?? $type }}</strong> tipinde içerik henüz eklenmedi.
                @elseif($cat)
                    <strong>{{ $catLabels[$cat] ?? $cat }}</strong> kategorisinde içerik bulunamadı.
                @else
                    Aramanla eşleşen içerik yok.
                @endif
            </div>
            <div class="dc-empty-actions">
                @if($type)<a href="{{ $dcDiscoverRoute($cat ? ['cat'=>$cat] : []) }}" class="btn alt" style="font-size:.85rem;">{{ $cat ? ($catLabels[$cat]??$cat).' — Tümü' : 'Filtreyi Kaldır' }}</a>@endif
                @if($cat)<a href="{{ $dcDiscoverRoute($type ? ['type'=>$type] : []) }}" class="btn alt" style="font-size:.85rem;">{{ $type ? ($typeLabels[$type]??$type).' — Tüm Kategoriler' : 'Kategoriyi Kaldır' }}</a>@endif
                <a href="{{ $dcDiscoverRoute() }}" class="btn" style="font-size:.85rem;">Tüm İçeriklere Dön</a>
            </div>
        </div>
        @else
        <div class="dc-grid" id="dc-grid">
            @foreach($items as $item)
            <a href="{{ $dcContentRoute($item->slug) }}" class="dc-card" data-search="{{ strtolower($item->title_tr.' '.$item->summary_tr) }}">
                <div class="dc-card-img" style="background:{{ $gradients[$item->category] ?? 'linear-gradient(135deg,#1e3a8a,#3b82f6)' }}">
                    @if($item->cover_image_url)
                        <img src="{{ $item->cover_image_url }}" alt="{{ $item->title_tr }}" loading="lazy">
                    @elseif($item->type === 'video_feature')
                        <div class="dc-play-btn">▶</div>
                    @else
                        <div class="dc-card-img-ph">
                            <div class="dc-icon-bubble">{{ $typeIcons[$item->type] ?? '📄' }}</div>
                        </div>
                    @endif
                    <div class="dc-type-ribbon">{{ $typeIcons[$item->type] ?? '📄' }} {{ $typeLabels[$item->type] ?? $item->type }}</div>
                </div>
                <div class="dc-card-body">
                    <div class="dc-card-badges">
                        @if($item->category)<span class="badge cat-{{ $item->category }}" style="font-size:10px;">{{ $catLabels[$item->category] ?? $item->category }}</span>@endif
                    </div>
                    <div class="dc-card-title">{{ $item->title_tr }}</div>
                    <div class="dc-card-summary">{{ $item->summary_tr }}</div>
                    <div class="dc-card-meta">
                        @if($item->metric_total_views)<span>👁 {{ number_format($item->metric_total_views) }}</span>@endif
                        @php $rtMins = $item->metric_avg_read_time_seconds ? intdiv($item->metric_avg_read_time_seconds, 60) : 0; @endphp
                        @if($rtMins)<span>⏱ {{ $rtMins }} dk</span>@endif
                        @if($item->published_at)<span>{{ $item->published_at->diffForHumans() }}</span>@endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        @if($items->hasPages())
        <div class="dc-load-more">
            {{ $items->appends(['cat'=>$cat,'type'=>$type,'q'=>$search])->links('partials.pagination') }}
        </div>
        @endif
        @endif
    </div>

    <div class="dc-sidebar">
        @if($popular->isNotEmpty())
        <div class="dc-widget">
            <div class="dc-widget-title">🔥 En Çok Görüntülenenler</div>
            @foreach($popular->take(5) as $i => $p)
            <a href="{{ $dcContentRoute($p->slug) }}" class="dc-pop-item">
                <div class="dc-pop-rank">{{ $i + 1 }}</div>
                <div style="flex:1;min-width:0;">
                    <div class="dc-pop-title">{{ $p->title_tr }}</div>
                    <div class="dc-pop-meta">{{ $typeIcons[$p->type] ?? '📄' }} {{ $typeLabels[$p->type] ?? $p->type }}</div>
                </div>
            </a>
            @endforeach
        </div>
        @endif

        <div class="dc-widget">
            <div class="dc-widget-title">📂 Kategoriler</div>
            <div class="dc-cat-list">
                <a href="{{ $dcDiscoverRoute() }}" class="dc-cat-item">
                    <span>🧭 Tümü</span>
                    <span class="dc-cat-item-count">{{ $items->total() }}</span>
                </a>
                @foreach($catLabels as $key => $label)
                <a href="{{ $dcDiscoverRoute(['cat' => $key]) }}" class="dc-cat-item">
                    <span>{{ $label }}</span>
                </a>
                @endforeach
            </div>
        </div>

        <div class="dc-widget">
            <div class="dc-widget-title">💡 İpucu</div>
            <div style="font-size:12px; color:var(--u-muted); line-height:1.55;">
                Bir içeriği kaydetmek için açtığında <strong>🔖 Kaydet</strong> butonunu kullan. Favorilerim sayfasından erişebilirsin.
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function(){
    var inp = document.getElementById('dc-search-input');
    if(!inp) return;
    var form = document.getElementById('dc-search-form');
    var timer;
    inp.addEventListener('input', function(){
        clearTimeout(timer);
        timer = setTimeout(function(){ form && form.submit(); }, 500);
    });
})();
</script>
@endpush

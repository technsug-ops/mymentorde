@extends('student.layouts.app')

@section('title', 'Keşfet')
@section('page_title', 'Keşfet')

@push('head')
<style>
/* ── dc-* Discover Hub (Student) ── */
.dc-hero{background:var(--hero-gradient,linear-gradient(135deg,var(--theme-hero-from-student,#4c1d95),var(--theme-hero-to-student,#7c3aed)));border-radius:14px;padding:36px 32px;color:#fff;margin-bottom:24px;}
.dc-hero h1{font-size:1.7rem;font-weight:700;margin:0 0 8px;}
.dc-hero p{font-size:.97rem;opacity:.9;margin:0 0 18px;}
.dc-search{display:flex;gap:8px;}
.dc-search input{flex:1;padding:10px 14px;border-radius:8px;border:none;font-size:.95rem;outline:none;}
.dc-search button{padding:10px 20px;background:#fff;color:var(--c-accent,#7c3aed);border:none;border-radius:8px;font-weight:600;cursor:pointer;}

.dc-filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;}
.dc-pill{padding:6px 14px;border-radius:20px;border:1.5px solid var(--u-line,#e5e7eb);background:var(--u-card,#fff);color:var(--u-text,#1a1a1a);font-size:.85rem;cursor:pointer;text-decoration:none;transition:all .15s;}
.dc-pill.active{background:var(--c-accent,#7c3aed);color:#fff;border-color:var(--c-accent,#7c3aed);font-weight:700;}
.dc-pill:hover:not(.active){background:var(--accent-soft,rgba(124,58,237,.08));border-color:var(--c-accent,#7c3aed);color:var(--c-accent,#7c3aed);}
.dc-sep{width:1px;background:var(--u-line,#e5e7eb);margin:0 4px;}

.dc-section-title{font-size:1.05rem;font-weight:700;color:var(--u-text,#1a1a1a);margin:0 0 14px;display:flex;align-items:center;gap:8px;}
.dc-section-title span{font-size:.8rem;font-weight:400;color:var(--u-muted,#888);}

/* Featured big cards */
.dc-featured-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;}
@media(max-width:768px){.dc-featured-grid{grid-template-columns:1fr;}}
.dc-feat-card{border-radius:14px;overflow:hidden;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);display:flex;flex-direction:column;text-decoration:none;color:inherit;transition:transform .18s,box-shadow .18s;}
.dc-feat-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.14);}
.dc-feat-img{height:170px;position:relative;overflow:hidden;}
.dc-feat-img::after{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.05) 1px,transparent 1px);background-size:22px 22px;}
.dc-feat-img img{width:100%;height:100%;object-fit:cover;}
.dc-feat-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;}
.dc-feat-body{padding:16px 18px;flex:1;display:flex;flex-direction:column;gap:7px;}
.dc-feat-badges{display:flex;gap:6px;flex-wrap:wrap;}
.dc-feat-title{font-size:1rem;font-weight:700;color:var(--u-text,#1a1a1a);line-height:1.4;}
.dc-feat-summary{font-size:.83rem;color:var(--u-muted,#555);flex:1;line-height:1.5;}

/* ── Icon bubble (placeholder) ── */
.dc-icon-bubble{width:64px;height:64px;background:rgba(255,255,255,.22);backdrop-filter:blur(8px);border-radius:18px;border:1.5px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;font-size:2rem;box-shadow:0 4px 20px rgba(0,0,0,.18);}
.dc-feat-img .dc-icon-bubble{width:76px;height:76px;font-size:2.5rem;border-radius:20px;}
/* Type ribbon */
.dc-type-ribbon{position:absolute;bottom:10px;right:10px;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);border-radius:8px;padding:3px 10px;font-size:.72rem;font-weight:700;color:#fff;z-index:2;letter-spacing:.02em;}
/* Play button overlay */
.dc-play-btn{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:52px;height:52px;background:rgba(255,255,255,.92);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;z-index:2;box-shadow:0 4px 16px rgba(0,0,0,.25);}

/* Content card grid */
.dc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
@media(max-width:900px){.dc-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:600px){.dc-grid{grid-template-columns:1fr;}}

.dc-card{background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:14px;overflow:hidden;display:flex;flex-direction:column;text-decoration:none;color:inherit;transition:transform .18s,box-shadow .18s;}
.dc-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.12);}
.dc-card-img{height:140px;position:relative;overflow:hidden;}
.dc-card-img::after{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.05) 1px,transparent 1px);background-size:18px 18px;}
.dc-card-img img{width:100%;height:100%;object-fit:cover;}
.dc-card-img-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;}
.dc-card-body{padding:13px 15px;flex:1;display:flex;flex-direction:column;gap:6px;}
.dc-card-badges{display:flex;gap:5px;flex-wrap:wrap;align-items:center;}
.dc-card-title{font-size:.92rem;font-weight:700;color:var(--u-text,#1a1a1a);line-height:1.4;}
.dc-card-summary{font-size:.81rem;color:var(--u-muted,#555);flex:1;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.5;}
.dc-card-meta{font-size:.76rem;color:var(--u-muted,#666);display:flex;gap:10px;align-items:center;margin-top:5px;border-top:1px solid var(--u-line,#f3f4f6);padding-top:8px;}

.dc-layout{display:grid;grid-template-columns:1fr 240px;gap:24px;align-items:start;}
@media(max-width:900px){.dc-layout{grid-template-columns:1fr;}}
.dc-sidebar{display:flex;flex-direction:column;gap:16px;}
.dc-widget{background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:10px;padding:14px 16px;}
.dc-widget-title{font-size:.85rem;font-weight:700;color:var(--u-text,#1a1a1a);margin:0 0 10px;}
.dc-pop-item{display:flex;gap:10px;align-items:flex-start;padding:7px 0;border-bottom:1px solid var(--u-line,#e5e7eb);text-decoration:none;color:inherit;}
.dc-pop-item:last-child{border-bottom:none;padding-bottom:0;}
.dc-pop-item:hover .dc-pop-title{color:var(--u-brand,#1f6fd9);}
.dc-pop-icon{font-size:1.2rem;flex-shrink:0;margin-top:1px;}
.dc-pop-title{font-size:.82rem;font-weight:600;color:var(--u-text,#1a1a1a);line-height:1.3;}
.dc-pop-meta{font-size:.73rem;color:var(--u-muted,#999);}

.badge-blog{background:#dce8f8;color:#1a56b0;}
.badge-video_feature{background:#e8e0f8;color:#4c2fa0;}
.badge-podcast{background:#d8eef5;color:#0e5f7a;}
.badge-presentation{background:#e8f0fe;color:#2a4fa8;}
.badge-experience{background:#e3e8f4;color:#2d3f6b;}
.badge-career_guide{background:#d6eed8;color:#1b5e30;}
.badge-tip{background:#f0e8d8;color:#7a4a10;}

.cat-student-life{background:#dce8f8;color:#1a4f9e;}
.cat-culture-fun{background:#e5e0f5;color:#4a2e8a;}
.cat-careers{background:#d6eed8;color:#1b5e30;}
.cat-tips-tricks{background:#f0e8d4;color:#7a4710;}
.cat-city-content{background:#d4eaf5;color:#0d5070;}
.cat-uni-content{background:#dde3f4;color:#2a3a80;}
.cat-success-stories{background:#dde3f4;color:#1a3a8a;}

.dc-load-more{text-align:center;margin-top:24px;}
.dc-empty{text-align:center;padding:48px 24px;color:var(--u-muted,#888);}
.dc-empty .dc-empty-icon{font-size:3rem;margin-bottom:12px;}
</style>
@endpush

@section('content')
@php
$typeLabels = [
    'blog'         => 'Blog',
    'video_feature'=> 'Video',
    'podcast'      => 'Podcast',
    'presentation' => 'Sunum',
    'experience'   => 'Deneyim',
    'career_guide' => 'Rehber',
    'tip'          => 'İpucu',
];
$catLabels = [
    'student-life'   => '🎓 Öğrenci Hayatı',
    'culture-fun'    => '🎭 Kültür & Eğlence',
    'careers'        => '💼 Kariyer',
    'tips-tricks'    => '💡 Pratik İpuçları',
    'city-content'   => '🏙 Şehirler',
    'uni-content'    => '🏛 Üniversiteler',
    'success-stories'=> '⭐ Başarı Hikayeleri',
];
$typeIcons = [
    'blog'         => '📝',
    'video_feature'=> '▶️',
    'podcast'      => '🎙',
    'presentation' => '📊',
    'experience'   => '💬',
    'career_guide' => '🗺',
    'tip'          => '💡',
];
$gradients = [
    'student-life'   => 'linear-gradient(135deg,#1e3a8a,#3b82f6)',
    'culture-fun'    => 'linear-gradient(135deg,#4c1d95,#8b5cf6)',
    'careers'        => 'linear-gradient(135deg,#064e3b,#10b981)',
    'tips-tricks'    => 'linear-gradient(135deg,#78350f,#f59e0b)',
    'city-content'   => 'linear-gradient(135deg,#0c4a6e,#0ea5e9)',
    'uni-content'    => 'linear-gradient(135deg,#312e81,#6366f1)',
    'success-stories'=> 'linear-gradient(135deg,#1e1b4b,#a78bfa)',
];
$catGradientMap = [
    'student-life'   => '#1e3a8a 0%,#3b82f6 100%',
    'culture-fun'    => '#4c1d95 0%,#8b5cf6 100%',
    'careers'        => '#064e3b 0%,#059669 100%',
    'tips-tricks'    => '#78350f 0%,#d97706 100%',
    'city-content'   => '#0c4a6e 0%,#0284c7 100%',
    'uni-content'    => '#312e81 0%,#4f46e5 100%',
    'success-stories'=> '#1e1b4b 0%,#7c3aed 100%',
];
@endphp

{{-- Breadcrumb hiyerarşi --}}
<div style="font-size:.82rem;color:var(--u-muted,#888);margin-bottom:12px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
    <a href="/student/dashboard" style="color:#059669;text-decoration:none;">Ana Sayfa</a>
    <span>›</span>
    <a href="{{ route('student.discover') }}" style="color:{{ ($cat||$type||$search)?'#059669':'var(--u-text,#333)' }};text-decoration:none;font-weight:{{ ($cat||$type||$search)?'400':'600' }};">🧭 Keşfet</a>
    @if($cat)
        <span>›</span>
        <span style="color:{{ $type?'#059669':'var(--u-text,#333)' }};font-weight:{{ $type?'400':'600' }};">
            <a href="{{ route('student.discover', ['cat'=>$cat]) }}" style="color:inherit;text-decoration:none;">{{ $catLabels[$cat] ?? $cat }}</a>
        </span>
    @endif
    @if($type)
        <span>›</span>
        <span style="color:var(--u-text,#333);font-weight:600;">{{ $typeLabels[$type] ?? $type }}</span>
    @endif
    @if($search && !$cat && !$type)
        <span>›</span>
        <span style="color:var(--u-text,#333);font-weight:600;">"{{ $search }}"</span>
    @endif
</div>

{{-- Hero — dinamik başlık --}}
@php
$heroTitle    = $cat ? ($catLabels[$cat] ?? $cat) : '🧭 Almanya\'ya Hazır Ol';
$heroTitle    = $type ? ($heroTitle . ' — ' . ($typeLabels[$type] ?? $type)) : $heroTitle;
$heroTitle    = $search ? '"'.$search.'" için sonuçlar' : $heroTitle;
$heroSubtitle = $cat
    ? 'Bu kategorideki tüm içerikler. İçerik tipine göre filtreleyebilirsiniz.'
    : 'Öğrenci hayatı, kariyer, kültür, şehir rehberleri ve daha fazlası.';
@endphp
<div class="dc-hero" @if($cat) style="background:linear-gradient(135deg,{{ $catGradientMap[$cat] ?? '#1e3a8a 0%,#3b82f6 100%' }})" @endif>
    <h1>{{ $heroTitle }}</h1>
    <p>{{ $heroSubtitle }}</p>
    <form class="dc-search" method="GET" action="" id="dc-search-form">
        @if($cat)<input type="hidden" name="cat" value="{{ $cat }}">@endif
        @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
        <input type="text" name="q" id="dc-search-input" value="{{ $search }}" placeholder="İçerik ara..." autocomplete="off">
        <button type="submit">Ara</button>
    </form>
</div>

{{-- Filtreler --}}
<div style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:12px;padding:12px 14px;margin-bottom:20px;display:flex;flex-direction:column;gap:10px;">
    {{-- Satır 1: Kategori --}}
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-size:.75rem;font-weight:700;color:var(--u-muted,#888);white-space:nowrap;min-width:60px;">Kategori</span>
        <a href="{{ route('student.discover') . ($type ? '?type='.$type : '') }}"
           class="dc-pill {{ !$cat ? 'active' : '' }}">Tümü</a>
        @foreach($catLabels as $key => $label)
            <a href="{{ route('student.discover') . '?cat=' . $key . ($type ? '&type='.$type : '') . ($search ? '&q='.urlencode($search) : '') }}"
               class="dc-pill {{ $cat === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
    {{-- Ayırıcı --}}
    <div style="height:1px;background:var(--u-line,#e2e8f0);"></div>
    {{-- Satır 2: İçerik Tipi --}}
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-size:.75rem;font-weight:700;color:var(--u-muted,#888);white-space:nowrap;min-width:60px;">Tip</span>
        <a href="{{ route('student.discover') . ($cat ? '?cat='.$cat : '') . ($search ? ($cat?'&':'?').'q='.urlencode($search) : '') }}"
           class="dc-pill {{ !$type ? 'active' : '' }}">Tümü</a>
        @foreach(['blog'=>'📝 Blog','video_feature'=>'▶ Video','podcast'=>'🎙 Podcast','presentation'=>'📊 Sunum','experience'=>'💬 Deneyim','career_guide'=>'🗺 Rehber','tip'=>'💡 İpucu'] as $key => $label)
            <a href="{{ route('student.discover') . '?type=' . $key . ($cat ? '&cat='.$cat : '') . ($search ? '&q='.urlencode($search) : '') }}"
               class="dc-pill {{ $type === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        @if($cat || $type || $search)
            <a href="{{ route('student.discover') }}" class="dc-pill" style="margin-left:auto;border-color:#ef4444;color:#ef4444;">✕ Temizle</a>
        @endif
    </div>
</div>

@if($featured->isNotEmpty() && !$cat && !$type && !$search)
<div class="dc-section-title">⭐ Öne Çıkanlar</div>
<div class="dc-featured-grid">
    @foreach($featured as $f)
    <a href="{{ route('student.content-detail', $f->slug) }}" class="dc-feat-card">
        <div class="dc-feat-img" style="background:{{ $gradients[$f->category] ?? 'linear-gradient(135deg,#1e3a8a,#3b82f6)' }}">
            @if($f->cover_image_url)
                <img src="{{ $f->cover_image_url }}" alt="{{ $f->title_tr }}" loading="lazy">
            @elseif($f->type === 'video_feature')
                <div class="dc-feat-img-placeholder">
                    <div style="width:56px;height:56px;background:rgba(255,255,255,.9);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">▶</div>
                </div>
            @else
                <div class="dc-feat-img-placeholder">
                    <div class="dc-icon-bubble">{{ $typeIcons[$f->type] ?? '📄' }}</div>
                </div>
            @endif
            <div class="dc-type-ribbon">{{ $typeLabels[$f->type] ?? $f->type }}</div>
        </div>
        <div class="dc-feat-body">
            <div class="dc-feat-badges">
                @if($f->category)<span class="badge cat-{{ $f->category }}">{{ $catLabels[$f->category] ?? $f->category }}</span>@endif
            </div>
            <div class="dc-feat-title">{{ $f->title_tr }}</div>
            <div class="dc-feat-summary">{{ Str::limit($f->summary_tr, 90) }}</div>
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
            <span>{{ $items->total() }} içerik</span>
        </div>

        @if($items->isEmpty())
        <div class="dc-empty">
            <div class="dc-empty-icon">🔍</div>
            <div style="font-size:1rem;font-weight:600;margin-bottom:8px;">Sonuç bulunamadı</div>
            <div style="font-size:.88rem;color:var(--u-muted,#999);margin-bottom:16px;">
                @if($cat && $type)
                    <strong>{{ $catLabels[$cat] ?? $cat }}</strong> kategorisinde <strong>{{ $typeLabels[$type] ?? $type }}</strong> tipinde içerik henüz eklenmedi.
                @elseif($cat)
                    <strong>{{ $catLabels[$cat] ?? $cat }}</strong> kategorisinde içerik bulunamadı.
                @else
                    Aramanızla eşleşen içerik yok.
                @endif
            </div>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                @if($type)
                <a href="{{ route('student.discover', $cat ? ['cat'=>$cat] : []) }}" class="btn alt" style="font-size:.85rem;">
                    {{ $cat ? ($catLabels[$cat]??$cat).' — Tümü' : 'Filtreyi Kaldır' }}
                </a>
                @endif
                @if($cat)
                <a href="{{ route('student.discover', $type ? ['type'=>$type] : []) }}" class="btn alt" style="font-size:.85rem;">
                    {{ $type ? ($typeLabels[$type]??$type).' — Tüm Kategoriler' : 'Kategoriyi Kaldır' }}
                </a>
                @endif
                <a href="{{ route('student.discover') }}" class="btn" style="font-size:.85rem;">Tüm İçeriklere Dön</a>
            </div>
        </div>
        @else
        <div class="dc-grid" id="dc-grid">
            @foreach($items as $item)
            <a href="{{ route('student.content-detail', $item->slug) }}" class="dc-card" data-search="{{ strtolower($item->title_tr.' '.$item->summary_tr) }}">
                <div class="dc-card-img" style="background:{{ $gradients[$item->category] ?? 'linear-gradient(135deg,#1e3a8a,#3b82f6)' }}">
                    @if($item->cover_image_url)
                        <img src="{{ $item->cover_image_url }}" alt="{{ $item->title_tr }}" loading="lazy">
                    @elseif($item->type === 'video_feature')
                        <div class="dc-card-img-ph">
                            <div style="width:44px;height:44px;background:rgba(255,255,255,.9);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;">▶</div>
                        </div>
                    @else
                        <div class="dc-card-img-ph">
                            <div class="dc-icon-bubble">{{ $typeIcons[$item->type] ?? '📄' }}</div>
                        </div>
                    @endif
                    <div class="dc-type-ribbon">{{ $typeLabels[$item->type] ?? $item->type }}</div>
                </div>
                <div class="dc-card-body">
                    <div class="dc-card-badges">
                        @if($item->category)<span class="badge cat-{{ $item->category }}" style="font-size:.72rem;">{{ $catLabels[$item->category] ?? $item->category }}</span>@endif
                    </div>
                    <div class="dc-card-title">{{ $item->title_tr }}</div>
                    <div class="dc-card-summary">{{ $item->summary_tr }}</div>
                    <div class="dc-card-meta">
                        @if($item->metric_total_views)<span>👁 {{ number_format($item->metric_total_views) }}</span>@endif
                        @php $rtMins = $item->metric_avg_read_time_seconds ? intdiv($item->metric_avg_read_time_seconds, 60) : 0; @endphp @if($rtMins)<span>⏱ {{ $rtMins }} dk</span>@endif
                        @if($item->published_at)<span>{{ $item->published_at->diffForHumans() }}</span>@endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <div id="dc-js-noresult" style="display:none;grid-column:1/-1;text-align:center;padding:32px;color:var(--u-muted,#888);">
            <div style="font-size:2rem;margin-bottom:8px;">🔍</div>
            <div>Bu isimde içerik bulunamadı. Farklı bir kelime deneyin.</div>
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
            @foreach($popular as $p)
            <a href="{{ route('student.content-detail', $p->slug) }}" class="dc-pop-item">
                <div class="dc-pop-icon">{{ $typeIcons[$p->type] ?? '📄' }}</div>
                <div>
                    <div class="dc-pop-title">{{ Str::limit($p->title_tr, 50) }}</div>
                    <div class="dc-pop-meta">{{ $typeLabels[$p->type] ?? $p->type }}</div>
                </div>
            </a>
            @endforeach
        </div>
        @endif

        <div class="dc-widget">
            <div class="dc-widget-title">📂 Kategoriler</div>
            @foreach($catLabels as $key => $label)
            <a href="{{ route('student.discover', ['cat' => $key]) }}" class="dc-pop-item">
                <div>
                    <div class="dc-pop-title">{{ $label }}</div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection

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

@extends('student.layouts.app')

@section('title', $item->title_tr)
@section('page_title', $item->title_tr)

@push('head')
<style>
/* ── Okuma Progress Bar ── */
#cd-progress-bar{position:fixed;top:0;left:0;height:3px;background:var(--u-brand,#7c3aed);width:0%;z-index:9999;transition:width .1s linear;}

/* ── Layout (içerik + sidebar) ── */
.cd-layout{display:grid;grid-template-columns:1fr 260px;gap:24px;align-items:start;}
@media(max-width:960px){.cd-layout{grid-template-columns:1fr;}}
.cd-sidebar{display:flex;flex-direction:column;gap:16px;}
.cd-sidebar-sticky{position:sticky;top:72px;}

/* ── TOC ── */
.cd-toc{background:var(--u-bg,#f5f0ff);border:1px solid var(--u-line,#e2d9f3);border-radius:12px;padding:16px 18px;}
.cd-toc-title{font-size:.78rem;font-weight:700;color:var(--u-muted,#6b5fa6);margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:6px;}
.cd-toc-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;}
.cd-toc-list a{font-size:.83rem;color:var(--u-brand,#7c3aed);text-decoration:none;display:block;padding:5px 0;border-bottom:1px solid var(--u-line,#e5e7eb);line-height:1.4;}
.cd-toc-list a:hover{color:var(--u-text,#1a1a1a);}
.cd-toc-list li:last-child a{border-bottom:none;}
.cd-toc-list li.h3 a{padding-left:14px;color:var(--u-muted,#6b5fa6);font-size:.79rem;}

/* ── Breadcrumb ── */
.cd-breadcrumb{font-size:.82rem;color:var(--u-muted,#888);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;}
.cd-breadcrumb a{color:var(--u-brand,#7c3aed);text-decoration:none;}
.cd-breadcrumb a:hover{text-decoration:underline;}
.cd-breadcrumb span{color:var(--u-muted,#bbb);}

/* ── Hero ── */
.cd-hero{border-radius:14px;overflow:hidden;position:relative;margin-bottom:24px;min-height:240px;display:flex;align-items:flex-end;}
.cd-hero-bg{position:absolute;inset:0;background:linear-gradient(135deg,#1e3a8a,#3b82f6);}
.cd-hero-bg img{width:100%;height:100%;object-fit:cover;}
.cd-hero-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7) 0%,rgba(0,0,0,.1) 60%);}
.cd-hero-body{position:relative;z-index:1;padding:28px 28px 24px;color:#fff;width:100%;}
.cd-hero-badges{display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;}
.cd-hero-badge{padding:3px 10px;border-radius:12px;font-size:.78rem;font-weight:600;background:rgba(255,255,255,.2);backdrop-filter:blur(4px);}
.cd-hero-title{font-size:1.5rem;font-weight:700;line-height:1.3;margin:0 0 6px;}
.cd-hero-summary{font-size:.93rem;opacity:.9;margin:0;}

/* ── Meta bar ── */
.cd-meta-bar{display:flex;gap:16px;align-items:center;flex-wrap:wrap;padding:12px 16px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:10px;margin-bottom:20px;font-size:.82rem;color:var(--u-muted,#888);}
.cd-meta-item{display:flex;align-items:center;gap:5px;}
.cd-tag{padding:2px 8px;border-radius:10px;background:rgba(124,58,237,.1);color:#6d28d9;font-size:.75rem;font-weight:500;}

/* ── Body content ── */
.cd-body{background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;padding:32px 36px;margin-bottom:24px;line-height:1.85;font-size:.97rem;}
.cd-body h2{font-size:1.18rem;font-weight:700;color:var(--u-brand,#7c3aed);border-left:4px solid var(--u-brand,#7c3aed);padding:6px 14px;margin:2.2rem 0 .9rem;background:rgba(124,58,237,.06);border-radius:0 8px 8px 0;}
.cd-body h3{font-size:1.02rem;font-weight:700;color:var(--u-text,#1a1a1a);margin:1.8rem 0 .6rem;padding-bottom:4px;border-bottom:1.5px solid var(--u-line,#e5e7eb);}
.cd-body h4{font-size:.95rem;font-weight:700;color:var(--u-muted,#6b5fa6);margin:1.2rem 0 .4rem;}
.cd-body p{color:var(--u-text,#333);margin-bottom:1.1rem;}
.cd-body ul{list-style:none;padding-left:0;margin-bottom:1.2rem;}
.cd-body ul li{position:relative;padding:7px 0 7px 24px;border-bottom:1px solid var(--u-line,#f3f4f6);color:var(--u-text,#333);}
.cd-body ul li:last-child{border-bottom:none;}
.cd-body ul li::before{content:'→';position:absolute;left:0;color:var(--u-brand,#7c3aed);font-weight:700;font-size:.9rem;}
.cd-body ol{padding-left:1.5rem;margin-bottom:1.2rem;color:var(--u-text,#333);}
.cd-body ol li{margin-bottom:.6rem;padding-left:4px;}
.cd-body blockquote{border-left:4px solid var(--u-brand,#7c3aed);padding:14px 22px;margin:1.8rem 0;background:rgba(124,58,237,.05);border-radius:0 10px 10px 0;font-style:italic;color:var(--u-muted,#6b5fa6);}
.cd-body strong{color:var(--u-text,#1a1a1a);font-weight:700;}
.cd-body a{color:var(--u-brand,#7c3aed);text-decoration:underline;text-underline-offset:3px;}
.cd-body hr{border:none;border-top:2px solid var(--u-line,#e5e7eb);margin:2rem 0;}
@media(max-width:768px){.cd-body{padding:20px 18px;}}

/* ── Embeds ── */
.cd-video-wrap{aspect-ratio:16/9;width:100%;border-radius:10px;overflow:hidden;background:#000;margin-bottom:16px;}
.cd-video-wrap iframe{width:100%;height:100%;border:none;}
.cd-embed-wrap{width:100%;border-radius:10px;overflow:hidden;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);margin-bottom:16px;}
.cd-embed-wrap iframe{width:100%;border:none;}

/* ── Special cards ── */
.cd-experience-card{background:linear-gradient(135deg,#f5f0ff,#ede9fe);border:1px solid var(--u-line,#ddd6fe);border-radius:12px;padding:24px;margin-bottom:20px;position:relative;}
.cd-experience-card::before{content:'"';position:absolute;top:10px;left:20px;font-size:4rem;color:#c4b5fd;line-height:1;font-family:Georgia,serif;}
.cd-experience-body{padding-left:20px;font-size:1rem;color:var(--u-text,#333);font-style:italic;line-height:1.8;}
.cd-tip-card{background:linear-gradient(135deg,#f5f0ff,#ede9fe);border:1px solid var(--u-line,#ddd6fe);border-radius:12px;padding:24px;margin-bottom:20px;display:flex;gap:16px;align-items:flex-start;}
.cd-tip-icon{font-size:2.5rem;flex-shrink:0;}
.cd-tip-body{font-size:1rem;color:var(--u-text,#333);line-height:1.8;}

/* ── City CTA ── */
.cd-city-cta{background:linear-gradient(135deg,var(--u-brand-2,#4c1d95),var(--u-brand,#7c3aed));color:#fff;border-radius:12px;padding:18px 22px;display:flex;justify-content:space-between;align-items:center;text-decoration:none;margin-bottom:24px;transition:opacity .15s;}
.cd-city-cta:hover{opacity:.9;}
.cd-city-cta-left{font-size:.95rem;font-weight:600;}
.cd-city-cta-sub{font-size:.8rem;opacity:.85;margin-top:2px;}
.cd-city-cta-arrow{font-size:1.4rem;}

/* ── Related ── */
.cd-related-title{font-size:1rem;font-weight:700;color:var(--u-text,#1a1a1a);margin-bottom:14px;}
.cd-related-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;}
@media(max-width:900px){.cd-related-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:500px){.cd-related-grid{grid-template-columns:1fr;}}
.cd-rel-card{background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:10px;overflow:hidden;text-decoration:none;color:inherit;display:flex;flex-direction:column;transition:transform .15s;}
.cd-rel-card:hover{transform:translateY(-2px);}
.cd-rel-img{height:90px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;}
.cd-rel-body{padding:10px 12px;}
.cd-rel-title{font-size:.82rem;font-weight:600;color:var(--u-text,#1a1a1a);line-height:1.35;}
.cd-rel-badge{font-size:.72rem;color:var(--u-muted,#999);margin-top:3px;}

/* ── Badge colors ── */
.badge-blog{background:#dce8f8;color:#1a56b0;}
.badge-video_feature{background:#e8e0f8;color:#4c2fa0;}
.badge-podcast{background:#d8eef5;color:#0e5f7a;}
.badge-presentation{background:#e8f0fe;color:#2a4fa8;}
.badge-experience{background:#ede9fe;color:#5b21b6;}
.badge-career_guide{background:#d6eed8;color:#1b5e30;}
.badge-tip{background:#f0e8d8;color:#7a4a10;}
</style>
@endpush

@section('content')
@php
$typeLabels = ['blog'=>'Blog','video_feature'=>'Video','podcast'=>'Podcast','presentation'=>'Sunum','experience'=>'Kişisel Deneyim','career_guide'=>'Kariyer Rehberi','tip'=>'Hızlı İpucu'];
$catLabels  = ['student-life'=>'🎓 Öğrenci Hayatı','culture-fun'=>'🎭 Kültür & Eğlence','careers'=>'💼 Kariyer','tips-tricks'=>'💡 Pratik İpuçları','city-content'=>'🏙 Şehirler','uni-content'=>'🏛 Üniversiteler','success-stories'=>'⭐ Başarı Hikayeleri'];
$typeIcons  = ['blog'=>'📝','video_feature'=>'▶️','podcast'=>'🎙','presentation'=>'📊','experience'=>'💬','career_guide'=>'🗺','tip'=>'💡'];
$gradients  = ['student-life'=>'linear-gradient(135deg,#1e3a8a,#3b82f6)','culture-fun'=>'linear-gradient(135deg,#4c1d95,#8b5cf6)','careers'=>'linear-gradient(135deg,#064e3b,#10b981)','tips-tricks'=>'linear-gradient(135deg,#78350f,#f59e0b)','city-content'=>'linear-gradient(135deg,#0c4a6e,#0ea5e9)','uni-content'=>'linear-gradient(135deg,#312e81,#6366f1)','success-stories'=>'linear-gradient(135deg,#1e1b4b,#a78bfa)'];

$hasToc   = in_array($item->type, ['blog','career_guide','experience']);
$itemTags = is_array($item->tags) ? $item->tags : (is_string($item->tags) ? json_decode($item->tags, true) ?? [] : []);

// Şehir etiketi kontrolü (city-content için Keşfet filtresi)
$citySlugs = ['berlin','münchen','hamburg','köln','frankfurt','stuttgart','düsseldorf','dresden','leipzig','nürnberg','bremen','hannover','dortmund','essen'];
$linkedCitySlug = collect($itemTags)->first(fn($t) => in_array(strtolower($t), $citySlugs));
@endphp

{{-- Breadcrumb --}}
<div class="cd-breadcrumb">
    <a href="{{ route('student.discover') }}">🧭 Keşfet</a>
    <span>/</span>
    @if($item->category)
    <a href="{{ route('student.discover', ['cat' => $item->category]) }}">{{ $catLabels[$item->category] ?? $item->category }}</a>
    <span>/</span>
    @endif
    <span style="color:var(--u-text,#333);">{{ Str::limit($item->title_tr, 50) }}</span>
</div>

{{-- Hero --}}
<div class="cd-hero" style="min-height:260px;">
    <div class="cd-hero-bg" style="background:{{ $gradients[$item->category] ?? 'linear-gradient(135deg,#1e3a8a,#3b82f6)' }}">
        @if($item->cover_image_url)
            <img src="{{ $item->cover_image_url }}" alt="{{ $item->title_tr }}">
        @endif
    </div>
    <div class="cd-hero-overlay"></div>
    <div class="cd-hero-body">
        <div class="cd-hero-badges">
            <span class="cd-hero-badge">{{ $typeIcons[$item->type] ?? '📄' }} {{ $typeLabels[$item->type] ?? $item->type }}</span>
            @if($item->category)
            <span class="cd-hero-badge">{{ $catLabels[$item->category] ?? $item->category }}</span>
            @endif
        </div>
        <div class="cd-hero-title">{{ $item->title_tr }}</div>
        @if($item->summary_tr)
        <div class="cd-hero-summary">{{ $item->summary_tr }}</div>
        @endif
    </div>
</div>

{{-- Meta bar — sidebar varsa gizlenir --}}
<div class="cd-meta-bar" @if($hasToc) style="display:none;" @endif>
    @if($item->metric_total_views)
    <div class="cd-meta-item">👁 {{ number_format($item->metric_total_views) }} görüntülenme</div>
    @endif
    @php $rtMins = $item->metric_avg_read_time_seconds ? intdiv($item->metric_avg_read_time_seconds, 60) : 0; @endphp
    @if($rtMins)
    <div class="cd-meta-item">⏱ {{ $rtMins }} dk okuma</div>
    @endif
    @if($item->published_at)
    <div class="cd-meta-item">📅 {{ $item->published_at->format('d M Y') }}</div>
    @endif
    @if(!empty($item->author))
    <div class="cd-meta-item">✍️ {{ $item->author }}</div>
    @endif
    @foreach($itemTags as $tag)
    <span class="cd-tag">🏷 {{ $tag }}</span>
    @endforeach
</div>

{{-- Beğen + Kaydet --}}
<div style="display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap;">
    <button id="btn-like"
        data-url="{{ route('student.content.react', $item->slug) }}"
        data-reacted="{{ $isLiked ? '1' : '0' }}"
        style="display:flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;border:1.5px solid {{ $isLiked ? 'var(--u-brand,#7c3aed)' : 'var(--u-line,#d6e1ef)' }};background:{{ $isLiked ? 'rgba(124,58,237,.08)' : 'var(--u-card,#fff)' }};color:{{ $isLiked ? 'var(--u-brand,#7c3aed)' : 'var(--u-muted,#6b7280)' }};font-size:.88rem;font-weight:600;cursor:pointer;transition:all .15s;">
        👍 <span id="like-label">{{ $isLiked ? 'Beğenildi' : 'Beğen' }}</span>
        @if($likeCount > 0)<span id="like-count" style="margin-left:4px;opacity:.7;">{{ $likeCount }}</span>@else<span id="like-count" style="margin-left:4px;opacity:.7;display:none;">0</span>@endif
    </button>

    <button id="btn-save"
        data-url="{{ route('student.content.save', $item->slug) }}"
        data-saved="{{ $isSaved ? '1' : '0' }}"
        style="display:flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;border:1.5px solid {{ $isSaved ? 'var(--u-brand,#7c3aed)' : 'var(--u-line,#d6e1ef)' }};background:{{ $isSaved ? 'rgba(124,58,237,.08)' : 'var(--u-card,#fff)' }};color:{{ $isSaved ? 'var(--u-brand,#7c3aed)' : 'var(--u-muted,#6b7280)' }};font-size:.88rem;font-weight:600;cursor:pointer;transition:all .15s;">
        🔖 <span id="save-label">{{ $isSaved ? 'Kaydedildi' : 'Kaydet' }}</span>
    </button>

    <a href="{{ route('student.saved') }}" style="font-size:.8rem;color:var(--u-muted,#6b7280);text-decoration:none;margin-left:auto;">📋 Kayıtlarım →</a>
</div>

{{-- City CTA --}}
@if($linkedCitySlug)
<a href="{{ route('student.discover', ['cat'=>'city-content','q'=>$linkedCitySlug]) }}" class="cd-city-cta">
    <div>
        <div class="cd-city-cta-left">📍 {{ ucfirst($linkedCitySlug) }} Şehir İçerikleri</div>
        <div class="cd-city-cta-sub">Bu şehre ait blog, deneyim ve kariyer rehberleri</div>
    </div>
    <div class="cd-city-cta-arrow">→</div>
</a>
@endif

{{-- 2-sütun layout: İçerik + TOC Sidebar (blog/career_guide/experience) --}}
@if($hasToc)
<div class="cd-layout">
<div>{{-- Sol: İçerik --}}
@endif

<div class="cd-body" id="cd-body-content">
    @if($item->type === 'video_feature' && $item->video_url)
        <div class="cd-video-wrap">
            <iframe src="{{ $item->video_url }}?rel=0&modestbranding=1"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen></iframe>
        </div>
        @if($item->content_tr){!! str_replace('\n', '<br>', $item->content_tr) !!}@endif

    @elseif($item->type === 'podcast' && $item->video_url)
        <div class="cd-embed-wrap">
            <iframe src="{{ $item->video_url }}" style="min-height:152px;"></iframe>
        </div>
        @if($item->content_tr){!! str_replace('\n', '<br>', $item->content_tr) !!}@endif

    @elseif($item->type === 'presentation' && $item->video_url)
        <div class="cd-embed-wrap">
            <iframe src="{{ $item->video_url }}" style="min-height:400px;" allowfullscreen></iframe>
        </div>
        @if($item->content_tr){!! str_replace('\n', '<br>', $item->content_tr) !!}@endif

    @elseif($item->type === 'experience')
        <div class="cd-experience-card">
            <div class="cd-experience-body">{!! str_replace('\n', '<br>', $item->content_tr) !!}</div>
        </div>

    @elseif($item->type === 'tip')
        <div class="cd-tip-card">
            <div class="cd-tip-icon">💡</div>
            <div class="cd-tip-body">{!! str_replace('\n', '<br>', $item->content_tr) !!}</div>
        </div>

    @else
        @if($item->content_tr)
        {!! str_replace('\n', '<br>', $item->content_tr) !!}
        @else
        <p style="color:var(--u-muted,#888);font-style:italic;">İçerik yakında eklenecek.</p>
        @endif
    @endif
</div>

@if($hasToc)
</div>{{-- /sol --}}

{{-- Sağ: TOC Sidebar --}}
<div class="cd-sidebar">
<div class="cd-sidebar-sticky">
<div class="cd-toc" id="cd-toc">
    <div class="cd-toc-title">📑 İçindekiler</div>
    <ul class="cd-toc-list"></ul>
</div>

{{-- Meta sidebar'da --}}
<div style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;padding:16px 18px;font-size:.83rem;color:var(--u-muted,#6b7280);display:flex;flex-direction:column;gap:8px;">
    @if($item->metric_total_views)
    <div>👁 <strong style="color:var(--u-text,#333);">{{ number_format($item->metric_total_views) }}</strong> görüntülenme</div>
    @endif
    @php $rtMins = $item->metric_avg_read_time_seconds ? intdiv($item->metric_avg_read_time_seconds, 60) : 0; @endphp
    @if($rtMins)
    <div>⏱ <strong style="color:var(--u-text,#333);">{{ $rtMins }} dk</strong> okuma süresi</div>
    @endif
    @if($item->published_at)
    <div>📅 {{ $item->published_at->format('d M Y') }}</div>
    @endif
    @if(!empty($item->author))
    <div>✍️ {{ $item->author }}</div>
    @endif
    @foreach($itemTags as $tag)
    <a href="{{ route('student.discover', ['q' => $tag]) }}" class="cd-tag" style="width:fit-content;">🏷 {{ $tag }}</a>
    @endforeach
</div>
</div>
</div>{{-- /sidebar --}}
</div>{{-- /cd-layout --}}
@endif

{{-- İlgili İçerikler --}}
@if($related->isNotEmpty())
<div class="cd-related-title">📚 İlgili İçerikler</div>
<div class="cd-related-grid">
    @foreach($related as $r)
    <a href="{{ route('student.content-detail', $r->slug) }}" class="cd-rel-card">
        <div class="cd-rel-img" style="background:{{ $gradients[$r->category] ?? 'linear-gradient(135deg,#4c1d95,#7c3aed)' }}">
            {{ $typeIcons[$r->type] ?? '📄' }}
        </div>
        <div class="cd-rel-body">
            <div class="cd-rel-title">{{ Str::limit($r->title_tr, 55) }}</div>
            <div class="cd-rel-badge">{{ $typeLabels[$r->type] ?? $r->type }}</div>
        </div>
    </a>
    @endforeach
</div>
@endif

{{-- Önceki / Sonraki --}}
@if(($prevItem ?? null) || ($nextItem ?? null))
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:24px;">
    @if($prevItem ?? null)
    <a href="{{ route('student.content-detail', $prevItem->slug) }}"
       style="display:flex;flex-direction:column;gap:4px;background:var(--u-card,#fff);border:1px solid var(--u-line,#d6e1ef);border-radius:10px;padding:14px 16px;text-decoration:none;color:inherit;transition:box-shadow .15s;"
       onmouseover="this.style.boxShadow='0 4px 14px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
        <span style="font-size:.73rem;font-weight:700;color:var(--u-muted,#6b5fa6);text-transform:uppercase;letter-spacing:.04em;">← Önceki</span>
        <span style="font-size:.88rem;font-weight:600;color:var(--u-text,#0f172a);line-height:1.35;">{{ Str::limit($prevItem->title_tr, 60) }}</span>
    </a>
    @else<div></div>@endif

    @if($nextItem ?? null)
    <a href="{{ route('student.content-detail', $nextItem->slug) }}"
       style="display:flex;flex-direction:column;gap:4px;background:var(--u-card,#fff);border:1px solid var(--u-line,#d6e1ef);border-radius:10px;padding:14px 16px;text-decoration:none;color:inherit;text-align:right;transition:box-shadow .15s;"
       onmouseover="this.style.boxShadow='0 4px 14px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
        <span style="font-size:.73rem;font-weight:700;color:var(--u-muted,#6b5fa6);text-transform:uppercase;letter-spacing:.04em;">Sonraki →</span>
        <span style="font-size:.88rem;font-weight:600;color:var(--u-text,#0f172a);line-height:1.35;">{{ Str::limit($nextItem->title_tr, 60) }}</span>
    </a>
    @else<div></div>@endif
</div>
@endif

<div style="margin-top:16px;text-align:center;">
    <a href="{{ route('student.discover') }}" class="btn alt">← Keşfet'e Dön</a>
</div>

@push('scripts')
<div id="cd-progress-bar"></div>
<script>
// ── Okuma İlerleme Barı ──
(function(){
    var bar=document.getElementById('cd-progress-bar');
    if(!bar)return;
    document.addEventListener('scroll',function(){
        var doc=document.documentElement;
        var scrolled=doc.scrollTop||document.body.scrollTop;
        var total=doc.scrollHeight-doc.clientHeight;
        bar.style.width=(total>0?Math.min(100,(scrolled/total)*100):0)+'%';
    },{passive:true});
})();

// ── İçindekiler (TOC) + Scroll Tracking ──
(function(){
    var body=document.getElementById('cd-body-content');
    var toc=document.getElementById('cd-toc');
    if(!body||!toc)return;
    var headings=body.querySelectorAll('h2,h3');
    if(headings.length<2){toc.style.display='none';return;}
    var list=toc.querySelector('.cd-toc-list');
    headings.forEach(function(h,i){
        var id='cd-h-'+i;
        h.id=id;
        var li=document.createElement('li');
        li.className=h.tagName==='H3'?'h3':'';
        var a=document.createElement('a');
        a.href='#'+id;
        a.textContent=h.textContent;
        a.addEventListener('click',function(e){
            e.preventDefault();
            document.getElementById(id).scrollIntoView({behavior:'smooth',block:'start'});
        });
        li.appendChild(a);
        list.appendChild(li);
    });
    // Aktif link takibi
    var tocLinks=list.querySelectorAll('a');
    window.addEventListener('scroll',function(){
        var scrollY=window.scrollY+100;
        var active=null;
        headings.forEach(function(h){if(h.offsetTop<=scrollY)active=h.id;});
        tocLinks.forEach(function(a){
            var isActive=a.getAttribute('href')==='#'+active;
            a.style.fontWeight=isActive?'700':'';
            a.style.color=isActive?'var(--u-text,#1a1a1a)':'';
        });
    },{passive:true});
})();

// ── Beğen ──
(function(){
    var btn=document.getElementById('btn-like');
    if(!btn)return;
    btn.addEventListener('click',function(){
        fetch(btn.getAttribute('data-url'),{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'','Accept':'application/json'}})
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d.ok)return;
            var on=d.reacted;
            document.getElementById('like-label').textContent=on?'Beğenildi':'Beğen';
            var c=document.getElementById('like-count');
            if(d.count>0){c.textContent=d.count;c.style.display='';}else{c.style.display='none';}
            btn.style.borderColor=on?'var(--u-brand,#7c3aed)':'var(--u-line,#d6e1ef)';
            btn.style.background=on?'rgba(124,58,237,.08)':'var(--u-card,#fff)';
            btn.style.color=on?'var(--u-brand,#7c3aed)':'var(--u-muted,#6b7280)';
        });
    });
})();

// ── Kaydet ──
(function(){
    var btn=document.getElementById('btn-save');
    if(!btn)return;
    btn.addEventListener('click',function(){
        fetch(btn.getAttribute('data-url'),{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'','Accept':'application/json'}})
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d.ok)return;
            var on=d.saved;
            document.getElementById('save-label').textContent=on?'Kaydedildi':'Kaydet';
            btn.style.borderColor=on?'var(--u-brand,#7c3aed)':'var(--u-line,#d6e1ef)';
            btn.style.background=on?'rgba(124,58,237,.08)':'var(--u-card,#fff)';
            btn.style.color=on?'var(--u-brand,#7c3aed)':'var(--u-muted,#6b7280)';
        });
    });
})();
</script>
@endpush
@endsection

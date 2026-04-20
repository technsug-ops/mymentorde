{{-- ══════════════════════════════════════════════════════════════════════════
  Shared Success Stories partial — used by both Guest and Student portals.
  Host view provides @extends + @section('content') wrapper and @include's this.
═══════════════════════════════════════════════════════════════════════════ --}}

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
.jm-minimalist .ss-hero { background: #e2e5ec !important; color: var(--u-text,#1a1a1a) !important; border: 1px solid rgba(0,0,0,.10) !important; }
.jm-minimalist .ss-hero * { color: inherit !important; opacity: 1 !important; }

/* ══════ Hero (compact + data-forward + image bg) ══════ */
.ss-hero {
    color:#fff; border-radius:14px; margin-bottom:20px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.14);
    position:relative;
    background:#0891b2 url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1400&q=80') center/cover;
}
.ss-hero::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, rgba(8,145,178,.92) 0%, rgba(22,163,74,.82) 100%);
}
.ss-hero-body { position:relative; display:flex; align-items:center; gap:24px; padding:26px 28px; }
.ss-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:8px; }
.ss-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.82; }
.ss-hero-marker { display:inline-block; width:5px; height:16px; background:rgba(255,255,255,.75); border-radius:3px; }
.ss-hero-title { font-size:32px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.5px; }
.ss-hero-overview { font-size:14px; opacity:.92; line-height:1.55; max-width:600px; margin-top:2px; }
.ss-hero-stats { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.ss-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:20px; background:rgba(255,255,255,.18); font-size:12px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.ss-hero-stat-ico { font-size:13px; }
.ss-hero-icon { font-size:64px; line-height:1; flex-shrink:0; opacity:.9; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }

@media (max-width:720px){
    .ss-hero{border-radius:12px;}
    .ss-hero-body{gap:14px; padding:18px; align-items:flex-start;}
    .ss-hero-title{font-size:22px; letter-spacing:-.3px;}
    .ss-hero-overview{font-size:12.5px; line-height:1.45; max-width:none;}
    .ss-hero-stats{gap:6px; margin-top:10px; padding-top:10px;}
    .ss-hero-stat{padding:4px 9px; font-size:11px;}
    .ss-hero-icon{font-size:40px; align-self:flex-start; margin-top:2px;}
    .ss-hero-label{font-size:10px; letter-spacing:.5px;}
    .ss-hero-marker{height:12px; width:3px;}
}

/* ══════ Stat Cards ══════ */
.ss-stats-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:28px; }
.ss-stat-card {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:14px;
    padding:18px 20px; position:relative; overflow:hidden;
    transition:transform .15s, box-shadow .15s;
    --s-color: var(--u-brand, #2563eb);
}
.ss-stat-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.08); }
.ss-stat-card::before {
    content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--s-color);
}
.ss-stat-head { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
.ss-stat-icon {
    width:38px; height:38px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:19px; flex-shrink:0;
    background:color-mix(in srgb, var(--s-color) 12%, #fff);
    border:1px solid color-mix(in srgb, var(--s-color) 22%, transparent);
}
.ss-stat-label { font-size:12px; font-weight:600; color:var(--u-muted); letter-spacing:.3px; }
.ss-stat-value { font-size:32px; font-weight:800; color:var(--u-text); line-height:1; letter-spacing:-.8px; margin-bottom:8px; }
.ss-stat-bar { height:4px; border-radius:2px; background:color-mix(in srgb, var(--s-color) 12%, transparent); overflow:hidden; }
.ss-stat-bar-fill { height:100%; background:var(--s-color); border-radius:2px; transition:width 1s ease-out; width:0; }

@media (max-width:720px){
    .ss-stats-grid{grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:20px;}
    .ss-stat-card{padding:12px 12px 14px;}
    .ss-stat-head{gap:8px; margin-bottom:8px;}
    .ss-stat-icon{width:32px; height:32px; font-size:16px; border-radius:8px;}
    .ss-stat-label{font-size:10.5px;}
    .ss-stat-value{font-size:22px; margin-bottom:6px;}
}

/* ══════ Filter Chips ══════ */
.ss-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.ss-filter-chip {
    padding:7px 14px; border-radius:20px; border:1px solid var(--u-line);
    background:var(--u-card); color:var(--u-text);
    font-size:12px; font-weight:600; cursor:pointer;
    display:inline-flex; align-items:center; gap:6px;
    transition:all .15s;
}
.ss-filter-chip:hover { border-color:var(--u-brand); }
.ss-filter-chip.active { background:var(--u-brand); color:#fff; border-color:var(--u-brand); }
.ss-filter-count {
    font-size:10.5px; font-weight:700; padding:1px 7px; border-radius:10px;
    background:rgba(0,0,0,.06); color:var(--u-muted);
}
.ss-filter-chip.active .ss-filter-count { background:rgba(255,255,255,.25); color:#fff; }

/* ══════ Story Cards (Quote-first with photo) ══════ */
.ss-story-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px; }
.ss-story {
    --s-color: #6366f1;
    position:relative; display:flex; flex-direction:column;
    background:var(--u-card); border:1px solid var(--u-line); border-radius:14px;
    cursor:pointer; overflow:hidden;
    transition:transform .18s, box-shadow .18s, border-color .18s;
}
.ss-story-photo {
    position:relative; height:200px; overflow:hidden;
    background:color-mix(in srgb, var(--s-color) 10%, var(--u-bg));
}
.ss-story-photo img {
    width:100%; height:100%; object-fit:cover;
    object-position:center 25%;
    transition:transform .4s;
}
.ss-story:hover .ss-story-photo img { transform:scale(1.05); }
.ss-story-photo::after {
    content:''; position:absolute; inset:auto 0 0 0; height:55%;
    background:linear-gradient(to bottom, transparent, rgba(0,0,0,.75));
    pointer-events:none;
}
.ss-story-photo-badge {
    position:absolute; top:10px; right:10px;
    padding:4px 10px; border-radius:20px;
    background:rgba(255,255,255,.95); color:var(--s-color);
    font-size:10px; font-weight:800; letter-spacing:.3px;
    box-shadow:0 2px 8px rgba(0,0,0,.14);
}
.ss-story-photo-city {
    position:absolute; left:12px; bottom:12px;
    color:#fff; font-size:12px; font-weight:700;
    display:inline-flex; align-items:center; gap:5px;
    text-shadow:0 1px 3px rgba(0,0,0,.7);
    z-index:1;
}
.ss-story-photo-city::before { content:'📍'; font-size:13px; }
.ss-story-body { padding:18px 20px 16px; display:flex; flex-direction:column; flex:1; }
.ss-story:hover {
    transform:translateY(-3px);
    box-shadow:0 12px 32px rgba(0,0,0,.12);
    border-color:color-mix(in srgb, var(--s-color) 35%, var(--u-line));
}
.ss-story-quote-mark {
    font-family:Georgia, serif; font-size:48px; line-height:.7;
    color:var(--s-color); opacity:.25; margin-bottom:4px;
    font-weight:700;
}
.ss-story-quote {
    font-size:13.5px; line-height:1.6; color:var(--u-text);
    display:-webkit-box; -webkit-line-clamp:4; -webkit-box-orient:vertical;
    overflow:hidden; margin-bottom:14px; flex:1;
}
.ss-story-foot { display:flex; align-items:center; gap:12px; padding-top:14px; border-top:1px solid var(--u-line); }
.ss-story-avatar {
    width:42px; height:42px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:800; font-size:13px; flex-shrink:0;
    box-shadow:0 2px 8px rgba(0,0,0,.12);
}
.ss-story-meta { flex:1; min-width:0; }
.ss-story-name { font-weight:700; font-size:13px; color:var(--u-text); line-height:1.2; margin-bottom:2px; }
.ss-story-prog { font-size:11px; color:var(--u-muted); line-height:1.3; }

@media (max-width:900px){ .ss-story-grid{grid-template-columns:repeat(2,1fr);} .ss-story-photo{height:180px;} }
@media (max-width:640px){
    .ss-story-grid{grid-template-columns:1fr; gap:12px;}
    .ss-story-photo{height:220px;}
    .ss-story-body{padding:14px 16px 14px;}
    .ss-story-quote-mark{font-size:36px;}
    .ss-story-quote{font-size:12.5px; -webkit-line-clamp:3;}
    .ss-story-avatar{width:36px; height:36px; font-size:12px;}
}

/* ══════ Video Testimonials ══════ */
.ss-video-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px; }
.ss-video {
    position:relative; border-radius:14px; overflow:hidden;
    background:#0f172a; cursor:pointer;
    border:1px solid var(--u-line);
    transition:transform .18s, box-shadow .18s;
    aspect-ratio: 4/5;
}
.ss-video:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(0,0,0,.2); }
.ss-video-img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
.ss-video-overlay {
    position:absolute; inset:0;
    background:linear-gradient(to bottom, rgba(0,0,0,0) 30%, rgba(0,0,0,.85));
}
.ss-video-play {
    position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
    width:54px; height:54px; border-radius:50%;
    background:rgba(255,255,255,.15); border:2px solid rgba(255,255,255,.55);
    display:flex; align-items:center; justify-content:center;
    transition:transform .2s, background .2s;
}
.ss-video:hover .ss-video-play { transform:translate(-50%,-50%) scale(1.1); background:rgba(255,255,255,.25); }
.ss-video-play-tri {
    width:0; height:0; border-top:10px solid transparent; border-bottom:10px solid transparent;
    border-left:16px solid #fff; margin-left:3px;
}
.ss-video-caption { position:absolute; left:0; right:0; bottom:0; padding:14px 14px 14px; color:#fff; }
.ss-video-title { font-weight:700; font-size:13.5px; line-height:1.3; margin-bottom:4px; }
.ss-video-desc { font-size:11.5px; opacity:.85; line-height:1.45; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.ss-video-badge {
    position:absolute; top:12px; left:12px;
    padding:3px 9px; border-radius:20px;
    background:rgba(255,255,255,.92); color:#0f172a;
    font-size:10px; font-weight:700;
    display:inline-flex; align-items:center; gap:4px;
}

@media (max-width:900px){ .ss-video-grid{grid-template-columns:repeat(2,1fr);} }
@media (max-width:520px){ .ss-video-grid{grid-template-columns:1fr;} }

/* ══════ CTA ══════ */
.ss-cta {
    background:linear-gradient(135deg, #0891b2 0%, #16a34a 100%);
    color:#fff; border-radius:16px; padding:32px 28px;
    text-align:center; margin-bottom:20px;
    position:relative; overflow:hidden;
}
.ss-cta::before {
    content:'🚀'; position:absolute; top:-20px; right:-20px;
    font-size:140px; opacity:.08; pointer-events:none;
}
.ss-cta-title { font-size:22px; font-weight:800; margin:0 0 8px; letter-spacing:-.3px; }
.ss-cta-sub { font-size:14px; opacity:.9; margin-bottom:18px; max-width:520px; margin-left:auto; margin-right:auto; line-height:1.5; }
.ss-cta-btn {
    display:inline-flex; align-items:center; gap:8px;
    padding:11px 22px; border-radius:24px;
    background:#fff; color:#0891b2; font-weight:700; font-size:14px;
    text-decoration:none; box-shadow:0 6px 18px rgba(0,0,0,.18);
    transition:transform .15s, box-shadow .15s;
}
.ss-cta-btn:hover { transform:translateY(-1px); box-shadow:0 8px 22px rgba(0,0,0,.24); text-decoration:none; }

@media (max-width:720px){
    .ss-cta{padding:22px 18px; border-radius:12px;}
    .ss-cta-title{font-size:18px;}
    .ss-cta-sub{font-size:12.5px;}
}

/* ══════ Modal ══════ */
.ss-modal-overlay { display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.55); backdrop-filter:blur(3px); align-items:center; justify-content:center; padding:20px; }
.ss-modal-overlay.open { display:flex; }
.ss-modal-box { background:var(--u-card,#fff); border-radius:18px; max-width:620px; width:100%; max-height:88vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.22); animation:ssModalIn .2s ease; }
@keyframes ssModalIn { from{opacity:0; transform:scale(.95) translateY(12px);} to{opacity:1; transform:scale(1) translateY(0);} }
.ss-modal-head { padding:20px 22px 16px; display:flex; align-items:center; gap:14px; border-bottom:1px solid var(--u-line,#e5e7eb); position:sticky; top:0; background:var(--u-card,#fff); z-index:1; border-radius:18px 18px 0 0; }
.ss-modal-close { margin-left:auto; width:32px; height:32px; border-radius:50%; border:1px solid var(--u-line,#e5e7eb); background:var(--u-bg,#f8fafc); cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ss-modal-close:hover { background:var(--u-line,#e5e7eb); }
.ss-modal-body { padding:20px 22px 24px; font-size:var(--tx-sm); color:var(--u-text,#1e293b); line-height:1.8; }
.ss-modal-body h3, .ss-modal-body h4 { font-weight:700; margin:14px 0 6px; }
.ss-modal-body p { margin:0 0 10px; }
.ss-modal-body blockquote { border-left:3px solid var(--theme-accent-guest, var(--u-brand,#2563eb)); margin:12px 0; padding:8px 14px; background:var(--u-bg,#f8fafc); border-radius:0 8px 8px 0; font-style:italic; color:var(--u-muted,#64748b); }
.ss-modal-body ul, .ss-modal-body ol { padding-left:20px; margin:8px 0; }
.ss-modal-body li { margin-bottom:4px; }

/* Filter hide */
.ss-story[data-source-hidden="1"] { display:none; }

/* ══════ City Mosaic ══════ */
.ss-cities { display:grid; grid-template-columns:repeat(6,1fr); gap:8px; margin-bottom:28px; }
.ss-city-tile { position:relative; aspect-ratio:1; border-radius:10px; overflow:hidden; cursor:pointer; transition:transform .2s; }
.ss-city-tile:hover { transform:scale(1.04); z-index:2; }
.ss-city-tile img { width:100%; height:100%; object-fit:cover; }
.ss-city-tile::after {
    content:''; position:absolute; inset:0;
    background:linear-gradient(to bottom, rgba(0,0,0,0) 40%, rgba(0,0,0,.75));
}
.ss-city-tile-label {
    position:absolute; left:8px; bottom:7px; right:8px;
    color:#fff; font-weight:800; font-size:11px;
    letter-spacing:.4px; z-index:1;
    text-shadow:0 1px 3px rgba(0,0,0,.6);
}
.ss-city-tile-count {
    position:absolute; top:8px; right:8px;
    background:rgba(255,255,255,.95); color:var(--u-text);
    font-size:10px; font-weight:800; padding:2px 7px; border-radius:12px;
    z-index:1; box-shadow:0 2px 6px rgba(0,0,0,.2);
}
@media (max-width:720px){ .ss-cities{grid-template-columns:repeat(3,1fr); gap:6px;} }

/* Section title */
.ss-section-title { display:flex; align-items:baseline; gap:10px; font-weight:700; font-size:var(--tx-base); margin-bottom:14px; color:var(--u-text); }
.ss-section-title small { font-size:11.5px; font-weight:500; color:var(--u-muted); }
</style>
@endpush

@php
if (!function_exists('ssExtractYtId')) {
    function ssExtractYtId(?string $url): ?string {
        if (!$url) return null;
        preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|watch\?v=|v\/))([\w\-]{11})/', $url, $m);
        return $m[1] ?? null;
    }
}
$brandName = config('brand.name', 'MentorDE');

$heroYtId  = ssExtractYtId($heroVideo->video_url ?? null);
$heroThumb = ($heroVideo->video_thumbnail_url ?? null) ?: ($heroYtId ? "https://img.youtube.com/vi/{$heroYtId}/hqdefault.jpg" : null);
$heroTitle = $heroVideo->title_tr ?? 'Almanya hayali nasıl gerçeğe dönüşür?';
$heroDesc  = $heroVideo->summary_tr ?? ($brandName . ' ile Almanya\'ya yerleşen öğrencilerin gerçek hikayeleri.');

// Portal context — Student mi Guest mi?
$ssIsStudent = request()->is('student/*');
$ssCta = $ssIsStudent
    ? ['href' => route('student.messages'), 'title' => 'Hikayen gelecek öğrencilere ilham olsun', 'sub' => 'Danışmanına mesaj at, kendi başarı hikayeni paylaş.', 'label' => 'Danışmanımla Paylaş']
    : ['href' => route('guest.registration.form'), 'title' => 'Sen de bu ailenin bir parçası ol', 'sub' => 'Danışmanın seni bekliyor. Almanya yolculuğuna bugün başla.', 'label' => 'Başvurumu Tamamla'];

// City tile route — guest route is public, works from both portals
$cityTileRoute = function ($slug) use ($ssIsStudent) {
    return $ssIsStudent ? route('student.info.city-detail', $slug) : route('guest.city-detail', $slug);
};

$srcMeta = [
    'Google'     => ['color'=>'#4285F4'],
    'Trustpilot' => ['color'=>'#00b67a'],
    $brandName   => ['color'=>'#e11d48'],
];
$avatarGradients = [
    'linear-gradient(135deg,#7c3aed,#2563eb)',
    'linear-gradient(135deg,#0891b2,#16a34a)',
    'linear-gradient(135deg,#dc2626,#d97706)',
    'linear-gradient(135deg,#f59e0b,#ef4444)',
    'linear-gradient(135deg,#14b8a6,#0891b2)',
    'linear-gradient(135deg,#8b5cf6,#ec4899)',
];

$hasCms = isset($cmsStories) && $cmsStories->isNotEmpty();

$staticStories = [
    ['initials'=>'AK','name'=>'Ahmet K.','program'=>'TU München — Mak. Müh.','source'=>'Google',
     'photo'=>'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=600&q=80','city'=>'Münih',
     'quote'=>$brandName . ' olmadan bu süreci tek başıma yönetemezdim. Uni-assist başvurusundan vize sürecine kadar her adımda yanımda oldular. Şu an TU München\'de 2. yılımdayım.'],
    ['initials'=>'ZY','name'=>'Zeynep Y.','program'=>'TU Berlin — Bilgisayar Müh.','source'=>'Trustpilot',
     'photo'=>'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=600&q=80','city'=>'Berlin',
     'quote'=>'Belge sürecinde çok zorlandım. Apostil ve yeminli tercüme için nereye gideceğimi bilmiyordum. Danışmanım adım adım rehberlik etti. TU Berlin\'e kabul aldım!'],
    ['initials'=>'MS','name'=>'Murat S.','program'=>'HAW Hamburg — İşletme','source'=>$brandName,
     'photo'=>'https://images.unsplash.com/photo-1527980965255-d3b416303d12?w=600&q=80','city'=>'Hamburg',
     'quote'=>'Almanca B2 sınavına hazırlanırken hem çalışıyor hem de başvuru sürecini yürütmek çok zordu. ' . $brandName . '\'nin sistematik takibi sayesinde hiçbir belge eksik kalmadı.'],
    ['initials'=>'EA','name'=>'Elif A.','program'=>'Uni Stuttgart — Elektrik Müh.','source'=>'Google',
     'photo'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=600&q=80','city'=>'Stuttgart',
     'quote'=>'Türkiye\'de lise mezunuydum. Studienkolleg süreci çok karmaşık görünüyordu. Danışmanım doğrudan üniversiteye geçiş için alternatif bir yol gösterdi. Harika!'],
    ['initials'=>'KD','name'=>'Kemal D.','program'=>'Goethe Uni — Finans','source'=>'Trustpilot',
     'photo'=>'https://images.unsplash.com/photo-1603415526960-f7e0328c63b1?w=600&q=80','city'=>'Frankfurt',
     'quote'=>'Goethe Uni\'ye kabul aldığımda inanamadım. Motivasyon mektubum için AI asistan ve danışman birlikte çok yardımcı oldu. Keşke daha önce başlasaydım.'],
    ['initials'=>'NT','name'=>'Neslihan T.','program'=>'TH Köln — Medya Tasarımı','source'=>$brandName,
     'photo'=>'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=600&q=80','city'=>'Köln',
     'quote'=>'Portfolyo hazırlığı ve yetenek sınavına danışmanımla birlikte hazırlandım. TH Köln tasarım programına kabul — çok mutluyum!'],
];

$allStories = $hasCms ? $cmsStories : collect($staticStories);
$counts = ['all' => $allStories->count(), 'Google' => 0, 'Trustpilot' => 0, $brandName => 0];
foreach ($allStories as $st) {
    $src = $hasCms ? ((is_array($st->tags) ? ($st->tags[0] ?? $brandName) : $brandName)) : $st['source'];
    if (isset($counts[$src])) $counts[$src]++;
}
@endphp

{{-- ══════ Hero ══════ --}}
<div class="ss-hero">
    <div class="ss-hero-body">
        <div class="ss-hero-main">
            <div class="ss-hero-label"><span class="ss-hero-marker"></span>{{ $brandName }} Ailesi</div>
            <h1 class="ss-hero-title">Öğrenci Başarı Hikayeleri</h1>
            <div class="ss-hero-overview">
                Türkiye'den Almanya'ya hayallerini gerçekleştiren öğrencilerimizin gerçek deneyimleri.
            </div>
            <div class="ss-hero-stats">
                <span class="ss-hero-stat"><span class="ss-hero-stat-ico">🎓</span>80+ öğrenci</span>
                <span class="ss-hero-stat"><span class="ss-hero-stat-ico">🏛</span>50+ üniversite</span>
                <span class="ss-hero-stat"><span class="ss-hero-stat-ico">⭐</span>%95 memnuniyet</span>
            </div>
        </div>
        <div class="ss-hero-icon">🌟</div>
    </div>
</div>

{{-- ══════ Stat Cards ══════ --}}
<div class="ss-stats-grid">
    <div class="ss-stat-card" style="--s-color:#0891b2;">
        <div class="ss-stat-head">
            <div class="ss-stat-icon">🎓</div>
            <div class="ss-stat-label">Almanya'da Öğrenci</div>
        </div>
        <div class="ss-stat-value">80+</div>
        <div class="ss-stat-bar"><div class="ss-stat-bar-fill" data-fill="85"></div></div>
    </div>
    <div class="ss-stat-card" style="--s-color:#7c3aed;">
        <div class="ss-stat-head">
            <div class="ss-stat-icon">🏛</div>
            <div class="ss-stat-label">Farklı Üniversite</div>
        </div>
        <div class="ss-stat-value">50+</div>
        <div class="ss-stat-bar"><div class="ss-stat-bar-fill" data-fill="70"></div></div>
    </div>
    <div class="ss-stat-card" style="--s-color:#f59e0b;">
        <div class="ss-stat-head">
            <div class="ss-stat-icon">⭐</div>
            <div class="ss-stat-label">Memnuniyet Oranı</div>
        </div>
        <div class="ss-stat-value">%95</div>
        <div class="ss-stat-bar"><div class="ss-stat-bar-fill" data-fill="95"></div></div>
    </div>
</div>

{{-- ══════ City Mosaic ══════ --}}
<div class="ss-section-title">📍 Öğrencilerimiz Almanya'da <small>6 şehirde 80+ öğrenci</small></div>
<div class="ss-cities">
    @foreach([
        ['city'=>'Münih',     'count'=>18, 'img'=>'https://images.unsplash.com/photo-1595867818082-083862f3d630?w=400&q=80', 'slug'=>'munich'],
        ['city'=>'Berlin',    'count'=>22, 'img'=>'https://images.unsplash.com/photo-1560969184-10fe8719e047?w=400&q=80',  'slug'=>'berlin'],
        ['city'=>'Hamburg',   'count'=>12, 'img'=>'https://images.unsplash.com/photo-1552751753-0fc84ae3a766?w=400&q=80',  'slug'=>'hamburg'],
        ['city'=>'Frankfurt', 'count'=>9,  'img'=>'https://images.unsplash.com/photo-1577185748577-b842fd77e9c7?w=400&q=80','slug'=>'frankfurt'],
        ['city'=>'Köln',      'count'=>8,  'img'=>'https://images.unsplash.com/photo-1598892886985-a6e2b28a5a22?w=400&q=80','slug'=>'cologne'],
        ['city'=>'Stuttgart', 'count'=>7,  'img'=>'https://images.unsplash.com/photo-1583079889956-c0c4dd6f61c1?w=400&q=80','slug'=>'stuttgart'],
    ] as $cityTile)
    <a class="ss-city-tile" href="{{ $cityTileRoute($cityTile['slug']) }}">
        <img src="{{ $cityTile['img'] }}" alt="{{ $cityTile['city'] }}" loading="lazy">
        <span class="ss-city-tile-count">{{ $cityTile['count'] }}</span>
        <span class="ss-city-tile-label">{{ $cityTile['city'] }}</span>
    </a>
    @endforeach
</div>

{{-- ══════ Filter Chips ══════ --}}
<div class="ss-section-title">
    Öğrenci Deneyimleri
    @if($hasCms)<small>— Marketing Admin panelinden yönetilir</small>@endif
</div>

<div class="ss-filters" id="ssFilters">
    <button class="ss-filter-chip active" data-filter="all" type="button">
        Tümü <span class="ss-filter-count">{{ $counts['all'] }}</span>
    </button>
    @foreach(['Google','Trustpilot',$brandName] as $src)
        @if($counts[$src] > 0)
        <button class="ss-filter-chip" data-filter="{{ $src }}" type="button">
            {{ $src }} <span class="ss-filter-count">{{ $counts[$src] }}</span>
        </button>
        @endif
    @endforeach
</div>

{{-- ══════ Story Grid ══════ --}}
<div class="ss-story-grid">
@if($hasCms)
    @php
        $cmsPhotos = ['https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=600&q=80',
                      'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=600&q=80',
                      'https://images.unsplash.com/photo-1527980965255-d3b416303d12?w=600&q=80',
                      'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=600&q=80',
                      'https://images.unsplash.com/photo-1603415526960-f7e0328c63b1?w=600&q=80',
                      'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=600&q=80'];
    @endphp
    @foreach($cmsStories as $i => $story)
    @php
        $tags       = is_array($story->tags) ? $story->tags : [];
        $source     = $tags[0] ?? $brandName;
        $sm         = $srcMeta[$source] ?? ['color'=>'#6366f1'];
        $initials   = $story->cover_image_alt ?: strtoupper(mb_substr($story->title_tr ?? 'M', 0, 2));
        $gradient   = $avatarGradients[$i % count($avatarGradients)];
        $storyPhoto = $story->cover_image_url ?? $cmsPhotos[$i % count($cmsPhotos)];
    @endphp
    <div class="ss-story" data-source="{{ $source }}" style="--s-color:{{ $sm['color'] }};" onclick="ssOpenModal(this)">
        <template class="ss-full-content">{!! str_replace('\n', '', $story->content_tr) !!}</template>
        <template class="ss-full-title">{{ $story->title_tr }}</template>
        <template class="ss-full-gradient">{{ $gradient }}</template>
        <template class="ss-full-initials">{{ $initials }}</template>
        <template class="ss-full-source-color">{{ $sm['color'] }}</template>
        <template class="ss-full-source">{{ $source }}</template>

        <div class="ss-story-photo">
            <img src="{{ $storyPhoto }}" alt="{{ $story->title_tr }}" loading="lazy">
            <span class="ss-story-photo-badge">{{ $source }}</span>
            @if(!empty($story->summary_tr))
            <span class="ss-story-photo-city">{{ \Illuminate\Support\Str::limit($story->summary_tr, 30) }}</span>
            @endif
        </div>
        <div class="ss-story-body">
            <div class="ss-story-quote-mark">❝</div>
            <div class="ss-story-quote">{!! strip_tags(str_replace('\n', ' ', $story->content_tr)) !!}</div>
            <div class="ss-story-foot">
                <div class="ss-story-avatar" style="background:{{ $gradient }};">{{ $initials }}</div>
                <div class="ss-story-meta">
                    <div class="ss-story-name">{{ $story->title_tr }}</div>
                    <div class="ss-story-prog">{{ $story->summary_tr ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@else
    @foreach($staticStories as $i => $s)
    @php
        $sm       = $srcMeta[$s['source']] ?? ['color'=>'#6366f1'];
        $gradient = $avatarGradients[$i % count($avatarGradients)];
    @endphp
    <div class="ss-story" data-source="{{ $s['source'] }}" style="--s-color:{{ $sm['color'] }};" onclick="ssOpenModal(this)">
        <template class="ss-full-content">{{ $s['quote'] }}</template>
        <template class="ss-full-title">{{ $s['name'] }}</template>
        <template class="ss-full-gradient">{{ $gradient }}</template>
        <template class="ss-full-initials">{{ $s['initials'] }}</template>
        <template class="ss-full-source-color">{{ $sm['color'] }}</template>
        <template class="ss-full-source">{{ $s['source'] }}</template>

        <div class="ss-story-photo">
            <img src="{{ $s['photo'] }}" alt="{{ $s['name'] }}" loading="lazy">
            <span class="ss-story-photo-badge">{{ $s['source'] }}</span>
            @if(!empty($s['city']))
            <span class="ss-story-photo-city">{{ $s['city'] }}</span>
            @endif
        </div>
        <div class="ss-story-body">
            <div class="ss-story-quote-mark">❝</div>
            <div class="ss-story-quote">{{ $s['quote'] }}</div>
            <div class="ss-story-foot">
                <div class="ss-story-avatar" style="background:{{ $gradient }};">{{ $s['initials'] }}</div>
                <div class="ss-story-meta">
                    <div class="ss-story-name">{{ $s['name'] }}</div>
                    <div class="ss-story-prog">{{ $s['program'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@endif
</div>

{{-- ══════ Video Testimonials ══════ --}}
<div class="ss-section-title">🎬 Video Deneyimler</div>
<div class="ss-video-grid">
    @php
        $videosToShow = [];
        if ($heroYtId) {
            $videosToShow[] = (object)[
                'yt_id' => $heroYtId, 'thumb' => $heroThumb,
                'title' => $heroTitle, 'desc' => $heroDesc, 'featured' => true,
            ];
        }
        if (isset($cmsVideos) && $cmsVideos->isNotEmpty()) {
            foreach ($cmsVideos as $vid) {
                $vYtId = ssExtractYtId($vid->video_url);
                if (!$vYtId) continue;
                $videosToShow[] = (object)[
                    'yt_id' => $vYtId,
                    'thumb' => $vid->video_thumbnail_url ?: "https://img.youtube.com/vi/{$vYtId}/hqdefault.jpg",
                    'title' => $vid->title_tr,
                    'desc'  => $vid->summary_tr ?? '',
                    'featured' => false,
                ];
            }
        }
    @endphp

    @if(count($videosToShow) > 0)
        @foreach($videosToShow as $v)
        <div class="ss-video" onclick="var b=this;b.innerHTML='<iframe src=\'https://www.youtube.com/embed/{{ $v->yt_id }}?autoplay=1\' style=\'width:100%;height:100%;border:none;position:absolute;inset:0;\' allow=\'autoplay;encrypted-media\' allowfullscreen></iframe>';b.style.cursor='default';">
            @if($v->thumb)
            <img class="ss-video-img" src="{{ $v->thumb }}" alt="{{ $v->title }}" loading="lazy">
            @else
            <div style="position:absolute;inset:0;background:linear-gradient(135deg,#1e293b,#334155);"></div>
            @endif
            <div class="ss-video-overlay"></div>
            @if($v->featured ?? false)
            <span class="ss-video-badge">⭐ Öne Çıkan</span>
            @endif
            <div class="ss-video-play"><div class="ss-video-play-tri"></div></div>
            <div class="ss-video-caption">
                <div class="ss-video-title">{{ $v->title }}</div>
                @if($v->desc)
                <div class="ss-video-desc">{{ $v->desc }}</div>
                @endif
            </div>
        </div>
        @endforeach
    @else
        @foreach([
            ['Ahmet K. — TU München','Uni-assist sürecini ve vize deneyimini anlatıyor'],
            ['Zeynep Y. — TU Berlin','TU Berlin\'e kabul aldıktan sonra ilk günlerini paylaşıyor'],
            ['Murat S. — HAW Hamburg','Belge hazırlık sürecinde ' . $brandName . ' deneyimi'],
        ] as [$vTitle, $vDesc])
        <div class="ss-video" style="background:linear-gradient(135deg,#1e293b,#334155);">
            <div class="ss-video-overlay"></div>
            <div class="ss-video-play"><div class="ss-video-play-tri"></div></div>
            <div class="ss-video-caption">
                <div class="ss-video-title">{{ $vTitle }}</div>
                <div class="ss-video-desc">{{ $vDesc }}</div>
            </div>
        </div>
        @endforeach
    @endif
</div>

{{-- ══════ CTA ══════ --}}
<div class="ss-cta">
    <div class="ss-cta-title">{{ $ssCta['title'] }}</div>
    <div class="ss-cta-sub">{{ $ssCta['sub'] }}</div>
    <a href="{{ $ssCta['href'] }}" class="ss-cta-btn">
        {{ $ssCta['label'] }} <span>→</span>
    </a>
</div>

{{-- Story Modal --}}
<div id="ssModal" class="ss-modal-overlay" onclick="if(event.target===this)ssCloseModal()">
    <div class="ss-modal-box">
        <div class="ss-modal-head">
            <div id="ssModalAvatar" style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.12);"></div>
            <div style="flex:1;min-width:0;">
                <div id="ssModalTitle" style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);margin-bottom:3px;"></div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:#f59e0b;font-size:12px;letter-spacing:1px;">★★★★★</span>
                    <span id="ssModalSource" style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;"></span>
                </div>
            </div>
            <button class="ss-modal-close" onclick="ssCloseModal()">✕</button>
        </div>
        <div id="ssModalBody" class="ss-modal-body"></div>
    </div>
</div>

<script>
function ssOpenModal(card) {
    var content  = card.querySelector('.ss-full-content').innerHTML;
    var title    = card.querySelector('.ss-full-title').innerHTML;
    var gradient = card.querySelector('.ss-full-gradient').innerHTML.trim();
    var initials = card.querySelector('.ss-full-initials').innerHTML.trim();
    var srcColor = card.querySelector('.ss-full-source-color').innerHTML.trim();
    var src      = card.querySelector('.ss-full-source').innerHTML.trim();

    var av = document.getElementById('ssModalAvatar');
    av.textContent = initials;
    av.style.background = gradient;
    document.getElementById('ssModalTitle').innerHTML  = title;
    var srcEl = document.getElementById('ssModalSource');
    srcEl.textContent = src;
    srcEl.style.background = 'color-mix(in srgb, ' + srcColor + ' 10%, transparent)';
    srcEl.style.color = srcColor;
    document.getElementById('ssModalBody').innerHTML = content;
    document.getElementById('ssModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function ssCloseModal() {
    document.getElementById('ssModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') ssCloseModal(); });

(function(){
    var filters = document.getElementById('ssFilters');
    if (!filters) return;
    filters.addEventListener('click', function(e){
        var btn = e.target.closest('.ss-filter-chip');
        if (!btn) return;
        filters.querySelectorAll('.ss-filter-chip').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        var filter = btn.dataset.filter;
        document.querySelectorAll('.ss-story').forEach(function(card){
            if (filter === 'all' || card.dataset.source === filter) card.removeAttribute('data-source-hidden');
            else card.setAttribute('data-source-hidden', '1');
        });
    });
})();

(function(){
    var bars = document.querySelectorAll('.ss-stat-bar-fill');
    if (!bars.length || !('IntersectionObserver' in window)) {
        bars.forEach(function(b){ b.style.width = (b.dataset.fill || 80) + '%'; });
        return;
    }
    var io = new IntersectionObserver(function(entries){
        entries.forEach(function(en){
            if (en.isIntersecting) {
                en.target.style.width = (en.target.dataset.fill || 80) + '%';
                io.unobserve(en.target);
            }
        });
    }, { threshold: .3 });
    bars.forEach(function(b){ io.observe(b); });
})();
</script>

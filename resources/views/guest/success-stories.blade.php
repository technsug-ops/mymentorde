@extends('guest.layouts.app')
@section('title', 'Başarı Hikayeleri')
@section('page_title', 'Öğrenci Başarı Hikayeleri')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
.jm-minimalist .card[style*="gradient"] {
    background: #e2e5ec !important;
    color: var(--u-text, #1a1a1a) !important;
    border: 1px solid rgba(0,0,0,.10) !important;
}
.jm-minimalist .card[style*="gradient"] [style*="opacity"] { color: var(--u-muted, #666) !important; opacity: 1 !important; }

/* ── Story card clamp ── */
.ss-story-card { cursor: pointer; transition: box-shadow .18s, transform .15s; }
.ss-story-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.13); transform: translateY(-2px); }
.ss-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: var(--tx-sm); color: var(--u-text,#1e293b); line-height: 1.7;
}
.ss-read-more {
    display: inline-flex; align-items: center; gap: 4px;
    margin-top: 10px; font-size: var(--tx-xs); font-weight: 700;
    color: var(--theme-accent-guest, var(--u-brand,#2563eb));
    background: none; border: none; cursor: pointer; padding: 0;
}
.ss-read-more:hover { text-decoration: underline; }

/* ── Story modal ── */
.ss-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,.55); backdrop-filter: blur(3px);
    align-items: center; justify-content: center; padding: 20px;
}
.ss-modal-overlay.open { display: flex; }
.ss-modal-box {
    background: var(--u-card,#fff); border-radius: 18px;
    max-width: 620px; width: 100%; max-height: 88vh;
    overflow-y: auto; box-shadow: 0 24px 60px rgba(0,0,0,.22);
    animation: ssModalIn .2s ease;
}
@keyframes ssModalIn {
    from { opacity:0; transform:scale(.95) translateY(12px); }
    to   { opacity:1; transform:scale(1)   translateY(0); }
}
.ss-modal-head {
    padding: 20px 22px 16px;
    display: flex; align-items: center; gap: 14px;
    border-bottom: 1px solid var(--u-line,#e5e7eb);
    position: sticky; top: 0; background: var(--u-card,#fff); z-index: 1;
    border-radius: 18px 18px 0 0;
}
.ss-modal-close {
    margin-left: auto; width: 32px; height: 32px; border-radius: 50%;
    border: 1px solid var(--u-line,#e5e7eb); background: var(--u-bg,#f8fafc);
    cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.ss-modal-close:hover { background: var(--u-line,#e5e7eb); }
.ss-modal-body {
    padding: 20px 22px 24px;
    font-size: var(--tx-sm); color: var(--u-text,#1e293b); line-height: 1.8;
}
.ss-modal-body h3, .ss-modal-body h4 { font-weight: 700; margin: 14px 0 6px; }
.ss-modal-body p  { margin: 0 0 10px; }
.ss-modal-body blockquote {
    border-left: 3px solid var(--theme-accent-guest, var(--u-brand,#2563eb));
    margin: 12px 0; padding: 8px 14px;
    background: var(--u-bg,#f8fafc); border-radius: 0 8px 8px 0;
    font-style: italic; color: var(--u-muted,#64748b);
}
.ss-modal-body ul, .ss-modal-body ol { padding-left: 20px; margin: 8px 0; }
.ss-modal-body li { margin-bottom: 4px; }
</style>
@endpush

@section('content')

{{-- Başlık --}}
<div class="card" style="background:linear-gradient(to right,#0891b2,#16a34a);color:#fff;margin-bottom:20px;">
    <div class="card-body" style="padding:28px 28px 24px;">
        <div style="font-size:var(--tx-sm);opacity:.85;margin-bottom:6px;">{{ config('brand.name', 'MentorDE') }} Ailesi</div>
        <div style="font-size:var(--tx-2xl);font-weight:800;margin-bottom:8px;">⭐ Başarı Hikayeleri</div>
        <div style="font-size:var(--tx-sm);opacity:.85;max-width:560px;line-height:1.6;">
            Türkiye'den Almanya'ya hayallerini gerçekleştiren öğrencilerimizin gerçek deneyimleri.
        </div>
    </div>
</div>

{{-- Video Tanıtım --}}
@php
function ssExtractYtId(?string $url): ?string {
    if (!$url) return null;
    preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|watch\?v=|v\/))([\w\-]{11})/', $url, $m);
    return $m[1] ?? null;
}
$heroYtId  = ssExtractYtId($heroVideo?->video_url ?? null);
$heroThumb = $heroVideo?->video_thumbnail_url
    ?: ($heroYtId ? "https://img.youtube.com/vi/{$heroYtId}/hqdefault.jpg" : null);
$heroTitle = $heroVideo?->title_tr ?: 'Almanya hayali nasıl gerçeğe dönüşür?';
$heroDesc  = $heroVideo?->summary_tr ?: (config('brand.name', 'MentorDE') . ' ile Almanya\'ya yerleşen öğrencilerin gerçek hikayeleri — başvurudan kabule, vizeden kayıta kadar tüm süreç.');
@endphp
<div class="card" style="margin-bottom:24px;overflow:hidden;">
    <div class="card-body" style="padding:0;">
        <div class="col2" style="margin-bottom:0;gap:0;">
            {{-- Video Player --}}
            @if($heroYtId)
            <div style="position:relative;background:#0f172a;aspect-ratio:16/9;cursor:pointer;min-height:220px;overflow:hidden;"
                 id="heroVideoBox"
                 onclick="this.innerHTML='<iframe src=\'https://www.youtube.com/embed/{{ $heroYtId }}?autoplay=1\' style=\'width:100%;height:100%;border:none;position:absolute;inset:0;\' allow=\'autoplay;encrypted-media\' allowfullscreen></iframe>';this.style.cursor='default';">
                @if($heroThumb)
                <img src="{{ $heroThumb }}" alt="{{ $heroTitle }}" loading="lazy" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.75;">
                @else
                <div style="position:absolute;inset:0;background:linear-gradient(to right,#1e3a5f,#0891b2);"></div>
                @endif
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;">
                    <div style="width:68px;height:68px;border-radius:50%;background:rgba(255,255,255,.15);border:3px solid rgba(255,255,255,.6);display:flex;align-items:center;justify-content:center;transition:transform .2s;"
                         onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="width:0;height:0;border-top:14px solid transparent;border-bottom:14px solid transparent;border-left:22px solid #fff;margin-left:4px;"></div>
                    </div>
                </div>
            </div>
            @else
            <div style="position:relative;background:linear-gradient(to right,#1e3a5f,#0891b2);aspect-ratio:16/9;min-height:220px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:10px;">
                <div style="font-size:40px;">🎬</div>
                <div style="color:rgba(255,255,255,.7);font-size:var(--tx-xs);">Video Marketing Admin panelinden yüklenebilir</div>
            </div>
            @endif
            {{-- Video Açıklaması --}}
            <div style="padding:28px 28px 24px;display:flex;flex-direction:column;justify-content:center;gap:12px;">
                <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-brand,#2563eb);text-transform:uppercase;letter-spacing:.06em;">Tanıtım Filmi</div>
                <div style="font-weight:800;font-size:var(--tx-lg);line-height:1.3;color:var(--u-text);">{{ $heroTitle }}</div>
                <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;">{{ $heroDesc }}</div>
                <div style="display:flex;gap:16px;margin-top:4px;">
                    <div style="display:flex;align-items:center;gap:6px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                        <span style="color:#f59e0b;">★★★★★</span> 4.9/5 memnuniyet
                    </div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">80+ öğrenci</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- İstatistikler --}}
<div class="col3" style="margin-bottom:28px;">
    @foreach([['🎓','80+','Öğrenci Almanya\'da'],['🏛','50+','Farklı Üniversite'],['⭐','%95','Memnuniyet Oranı']] as [$icon,$val,$lbl])
    <div class="card" style="text-align:center;">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:8px;">{{ $icon }}</div>
            <div style="font-size:var(--tx-2xl);font-weight:800;color:var(--u-brand,#2563eb);line-height:1;">{{ $val }}</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin-top:4px;">{{ $lbl }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── CMS Kaynak Renk Haritası ─────────────────────────────────────────── --}}
@php
$brandName = config('brand.name', 'MentorDE');
$srcMeta = [
    'Google'     => ['color'=>'#4285F4','bg'=>'#e8f0fe'],
    'Trustpilot' => ['color'=>'#00b67a','bg'=>'#e5f7f1'],
    $brandName   => ['color'=>'#e11d48','bg'=>'#fff1f2'],
];
$avatarGradients = [
    'linear-gradient(to right,#7c3aed,#2563eb)',
    'linear-gradient(to right,#0891b2,#16a34a)',
    'linear-gradient(to right,#dc2626,#d97706)',
    'linear-gradient(to right,#7c3aed,#2563eb)',
    'linear-gradient(to right,#0891b2,#16a34a)',
    'linear-gradient(to right,#dc2626,#d97706)',
];

// --- CMS'den gelen hikayeler ---
$hasCms = isset($cmsStories) && $cmsStories->isNotEmpty();

// --- Statik fallback ---
$staticStories = [
    ['initials'=>'AK','name'=>'Ahmet K.','program'=>'TU München — Mak. Müh.','source'=>'Google',
     'quote'=>'"' . $brandName . ' olmadan bu süreci tek başıma yönetemezdim. Uni-assist başvurusundan vize sürecine kadar her adımda yanımda oldular. Şu an TU München\'de 2. yılımdayım."'],
    ['initials'=>'ZY','name'=>'Zeynep Y.','program'=>'TU Berlin — Bilgisayar Müh.','source'=>'Trustpilot',
     'quote'=>'"Belge sürecinde çok zorlandım. Apostil ve yeminli tercüme için nereye gideceğimi bilmiyordum. Danışmanım adım adım rehberlik etti. TU Berlin\'e kabul aldım!"'],
    ['initials'=>'MS','name'=>'Murat S.','program'=>'HAW Hamburg — İşletme','source'=>$brandName,
     'quote'=>'"Almanca B2 sınavına hazırlanırken hem çalışıyor hem de başvuru sürecini yürütmek çok zordu. ' . $brandName . '\'nin sistematik takibi sayesinde hiçbir belge eksik kalmadı."'],
    ['initials'=>'EA','name'=>'Elif A.','program'=>'Uni Stuttgart — Elektrik Müh.','source'=>'Google',
     'quote'=>'"Türkiye\'de lise mezunuydum. Studienkolleg süreci çok karmaşık görünüyordu. Danışmanım doğrudan üniversiteye geçiş için alternatif bir yol gösterdi. Harika!"'],
    ['initials'=>'KD','name'=>'Kemal D.','program'=>'Goethe Uni — Finans','source'=>'Trustpilot',
     'quote'=>'"Goethe Uni\'ye kabul aldığımda inanamadım. Motivasyon mektubum için AI asistan ve danışman birlikte çok yardımcı oldu. Keşke daha önce başlasaydım."'],
    ['initials'=>'NT','name'=>'Neslihan T.','program'=>'TH Köln — Medya Tasarımı','source'=>$brandName,
     'quote'=>'"Portfolyo hazırlığı ve yetenek sınavına danışmanımla birlikte hazırlandım. TH Köln tasarım programına kabul — çok mutluyum!"'],
];
@endphp

<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:16px;color:var(--u-text);">
    Öğrenci Deneyimleri
    @if($hasCms)
        <span style="font-size:var(--tx-xs);font-weight:500;color:var(--u-muted,#94a3b8);margin-left:8px;">— Marketing Admin panelinden yönetilir</span>
    @endif
</div>

<div class="col3" style="margin-bottom:28px;">

@if($hasCms)
    {{-- ── CMS Kayıtlı Hikayeler ──────────────────────────────────────────── --}}
    @foreach($cmsStories as $i => $story)
    @php
        $tags      = is_array($story->tags) ? $story->tags : [];
        $source    = $tags[0] ?? $brandName;
        $sm        = $srcMeta[$source] ?? ['color'=>'#6366f1','bg'=>'#eef2ff'];
        $initials  = $story->cover_image_alt ?: strtoupper(substr($story->title_tr ?? 'M', 0, 2));
        $gradient  = $avatarGradients[$i % count($avatarGradients)];
        $extraTags = array_slice($tags, 1); // kaynak dışındaki etiketler
    @endphp
    <div class="ss-story-card" style="background:var(--u-card,#fff);border:1.5px solid #f43f5e;border-radius:12px;display:flex;flex-direction:column;"
         onclick="ssOpenModal(this)">
        {{-- Gizli full content --}}
        <template class="ss-full-content">{!! str_replace('\n', '', $story->content_tr) !!}</template>
        <template class="ss-full-title">{{ $story->title_tr }}</template>
        <template class="ss-full-gradient">{{ $gradient }}</template>
        <template class="ss-full-initials">{{ $initials }}</template>
        <template class="ss-full-source-bg">{{ $sm['bg'] }}</template>
        <template class="ss-full-source-color">{{ $sm['color'] }}</template>
        <template class="ss-full-source">{{ $source }}</template>

        <div style="padding:18px 18px 14px;display:flex;align-items:center;gap:14px;">
            <div style="width:52px;height:52px;border-radius:50%;background:{{ $gradient }};
                        display:flex;align-items:center;justify-content:center;
                        color:#fff;font-weight:800;font-size:15px;flex-shrink:0;
                        box-shadow:0 2px 8px rgba(0,0,0,.12);">
                {{ $initials }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);margin-bottom:3px;">{{ $story->title_tr }}</div>
                <div style="color:#f59e0b;font-size:var(--tx-sm);letter-spacing:1px;margin-bottom:4px;">★★★★★</div>
                <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;
                             background:{{ $sm['bg'] }};color:{{ $sm['color'] }};
                             font-size:11px;font-weight:700;">{{ $source }}</span>
            </div>
        </div>
        <div style="border-top:1px solid #fecdd3;margin:0 18px;"></div>
        <div style="padding:14px 18px 18px;flex:1;">
            <div class="ss-clamp">{!! str_replace('\n', '', $story->content_tr) !!}</div>
            @if($story->summary_tr)
            <div style="margin-top:8px;font-size:var(--tx-xs);color:var(--u-muted,#94a3b8);">{{ $story->summary_tr }}</div>
            @endif
            <button class="ss-read-more" onclick="event.stopPropagation();ssOpenModal(this.closest('.ss-story-card'))">Devamını Gör →</button>
        </div>
    </div>
    @endforeach

@else
    {{-- ── Statik Fallback ────────────────────────────────────────────────── --}}
    @foreach($staticStories as $i => $s)
    @php
        $sm       = $srcMeta[$s['source']] ?? ['color'=>'#6366f1','bg'=>'#eef2ff'];
        $gradient = $avatarGradients[$i % count($avatarGradients)];
    @endphp
    <div class="ss-story-card" style="background:var(--u-card,#fff);border:1.5px solid #f43f5e;border-radius:12px;display:flex;flex-direction:column;"
         onclick="ssOpenModal(this)">
        <template class="ss-full-content">{{ $s['quote'] }}</template>
        <template class="ss-full-title">{{ $s['name'] }}</template>
        <template class="ss-full-gradient">{{ $gradient }}</template>
        <template class="ss-full-initials">{{ $s['initials'] }}</template>
        <template class="ss-full-source-bg">{{ $sm['bg'] }}</template>
        <template class="ss-full-source-color">{{ $sm['color'] }}</template>
        <template class="ss-full-source">{{ $s['source'] }}</template>

        <div style="padding:18px 18px 14px;display:flex;align-items:center;gap:14px;">
            <div style="width:52px;height:52px;border-radius:50%;background:{{ $gradient }};
                        display:flex;align-items:center;justify-content:center;
                        color:#fff;font-weight:800;font-size:15px;flex-shrink:0;
                        box-shadow:0 2px 8px rgba(0,0,0,.12);">
                {{ $s['initials'] }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);margin-bottom:3px;">{{ $s['name'] }}</div>
                <div style="color:#f59e0b;font-size:var(--tx-sm);letter-spacing:1px;margin-bottom:4px;">★★★★★</div>
                <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;
                             background:{{ $sm['bg'] }};color:{{ $sm['color'] }};
                             font-size:11px;font-weight:700;">{{ $s['source'] }}</span>
            </div>
        </div>
        <div style="border-top:1px solid #fecdd3;margin:0 18px;"></div>
        <div style="padding:14px 18px 18px;flex:1;">
            <div class="ss-clamp">{{ $s['quote'] }}</div>
            <div style="margin-top:8px;font-size:var(--tx-xs);color:var(--u-muted,#94a3b8);">{{ $s['program'] }}</div>
            <button class="ss-read-more" onclick="event.stopPropagation();ssOpenModal(this.closest('.ss-story-card'))">Devamını Gör →</button>
        </div>
    </div>
    @endforeach
@endif

</div>

{{-- Video Testimonials --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">🎬 Video Deneyimler</div>
<div class="col3" style="margin-bottom:28px;">
@if(isset($cmsVideos) && $cmsVideos->isNotEmpty())
    @foreach($cmsVideos as $vid)
    @php
        $vYtId  = ssExtractYtId($vid->video_url);
        $vThumb = $vid->video_thumbnail_url
            ?: ($vYtId ? "https://img.youtube.com/vi/{$vYtId}/hqdefault.jpg" : null);
    @endphp
    @if($vYtId)
    <div class="card" style="overflow:hidden;">
        <div style="position:relative;aspect-ratio:16/9;background:#0f172a;cursor:pointer;overflow:hidden;"
             onclick="var b=this;b.innerHTML='<iframe src=\'https://www.youtube.com/embed/{{ $vYtId }}?autoplay=1\' style=\'width:100%;height:100%;border:none;position:absolute;inset:0;\' allow=\'autoplay;encrypted-media\' allowfullscreen></iframe>';b.style.cursor='default';">
            @if($vThumb)
            <img src="{{ $vThumb }}" alt="{{ $vid->title_tr }}" loading="lazy" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.75;">
            @else
            <div style="position:absolute;inset:0;background:linear-gradient(to right,#1e293b,#334155);"></div>
            @endif
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
                <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;"
                     onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <div style="width:0;height:0;border-top:10px solid transparent;border-bottom:10px solid transparent;border-left:17px solid #fff;margin-left:3px;"></div>
                </div>
            </div>
        </div>
        <div class="card-body" style="padding:14px 16px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:4px;">{{ $vid->title_tr }}</div>
            @if($vid->summary_tr)
            <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;">{{ $vid->summary_tr }}</div>
            @endif
        </div>
    </div>
    @endif
    @endforeach
@else
    @foreach([
        ['Ahmet K. — TU München','Uni-assist sürecini ve vize deneyimini anlatıyor'],
        ['Zeynep Y. — TU Berlin','TU Berlin\'e kabul aldıktan sonra ilk günlerini paylaşıyor'],
        ['Murat S. — HAW Hamburg','Belge hazırlık sürecinde ' . $brandName . ' deneyimi'],
    ] as [$vTitle, $vDesc])
    <div class="card" style="overflow:hidden;">
        <div style="aspect-ratio:16/9;background:linear-gradient(to right,#1e293b,#334155);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:10px;">
            <div style="font-size:32px;">🎬</div>
            <div style="color:rgba(255,255,255,.6);font-size:var(--tx-xs);text-align:center;padding:0 16px;">Video Marketing Admin panelinden yüklenebilir</div>
        </div>
        <div class="card-body" style="padding:14px 16px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:4px;">{{ $vTitle }}</div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;">{{ $vDesc }}</div>
        </div>
    </div>
    @endforeach
@endif
</div>

{{-- CTA --}}
<div class="card" style="background:linear-gradient(135deg,#eff6ff,#f0fdf4);border:1.5px solid var(--u-brand,#2563eb);margin-bottom:20px;">
    <div class="card-body" style="padding:24px;text-align:center;">
        <div style="font-size:var(--tx-xl);margin-bottom:8px;">🚀</div>
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:6px;">Sen de bu ailenin bir parçası ol!</div>
        <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin-bottom:16px;">Danışmanın seni bekliyor. Almanya yolculuğuna bugün başla.</div>
        <a href="{{ route('guest.registration.form') }}" class="btn ok" style="margin-right:8px;">Başvurumu Tamamla →</a>
        <a href="{{ route('guest.dashboard') }}" class="btn alt">Dashboard'a Dön</a>
    </div>
</div>

{{-- ── Story Modal ── --}}
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
    var srcBg    = card.querySelector('.ss-full-source-bg').innerHTML.trim();
    var srcColor = card.querySelector('.ss-full-source-color').innerHTML.trim();
    var src      = card.querySelector('.ss-full-source').innerHTML.trim();

    document.getElementById('ssModalAvatar').textContent  = initials;
    document.getElementById('ssModalAvatar').style.background = gradient;
    document.getElementById('ssModalTitle').innerHTML  = title;
    document.getElementById('ssModalSource').textContent = src;
    document.getElementById('ssModalSource').style.background = srcBg;
    document.getElementById('ssModalSource').style.color = srcColor;
    document.getElementById('ssModalBody').innerHTML = content;
    document.getElementById('ssModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function ssCloseModal() {
    document.getElementById('ssModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') ssCloseModal(); });
</script>

@endsection

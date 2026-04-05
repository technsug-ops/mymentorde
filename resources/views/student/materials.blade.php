@extends('student.layouts.app')

@section('title', 'Materyaller')
@section('page_title', 'Materyaller')

@push('head')
<style>
/* ── mat-* Materials ── */

.mat-header {
    display: flex; align-items: center; gap: 14px;
    background: linear-gradient(to right, #6d28d9, #7c3aed);
    border-radius: 14px; padding: 14px 18px; margin-bottom: 20px; color: #fff;
}
.mat-header-icon { font-size: 24px; }
.mat-header-title { font-size: 16px; font-weight: 800; }
.mat-header-sub   { font-size: 12px; opacity: .75; }
.mat-count-pill {
    margin-left: auto; background: rgba(255,255,255,.2);
    border-radius: 999px; padding: 4px 12px; font-size: 12px; font-weight: 700;
}

.mat-progress {
    display: flex; align-items: center; gap: 10px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 10px; padding: 10px 14px; margin-bottom: 16px;
}
.mat-progress-bar { flex: 1; height: 5px; background: var(--u-line); border-radius: 3px; overflow: hidden; }
.mat-progress-fill { height: 100%; background: #7c3aed; border-radius: 3px; }
.mat-progress-lbl { font-size: 12px; font-weight: 700; color: var(--u-text); white-space: nowrap; }

/* === VIEWS === */
#mat-grid-view   { display: block; }
#mat-detail-view { display: none; }

/* Section heading */
.mat-section-head {
    display: flex; align-items: center; gap: 10px; margin-bottom: 12px;
}
.mat-section-head span:first-child { font-size: 13px; font-weight: 800; color: var(--u-text); }

/* ── VIDEO GRID ── */
.mat-video-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px; margin-bottom: 28px;
}
@media(max-width:900px){ .mat-video-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:580px){ .mat-video-grid { grid-template-columns: 1fr; } }

.mat-video-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; overflow: hidden; cursor: pointer;
    transition: box-shadow .15s, border-color .15s;
    display: flex; flex-direction: column;
}
.mat-video-card:hover { border-color: #7c3aed; box-shadow: 0 4px 18px rgba(124,58,237,.12); }

.mat-video-thumb {
    position: relative; aspect-ratio: 16/9;
    background: #1a1a2e; overflow: hidden; flex-shrink: 0;
}
.mat-video-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform .2s; }
.mat-video-card:hover .mat-video-thumb img { transform: scale(1.03); }
.mat-video-play {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,.25);
}
.mat-video-play-btn {
    width: 48px; height: 48px; border-radius: 50%;
    background: rgba(255,255,255,.92); display: flex; align-items: center; justify-content: center;
    font-size: 18px; transition: transform .15s;
}
.mat-video-card:hover .mat-video-play-btn { transform: scale(1.1); }
.mat-video-type-badge {
    position: absolute; top: 8px; left: 8px;
    padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 700;
    background: rgba(124,58,237,.85); color: #fff;
}

.mat-video-body { padding: 10px 12px 12px; flex: 1; display: flex; flex-direction: column; }
.mat-card-title {
    font-size: 13px; font-weight: 700; color: var(--u-text);
    line-height: 1.4; margin-bottom: 8px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.mat-card-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: auto; }
.mat-card-cat {
    font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 5px;
    background: rgba(124,58,237,.08); color: #7c3aed; border: 1px solid rgba(124,58,237,.15);
}
.mat-card-date { font-size: 11px; color: var(--u-muted); }
.mat-card-new  { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 5px; background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
.mat-card-read { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 5px; background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; }

/* ── WRITTEN LIST (compact) ── */
.mat-written-list {
    display: flex; flex-direction: column; gap: 8px; margin-bottom: 28px;
}
.mat-written-item {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 10px; padding: 12px 14px;
    display: flex; align-items: center; gap: 14px;
    cursor: pointer; transition: border-color .15s, box-shadow .15s;
}
.mat-written-item:hover { border-color: #7c3aed; box-shadow: 0 2px 10px rgba(124,58,237,.09); }
.mat-written-icon {
    width: 44px; height: 44px; flex-shrink: 0;
    border-radius: 10px; background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    display: flex; align-items: center; justify-content: center; font-size: 22px;
}
.mat-written-info { flex: 1; min-width: 0; }
.mat-written-title {
    font-size: 13px; font-weight: 700; color: var(--u-text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-bottom: 4px;
}
.mat-written-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.mat-written-arr { color: var(--u-muted); font-size: 16px; flex-shrink: 0; }

/* ── DETAIL VIEW ── */
.mat-detail-back {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 600; color: var(--u-muted);
    cursor: pointer; padding: 6px 0; margin-bottom: 14px;
    background: none; border: none;
}
.mat-detail-back:hover { color: #7c3aed; }

.mat-detail-layout {
    display: grid; grid-template-columns: 1fr 400px; gap: 20px; align-items: start;
}
@media(max-width:960px) { .mat-detail-layout { grid-template-columns: 1fr; } }

.mat-detail-cat {
    display: inline-block; font-size: 11px; font-weight: 700;
    padding: 3px 10px; border-radius: 6px; margin-bottom: 10px;
    background: rgba(124,58,237,.08); color: #7c3aed; border: 1px solid rgba(124,58,237,.15);
}
.mat-detail-title {
    font-size: 20px; font-weight: 800; color: var(--u-text);
    line-height: 1.3; margin-bottom: 10px;
}
.mat-detail-meta { display: flex; gap: 8px; margin-bottom: 16px; }
.mat-detail-body {
    font-size: 13px; color: var(--u-text); line-height: 1.8;
    margin-bottom: 16px;
    padding: 16px; background: var(--u-bg); border: 1px solid var(--u-line); border-radius: 10px;
}
.mat-detail-body p { margin: 0 0 .75em; }
.mat-detail-body p:last-child { margin-bottom: 0; }
.mat-detail-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
.mat-detail-tag {
    padding: 3px 10px; border-radius: 999px; font-size: 11px;
    background: var(--u-bg); border: 1px solid var(--u-line); color: var(--u-muted);
}
.mat-detail-actions { display: flex; gap: 10px; flex-wrap: wrap; }

/* Right panel */
.mat-detail-player {
    position: sticky; top: 20px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; overflow: hidden;
}
.mat-player-video { aspect-ratio: 16/9; background: #000; }
.mat-player-video iframe { width: 100%; height: 100%; display: block; }

/* PDF embed */
.mat-player-pdf { width: 100%; height: 520px; }
.mat-player-pdf iframe { width: 100%; height: 100%; display: block; border: none; }

/* No-media placeholder */
.mat-player-none {
    padding: 32px 20px; display: flex; align-items: center; justify-content: center;
    flex-direction: column; gap: 8px;
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
}
.mat-player-none-icon { font-size: 48px; }
.mat-player-none-lbl { font-size: 13px; color: #7c3aed; font-weight: 700; }

.mat-player-info { padding: 14px; }
.mat-player-title { font-size: 13px; font-weight: 700; color: var(--u-text); margin-bottom: 10px; }
.mat-source-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 10px; border-radius: 8px;
    background: #7c3aed; color: #fff; font-size: 13px; font-weight: 700;
    text-decoration: none; transition: background .15s;
}
.mat-source-btn:hover { background: #6d28d9; color: #fff; }
.mat-source-btn.yt   { background: #dc2626; }
.mat-source-btn.yt:hover { background: #b91c1c; }

/* Empty */
.mat-empty {
    text-align: center; padding: 48px 20px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; color: var(--u-muted);
}

/* ── VIDEO MODAL ── */
.mat-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,.75); align-items: center; justify-content: center;
    padding: 20px;
}
.mat-modal-overlay.open { display: flex; }
.mat-modal-box {
    background: #1a1a2e; border-radius: 14px; overflow: hidden;
    width: 100%; max-width: 820px;
    box-shadow: 0 24px 80px rgba(0,0,0,.6);
    display: flex; flex-direction: column;
}
.mat-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; background: #12121e;
}
.mat-modal-title { font-size: 14px; font-weight: 700; color: #fff; max-width: 70%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mat-modal-close {
    width: 32px; height: 32px; border-radius: 50%; border: none;
    background: rgba(255,255,255,.15); color: #fff; font-size: 18px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.mat-modal-close:hover { background: rgba(255,255,255,.3); }
.mat-modal-player { aspect-ratio: 16/9; background: #000; }
.mat-modal-player iframe { width: 100%; height: 100%; display: block; }
.mat-modal-footer {
    padding: 12px 18px; background: #12121e;
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
}
.mat-modal-footer-lbl { font-size: 12px; color: rgba(255,255,255,.5); }
.mat-modal-yt-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 8px; font-size: 12px; font-weight: 700;
    background: #dc2626; color: #fff; text-decoration: none; transition: background .15s;
}
.mat-modal-yt-btn:hover { background: #b91c1c; color: #fff; }
.mat-modal-detail-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 8px; font-size: 12px; font-weight: 700;
    background: rgba(255,255,255,.12); color: #fff; border: none; cursor: pointer;
    transition: background .15s;
}
.mat-modal-detail-btn:hover { background: rgba(255,255,255,.22); }
</style>
@endpush

@section('content')
@php
    $mats      = $materials ?? collect();
    $readIds   = collect($readMaterialIds ?? []);
    $cats      = $categories ?? collect();
    $activeCat = $activeCat ?? '';
    $total   = $mats->count();
    $readCount = $mats->filter(fn($m) => $readIds->contains((int)$m->id))->count();
    $pct = $total > 0 ? round($readCount / $total * 100) : 0;

    $getYouTubeId = function($body) {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', (string)$body, $m)) {
            return $m[1];
        }
        return null;
    };

    // Helper: normalise Google Drive link → embeddable URL
    $toPdfEmbedUrl = function($url) {
        if (!$url) return null;
        // Google Drive: /file/d/{ID}/view → /file/d/{ID}/preview
        if (preg_match('#drive\.google\.com/file/d/([^/?]+)#', $url, $m)) {
            return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
        }
        // Direct .pdf link — use Google Docs Viewer
        if (str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.pdf')) {
            return 'https://docs.google.com/viewer?url=' . urlencode($url) . '&embedded=true';
        }
        return null;
    };

    // is_video = has YouTube in body OR media_type=video
    $isVideo = fn($m) =>
        ($m->media_type === 'video') ||
        $getYouTubeId($m->body_tr ?: $m->body_en ?: $m->body_de ?: '') ||
        ($m->source_url && $getYouTubeId($m->source_url));

    $videoMats   = $mats->filter(fn($m) => $isVideo($m));
    $writtenMats = $mats->reject(fn($m) => $isVideo($m));
@endphp

{{-- Header --}}
<div class="mat-header">
    <div class="mat-header-icon">📚</div>
    <div>
        <div class="mat-header-title">Materyaller</div>
        <div class="mat-header-sub">Danışmanınız tarafından paylaşılan rehberler ve videolar</div>
    </div>
    @if($total > 0)
    <div class="mat-count-pill">{{ $total }} materyal</div>
    @endif
</div>

@if($total > 0)
<div class="mat-progress">
    <span class="mat-progress-lbl">İlerleme</span>
    <div class="mat-progress-bar"><div class="mat-progress-fill" style="width:{{ $pct }}%"></div></div>
    <span class="mat-progress-lbl">{{ $readCount }}/{{ $total }}</span>
    <span class="badge {{ $pct === 100 ? 'ok' : ($pct > 0 ? 'warn' : 'pending') }}">{{ $pct }}%</span>
</div>
@endif

@if($cats->isNotEmpty())
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;align-items:center;">
    <a href="{{ route('student.materials') }}"
       style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid {{ $activeCat==='' ? '#7c3aed' : 'var(--u-line,#e2e8f0)' }};background:{{ $activeCat==='' ? '#7c3aed' : 'transparent' }};color:{{ $activeCat==='' ? '#fff' : 'var(--u-muted,#64748b)' }};transition:all .15s;">
        Tümü
    </a>
    @foreach($cats as $cat)
    <a href="{{ route('student.materials', ['cat' => $cat]) }}"
       style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid {{ $activeCat===$cat ? '#7c3aed' : 'var(--u-line,#e2e8f0)' }};background:{{ $activeCat===$cat ? '#7c3aed' : 'transparent' }};color:{{ $activeCat===$cat ? '#fff' : 'var(--u-muted,#64748b)' }};transition:all .15s;">
        {{ $cat }}
    </a>
    @endforeach
</div>
@endif

{{-- === GRID VIEW === --}}
<div id="mat-grid-view">
    @if($mats->isEmpty())
    <div class="mat-empty">
        <div style="font-size:40px;margin-bottom:10px;">📚</div>
        <div style="font-size:var(--tx-base);font-weight:700;margin-bottom:6px;">Henüz materyal yok</div>
        <div style="font-size:var(--tx-sm);">Danışmanınız materyal paylaştığında burada görünecek.</div>
    </div>
    @else

    {{-- ── Video Materyaller ── --}}
    @if($videoMats->isNotEmpty())
    <div class="mat-section-head">
        <span>🎬 Video Materyaller</span>
        <span class="badge warn">{{ $videoMats->count() }}</span>
        <hr style="flex:1;border:none;border-top:1px solid var(--u-line);">
    </div>
    <div class="mat-video-grid">
        @foreach($videoMats as $m)
        @php
            $title = $m->title_tr ?: ($m->title_en ?: ($m->title_de ?: 'Başlık yok'));
            $body  = $m->body_tr  ?: ($m->body_en  ?: ($m->body_de  ?: ''));
            $read  = $readIds->contains((int)$m->id);
            $ytId  = $getYouTubeId($m->source_url ?: $body);
            $tags  = is_array($m->tags) ? $m->tags : [];
            $displayBody = preg_replace('/(?:📺 )?Video:\s*https?:\/\/\S+\n?/', '', (string)$body);
            $displayBody = trim($displayBody);
            $readUrl = route('student.materials.read', $m->id);
            $ytUrl = $ytId ? 'https://www.youtube.com/watch?v='.$ytId : ($m->source_url ?? '');
        @endphp
        <div class="mat-video-card">
            <div class="mat-video-thumb"
                 onclick="matOpenModal({{ json_encode($ytId) }}, {{ json_encode($title) }}, {{ json_encode($ytUrl) }}, {{ $m->id }}, {{ json_encode($displayBody) }}, {{ json_encode($m->category ?? '') }}, {{ json_encode($tags) }}, {{ json_encode($readUrl) }}, {{ $read ? 'true' : 'false' }})"
                 style="cursor:pointer;">
                <img src="https://img.youtube.com/vi/{{ $ytId }}/hqdefault.jpg" alt="thumbnail" loading="lazy">
                <div class="mat-video-play">
                    <div class="mat-video-play-btn">▶</div>
                </div>
                <span class="mat-video-type-badge">🎬 Video</span>
            </div>
            <div class="mat-video-body"
                 onclick="matOpenDetail({{ $m->id }}, {{ json_encode($title) }}, {{ json_encode($displayBody) }}, {{ json_encode($m->category ?? '') }}, {{ json_encode($tags) }}, {{ json_encode($ytId) }}, {{ json_encode($ytUrl) }}, {{ json_encode($readUrl) }}, {{ $read ? 'true' : 'false' }}, null)"
                 style="cursor:pointer;">
                <div class="mat-card-title">{{ $title }}</div>
                <div class="mat-card-meta">
                    @if($m->category)<span class="mat-card-cat">{{ $m->category }}</span>@endif
                    <span class="mat-card-date">{{ $m->updated_at->format('d.m.Y') }}</span>
                    <span class="{{ $read ? 'mat-card-read' : 'mat-card-new' }}">{{ $read ? '✓ Okundu' : 'Yeni' }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Yazılı Materyaller (compact list) ── --}}
    @if($writtenMats->isNotEmpty())
    <div class="mat-section-head">
        <span>📄 Yazılı Materyaller</span>
        <span class="badge info">{{ $writtenMats->count() }}</span>
        <hr style="flex:1;border:none;border-top:1px solid var(--u-line);">
    </div>
    <div class="mat-written-list">
        @foreach($writtenMats as $m)
        @php
            $title = $m->title_tr ?: ($m->title_en ?: ($m->title_de ?: 'Başlık yok'));
            $body  = $m->body_tr  ?: ($m->body_en  ?: ($m->body_de  ?: ''));
            $read  = $readIds->contains((int)$m->id);
            $tags  = is_array($m->tags) ? $m->tags : [];
            $displayBody = trim((string)$body);
            $readUrl = route('student.materials.read', $m->id);
            $srcUrl  = $m->source_url ?? '';
            // Local uploaded PDF takes priority
            $fileInlineUrl = $m->file_path ? route('student.materials.file', $m->id) : null;
            $fileDownloadUrl = $m->file_path ? route('student.materials.file', $m->id).'?download=1' : null;
            // Fallback: Google Drive / external PDF
            $pdfEmbed = $fileInlineUrl ?? $toPdfEmbedUrl($m->source_url ?? '');
            $isPdf = (bool)$pdfEmbed || ($m->media_type === 'pdf');
            $icon = $isPdf ? '📑' : '📄';
        @endphp
        <div class="mat-written-item"
             onclick="matOpenDetail({{ $m->id }}, {{ json_encode($title) }}, {{ json_encode($displayBody) }}, {{ json_encode($m->category ?? '') }}, {{ json_encode($tags) }}, null, {{ json_encode($srcUrl) }}, {{ json_encode($readUrl) }}, {{ $read ? 'true' : 'false' }}, {{ json_encode($pdfEmbed) }}, {{ json_encode($fileDownloadUrl) }})">
            <div class="mat-written-icon">{{ $icon }}</div>
            <div class="mat-written-info">
                <div class="mat-written-title">{{ $title }}</div>
                <div class="mat-written-meta">
                    @if($m->category)<span class="mat-card-cat">{{ $m->category }}</span>@endif
                    <span class="mat-card-date">{{ $m->updated_at->format('d.m.Y') }}</span>
                    <span class="{{ $read ? 'mat-card-read' : 'mat-card-new' }}">{{ $read ? '✓ Okundu' : 'Yeni' }}</span>
                    @if($isPdf)<span style="font-size:var(--tx-xs);font-weight:700;padding:2px 7px;border-radius:5px;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;">PDF</span>@endif
                </div>
            </div>
            <div class="mat-written-arr">›</div>
        </div>
        @endforeach
    </div>
    @endif

    @endif
</div>

{{-- === VIDEO MODAL === --}}
<div class="mat-modal-overlay" id="mat-video-modal" onclick="matModalClose(event)">
    <div class="mat-modal-box" onclick="event.stopPropagation()">
        <div class="mat-modal-header">
            <span class="mat-modal-title" id="mat-modal-title"></span>
            <button class="mat-modal-close" onclick="matCloseModal()">✕</button>
        </div>
        <div class="mat-modal-player" id="mat-modal-player"></div>
        <div class="mat-modal-footer">
            <span class="mat-modal-footer-lbl">Tam ekran için YouTube'a gidin</span>
            <div style="display:flex;gap:8px;">
                <button class="mat-modal-detail-btn" id="mat-modal-detail-btn">📄 Detaylar</button>
                <a class="mat-modal-yt-btn" id="mat-modal-yt-link" href="#" target="_blank" rel="noopener">▶ YouTube'da İzle</a>
            </div>
        </div>
    </div>
</div>

{{-- === DETAIL VIEW === --}}
<div id="mat-detail-view">
    <button class="mat-detail-back" onclick="matCloseDetail()">← Materyallere Dön</button>

    <div class="mat-detail-layout">
        {{-- Left: info --}}
        <div>
            <span class="mat-detail-cat" id="det-cat"></span>
            <h2 class="mat-detail-title" id="det-title"></h2>
            <div class="mat-detail-meta" id="det-meta"></div>
            <div class="mat-detail-body" id="det-body"></div>
            <div class="mat-detail-tags" id="det-tags"></div>
            <div class="mat-detail-actions" id="det-actions"></div>
        </div>
        {{-- Right: player --}}
        <div class="mat-detail-player">
            <div id="det-player"></div>
            <div class="mat-player-info">
                <div class="mat-player-title" id="det-player-title"></div>
                <div id="det-source-btn"></div>
            </div>
        </div>
    </div>
</div>

<script>
var _matDetailArgs = null;

function matOpenModal(ytId, title, ytUrl, id, body, cat, tags, readUrl, isRead) {
    _matDetailArgs = [id, title, body, cat, tags, ytId, ytUrl, readUrl, isRead, null];
    document.getElementById('mat-modal-title').textContent = title;
    document.getElementById('mat-modal-player').innerHTML =
        '<iframe src="https://www.youtube.com/embed/' + ytId + '?rel=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    document.getElementById('mat-modal-yt-link').href = ytUrl;
    document.getElementById('mat-modal-detail-btn').onclick = function() {
        matCloseModal();
        matOpenDetail.apply(null, _matDetailArgs);
    };
    document.getElementById('mat-video-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function matCloseModal() {
    document.getElementById('mat-video-modal').classList.remove('open');
    document.getElementById('mat-modal-player').innerHTML = '';
    document.body.style.overflow = '';
}

function matModalClose(e) {
    if (e.target === document.getElementById('mat-video-modal')) matCloseModal();
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') matCloseModal();
});

// pdfEmbed = local file serve URL or Google Drive preview URL
// downloadUrl = local file download URL (null if not a local file)
function matOpenDetail(id, title, body, cat, tags, ytId, srcUrl, readUrl, isRead, pdfEmbed, downloadUrl) {
    document.getElementById('mat-grid-view').style.display = 'none';
    document.getElementById('mat-detail-view').style.display = 'block';
    window.scrollTo(0, 0);

    const catEl = document.getElementById('det-cat');
    catEl.textContent = cat || '';
    catEl.style.display = cat ? '' : 'none';

    document.getElementById('det-title').textContent = title;
    document.getElementById('det-player-title').textContent = title;

    const metaEl = document.getElementById('det-meta');
    metaEl.innerHTML = isRead
        ? '<span class="badge ok">✓ Okundu</span>'
        : '<span class="badge warn">Yeni</span>';

    // Body — render paragraphs
    const bodyEl = document.getElementById('det-body');
    if (body) {
        bodyEl.innerHTML = body.split(/\n\n+/).map(function(p) {
            return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
        }).join('');
        bodyEl.style.display = '';
    } else {
        bodyEl.style.display = 'none';
    }

    const tagsEl = document.getElementById('det-tags');
    tagsEl.innerHTML = (tags || []).map(function(t) {
        return '<span class="mat-detail-tag">#' + t + '</span>';
    }).join('');

    // Player
    const playerEl = document.getElementById('det-player');
    const srcEl = document.getElementById('det-source-btn');

    if (ytId) {
        playerEl.className = 'mat-player-video';
        playerEl.innerHTML = '<iframe src="https://www.youtube.com/embed/' + ytId + '?rel=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        srcEl.innerHTML = '<a href="' + srcUrl + '" target="_blank" rel="noopener" class="mat-source-btn yt">▶ YouTube\'da İzle</a>';
    } else if (pdfEmbed) {
        playerEl.className = 'mat-player-pdf';
        playerEl.innerHTML = '<iframe src="' + pdfEmbed + '" allowfullscreen></iframe>';
        var pdfBtns = '';
        if (downloadUrl) {
            pdfBtns += '<a href="' + downloadUrl + '" class="mat-source-btn" style="margin-bottom:8px;">📥 PDF\'i İndir</a>';
        }
        srcEl.innerHTML = pdfBtns;
    } else if (srcUrl) {
        playerEl.className = 'mat-player-none';
        playerEl.innerHTML = '<div class="mat-player-none-icon">🔗</div><div class="mat-player-none-lbl">Kaynak bağlantı mevcut</div>';
        srcEl.innerHTML = '<a href="' + srcUrl + '" target="_blank" rel="noopener" class="mat-source-btn">🔗 Kaynağı Aç</a>';
    } else {
        playerEl.className = 'mat-player-none';
        playerEl.innerHTML = '<div class="mat-player-none-icon">📄</div><div class="mat-player-none-lbl">Metin Materyali</div>';
        srcEl.innerHTML = '';
    }

    // Actions
    const actEl = document.getElementById('det-actions');
    if (!isRead) {
        actEl.innerHTML = '<form method="post" action="' + readUrl + '" style="margin:0;">' +
            '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
            '<button type="submit" class="btn ok" style="font-size:var(--tx-sm);padding:9px 18px;">✓ Okundu İşaretle</button>' +
            '</form>';
    } else {
        actEl.innerHTML = '<span style="font-size:var(--tx-sm);color:var(--u-muted);">Bu materyali zaten okudunuz.</span>';
    }
}

function matCloseDetail() {
    document.getElementById('mat-grid-view').style.display = 'block';
    document.getElementById('mat-detail-view').style.display = 'none';
    document.getElementById('det-player').innerHTML = '';
}
</script>
@endsection

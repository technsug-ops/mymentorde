@extends('marketing-admin.layouts.app')

@section('title', 'Şehir Videoları')
@section('page_subtitle', 'Şehir detay sayfasındaki video havuzunu yönet (kategori bazlı, sıralanabilir)')

@section('content')
<style>
.cv-wrap { padding:0 4px; }
.cv-grid { display:grid; grid-template-columns:240px 1fr; gap:14px; align-items:start; }
@media (max-width:900px){ .cv-grid { grid-template-columns:1fr; } }

.cv-cities { background:var(--u-card,#fff); border:1px solid var(--u-line,#e2e8f0); border-radius:10px; padding:8px; max-height:80vh; overflow-y:auto; }
.cv-city-btn {
    display:flex; align-items:center; gap:8px;
    width:100%; padding:8px 10px; border-radius:8px; cursor:pointer;
    background:transparent; border:1px solid transparent; text-align:left;
    color:var(--u-text); text-decoration:none; font-size:13px; font-weight:600;
    margin-bottom:2px;
}
.cv-city-btn:hover { background:rgba(126,88,191,.06); }
.cv-city-btn.active { background:rgba(126,88,191,.14); border-color:rgba(126,88,191,.3); color:#7e58bf; }
.cv-city-emoji { font-size:18px; line-height:1; }
.cv-city-count { margin-left:auto; font-size:11px; padding:2px 8px; border-radius:999px; background:var(--u-bg,#f1f5f9); color:var(--u-muted,#64748b); font-weight:700; }
.cv-city-btn.active .cv-city-count { background:rgba(126,88,191,.2); color:#7e58bf; }

.cv-panel { background:var(--u-card,#fff); border:1px solid var(--u-line,#e2e8f0); border-radius:10px; padding:16px; }
.cv-panel h2 { margin:0 0 4px; font-size:18px; display:flex; align-items:center; gap:8px; }
.cv-panel .cv-sub { color:var(--u-muted,#64748b); font-size:12px; margin-bottom:14px; }

.cv-form-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px; }
@media (max-width:700px){ .cv-form-row { grid-template-columns:1fr; } }
.cv-form-row label { display:flex; flex-direction:column; gap:4px; font-size:12px; font-weight:600; color:var(--u-text); }
.cv-form-row input, .cv-form-row select, .cv-form-row textarea { padding:8px 10px; border:1px solid var(--u-line,#cbd5e1); border-radius:7px; font-size:13px; background:var(--u-bg,#fff); color:var(--u-text); }
.cv-form-row textarea { resize:vertical; min-height:60px; font-family:inherit; }
.cv-form-actions { display:flex; gap:8px; margin-top:6px; }
.cv-btn { padding:8px 16px; border-radius:7px; font-size:13px; font-weight:700; cursor:pointer; border:none; }
.cv-btn-primary { background:#7e58bf; color:#fff; }
.cv-btn-primary:hover { background:#6b46a8; }
.cv-btn-ghost { background:var(--u-bg,#f1f5f9); color:var(--u-text); border:1px solid var(--u-line,#cbd5e1); }
.cv-btn-danger { background:rgba(220,38,38,.1); color:#dc2626; border:1px solid rgba(220,38,38,.3); }
.cv-btn-danger:hover { background:rgba(220,38,38,.18); }

.cv-section-title { font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:var(--u-muted,#64748b); margin:18px 0 8px; display:flex; align-items:center; gap:8px; }
.cv-section-title .cv-cat-dot { width:8px; height:8px; border-radius:50%; }
.cv-cat-color-şehir       { background:#2563eb; }
.cv-cat-color-üniversite  { background:#7c3aed; }
.cv-cat-color-yaşam       { background:#16a34a; }
.cv-cat-color-kariyer     { background:#d97706; }
.cv-cat-color-genel       { background:#64748b; }

.cv-vid { display:flex; gap:12px; padding:10px; background:var(--u-bg,#f8fafc); border:1px solid var(--u-line,#e2e8f0); border-radius:10px; margin-bottom:8px; }
.cv-vid-thumb { width:110px; aspect-ratio:16/9; border-radius:7px; background:#0f172a center/cover no-repeat; flex-shrink:0; position:relative; }
.cv-vid-thumb-ph { display:flex; align-items:center; justify-content:center; color:#cbd5e1; font-size:24px; }
.cv-vid-meta { flex:1; min-width:0; }
.cv-vid-title { font-weight:700; font-size:13px; color:var(--u-text); margin-bottom:3px; line-height:1.3; }
.cv-vid-desc { font-size:11.5px; color:var(--u-muted,#64748b); margin-bottom:4px; line-height:1.45; }
.cv-vid-info { font-size:10.5px; color:var(--u-muted,#94a3b8); font-family:ui-monospace,monospace; }
.cv-vid-actions { display:flex; flex-direction:column; gap:4px; }
.cv-vid-actions .cv-iconbtn { width:28px; height:28px; border:none; background:transparent; border-radius:6px; cursor:pointer; font-size:14px; color:var(--u-muted,#64748b); display:flex; align-items:center; justify-content:center; }
.cv-vid-actions .cv-iconbtn:hover { background:rgba(126,88,191,.1); color:#7e58bf; }
.cv-vid-actions .cv-iconbtn.del:hover { background:rgba(220,38,38,.12); color:#dc2626; }

.cv-empty { padding:24px; text-align:center; color:var(--u-muted,#64748b); font-size:12.5px; border:1px dashed var(--u-line,#cbd5e1); border-radius:10px; }

.cv-flash { padding:10px 14px; background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.3); border-radius:8px; color:#047857; font-size:13px; font-weight:600; margin-bottom:12px; }

/* Inline edit form (initially hidden, shown via JS) */
.cv-edit-form { display:none; padding:10px; margin-top:6px; background:rgba(126,88,191,.05); border:1px dashed rgba(126,88,191,.3); border-radius:8px; }
.cv-edit-form.active { display:block; }
</style>

<div class="cv-wrap">
    @if(session('status'))
        <div class="cv-flash">✓ {{ session('status') }}</div>
    @endif

    <div class="cv-grid">
        {{-- Sol: Şehir listesi --}}
        <aside class="cv-cities">
            <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--u-muted,#64748b);padding:6px 10px 10px;">
                Şehirler ({{ $cities->count() }})
            </div>
            @foreach($citySummary as $c)
                <a href="?city={{ $c['slug'] }}"
                   class="cv-city-btn {{ $selected && $selected->slug === $c['slug'] ? 'active' : '' }}">
                    <span class="cv-city-emoji">{{ $c['emoji'] ?: '📍' }}</span>
                    <span>{{ $c['name'] }}</span>
                    <span class="cv-city-count" title="{{ $c['count'] }} gerçek / {{ $c['total'] }} toplam">{{ $c['count'] }}/{{ $c['total'] }}</span>
                </a>
            @endforeach
        </aside>

        {{-- Sağ: Seçili şehrin videoları + ekleme formu --}}
        <section class="cv-panel">
            @if($selected)
            <h2><span>{{ $selected->emoji ?: '📍' }}</span> {{ $selected->name }} Videoları</h2>
            <div class="cv-sub">Toplam <strong>{{ count($videos) }}</strong> video — kategoriler bazında düzenli sırada. Üst = önce gösterilir.</div>

            {{-- Ekleme formu --}}
            <form method="POST" action="/mktg-admin/city-videos/{{ $selected->slug }}" style="padding:14px; background:rgba(126,88,191,.05); border:1px solid rgba(126,88,191,.18); border-radius:10px; margin-bottom:18px;">
                @csrf
                <div style="font-weight:700; font-size:13px; color:#7e58bf; margin-bottom:10px;">➕ Yeni Video Ekle</div>
                <div class="cv-form-row">
                    <label>
                        <span>YouTube URL veya 11-karakterli ID</span>
                        <input type="text" name="youtube_url" required placeholder="https://www.youtube.com/watch?v=... veya dQw4w9WgXcQ">
                    </label>
                    <label>
                        <span>Kategori</span>
                        <select name="category" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="cv-form-row">
                    <label>
                        <span>Başlık</span>
                        <input type="text" name="title" required maxlength="200" placeholder="Berlin'de Öğrenci Hayatı">
                    </label>
                    <label>
                        <span>Süre (opsiyonel)</span>
                        <input type="text" name="duration" maxlength="20" placeholder="8:24">
                    </label>
                </div>
                <div class="cv-form-row" style="grid-template-columns:1fr;">
                    <label>
                        <span>Açıklama (opsiyonel)</span>
                        <textarea name="description" maxlength="500" placeholder="Videoya kısa açıklama..."></textarea>
                    </label>
                </div>
                <div class="cv-form-actions">
                    <button type="submit" class="cv-btn cv-btn-primary">Ekle</button>
                </div>
            </form>

            {{-- Kategori bazlı liste --}}
            @php
                $byCategory = collect($videos)
                    ->map(fn($v, $i) => array_merge($v, ['_idx' => $i]))
                    ->groupBy('category');
                $categoryLabels = ['şehir' => 'Şehir Hayatı', 'üniversite' => 'Üniversite', 'yaşam' => 'Yaşam', 'kariyer' => 'Kariyer', 'genel' => 'Genel'];
            @endphp

            @foreach($categories as $cat)
                @php $catVids = $byCategory[$cat] ?? collect(); @endphp
                @if($catVids->isNotEmpty())
                <div class="cv-section-title">
                    <span class="cv-cat-dot cv-cat-color-{{ $cat }}"></span>
                    {{ $categoryLabels[$cat] ?? $cat }} ({{ $catVids->count() }})
                </div>
                @foreach($catVids as $v)
                    @php $thumb = $v['youtube_id'] ? 'https://img.youtube.com/vi/'.$v['youtube_id'].'/hqdefault.jpg' : ''; @endphp
                    <div class="cv-vid" data-vid-row="{{ $v['_idx'] }}">
                        <div class="cv-vid-thumb {{ $thumb ? '' : 'cv-vid-thumb-ph' }}" style="{{ $thumb ? "background-image:url('{$thumb}');" : '' }}">
                            @if(!$thumb)▶@endif
                        </div>
                        <div class="cv-vid-meta">
                            <div class="cv-vid-title">{{ $v['title'] }}</div>
                            @if(!empty($v['description']))
                                <div class="cv-vid-desc">{{ $v['description'] }}</div>
                            @endif
                            <div class="cv-vid-info">
                                ID: <code>{{ $v['youtube_id'] }}</code>
                                @if(!empty($v['duration'])) · ⏱ {{ $v['duration'] }} @endif
                                · <a href="https://www.youtube.com/watch?v={{ $v['youtube_id'] }}" target="_blank" style="color:#7e58bf;">YouTube'da aç ↗</a>
                            </div>

                            {{-- Inline edit form --}}
                            <form method="POST" action="/mktg-admin/city-videos/{{ $selected->slug }}/{{ $v['_idx'] }}" class="cv-edit-form" data-edit-form="{{ $v['_idx'] }}">
                                @csrf
                                @method('PATCH')
                                <div class="cv-form-row">
                                    <label>
                                        <span>YouTube URL/ID</span>
                                        <input type="text" name="youtube_url" required value="{{ $v['youtube_id'] }}">
                                    </label>
                                    <label>
                                        <span>Kategori</span>
                                        <select name="category" required>
                                            @foreach($categories as $optCat)
                                                <option value="{{ $optCat }}" @selected($v['category'] === $optCat)>{{ ucfirst($optCat) }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="cv-form-row">
                                    <label>
                                        <span>Başlık</span>
                                        <input type="text" name="title" required value="{{ $v['title'] }}">
                                    </label>
                                    <label>
                                        <span>Süre</span>
                                        <input type="text" name="duration" value="{{ $v['duration'] ?? '' }}">
                                    </label>
                                </div>
                                <div class="cv-form-row" style="grid-template-columns:1fr;">
                                    <label>
                                        <span>Açıklama</span>
                                        <textarea name="description">{{ $v['description'] ?? '' }}</textarea>
                                    </label>
                                </div>
                                <div class="cv-form-actions">
                                    <button type="submit" class="cv-btn cv-btn-primary">Kaydet</button>
                                    <button type="button" class="cv-btn cv-btn-ghost" data-cancel-edit="{{ $v['_idx'] }}">İptal</button>
                                </div>
                            </form>
                        </div>
                        <div class="cv-vid-actions">
                            <button type="button" class="cv-iconbtn" data-edit-toggle="{{ $v['_idx'] }}" title="Düzenle">✎</button>
                            <form method="POST" action="/mktg-admin/city-videos/{{ $selected->slug }}/{{ $v['_idx'] }}/move" style="display:inline;">
                                @csrf
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="cv-iconbtn" title="Yukarı taşı">↑</button>
                            </form>
                            <form method="POST" action="/mktg-admin/city-videos/{{ $selected->slug }}/{{ $v['_idx'] }}/move" style="display:inline;">
                                @csrf
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="cv-iconbtn" title="Aşağı taşı">↓</button>
                            </form>
                            <form method="POST" action="/mktg-admin/city-videos/{{ $selected->slug }}/{{ $v['_idx'] }}" style="display:inline;" data-del-form>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="cv-iconbtn del" title="Sil" data-del-title="{{ $v['title'] }}">🗑</button>
                            </form>
                        </div>
                    </div>
                @endforeach
                @endif
            @endforeach

            @if(empty($videos))
                <div class="cv-empty">Bu şehir için henüz video eklenmemiş. Üstteki formu kullanarak ilk videoyu ekle.</div>
            @endif
            @else
                <div class="cv-empty">Sol panelden bir şehir seç.</div>
            @endif
        </section>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // Düzenle toggle: ilgili .cv-edit-form'u aç
    document.querySelectorAll('[data-edit-toggle]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var idx = btn.dataset.editToggle;
            document.querySelectorAll('.cv-edit-form').forEach(function(f){ f.classList.remove('active'); });
            var form = document.querySelector('[data-edit-form="' + idx + '"]');
            if (form) form.classList.add('active');
        });
    });
    document.querySelectorAll('[data-cancel-edit]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var idx = btn.dataset.cancelEdit;
            var form = document.querySelector('[data-edit-form="' + idx + '"]');
            if (form) form.classList.remove('active');
        });
    });

    // Sil confirm
    document.querySelectorAll('[data-del-form]').forEach(function(form){
        form.addEventListener('submit', function(e){
            var btn = form.querySelector('[data-del-title]');
            var title = btn ? btn.dataset.delTitle : 'bu video';
            if (!confirm('"' + title + '" videosunu silmek istediğine emin misin?')) {
                e.preventDefault();
            }
        });
    });
})();
</script>
@endsection

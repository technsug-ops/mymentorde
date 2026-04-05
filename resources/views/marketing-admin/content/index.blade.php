@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn" style="font-size:var(--tx-xs);padding:6px 12px;background:var(--u-brand,#1e40af);color:#fff;border-color:transparent;" href="/mktg-admin/content">İçerik</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/categories">Kategoriler</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/media">Medya</a>
@endsection

@section('title', 'CMS İçerik')
@section('page_subtitle', 'CMS İçerik Yönetimi — blog, duyuru ve kampanya içerikleri')

@push('head')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
@endpush

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }

/* Stats bar */
.ct-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.ct-stat  { flex:1; padding:10px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.ct-stat:last-child { border-right:none; }
.ct-val   { font-size:20px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.ct-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

/* 2-col layout */
.ct-grid { display:grid; grid-template-columns:1fr 1.2fr; gap:12px; }
@media(max-width:1200px){ .ct-grid { grid-template-columns:1fr; } }

/* Form inputs */
.fm-row   { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px; }
.fm-row-1 { margin-bottom:8px; }
.fm-row input, .fm-row select, .fm-row textarea,
.fm-row-1 input, .fm-row-1 select, .fm-row-1 textarea {
    width:100%; box-sizing:border-box; height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.fm-row textarea, .fm-row-1 textarea { height:80px; padding:8px 10px; resize:vertical; }
.fm-row input:focus, .fm-row select:focus, .fm-row textarea:focus,
.fm-row-1 input:focus, .fm-row-1 select:focus, .fm-row-1 textarea:focus {
    border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10);
}
.fm-row input[type=datetime-local] { padding:0 8px; }

/* Filter bar */
.fl-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding:8px 0 4px; }
.fl-bar input, .fl-bar select {
    height:34px; padding:0 10px; border:1px solid var(--u-line,#e2e8f0);
    border-radius:8px; background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:12px; outline:none; min-width:110px; appearance:auto;
}
.fl-bar input:focus, .fl-bar select:focus { border-color:var(--u-brand,#1e40af); }

/* Table */
.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; margin-top:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; min-width:780px; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }
.tl-acts { display:flex; gap:4px; flex-wrap:wrap; }

/* Details guide */
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

/* Alerts */
.flash   { border:1px solid var(--u-ok,#16a34a); background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,#fff); color:var(--u-ok,#16a34a); border-radius:10px; padding:10px 14px; font-size:13px; }
.err-box { border:1px solid var(--u-danger,#dc2626); background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,#fff); color:var(--u-danger,#dc2626); border-radius:10px; padding:10px 14px; font-size:13px; }
</style>

<div style="display:grid;gap:12px;">
    @if(session('status')) <div class="flash">{{ session('status') }}</div> @endif
    @if($errors->any())
        <div class="err-box">@foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach</div>
    @endif

    {{-- KPI bar --}}
    <div class="ct-stats">
        <div class="ct-stat"><div class="ct-val">{{ $stats['total'] ?? 0 }}</div><div class="ct-lbl">Toplam</div></div>
        <div class="ct-stat"><div class="ct-val">{{ $stats['published'] ?? 0 }}</div><div class="ct-lbl">Published</div></div>
        <div class="ct-stat"><div class="ct-val">{{ $stats['scheduled'] ?? 0 }}</div><div class="ct-lbl">Scheduled</div></div>
        <div class="ct-stat"><div class="ct-val">{{ $stats['featured'] ?? 0 }}</div><div class="ct-lbl">Featured</div></div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — İçerik Yönetimi (CMS)</h3>
            <span class="det-chev">▼</span>
        </summary>
        <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 14px;line-height:1.6;">
            Web sitesi, blog, duyuru ve diğer içerikleri oluşturun; kategorilere ekleyin, medya yükleyin ve yayın zamanlayın.
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div style="display:flex;flex-direction:column;gap:8px;font-size:var(--tx-xs);line-height:1.5;">
                @foreach(['Kategori oluştur — Kategoriler sekmesinden içerik kategorileri tanımla.','Medya yükle — Medya sekmesinde görselleri önceden yükle, içeriklerde kullan.','İçerik oluştur — Başlık, kısa özet, içerik gövdesi ve kategori doldur.','Durumu ayarla — Draft olarak kaydet; hazırsa Published, ileri tarih için Scheduled seç.','Revizyon takibi — Her düzenlemede otomatik revizyon kaydedilir; eski versiyona dönülebilir.'] as $i => $step)
                <div style="display:flex;gap:8px;align-items:flex-start;">
                    <span style="background:var(--u-brand,#1e40af);color:#fff;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:var(--tx-xs);font-weight:700;flex-shrink:0;">{{ $i+1 }}</span>
                    <span>{{ $step }}</span>
                </div>
                @endforeach
            </div>
            <div>
                <div style="font-size:var(--tx-xs);font-weight:600;margin-bottom:8px;">İçerik Durumları</div>
                <div style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;overflow:hidden;font-size:var(--tx-xs);">
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);align-items:center;"><span class="badge">Draft</span><span style="color:var(--u-muted);">Taslak — sadece yöneticiler görür</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);align-items:center;"><span class="badge ok">Published</span><span style="color:var(--u-muted);">Yayında — herkese açık</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);align-items:center;"><span class="badge info">Scheduled</span><span style="color:var(--u-muted);">Zamanlandı — ileri tarihte yayınlanır</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;align-items:center;"><span class="badge pending">Archived</span><span style="color:var(--u-muted);">Arşivlendi — listede gizlenir</span></div>
                </div>
                <div style="margin-top:10px;background:color-mix(in srgb,var(--u-brand,#1e40af) 5%,var(--u-card,#fff));border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                    💡 "Öne Çıkar" ile içeriği web sitesinde öne çıkar. SEO için slug, meta başlık ve açıklama alanlarını doldurmayı unutma.
                </div>
            </div>
        </div>
    </details>

    {{-- 2-col: form + list --}}
    <div class="ct-grid">

        {{-- Yeni / Düzenleme formu --}}
        @php
            $isEdit = !empty($editing);
            $action = $isEdit ? '/mktg-admin/content/'.$editing->id : '/mktg-admin/content';
            $gallery     = old('gallery_urls',          $isEdit ? implode(',', (array) ($editing->gallery_urls          ?? [])) : '');
            $keywords    = old('seo_keywords',           $isEdit ? implode(',', (array) ($editing->seo_keywords           ?? [])) : '');
            $tags        = old('tags',                   $isEdit ? implode(',', (array) ($editing->tags                   ?? [])) : '');
            $targetTypes = old('target_student_types',   $isEdit ? implode(',', (array) ($editing->target_student_types   ?? [])) : '');
        @endphp
        <details class="card" {{ $isEdit ? 'open' : '' }}>
            <summary class="det-sum">
                <h3>{{ $isEdit ? '✏️ İçerik Düzenle #'.$editing->id : '+ Yeni İçerik' }}</h3>
                <span class="det-chev">▼</span>
            </summary>
            <form method="POST" action="{{ $action }}" style="margin-top:12px;">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="fm-row">
                    <select name="type">
                        @foreach(($typeOptions ?? []) as $tp)
                            <option value="{{ $tp }}" @selected(old('type', $editing->type ?? 'blog') === $tp)>{{ $tp }}</option>
                        @endforeach
                    </select>
                    <select name="status">
                        @foreach(($statusOptions ?? []) as $st)
                            <option value="{{ $st }}" @selected(old('status', $editing->status ?? 'draft') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fm-row-1">
                    <input id="cms-title-input" name="title_tr" placeholder="Başlık TR" value="{{ old('title_tr', $editing->title_tr ?? '') }}" required style="width:100%;box-sizing:border-box;height:36px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:13px;outline:none;transition:border-color .15s;">
                </div>
                <div class="fm-row">
                    <select name="category">
                        <option value="">Kategori (opsiyonel)</option>
                        @foreach(($categories ?? []) as $cat)
                            <option value="{{ $cat->code }}" @selected(old('category', $editing->category ?? '') === $cat->code)>{{ $cat->code }} — {{ $cat->name_tr }}</option>
                        @endforeach
                    </select>
                    <select name="linked_campaign_id">
                        <option value="">Bağlı Kampanya (opsiyonel)</option>
                        @foreach(($campaignOptions ?? []) as $cmp)
                            <option value="{{ $cmp->id }}" @selected((string) old('linked_campaign_id', $editing->linked_campaign_id ?? '') === (string) $cmp->id)>#{{ $cmp->id }} {{ $cmp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fm-row">
                    <input name="summary_tr" placeholder="Kısa özet" value="{{ old('summary_tr', $editing->summary_tr ?? '') }}">
                    <input name="cover_image_url" placeholder="Cover image URL" value="{{ old('cover_image_url', $editing->cover_image_url ?? '') }}">
                </div>
                <div class="fm-row-1">
                    {{-- Gizli textarea (form submit için) --}}
                    <textarea name="content_tr" id="content-tr-hidden" style="display:none;" required>{{ old('content_tr', $editing->content_tr ?? '') }}</textarea>
                    {{-- Quill editör alanı --}}
                    <div id="quill-editor-cms" style="height:280px;border:1px solid var(--u-line,#e2e8f0);border-radius:0 0 8px 8px;background:var(--u-card,#fff);font-size:13px;"></div>
                </div>

                {{-- Gelişmiş Ayarlar --}}
                <details style="margin-top:8px;" {{ $isEdit ? 'open' : '' }}>
                    <summary style="cursor:pointer;font-size:12px;font-weight:700;color:var(--u-muted,#64748b);padding:4px 0;list-style:none;display:flex;align-items:center;gap:6px;user-select:none;">
                        <span style="display:inline-block;transition:transform .2s;" class="cms-adv-chev">▶</span> Gelişmiş Ayarlar
                    </summary>
                    <div style="margin-top:8px;display:grid;gap:8px;padding:10px;border:1px dashed var(--u-line,#e2e8f0);border-radius:8px;">
                        <div class="fm-row">
                            <input id="cms-slug-input" name="slug" placeholder="slug (otomatik üretilir)" value="{{ old('slug', $editing->slug ?? '') }}" style="font-family:ui-monospace,monospace;font-size:12px;">
                            <select name="target_audience">
                                <option value="all" @selected(old('target_audience', $editing->target_audience ?? 'all') === 'all')>🌐 Tüm Kullanıcılar</option>
                                <option value="guests" @selected(old('target_audience', $editing->target_audience ?? '') === 'guests')>👤 Sadece Guest</option>
                                <option value="students" @selected(old('target_audience', $editing->target_audience ?? '') === 'students')>🎓 Sadece Student</option>
                            </select>
                        </div>
                        <div class="fm-row">
                            <input name="video_url" placeholder="Video/Podcast/Sunum URL (YouTube embed, Spotify, Google Slides)" value="{{ old('video_url', $editing->video_url ?? '') }}">
                        </div>
                        <div class="fm-row">
                            <input name="video_thumbnail_url" placeholder="Video thumbnail URL" value="{{ old('video_thumbnail_url', $editing->video_thumbnail_url ?? '') }}">
                            <input name="gallery_urls" placeholder="Galeri URL (virgüllü)" value="{{ $gallery }}">
                        </div>
                        <div class="fm-row">
                            <input name="tags" placeholder="Etiketler (virgüllü)" value="{{ $tags }}">
                            <input name="seo_keywords" placeholder="SEO keywords (virgüllü)" value="{{ $keywords }}">
                        </div>
                        <div class="fm-row">
                            <input name="target_student_types" placeholder="Hedef öğrenci tipleri (virgüllü)" value="{{ $targetTypes }}">
                            <input name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at', !empty($editing->scheduled_at) ? \Illuminate\Support\Carbon::parse($editing->scheduled_at)->format('Y-m-d\TH:i') : '') }}">
                        </div>
                        <div class="fm-row">
                            <select name="is_featured">
                                <option value="0" @selected((string) old('is_featured', isset($editing) ? (int) $editing->is_featured : 0) === '0')>Featured: Hayır</option>
                                <option value="1" @selected((string) old('is_featured', isset($editing) ? (int) $editing->is_featured : 0) === '1')>Featured: Evet</option>
                            </select>
                            <input name="featured_order" type="number" min="1" max="9999" placeholder="Featured order" value="{{ old('featured_order', $editing->featured_order ?? '') }}">
                        </div>
                        <div class="fm-row-1" style="margin-bottom:0;">
                            <input name="change_note" placeholder="Değişiklik notu" value="{{ old('change_note') }}" style="width:100%;box-sizing:border-box;height:36px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:13px;outline:none;transition:border-color .15s;">
                        </div>
                    </div>
                </details>
                <script>
                (function() {
                    var titleEl = document.getElementById('cms-title-input');
                    var slugEl  = document.getElementById('cms-slug-input');
                    if (!titleEl || !slugEl) return;
                    var slugEdited = slugEl.value !== '';
                    slugEl.addEventListener('input', function() { slugEdited = slugEl.value !== ''; });
                    titleEl.addEventListener('input', function() {
                        if (slugEdited) return;
                        slugEl.value = titleEl.value.toLowerCase()
                            .replace(/[ğ]/g,'g').replace(/[ü]/g,'u').replace(/[ş]/g,'s')
                            .replace(/[ı]/g,'i').replace(/[ö]/g,'o').replace(/[ç]/g,'c')
                            .replace(/[^a-z0-9\s-]/g,'').trim().replace(/\s+/g,'-').replace(/-+/g,'-');
                    });
                    document.querySelectorAll('details').forEach(function(d) {
                        d.addEventListener('toggle', function() {
                            var chev = d.querySelector('.cms-adv-chev');
                            if (chev) chev.style.transform = d.open ? 'rotate(90deg)' : 'rotate(0deg)';
                        });
                    });
                })();
                </script>

                <div style="display:flex;gap:8px;margin-top:6px;">
                    <button type="submit" class="btn ok">{{ $isEdit ? 'İçerik Güncelle' : 'İçerik Ekle' }}</button>
                    <a href="/mktg-admin/content" class="btn alt">Temizle</a>
                </div>
            </form>
        </details>

        {{-- İçerik Listesi --}}
        <article class="card" style="min-width:0;">
            <h3 style="margin:0 0 2px;font-size:var(--tx-sm);font-weight:700;">İçerik Listesi</h3>
            <form method="GET" action="/mktg-admin/content">
                <div class="fl-bar">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="slug / başlık / kategori" style="flex:1;min-width:140px;">
                    <select name="status">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tüm durumlar</option>
                        @foreach(($statusOptions ?? []) as $st)
                            <option value="{{ $st }}" @selected(($filters['status'] ?? 'all') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    <select name="type">
                        <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>Tüm tipler</option>
                        @foreach(($typeOptions ?? []) as $tp)
                            <option value="{{ $tp }}" @selected(($filters['type'] ?? 'all') === $tp)>{{ $tp }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                    <a href="/mktg-admin/content" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 14px;display:flex;align-items:center;">Temizle</a>
                </div>
            </form>

            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>ID</th><th>İçerik</th><th>Tip</th><th>Durum</th><th>Views</th><th>Rev</th><th>İşlem</th>
                    </tr></thead>
                    <tbody>
                    @forelse(($rows ?? []) as $row)
                        @php
                            $stBadge = match($row->status) {
                                'published' => 'ok',
                                'scheduled' => 'info',
                                'archived'  => 'pending',
                                default     => '',
                            };
                        @endphp
                        <tr>
                            <td style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">#{{ $row->id }}</td>
                            <td>
                                <strong>{{ $row->title_tr }}</strong><br>
                                <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row->slug }}</span>
                            </td>
                            <td>
                                <span>{{ $row->type }}</span><br>
                                <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row->category ?: '—' }}</span>
                            </td>
                            <td><span class="badge {{ $stBadge }}">{{ $row->status }}</span></td>
                            <td style="font-size:var(--tx-xs);">{{ (int) $row->metric_total_views }}<span style="color:var(--u-muted);">/{{ (int) $row->metric_unique_views }}</span></td>
                            <td style="font-size:var(--tx-xs);">{{ (int) $row->current_revision }}</td>
                            <td>
                                <div class="tl-acts">
                                    <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;" href="/mktg-admin/content?edit_id={{ $row->id }}">Düzenle</a>
                                    <form method="POST" action="/mktg-admin/content/{{ $row->id }}/publish" style="display:inline;">@csrf @method('PUT')<button class="btn ok" style="font-size:var(--tx-xs);padding:4px 8px;" type="submit">Yayınla</button></form>
                                    <form method="POST" action="/mktg-admin/content/{{ $row->id }}/unpublish" style="display:inline;">@csrf @method('PUT')<button class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;" type="submit">Draft</button></form>
                                    <form method="POST" action="/mktg-admin/content/{{ $row->id }}/feature" style="display:inline;">@csrf @method('PUT')<button class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;" type="submit">Feature</button></form>
                                    <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;" href="/mktg-admin/content/{{ $row->id }}/stats">Stats</a>
                                    <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;" href="/mktg-admin/content/{{ $row->id }}/revisions">Rev.</a>
                                    <form method="POST" action="/mktg-admin/content/{{ $row->id }}" style="display:inline;">@csrf @method('DELETE')<button class="btn warn" style="font-size:var(--tx-xs);padding:4px 8px;" type="submit" onclick="return confirm('İçerik silinsin mi?')">Sil</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">İçerik kaydı yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;">{{ $rows->links() }}</div>
        </article>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function(){
    var editorEl = document.getElementById('quill-editor-cms');
    var hiddenEl = document.getElementById('content-tr-hidden');
    if (!editorEl || !hiddenEl) return;

    var quill = new Quill('#quill-editor-cms', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold','italic','underline','strike'],
                [{'header':[1,2,3,false]}],
                [{'list':'ordered'},{'list':'bullet'}],
                ['link','blockquote','code-block'],
                ['clean']
            ]
        },
        placeholder: 'Icerigi buraya yazin...'
    });

    // Mevcut degeri yukle
    var existing = hiddenEl.value;
    if (existing) quill.root.innerHTML = existing;

    // Form submit oncesi hidden textarea'ya yaz
    var formEl = editorEl.closest('form');
    if (formEl) {
        formEl.addEventListener('submit', function(){
            hiddenEl.value = quill.root.innerHTML;
        });
    }
})();
</script>
@endpush

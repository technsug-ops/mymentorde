@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/content/overview">← İçerik Tablosu</a>
@endsection

@section('title', $isEdit ? 'İçerik Düzenle' : 'Yeni İçerik')
@section('page_subtitle', $isEdit ? ($editing->content_code ?? '#'.$editing->id).' — '.$editing->title_tr : 'Yeni blog')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
@endpush

@section('content')
<style>
/* AI Labs paleti — tek mor accent, sade tipografi, gradient yok */
.ce-wrap { max-width: 800px; margin: 20px auto; padding: 0 16px; }

/* Tek tip kart — AI Labs benzeri */
.ce-block { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 16px; }
.ce-block-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

/* AI Üret — info kutucuğu sade mor */
.ce-ai-block { background: #faf7ff; border: 1px solid #ede9fe; }
.ce-ai-block .ce-block-label { color: #5b2e91; }
.ce-ai-row { display: flex; gap: 10px; align-items: flex-start; }
.ce-ai-row textarea { flex: 1; font-size: 13px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; resize: vertical; min-height: 60px; box-sizing: border-box; outline: none; font-family: inherit; }
.ce-ai-row textarea:focus { border-color: #5b2e91; }
.ce-ai-btn { padding: 0 22px; background: #5b2e91; color: #fff; border: 0; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; min-height: 60px; white-space: nowrap; }
.ce-ai-btn:hover { background: #4a2578; }
.ce-ai-btn:disabled { opacity: .5; cursor: wait; }
.ce-ai-opts { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
.ce-ai-opts select { padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; background: #fff; color: #334155; cursor: pointer; font-family: inherit; }
.ce-ai-opts select:focus { border-color: #5b2e91; outline: none; }
.ce-ai-status { font-size: 12px; color: #64748b; margin-top: 8px; min-height: 16px; }

/* Başlık & Özet */
.ce-title-input {
    width: 100%; box-sizing: border-box; padding: 12px 14px;
    font-size: 18px; font-weight: 700;
    border: 1px solid #cbd5e1; border-radius: 8px;
    background: #fff; color: #0f172a;
    outline: none; font-family: inherit;
}
.ce-title-input:focus { border-color: #5b2e91; }
.ce-summary-input {
    width: 100%; box-sizing: border-box; padding: 10px 12px; margin-top: 10px;
    font-size: 13px; line-height: 1.5;
    border: 1px solid #cbd5e1; border-radius: 8px;
    background: #fff; color: #334155;
    outline: none; min-height: 56px; resize: vertical; font-family: inherit;
}
.ce-summary-input:focus { border-color: #5b2e91; }

/* Quill */
#quill-editor-cms { height: 500px; border: 1px solid #e2e8f0; border-top: 0; border-radius: 0 0 8px 8px; background: #fff; font-size: 14px; }
.ql-toolbar.ql-snow { border: 1px solid #e2e8f0 !important; border-radius: 8px 8px 0 0; background: #faf7ff; }
.ql-snow .ql-stroke { stroke: #334155 !important; }
.ql-snow.ql-toolbar button:hover .ql-stroke { stroke: #5b2e91 !important; }
.ql-snow.ql-toolbar button.ql-active .ql-stroke { stroke: #5b2e91 !important; }

/* Hızlı ayarlar grid (Kapak + Settings yan yana) */
.ce-quick { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 680px) { .ce-quick { grid-template-columns: 1fr; } }

/* Status chips */
.ce-status-chips { display: flex; gap: 6px; flex-wrap: wrap; }
.ce-status-chip { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 16px; font-size: 12px; font-weight: 600; cursor: pointer; background: #fff; color: #334155; transition: all .15s; }
.ce-status-chip:hover { border-color: #5b2e91; background: #faf7ff; color: #5b2e91; }
.ce-status-chip.active { background: #5b2e91; border-color: #5b2e91; color: #fff; }

/* Cover */
.ce-cover-mini { width: 100%; aspect-ratio: 16/9; border-radius: 8px; background: #e2e8f0 center/cover; border: 1px solid #cbd5e1; margin-bottom: 10px; }
.ce-cover-btns { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px; }
.ce-cover-btn { flex: 1; min-width: 80px; padding: 7px 10px; font-size: 11.5px; font-weight: 600; border-radius: 6px; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; color: #334155; text-align: center; transition: all .15s; display: inline-flex; align-items: center; justify-content: center; gap: 4px; }
.ce-cover-btn:hover { background: #faf7ff; border-color: #5b2e91; color: #5b2e91; }
.ce-cover-btn.primary { background: #5b2e91; border-color: #5b2e91; color: #fff; }
.ce-cover-btn.primary:hover { background: #4a2578; color: #fff; }

/* Field generic */
.ce-field { margin-bottom: 12px; }
.ce-field:last-child { margin-bottom: 0; }
.ce-field label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 5px; }
.ce-field label .ce-hint { font-weight: 400; color: #94a3b8; font-size: 11px; margin-left: 4px; }
.ce-field input, .ce-field select, .ce-field textarea {
    width: 100%; box-sizing: border-box; padding: 9px 11px; font-size: 13px;
    border: 1px solid #cbd5e1; border-radius: 8px;
    background: #fff; color: #334155; outline: none;
    font-family: inherit;
}
.ce-field input:focus, .ce-field select:focus, .ce-field textarea:focus { border-color: #5b2e91; }
.ce-field-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

/* Accordion */
.ce-accordion { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 16px; overflow: hidden; }
.ce-accordion summary { cursor: pointer; padding: 14px 22px; font-size: 13px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 10px; list-style: none; outline: none; user-select: none; }
.ce-accordion summary::-webkit-details-marker { display: none; }
.ce-accordion summary::before { content: '▶'; font-size: 9px; color: #94a3b8; transition: transform .2s; }
.ce-accordion[open] summary::before { transform: rotate(90deg); }
.ce-accordion summary:hover { background: #faf7ff; color: #5b2e91; }
.ce-accordion-body { padding: 6px 22px 18px; }

/* Sub-section */
.ce-subsec { margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px dashed #e2e8f0; }
.ce-subsec:last-child { border-bottom: 0; padding-bottom: 0; margin-bottom: 0; }
.ce-subsec-title { font-size: 11.5px; font-weight: 700; color: #5b2e91; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }

/* Inline mini AI buton */
.ce-mini-ai { padding: 5px 12px; background: #5b2e91; color: #fff; border: 0; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; margin-left: auto; }
.ce-mini-ai:hover { background: #4a2578; }
.ce-mini-ai:disabled { opacity: .5; cursor: wait; }

/* Toolbar */
.ce-toolbar { position: sticky; bottom: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 18px; margin-top: 18px; display: flex; justify-content: space-between; align-items: center; gap: 12px; box-shadow: 0 -2px 8px rgba(0,0,0,.04); z-index: 10; flex-wrap: wrap; }
.ce-btn-primary { padding: 10px 22px; background: #5b2e91; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; }
.ce-btn-primary:hover { background: #4a2578; }
.ce-btn-secondary { padding: 9px 16px; background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.ce-btn-secondary:hover { background: #e2e8f0; }
.ce-btn-danger { padding: 9px 16px; background: #fff; color: #dc2626; border: 1px solid #fca5a5; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; }
.ce-btn-danger:hover { background: #fee2e2; }
.ce-meta-tiny { font-size: 11px; color: #94a3b8; display: flex; gap: 12px; flex-wrap: wrap; }
.ce-info-banner { padding: 10px 14px; background: #faf7ff; border: 1px solid #ede9fe; border-radius: 10px; font-size: 12.5px; color: #334155; margin-bottom: 14px; }
.ce-info-banner.success { background: #dcfce7; border-color: #86efac; color: #166534; }
</style>

<div class="ce-wrap">
    @if(session('status'))
        <div class="ce-info-banner success">✓ {{ session('status') }}</div>
    @endif

    @php
        $action = $isEdit ? '/mktg-admin/content/'.$editing->id : '/mktg-admin/content';
        $gallery     = old('gallery_urls',         $isEdit ? implode(',', (array) ($editing->gallery_urls         ?? [])) : '');
        $keywords    = old('seo_keywords',          $isEdit ? implode(',', (array) ($editing->seo_keywords          ?? [])) : '');
        $tags        = old('tags',                  $isEdit ? implode(',', (array) ($editing->tags                  ?? [])) : '');
        $targetTypes = old('target_student_types',  $isEdit ? implode(',', (array) ($editing->target_student_types  ?? [])) : '');
        $coverUrl    = old('cover_image_url',       $editing->cover_image_url ?? '');
        $currentCat  = old('category',              $editing->category ?? '');
        $currentStatus = old('status',              $editing->status ?? 'draft');
        $aiCategories = \App\Models\Marketing\CmsContent::AI_CATEGORY_CONTEXTS;
    @endphp

    <form method="POST" action="{{ $action }}" id="ce-form">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- ──────────────── 1. AI ÜRET (kompakt) ──────────────── --}}
        <div class="ce-block ce-ai-block">
            <div class="ce-block-label">✨ AI ile İçerik Üret</div>
            <div class="ce-ai-row">
                <textarea id="ce-ai-topic" placeholder="Konuyu 1-2 cümle yaz, AI başlık + özet + içerik üretsin…"></textarea>
                <button type="button" id="ce-ai-generate-btn" class="ce-ai-btn" data-ai-url="{{ url('/mktg-admin/content/ai-generate') }}">✨ Üret</button>
            </div>
            <div class="ce-ai-opts">
                <select id="ce-ai-category" title="Kategori (AI'a bağlam verir)">
                    @foreach($aiCategories as $code => $meta)
                        <option value="{{ $code }}" @selected($currentCat === $code)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
                <select id="ce-ai-language" title="Dil"><option value="tr" selected>🇹🇷 TR</option><option value="de">🇩🇪 DE</option><option value="en">🇬🇧 EN</option></select>
                <select id="ce-ai-tone" title="Ton"><option value="professional">Profesyonel</option><option value="casual">Samimi</option><option value="inspiring">İlham</option><option value="academic">Akademik</option></select>
                <select id="ce-ai-wordcount" title="Kelime"><option value="400">~400</option><option value="600" selected>~600</option><option value="900">~900</option><option value="1200">~1200</option></select>
            </div>
            <div class="ce-ai-status" id="ce-ai-status"></div>
        </div>

        {{-- ──────────────── 2. BAŞLIK + ÖZET (öne çıkan, tek blok) ──────────────── --}}
        <div class="ce-block">
            <input class="ce-title-input" name="title_tr" value="{{ old('title_tr', $editing->title_tr ?? '') }}" required maxlength="255" placeholder="Başlık (TR)">
            <textarea class="ce-summary-input" name="summary_tr" maxlength="500" rows="2" placeholder="Kısa özet (1-2 cümle, kart önyüzü için)">{{ old('summary_tr', $editing->summary_tr ?? '') }}</textarea>
        </div>

        {{-- ──────────────── 3. QUILL ──────────────── --}}
        <div class="ce-block" style="padding: 0;">
            <textarea name="content_tr" id="content-tr-hidden" style="display:none;" required>{{ old('content_tr', $editing->content_tr ?? '') }}</textarea>
            <div id="quill-editor-cms"></div>
        </div>

        {{-- ──────────────── 4. HIZLI AYARLAR (Kapak + Settings yan yana) ──────────────── --}}
        <div class="ce-quick">
            {{-- Kapak --}}
            <div class="ce-block">
                <div class="ce-block-label">🖼 Kapak Görseli</div>
                <div id="cms-cover-preview" class="ce-cover-mini" style="background-image:url('{{ $coverUrl }}');"></div>
                <div class="ce-cover-btns">
                    <button type="button" class="ce-cover-btn primary" id="ce-ai-cover-btn" data-ai-cover-url="{{ url('/mktg-admin/content/ai-suggest-cover') }}" title="AI ile öner">✨ AI</button>
                    <label class="ce-cover-btn">
                        📤 Yükle
                        <input type="file" id="cms-cover-file" accept="image/jpeg,image/png,image/webp" style="display:none;" data-upload-url="{{ url('/mktg-admin/content/upload-cover') }}">
                    </label>
                    <button type="button" class="ce-cover-btn" id="ce-wiki-toggle-btn">🏛 Wiki</button>
                </div>
                <div id="ce-ai-cover-status" style="font-size:10.5px; color:var(--u-muted,#64748b); min-height:14px;"></div>
                <div id="ce-wiki-panel" style="display:none; margin-top:6px; padding:7px; border:1px dashed var(--u-line,#cbd5e1); border-radius:5px; background:var(--u-bg,#f8fafc);">
                    <input id="cms-wiki-uni-input" type="text" placeholder="Üniversite/şehir adı" style="width:100%; box-sizing:border-box; padding:5px 7px; font-size:11.5px; border:1px solid var(--u-line,#cbd5e1); border-radius:4px; margin-bottom:5px;">
                    <button type="button" id="cms-wiki-fetch-btn" data-fetch-url="{{ url('/mktg-admin/content/fetch-university-image') }}" class="ce-cover-btn primary" style="width:100%;">📷 Wiki'den Çek</button>
                    <div id="cms-wiki-status" style="font-size:10.5px; color:var(--u-muted,#64748b); margin-top:4px;"></div>
                </div>
                <input type="hidden" id="cms-cover-url-input" name="cover_image_url" value="{{ $coverUrl }}">
                <input type="hidden" id="cms-cover-alt-input" name="cover_image_alt" value="{{ old('cover_image_alt', $editing->cover_image_alt ?? '') }}">
                <div id="cms-cover-status" style="font-size:10.5px; color:var(--u-muted,#64748b); margin-top:4px;"></div>
            </div>

            {{-- Hızlı Ayarlar --}}
            <div class="ce-block">
                <div class="ce-block-label">⚙ Hızlı Ayarlar</div>
                <div class="ce-field">
                    <label>Durum</label>
                    <div class="ce-status-chips" id="ce-status-chips">
                        @foreach(($statusOptions ?? []) as $st)
                            @php $isActive = $currentStatus === $st; @endphp
                            <div class="ce-status-chip {{ $isActive ? 'active' : '' }}" data-status="{{ $st }}">
                                @switch($st)
                                    @case('draft') 📝 Taslak @break
                                    @case('published') ✓ Yayın @break
                                    @case('scheduled') ⏰ Zamanlı @break
                                    @case('archived') 📦 Arşiv @break
                                @endswitch
                            </div>
                        @endforeach
                    </div>
                    <input type="hidden" name="status" id="ce-status-input" value="{{ $currentStatus }}">
                </div>
                <div id="ce-scheduled-wrap" style="display:{{ $currentStatus === 'scheduled' ? 'block' : 'none' }};">
                    <div class="ce-field">
                        <label>Yayın Zamanı</label>
                        <input name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at', !empty($editing->scheduled_at) ? \Illuminate\Support\Carbon::parse($editing->scheduled_at)->format('Y-m-d\TH:i') : '') }}">
                    </div>
                </div>
                <div class="ce-field">
                    <label>Kategori</label>
                    <select name="category">
                        <option value="">— Seçim yok —</option>
                        @foreach($aiCategories as $code => $meta)
                            <option value="{{ $code }}" @selected($currentCat === $code)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ce-field">
                    <label style="display:flex; align-items:center; gap:7px; cursor:pointer;">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $editing->is_featured ?? false) ? 'checked' : '' }} style="width:auto;">
                        <span>⭐ Öne Çıkar (Featured)</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- ──────────────── 5. GELİŞMİŞ AYARLAR (collapsed, tek accordion) ──────────────── --}}
        <details class="ce-accordion">
            <summary>⚙️ Gelişmiş Ayarlar — SEO, Yazar, Hedef Kitle, Slug, Video, Type</summary>
            <div class="ce-accordion-body">

                {{-- SEO --}}
                <div class="ce-subsec">
                    <div class="ce-subsec-title">
                        🔍 SEO & Meta
                        <button type="button" id="ce-ai-seo-btn" class="ce-mini-ai" data-ai-seo-url="{{ url('/mktg-admin/content/ai-suggest-seo') }}">✨ AI Öner</button>
                    </div>
                    <div id="ce-ai-seo-status" style="font-size:11px; color:var(--u-muted,#64748b); margin-bottom:8px; min-height:14px;"></div>
                    <div class="ce-field">
                        <label>Slug <span class="ce-hint">— Boş = otomatik</span></label>
                        <input name="slug" value="{{ old('slug', $editing->slug ?? '') }}" placeholder="otomatik" style="font-family:ui-monospace,monospace; font-size:11.5px;">
                    </div>
                    <div class="ce-field-row-2">
                        <div class="ce-field">
                            <label>SEO Meta Başlığı <span class="ce-hint">— max 60 ch</span></label>
                            <input name="seo_meta_title_tr" maxlength="255" value="{{ old('seo_meta_title_tr', $editing->seo_meta_title_tr ?? '') }}">
                        </div>
                        <div class="ce-field">
                            <label>OG Image URL</label>
                            <input name="seo_og_image_url" value="{{ old('seo_og_image_url', $editing->seo_og_image_url ?? '') }}">
                        </div>
                    </div>
                    <div class="ce-field">
                        <label>SEO Meta Açıklaması <span class="ce-hint">— max 160 ch</span></label>
                        <textarea name="seo_meta_description_tr" maxlength="300" rows="2">{{ old('seo_meta_description_tr', $editing->seo_meta_description_tr ?? '') }}</textarea>
                    </div>
                    <div class="ce-field-row-2">
                        <div class="ce-field">
                            <label>SEO Keywords <span class="ce-hint">— Virgülle</span></label>
                            <input name="seo_keywords" value="{{ $keywords }}" placeholder="berlin, başvuru">
                        </div>
                        <div class="ce-field">
                            <label>Tags <span class="ce-hint">— Virgülle</span></label>
                            <input name="tags" value="{{ $tags }}" placeholder="mühendislik, master">
                        </div>
                    </div>
                    <div class="ce-field">
                        <label>Canonical URL</label>
                        <input name="seo_canonical_url" value="{{ old('seo_canonical_url', $editing->seo_canonical_url ?? '') }}">
                    </div>
                </div>

                {{-- Yazar --}}
                <div class="ce-subsec">
                    <div class="ce-subsec-title">✍️ Yazar <span style="font-weight:400; text-transform:none; color:var(--u-muted,#94a3b8); font-size:10.5px;">(boş = sistem oluşturan)</span></div>
                    <div class="ce-field-row-2">
                        <div class="ce-field">
                            <label>Yazar Adı</label>
                            <input name="author_name" maxlength="120" value="{{ old('author_name', $editing->author_name ?? '') }}" placeholder="örn: Dr. Selen Yılmaz">
                        </div>
                        <div class="ce-field">
                            <label>Rol</label>
                            <input name="author_role" maxlength="80" value="{{ old('author_role', $editing->author_role ?? '') }}" placeholder="Partner, Misafir, Editör">
                        </div>
                    </div>
                </div>

                {{-- Hedef --}}
                <div class="ce-subsec">
                    <div class="ce-subsec-title">🎯 Hedef Kitle & Kampanya</div>
                    <div class="ce-field-row-2">
                        <div class="ce-field">
                            <label>Audience</label>
                            <select name="target_audience">
                                <option value="all" @selected(old('target_audience', $editing->target_audience ?? 'all') === 'all')>🌐 Tüm Kullanıcılar</option>
                                <option value="general" @selected(old('target_audience', $editing->target_audience ?? '') === 'general')>🌍 Genel (kayıtsız ziyaretçi)</option>
                                <option value="guests" @selected(old('target_audience', $editing->target_audience ?? '') === 'guests')>👤 Aday Öğrenci</option>
                                <option value="students" @selected(old('target_audience', $editing->target_audience ?? '') === 'students')>🎓 Öğrenci</option>
                                <option value="parents" @selected(old('target_audience', $editing->target_audience ?? '') === 'parents')>👨‍👩‍👧 Veliler</option>
                            </select>
                        </div>
                        <div class="ce-field">
                            <label>Bağlı Kampanya</label>
                            <select name="linked_campaign_id">
                                <option value="">— Yok —</option>
                                @foreach(($campaignOptions ?? []) as $cmp)
                                    <option value="{{ $cmp->id }}" @selected((string) old('linked_campaign_id', $editing->linked_campaign_id ?? '') === (string) $cmp->id)>#{{ $cmp->id }} {{ $cmp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="ce-field-row-2">
                        <div class="ce-field">
                            <label>Hedef Öğrenci Tipleri</label>
                            <input name="target_student_types" value="{{ $targetTypes }}" placeholder="bachelor, master">
                        </div>
                        <div class="ce-field">
                            <label>Featured Sıra <span class="ce-hint">— 1 en yukarı</span></label>
                            <input name="featured_order" type="number" min="1" max="9999" value="{{ old('featured_order', $editing->featured_order ?? '') }}">
                        </div>
                    </div>
                </div>

                {{-- Video & Tip --}}
                <div class="ce-subsec">
                    <div class="ce-subsec-title">🎬 Video, Galeri & Tip</div>
                    <div class="ce-field-row-2">
                        <div class="ce-field">
                            <label>İçerik Tipi</label>
                            <select name="type">
                                @foreach(($typeOptions ?? []) as $tp)
                                    <option value="{{ $tp }}" @selected(old('type', $editing->type ?? 'blog') === $tp)>{{ $tp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ce-field">
                            <label>Video URL</label>
                            <input name="video_url" value="{{ old('video_url', $editing->video_url ?? '') }}" placeholder="YouTube embed">
                        </div>
                    </div>
                    <div class="ce-field-row-2">
                        <div class="ce-field">
                            <label>Video Thumbnail URL</label>
                            <input name="video_thumbnail_url" value="{{ old('video_thumbnail_url', $editing->video_thumbnail_url ?? '') }}">
                        </div>
                        <div class="ce-field">
                            <label>Galeri URL'leri <span class="ce-hint">— Virgülle</span></label>
                            <input name="gallery_urls" value="{{ $gallery }}">
                        </div>
                    </div>
                </div>
            </div>
        </details>

        {{-- ──────────────── Toolbar ──────────────── --}}
        <div class="ce-toolbar">
            <div class="ce-meta-tiny">
                @if($isEdit)
                    <span><strong>ID:</strong> #{{ $editing->id }}</span>
                    <span><strong>Kod:</strong> {{ $editing->content_code ?: '—' }}</span>
                    <span><strong>Rev:</strong> {{ $editing->current_revision }}</span>
                @else
                    <span>Yeni içerik — kayıt sonrası ID kodu otomatik atanacak</span>
                @endif
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <a href="{{ url('/mktg-admin/content/overview') }}" class="ce-btn-secondary">← İptal</a>
                @if($isEdit)
                    <a href="{{ url('/mktg-admin/content/'.$editing->id.'/pdf') }}" class="ce-btn-secondary" target="_blank">📄 PDF</a>
                    <button type="button" id="ce-btn-delete" class="ce-btn-danger" data-delete-url="{{ url('/mktg-admin/content/'.$editing->id) }}">🗑 Sil</button>
                @endif
                <button type="submit" class="ce-btn-primary">{{ $isEdit ? '💾 Güncelle' : '+ Yarat' }}</button>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js" nonce="{{ $cspNonce ?? '' }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Quill
    var quill = new Quill('#quill-editor-cms', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'blockquote'],
                ['clean'],
            ],
        },
        placeholder: 'İçeriği buraya yaz… (veya yukarıdan AI ile üret)',
    });
    var hidden = document.getElementById('content-tr-hidden');
    var form = document.getElementById('ce-form');
    if (hidden.value) quill.root.innerHTML = hidden.value;
    form.addEventListener('submit', function(){ hidden.value = quill.root.innerHTML; });

    // Common refs
    var titleInput = document.querySelector('input[name="title_tr"]');
    var summaryInput = document.querySelector('textarea[name="summary_tr"]');
    var mainCatSelect = document.querySelector('select[name="category"]');
    var aiCatSelect = document.getElementById('ce-ai-category');
    var urlInput = document.getElementById('cms-cover-url-input');
    var altInput = document.getElementById('cms-cover-alt-input');
    var preview = document.getElementById('cms-cover-preview');
    var coverStatus = document.getElementById('cms-cover-status');
    function syncPreview(url){ if (preview) preview.style.backgroundImage = 'url("' + (url || '') + '")'; }

    // Status chips
    var schedWrap = document.getElementById('ce-scheduled-wrap');
    document.querySelectorAll('.ce-status-chip').forEach(function(chip){
        chip.addEventListener('click', function(){
            document.querySelectorAll('.ce-status-chip').forEach(function(c){ c.classList.remove('active'); });
            chip.classList.add('active');
            var st = chip.dataset.status;
            document.getElementById('ce-status-input').value = st;
            if (schedWrap) schedWrap.style.display = (st === 'scheduled') ? 'block' : 'none';
        });
    });

    // AI category sync
    aiCatSelect.addEventListener('change', function(){
        if (mainCatSelect) mainCatSelect.value = aiCatSelect.value;
    });

    // AI Generate
    var aiBtn = document.getElementById('ce-ai-generate-btn');
    var aiStatus = document.getElementById('ce-ai-status');
    aiBtn.addEventListener('click', function(){
        var topic = (document.getElementById('ce-ai-topic').value || '').trim();
        if (!topic) { aiStatus.textContent = '⚠️ Konu yaz'; aiStatus.style.color = '#dc2626'; return; }
        if ((titleInput.value || summaryInput.value || (hidden.value && hidden.value.length > 20)) && !confirm('Mevcut başlık/özet/içerik DEĞİŞTİRİLECEK. Devam?')) return;
        aiStatus.textContent = '⏳ Gemini içerik üretiyor… (~15-25 sn)'; aiStatus.style.color = '#5b2e91';
        aiBtn.disabled = true;
        var fd = new FormData();
        fd.append('topic', topic);
        fd.append('category', aiCatSelect.value);
        fd.append('language', document.getElementById('ce-ai-language').value);
        fd.append('tone', document.getElementById('ce-ai-tone').value);
        fd.append('word_count', document.getElementById('ce-ai-wordcount').value);
        fetch(aiBtn.dataset.aiUrl, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json'}, body:fd })
        .then(function(r){ return r.json().then(function(j){ return { ok:r.ok, data:j }; }); })
        .then(function(res){
            aiBtn.disabled = false;
            if (res.ok && res.data && res.data.ok && res.data.content) {
                if (res.data.title) titleInput.value = res.data.title;
                if (res.data.summary) summaryInput.value = res.data.summary;
                if (res.data.content) { hidden.value = res.data.content; quill.root.innerHTML = res.data.content; }
                if (mainCatSelect && aiCatSelect.value) mainCatSelect.value = aiCatSelect.value;
                aiStatus.textContent = '✓ Üretildi (' + (res.data.tokens || 0) + ' token). Kontrol et + Kaydet.'; aiStatus.style.color = '#16a34a';
                titleInput.scrollIntoView({ behavior:'smooth', block:'center' });
            } else {
                aiStatus.textContent = '⚠️ ' + ((res.data && res.data.message) || 'Başarısız'); aiStatus.style.color = '#dc2626';
            }
        })
        .catch(function(){ aiBtn.disabled = false; aiStatus.textContent = '⚠️ Bağlantı hatası'; aiStatus.style.color = '#dc2626'; });
    });

    // Cover upload
    var fileInput = document.getElementById('cms-cover-file');
    fileInput.addEventListener('change', function(){
        var f = fileInput.files && fileInput.files[0];
        if (!f) return;
        if (f.size > 5*1024*1024) { coverStatus.textContent = '⚠️ 5 MB üstü'; coverStatus.style.color = '#dc2626'; return; }
        coverStatus.textContent = 'Yükleniyor…'; coverStatus.style.color = '#64748b';
        var fd = new FormData(); fd.append('image', f);
        fetch(fileInput.dataset.uploadUrl, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json'}, body:fd })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.ok && res.url) { urlInput.value = res.url; syncPreview(res.url); coverStatus.textContent = '✓ Yüklendi'; coverStatus.style.color = '#16a34a'; }
            else { coverStatus.textContent = '⚠️ ' + (res.message || 'Hata'); coverStatus.style.color = '#dc2626'; }
        });
    });

    // Wikipedia panel
    var wikiToggleBtn = document.getElementById('ce-wiki-toggle-btn');
    var wikiPanel = document.getElementById('ce-wiki-panel');
    wikiToggleBtn.addEventListener('click', function(){ wikiPanel.style.display = wikiPanel.style.display === 'none' ? 'block' : 'none'; });
    var wikiBtn = document.getElementById('cms-wiki-fetch-btn');
    var wikiInput = document.getElementById('cms-wiki-uni-input');
    var wikiStatus = document.getElementById('cms-wiki-status');
    function runWikiFetch(){
        var name = (wikiInput.value || '').trim();
        if (!name) { wikiStatus.textContent = '⚠️ İsim gerekli'; wikiStatus.style.color = '#dc2626'; return; }
        wikiStatus.textContent = 'Aranıyor…'; wikiStatus.style.color = '#64748b';
        wikiBtn.disabled = true;
        var fd = new FormData(); fd.append('university_name', name);
        fetch(wikiBtn.dataset.fetchUrl, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json'}, body:fd })
        .then(function(r){ return r.json().then(function(j){ return { ok:r.ok, data:j }; }); })
        .then(function(res){
            wikiBtn.disabled = false;
            if (res.ok && res.data && res.data.ok && res.data.url) {
                urlInput.value = res.data.url; syncPreview(res.data.url);
                if (altInput && res.data.attribution) altInput.value = res.data.attribution;
                wikiStatus.textContent = '✓ ' + (res.data.lang || '').toUpperCase() + ' — ' + (res.data.page_title || ''); wikiStatus.style.color = '#16a34a';
            } else { wikiStatus.textContent = '⚠️ ' + ((res.data && res.data.message) || 'Bulunamadı'); wikiStatus.style.color = '#dc2626'; }
        });
    }
    wikiBtn.addEventListener('click', runWikiFetch);
    wikiInput.addEventListener('keydown', function(e){ if (e.key==='Enter') { e.preventDefault(); runWikiFetch(); } });

    // AI Cover Suggest
    var aiCoverBtn = document.getElementById('ce-ai-cover-btn');
    var aiCoverStatus = document.getElementById('ce-ai-cover-status');
    aiCoverBtn.addEventListener('click', function(){
        var topic = (document.getElementById('ce-ai-topic')?.value || '').trim() || (titleInput.value || '').trim() || (summaryInput.value || '').trim();
        if (!topic) { aiCoverStatus.textContent = '⚠️ Önce konu/başlık yaz'; aiCoverStatus.style.color = '#dc2626'; return; }
        if (urlInput.value && !confirm('Mevcut kapak değiştirilecek. Devam?')) return;
        aiCoverStatus.textContent = '⏳ AI öneriyor + Wiki aranıyor…'; aiCoverStatus.style.color = '#5b2e91';
        aiCoverBtn.disabled = true;
        var fd = new FormData(); fd.append('topic', topic);
        if (titleInput.value) fd.append('title', titleInput.value);
        var cv = (mainCatSelect && mainCatSelect.value) || (aiCatSelect && aiCatSelect.value) || ''; if (cv) fd.append('category', cv);
        fetch(aiCoverBtn.dataset.aiCoverUrl, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json'}, body:fd })
        .then(function(r){ return r.json().then(function(j){ return { ok:r.ok, data:j }; }); })
        .then(function(res){
            aiCoverBtn.disabled = false;
            if (res.ok && res.data && res.data.ok && res.data.url) {
                urlInput.value = res.data.url; syncPreview(res.data.url);
                if (altInput && res.data.attribution) altInput.value = res.data.attribution;
                aiCoverStatus.textContent = '✓ "' + (res.data.suggested_keyword || '') + '"'; aiCoverStatus.style.color = '#16a34a';
            } else {
                aiCoverStatus.textContent = '⚠️ ' + ((res.data && res.data.message) || 'Başarısız'); aiCoverStatus.style.color = '#dc2626';
            }
        });
    });

    // AI SEO
    var aiSeoBtn = document.getElementById('ce-ai-seo-btn');
    var aiSeoStatus = document.getElementById('ce-ai-seo-status');
    aiSeoBtn.addEventListener('click', function(){
        var title = (titleInput.value || '').trim();
        if (!title) { aiSeoStatus.textContent = '⚠️ Önce başlık yaz'; aiSeoStatus.style.color = '#dc2626'; return; }
        hidden.value = quill.root.innerHTML;
        var contentText = quill.getText().trim();
        aiSeoStatus.textContent = '⏳ SEO üretiliyor…'; aiSeoStatus.style.color = '#5b2e91';
        aiSeoBtn.disabled = true;
        var fd = new FormData();
        fd.append('title', title);
        if (summaryInput.value) fd.append('summary', summaryInput.value);
        if (contentText) fd.append('content', contentText);
        var cv = (mainCatSelect && mainCatSelect.value) || (aiCatSelect && aiCatSelect.value) || ''; if (cv) fd.append('category', cv);
        fd.append('language', document.getElementById('ce-ai-language')?.value || 'tr');
        fetch(aiSeoBtn.dataset.aiSeoUrl, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json'}, body:fd })
        .then(function(r){ return r.json().then(function(j){ return { ok:r.ok, data:j }; }); })
        .then(function(res){
            aiSeoBtn.disabled = false;
            if (res.ok && res.data && res.data.ok) {
                var t = document.querySelector('input[name="seo_meta_title_tr"]');
                var d = document.querySelector('textarea[name="seo_meta_description_tr"]');
                var k = document.querySelector('input[name="seo_keywords"]');
                var g = document.querySelector('input[name="tags"]');
                if (t && res.data.seo_meta_title) t.value = res.data.seo_meta_title;
                if (d && res.data.seo_meta_description) d.value = res.data.seo_meta_description;
                if (k && Array.isArray(res.data.seo_keywords)) k.value = res.data.seo_keywords.join(', ');
                if (g && Array.isArray(res.data.tags)) g.value = res.data.tags.join(', ');
                aiSeoStatus.textContent = '✓ SEO dolduruldu'; aiSeoStatus.style.color = '#16a34a';
            } else {
                aiSeoStatus.textContent = '⚠️ ' + ((res.data && res.data.message) || 'Başarısız'); aiSeoStatus.style.color = '#dc2626';
            }
        });
    });

    // Delete
    var delBtn = document.getElementById('ce-btn-delete');
    if (delBtn) {
        delBtn.addEventListener('click', function(){
            if (!confirm('Bu içeriği silmek istediğine emin misin? Geri alınamaz.')) return;
            fetch(delBtn.dataset.deleteUrl, {
                method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded'},
                body:'_method=DELETE',
            })
            .then(function(r){ return r.json(); })
            .then(function(res){ if (res.ok) window.location.href = '/mktg-admin/content/overview'; else alert('⚠️ Silme başarısız'); });
        });
    }
})();
</script>
@endsection

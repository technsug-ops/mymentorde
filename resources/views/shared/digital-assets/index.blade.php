@extends($layout)

@section('title', 'Dijital Varlıklar — ' . config('brand.name', 'MentorDE'))

@section('content')
<style>
/* ── DAM unified action buttons (ghost style, color on hover) ───────────── */
.dam-action-row { display:inline-flex; align-items:center; gap:2px; flex-wrap:nowrap; }
.dam-iconbtn {
    width:30px; height:30px;
    display:inline-flex; align-items:center; justify-content:center;
    border:1px solid transparent; background:transparent; color:#64748b;
    border-radius:7px; font-size:14px; line-height:1;
    cursor:pointer; text-decoration:none; padding:0;
    transition:background .12s ease, color .12s ease, border-color .12s ease;
}
.dam-iconbtn:hover { background:#f1f5f9; color:#0f172a; border-color:#e2e8f0; }
.dam-iconbtn:focus-visible { outline:2px solid #6366f1; outline-offset:1px; }
.dam-iconbtn.is-fav-on { color:#f59e0b; }
.dam-iconbtn.is-fav-on:hover { background:#fef3c7; color:#d97706; border-color:#fde68a; }
.dam-iconbtn.v-download:hover { background:#eef2ff; color:#4338ca; border-color:#e0e7ff; }
.dam-iconbtn.v-edit:hover     { background:#eff6ff; color:#1d4ed8; border-color:#dbeafe; }
.dam-iconbtn.v-move:hover     { background:#fef3c7; color:#92400e; border-color:#fde68a; }
.dam-iconbtn.v-share:hover    { background:#dcfce7; color:#15803d; border-color:#bbf7d0; }
.dam-iconbtn.v-delete:hover   { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.dam-iconbtn.v-notify:hover   { background:#eef2ff; color:#3730a3; border-color:#c7d2fe; }
.dam-iconbtn-form { display:inline-flex; margin:0; padding:0; }

/* Grid kart: primary "İndir" butonu ve secondary icon row */
.dam-card-primary {
    display:flex; align-items:center; justify-content:center; gap:5px;
    width:100%; padding:8px 10px;
    background:#0f172a; color:#fff; text-decoration:none;
    border:none; border-radius:7px;
    font-size:12px; font-weight:600;
    cursor:pointer; transition:background .12s;
}
.dam-card-primary:hover { background:#1e293b; }
.dam-card-actions-sec {
    display:flex; justify-content:center; align-items:center; gap:2px;
    margin-top:6px;
}

/* Grid bulk select — checkbox her zaman görünür (subtle), seçili kart mavi çerçeveli */
.dam-grid-check {
    position:absolute; top:6px; left:6px; z-index:3;
    width:26px; height:26px; border-radius:6px;
    background:rgba(255,255,255,.92);
    border:1px solid rgba(148,163,184,.5);
    display:flex; align-items:center; justify-content:center;
    cursor:pointer;
    box-shadow:0 2px 6px rgba(0,0,0,.12);
    transition:background .12s, border-color .12s, transform .12s;
}
.dam-grid-check:hover { background:#fff; border-color:#6366f1; }
.dam-grid-check input {
    width:16px; height:16px; margin:0; cursor:pointer;
}
.dam-card.is-selected {
    box-shadow:0 0 0 2px #6366f1, 0 6px 16px rgba(99,102,241,.18) !important;
    border-color:#6366f1 !important;
}
.dam-card.is-selected .dam-grid-check {
    background:#6366f1; border-color:#4338ca;
}
.dam-card.is-selected .dam-grid-check input { accent-color:#fff; }
</style>
<div class="dam-page" style="padding:24px;">
    <div class="dam-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="margin:0;font-size:24px;font-weight:700;color:var(--text,#0f172a);">
                @if($onlyFavorites ?? false)
                    <span style="margin-right:8px;">⭐</span>Yıldızlılarım
                @else
                    <span style="margin-right:8px;">📁</span>Dijital Varlıklar
                @endif
            </h1>
            <div style="font-size:13px;color:var(--text-muted,#64748b);margin-top:4px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                @if($onlyFavorites ?? false)
                    Yıldızladığınız dosyalar — sadece size özel
                @elseif(empty($breadcrumb))
                    Tüm klasörler
                @else
                    <div>
                    @foreach($breadcrumb as $i => $crumb)
                        @if($i > 0) <span style="margin:0 6px;">›</span> @endif
                        <span>{{ $crumb['name'] }}</span>
                    @endforeach
                    </div>

                    {{-- E1/E2 — Klasör yönetim butonları (rename/move/delete) — sadece admin ve non-system --}}
                    @can('dam.folder.manage')
                        @if($currentFolder && !$currentFolder->is_system)
                        <div style="display:flex;gap:5px;">
                            <button type="button" id="damFolderSettingsBtn"
                                    data-folder-id="{{ $currentFolder->id }}"
                                    data-folder-name="{{ $currentFolder->name }}"
                                    data-folder-description="{{ $currentFolder->description ?? '' }}"
                                    data-folder-roles='@json($currentFolder->allowed_roles ?? [])'
                                    data-update-url="{{ route($routePrefix . '.folder.update', $currentFolder->id) }}"
                                    title="Klasör ayarları (ad, açıklama, yetkiler)"
                                    style="padding:3px 8px;font-size:11px;border:1px solid #e2e8f0;background:#fff;border-radius:5px;cursor:pointer">⚙ Ayarlar</button>

                            <button type="button" id="damFolderMoveBtn"
                                    data-folder-id="{{ $currentFolder->id }}"
                                    data-move-url="{{ route($routePrefix . '.folder.move', $currentFolder->id) }}"
                                    title="Klasörü başka yere taşı"
                                    style="padding:3px 8px;font-size:11px;border:1px solid #e2e8f0;background:#fff;border-radius:5px;cursor:pointer">➜ Taşı</button>

                            <form method="POST" action="{{ route($routePrefix . '.folder.destroy', $currentFolder->id) }}" style="display:inline"
                                  onsubmit="return confirm('{{ $currentFolder->name }} klasörünü silmek istediğinize emin misiniz? Klasör boş olmalı.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        style="padding:3px 8px;font-size:11px;border:1px solid #fecaca;background:#fef2f2;color:#dc2626;border-radius:5px;cursor:pointer">✕ Sil</button>
                            </form>
                        </div>
                        @endif
                    @endcan
                @endif
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            @can('dam.folder.manage')
                <a href="{{ route($routePrefix . '.reports') }}"
                   style="padding:10px 16px;border-radius:8px;border:1px solid var(--u-line,#e2e8f0);background:#fff;color:var(--u-text,#0f172a);text-decoration:none;font-weight:600;font-size:13px;display:flex;align-items:center;gap:6px;"
                   title="DAM Raporları">
                    📊 Raporlar
                </a>
            @endcan
        @unless($readOnly)
            @if(auth()->user()?->hasPermissionCode('dam.upload') || auth()->user()?->hasPermissionCode('dam.folder.manage'))
            <div style="position:relative;" id="dam-add-wrapper">
                <button type="button" id="dam-add-btn"
                        style="padding:10px 20px;border-radius:8px;border:none;background:var(--c-accent,#0f172a);color:#fff;cursor:pointer;font-weight:600;font-size:14px;display:flex;align-items:center;gap:6px;">
                    <span style="font-size:18px;line-height:1;">+</span> Ekle
                </button>
                <div id="dam-add-menu"
                     style="display:none;position:absolute;top:calc(100% + 6px);right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.12);min-width:220px;z-index:100;overflow:hidden;">
                    @can('dam.upload')
                    <button type="button" data-dam-action="upload"
                            style="display:flex;align-items:center;gap:10px;width:100%;padding:12px 14px;border:none;background:#fff;cursor:pointer;font-size:14px;color:#0f172a;text-align:left;">
                        <span style="font-size:18px;">↑</span>
                        <span>
                            <div style="font-weight:600;">Dosya Yükle</div>
                            <div style="font-size:11px;color:#64748b;">Bir veya birden fazla dosya</div>
                        </span>
                    </button>
                    <button type="button" data-dam-action="link"
                            style="display:flex;align-items:center;gap:10px;width:100%;padding:12px 14px;border:none;background:#fff;cursor:pointer;font-size:14px;color:#0f172a;text-align:left;border-top:1px solid #f1f5f9;">
                        <span style="font-size:18px;">🔗</span>
                        <span>
                            <div style="font-weight:600;">Link Ekle</div>
                            <div style="font-size:11px;color:#64748b;">Drive, YouTube, Notion vb.</div>
                        </span>
                    </button>
                    @endcan
                    @can('dam.folder.manage')
                    <button type="button" data-dam-action="folder"
                            style="display:flex;align-items:center;gap:10px;width:100%;padding:12px 14px;border:none;background:#fff;cursor:pointer;font-size:14px;color:#0f172a;text-align:left;border-top:1px solid #f1f5f9;">
                        <span style="font-size:18px;">📁</span>
                        <span>
                            <div style="font-weight:600;">Yeni Klasör</div>
                            <div style="font-size:11px;color:#64748b;">Dosyaları gruplamak için</div>
                        </span>
                    </button>
                    @endcan
                </div>
            </div>
            @endif
        @endunless
        </div>
    </div>

    @if(session('status'))
        <div style="padding:12px 16px;background:#dcfce7;color:#166534;border-radius:8px;margin-bottom:16px;">
            {{ session('status') }}
        </div>
    @endif

    {{-- Arama + hızlı filtreler --}}
    <form method="GET" action="{{ route($routePrefix . '.index') }}" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin-bottom:16px;">
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <div style="flex:1;min-width:220px;position:relative;">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:14px;color:#94a3b8;">🔍</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="İsim, açıklama, DOC kodu..."
                       style="width:100%;padding:10px 12px 10px 36px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;background:#f8fafc;">
            </div>
            <select name="category" style="padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;">
                <option value="">Tüm türler</option>
                <option value="image"    @selected(($filters['category'] ?? '') === 'image')>🖼️ Görsel</option>
                <option value="video"    @selected(($filters['category'] ?? '') === 'video')>🎬 Video</option>
                <option value="audio"    @selected(($filters['category'] ?? '') === 'audio')>🎵 Ses</option>
                <option value="document" @selected(($filters['category'] ?? '') === 'document')>📄 Doküman</option>
                <option value="archive"  @selected(($filters['category'] ?? '') === 'archive')>🗜️ Arşiv</option>
                <option value="other"    @selected(($filters['category'] ?? '') === 'other')>📎 Diğer</option>
            </select>
            <button type="submit"
                    style="padding:10px 20px;border-radius:8px;border:none;background:var(--c-accent,#0f172a);color:#fff;cursor:pointer;font-weight:600;font-size:13px;">
                Ara
            </button>
            @if($hasFilters)
                <a href="{{ route($routePrefix . '.index') }}{{ $currentFolder ? '' : '' }}"
                   style="padding:10px 14px;border-radius:8px;border:1px solid #fecaca;background:#fef2f2;color:#dc2626;text-decoration:none;font-size:13px;font-weight:600;">
                    ✕ Temizle
                </a>
                {{-- DAM5 — Mevcut aramayı kaydet --}}
                <button type="button" id="dam-save-search-btn"
                        style="padding:10px 14px;border-radius:8px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;cursor:pointer;font-size:13px;font-weight:600;"
                        title="Bu filtre kombinasyonunu kaydet">
                    💾 Kaydet
                </button>
            @endif
            {{-- DAM5 — Kayıtlı aramalar dropdown --}}
            @if(!empty($savedSearches) && $savedSearches->count() > 0)
                <div style="position:relative;" id="dam-saved-wrapper">
                    <button type="button" id="dam-saved-btn"
                            style="padding:10px 14px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;cursor:pointer;font-size:13px;font-weight:600;">
                        📂 Kayıtlılar ({{ $savedSearches->count() }})
                    </button>
                    <div id="dam-saved-menu"
                         style="display:none;position:absolute;top:calc(100% + 6px);right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.12);min-width:260px;max-height:320px;overflow-y:auto;z-index:100;">
                        @foreach($savedSearches as $ss)
                            @php
                                $params = is_array($ss->query_params) ? $ss->query_params : [];
                                $qs     = http_build_query($params);
                                $ssUrl  = route($routePrefix . '.index') . ($qs ? '?' . $qs : '');
                            @endphp
                            <div style="display:flex;align-items:center;gap:4px;border-bottom:1px solid #f1f5f9;">
                                <a href="{{ $ssUrl }}"
                                   style="flex:1;padding:10px 12px;color:var(--u-text);text-decoration:none;font-size:13px;">
                                    {{ $ss->name }}
                                </a>
                                <form method="POST" action="{{ route($routePrefix . '.saved-search.destroy', $ss->id) }}" style="display:inline"
                                      onsubmit="return confirm('{{ $ss->name }} aramasını silmek istediğinize emin misiniz?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;padding:8px 10px;font-size:13px;" title="Sil">✕</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        {{-- Gelişmiş filtreler (collapse) — E6 --}}
        <details style="margin-top:10px;" @if(!empty($filters['uploader']) || !empty($filters['size_min']) || !empty($filters['size_max']) || !empty($filters['from']) || !empty($filters['to'])) open @endif>
            <summary style="cursor:pointer;font-size:12px;color:#64748b;user-select:none;">Gelişmiş filtreler</summary>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;">
                {{-- Tarih aralığı --}}
                <label style="font-size:12px;color:#64748b;display:flex;flex-direction:column;gap:3px;">
                    <span>Başlangıç tarihi</span>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                           style="padding:7px 9px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;">
                </label>
                <label style="font-size:12px;color:#64748b;display:flex;flex-direction:column;gap:3px;">
                    <span>Bitiş tarihi</span>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                           style="padding:7px 9px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;">
                </label>

                {{-- E6: Uploader filter --}}
                <label style="font-size:12px;color:#64748b;display:flex;flex-direction:column;gap:3px;">
                    <span>Yükleyen kişi</span>
                    <select name="uploader" style="padding:7px 9px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;background:#fff;">
                        <option value="">Herkes</option>
                        @foreach(($uploaderList ?? collect()) as $uu)
                            <option value="{{ $uu->id }}" @selected((int)($filters['uploader'] ?? 0) === (int)$uu->id)>{{ $uu->name }}</option>
                        @endforeach
                    </select>
                </label>

                {{-- E6: Size range --}}
                <label style="font-size:12px;color:#64748b;display:flex;flex-direction:column;gap:3px;">
                    <span>Min boyut (MB)</span>
                    <input type="number" name="size_min_mb" min="0" step="0.1"
                           value="{{ !empty($filters['size_min']) ? round($filters['size_min']/1024/1024, 2) : '' }}"
                           style="padding:7px 9px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;">
                </label>
                <label style="font-size:12px;color:#64748b;display:flex;flex-direction:column;gap:3px;">
                    <span>Max boyut (MB)</span>
                    <input type="number" name="size_max_mb" min="0" step="0.1"
                           value="{{ !empty($filters['size_max']) ? round($filters['size_max']/1024/1024, 2) : '' }}"
                           style="padding:7px 9px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;">
                </label>

                @if(!empty($filters['tag']))
                    <span style="grid-column:span 2;display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#e0e7ff;color:#3730a3;border-radius:99px;font-size:11px;font-weight:600;align-self:end">
                        Etiket: {{ $filters['tag'] }}
                        <input type="hidden" name="tag" value="{{ $filters['tag'] }}">
                    </span>
                @endif
            </div>
        </details>
        @if($hasFilters)
            <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;font-size:12px;color:#64748b;">
                <strong style="color:#0f172a;">{{ $assets->total() }}</strong> sonuç bulundu · Filtre aktif, tüm klasörlerde aranıyor
            </div>
        @endif
    </form>

    {{-- Popüler etiketler şeridi — sadece tag varsa görünür --}}
    @if(!empty($popularTags))
    <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-bottom:16px;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
        <span style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-right:4px;">🏷️ Popüler</span>
        @foreach($popularTags as $tagName => $tagCount)
            @php $isActive = (($filters['tag'] ?? '') === $tagName); @endphp
            <a href="{{ route($routePrefix . '.index') }}?tag={{ urlencode($tagName) }}"
               style="font-size:11px;padding:4px 10px;text-decoration:none;border-radius:99px;font-weight:600;white-space:nowrap;
                      {{ $isActive ? 'background:var(--c-accent,#0f172a);color:#fff;' : 'background:#fff;color:#475569;border:1px solid #e2e8f0;' }}">
                {{ $tagName }} <span style="opacity:.55;font-weight:500;">{{ $tagCount }}</span>
            </a>
        @endforeach
    </div>
    @endif

    <div class="dam-layout" style="display:grid;grid-template-columns:260px 1fr;gap:20px;align-items:start;">
        {{-- Sol panel: klasör ağacı --}}
        <aside class="dam-sidebar" style="background:#fff;border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px;position:sticky;top:20px;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted,#64748b);margin-bottom:10px;">
                Klasörler
            </div>
            <a href="{{ route($routePrefix . '.index') }}"
               style="display:block;padding:8px 10px;border-radius:6px;text-decoration:none;color:var(--text,#0f172a);font-size:14px;{{ empty($currentFolder) && !($onlyFavorites ?? false) ? 'background:var(--accent-soft,#f1f5f9);font-weight:600;' : '' }}">
                🏠 Tüm Varlıklar
            </a>
            <a href="{{ route($routePrefix . '.favorites') }}"
               style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:6px;text-decoration:none;color:var(--text,#0f172a);font-size:14px;margin-top:2px;{{ ($onlyFavorites ?? false) ? 'background:var(--accent-soft,#f1f5f9);font-weight:600;' : '' }}">
                <span>⭐ Yıldızlılarım</span>
                @if(($favoriteCount ?? 0) > 0)
                    <span style="background:#fbbf24;color:#78350f;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;">{{ $favoriteCount }}</span>
                @endif
            </a>
            <div style="height:1px;background:#f1f5f9;margin:8px 0;"></div>
            @include('shared.digital-assets._folder_tree', [
                'nodes'             => $tree,
                'level'             => 0,
                'currentId'         => $currentFolder?->id,
                'routePrefix'       => $routePrefix,
                'favoriteFolderIds' => $favoriteFolderIds ?? [],
            ])
        </aside>

        {{-- Sağ panel: dosya grid/list --}}
        <main class="dam-main" style="min-width:0;">
            {{-- Görünüm toggle + (liste modunda) kolon seçici --}}
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px;align-items:center;position:relative;">
                @if($viewMode === 'list')
                <div style="position:relative;" id="dam-cols-wrapper">
                    <button type="button" id="dam-cols-btn"
                            style="padding:8px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;color:#64748b;display:flex;align-items:center;gap:6px;">
                        ⚙ Kolonlar
                    </button>
                    <div id="dam-cols-menu"
                         style="display:none;position:absolute;top:calc(100% + 6px);right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.12);min-width:220px;z-index:100;padding:10px 14px;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.5px;margin-bottom:8px;">Gösterilecek Kolonlar</div>
                        @php
                            $colOptions = [
                                'type'     => 'Tür',
                                'category' => 'Kategori',
                                'tags'     => 'Etiketler',
                                'doc'      => 'DOC Kodu',
                                'size'     => 'Boyut',
                                'uploader' => 'Yükleyen',
                                'date'     => 'Tarih',
                            ];
                        @endphp
                        @foreach($colOptions as $key => $label)
                            <label style="display:flex;align-items:center;gap:8px;padding:5px 2px;cursor:pointer;font-size:13px;color:#334155;">
                                <input type="checkbox" class="dam-col-toggle" data-col-key="{{ $key }}" checked>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                        <div style="font-size:10px;color:#94a3b8;margin-top:8px;padding-top:8px;border-top:1px solid #f1f5f9;">
                            Ad ve İşlem kolonları her zaman açık. Tercihiniz kaydedilir.
                        </div>
                    </div>
                </div>
                @endif
                <div style="display:inline-flex;background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                    @php
                        $qs = request()->except('view');
                        $gridUrl = request()->url() . '?' . http_build_query(array_merge($qs, ['view' => 'grid']));
                        $listUrl = request()->url() . '?' . http_build_query(array_merge($qs, ['view' => 'list']));
                    @endphp
                    <a href="{{ $gridUrl }}"
                       style="padding:8px 14px;text-decoration:none;font-size:13px;font-weight:600;{{ $viewMode === 'grid' ? 'background:var(--c-accent,#0f172a);color:#fff;' : 'color:#64748b;' }}"
                       title="Büyük görünüm">
                        ⊞ Grid
                    </a>
                    <a href="{{ $listUrl }}"
                       style="padding:8px 14px;text-decoration:none;font-size:13px;font-weight:600;border-left:1px solid #e2e8f0;{{ $viewMode === 'list' ? 'background:var(--c-accent,#0f172a);color:#fff;' : 'color:#64748b;' }}"
                       title="Liste görünümü">
                        ☰ Liste
                    </a>
                </div>
            </div>

            {{-- DAM3 — Bulk action bar (liste + grid ortak). Seçim >0 olunca görünür. --}}
            <div id="dam-bulk-bar" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;margin-bottom:10px;align-items:center;gap:12px;">
                <span id="dam-bulk-count" style="font-size:13px;font-weight:700;color:#1e40af">0 dosya seçili</span>
                <div style="flex:1"></div>
                <form method="POST" action="{{ route($routePrefix . '.bulk.download') }}" id="dam-bulk-form" style="display:inline">
                    @csrf
                    <button type="submit" class="btn ok" style="padding:7px 14px;font-size:12px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:6px;cursor:pointer">
                        📦 Seçilenleri ZIP İndir
                    </button>
                </form>
                <button type="button" id="dam-bulk-clear" style="padding:7px 12px;font-size:12px;font-weight:600;background:#fff;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer">
                    Temizle
                </button>
            </div>

            @if($viewMode === 'list')
                @include('shared.digital-assets._asset_list', ['assets' => $assets, 'readOnly' => $readOnly, 'routePrefix' => $routePrefix, 'favoriteIds' => $favoriteIds, 'sortKey' => $sortKey, 'sortDir' => $sortDir])
            @else
                @include('shared.digital-assets._asset_grid', ['assets' => $assets, 'readOnly' => $readOnly, 'routePrefix' => $routePrefix, 'favoriteIds' => $favoriteIds])
            @endif
        </main>
    </div>

    @unless($readOnly)
        @can('dam.upload')
            @include('shared.digital-assets._upload_modal', ['currentFolder' => $currentFolder, 'routePrefix' => $routePrefix, 'mentionableUsers' => $mentionableUsers ?? collect(), 'mentionRoleGroups' => $mentionRoleGroups ?? []])
            @include('shared.digital-assets._link_modal', ['currentFolder' => $currentFolder, 'routePrefix' => $routePrefix])
        @endcan
        @can('dam.folder.manage')
            @include('shared.digital-assets._folder_modal', ['currentFolder' => $currentFolder, 'routePrefix' => $routePrefix])
            @include('shared.digital-assets._folder_edit_modal')
        @endcan
        @can('dam.update')
            @include('shared.digital-assets._edit_modal')
            @include('shared.digital-assets._share_modal')
        @endcan
        @can('dam.upload')
            @include('shared.digital-assets._notify_modal', ['mentionableUsers' => $mentionableUsers ?? collect(), 'mentionRoleGroups' => $mentionRoleGroups ?? []])
        @endcan
    @endunless

    {{-- Lightbox (büyük önizleme) --}}
    <dialog id="dam-lightbox" style="border:none;border-radius:14px;padding:0;max-width:1100px;width:92vw;max-height:92vh;box-shadow:0 30px 80px rgba(0,0,0,.4);background:#fff;">
        <div style="display:grid;grid-template-columns:1fr 320px;min-height:500px;max-height:92vh;">
            {{-- Sol: önizleme --}}
            <div id="dam-lb-preview" style="background:#0f172a;display:flex;align-items:center;justify-content:center;padding:20px;overflow:auto;min-height:400px;color:#fff;">
                <div id="dam-lb-loading" style="text-align:center;color:#94a3b8;">Yükleniyor...</div>
            </div>
            {{-- Sağ: bilgi paneli --}}
            <div style="padding:24px;display:flex;flex-direction:column;gap:14px;border-left:1px solid #e2e8f0;background:#fff;overflow:auto;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                    <h3 id="dam-lb-name" style="margin:0;font-size:16px;font-weight:700;color:#0f172a;line-height:1.4;word-break:break-word;"></h3>
                    <button type="button" onclick="document.getElementById('dam-lightbox').close()"
                            style="background:none;border:none;font-size:24px;cursor:pointer;color:#64748b;line-height:1;flex-shrink:0;">×</button>
                </div>
                <div id="dam-lb-meta" style="font-size:12px;color:#64748b;display:flex;flex-direction:column;gap:6px;"></div>
                <div id="dam-lb-description" style="font-size:13px;color:#334155;line-height:1.5;display:none;"></div>
                <div id="dam-lb-tags" style="display:flex;flex-wrap:wrap;gap:4px;"></div>
                <div id="dam-lb-actions" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:auto;padding-top:14px;border-top:1px solid #f1f5f9;"></div>
            </div>
        </div>
    </dialog>
</div>

{{-- DAM sayfası etkileşim script'i --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // ── "+ Ekle" dropdown ──────────────────────────────────────
    const btn   = document.getElementById('dam-add-btn');
    const menu  = document.getElementById('dam-add-menu');
    const wrap  = document.getElementById('dam-add-wrapper');
    if (btn && menu) {
        btn.addEventListener('click', function(e){
            e.stopPropagation();
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        });

        document.addEventListener('click', function(e){
            if (wrap && !wrap.contains(e.target)) menu.style.display = 'none';
        });

        const actionDialogMap = {
            upload: 'dam-upload-modal',
            link:   'dam-link-modal',
            folder: 'dam-folder-modal',
        };
        menu.querySelectorAll('[data-dam-action]').forEach(function(el){
            el.addEventListener('mouseenter', function(){ el.style.background = '#f1f5f9'; });
            el.addEventListener('mouseleave', function(){ el.style.background = '#fff'; });
            el.addEventListener('click', function(){
                menu.style.display = 'none';
                const action = el.getAttribute('data-dam-action');
                const dialogId = actionDialogMap[action];
                if (!dialogId) return;
                const dlg = document.getElementById(dialogId);
                if (dlg && typeof dlg.showModal === 'function') dlg.showModal();
            });
        });
    }

    // ── Lightbox (kart tıklama → büyük önizleme) ───────────────
    const lightbox    = document.getElementById('dam-lightbox');
    const lbPreview   = document.getElementById('dam-lb-preview');
    const lbName      = document.getElementById('dam-lb-name');
    const lbMeta      = document.getElementById('dam-lb-meta');
    const lbDesc      = document.getElementById('dam-lb-description');
    const lbTags      = document.getElementById('dam-lb-tags');
    const lbActions   = document.getElementById('dam-lb-actions');

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function(c){
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }

    function openLightbox(card) {
        if (!lightbox || !card) return;
        const data = {
            id:          card.getAttribute('data-asset-id'),
            name:        card.getAttribute('data-asset-name'),
            description: card.getAttribute('data-asset-description'),
            tags:        JSON.parse(card.getAttribute('data-asset-tags') || '[]'),
            category:    card.getAttribute('data-asset-category'),
            mime:        card.getAttribute('data-asset-mime'),
            extension:   card.getAttribute('data-asset-extension'),
            size:        card.getAttribute('data-asset-size'),
            doc:         card.getAttribute('data-asset-doc'),
            creator:     card.getAttribute('data-asset-creator'),
            date:        card.getAttribute('data-asset-date'),
            source:      card.getAttribute('data-asset-source'),
            url:         card.getAttribute('data-asset-url'),
            preview:     card.getAttribute('data-asset-preview'),
            download:    card.getAttribute('data-asset-download'),
            emoji:       card.getAttribute('data-asset-emoji') || '📎',
            youtubeId:   card.getAttribute('data-asset-youtube') || '',
        };

        lbName.textContent = data.name;

        // Önizleme alanı
        let previewHtml = '';
        if (data.source === 'link' && data.youtubeId) {
            // YouTube — nocookie embed
            previewHtml =
                '<iframe src="https://www.youtube-nocookie.com/embed/' + escapeHtml(data.youtubeId) + '" ' +
                'style="width:100%;aspect-ratio:16/9;max-height:80vh;border:none;border-radius:6px;background:#000;" ' +
                'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" ' +
                'allowfullscreen></iframe>';
        } else if (data.source === 'link') {
            previewHtml =
                '<div style="text-align:center;padding:40px 20px;">' +
                '<div style="font-size:96px;margin-bottom:20px;">' + data.emoji + '</div>' +
                '<div style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:8px;">Harici Bağlantı</div>' +
                '<div style="font-size:13px;color:#cbd5e1;margin-bottom:24px;word-break:break-all;max-width:500px;">' + escapeHtml(data.url) + '</div>' +
                '<a href="' + escapeHtml(data.url) + '" target="_blank" rel="noopener" ' +
                'style="display:inline-block;padding:12px 24px;background:#3730a3;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">' +
                '🔗 Yeni Sekmede Aç' +
                '</a>' +
                '</div>';
        } else if (data.category === 'image' && data.preview) {
            previewHtml = '<img src="' + escapeHtml(data.preview) + '" alt="' + escapeHtml(data.name) + '" ' +
                'style="max-width:100%;max-height:80vh;object-fit:contain;border-radius:6px;">';
        } else if (data.mime === 'application/pdf' && data.preview) {
            previewHtml = '<iframe src="' + escapeHtml(data.preview) + '" ' +
                'style="width:100%;height:80vh;border:none;background:#fff;border-radius:6px;"></iframe>';
        } else {
            previewHtml =
                '<div style="text-align:center;padding:40px 20px;">' +
                '<div style="font-size:96px;margin-bottom:20px;">' + data.emoji + '</div>' +
                '<div style="font-size:13px;color:#94a3b8;margin-bottom:24px;">Bu dosya türü için önizleme yok.</div>' +
                '<a href="' + escapeHtml(data.download) + '" ' +
                'style="display:inline-block;padding:12px 24px;background:#1d4ed8;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">' +
                '↓ Dosyayı İndir' +
                '</a>' +
                '</div>';
        }
        lbPreview.innerHTML = previewHtml;

        // Metadata
        const metaRows = [];
        if (data.doc)       metaRows.push('<div><strong style="color:#0f172a;">DOC:</strong> <span style="font-family:monospace;">' + escapeHtml(data.doc) + '</span></div>');
        if (data.size && data.source !== 'link') metaRows.push('<div><strong style="color:#0f172a;">Boyut:</strong> ' + escapeHtml(data.size) + '</div>');
        if (data.extension) metaRows.push('<div><strong style="color:#0f172a;">Tür:</strong> ' + escapeHtml(data.extension.toUpperCase()) + '</div>');
        if (data.creator)   metaRows.push('<div><strong style="color:#0f172a;">Yükleyen:</strong> ' + escapeHtml(data.creator) + '</div>');
        if (data.date)      metaRows.push('<div><strong style="color:#0f172a;">Tarih:</strong> ' + escapeHtml(data.date) + '</div>');
        lbMeta.innerHTML = metaRows.join('');

        // Açıklama
        if (data.description) {
            lbDesc.style.display = 'block';
            lbDesc.textContent = data.description;
        } else {
            lbDesc.style.display = 'none';
        }

        // Etiketler
        lbTags.innerHTML = '';
        if (Array.isArray(data.tags) && data.tags.length > 0) {
            data.tags.forEach(function(t){
                const chip = document.createElement('span');
                chip.style.cssText = 'font-size:10px;padding:3px 8px;background:#e0e7ff;color:#3730a3;border-radius:99px;font-weight:600;';
                chip.textContent = t;
                lbTags.appendChild(chip);
            });
        }

        // Aksiyon butonları
        const downloadLabel = data.source === 'link' ? '🔗 Linke Git' : '↓ İndir';
        const downloadAttrs = data.source === 'link' ? ' target="_blank" rel="noopener"' : '';
        lbActions.innerHTML =
            '<a href="' + escapeHtml(data.download) + '"' + downloadAttrs + ' ' +
            'style="flex:1;text-align:center;padding:8px 12px;background:var(--c-accent,#0f172a);color:#fff;text-decoration:none;border-radius:6px;font-weight:600;font-size:13px;">' +
            downloadLabel +
            '</a>';

        if (typeof lightbox.showModal === 'function') {
            lightbox.showModal();
        }
    }

    // Kart click handler:
    //   - Buton/link/form → lightbox açma (data-stop-card-click ile korunuyor)
    //   - Herhangi bir kart seçiliyse (selection mode) → tıklama seçim toggle eder
    //   - Hiçbir şey seçili değilse → tıklama lightbox açar
    function anySelected() {
        return document.querySelector('.dam-row-check:checked') !== null;
    }
    document.querySelectorAll('.dam-card').forEach(function(card){
        card.addEventListener('click', function(e){
            // Buton/link/form içine tıklandıysa özel handler çalışacak → bize gerek yok
            if (e.target.closest('[data-stop-card-click="1"]')) return;
            if (e.target.closest('.dam-fav-btn')) return;
            if (e.target.closest('button')) return;
            if (e.target.closest('a[href]') && !e.target.closest('.dam-card-preview')) return;

            // Selection mode aktifse → seçim toggle
            if (anySelected()) {
                const cb = card.querySelector('.dam-row-check');
                if (cb) {
                    cb.checked = !cb.checked;
                    updateBulkBar();
                }
                return;
            }

            openLightbox(card);
        });
    });

    // ESC kapatır (browser default'u olabilir ama emin olalım)
    if (lightbox) {
        lightbox.addEventListener('click', function(e){
            // Backdrop'a tıklayınca kapat
            if (e.target === lightbox) lightbox.close();
        });
    }

    // ── Kolon seçici (localStorage) ─────────────────────────────
    const COL_STORAGE_KEY = 'dam_hidden_columns_v1';
    // Varsayılan olarak gizli: DOC Kodu (kullanıcı çok geniş buluyor)
    const COL_DEFAULT_HIDDEN = ['doc'];

    function loadHiddenCols() {
        try {
            const raw = localStorage.getItem(COL_STORAGE_KEY);
            if (raw === null) return COL_DEFAULT_HIDDEN.slice();
            const arr = JSON.parse(raw);
            return Array.isArray(arr) ? arr : COL_DEFAULT_HIDDEN.slice();
        } catch (e) {
            return COL_DEFAULT_HIDDEN.slice();
        }
    }

    function saveHiddenCols(list) {
        try { localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(list)); } catch (e) {}
    }

    function applyColumnVisibility(hiddenList) {
        const hidden = new Set(hiddenList);
        document.querySelectorAll('#dam-list-table [data-col]').forEach(function(el){
            const key = el.getAttribute('data-col');
            if (key === 'name' || key === 'actions' || key === 'select') return; // zorunlu
            el.style.display = hidden.has(key) ? 'none' : '';
        });
    }

    // Uygula (tablonun ilk renderında)
    const initialHidden = loadHiddenCols();
    applyColumnVisibility(initialHidden);

    // Checkbox'ları uyumla (checked = kolon görünür)
    document.querySelectorAll('.dam-col-toggle').forEach(function(cb){
        const key = cb.getAttribute('data-col-key');
        cb.checked = !initialHidden.includes(key);
        cb.addEventListener('change', function(){
            const current = loadHiddenCols();
            const idx = current.indexOf(key);
            if (cb.checked && idx > -1) current.splice(idx, 1);
            else if (!cb.checked && idx === -1) current.push(key);
            saveHiddenCols(current);
            applyColumnVisibility(current);
        });
    });

    // Kolonlar butonu dropdown toggle
    const colsBtn   = document.getElementById('dam-cols-btn');
    const colsMenu  = document.getElementById('dam-cols-menu');
    const colsWrap  = document.getElementById('dam-cols-wrapper');
    if (colsBtn && colsMenu) {
        colsBtn.addEventListener('click', function(e){
            e.stopPropagation();
            colsMenu.style.display = (colsMenu.style.display === 'block') ? 'none' : 'block';
        });
        document.addEventListener('click', function(e){
            if (colsWrap && !colsWrap.contains(e.target)) colsMenu.style.display = 'none';
        });
    }

    // ── DAM5 — Saved searches dropdown toggle ──────────────────
    const savedBtn   = document.getElementById('dam-saved-btn');
    const savedMenu  = document.getElementById('dam-saved-menu');
    const savedWrap  = document.getElementById('dam-saved-wrapper');
    if (savedBtn && savedMenu) {
        savedBtn.addEventListener('click', function(e){
            e.stopPropagation();
            savedMenu.style.display = (savedMenu.style.display === 'block') ? 'none' : 'block';
        });
        document.addEventListener('click', function(e){
            if (savedWrap && !savedWrap.contains(e.target)) savedMenu.style.display = 'none';
        });
    }

    // ── DAM5 — "Aramayı Kaydet" butonu: mevcut query + name ile POST ──
    const saveSearchBtn = document.getElementById('dam-save-search-btn');
    if (saveSearchBtn) {
        saveSearchBtn.addEventListener('click', function(){
            const name = window.prompt('Bu arama için bir isim girin:');
            if (!name || name.trim() === '') return;

            const f = document.createElement('form');
            f.method = 'POST';
            f.action = '{{ route($routePrefix . ".saved-search.store") }}';

            // CSRF
            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
            f.appendChild(csrf);

            // Name + mevcut query params
            const nameInput = document.createElement('input');
            nameInput.type = 'hidden'; nameInput.name = 'name'; nameInput.value = name.trim();
            f.appendChild(nameInput);

            // Mevcut URL'deki query params'ı kopyala
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.forEach(function(v, k){
                if (k === '_token' || k === 'name') return;
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = k; inp.value = v;
                f.appendChild(inp);
            });

            document.body.appendChild(f);
            f.submit();
        });
    }

    // ── DAM3 — Bulk selection + download ───────────────────────
    const bulkBar       = document.getElementById('dam-bulk-bar');
    const bulkCount     = document.getElementById('dam-bulk-count');
    const bulkForm      = document.getElementById('dam-bulk-form');
    const bulkClearBtn  = document.getElementById('dam-bulk-clear');
    const bulkSelectAll = document.getElementById('dam-bulk-select-all');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.dam-row-check:checked');
        const n = checked.length;
        if (bulkBar) bulkBar.style.display = n > 0 ? 'flex' : 'none';
        if (bulkCount) bulkCount.textContent = n + ' dosya seçili';

        // Bulk form'u güncelle — eski hidden input'ları sil, yenilerini ekle
        if (bulkForm) {
            bulkForm.querySelectorAll('input[name="asset_ids[]"]').forEach(i => i.remove());
            checked.forEach(function(cb){
                const hid = document.createElement('input');
                hid.type = 'hidden'; hid.name = 'asset_ids[]'; hid.value = cb.value;
                bulkForm.appendChild(hid);
            });
        }

        // Grid kartlarında seçili state'i senkronize et
        document.querySelectorAll('.dam-card').forEach(function(card){
            const cb = card.querySelector('.dam-row-check');
            if (cb) card.classList.toggle('is-selected', cb.checked);
        });
    }

    document.querySelectorAll('.dam-row-check').forEach(function(cb){
        cb.addEventListener('change', updateBulkBar);
    });

    // Grid kartlarında: kart gövdesine tıklama → seçim VARSA seçim toggle, YOKSA lightbox.
    // Checkbox'a tıklama → sadece seçim (lightbox açılmaz, stop-card-click ile).
    document.querySelectorAll('.dam-card').forEach(function(card){
        const cb = card.querySelector('.dam-row-check');
        if (!cb) return;
        // Checkbox kendi change'iyle updateBulkBar'ı tetikler; ek bir şey gerekmez.
    });

    if (bulkSelectAll) {
        bulkSelectAll.addEventListener('change', function(){
            document.querySelectorAll('.dam-row-check').forEach(function(cb){
                cb.checked = bulkSelectAll.checked;
            });
            updateBulkBar();
        });
    }

    if (bulkClearBtn) {
        bulkClearBtn.addEventListener('click', function(){
            document.querySelectorAll('.dam-row-check').forEach(cb => cb.checked = false);
            if (bulkSelectAll) bulkSelectAll.checked = false;
            updateBulkBar();
        });
    }

    // ── Post-upload mention modal (📢 Bildir) ──────────────────
    const notifyModal = document.getElementById('dam-notify-modal');
    const notifyForm  = document.getElementById('dam-notify-form');
    const notifyName  = document.getElementById('dam-notify-asset-name');
    const notifyCount = document.getElementById('dam-nm-count');
    const notifyWarn  = document.getElementById('dam-nm-warn');
    const notifySearch= document.getElementById('dam-nm-search');

    function resetNotifyModal() {
        if (!notifyForm) return;
        notifyForm.querySelectorAll('.dam-nm-cb, .dam-nm-group-cb').forEach(cb => cb.checked = false);
        const noteInput = notifyForm.querySelector('input[name="notify_note"]');
        if (noteInput) noteInput.value = '';
        if (notifySearch) notifySearch.value = '';
        notifyForm.querySelectorAll('.dam-nm-row').forEach(r => r.style.display = 'flex');
        updateNotifyCount();
    }

    function updateNotifyCount() {
        if (!notifyForm || !notifyCount) return;
        let n = 0;
        notifyForm.querySelectorAll('.dam-nm-cb:checked').forEach(() => n++);
        let gtot = 0;
        notifyForm.querySelectorAll('.dam-nm-group-cb:checked').forEach(function(cb){
            gtot += parseInt(cb.getAttribute('data-count') || '0', 10);
        });
        const total = n + gtot;
        notifyCount.textContent = total > 0 ? ('~' + total + ' kişi seçildi') : '0 kişi seçildi';
        if (notifyWarn) notifyWarn.style.display = gtot >= 50 ? 'block' : 'none';
    }

    document.querySelectorAll('.dam-notify-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.stopPropagation();
            if (!notifyModal || !notifyForm) return;
            notifyForm.setAttribute('action', btn.getAttribute('data-notify-url') || '');
            if (notifyName) notifyName.textContent = btn.getAttribute('data-asset-name') || '—';
            resetNotifyModal();
            if (typeof notifyModal.showModal === 'function') {
                notifyModal.showModal();
            }
        });
    });

    if (notifyForm) {
        notifyForm.addEventListener('change', function(e){
            if (!e.target || !e.target.classList) return;
            if (e.target.classList.contains('dam-nm-cb') || e.target.classList.contains('dam-nm-group-cb')) {
                updateNotifyCount();
            }
        });
    }
    if (notifySearch) {
        notifySearch.addEventListener('input', function(){
            const q = (this.value || '').toLowerCase().trim();
            notifyForm.querySelectorAll('.dam-nm-row').forEach(function(r){
                const name = r.getAttribute('data-name') || '';
                r.style.display = (q === '' || name.indexOf(q) !== -1) ? 'flex' : 'none';
            });
        });
    }
    ['dam-notify-close', 'dam-notify-cancel'].forEach(function(id){
        const b = document.getElementById(id);
        if (b && notifyModal) {
            b.addEventListener('click', function(){
                if (typeof notifyModal.close === 'function') notifyModal.close();
            });
        }
    });

    // ── DAM4 — Share link modal ─────────────────────────────────
    const shareModal = document.getElementById('dam-share-modal');
    const shareForm  = document.getElementById('dam-share-form');
    const shareName  = document.getElementById('dam-share-asset-name');

    document.querySelectorAll('.dam-share-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.stopPropagation();
            if (!shareModal || !shareForm) return;

            shareForm.setAttribute('action', btn.getAttribute('data-share-url'));
            shareName.textContent = btn.getAttribute('data-asset-name') || '—';
            if (typeof shareModal.showModal === 'function') {
                shareModal.showModal();
            }
        });
    });

    const shareCloseBtn  = document.getElementById('dam-share-close');
    const shareCancelBtn = document.getElementById('dam-share-cancel');
    [shareCloseBtn, shareCancelBtn].forEach(function(btn){
        if (btn && shareModal) {
            btn.addEventListener('click', function(){
                if (typeof shareModal.close === 'function') shareModal.close();
            });
        }
    });

    // ── Düzenle modal (data-attr'lardan doldur + aç) ───────────
    const editModal = document.getElementById('dam-edit-modal');
    const editForm  = document.getElementById('dam-edit-form');
    const editName  = document.getElementById('dam-edit-name');
    const editDesc  = document.getElementById('dam-edit-description');
    const editPin   = document.getElementById('dam-edit-pinned');
    const editTagWrap = document.getElementById('dam-edit-tag-chips');
    const editTagInput = document.getElementById('dam-edit-tag-input');

    function renderEditChips(tagSet) {
        if (!editTagWrap) return;
        editTagWrap.querySelectorAll('.dam-chip, input[name="tags[]"]').forEach(n => n.remove());
        tagSet.forEach(function(tag){
            const chip = document.createElement('span');
            chip.className = 'dam-chip';
            chip.style.cssText = 'display:inline-flex;align-items:center;gap:4px;padding:3px 8px;background:#e0e7ff;color:#3730a3;border-radius:99px;font-size:11px;font-weight:600;';
            chip.textContent = tag;
            const x = document.createElement('button');
            x.type = 'button';
            x.textContent = '×';
            x.style.cssText = 'background:none;border:none;cursor:pointer;color:#3730a3;font-size:14px;line-height:1;padding:0;margin-left:2px;';
            x.addEventListener('click', function(){ tagSet.delete(tag); renderEditChips(tagSet); });
            chip.appendChild(x);
            editTagWrap.insertBefore(chip, editTagInput);

            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = 'tags[]';
            hidden.value = tag;
            editTagWrap.appendChild(hidden);
        });
    }

    if (editModal && editForm) {
        const editTagSet = new Set();

        document.querySelectorAll('.dam-edit-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                const url = btn.getAttribute('data-update-url');
                editForm.setAttribute('action', url);
                editName.value = btn.getAttribute('data-asset-name') || '';
                editDesc.value = btn.getAttribute('data-asset-description') || '';
                editPin.checked = btn.getAttribute('data-asset-pinned') === '1';

                editTagSet.clear();
                try {
                    const tagsRaw = btn.getAttribute('data-asset-tags') || '[]';
                    const tagsArr = JSON.parse(tagsRaw);
                    if (Array.isArray(tagsArr)) {
                        tagsArr.forEach(t => editTagSet.add(String(t)));
                    }
                } catch (e) {}
                renderEditChips(editTagSet);

                if (typeof editModal.showModal === 'function') {
                    editModal.showModal();
                }
            });
        });

        if (editTagInput) {
            editTagInput.addEventListener('keydown', function(e){
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const t = (editTagInput.value || '').trim().toLowerCase()
                        .replace(/[^a-z0-9ğüşıöç\-_ ]/gi, '').substring(0, 60);
                    if (t && !editTagSet.has(t)) {
                        editTagSet.add(t);
                        renderEditChips(editTagSet);
                    }
                    editTagInput.value = '';
                } else if (e.key === 'Backspace' && editTagInput.value === '' && editTagSet.size > 0) {
                    const last = Array.from(editTagSet).pop();
                    editTagSet.delete(last);
                    renderEditChips(editTagSet);
                }
            });
        }
    }

    // ── Yıldız (favori) toggle — AJAX ──────────────────────────
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    // ── E4 — Folder star toggle (aynı pattern) ─────────────────
    document.querySelectorAll('.dam-folder-star').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            const url = btn.getAttribute('data-toggle-url');
            if (!url || btn.dataset.loading === '1') return;
            btn.dataset.loading = '1';
            btn.style.opacity = '0.5';

            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
            .then(function(r){ return r.ok ? r.json() : Promise.reject(r); })
            .then(function(data){
                const fav = !!data.favorited;
                btn.textContent = fav ? '⭐' : '☆';
                btn.style.color = fav ? '#f59e0b' : 'var(--text-muted, #94a3b8)';
                btn.setAttribute('title', fav ? 'Yıldızı kaldır' : 'Yıldızla');
            })
            .catch(function(){ btn.style.color = '#dc2626'; setTimeout(() => { btn.style.color = 'var(--text-muted, #94a3b8)'; }, 1500); })
            .finally(function(){ btn.dataset.loading = '0'; btn.style.opacity = '1'; });
        });
    });

    // ── E1 — Klasör ayarları modalı (ad + açıklama + yetkiler) ────
    // Hem breadcrumb'daki "⚙ Ayarlar" butonu hem sidebar tree'deki inline ⚙ butonu.
    const folderEditModal = document.getElementById('dam-folder-edit-modal');
    const folderEditForm  = document.getElementById('dam-folder-edit-form');

    function openFolderSettingsModal(triggerEl) {
        if (!folderEditModal || !folderEditForm || !triggerEl) return;
        const name = triggerEl.getAttribute('data-folder-name') || '';
        const desc = triggerEl.getAttribute('data-folder-description') || '';
        const url  = triggerEl.getAttribute('data-update-url');
        let roles = [];
        try { roles = JSON.parse(triggerEl.getAttribute('data-folder-roles') || '[]') || []; }
        catch(e) { roles = []; }

        folderEditForm.setAttribute('action', url);
        folderEditForm.querySelector('#dam-fedit-name').value = name;
        folderEditForm.querySelector('#dam-fedit-description').value = desc;
        folderEditForm.querySelectorAll('.dam-fedit-role').forEach(function(cb){
            cb.checked = Array.isArray(roles) && roles.indexOf(cb.value) !== -1;
        });

        if (typeof folderEditModal.showModal === 'function') {
            folderEditModal.showModal();
        } else {
            folderEditModal.setAttribute('open', '');
        }
    }

    // Breadcrumb butonu (mevcut klasör)
    const folderSettingsBtn = document.getElementById('damFolderSettingsBtn');
    if (folderSettingsBtn) {
        folderSettingsBtn.addEventListener('click', function(){
            openFolderSettingsModal(folderSettingsBtn);
        });
    }
    // Sidebar tree inline butonları (tüm klasörler)
    document.querySelectorAll('.dam-folder-settings-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            openFolderSettingsModal(btn);
        });
    });

    // ── E2 — Klasör taşıma (select ile parent seç) ──────────────
    const folderMoveBtn = document.getElementById('damFolderMoveBtn');
    if (folderMoveBtn) {
        folderMoveBtn.addEventListener('click', function(){
            const url = folderMoveBtn.getAttribute('data-move-url');
            const selfId = parseInt(folderMoveBtn.getAttribute('data-folder-id'), 10);
            openFolderPicker('Klasörü taşımak istediğiniz hedef klasörü seçin', selfId, function(targetId){
                const f = document.createElement('form');
                f.method = 'POST';
                f.action = url;
                f.innerHTML = '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                              '<input type="hidden" name="parent_id" value="' + (targetId || '') + '">';
                document.body.appendChild(f);
                f.submit();
            });
        });
    }

    // ── E3 — Dosya taşıma (select ile folder seç) ──────────────
    document.querySelectorAll('.dam-move-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.stopPropagation();
            const url = btn.getAttribute('data-move-url');
            const name = btn.getAttribute('data-asset-name') || 'dosya';
            openFolderPicker('"' + name + '" dosyasını hangi klasöre taşıyalım?', null, function(targetId){
                const f = document.createElement('form');
                f.method = 'POST';
                f.action = url;
                f.innerHTML = '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                              '<input type="hidden" name="folder_id" value="' + (targetId || '') + '">';
                document.body.appendChild(f);
                f.submit();
            });
        });
    });

    // ── Folder picker (basit prompt; gelişmiş modal yerine flat listing) ──
    // Klasörleri sidebar'dan dinamik çekip basit bir seçim alert'i gösterir
    function openFolderPicker(title, excludeFolderId, cb) {
        const folders = [];
        document.querySelectorAll('.dam-folder-star').forEach(function(fb){
            const fid = parseInt(fb.getAttribute('data-folder-id'), 10);
            if (fid === excludeFolderId) return;
            const link = fb.previousElementSibling;
            const fname = link ? link.textContent.trim().replace(/[★🔒]/g, '').trim() : '#' + fid;
            folders.push({ id: fid, name: fname });
        });
        if (folders.length === 0) {
            if (confirm(title + '\n\nKlasör yok — kök dizine taşımak ister misiniz?')) {
                cb(null);
            }
            return;
        }
        let msg = title + '\n\n';
        msg += '0 — 📂 Kök dizin (hiçbiri)\n';
        folders.forEach(function(f, i){ msg += (i + 1) + ' — ' + f.name + '\n'; });
        const pick = window.prompt(msg + '\nNumara girin:');
        if (pick === null) return;
        const n = parseInt(pick, 10);
        if (isNaN(n) || n < 0 || n > folders.length) { alert('Geçersiz seçim.'); return; }
        cb(n === 0 ? null : folders[n - 1].id);
    }

    // ── Asset fav toggle (mevcut, değişmedi) ─────────────────────
    document.querySelectorAll('.dam-fav-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            const url = btn.getAttribute('data-toggle-url');
            if (!url || btn.dataset.loading === '1') return;
            btn.dataset.loading = '1';
            btn.style.opacity = '0.5';

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
            .then(function(r){ return r.ok ? r.json() : Promise.reject(r); })
            .then(function(data){
                const fav = !!data.favorited;
                btn.textContent = fav ? '★' : '☆';
                btn.style.color = fav ? '#f59e0b' : '#cbd5e1';
                btn.setAttribute('aria-pressed', fav ? 'true' : 'false');
                btn.setAttribute('title', fav ? 'Yıldızı kaldır' : 'Yıldızla');

                // Sol paneldeki sayı badge'ini güncelle
                const sidebarLink = document.querySelector('a[href*="/digital-assets/favorites"]');
                if (sidebarLink && typeof data.count === 'number') {
                    let badge = sidebarLink.querySelector('span:last-child');
                    if (data.count > 0) {
                        if (!badge || !badge.style.borderRadius) {
                            badge = document.createElement('span');
                            badge.style.cssText = 'background:#fbbf24;color:#78350f;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;';
                            sidebarLink.appendChild(badge);
                        }
                        badge.textContent = data.count;
                    } else if (badge && badge.style.borderRadius) {
                        badge.remove();
                    }
                }
            })
            .catch(function(){
                btn.style.color = '#dc2626';
                setTimeout(function(){ btn.style.color = '#cbd5e1'; }, 1500);
            })
            .finally(function(){
                btn.dataset.loading = '0';
                btn.style.opacity = '1';
            });
        });
    });

    // ── Tag chip input (link modal — aynı mantık, farklı id'ler) ──
    const linkTagContainer = document.getElementById('dam-link-tag-chips');
    const linkTagInput     = document.getElementById('dam-link-tag-input');
    if (linkTagContainer && linkTagInput) {
        const linkTags = new Set();
        function renderLinkChips() {
            linkTagContainer.querySelectorAll('.dam-chip, input[name="tags[]"]').forEach(n => n.remove());
            linkTags.forEach(function(tag){
                const chip = document.createElement('span');
                chip.className = 'dam-chip';
                chip.style.cssText = 'display:inline-flex;align-items:center;gap:4px;padding:3px 8px;background:#e0e7ff;color:#3730a3;border-radius:99px;font-size:11px;font-weight:600;';
                chip.textContent = tag;
                const x = document.createElement('button');
                x.type = 'button';
                x.textContent = '×';
                x.style.cssText = 'background:none;border:none;cursor:pointer;color:#3730a3;font-size:14px;line-height:1;padding:0;margin-left:2px;';
                x.addEventListener('click', function(){ linkTags.delete(tag); renderLinkChips(); });
                chip.appendChild(x);
                linkTagContainer.insertBefore(chip, linkTagInput);

                const hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'tags[]';
                hidden.value = tag;
                linkTagContainer.appendChild(hidden);
            });
        }
        linkTagInput.addEventListener('keydown', function(e){
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const t = (linkTagInput.value || '').trim().toLowerCase()
                    .replace(/[^a-z0-9ğüşıöç\-_ ]/gi, '').substring(0, 60);
                if (t && !linkTags.has(t)) { linkTags.add(t); renderLinkChips(); }
                linkTagInput.value = '';
            } else if (e.key === 'Backspace' && linkTagInput.value === '' && linkTags.size > 0) {
                const last = Array.from(linkTags).pop();
                linkTags.delete(last);
                renderLinkChips();
            }
        });
        const linkModal = document.getElementById('dam-link-modal');
        if (linkModal) {
            linkModal.addEventListener('close', function(){
                linkTags.clear();
                renderLinkChips();
                linkTagInput.value = '';
            });
        }
    }

    // ── Tag chip input (upload modal) ──────────────────────────
    const tagContainer = document.getElementById('dam-tag-chips');
    const tagInput     = document.getElementById('dam-tag-input');
    if (tagContainer && tagInput) {
        const tags = new Set();

        function renderChips() {
            // Tüm chip'leri ve hidden input'ları temizle
            tagContainer.querySelectorAll('.dam-chip, input[name="tags[]"]').forEach(n => n.remove());
            tags.forEach(function(tag){
                const chip = document.createElement('span');
                chip.className = 'dam-chip';
                chip.style.cssText = 'display:inline-flex;align-items:center;gap:4px;padding:3px 8px;background:#e0e7ff;color:#3730a3;border-radius:99px;font-size:11px;font-weight:600;';
                chip.textContent = tag;
                const x = document.createElement('button');
                x.type = 'button';
                x.textContent = '×';
                x.style.cssText = 'background:none;border:none;cursor:pointer;color:#3730a3;font-size:14px;line-height:1;padding:0;margin-left:2px;';
                x.addEventListener('click', function(){ tags.delete(tag); renderChips(); });
                chip.appendChild(x);
                tagContainer.insertBefore(chip, tagInput);

                const hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'tags[]';
                hidden.value = tag;
                tagContainer.appendChild(hidden);
            });
        }

        function addTag(raw) {
            const t = (raw || '').trim().toLowerCase().replace(/[^a-z0-9ğüşıöç\-_ ]/gi, '').substring(0, 60);
            if (t && !tags.has(t)) { tags.add(t); renderChips(); }
        }

        tagInput.addEventListener('keydown', function(e){
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addTag(tagInput.value);
                tagInput.value = '';
            } else if (e.key === 'Backspace' && tagInput.value === '' && tags.size > 0) {
                const last = Array.from(tags).pop();
                tags.delete(last);
                renderChips();
            }
        });

        // Modal kapatıldığında input'u temizle (sonraki upload için)
        const modal = document.getElementById('dam-upload-modal');
        if (modal) {
            modal.addEventListener('close', function(){
                tags.clear();
                renderChips();
                tagInput.value = '';
            });
        }
    }
})();
</script>
@endsection

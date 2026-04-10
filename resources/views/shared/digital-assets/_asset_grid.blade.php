@php
    $categoryEmoji = [
        'image'    => '🖼️',
        'video'    => '🎬',
        'audio'    => '🎵',
        'document' => '📄',
        'archive'  => '🗜️',
        'other'    => '📎',
    ];

    // YouTube URL'sinden video ID çıkar (youtu.be, youtube.com/watch, /embed, /shorts)
    $extractYoutubeId = function (?string $url): ?string {
        if (!$url) return null;
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([a-zA-Z0-9_-]{11})/i', $url, $m)) {
            return $m[1];
        }
        return null;
    };
@endphp

@if($assets->isEmpty())
    <div style="background:#fff;border:1px dashed var(--border,#cbd5e1);border-radius:12px;padding:60px 20px;text-align:center;color:var(--text-muted,#64748b);">
        <div style="font-size:48px;margin-bottom:12px;">📦</div>
        <div style="font-weight:600;color:var(--text,#0f172a);margin-bottom:6px;">Bu klasörde henüz dosya yok</div>
        @unless($readOnly)
            @can('dam.upload')
                <div style="font-size:13px;">Yukarıdaki "+ Ekle" butonunu kullanın.</div>
            @endcan
        @endunless
    </div>
@else
<div class="dam-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;">
    @foreach($assets as $asset)
    @php
        $isFav     = in_array($asset->id, $favoriteIds ?? [], true);
        $isLink    = $asset->source_type === 'link';
        $emoji     = $categoryEmoji[$asset->category] ?? $categoryEmoji['other'];
        $previewUrl = $isLink ? null : route($routePrefix . '.preview', $asset->id);
        $youtubeId = $isLink ? $extractYoutubeId($asset->external_url) : null;
        $ytThumb   = $youtubeId ? ('https://img.youtube.com/vi/' . $youtubeId . '/hqdefault.jpg') : null;
    @endphp
    <div class="dam-card"
         data-asset-id="{{ $asset->id }}"
         data-asset-name="{{ $asset->name }}"
         data-asset-description="{{ $asset->description ?? '' }}"
         data-asset-tags='@json($asset->tags ?? [])'
         data-asset-category="{{ $asset->category }}"
         data-asset-mime="{{ $asset->mime_type ?? '' }}"
         data-asset-extension="{{ $asset->extension ?? '' }}"
         data-asset-size="{{ $asset->human_size }}"
         data-asset-doc="{{ $asset->doc_code ?? '' }}"
         data-asset-creator="{{ $asset->creator?->name ?? '' }}"
         data-asset-date="{{ $asset->created_at?->format('d.m.Y H:i') }}"
         data-asset-source="{{ $asset->source_type }}"
         data-asset-url="{{ $asset->external_url ?? '' }}"
         data-asset-preview="{{ $previewUrl ?? '' }}"
         data-asset-download="{{ route($routePrefix . '.download', $asset->id) }}"
         data-asset-emoji="{{ $emoji }}"
         data-asset-youtube="{{ $youtubeId ?? '' }}"
         style="background:#fff;border:1px solid var(--border,#e2e8f0);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s;cursor:pointer;"
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,.08)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''">
        {{-- Önizleme alanı --}}
        <div class="dam-card-preview" style="aspect-ratio:1.4;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);display:flex;align-items:center;justify-content:center;font-size:42px;position:relative;overflow:hidden;">
            @if($isLink && $ytThumb)
                {{-- YouTube linki: gerçek thumbnail --}}
                <img src="{{ $ytThumb }}" alt="{{ $asset->name }}"
                     style="width:100%;height:100%;object-fit:cover;" loading="lazy"
                     onerror="this.style.display='none';this.parentElement.insertAdjacentHTML('afterbegin','<div style=&quot;font-size:38px;&quot;>🎬</div>');">
                {{-- Play button overlay --}}
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;">
                    <div style="width:48px;height:48px;border-radius:50%;background:rgba(0,0,0,.65);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;padding-left:3px;">▶</div>
                </div>
            @elseif($isLink)
                <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                    <span style="font-size:38px;">{{ $emoji }}</span>
                    <span style="font-size:9px;color:#64748b;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">Harici Link</span>
                </div>
            @elseif($asset->is_image)
                <img src="{{ $previewUrl }}" alt="{{ $asset->name }}"
                     style="width:100%;height:100%;object-fit:cover;" loading="lazy"
                     onerror="this.style.display='none';this.parentElement.innerHTML+='<span style=\'font-size:42px;\'>{{ $emoji }}</span>';">
            @else
                {!! $emoji !!}
            @endif

            {{-- Link badge --}}
            @if($isLink)
                <div style="position:absolute;top:6px;left:6px;background:#3730a3;color:#fff;font-size:10px;padding:2px 7px;border-radius:99px;font-weight:700;display:flex;align-items:center;gap:3px;">🔗 Link</div>
            @elseif($asset->is_pinned)
                <div style="position:absolute;top:6px;left:6px;background:#fbbf24;color:#78350f;font-size:10px;padding:2px 6px;border-radius:99px;font-weight:700;">📌 Sabit</div>
            @endif

            <button type="button" class="dam-fav-btn" data-asset-id="{{ $asset->id }}"
                    data-toggle-url="{{ route($routePrefix . '.favorite.toggle', $asset->id) }}"
                    aria-pressed="{{ $isFav ? 'true' : 'false' }}"
                    title="{{ $isFav ? 'Yıldızı kaldır' : 'Yıldızla' }}"
                    style="position:absolute;top:6px;right:6px;width:30px;height:30px;border-radius:50%;border:none;background:rgba(255,255,255,.92);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;line-height:1;box-shadow:0 2px 6px rgba(0,0,0,.12);{{ $isFav ? 'color:#f59e0b;' : 'color:#cbd5e1;' }}">
                {{ $isFav ? '★' : '☆' }}
            </button>
        </div>
        {{-- Bilgi --}}
        <div style="padding:10px 12px;flex:1;display:flex;flex-direction:column;">
            <div style="font-size:13px;font-weight:600;color:var(--text,#0f172a);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $asset->name }}">
                {{ $asset->name }}
            </div>
            <div style="font-size:11px;color:var(--text-muted,#64748b);margin-top:2px;">
                @if($isLink)
                    🔗 Harici link
                @else
                    {{ strtoupper($asset->extension) }} · {{ $asset->human_size }}
                @endif

            </div>
            @if($asset->doc_code)
                <div style="font-size:10px;color:#94a3b8;margin-top:2px;font-family:monospace;">{{ $asset->doc_code }}</div>
            @endif
            @if(!empty($asset->tags) && is_array($asset->tags))
                <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:6px;">
                    @foreach(array_slice($asset->tags, 0, 3) as $tag)
                        <a href="?tag={{ urlencode($tag) }}" data-stop-card-click="1"
                           style="font-size:9px;padding:2px 6px;background:#e0e7ff;color:#3730a3;text-decoration:none;border-radius:99px;font-weight:600;">
                            {{ $tag }}
                        </a>
                    @endforeach
                    @if(count($asset->tags) > 3)
                        <span style="font-size:9px;color:#94a3b8;">+{{ count($asset->tags) - 3 }}</span>
                    @endif
                </div>
            @endif
            <div style="font-size:10px;color:#94a3b8;margin-top:6px;border-top:1px solid #f1f5f9;padding-top:6px;display:flex;align-items:center;gap:4px;" title="Yükleyen: {{ $asset->creator?->name ?? '—' }}">
                <span>👤</span>
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">{{ $asset->creator?->name ?? 'Bilinmiyor' }}</span>
                <span>·</span>
                <span>{{ $asset->created_at?->format('d.m.Y') }}</span>
            </div>
            <div style="display:flex;gap:4px;margin-top:10px;">
                <a href="{{ route($routePrefix . '.download', $asset->id) }}"
                   data-stop-card-click="1"
                   {{ $isLink ? 'target=_blank' : '' }}
                   style="flex:1;text-align:center;padding:6px 8px;font-size:12px;background:var(--accent-soft,#f1f5f9);color:var(--text,#0f172a);text-decoration:none;border-radius:6px;font-weight:600;">
                    {{ $isLink ? 'Aç ↗' : '↓ İndir' }}
                </a>
                @unless($readOnly)
                @can('dam.update')
                <button type="button" class="dam-edit-btn"
                        data-stop-card-click="1"
                        data-asset-id="{{ $asset->id }}"
                        data-asset-name="{{ $asset->name }}"
                        data-asset-description="{{ $asset->description ?? '' }}"
                        data-asset-tags='@json($asset->tags ?? [])'
                        data-asset-pinned="{{ $asset->is_pinned ? '1' : '0' }}"
                        data-update-url="{{ route($routePrefix . '.update', $asset->id) }}"
                        style="padding:6px 10px;background:#eff6ff;color:#1d4ed8;border:none;border-radius:6px;font-size:12px;cursor:pointer;font-weight:600;"
                        title="Düzenle">
                    ✎
                </button>
                @endcan
                @can('dam.delete')
                <form method="POST" action="{{ route($routePrefix . '.destroy', $asset->id) }}" style="flex:0 0 auto;" data-stop-card-click="1"
                      onsubmit="return confirm('Bu varlığı silmek istediğinize emin misiniz?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            style="padding:6px 10px;background:#fef2f2;color:#dc2626;border:none;border-radius:6px;font-size:12px;cursor:pointer;font-weight:600;"
                            title="Sil">
                        ×
                    </button>
                </form>
                @endcan
                @endunless
            </div>
        </div>
    </div>
    @endforeach
</div>

<div style="margin-top:24px;">
    {{ $assets->links() }}
</div>
@endif

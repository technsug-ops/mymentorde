@php
    // Sıralanabilir başlık yardımcısı — aynı kolona tıklanınca yön değişir
    $makeSortUrl = function (string $col) use ($sortKey, $sortDir) {
        $nextDir = ($sortKey === $col && $sortDir === 'asc') ? 'desc' : 'asc';
        $params  = array_merge(request()->query(), ['sort' => $col, 'dir' => $nextDir]);
        return '?' . http_build_query($params);
    };
    $sortIndicator = function (string $col) use ($sortKey, $sortDir) {
        if ($sortKey !== $col) return '<span style="color:#cbd5e1;">⇅</span>';
        return $sortDir === 'asc'
            ? '<span style="color:#0f172a;">▲</span>'
            : '<span style="color:#0f172a;">▼</span>';
    };
    $thStyle = 'padding:10px 10px;text-align:left;font-weight:600;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.5px;user-select:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
    $tdBase  = 'padding:10px 10px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';

    $categoryLabels = [
        'image'    => ['🖼️', 'Görsel'],
        'video'    => ['🎬', 'Video'],
        'audio'    => ['🎵', 'Ses'],
        'document' => ['📄', 'Doküman'],
        'archive'  => ['🗜️', 'Arşiv'],
        'other'    => ['📎', 'Diğer'],
    ];
@endphp

@if($assets->isEmpty())
    <div style="background:#fff;border:1px dashed #cbd5e1;border-radius:12px;padding:60px 20px;text-align:center;color:#64748b;">
        <div style="font-size:48px;margin-bottom:12px;">📦</div>
        <div style="font-weight:600;color:#0f172a;margin-bottom:6px;">Bu klasörde henüz dosya yok</div>
    </div>
@else
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
    <table id="dam-list-table" style="width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed;">
        <colgroup>
            <col data-col="select"   style="width:34px;">
            <col data-col="type"     style="width:44px;">
            <col data-col="name"     style="width:auto;">
            <col data-col="category" style="width:90px;">
            <col data-col="tags"     style="width:150px;">
            <col data-col="doc"      style="width:130px;">
            <col data-col="size"     style="width:80px;">
            <col data-col="uploader" style="width:120px;">
            <col data-col="date"     style="width:100px;">
            <col data-col="actions"  style="width:215px;">
        </colgroup>
        <thead>
            <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <th data-col="select" style="{{ $thStyle }}text-align:center;">
                    <input type="checkbox" id="dam-bulk-select-all" title="Tümünü seç" style="cursor:pointer">
                </th>
                <th data-col="type"     style="{{ $thStyle }}text-align:center;">Tür</th>
                <th data-col="name"     style="{{ $thStyle }}"><a href="{{ $makeSortUrl('name') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">Ad {!! $sortIndicator('name') !!}</a></th>
                <th data-col="category" style="{{ $thStyle }}"><a href="{{ $makeSortUrl('category') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">Kategori {!! $sortIndicator('category') !!}</a></th>
                <th data-col="tags"     style="{{ $thStyle }}">Etiketler</th>
                <th data-col="doc"      style="{{ $thStyle }}"><a href="{{ $makeSortUrl('doc_code') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">DOC Kodu {!! $sortIndicator('doc_code') !!}</a></th>
                <th data-col="size"     style="{{ $thStyle }}text-align:right;"><a href="{{ $makeSortUrl('size_bytes') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">Boyut {!! $sortIndicator('size_bytes') !!}</a></th>
                <th data-col="uploader" style="{{ $thStyle }}"><a href="{{ $makeSortUrl('creator') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">Yükleyen {!! $sortIndicator('creator') !!}</a></th>
                <th data-col="date"     style="{{ $thStyle }}"><a href="{{ $makeSortUrl('created_at') }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">Tarih {!! $sortIndicator('created_at') !!}</a></th>
                <th data-col="actions"  style="{{ $thStyle }}text-align:right;">İşlem</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $asset)
            @php
                $cat = $categoryLabels[$asset->category] ?? $categoryLabels['other'];
                $isFav = in_array($asset->id, $favoriteIds ?? [], true);
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td data-col="select" style="{{ $tdBase }}text-align:center;">
                    <input type="checkbox" class="dam-row-check" value="{{ $asset->id }}" style="cursor:pointer">
                </td>
                <td data-col="type" style="{{ $tdBase }}text-align:center;font-size:22px;">{!! $cat[0] !!}</td>
                <td data-col="name" style="padding:10px 10px;overflow:hidden;">
                    <div style="font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $asset->name }}">{{ $asset->name }}</div>
                    @if($asset->description)
                        <div style="font-size:11px;color:#64748b;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $asset->description }}</div>
                    @endif
                    <div style="font-size:10px;color:#94a3b8;margin-top:2px;">
                        {{ strtoupper($asset->extension) }}

                        @if($asset->is_pinned) · <span style="color:#d97706;">📌 Sabit</span> @endif
                    </div>
                </td>
                <td data-col="category" style="{{ $tdBase }}color:#475569;" title="{{ $cat[1] }}">{{ $cat[1] }}</td>
                <td data-col="tags" style="padding:10px 10px;overflow:hidden;">
                    @if(!empty($asset->tags) && is_array($asset->tags))
                        <div style="display:flex;flex-wrap:wrap;gap:3px;max-height:40px;overflow:hidden;">
                            @foreach($asset->tags as $tag)
                                <a href="?tag={{ urlencode($tag) }}"
                                   style="font-size:10px;padding:2px 7px;background:#e0e7ff;color:#3730a3;text-decoration:none;border-radius:99px;font-weight:600;">
                                    {{ $tag }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span style="color:#cbd5e1;font-size:11px;">—</span>
                    @endif
                </td>
                <td data-col="doc" style="{{ $tdBase }}font-family:monospace;font-size:11px;" title="{{ $asset->doc_code ?? '' }}">{{ $asset->doc_code ?? '—' }}</td>
                <td data-col="size" style="{{ $tdBase }}text-align:right;">{{ $asset->human_size }}</td>
                <td data-col="uploader" style="{{ $tdBase }}" title="{{ $asset->creator?->name ?? '—' }}">{{ $asset->creator?->name ?? '—' }}</td>
                <td data-col="date" style="{{ $tdBase }}" title="{{ $asset->created_at?->format('d.m.Y H:i') }}">{{ $asset->created_at?->format('d.m.Y') }}</td>
                <td data-col="actions" style="padding:10px 10px;text-align:right;white-space:nowrap;">
                    <div class="dam-action-row">
                        <button type="button" class="dam-iconbtn dam-fav-btn {{ $isFav ? 'is-fav-on' : '' }}"
                                data-asset-id="{{ $asset->id }}"
                                data-toggle-url="{{ route($routePrefix . '.favorite.toggle', $asset->id) }}"
                                aria-pressed="{{ $isFav ? 'true' : 'false' }}"
                                title="{{ $isFav ? 'Yıldızı kaldır' : 'Yıldızla' }}">
                            {{ $isFav ? '★' : '☆' }}
                        </button>
                        <a class="dam-iconbtn v-download"
                           href="{{ route($routePrefix . '.download', $asset->id) }}"
                           title="İndir">↓</a>
                        @unless($readOnly)
                        @can('dam.update')
                        <button type="button" class="dam-iconbtn v-edit dam-edit-btn"
                                data-asset-id="{{ $asset->id }}"
                                data-asset-name="{{ $asset->name }}"
                                data-asset-description="{{ $asset->description ?? '' }}"
                                data-asset-tags='@json($asset->tags ?? [])'
                                data-asset-pinned="{{ $asset->is_pinned ? '1' : '0' }}"
                                data-update-url="{{ route($routePrefix . '.update', $asset->id) }}"
                                title="Düzenle">✎</button>
                        <button type="button" class="dam-iconbtn v-move dam-move-btn"
                                data-asset-id="{{ $asset->id }}"
                                data-asset-name="{{ $asset->name }}"
                                data-move-url="{{ route($routePrefix . '.move', $asset->id) }}"
                                title="Başka klasöre taşı">➜</button>
                        <button type="button" class="dam-iconbtn v-share dam-share-btn"
                                data-asset-id="{{ $asset->id }}"
                                data-asset-name="{{ $asset->name }}"
                                data-share-url="{{ route($routePrefix . '.share.create', $asset->id) }}"
                                title="Paylaşım linki oluştur">🔗</button>
                        @endcan
                        @can('dam.upload')
                        <button type="button" class="dam-iconbtn v-notify dam-notify-btn"
                                data-asset-id="{{ $asset->id }}"
                                data-asset-name="{{ $asset->name }}"
                                data-notify-url="{{ route($routePrefix . '.notify', $asset->id) }}"
                                title="Kişilere bildir">📢</button>
                        @endcan
                        @can('dam.delete')
                        <form method="POST" action="{{ route($routePrefix . '.destroy', $asset->id) }}" class="dam-iconbtn-form"
                              onsubmit="return confirm('Bu varlığı silmek istediğinize emin misiniz?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dam-iconbtn v-delete" title="Sil">×</button>
                        </form>
                        @endcan
                        @endunless
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">
    {{ $assets->links() }}
</div>
@endif

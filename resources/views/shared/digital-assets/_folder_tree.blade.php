@foreach($nodes as $node)
    @php
        $isFav = in_array($node['id'], $favoriteFolderIds ?? [], true);
    @endphp
    <div style="display:flex;align-items:center;gap:4px;margin-top:2px;padding-left:{{ 10 + ($level * 14) }}px;border-radius:6px;{{ ($currentId ?? null) === $node['id'] ? 'background:var(--accent-soft,#f1f5f9);' : '' }}">
        <a href="{{ route($routePrefix . '.folder.show', $node['id']) }}"
           style="flex:1;padding:6px 6px;text-decoration:none;color:var(--text,#0f172a);font-size:13px;{{ ($currentId ?? null) === $node['id'] ? 'font-weight:600;' : '' }}">
            <span style="margin-right:4px;">{{ $node['icon'] ? '' : '📂' }}</span>{{ $node['name'] }}
            @if($node['is_system'])
                <span style="font-size:10px;color:var(--text-muted,#64748b);" title="Sistem klasörü">★</span>
            @endif
            @if(!empty($node['is_restricted']))
                <span style="font-size:10px;color:#d97706;" title="Kısıtlı erişim">🔒</span>
            @endif
        </a>
        {{-- E4 — Yıldızlama butonu --}}
        <button type="button"
                class="dam-folder-star"
                data-folder-id="{{ $node['id'] }}"
                data-toggle-url="{{ route($routePrefix . '.folder.favorite.toggle', $node['id']) }}"
                title="{{ $isFav ? 'Yıldızı kaldır' : 'Yıldızla' }}"
                style="background:none;border:none;cursor:pointer;font-size:13px;padding:2px 6px;color:{{ $isFav ? '#f59e0b' : 'var(--text-muted,#94a3b8)' }};margin-right:6px;">
            {{ $isFav ? '⭐' : '☆' }}
        </button>
    </div>
    @if(!empty($node['children']) && count($node['children']))
        @include('shared.digital-assets._folder_tree', [
            'nodes'             => $node['children'],
            'level'             => $level + 1,
            'currentId'         => $currentId ?? null,
            'routePrefix'       => $routePrefix,
            'favoriteFolderIds' => $favoriteFolderIds ?? [],
        ])
    @endif
@endforeach

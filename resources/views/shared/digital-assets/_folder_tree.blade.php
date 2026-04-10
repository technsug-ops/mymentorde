@foreach($nodes as $node)
    <a href="{{ route($routePrefix . '.folder.show', $node['id']) }}"
       style="display:block;padding:6px 10px;padding-left:{{ 10 + ($level * 14) }}px;border-radius:6px;text-decoration:none;color:var(--text,#0f172a);font-size:13px;margin-top:2px;{{ ($currentId ?? null) === $node['id'] ? 'background:var(--accent-soft,#f1f5f9);font-weight:600;' : '' }}">
        <span style="margin-right:4px;">{{ $node['icon'] ? '' : '📂' }}</span>{{ $node['name'] }}
        @if($node['is_system'])
            <span style="font-size:10px;color:var(--text-muted,#64748b);" title="Sistem klasörü">★</span>
        @endif
        @if(!empty($node['is_restricted']))
            <span style="font-size:10px;color:#d97706;" title="Kısıtlı erişim">🔒</span>
        @endif
    </a>
    @if(!empty($node['children']) && count($node['children']))
        @include('shared.digital-assets._folder_tree', [
            'nodes'      => $node['children'],
            'level'      => $level + 1,
            'currentId'  => $currentId ?? null,
            'routePrefix'=> $routePrefix,
        ])
    @endif
@endforeach

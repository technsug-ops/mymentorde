@props(['items' => [], 'default' => null])
@php $def = $default ?? ($items[0] ?? ''); @endphp
<div x-data="{ tab: '{{ $def }}' }">
    <div style="display:flex;gap:2px;border-bottom:2px solid var(--u-line);margin-bottom:16px;flex-wrap:wrap;">
        @foreach($items as $item)
        <button @click="tab='{{ $item }}'" :style="tab==='{{ $item }}'?'border-bottom:2px solid var(--u-brand);color:var(--u-brand);font-weight:600':'color:var(--u-muted)'" style="padding:10px 16px;font-size:13px;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;">{{ $item }}</button>
        @endforeach
    </div>
    {{ $slot }}
</div>

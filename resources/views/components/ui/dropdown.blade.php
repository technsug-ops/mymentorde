@props([
    'align' => 'left',  // left | right
])
<div
    class="ds-dropdown"
    x-data="{ open: false }"
    @click.away="open = false"
    @keydown.escape="open = false"
>
    {{-- Trigger slot --}}
    <div @click="open = !open" style="cursor:pointer">
        {{ $trigger ?? '' }}
    </div>

    {{-- Menu --}}
    <div
        class="ds-dropdown-menu"
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-[-6px]"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display:none; {{ $align === 'right' ? 'left:auto;right:0' : '' }}"
        @click="open = false"
    >
        {{ $slot }}
    </div>
</div>

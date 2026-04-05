@props([
    'id'      => 'modal-' . uniqid(),
    'title'   => '',
    'size'    => 'md',   // sm | md | lg | xl
    'trigger' => null,   // null = x-show ile kontrol
])
@php
$maxWidths = ['sm' => '400px', 'md' => '520px', 'lg' => '680px', 'xl' => '860px'];
$maxW = $maxWidths[$size] ?? $maxWidths['md'];
@endphp

<div
    x-data="{ open: false }"
    @if($trigger) @click.away="open = false" @endif
    id="{{ $id }}"
>
    @if($trigger)
        <div @click="open = true">{{ $trigger }}</div>
    @endif

    <template x-teleport="body">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="ds-modal-overlay"
            @click.self="open = false"
            @keydown.escape.window="open = false"
            style="display:none"
        >
            <div
                class="ds-modal"
                style="max-width:{{ $maxW }}"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
            >
                @if($title)
                <div class="ds-modal-header">
                    <span>{{ $title }}</span>
                    <button class="ds-modal-close" @click="open = false" type="button">✕</button>
                </div>
                @endif

                <div class="ds-modal-body">
                    {{ $slot }}
                </div>

                @isset($footer)
                <div class="ds-modal-footer">
                    {{ $footer }}
                </div>
                @endisset
            </div>
        </div>
    </template>
</div>

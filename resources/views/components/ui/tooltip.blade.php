@props([
    'text' => '',
    'pos'  => 'top',  // top | bottom | left | right (CSS ile üst varsayılan)
])
<span data-tooltip="{{ $text }}" style="display:inline-flex;align-items:center;">
    {{ $slot }}
</span>

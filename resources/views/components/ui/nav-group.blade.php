@props(['id', 'label', 'open' => false])
<div class="nav-group {{ $open ? 'open has-active' : '' }}" id="{{ $id }}">
    <button class="nav-group-btn" type="button" data-toggle-group="{{ $id }}">
        <span>{{ $label }}</span><span class="nav-caret">▾</span>
    </button>
    <div class="nav-sub">{{ $slot }}</div>
</div>

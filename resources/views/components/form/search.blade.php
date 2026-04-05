@props([
    'placeholder' => 'Ara...',
    'name'        => 'q',
    'value'       => null,
    'method'      => null,  // null = GET form submit, 'live' = x-model
    'id'          => null,
])
@php $fieldId = $id ?? 'search-' . uniqid(); @endphp

<div class="ds-search">
    <span class="ds-search-icon">🔍</span>
    <input
        type="search"
        id="{{ $fieldId }}"
        name="{{ $name }}"
        value="{{ $value ?? request($name) }}"
        placeholder="{{ $placeholder }}"
        class="ds-input"
        autocomplete="off"
        {{ $attributes->except(['placeholder','name','value','method','id']) }}
    >
</div>

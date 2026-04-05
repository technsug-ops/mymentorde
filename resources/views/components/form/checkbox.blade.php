@props([
    'label'   => '',
    'hint'    => null,
    'error'   => null,
    'checked' => false,
    'name'    => null,
    'value'   => '1',
    'id'      => null,
])
@php
$fieldId  = $id ?? $name ?? 'chk-' . uniqid();
$isChecked = old($name ?? $fieldId, $checked);
@endphp

<div class="ds-field">
    <label class="ds-checkbox" for="{{ $fieldId }}">
        <input
            type="checkbox"
            id="{{ $fieldId }}"
            name="{{ $name ?? $fieldId }}"
            value="{{ $value }}"
            @if($isChecked) checked @endif
            {{ $attributes->except(['label','hint','error','checked','name','value','id']) }}
        >
        <span style="font-size:var(--font-sm);color:var(--color-text)">{{ $label }}{{ $slot }}</span>
    </label>
    @if($hint && !$error)
        <span class="ds-hint" style="margin-left:24px">{{ $hint }}</span>
    @endif
    @if($error)
        <span class="ds-error" style="margin-left:24px">{{ $error }}</span>
    @endif
</div>

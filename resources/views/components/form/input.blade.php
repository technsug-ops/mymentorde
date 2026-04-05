@props([
    'label'       => null,
    'hint'        => null,
    'error'       => null,
    'icon'        => null,
    'required'    => false,
    'type'        => 'text',
    'id'          => null,
    'name'        => null,
    'value'       => null,
    'placeholder' => null,
])
@php $fieldId = $id ?? $name ?? 'f-' . uniqid(); @endphp

<div class="ds-field">
    @if($label)
    <label class="ds-label" for="{{ $fieldId }}">
        {{ $label }}@if($required)<span class="required">*</span>@endif
    </label>
    @endif

    <div @if($icon) class="ds-input-icon-wrap" @endif>
        @if($icon)
            <span class="ds-input-icon">{{ $icon }}</span>
        @endif
        <input
            type="{{ $type }}"
            id="{{ $fieldId }}"
            name="{{ $name ?? $fieldId }}"
            value="{{ old($name ?? $fieldId, $value) }}"
            placeholder="{{ $placeholder }}"
            class="ds-input{{ $error ? ' has-error' : '' }}"
            @if($required) required @endif
            {{ $attributes->except(['label','hint','error','icon','required','type','id','name','value','placeholder']) }}
        >
    </div>

    @if($hint && !$error)
        <span class="ds-hint">{{ $hint }}</span>
    @endif
    @if($error)
        <span class="ds-error">{{ $error }}</span>
    @endif
</div>

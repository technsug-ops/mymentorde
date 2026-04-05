@props([
    'label'       => null,
    'hint'        => null,
    'error'       => null,
    'required'    => false,
    'name'        => null,
    'value'       => null,
    'id'          => null,
    'min'         => null,
    'max'         => null,
    'placeholder' => 'YYYY-MM-DD',
])
@php $fieldId = $id ?? $name ?? 'dp-' . uniqid(); @endphp

<div class="ds-field">
    @if($label)
    <label class="ds-label" for="{{ $fieldId }}">
        {{ $label }}@if($required)<span class="required">*</span>@endif
    </label>
    @endif

    <div class="ds-input-icon-wrap">
        <span class="ds-input-icon">📅</span>
        <input
            type="date"
            id="{{ $fieldId }}"
            name="{{ $name ?? $fieldId }}"
            value="{{ old($name ?? $fieldId, $value) }}"
            placeholder="{{ $placeholder }}"
            class="ds-input{{ $error ? ' has-error' : '' }}"
            @if($required) required @endif
            @if($min) min="{{ $min }}" @endif
            @if($max) max="{{ $max }}" @endif
            {{ $attributes->except(['label','hint','error','required','name','value','id','min','max','placeholder']) }}
        >
    </div>

    @if($hint && !$error)
        <span class="ds-hint">{{ $hint }}</span>
    @endif
    @if($error)
        <span class="ds-error">{{ $error }}</span>
    @endif
</div>

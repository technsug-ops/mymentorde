@props([
    'label'       => null,
    'hint'        => null,
    'error'       => null,
    'required'    => false,
    'rows'        => 4,
    'id'          => null,
    'name'        => null,
    'placeholder' => null,
])
@php $fieldId = $id ?? $name ?? 'ta-' . uniqid(); @endphp

<div class="ds-field">
    @if($label)
    <label class="ds-label" for="{{ $fieldId }}">
        {{ $label }}@if($required)<span class="required">*</span>@endif
    </label>
    @endif

    <textarea
        id="{{ $fieldId }}"
        name="{{ $name ?? $fieldId }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        class="ds-textarea{{ $error ? ' has-error' : '' }}"
        @if($required) required @endif
        {{ $attributes->except(['label','hint','error','required','rows','id','name','placeholder']) }}
    >{{ old($name ?? $fieldId, $slot->isNotEmpty() ? $slot : '') }}</textarea>

    @if($hint && !$error)
        <span class="ds-hint">{{ $hint }}</span>
    @endif
    @if($error)
        <span class="ds-error">{{ $error }}</span>
    @endif
</div>

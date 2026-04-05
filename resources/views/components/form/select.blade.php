@props([
    'label'    => null,
    'hint'     => null,
    'error'    => null,
    'required' => false,
    'options'  => [],   // ['value' => 'Label'] veya [['value'=>'...','label'=>'...']]
    'selected' => null,
    'placeholder' => '— Seçiniz —',
    'id'       => null,
    'name'     => null,
])
@php
$fieldId = $id ?? $name ?? 'sel-' . uniqid();
$cur     = old($name ?? $fieldId, $selected);
@endphp

<div class="ds-field">
    @if($label)
    <label class="ds-label" for="{{ $fieldId }}">
        {{ $label }}@if($required)<span class="required">*</span>@endif
    </label>
    @endif

    <select
        id="{{ $fieldId }}"
        name="{{ $name ?? $fieldId }}"
        class="ds-select{{ $error ? ' has-error' : '' }}"
        @if($required) required @endif
        {{ $attributes->except(['label','hint','error','required','options','selected','placeholder','id','name']) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $val => $lbl)
            @if(is_array($lbl))
                <option value="{{ $lbl['value'] }}" {{ (string)$cur === (string)$lbl['value'] ? 'selected' : '' }}>
                    {{ $lbl['label'] }}
                </option>
            @else
                <option value="{{ $val }}" {{ (string)$cur === (string)$val ? 'selected' : '' }}>
                    {{ $lbl }}
                </option>
            @endif
        @endforeach

        {{ $slot }}
    </select>

    @if($hint && !$error)
        <span class="ds-hint">{{ $hint }}</span>
    @endif
    @if($error)
        <span class="ds-error">{{ $error }}</span>
    @endif
</div>

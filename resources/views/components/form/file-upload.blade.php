@props([
    'label'    => 'Dosya Yükle',
    'hint'     => 'PDF, JPG, PNG — maks 10 MB',
    'error'    => null,
    'name'     => 'file',
    'accept'   => '.pdf,.jpg,.jpeg,.png',
    'multiple' => false,
    'id'       => null,
    'icon'     => '📎',
])
@php $fieldId = $id ?? $name ?? 'fu-' . uniqid(); @endphp

<div class="ds-field">
    <label class="ds-file-upload{{ $error ? ' has-error' : '' }}" for="{{ $fieldId }}">
        <input
            type="file"
            id="{{ $fieldId }}"
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            accept="{{ $accept }}"
            @if($multiple) multiple @endif
            {{ $attributes->except(['label','hint','error','name','accept','multiple','id','icon']) }}
        >
        <div class="ds-file-upload-icon">{{ $icon }}</div>
        <div class="ds-file-upload-label">{{ $label }}</div>
        <div class="ds-file-upload-hint">{{ $hint }}</div>
        <div id="{{ $fieldId }}-name" class="ds-hint" style="margin-top:6px;display:none"></div>
    </label>

    @if($error)
        <span class="ds-error">{{ $error }}</span>
    @endif
</div>

<script>
(function(){
    var inp = document.getElementById('{{ $fieldId }}');
    var nameEl = document.getElementById('{{ $fieldId }}-name');
    if(inp && nameEl) {
        inp.addEventListener('change', function(){
            if(this.files.length) {
                nameEl.textContent = Array.from(this.files).map(f=>f.name).join(', ');
                nameEl.style.display = 'block';
            }
        });
    }
})();
</script>

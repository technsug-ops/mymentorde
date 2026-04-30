@extends('study-buddy.layout')

@section('title', $stepDef['title'] . ' — Study Buddy')

@section('back-link')
    @if($currentStep > 1)
        <a href="{{ route('study-buddy.step', ['n' => $currentStep - 1]) }}" class="sb-back">← Geri</a>
    @endif
@endsection

@section('content')
<div class="sb-progress-wrap">
    <div class="sb-progress-meta">
        <span>Adım {{ $currentStep }} / {{ $totalSteps }}</span>
        <span>%{{ $progress }} tamamlandı</span>
    </div>
    <div class="sb-progress-bar">
        <div class="sb-progress-fill" style="width: {{ $progress }}%;"></div>
    </div>
</div>

<div class="sb-card">
    <h1 class="sb-title">{{ $stepDef['title'] }}</h1>
    @if(! empty($stepDef['subtitle']))
        <p class="sb-subtitle">{{ $stepDef['subtitle'] }}</p>
    @endif

    <form method="POST" action="{{ route('study-buddy.step.save', ['n' => $currentStep]) }}" id="stepForm">
        @csrf

        @if($stepDef['type'] === 'cards')
            @php
                $optCount = count($stepDef['options'] ?? []);
                $cols = $optCount <= 3 ? 'cols-1' : ($optCount <= 4 ? 'cols-2' : '');
            @endphp
            <div class="sb-options {{ $cols }}">
                @foreach(($stepDef['options'] ?? []) as $opt)
                    <label class="sb-option {{ (string)$answer === (string)$opt['value'] ? 'selected' : '' }}" data-value="{{ $opt['value'] }}">
                        <input type="radio" name="{{ $stepDef['key'] }}" value="{{ $opt['value'] }}"
                               @checked((string)$answer === (string)$opt['value']) required>
                        @if(! empty($opt['icon']))
                            <span class="sb-option-icon">{!! $opt['icon'] !!}</span>
                        @endif
                        <div class="sb-option-text">
                            <div class="sb-option-label">{{ $opt['label'] }}</div>
                            @if(! empty($opt['desc']))
                                <div class="sb-option-desc">{{ $opt['desc'] }}</div>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        @elseif($stepDef['type'] === 'checkbox_group')
            @php
                $checked = is_array($answer) ? array_map('strval', $answer) : [];
                $maxSel = (int) ($stepDef['max'] ?? 0);
            @endphp
            @if($maxSel > 0)
                <div style="font-size: 12.5px; color: #6b5894; margin-bottom: 12px; text-align: center;">
                    <span data-cbx-counter>0</span>/{{ $maxSel }} seçildi
                </div>
            @endif
            <div class="sb-options" data-cbx-group data-cbx-max="{{ $maxSel }}">
                @foreach(($stepDef['options'] ?? []) as $opt)
                    <label class="sb-option {{ in_array((string)$opt['value'], $checked, true) ? 'selected' : '' }}">
                        <input type="checkbox" name="{{ $stepDef['key'] }}[]" value="{{ $opt['value'] }}"
                               @checked(in_array((string)$opt['value'], $checked, true))
                               style="position:absolute; opacity:0;">
                        @if(! empty($opt['icon']))
                            <span class="sb-option-icon">{!! $opt['icon'] !!}</span>
                        @endif
                        <div class="sb-option-text">
                            <div class="sb-option-label">{{ $opt['label'] }}</div>
                            @if(! empty($opt['desc']))
                                <div class="sb-option-desc">{{ $opt['desc'] }}</div>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        @endif

        <div class="sb-nav">
            <a href="{{ route('study-buddy.landing') }}" class="sb-btn sb-btn-ghost">Vazgeç</a>
            <button type="submit" class="sb-btn sb-btn-primary" id="nextBtn">
                {{ $currentStep >= $totalSteps ? 'Sonuçları Göster' : 'Devam Et' }}
                <span style="font-size: 16px;">→</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function(){
    var cbxGroup = document.querySelector('[data-cbx-group]');
    var cbxMax = cbxGroup ? parseInt(cbxGroup.getAttribute('data-cbx-max') || '0', 10) : 0;
    var cbxCounter = document.querySelector('[data-cbx-counter]');

    function updateCbxCounter(){
        if (! cbxGroup || ! cbxCounter) return;
        var n = cbxGroup.querySelectorAll('input[type="checkbox"]:checked').length;
        cbxCounter.textContent = n;
    }
    updateCbxCounter();

    // Tüm option'lara click handler
    document.querySelectorAll('.sb-option').forEach(function(opt){
        opt.addEventListener('click', function(e){
            // Native input'a tıklarsa zaten kendi davranışını yapsın
            if (e.target.tagName === 'INPUT') return;

            var radio = opt.querySelector('input[type="radio"]');
            var checkbox = opt.querySelector('input[type="checkbox"]');

            if (radio) {
                // Radio: tek seçim
                document.querySelectorAll('.sb-option').forEach(function(o){ o.classList.remove('selected'); });
                opt.classList.add('selected');
                radio.checked = true;
            } else if (checkbox && cbxGroup) {
                // Checkbox: çoklu seçim, max kontrolü
                var currentlyChecked = cbxGroup.querySelectorAll('input[type="checkbox"]:checked').length;
                if (! checkbox.checked && cbxMax > 0 && currentlyChecked >= cbxMax) {
                    // Limit dolu, ekleme yapma
                    return;
                }
                checkbox.checked = ! checkbox.checked;
                opt.classList.toggle('selected', checkbox.checked);
                updateCbxCounter();
            }
        });
    });

    // Klavye: Enter ile submit
    document.addEventListener('keydown', function(e){
        if (e.key === 'Enter' && !e.shiftKey && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            document.getElementById('stepForm').submit();
        }
    });
})();
</script>
@endpush
@endsection

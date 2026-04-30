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
// Card radio — tıklayınca selected class güncelle + form'u submit et (auto-advance opsiyonel)
document.querySelectorAll('.sb-option').forEach(function(opt){
    opt.addEventListener('click', function(){
        document.querySelectorAll('.sb-option').forEach(function(o){ o.classList.remove('selected'); });
        opt.classList.add('selected');
        var input = opt.querySelector('input[type="radio"]');
        if (input) input.checked = true;
    });
});

// Klavye: Enter ile submit, ok tuşları ile arasında geçiş
document.addEventListener('keydown', function(e){
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('stepForm').submit();
    }
});
</script>
@endpush
@endsection

@extends('uni-match.layout')

@section('title', $stepDef['title'] . ' — UniMatch')

@push('head')
<script nonce="{{ $cspNonce ?? '' }}">
    document.documentElement.classList.add('sb-wizard-active');
</script>
@endpush

@section('back-link')
    @if($currentStep > 1)
        <a href="{{ route('uni-match.step', ['n' => $currentStep - 1]) }}" class="sb-back">← Geri</a>
    @endif
@endsection

@section('content')
@php
    $remainingSteps = max(0, $totalSteps - $currentStep);
    $remainingSec = $remainingSteps * 20;
    $remainingMin = $remainingSec >= 60 ? ceil($remainingSec / 60) : null;
    $remainingLabel = $remainingMin ? "~{$remainingMin} dk kaldı" : ($remainingSteps > 0 ? '<1 dk kaldı' : 'Sonuç sayfasına geçiyorsun!');

    // Step bağlamına göre sağ panel görseli (her adım için tematik gradient + emoji + alt başlık)
    $stepKey = $stepDef['key'] ?? '';
    $rightPanelMap = [
        'target_degree'        => ['emoji' => '🎓', 'tag' => 'Akademik hedefin', 'gradient' => 'linear-gradient(135deg, #7e58bf 0%, #5e3f9c 100%)'],
        'target_field'         => ['emoji' => '🧬', 'tag' => 'İlgi alanın',       'gradient' => 'linear-gradient(135deg, #6c47a8 0%, #a07ed9 100%)'],
        'study_language'       => ['emoji' => '🌍', 'tag' => 'Eğitim dili',       'gradient' => 'linear-gradient(135deg, #5e3f9c 0%, #b79ae9 100%)'],
        'germany_motivation'   => ['emoji' => '✨', 'tag' => 'Motivasyon',        'gradient' => 'linear-gradient(135deg, #7e58bf 0%, #c4a5ff 100%)'],
        'start_term'           => ['emoji' => '📅', 'tag' => 'Başlangıç dönemi',  'gradient' => 'linear-gradient(135deg, #6a47a8 0%, #a07ed9 100%)'],
        'current_education_level' => ['emoji' => '📚', 'tag' => 'Mevcut eğitim',   'gradient' => 'linear-gradient(135deg, #7e58bf 0%, #6a47a8 100%)'],
        'high_school_type'     => ['emoji' => '🏫', 'tag' => 'Lise türü',         'gradient' => 'linear-gradient(135deg, #5e3f9c 0%, #7e58bf 100%)'],
        'gpa_range'            => ['emoji' => '📊', 'tag' => 'Akademik başarı',   'gradient' => 'linear-gradient(135deg, #6a47a8 0%, #b79ae9 100%)'],
        'german_level'         => ['emoji' => '🇩🇪', 'tag' => 'Almanca seviyesi',  'gradient' => 'linear-gradient(135deg, #7e58bf 0%, #a07ed9 100%)'],
        'english_level'        => ['emoji' => '🇬🇧', 'tag' => 'İngilizce seviyesi','gradient' => 'linear-gradient(135deg, #6c47a8 0%, #c4a5ff 100%)'],
        'language_certificate' => ['emoji' => '📜', 'tag' => 'Dil sertifikası',   'gradient' => 'linear-gradient(135deg, #5e3f9c 0%, #a07ed9 100%)'],
        'finance_method'       => ['emoji' => '💰', 'tag' => 'Finansman',         'gradient' => 'linear-gradient(135deg, #6a47a8 0%, #b79ae9 100%)'],
        'monthly_budget'       => ['emoji' => '💶', 'tag' => 'Aylık bütçe',       'gradient' => 'linear-gradient(135deg, #7e58bf 0%, #5e3f9c 100%)'],
        'tuition_tolerance'    => ['emoji' => '🎟️', 'tag' => 'Öğrenim ücreti',    'gradient' => 'linear-gradient(135deg, #6a47a8 0%, #a07ed9 100%)'],
        'preferred_cities'     => ['emoji' => '🏙️', 'tag' => 'Tercih şehirler',   'gradient' => 'linear-gradient(135deg, #7e58bf 0%, #c4a5ff 100%)'],
        'living_priority'      => ['emoji' => '🏡', 'tag' => 'Yaşam tarzı',       'gradient' => 'linear-gradient(135deg, #5e3f9c 0%, #b79ae9 100%)'],
        'has_aps'              => ['emoji' => '📋', 'tag' => 'APS durumu',         'gradient' => 'linear-gradient(135deg, #6c47a8 0%, #a07ed9 100%)'],
        'visa_readiness'       => ['emoji' => '✈️', 'tag' => 'Vize hazırlığı',    'gradient' => 'linear-gradient(135deg, #7e58bf 0%, #6a47a8 100%)'],
        'mentorde_help_areas'  => ['emoji' => '🤝', 'tag' => 'Destek alanları',   'gradient' => 'linear-gradient(135deg, #5e3f9c 0%, #c4a5ff 100%)'],
    ];
    $rightPanel = $rightPanelMap[$stepKey] ?? ['emoji' => '🎯', 'tag' => 'UniMatch', 'gradient' => 'linear-gradient(135deg, #7e58bf 0%, #a07ed9 100%)'];

    // Filter sayısı azalma hissi (öncekiyle karşılaştırma için)
    $totalPrograms = $totalPrograms ?? 13000;
    $liveCount = $liveCount ?? $totalPrograms;
    $filterPct = $totalPrograms > 0 ? min(100, max(0, (int) round(100 - ($liveCount / $totalPrograms * 100)))) : 0;
@endphp

{{-- Pill segmented progress (sağ panel görsel anchor olduğu için bg-blobs gereksiz) --}}
<div class="sb-pill-meta">
    <span>Adım <span class="sb-pill-step">{{ $currentStep }}</span> / {{ $totalSteps }}</span>
    <span style="color:#7e58bf;">⏱ {{ $remainingLabel }}</span>
    <span>%{{ $progress }}</span>
</div>
<div class="sb-pill-progress" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Wizard ilerlemesi: %{{ $progress }}">
    @for($i = 1; $i <= $totalSteps; $i++)
        @php $cls = $i < $currentStep ? 'is-done' : ($i === $currentStep ? 'is-active' : ''); @endphp
        <span class="sb-pill {{ $cls }}"></span>
    @endfor
</div>

<div class="sb-split">
<div class="sb-card sb-split-left" role="region" aria-labelledby="sb-step-title-{{ $currentStep }}">
    <h1 class="sb-title" id="sb-step-title-{{ $currentStep }}" tabindex="-1">{{ $stepDef['title'] }}</h1>
    <span class="sr-only">Adım {{ $currentStep }} / {{ $totalSteps }}. Yön tuşları veya Tab ile gez, Enter ile seç ve devam et.</span>
    @if(! empty($stepDef['subtitle']))
        <p class="sb-subtitle">{{ $stepDef['subtitle'] }}</p>
    @endif

    <form method="POST" action="{{ route('uni-match.step.save', ['n' => $currentStep]) }}" id="stepForm">
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
                            <span class="sb-option-icon">{!! \App\Support\LucideIcons::renderOrEmoji($opt['icon'] ?? null, 32) !!}</span>
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
                            <span class="sb-option-icon">{!! \App\Support\LucideIcons::renderOrEmoji($opt['icon'] ?? null, 32) !!}</span>
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
        @elseif($stepDef['type'] === 'searchable_cities')
            @php
                $checked = is_array($answer) ? array_map('strval', $answer) : [];
                $maxSel = (int) ($stepDef['max'] ?? 5);
                $popular = $stepDef['popular'] ?? [];
                $popularValues = array_map(fn($o) => (string) $o['value'], $popular);
            @endphp

            <div data-cities-widget data-max="{{ $maxSel }}">
                {{-- Counter --}}
                <div style="font-size: 12.5px; color: #6b5894; margin-bottom: 10px; text-align: center;">
                    <span data-cities-counter>{{ count($checked) }}</span>/{{ $maxSel }} seçildi
                </div>

                {{-- Search input --}}
                <div style="position: relative; margin-bottom: 14px;">
                    <input type="text" data-cities-search
                           placeholder="🔍 Şehir ara (örn. Saarbrücken, Trier, Lüneburg)..."
                           autocomplete="off"
                           style="width: 100%; padding: 13px 16px; font-size: 14px; border: 2px solid #d4c5e8; border-radius: 10px; background: #fff; outline: none; font-family: inherit; transition: border-color .15s;">
                    <div data-cities-suggestions
                         style="position: absolute; top: 100%; left: 0; right: 0; margin-top: 4px; background: #fff; border: 1px solid #d4c5e8; border-radius: 10px; max-height: 280px; overflow-y: auto; box-shadow: 0 8px 24px rgba(126,88,191,.18); z-index: 10; display: none;">
                    </div>
                </div>

                {{-- Selected chips --}}
                <div data-cities-chips
                     style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; min-height: {{ count($checked) > 0 ? '0' : '0' }};">
                </div>

                {{-- Popular grid --}}
                <div style="font-size: 12px; font-weight: 600; color: #6b5894; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px;">
                    Popüler Şehirler ({{ count($popular) }})
                </div>
                <div class="sb-options" data-cities-popular>
                    @foreach($popular as $opt)
                        <label class="sb-option" data-city-value="{{ $opt['value'] }}" style="cursor: pointer;">
                            @if(! empty($opt['icon']))
                                <span class="sb-option-icon">{!! \App\Support\LucideIcons::renderOrEmoji($opt['icon'] ?? null, 32) !!}</span>
                            @endif
                            <div class="sb-option-text">
                                <div class="sb-option-label">{{ $opt['label'] }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Hidden inputs for form submission (JS-managed) --}}
                <div data-cities-inputs style="display: none;">
                    @foreach($checked as $val)
                        <input type="hidden" name="{{ $stepDef['key'] }}[]" value="{{ $val }}">
                    @endforeach
                </div>
            </div>

            {{-- Initial state for JS --}}
            @php
                $cityLabels = [];
                foreach ($popular as $p) { $cityLabels[(string) $p['value']] = (string) $p['label']; }
            @endphp
            <script type="application/json" id="citiesState">
                {!! json_encode([
                    'all'      => $allCities ?? [],
                    'popular'  => $popularValues,
                    'labels'   => $cityLabels,
                    'selected' => array_values($checked),
                    'max'      => $maxSel,
                    'fieldKey' => $stepDef['key'],
                ], JSON_UNESCAPED_UNICODE) !!}
            </script>
        @endif

        <div class="sb-nav">
            <div class="sb-nav-inner">
                @if($currentStep > 1)
                    <a href="{{ route('uni-match.step', ['n' => $currentStep - 1]) }}" class="sb-btn sb-btn-ghost">
                        <span style="font-size:16px;">←</span> Geri
                    </a>
                @else
                    <a href="{{ route('uni-match.landing') }}" class="sb-btn sb-btn-ghost">Vazgeç</a>
                @endif
                <button type="submit" class="sb-btn sb-btn-primary" id="nextBtn">
                    {{ $currentStep >= $totalSteps ? 'Sonuçları Göster' : 'Devam Et' }}
                    <span style="font-size: 16px;">→</span>
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Sağ panel: bağlamsal görsel + canlı filter count --}}
<aside class="sb-split-right" style="background: {{ $rightPanel['gradient'] }};" aria-label="Filtreleme görseli">
    <div class="sb-rp-bg" aria-hidden="true">
        <span class="sb-rp-shape sb-rp-shape-1"></span>
        <span class="sb-rp-shape sb-rp-shape-2"></span>
        <span class="sb-rp-shape sb-rp-shape-3"></span>
    </div>

    <div class="sb-rp-content">
        <div class="sb-rp-tag">{{ $rightPanel['tag'] }}</div>
        <div class="sb-rp-emoji" aria-hidden="true">{{ $rightPanel['emoji'] }}</div>

        {{-- Canlı filter count card --}}
        <div class="sb-rp-counter" data-live-counter>
            <div class="sb-rp-counter-label">📊 Cevaplarınla uyumlu</div>
            <div class="sb-rp-counter-num">
                <span data-count-target="{{ $liveCount }}">{{ number_format($liveCount, 0, ',', '.') }}</span>
                <span class="sb-rp-counter-suffix">program</span>
            </div>
            <div class="sb-rp-counter-meta">
                {{ number_format($totalPrograms, 0, ',', '.') }} toplam programdan
                @if($filterPct > 0)
                    · <strong>%{{ $filterPct }}</strong> daraltıldı
                @endif
            </div>
            <div class="sb-rp-counter-bar" aria-hidden="true">
                <div class="sb-rp-counter-bar-fill" style="width: {{ max(8, 100 - $filterPct) }}%;"></div>
            </div>
            <div class="sb-rp-counter-hint">
                @if($currentStep < $totalSteps)
                    Sonraki cevaplar bu sayıyı daha da daraltacak.
                @else
                    Sıralı 10 öneri için son adım!
                @endif
            </div>
        </div>

        <div class="sb-rp-trust">
            <div class="sb-rp-trust-item">
                <strong>13K+</strong>
                <span>program kataloğu</span>
            </div>
            <div class="sb-rp-trust-item">
                <strong>331</strong>
                <span>üniversite</span>
            </div>
            <div class="sb-rp-trust-item">
                <strong>9</strong>
                <span>faktör skor</span>
            </div>
        </div>
    </div>
</aside>

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

    // Klavye: Enter ile submit (cities search input hariç)
    document.addEventListener('keydown', function(e){
        if (e.key === 'Enter' && !e.shiftKey
            && e.target.tagName !== 'TEXTAREA'
            && !e.target.matches('[data-cities-search]')) {
            e.preventDefault();
            var form = document.getElementById('stepForm');
            if (form) form.requestSubmit ? form.requestSubmit() : form.submit();
        }
    });

    // Submit feedback: button spinner + disable (Linear-tarzı micro-interaction)
    var stepForm = document.getElementById('stepForm');
    if (stepForm) {
        stepForm.addEventListener('submit', function(){
            var btn = document.getElementById('nextBtn');
            if (btn && !btn.classList.contains('is-loading')) {
                btn.classList.add('is-loading');
                btn.disabled = true;
            }
        });
    }

    // ────── Searchable Cities Widget ──────
    var citiesStateEl = document.getElementById('citiesState');
    if (citiesStateEl) {
        var state = JSON.parse(citiesStateEl.textContent);
        var widget    = document.querySelector('[data-cities-widget]');
        var search    = widget.querySelector('[data-cities-search]');
        var suggBox   = widget.querySelector('[data-cities-suggestions]');
        var chipsBox  = widget.querySelector('[data-cities-chips]');
        var popularEl = widget.querySelector('[data-cities-popular]');
        var inputsBox = widget.querySelector('[data-cities-inputs]');
        var counter   = widget.querySelector('[data-cities-counter]');

        var selected = new Set(state.selected || []);
        var maxSel   = parseInt(state.max || 5, 10);
        var fieldKey = state.fieldKey || 'preferred_cities';
        var popularSet = new Set(state.popular || []);

        function labelFor(val){
            return state.labels[val] || val;
        }

        function rebuildChips(){
            chipsBox.innerHTML = '';
            selected.forEach(function(val){
                var chip = document.createElement('span');
                chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:6px 10px 6px 12px;background:linear-gradient(135deg,#7e58bf,#a07ed9);color:#fff;border-radius:18px;font-size:12.5px;font-weight:600;box-shadow:0 2px 6px rgba(126,88,191,.25);';
                chip.innerHTML = '<span>' + labelFor(val) + '</span>';
                var x = document.createElement('button');
                x.type = 'button';
                x.textContent = '×';
                x.setAttribute('aria-label', 'Kaldır: ' + labelFor(val));
                x.style.cssText = 'background:rgba(255,255,255,.22);border:0;color:#fff;width:18px;height:18px;border-radius:50%;font-size:13px;line-height:1;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:0;font-family:inherit;';
                x.addEventListener('click', function(){ removeCity(val); });
                chip.appendChild(x);
                chipsBox.appendChild(chip);
            });
        }

        function rebuildInputs(){
            inputsBox.innerHTML = '';
            selected.forEach(function(val){
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = fieldKey + '[]';
                inp.value = val;
                inputsBox.appendChild(inp);
            });
        }

        function syncPopularState(){
            popularEl.querySelectorAll('[data-city-value]').forEach(function(el){
                var v = el.getAttribute('data-city-value');
                el.classList.toggle('selected', selected.has(v));
                // Limit dolu ve seçili değilse görsel olarak kısıtla
                if (selected.size >= maxSel && !selected.has(v)) {
                    el.style.opacity = '.45';
                    el.style.pointerEvents = 'none';
                } else {
                    el.style.opacity = '';
                    el.style.pointerEvents = '';
                }
            });
        }

        function updateCounter(){
            counter.textContent = selected.size;
        }

        function addCity(val){
            if (!val) return;
            if (selected.size >= maxSel && !selected.has(val)) return;
            selected.add(val);
            // Popüler değilse label'ı kaydet (ham value)
            if (!state.labels[val]) state.labels[val] = val;
            refresh();
            search.value = '';
            suggBox.style.display = 'none';
            search.focus();
        }

        function removeCity(val){
            selected.delete(val);
            refresh();
        }

        function refresh(){
            rebuildChips();
            rebuildInputs();
            syncPopularState();
            updateCounter();
        }

        // Popular tıklama
        popularEl.querySelectorAll('[data-city-value]').forEach(function(el){
            el.addEventListener('click', function(e){
                e.preventDefault();
                var v = el.getAttribute('data-city-value');
                if (selected.has(v)) {
                    removeCity(v);
                } else {
                    addCity(v);
                }
            });
        });

        // Search input → öneri kutusu
        function renderSuggestions(query){
            suggBox.innerHTML = '';
            if (!query || query.length < 1) {
                suggBox.style.display = 'none';
                return;
            }
            var q = query.toLowerCase();
            // Filtre: Tüm şehirlerden, popüler olmayanlar tercih edilsin (popüler zaten kart olarak var)
            var matches = (state.all || [])
                .filter(function(c){ return c.toLowerCase().indexOf(q) !== -1; })
                .slice(0, 12);
            if (matches.length === 0) {
                suggBox.innerHTML = '<div style="padding:12px 16px;color:#9c8bb9;font-size:13px;">Eşleşen şehir bulunamadı.</div>';
                suggBox.style.display = 'block';
                return;
            }
            matches.forEach(function(city){
                var isSelected = selected.has(city);
                var isPopular  = popularSet.has(city);
                var item = document.createElement('div');
                item.style.cssText = 'padding:10px 16px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f0eaf7;font-size:13.5px;color:#3d2c5e;';
                item.innerHTML = '<span>' + city + (isPopular ? ' <span style="font-size:10px;color:#a07ed9;font-weight:600;">★ popüler</span>' : '') + '</span>'
                    + '<span style="font-size:11px;color:#9c8bb9;">' + (isSelected ? '✓ seçili' : 'Ekle') + '</span>';
                item.addEventListener('mouseenter', function(){ item.style.background = '#f9f6fc'; });
                item.addEventListener('mouseleave', function(){ item.style.background = ''; });
                item.addEventListener('click', function(){
                    if (isSelected) {
                        removeCity(city);
                    } else {
                        addCity(city);
                    }
                });
                suggBox.appendChild(item);
            });
            suggBox.style.display = 'block';
        }
        search.addEventListener('input', function(){ renderSuggestions(search.value.trim()); });
        search.addEventListener('focus', function(){
            if (search.value.trim()) renderSuggestions(search.value.trim());
        });
        document.addEventListener('click', function(e){
            if (!widget.contains(e.target)) suggBox.style.display = 'none';
        });

        // İlk render
        refresh();
    }
})();
</script>
@endpush
@endsection

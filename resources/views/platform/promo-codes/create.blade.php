@extends('platform.layouts.app')

@section('title', 'Yeni İndirim Kodu — Platform Owner')

@push('styles')
<style>
    .pc-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 800px) { .pc-form-grid { grid-template-columns: 1fr; } }

    .pc-form-hint { display: block; font-size: 11px; color: var(--plat-muted); margin-top: 4px; }
    .pc-form-section { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .pc-form-section h3 { margin: 0 0 14px; color: #fff; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; }

    .pc-type-help { font-size: 12px; color: var(--plat-accent-2); background: var(--plat-accent-bg); padding: 10px 12px; border-radius: 8px; margin-top: 8px; display: none; }
    .pc-type-help.show { display: block; }

    .pc-checkbox-row { display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: var(--plat-panel-2); border: 1px solid var(--plat-border); border-radius: 8px; }
    .pc-checkbox-row input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--plat-accent); cursor: pointer; }
    .pc-checkbox-row label { color: #fff; font-weight: 600; cursor: pointer; }
</style>
@endpush

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Yeni İndirim Kodu</h1>
        <p class="plat-page-sub">Kupon kodu oluştur — abonelik faturalarında otomatik uygulanacak</p>
    </div>
    <a href="{{ route('platform.promo-codes') }}" class="plat-btn plat-btn-ghost">
        <x-icon name="arrow-left" size="14" /> Listeye dön
    </a>
</div>

<form method="POST" action="{{ route('platform.promo-codes.store') }}" autocomplete="off">
    @csrf

    {{-- Temel --}}
    <div class="pc-form-section">
        <h3><x-icon name="tag" size="16" /> Temel Bilgiler</h3>
        <div class="pc-form-grid">
            <div class="plat-form-group">
                <label class="plat-form-label" for="code">Kod *</label>
                <input type="text" id="code" name="code" class="plat-input" value="{{ old('code') }}" required
                       style="text-transform:uppercase;font-family:ui-monospace,monospace;font-weight:700;letter-spacing:.5px;"
                       placeholder="ORN: SUMMER25, EARLYBIRD" maxlength="50">
                <small class="pc-form-hint">3-50 karakter, sadece harf/rakam/_/-. Büyük harfe çevrilir.</small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="type">İndirim Tipi *</label>
                <select id="type" name="type" class="plat-select" required>
                    <option value="">— seçin —</option>
                    <option value="percentage"          {{ old('type') === 'percentage'          ? 'selected' : '' }}>Yüzde indirim (%)</option>
                    <option value="fixed_amount"        {{ old('type') === 'fixed_amount'        ? 'selected' : '' }}>Sabit EUR indirim</option>
                    <option value="first_n_months_free" {{ old('type') === 'first_n_months_free' ? 'selected' : '' }}>İlk N ay ücretsiz</option>
                </select>
                <div class="pc-type-help" id="type-help-percentage">
                    Faturanın yüzdesi kadar indirim. Örnek: 20 girersen %20 off.
                </div>
                <div class="pc-type-help" id="type-help-fixed_amount">
                    Faturadan sabit EUR düşülür. Örnek: 50 girersen €50 indirim (fatura küçükse o tutara kadar).
                </div>
                <div class="pc-type-help" id="type-help-first_n_months_free">
                    İlk N ay tam %100 ücretsiz. Aşağıda kaç ay olduğunu belirt.
                </div>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="value">Değer *</label>
                <input type="number" step="0.01" min="0.01" id="value" name="value" class="plat-input"
                       value="{{ old('value') }}" required placeholder="20.00">
                <small class="pc-form-hint">Yüzde için %, sabit için EUR, ücretsiz ay için %100 olduğundan 100 girin.</small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="duration_months">Süre (Ay) <span style="text-transform:none;color:var(--plat-muted);">— ücretsiz ay tipinde zorunlu</span></label>
                <input type="number" min="1" max="60" id="duration_months" name="duration_months" class="plat-input"
                       value="{{ old('duration_months') }}" placeholder="3">
                <small class="pc-form-hint">Tek seferlik percentage/fixed için boş bırakın.</small>
            </div>
        </div>
    </div>

    {{-- Sınırlar --}}
    <div class="pc-form-section">
        <h3><x-icon name="shield" size="16" /> Sınırlar & Kapsam</h3>
        <div class="pc-form-grid">
            <div class="plat-form-group">
                <label class="plat-form-label" for="applies_to_tier">Geçerli Tier</label>
                <select id="applies_to_tier" name="applies_to_tier" class="plat-select">
                    @foreach($tiers as $t)
                        <option value="{{ $t['value'] }}" {{ old('applies_to_tier') === $t['value'] ? 'selected' : '' }}>{{ $t['label'] }}</option>
                    @endforeach
                </select>
                <small class="pc-form-hint">Belirtirseniz sadece o tier'a uygulanır; boş ise hepsi.</small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="max_uses">Max Kullanım</label>
                <input type="number" min="1" id="max_uses" name="max_uses" class="plat-input" value="{{ old('max_uses') }}" placeholder="örn 100">
                <small class="pc-form-hint">Toplam kaç company kullanabilir? Boş = sınırsız.</small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="valid_from">Geçerlilik Başlangıç *</label>
                <input type="date" id="valid_from" name="valid_from" class="plat-input"
                       value="{{ old('valid_from', now()->format('Y-m-d')) }}" required>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="valid_until">Geçerlilik Bitiş *</label>
                <input type="date" id="valid_until" name="valid_until" class="plat-input"
                       value="{{ old('valid_until', now()->addMonths(3)->format('Y-m-d')) }}" required>
            </div>
        </div>

        <div class="plat-form-group">
            <label class="plat-form-label" for="description">Açıklama (opsiyonel)</label>
            <textarea id="description" name="description" class="plat-textarea" rows="2" maxlength="300"
                      placeholder="Yaz kampanyası — Gold tier'a özel %20 indirim, 3 ay geçerli.">{{ old('description') }}</textarea>
        </div>

        <div class="pc-checkbox-row">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
            <label for="is_active">Kod hemen aktif olsun</label>
        </div>
    </div>

    {{-- Submit --}}
    <div style="display:flex;gap:10px;justify-content:flex-end;">
        <a href="{{ route('platform.promo-codes') }}" class="plat-btn plat-btn-ghost">İptal</a>
        <button type="submit" class="plat-btn plat-btn-primary">
            <x-icon name="check" size="14" /> Kodu Oluştur
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var typeSelect = document.getElementById('type');
    var helps = {
        'percentage':          document.getElementById('type-help-percentage'),
        'fixed_amount':        document.getElementById('type-help-fixed_amount'),
        'first_n_months_free': document.getElementById('type-help-first_n_months_free'),
    };
    function syncHelp() {
        Object.keys(helps).forEach(function(k){
            if (helps[k]) helps[k].classList.toggle('show', typeSelect.value === k);
        });
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', syncHelp);
        syncHelp();
    }

    // Code input uppercase live
    var codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function(){
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9_\-]/g, '');
        });
    }
})();
</script>
@endpush

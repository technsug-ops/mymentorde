@extends('guest.layouts.app')

@section('title', 'Ön Kayıt Formu')
@section('page_title', 'Kayıt Süreci - Ön Kayıt Formu')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ── grf-* Guest Registration Form scoped ── */

/* Metrics strip */
.grf-metrics {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
@media(max-width:1024px){ .grf-metrics { grid-template-columns: 1fr 1fr; } }
@media(max-width:600px){ .grf-metrics { grid-template-columns: 1fr; } }
.grf-metric {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 14px;
    padding: 14px 18px;
}
.grf-metric-label { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--u-muted); margin-bottom: 6px; }
.grf-metric-val   { font-size: 28px; font-weight: 800; color: var(--u-text); line-height: 1; }

/* Progress */
.grf-progress-card {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 16px;
}
.grf-progress-label { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; color: var(--u-muted); margin-bottom: 6px; }
.grf-progress-bar { height: 8px; border-radius: 999px; background: var(--u-line); overflow: hidden; }
.grf-progress-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #1d66d6, #3b82f6); transition: width .4s ease; }

/* Section nav */
.grf-nav {
    position: sticky;
    top: 8px;
    z-index: 30;
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding: 8px 10px;
    border: 1px solid var(--u-line);
    border-radius: 12px;
    background: var(--u-card);
    box-shadow: var(--u-shadow);
    margin-bottom: 14px;
    scrollbar-width: thin;
}
.grf-nav a {
    white-space: nowrap;
    border: 1px solid var(--u-line);
    border-radius: 999px;
    padding: 5px 14px;
    color: var(--u-text);
    font-size: 14px; font-weight: 600;
    background: var(--u-card);
    text-decoration: none;
    display: inline-flex; align-items: center;
    transition: background .15s, border-color .15s, color .15s;
    flex-shrink: 0;
}
.grf-nav a:hover { background: #f0f6ff; border-color: #93c5fd; color: var(--u-brand); text-decoration: none; }
.grf-nav a.active {
    background: var(--u-brand); border-color: var(--u-brand);
    color: #fff; font-weight: 700;
}
.grf-nav a.done {
    background: rgba(22,163,74,.1); border-color: rgba(22,163,74,.4);
    color: var(--u-ok, #16a34a);
}
.grf-nav-meta {
    display: flex; justify-content: space-between; align-items: center;
    gap: 10px; margin-bottom: 14px;
}
.grf-nav-counter {
    font-weight: 700; color: var(--u-brand);
    background: #eff6ff; border: 1px solid #bfdbfe;
    border-radius: 999px; padding: 5px 14px; font-size: 15px;
}

/* Step panels */
.grf-step-panels { position: relative; }
.grf-panel {
    border: 1px solid var(--u-line);
    border-radius: 14px;
    margin-bottom: 14px;
    background: var(--u-card);
    overflow: hidden;
    display: none;
}
.grf-panel.active { display: block; }
.grf-panel-head {
    padding: 14px 18px;
    background: linear-gradient(180deg, #f7fbff, #eef5ff);
    border-bottom: 1px solid var(--u-line);
    font-size: 16px; font-weight: 700; color: #143762;
}
.grf-panel-body { padding: 16px 18px; }

/* Form grid */
.grf-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}
@media(max-width:740px){ .grf-form-grid { grid-template-columns: 1fr; } }
.form-group { display: grid; gap: 6px; }
.label-row { display: flex; align-items: center; gap: 6px; }
.label-row label { font-weight: 700; color: var(--u-text); font-size: 15px; }
.required-star { color: var(--u-danger); font-weight: 700; }

/* Help toggle */
.help-toggle-btn {
    display: inline-flex; align-items: center; gap: 4px;
    background: #eef4fb; border: 1px solid #c5d8f2; border-radius: 6px;
    color: #2e5fa3; font-size: 13px; font-weight: 600;
    padding: 3px 9px; cursor: pointer;
    transition: background .15s;
}
.help-toggle-btn:hover { background: #ddeaf9; }
.help-panel {
    display: none; font-size: 14px; color: #34597f; line-height: 1.65;
    background: #f4f9ff; border: 1px solid #cfe0f7; border-radius: 8px;
    padding: 8px 12px;
}
.help-panel.open { display: block; }
.field-error { color: var(--u-danger); font-size: 14px; }

/* Form inputs */
.grf-panel-body .form-group input,
.grf-panel-body .form-group select,
.grf-panel-body .form-group textarea {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid var(--u-line); border-radius: 8px;
    font-size: 16px; color: var(--u-text); background: var(--u-card);
    font-family: inherit; box-sizing: border-box;
    transition: border-color .15s, box-shadow .15s;
}
.grf-panel-body .form-group input:focus,
.grf-panel-body .form-group select:focus,
.grf-panel-body .form-group textarea:focus {
    outline: none; border-color: var(--u-brand);
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}

/* Sticky footer */
.grf-sticky {
    position: sticky; bottom: 8px; z-index: 20;
    display: flex; justify-content: space-between; gap: 8px; align-items: center;
    flex-wrap: wrap;
    border: 1px solid #bfd2ef; background: rgba(241,247,255,.97);
    border-radius: 12px; padding: 10px 14px;
    backdrop-filter: blur(8px);
    box-shadow: 0 4px 16px rgba(37,99,235,.1);
}
.grf-sticky-note { color: var(--u-muted); font-size: 12px; }

/* ── Minimalist overrides ── */
.jm-minimalist .grf-panel-head {
    background: #e2e5ec !important;
    color: var(--u-text, #1a1a1a) !important;
    border-bottom: 1px solid rgba(0,0,0,.10) !important;
}
.jm-minimalist .grf-progress-fill { background: var(--u-brand, #111) !important; }
.jm-minimalist .grf-nav a:hover { background: var(--u-bg) !important; }
</style>
@endpush

@section('content')
@php
    $draft = is_array($guest?->registration_form_draft) ? $guest->registration_form_draft : [];
    $allFields = collect($registrationFieldGroups ?? [])->flatMap(fn($g) => $g['fields'] ?? []);
    $requiredFields = $allFields->filter(fn($f) => !empty($f['required']));
    $requiredTotal = $requiredFields->count();
    $requiredFilled = $requiredFields->filter(function($f) use ($draft, $guest) {
        $k = (string)($f['key'] ?? '');
        $v = $draft[$k] ?? ($guest?->{$k} ?? null);
        return trim((string)$v) !== '';
    })->count();
    $completionPct = $requiredTotal > 0 ? (int)round(($requiredFilled / $requiredTotal) * 100) : 0;
    $groupCount = count($registrationFieldGroups ?? []);
@endphp

{{-- ── Top Bar ── --}}
<div class="card" style="margin-bottom:14px;">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div class="muted" style="font-size:var(--tx-sm);">Adım adım ilerle. Her adımda kaydet, sonunda tek tıkla gönder.</div>
        <div style="display:flex;gap:8px;">
            <a class="btn alt" href="{{ route('guest.registration.documents') }}" style="font-size:var(--tx-xs);">Belgelere Geç</a>
            <a class="btn alt" href="{{ route('guest.dashboard') }}" style="font-size:var(--tx-xs);">Dashboard</a>
        </div>
    </div>
</div>

{{-- ── Metrics Strip ── --}}
<div class="grf-metrics">
    <div class="grf-metric">
        <div class="grf-metric-label">Zorunlu Alan</div>
        <div class="grf-metric-val">{{ $requiredTotal }}</div>
    </div>
    <div class="grf-metric">
        <div class="grf-metric-label">Dolu Zorunlu</div>
        <div class="grf-metric-val">{{ $requiredFilled }}</div>
    </div>
    <div class="grf-metric">
        <div class="grf-metric-label">Tamamlama</div>
        <div class="grf-metric-val">%{{ $completionPct }}</div>
    </div>
    <div class="grf-metric">
        <div class="grf-metric-label">Son Kayıt</div>
        <div class="grf-metric-val" style="font-size:var(--tx-sm);line-height:1.3;">{{ $guest?->registration_form_draft_saved_at ?: '-' }}</div>
    </div>
</div>

{{-- ── Progress ── --}}
<div class="grf-progress-card">
    <div class="grf-progress-label">
        <span>Form İlerlemesi (Zorunlu Alan Bazlı)</span>
        <span style="color:var(--u-brand);font-size:var(--tx-sm);font-weight:800;">%{{ $completionPct }}</span>
    </div>
    <div class="grf-progress-bar">
        <div class="grf-progress-fill" style="width:{{ $completionPct }}%;"></div>
    </div>
</div>

{{-- ── Form Card ── --}}
<div class="card">
    <div class="card-body">
        {{-- Section nav --}}
        <div class="grf-nav" id="sectionNav">
            @foreach(($registrationFieldGroups ?? []) as $group)
                <a href="#rg-{{ $loop->index }}" data-step-link="{{ $loop->index }}" data-step-pill="{{ $loop->index }}">
                    {{ $group['title'] ?? 'Bölüm' }}
                    <span data-step-missing="{{ $loop->index }}" style="font-size:var(--tx-xs);margin-left:4px;font-weight:700;"></span>
                </a>
            @endforeach
        </div>
        <div class="grf-nav-meta">
            <div class="grf-nav-counter" id="sectionNavMeta">Adım 1 / {{ $groupCount }}</div>
            <div class="muted" style="font-size:var(--tx-xs);">Sekme geçişinde ekran sabit kalır; sadece ilgili bölüm görünür.</div>
        </div>

        <form id="guestRegistrationForm" method="POST"
              action="{{ route('guest.registration.autosave') }}"
              data-ajax-save-url="{{ route('guest.registration.ajax-save') }}">
            @csrf
            <input type="hidden" name="draft_saved_at" value="{{ optional($guest?->registration_form_draft_saved_at)->toIso8601String() }}">

            <div id="stepPanelsStage" class="grf-step-panels">
                @foreach(($registrationFieldGroups ?? []) as $group)
                    <section id="rg-{{ $loop->index }}" class="grf-panel" data-step="{{ $loop->index }}">
                        <div class="grf-panel-head">
                            Adım {{ $loop->iteration }} / {{ $groupCount }} — {{ $group['title'] ?? 'Alanlar' }}
                        </div>
                        <div class="grf-panel-body">
                            <div class="grf-form-grid">
                                @foreach(($group['fields'] ?? []) as $field)
                                    @php
                                        $key         = (string)($field['key'] ?? '');
                                        $type        = (string)($field['type'] ?? 'text');
                                        $label       = (string)($field['label'] ?? $key);
                                        $required    = (bool)($field['required'] ?? false);
                                        $placeholder = (string)($field['placeholder'] ?? '');
                                        $max         = (int)($field['max'] ?? 255);
                                        $value       = old($key, $draft[$key] ?? ($guest?->{$key} ?? ''));
                                    @endphp
                                    @if($key === '') @continue @endif
                                    <div class="form-group" data-field-key="{{ $key }}">
                                        <div class="label-row">
                                            <label>{{ $label }} @if($required)<span class="required-star">*</span>@endif</label>
                                        </div>
                                        @if(!empty($field['help_text']))
                                            <button type="button" class="help-toggle-btn"
                                                    onclick="var p=this.nextElementSibling;p.classList.toggle('open');this.textContent=p.classList.contains('open')?'▲ Kapat':'ℹ Rehber';">ℹ Rehber</button>
                                            <div class="help-panel">{{ $field['help_text'] }}</div>
                                        @endif
                                        @if($type === 'select')
                                            <select class="{{ $required ? 'final-required' : '' }}" name="{{ $key }}" data-required="{{ $required ? '1' : '0' }}">
                                                <option value="">Seçiniz</option>
                                                @foreach(($field['options'] ?? []) as $opt)
                                                    <option value="{{ $opt['value'] }}" @selected((string)$value === (string)$opt['value'])>{{ $opt['label'] }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($type === 'textarea')
                                            <textarea class="{{ $required ? 'final-required' : '' }}" name="{{ $key }}" rows="4" maxlength="{{ $max }}" placeholder="{{ $placeholder }}" data-required="{{ $required ? '1' : '0' }}">{{ $value }}</textarea>
                                        @elseif($type === 'date')
                                            <input class="{{ $required ? 'final-required' : '' }}" type="date" name="{{ $key }}" value="{{ $value }}" data-required="{{ $required ? '1' : '0' }}">
                                        @else
                                            @php
                                                $inputmode = match(true) {
                                                    $type === 'email'                              => 'email',
                                                    $type === 'phone'                              => 'tel',
                                                    $type === 'money' || str_contains($key, '_gpa') || $key === 'financial_budget_eur' => 'decimal',
                                                    $key === 'postal_code'                         => 'numeric',
                                                    default                                        => '',
                                                };
                                                $autocomplete = match($key) {
                                                    'email'        => 'email',
                                                    'phone'        => 'tel',
                                                    'first_name'   => 'given-name',
                                                    'last_name'    => 'family-name',
                                                    'address_full' => 'street-address',
                                                    'postal_code'  => 'postal-code',
                                                    'city'         => 'address-level2',
                                                    default        => '',
                                                };
                                            @endphp
                                            <input class="{{ $required ? 'final-required' : '' }}"
                                                   type="{{ $type === 'email' ? 'email' : ($type === 'phone' ? 'tel' : 'text') }}"
                                                   name="{{ $key }}"
                                                   value="{{ $value }}"
                                                   maxlength="{{ $max }}"
                                                   placeholder="{{ $placeholder }}"
                                                   data-required="{{ $required ? '1' : '0' }}"
                                                   @if($inputmode) inputmode="{{ $inputmode }}" @endif
                                                   @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif>
                                        @endif
                                        @error($key)
                                            <div class="field-error">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>

            <div style="margin:0 0 12px;padding:12px 14px;border:1px dashed var(--u-line);border-radius:10px;background:var(--u-bg);">
                <p class="muted" style="margin:0;font-size:var(--tx-sm);">
                    Belge/sertifika yüklemeleri için <a href="{{ route('guest.registration.documents') }}" style="color:var(--u-brand);font-weight:600;">Ön Kayıt Belgeleri</a> ekranına geçebilirsin.
                </p>
            </div>

            {{-- Sticky action bar --}}
            <div class="grf-sticky">
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <button id="btnPrevStep" class="btn" type="button">← Geri</button>
                    <button id="btnNextStep" class="btn ok" type="button">İleri →</button>
                    <button id="btnFirstMissing" class="btn alt" type="button">İlk Eksik Zorunlu</button>
                    <span id="stepMeta" class="grf-sticky-note"></span>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button id="btnDraftSave" class="btn" type="submit">Taslak Kaydet</button>
                    <button id="btnFinalSubmit" class="btn ok" type="submit"
                            formaction="{{ route('guest.registration.submit') }}">Formu Gönder ✓</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script defer src="{{ Vite::asset('resources/js/guest-registration-form.js') }}"></script>
<script>
(function(){
    var _orig = window.__designToggle;
    window.__designToggle = function(d){
        if(_orig) _orig(d);
        setTimeout(function(){ document.documentElement.classList.toggle('jm-minimalist', d==='minimalist'); }, 50);
    };
})();
</script>
@endsection

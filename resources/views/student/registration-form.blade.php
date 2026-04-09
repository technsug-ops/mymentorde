@extends('student.layouts.app')

@section('title', 'Kayıt Formu')
@section('page_title', 'Kayıt Süreci - Form')

@push('head')
<style>
/* ── srf-* Student Registration Form — Step Pills Redesign ── */

/* ── Form Topbar with Step Pills ── */
.srf-topbar {
    background: var(--u-card); border-bottom: 1px solid var(--u-line);
    padding: 0 24px; display: flex; align-items: stretch;
    position: sticky; top: 0; z-index: 10; margin: -24px -24px 24px;
}
.srf-topbar-left {
    display: flex; align-items: center; gap: 14px;
    padding: 12px 0; flex: 1; min-width: 0;
}
.srf-topbar-title { font-size: 15px; font-weight: 700; color: var(--u-text); white-space: nowrap; }
.srf-topbar-div { width: 1px; height: 20px; background: var(--u-line); }

.srf-pills {
    display: flex; gap: 4px; overflow-x: auto;
    scrollbar-width: none; -ms-overflow-style: none;
}
.srf-pills::-webkit-scrollbar { display: none; }
.srf-pill {
    display: flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 99px;
    font-size: 11px; font-weight: 600; white-space: nowrap;
    cursor: pointer; transition: all .2s;
    border: 1.5px solid transparent;
    background: var(--u-bg); color: var(--u-muted);
}
.srf-pill.done { background: rgba(22,163,74,.08); color: var(--u-ok); }
.srf-pill.active {
    background: rgba(37,99,235,.06); color: var(--u-brand);
    border-color: var(--u-brand); box-shadow: 0 0 0 3px rgba(37,99,235,.06);
}
.srf-pill-num {
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 700;
}
.srf-pill.done .srf-pill-num { background: var(--u-ok); color: #fff; }
.srf-pill.active .srf-pill-num { background: var(--u-brand); color: #fff; }
.srf-pill:not(.done):not(.active) .srf-pill-num { background: var(--u-line); color: var(--u-muted); }

.srf-topbar-right {
    display: flex; align-items: center; gap: 8px; padding: 12px 0;
}
.srf-topbar-btn {
    padding: 7px 14px; border-radius: 8px; border: 1px solid var(--u-line);
    background: var(--u-card); font-size: 12px; font-weight: 600;
    color: var(--u-muted); text-decoration: none; transition: all .15s;
    display: inline-flex; align-items: center; gap: 5px;
}
.srf-topbar-btn:hover { background: var(--u-bg); color: var(--u-text); text-decoration: none; }

/* ── Form body ── */
.srf-body { display: flex; justify-content: center; }
.srf-center { width: 100%; max-width: 720px; margin: 0 auto; }

/* ── Step Context — full width above card ── */
.srf-step-ctx { margin-bottom: 20px; }
.srf-step-tag {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: var(--u-brand);
    background: rgba(37,99,235,.06); padding: 4px 12px; border-radius: 99px;
    margin-bottom: 10px;
}
.srf-step-title {
    font-size: 20px; font-weight: 800; letter-spacing: -.3px;
    margin-bottom: 8px; line-height: 1.3; color: var(--u-text);
}
.srf-step-why {
    font-size: 12px; color: var(--u-muted); line-height: 1.6;
    padding: 10px 14px; background: var(--u-bg);
    border-left: 3px solid var(--u-brand); border-radius: 0 8px 8px 0;
}

/* ── Form Card ── */
.srf-card {
    background: var(--u-card); border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,.06);
    padding: 28px 32px; margin-bottom: 20px;
    border: 1px solid var(--u-line);
}

/* ── Fields — 2-col grid ── */
.srf-form-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 18px;
}
.srf-form-grid .srf-form-group.srf-full { grid-column: 1 / -1; }
@media(max-width:640px){ .srf-form-grid { grid-template-columns: 1fr; } }

.srf-form-group { display: grid; gap: 5px; }
.srf-label-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.srf-label-row label { font-weight: 700; color: var(--u-text); font-size: 13px; }
.required-star { color: var(--u-danger); font-weight: 700; }
.srf-field-error { color: var(--u-danger); font-size: 13px; }

/* Prefilled + filled state */
.srf-prefilled {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 600; color: var(--u-ok);
    background: rgba(22,163,74,.08); padding: 1px 8px; border-radius: 4px;
}
.srf-form-group.is-filled input,
.srf-form-group.is-filled select {
    border-color: var(--u-ok);
    background: rgba(22,163,74,.02);
}

.form-section .srf-form-group input,
.form-section .srf-form-group select,
.form-section .srf-form-group textarea {
    width: 100%; padding: 11px 16px;
    border: 2px solid var(--u-line); border-radius: 8px;
    font-size: 16px; color: var(--u-text); background: var(--u-card);
    font-family: inherit; box-sizing: border-box;
    transition: border-color .2s, box-shadow .2s;
}
.form-section .srf-form-group input:focus,
.form-section .srf-form-group select:focus,
.form-section .srf-form-group textarea:focus {
    outline: none; border-color: var(--u-brand);
    box-shadow: 0 0 0 4px rgba(37,99,235,.06);
}

/* ── Navigation ── */
.srf-nav-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding-top: 4px; gap: 12px;
}
.srf-nav-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 24px; border-radius: 8px;
    font-size: 14px; font-weight: 700; cursor: pointer;
    border: none; transition: all .2s; font-family: inherit;
}
.srf-nav-btn.back { background: transparent; color: var(--u-muted); border: 1.5px solid var(--u-line); }
.srf-nav-btn.back:hover { background: var(--u-bg); color: var(--u-text); }
.srf-nav-btn.next { background: var(--u-brand); color: #fff; box-shadow: 0 4px 12px rgba(37,99,235,.2); }
.srf-nav-btn.next:hover { transform: translateY(-1px); }
.srf-nav-btn.finish { background: var(--u-ok); color: #fff; }
.srf-nav-mid { font-size: 11px; color: var(--u-muted); display: flex; align-items: center; gap: 6px; }
.srf-nav-mid kbd {
    background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 4px; padding: 2px 6px; font-size: 10px; font-family: inherit;
}

/* Panels (JS compat) */
.form-section { display: none; width: 100%; max-width: 680px; }
.form-section.active { display: block; animation: srfFadeUp .4s ease; }
@keyframes srfFadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

.srf-hidden { display: none; }

/* Autosave toast */
.srf-toast {
    position: fixed; bottom: 20px; right: 20px;
    background: var(--u-text); color: #fff;
    padding: 10px 18px; border-radius: 10px;
    font-size: 13px; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
    opacity: 0; transform: translateY(8px);
    transition: all .3s; z-index: 100; pointer-events: none;
}
.srf-toast.show { opacity: 1; transform: translateY(0); }

/* ── Responsive ── */
@media(max-width:900px){
    .srf-topbar { margin: -16px -16px 16px; padding: 0 16px; }
    .srf-pills { display: none; }
    .srf-topbar-div { display: none; }
    .srf-card { padding: 20px; }
}
@media(max-width:600px){
    .srf-topbar { margin: -12px -12px 12px; padding: 0 12px; flex-wrap: wrap; }
    .srf-step-title { font-size: 20px; }
    .srf-card { padding: 16px; border-radius: 12px; }
    .srf-nav-bar { flex-direction: column; }
    .srf-nav-btn { width: 100%; justify-content: center; }
    .srf-nav-mid { display: none; }
    .srf-topbar-right { width: 100%; padding: 0 0 10px; }
    .srf-topbar-btn { flex: 1; justify-content: center; font-size: 11px; padding: 6px 10px; }
}

/* ── Minimalist overrides ── */
.jm-minimalist .srf-card { box-shadow: none; border: 1px solid var(--u-line); }
.jm-minimalist .srf-step-why { border-left-color: var(--u-text); }
</style>
@endpush

@section('content')
@php
    $draft          = is_array($guestApplication?->registration_form_draft) ? $guestApplication->registration_form_draft : [];
    $allFields      = collect($registrationFieldGroups ?? [])->flatMap(fn($g) => $g['fields'] ?? []);
    $requiredFields = $allFields->filter(fn($f) => !empty($f['required']));
    $requiredTotal  = $requiredFields->count();
    $requiredFilled = $requiredFields->filter(function ($f) use ($draft, $guestApplication) {
        $k = (string) ($f['key'] ?? '');
        $v = $draft[$k] ?? ($guestApplication?->{$k} ?? null);
        return trim((string) $v) !== '';
    })->count();
    $completionPct = $requiredTotal > 0 ? (int) round(($requiredFilled / $requiredTotal) * 100) : 0;
    $groupCount    = count($registrationFieldGroups ?? []);

    $stepIcons = ['👤', '📍', '🎓', '🗣️', '💰', '👨‍👩‍👧', '📂'];
    $stepShortNames = ['Kişisel', 'Adres', 'Eğitim', 'Dil', 'Finans', 'Aile', 'Ek Bilgi'];
    $stepWhys = [
        'Üniversite başvurularında kimlik bilgilerin gerekli. Bu bilgiler sadece senin dosyanda kalır.',
        'Adres bilgilerin üniversite kabul mektuplarının gönderimi ve vize başvurun için kullanılacak.',
        'Sana en uygun üniversite ve programı bulmamız için eğitim bilgilerin çok önemli.',
        'Dil seviyen, hazırlık programı ihtiyacını ve başvurabileceğin üniversiteleri belirler.',
        'Bu bilgiler sana uygun burs ve finansman seçeneklerini bulmamızı sağlar.',
        'Bazı üniversiteler ve burs programları veli bilgisi istiyor.',
        'Bu alan tamamen opsiyonel. Eklemek istediğin bilgi varsa buraya yazabilirsin.',
    ];
    $formLocked = (bool) ($guestApplication?->registration_form_submitted_at ?? false);
@endphp

{{-- ── Topbar with Step Pills ── --}}
<div class="srf-topbar">
    <div class="srf-topbar-left">
        <div class="srf-topbar-title">Kayıt Formu</div>
        <div class="srf-topbar-div"></div>
        <div class="srf-pills" id="srfPillNav">
            @foreach(($registrationFieldGroups ?? []) as $i => $group)
                <div class="srf-pill {{ $i === 0 ? 'active' : '' }}" data-pill-index="{{ $i }}">
                    <span class="srf-pill-num">{{ $i + 1 }}</span>
                    {{ $stepShortNames[$i] ?? $group['title'] ?? 'Bölüm' }}
                </div>
            @endforeach
        </div>
    </div>
    <div class="srf-topbar-right">
        <a class="srf-topbar-btn" href="{{ route('student.registration.form.pdf') }}" target="_blank">📄 PDF</a>
        @if($formLocked)
            <button type="button" class="srf-topbar-btn" id="srf-unlock-btn" style="cursor:pointer;border:none;font:inherit;background:none;">✏️ Formu Düzenle</button>
        @endif
        <a class="srf-topbar-btn" href="{{ route('student.registration.documents') }}">📂 Belgeler</a>
        <a class="srf-topbar-btn" href="/student/dashboard">🏠 Dashboard</a>
    </div>
</div>

{{-- ── Kilitli Form Bilgi Bandı ── --}}
@if($formLocked)
<div id="srf-locked-banner" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:1px solid #bbf7d0;border-radius:12px;padding:14px 20px;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:22px;">🔒</span>
    <div style="flex:1;">
        <div style="font-weight:700;font-size:var(--tx-sm);color:#166534;">Form Gönderildi</div>
        <div style="font-size:var(--tx-xs);color:#15803d;">Formunuz {{ optional($guestApplication->registration_form_submitted_at)->format('d.m.Y H:i') }} tarihinde gönderilmiştir. Değişiklik yapmak için "Formu Düzenle" butonunu kullanın.</div>
    </div>
</div>
@endif

{{-- ── Form Body ── --}}
<div class="srf-body">
    <div class="srf-center">
        <form method="POST" action="{{ route('student.registration.submit') }}" id="studentRegForm">
            @csrf

            {{-- Hidden nav for JS compat --}}
            <div id="sectionNav" class="srf-hidden">
                @foreach(($registrationFieldGroups ?? []) as $i => $group)
                    <button type="button" data-sec-index="{{ $i }}">{{ $group['title'] ?? '' }}</button>
                @endforeach
            </div>

            <div id="studentFormStage">
                @foreach(($registrationFieldGroups ?? []) as $i => $group)
                <section class="form-section {{ $i === 0 ? 'active' : '' }}" data-sec-index="{{ $i }}">
                    <div class="srf-step-ctx">
                        <div class="srf-step-tag">{{ $stepIcons[$i] ?? '📋' }} Adım {{ $i + 1 }} / {{ $groupCount }}</div>
                        <h1 class="srf-step-title">{{ $group['title'] ?? 'Alanlar' }}</h1>
                        @if(!empty($stepWhys[$i]))
                            <div class="srf-step-why">{{ $stepWhys[$i] }}</div>
                        @endif
                    </div>

                    {{-- Form Card --}}
                    <div class="srf-card">
                        <div class="srf-form-grid">
                            @foreach(($group['fields'] ?? []) as $field)
                            @php
                                $key         = (string) ($field['key'] ?? '');
                                $type        = (string) ($field['type'] ?? 'text');
                                $label       = (string) ($field['label'] ?? $key);
                                $required    = !empty($field['required']);
                                $placeholder = (string) ($field['placeholder'] ?? '');
                                $options     = is_array($field['options'] ?? null) ? $field['options'] : [];
                                $value       = old($key, $draft[$key] ?? ($guestApplication?->{$key} ?? ''));
                                $isFilled    = trim((string)$value) !== '';
                                $isWide      = $type === 'textarea' || $type === 'email'
                                    || !empty($field['full_width']) || !empty($field['help_text'])
                                    || str_contains($key, 'address') || str_contains($key, 'motivation');
                            @endphp
                            @if($key === '') @continue @endif
                            <div class="srf-form-group{{ $isFilled ? ' is-filled' : '' }}{{ $isWide ? ' srf-full' : '' }}" data-field-key="{{ $key }}">
                                <div class="srf-label-row">
                                    <label>{{ $label }} @if($required)<span class="required-star">*</span>@endif</label>
                                    @if($isFilled && in_array($key, ['first_name','last_name','email','phone']))
                                        <span class="srf-prefilled">✓ Kayıttan</span>
                                    @endif
                                </div>
                                @if($type === 'select')
                                    <select name="{{ $key }}" data-required="{{ $required ? '1' : '0' }}" {{ $formLocked ? 'disabled' : '' }}>
                                        <option value="">Seçiniz</option>
                                        @foreach($options as $opt)
                                        @php
                                            $ov = is_array($opt) ? (string)($opt['value'] ?? $opt['code'] ?? $opt['id'] ?? '') : (string)$opt;
                                            $ol = is_array($opt) ? (string)($opt['label'] ?? $opt['name'] ?? $ov) : (string)$opt;
                                        @endphp
                                            <option value="{{ $ov }}" @selected((string)$value === $ov)>{{ $ol }}</option>
                                        @endforeach
                                    </select>
                                @elseif($type === 'textarea')
                                    <textarea name="{{ $key }}" rows="4" placeholder="{{ $placeholder }}"
                                              data-required="{{ $required ? '1' : '0' }}" {{ $formLocked ? 'disabled' : '' }}>{{ (string)$value }}</textarea>
                                @elseif($type === 'date')
                                    <input type="date" name="{{ $key }}" value="{{ $value }}"
                                           data-required="{{ $required ? '1' : '0' }}" {{ $formLocked ? 'disabled' : '' }}>
                                @else
                                    <input type="{{ $type === 'email' ? 'email' : 'text' }}"
                                           name="{{ $key }}" value="{{ (string)$value }}"
                                           placeholder="{{ $placeholder }}"
                                           data-required="{{ $required ? '1' : '0' }}" {{ $formLocked ? 'disabled' : '' }}>
                                @endif
                                @error($key)
                                    <div class="srf-field-error">{{ $message }}</div>
                                @enderror
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Step Nav --}}
                    <div class="srf-nav-bar">
                        @if($i > 0)
                            <button type="button" class="srf-nav-btn back" data-srf-prev>← Geri</button>
                        @else
                            <div></div>
                        @endif
                        <div class="srf-nav-mid"><kbd>Enter</kbd> devam · <kbd>Shift+Enter</kbd> geri</div>
                        @if($i === $groupCount - 1)
                            <a href="{{ route('student.registration.form.pdf') }}" target="_blank"
                               class="srf-nav-btn back" style="text-decoration:none;">📄 Ön İzleme</a>
                            @if($formLocked)
                                <button type="submit" class="srf-nav-btn finish srf-update-btn" style="display:none;">🔄 Formu Güncelle</button>
                            @else
                                <button type="submit" class="srf-nav-btn finish">✅ Formu Tamamla</button>
                            @endif
                        @else
                            <button type="button" class="srf-nav-btn next" data-srf-next>Devam Et →</button>
                        @endif
                    </div>
                </section>
                @endforeach
            </div>

            {{-- Hidden action buttons for JS compat --}}
            <div class="srf-hidden">
                <button id="prevSectionBtn" type="button"></button>
                <button id="nextSectionBtn" type="button"></button>
                <span id="sectionCounter"></span>
                <span id="sectionCounter2"></span>
                <button type="submit" formaction="{{ route('student.registration.autosave') }}"></button>
            </div>
        </form>
    </div>
</div>

{{-- Autosave toast --}}
<div class="srf-toast" id="srfToast"><span style="width:8px;height:8px;border-radius:50%;background:var(--u-ok);"></span> Otomatik kaydedildi</div>

<script defer src="{{ Vite::asset('resources/js/student-registration-form.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // Pill sync
    var sections = document.querySelectorAll('.form-section[data-sec-index]');
    var pills = document.querySelectorAll('.srf-pill[data-pill-index]');

    var observer = new MutationObserver(function(){
        var activeIdx = -1;
        sections.forEach(function(s, i){ if(s.classList.contains('active')) activeIdx = i; });
        if(activeIdx < 0) return;
        pills.forEach(function(p, i){
            p.classList.remove('active','done');
            if(i < activeIdx) p.classList.add('done');
            else if(i === activeIdx) p.classList.add('active');
        });
        var ap = document.querySelector('.srf-pill.active');
        if(ap) ap.scrollIntoView({behavior:'smooth', block:'nearest', inline:'center'});
    });
    sections.forEach(function(s){ observer.observe(s, {attributes: true, attributeFilter: ['class']}); });

    // Pill click
    pills.forEach(function(pill){
        pill.addEventListener('click', function(){
            var idx = parseInt(pill.getAttribute('data-pill-index'));
            var btn = document.querySelector('#sectionNav button[data-sec-index="'+idx+'"]');
            if(btn) btn.click();
        });
    });

    // In-card nav
    document.addEventListener('click', function(e){
        if(e.target.closest('[data-srf-next]')) document.getElementById('nextSectionBtn')?.click();
        if(e.target.closest('[data-srf-prev]')) document.getElementById('prevSectionBtn')?.click();
    });

    // Keyboard nav
    document.addEventListener('keydown', function(e){
        if(e.key === 'Enter' && !e.shiftKey && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault(); document.getElementById('nextSectionBtn')?.click();
        }
        if(e.key === 'Enter' && e.shiftKey) {
            e.preventDefault(); document.getElementById('prevSectionBtn')?.click();
        }
    });

    // Kilit açma — Formu Düzenle
    var unlockBtn = document.getElementById('srf-unlock-btn');
    if(unlockBtn){
        unlockBtn.addEventListener('click', function(){
            var form = document.getElementById('studentRegForm');
            if(!form) return;
            form.querySelectorAll('input[disabled], select[disabled], textarea[disabled]').forEach(function(el){
                el.removeAttribute('disabled');
            });
            var banner = document.getElementById('srf-locked-banner');
            if(banner) banner.style.display = 'none';
            unlockBtn.style.display = 'none';
            document.querySelectorAll('.srf-update-btn').forEach(function(b){ b.style.display = ''; });
        });
    }

    // Autosave toast + filled state
    var toast = document.getElementById('srfToast'), tt;
    document.addEventListener('input', function(e){
        if(!e.target.closest('form')) return;
        var fg = e.target.closest('.srf-form-group');
        if(fg) fg.classList.toggle('is-filled', e.target.value.trim() !== '');
        if(toast){ toast.classList.add('show'); clearTimeout(tt); tt = setTimeout(function(){ toast.classList.remove('show'); }, 1500); }
    });
    document.addEventListener('change', function(e){
        var fg = e.target.closest('.srf-form-group');
        if(fg) fg.classList.toggle('is-filled', e.target.value.trim() !== '');
    });
})();
</script>
@endsection

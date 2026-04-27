@extends('guest.layouts.app')

@section('title', 'Ön Kayıt Formu')
@section('page_title', 'Kayıt Süreci - Ön Kayıt Formu')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>

{{-- Flatpickr — kullanıcı dostu tarih seçici (yıl/ay dropdown'lı, TR locale) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/tr.js" defer></script>
<style>
/* Flatpickr — proje teması ile uyum */
.flatpickr-input { background:#fff !important; }
.flatpickr-calendar { font-family:inherit; box-shadow:0 12px 32px rgba(0,0,0,.18); border-radius:10px; }
.flatpickr-calendar.open { z-index:10000; }
.flatpickr-current-month .flatpickr-monthDropdown-months,
.flatpickr-current-month input.cur-year { font-size:14px; font-weight:600; }
.flatpickr-day.selected { background:var(--u-brand,#2563eb); border-color:var(--u-brand,#2563eb); }
.flatpickr-day.today { border-color:var(--u-brand,#2563eb); }
</style>

<style>
/* ── grf-* Aday Öğrenci Kayıtistration Form — Step Pills Redesign ── */

/* ── Form Topbar with Step Pills ── */
.grf-topbar {
    background: var(--u-card); border-bottom: 1px solid var(--u-line);
    padding: 0 24px; display: flex; align-items: stretch;
    position: sticky; top: 0; z-index: 10; margin: -24px -24px 24px;
}
.grf-topbar-left {
    display: flex; align-items: center; gap: 14px;
    padding: 12px 0; flex: 1; min-width: 0;
}
.grf-topbar-title { font-size: 15px; font-weight: 700; color: var(--u-text); white-space: nowrap; }
.grf-topbar-div { width: 1px; height: 20px; background: var(--u-line); }

/* Step pills */
.grf-pills {
    display: flex; gap: 4px; overflow-x: auto;
    scrollbar-width: none; -ms-overflow-style: none;
}
.grf-pills::-webkit-scrollbar { display: none; }
.grf-pill {
    display: flex; align-items: center; gap: 5px;
    padding: 5px 12px 4px; border-radius: 12px;
    font-size: 11px; font-weight: 600; white-space: nowrap;
    cursor: pointer; transition: all .2s;
    border: 1.5px solid transparent;
    background: var(--u-bg); color: var(--u-muted);
    text-decoration: none; min-width: 70px;
}
.grf-pill.done { background: rgba(22,163,74,.08); color: var(--u-ok); }
.grf-pill.active {
    background: rgba(37,99,235,.06); color: var(--u-brand);
    border-color: var(--u-brand); box-shadow: 0 0 0 3px rgba(37,99,235,.06);
}
.grf-pill-num {
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 700;
}
.grf-pill.done .grf-pill-num { background: var(--u-ok); color: #fff; }
.grf-pill.active .grf-pill-num { background: var(--u-brand); color: #fff; }
.grf-pill:not(.done):not(.active) .grf-pill-num { background: var(--u-line); color: var(--u-muted); }
.grf-pill-bar { width: 100%; height: 3px; background: var(--u-line); border-radius: 2px; margin-top: 3px; overflow: hidden; }
.grf-pill-bar-fill { height: 100%; border-radius: 2px; background: var(--u-brand); transition: width .3s ease; }
.grf-pill.done .grf-pill-bar-fill { background: var(--u-ok); width: 100% !important; }

.grf-topbar-right {
    display: flex; align-items: center; gap: 8px; padding: 12px 0;
}
.grf-topbar-btn {
    padding: 7px 14px; border-radius: 8px; border: 1px solid var(--u-line);
    background: var(--u-card); font-size: 12px; font-weight: 600;
    color: var(--u-muted); text-decoration: none; transition: all .15s;
    display: inline-flex; align-items: center; gap: 5px;
}
.grf-topbar-btn:hover { background: var(--u-bg); color: var(--u-text); text-decoration: none; }

/* ── Form body ── */
.grf-body { display: flex; justify-content: center; }
.grf-center { width: 100%; max-width: 720px; margin: 0 auto; }

/* ── Step Context — full width above card ── */
.grf-step-ctx { margin-bottom: 20px; }
.grf-step-tag {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: var(--u-brand);
    background: rgba(37,99,235,.06); padding: 4px 12px; border-radius: 99px;
    margin-bottom: 10px;
}
.grf-step-title {
    font-size: 20px; font-weight: 800; letter-spacing: -.3px;
    margin-bottom: 8px; line-height: 1.3; color: var(--u-text);
}
.grf-step-why {
    font-size: 12px; color: var(--u-muted); line-height: 1.6;
    padding: 10px 14px; background: var(--u-bg);
    border-left: 3px solid var(--u-brand); border-radius: 0 8px 8px 0;
}

/* ── Form Card — full width ── */
.grf-card {
    background: var(--u-card); border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,.06);
    padding: 28px 32px; margin-bottom: 20px;
    border: 1px solid var(--u-line);
}

/* ── Fields — 2-col grid ── */
.grf-form-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 18px;
}
.grf-form-grid .form-group.grf-full { grid-column: 1 / -1; }
@media(max-width:640px){ .grf-form-grid { grid-template-columns: 1fr; } }

.form-group { display: grid; gap: 5px; }
.label-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.label-row label { font-weight: 700; color: var(--u-text); font-size: 13px; }
.required-star { color: var(--u-danger); font-weight: 700; }

/* Prefilled badge — B9: minimal ✓ sembolü, text yok */
.grf-prefilled {
    display: inline-flex; align-items: center; justify-content: center;
    width: 16px; height: 16px;
    font-size: 11px; line-height: 1;
    color: var(--u-ok); font-weight: 700;
}

/* Filled state — green border */
.form-group.is-filled input,
.form-group.is-filled select {
    border-color: var(--u-ok);
    background: rgba(22,163,74,.02);
}

/* Help toggle */
.help-toggle-btn {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(37,99,235,.06); border: 1px solid rgba(37,99,235,.15); border-radius: 6px;
    color: var(--u-brand); font-size: 11px; font-weight: 600;
    padding: 2px 8px; cursor: pointer;
}
.help-panel {
    display: none; font-size: 12px; color: var(--u-muted); line-height: 1.6;
    background: var(--u-bg); border: 1px solid var(--u-line); border-radius: 8px;
    padding: 8px 12px;
}
.help-panel.open { display: block; }
.field-error { color: var(--u-danger); font-size: 13px; }
.field-hint { font-size: 11px; color: var(--u-muted); margin-bottom: 4px; }

/* Inputs */
.grf-panel .form-group input,
.grf-panel .form-group select,
.grf-panel .form-group textarea {
    width: 100%; padding: 11px 16px;
    border: 2px solid var(--u-line); border-radius: 8px;
    font-size: 16px; color: var(--u-text); background: var(--u-card);
    font-family: inherit; box-sizing: border-box;
    transition: border-color .2s, box-shadow .2s;
}
.grf-panel .form-group input:focus,
.grf-panel .form-group select:focus,
.grf-panel .form-group textarea:focus {
    outline: none; border-color: var(--u-brand);
    box-shadow: 0 0 0 4px rgba(37,99,235,.06);
}

/* ── Navigation ── */
.grf-nav-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding-top: 4px; gap: 12px;
}
.grf-nav-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 24px; border-radius: 8px;
    font-size: 14px; font-weight: 700; cursor: pointer;
    border: none; transition: all .2s; font-family: inherit;
}
.grf-nav-btn.back { background: transparent; color: var(--u-muted); border: 1.5px solid var(--u-line); }
.grf-nav-btn.back:hover { background: var(--u-bg); color: var(--u-text); }
.grf-nav-btn.next { background: var(--u-brand); color: #fff; box-shadow: 0 4px 12px rgba(37,99,235,.2); }
.grf-nav-btn.next:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(37,99,235,.25); }
.grf-nav-btn.finish { background: var(--u-ok); color: #fff; box-shadow: 0 4px 12px rgba(22,163,74,.2); }
.grf-nav-mid { font-size: 11px; color: var(--u-muted); display: flex; align-items: center; gap: 6px; }
.grf-nav-mid kbd {
    background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 4px; padding: 2px 6px; font-size: 10px; font-family: inherit;
}

/* Checkbox group — Multi-select kartlı grid (biggest_concerns) */
.cbx-grid {
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:10px;
    padding:10px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:8px;
}
.cbx-grid .cbx-item {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px; background:#fff;
    border:1.5px solid #e2e8f0; border-radius:7px;
    cursor:pointer; font-size:13px; line-height:1.35;
    min-height:46px;
    transition:border-color .15s, background .15s, box-shadow .15s;
}
.cbx-grid .cbx-item:hover { border-color:var(--u-brand,#2563eb); background:#fbfdff; }
.cbx-grid .cbx-item input[type=checkbox] { flex-shrink:0; width:16px; height:16px; margin:0; cursor:pointer; }
.cbx-grid .cbx-item:has(input[type=checkbox]:checked) {
    border-color:var(--u-brand,#2563eb);
    background:rgba(37,99,235,.05);
    box-shadow:0 0 0 2px rgba(37,99,235,.06);
}
.cbx-grid .cbx-item:has(input[type=checkbox]:checked) .cbx-label {
    color:var(--u-brand,#2563eb);
    font-weight:600;
}
.cbx-grid .cbx-label {
    flex:1; text-align:left; color:var(--u-text,#0f172a); font-weight:500;
    word-break:break-word;
}
@media (max-width:720px) { .cbx-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width:420px) { .cbx-grid { grid-template-columns:1fr; } }

/* Panels (JS compat) */
.grf-panel { display: none; }
.grf-panel.active { display: block; animation: grfFadeUp .4s ease; }
@keyframes grfFadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Hidden nav for JS compat */
.grf-hidden { display: none; }

/* Autosave toast — minimal ✓ badge */
.grf-toast {
    position: fixed; bottom: 20px; right: 20px;
    width: 40px; height: 40px;
    background: var(--u-ok, #10b981); color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: 700; line-height: 1;
    opacity: 0; transform: translateY(8px) scale(.9);
    transition: all .3s; z-index: 100; pointer-events: none;
    box-shadow: 0 4px 12px rgba(16,185,129,.35);
}
.grf-toast.show { opacity: 1; transform: translateY(0) scale(1); }

/* ── Responsive ── */
@media(max-width:900px){
    .grf-topbar { margin: -16px -16px 16px; padding: 0 16px; }
    .grf-pills { display: none; }
    .grf-topbar-div { display: none; }
    .grf-card { padding: 20px; }
}
@media(max-width:600px){
    .grf-topbar { margin: -12px -12px 12px; padding: 0 12px; flex-wrap: wrap; }
    .grf-step-title { font-size: 20px; }
    .grf-card { padding: 16px; border-radius: 12px; }
    .grf-nav-bar { flex-direction: column; }
    .grf-nav-btn { width: 100%; justify-content: center; }
    .grf-nav-mid { display: none; }
    .grf-topbar-right { width: 100%; padding: 0 0 10px; }
    .grf-topbar-btn { flex: 1; justify-content: center; font-size: 11px; padding: 6px 10px; }
}

/* ── Minimalist overrides ── */
.jm-minimalist .grf-card { box-shadow: none; border: 1px solid var(--u-line); }
.jm-minimalist .grf-step-why { border-left-color: var(--u-text); }
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

    // 3-Level form: $formLevel 1 ya da 2. Default 2 (geriye uyum).
    $formLevel = $formLevel ?? 2;

    if ($formLevel === 1) {
        // Level 1 — 6 wizard yapısı (User'ın istediği)
        $stepIcons = ['👤', '🎓', '🎯', '🗣️', '💰', '💭'];
        $stepShortNames = ['Kişisel', 'Akademik', 'Hedef', 'Dil', 'Mali', 'Motivasyon'];
        $stepWhys = [
            'Apply formundan gelen kimlik bilgileri otomatik dolduruldu. Sadece doğum tarihini eklemen yeterli.',
            'Lise türü ve mezuniyet bilgilerin akademik profil değerlendirmesi için gerekli. Üniversiteye devam ediyorsan kısaca onu da yaz.',
            'Hangi bölümü/programı hedeflediğini bilmem, sana uygun üniversiteleri bulmam için kritik.',
            'Almanca ve İngilizce seviyeni biliyorsam, dil hazırlık ihtiyacın olup olmadığını ve hangi üniversitelere başvurabileceğini netleştirebiliriz.',
            'Vize için mali kanıt yöntemini erkenden planlamak süreci hızlandırır. Almanya\'da tanıdığın varsa not edelim.',
            'En çok hangi konuda destek beklediğini bilirsem, görüşmemizi o yöne kurabiliriz.',
        ];
    } else {
        // Level 2 — Mevcut 8 wizard (spouse_info section_order=15 ile evli ise araya girer)
        $stepIcons = ['👤', '💍', '📍', '🎓', '🗣️', '💰', '👨‍👩‍👧', '📂', '💭'];
        $stepShortNames = ['Kişisel', 'Eş', 'Adres', 'Eğitim', 'Dil', 'Finans', 'Aile', 'Ek Bilgi', 'Motivasyon'];
        $stepWhys = [
            'Üniversite başvurularında kimlik bilgilerin gerekli. Bu bilgiler sadece senin dosyanda kalır ve üçüncü kişilerle paylaşılmaz.',
            'Evli iseniz eşinize ait kimlik ve iletişim bilgileri eş ikametgah / vize başvurusu için gerekli olabilir. Evli değilseniz bu adım atlanır.',
            'Adres bilgilerin üniversite kabul mektuplarının gönderimi ve vize başvurun için kullanılacak.',
            'Sana en uygun üniversite ve programı bulmamız için eğitim bilgilerin çok önemli.',
            'Dil seviyen, hazırlık programı ihtiyacını ve başvurabileceğin üniversiteleri belirler.',
            'Almanya vizesi için bloke hesap ve finansal yeterlilik gerekiyor. Bu bilgiler sana uygun burs ve finansman seçeneklerini bulmamızı sağlar.',
            'Bazı üniversiteler ve burs programları veli bilgisi istiyor. Ayrıca acil durumda ulaşabileceğimiz bir kişi olması önemli.',
            'Bu alan tamamen opsiyonel. Eklemek istediğin bilgi varsa buraya yazabilirsin.',
            'Almanya\'da eğitim sürecindeki motivasyon ve hazırlık durumunu anlamamız sana özel rehberlik için faydalı olur.',
        ];
    }
@endphp

{{-- ── Topbar with Step Pills ── --}}
<div class="grf-topbar">
    <div class="grf-topbar-left">
        <div class="grf-topbar-title">
            @if($formLevel === 1)
                Aday Öğrenci Başvuru Formu
                <span style="font-size:11px;font-weight:600;padding:3px 8px;border-radius:10px;background:#dbeafe;color:#1e40af;margin-left:8px;">Seviye 1 / 2</span>
            @else
                Başvuru Formu
                <span style="font-size:11px;font-weight:600;padding:3px 8px;border-radius:10px;background:#f3e8ff;color:#6b21a8;margin-left:8px;">Seviye 2 / 2 — Tam Form</span>
            @endif
        </div>
        <div class="grf-topbar-div"></div>
        <div class="grf-pills" id="grfPillNav">
            @foreach(($registrationFieldGroups ?? []) as $group)
                <a class="grf-pill {{ $loop->first ? 'active' : '' }}" data-step-link="{{ $loop->index }}" data-step-pill="{{ $loop->index }}" href="#rg-{{ $loop->index }}" style="flex-direction:column;align-items:stretch;">
                    <span style="display:flex;align-items:center;gap:5px;">
                        <span class="grf-pill-num">{{ $loop->iteration }}</span>
                        {{ $stepShortNames[$loop->index] ?? $group['title'] ?? 'Bölüm' }}
                    </span>
                    <span data-step-missing="{{ $loop->index }}" style="display:none;"></span>
                    <div class="grf-pill-bar"><div class="grf-pill-bar-fill" data-step-bar="{{ $loop->index }}" style="width:0%"></div></div>
                </a>
            @endforeach
        </div>
    </div>
    <div class="grf-topbar-right">
        <a class="grf-topbar-btn" href="{{ route('guest.registration.documents') }}">📄 Belgeler</a>
        <a class="grf-topbar-btn" href="{{ route('guest.dashboard') }}">🏠 Dashboard</a>
    </div>
</div>

{{-- ── Form Body ── --}}
<div class="grf-body">
    <div class="grf-center">
        <form id="guestRegistrationForm" method="POST" novalidate
              action="{{ route('guest.registration.autosave') }}"
              data-ajax-save-url="{{ route('guest.registration.ajax-save') }}">
            @csrf
            <input type="hidden" name="draft_saved_at" value="{{ optional($guest?->registration_form_draft_saved_at)->toIso8601String() }}">

            <div id="stepPanelsStage">
                @foreach(($registrationFieldGroups ?? []) as $group)
                    <section id="rg-{{ $loop->index }}" class="grf-panel" data-step="{{ $loop->index }}">
                        <div class="grf-step-ctx">
                            <div class="grf-step-tag">{{ $stepIcons[$loop->index] ?? '📋' }} Adım {{ $loop->iteration }} / {{ $groupCount }}</div>
                            <h1 class="grf-step-title">{{ $group['title'] ?? 'Alanlar' }}</h1>
                            @if(!empty($stepWhys[$loop->index]))
                                <div class="grf-step-why">{{ $stepWhys[$loop->index] }}</div>
                            @endif
                        </div>

                        {{-- Form Card: full width --}}
                        <div class="grf-card">
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
                                        // B10: application_country DB'de 'de' (code) saklanıyor ama eski kayıtlar
                                        // 'Almanya' (label) olabilir. Render öncesi code'a normalize et.
                                        if ($key === 'application_country') {
                                            $value = \App\Support\GuestRegistrationFormCatalog::normalizeCountryValue($value);
                                        }
                                        // checkbox_group gibi multi-select field'larda $value array olabilir.
                                        $isFilled    = is_array($value) ? !empty($value) : trim((string)$value) !== '';
                                        // help_text varsa otomatik wide YAPMA — kısa help'ler 2-col'da kalır.
                                        // Sadece açıkça uzun help_text (200+ char) ve textarea/email/address/motivation wide.
                                        $hasLongHelp = !empty($field['help_text']) && mb_strlen((string) $field['help_text']) > 200;
                                        $isWide      = $type === 'textarea' || $type === 'email' || $type === 'checkbox_group'
                                            || $hasLongHelp || !empty($field['full_width'])
                                            || str_contains($key, 'address') || str_contains($key, 'motivation');
                                    @endphp
                                    @if($key === '') @continue @endif
                                    <div class="form-group{{ $isFilled ? ' is-filled' : '' }}{{ $isWide ? ' grf-full' : '' }}" data-field-key="{{ $key }}">
                                        <div class="label-row">
                                            <label>{{ $label }} @if($required)<span class="required-star">*</span>@endif</label>
                                            @if($isFilled && in_array($key, ['first_name','last_name','email','phone','gender','communication_language','application_country','application_type']))
                                                <span class="grf-prefilled" title="Başvuru formundan otomatik dolduruldu">✓</span>
                                            @endif
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
                                        @elseif($type === 'checkbox_group')
                                            @php
                                                // biggest_concerns gibi multi-select — JSON dizi olarak saklanır
                                                $rawValues = is_array($value) ? $value : (is_string($value) && $value !== '' ? (json_decode($value, true) ?? []) : []);
                                                $checkedSet = collect($rawValues)->map(fn ($v) => (string) $v)->all();
                                            @endphp
                                            <div class="cbx-grid">
                                                @foreach(($field['options'] ?? []) as $opt)
                                                    <label class="cbx-item">
                                                        <input type="checkbox" name="{{ $key }}[]" value="{{ $opt['value'] }}" @checked(in_array((string)$opt['value'], $checkedSet, true))>
                                                        <span class="cbx-label">{{ $opt['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @elseif($type === 'textarea')
                                            <textarea class="{{ $required ? 'final-required' : '' }}" name="{{ $key }}" rows="4" maxlength="{{ $max }}" placeholder="{{ $placeholder }}" data-required="{{ $required ? '1' : '0' }}">{{ $value }}</textarea>
                                        @elseif($type === 'date')
                                            @php
                                                // Tarih validasyonu — alan tipine göre min/max kısıtları
                                                // 1) Geçmişte olamaz (gelecek hedef tarihler)
                                                $futureOnly = in_array($key, [
                                                    'university_start_target_date',
                                                    'planned_start_date',
                                                    'target_start_date',
                                                ], true);
                                                $dateMin = $futureOnly ? now()->format('Y-m-d') : null;

                                                // 2) Gelecekte olamaz (geçmiş tarihler)
                                                // birth_date için ek kısıt: en az 15 yaşında olunmalı
                                                $dateMax = null;
                                                $maxMessage = '';
                                                if ($key === 'birth_date') {
                                                    $dateMax = now()->subYears(15)->format('Y-m-d');
                                                    $maxMessage = 'En az 15 yaşında olmalısınız (doğum tarihi gelecekte olamaz).';
                                                } elseif (in_array($key, [
                                                    'father_birth_date', 'mother_birth_date', 'spouse_birth_date', 'marriage_date',
                                                    'primary_start_date', 'primary_end_date',
                                                    'middle_start_date', 'middle_end_date',
                                                    'high_start_date', 'high_end_date',
                                                ], true)) {
                                                    $dateMax = now()->format('Y-m-d');
                                                    $maxMessage = 'Tarih gelecekte olamaz.';
                                                }
                                            @endphp
                                            <input class="{{ $required ? 'final-required' : '' }}" type="date" name="{{ $key }}" value="{{ $value }}"
                                                @if($dateMin) min="{{ $dateMin }}" @endif
                                                @if($dateMax) max="{{ $dateMax }}" @endif
                                                data-required="{{ $required ? '1' : '0' }}"
                                                @if($futureOnly) data-future-only="1" oninvalid="this.setCustomValidity('Bu tarih bugünden eski olamaz.')" oninput="this.setCustomValidity('')" @endif
                                                @if($dateMax) data-past-only="1" oninvalid="this.setCustomValidity(@js($maxMessage))" oninput="this.setCustomValidity('')" @endif>
                                        @else
                                            @php
                                                // B11: harf-only alanlar (şehir, ilçe, il, doğum yeri) — sayı/sembol kabul etmesin
                                                $isTextOnly = in_array($key, [
                                                    'application_city', 'district', 'province', 'birth_place',
                                                    'father_birth_place', 'mother_birth_place',
                                                ], true);

                                                // B14: mezuniyet ortalaması — sadece sayı (0-100 veya 0-5, kullanıcı tercihine bağlı)
                                                $isGrade = in_array($key, ['primary_grade', 'middle_grade', 'high_school_grade'], true);

                                                $inputType = match(true) {
                                                    $type === 'email' => 'email',
                                                    $type === 'phone' => 'tel',
                                                    $type === 'month' => 'month',
                                                    $isGrade => 'number',
                                                    default => 'text',
                                                };

                                                $inputmode = match(true) {
                                                    $type === 'email' => 'email',
                                                    $type === 'phone' => 'tel',
                                                    $isGrade || $type === 'money' || str_contains($key, '_gpa') || $key === 'financial_budget_eur' => 'decimal',
                                                    $key === 'postal_code' => 'numeric',
                                                    default => '',
                                                };
                                                $autocomplete = match($key) {
                                                    'email' => 'email', 'phone' => 'tel',
                                                    'first_name' => 'given-name', 'last_name' => 'family-name',
                                                    'address_full' => 'street-address', 'postal_code' => 'postal-code',
                                                    'city' => 'address-level2', default => '',
                                                };

                                                $pattern = $isTextOnly
                                                    ? "[A-Za-zçğıöşüÇĞİÖŞÜ\s\-.',]+"
                                                    : '';
                                                $patternTitle = $isTextOnly
                                                    ? 'Sadece harf, boşluk ve tire kullanın (sayı içeremez)'
                                                    : '';
                                            @endphp
                                            <input class="{{ $required ? 'final-required' : '' }}"
                                                   type="{{ $inputType }}"
                                                   name="{{ $key }}" value="{{ $value }}" maxlength="{{ $max }}"
                                                   placeholder="{{ $placeholder }}" data-required="{{ $required ? '1' : '0' }}"
                                                   @if($inputmode) inputmode="{{ $inputmode }}" @endif
                                                   @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                                                   @if($isGrade) step="0.01" min="0" max="100" @endif
                                                   @if($pattern) pattern="{{ $pattern }}" title="{{ $patternTitle }}" @endif>
                                        @endif
                                        @error($key)
                                            <div class="field-error">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Step Nav --}}
                        <div class="grf-nav-bar">
                            @if(!$loop->first)
                                <button type="button" class="grf-nav-btn back" data-grf-prev>← Geri</button>
                            @else
                                <div></div>
                            @endif
                            <div class="grf-nav-mid"><kbd>Enter</kbd> devam · <kbd>Shift+Enter</kbd> geri</div>
                            @if($loop->last)
                                <a href="{{ route('guest.registration.form.pdf') }}" target="_blank"
                                   class="grf-nav-btn back" style="text-decoration:none;">
                                    📄 Ön İzleme
                                </a>
                                <button type="submit" class="grf-nav-btn finish" id="btnFinalSubmit"
                                        formaction="{{ route('guest.registration.submit') }}">✅ Formu Tamamla</button>
                            @else
                                <button type="button" class="grf-nav-btn next" data-grf-next>Devam Et →</button>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>

            {{-- Hidden elements for JS compat --}}
            <div class="grf-hidden">
                <button id="btnPrevStep" type="button"></button>
                <button id="btnNextStep" type="button"></button>
                <button id="btnFirstMissing" type="button"></button>
                <button id="btnDraftSave" type="submit"></button>
                <span id="stepMeta"></span>
                <span id="sectionNavMeta"></span>
            </div>
        </form>
    </div>
</div>

{{-- Autosave toast — sadece ✓ (B9) --}}
<div class="grf-toast" id="grfToast" role="status" aria-label="Kaydedildi">✓</div>

<script defer src="{{ Vite::asset('resources/js/guest-registration-form.js') }}"></script>

{{-- Conditional: higher_education_status değerine göre üniversite alanlarını göster/gizle --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var statusEl = document.querySelector('select[name="higher_education_status"]');
    if (!statusEl) return;

    // Üniversite alanları (data-field-key üstünden form-group bul)
    var uniFieldKeys = ['university_name', 'university_department', 'university_year'];
    var uniGroups = uniFieldKeys.map(function(k){
        return document.querySelector('.form-group[data-field-key="' + k + '"]');
    }).filter(Boolean);

    // Sadece enrolled durumunda anlamlı olan field'lar (sınıf vb.)
    var enrolledOnlyKeys = ['university_year'];

    // 'not_started' = lise mezunu, henüz üni yok → hepsini gizle
    // 'enrolled' / 'dropped' / 'graduated' → üni adı + bölüm göster, sınıf sadece enrolled'de
    function applyVisibility(){
        var v = (statusEl.value || '').toLowerCase();
        var showUni = (v === 'enrolled' || v === 'dropped' || v === 'graduated');
        var showYear = (v === 'enrolled');

        uniGroups.forEach(function(g){
            var key = g.getAttribute('data-field-key');
            var enrolledOnly = enrolledOnlyKeys.indexOf(key) !== -1;
            var visible = enrolledOnly ? showYear : showUni;
            g.style.display = visible ? '' : 'none';

            // Hidden iken required attribute'ünü temizle (form submit hatası önle)
            var input = g.querySelector('input, select, textarea');
            if (input) {
                if (!visible) {
                    input.removeAttribute('required');
                } else if (input.classList.contains('final-required')) {
                    input.setAttribute('required', 'required');
                }
            }
        });
    }

    statusEl.addEventListener('change', applyVisibility);
    statusEl.addEventListener('input', applyVisibility);
    applyVisibility(); // Sayfa yüklenince başlangıç durumu
})();

// Conditional: finance_method='sponsor' (Garantör) seçilirse garantör detay alanları
(function(){
    var financeEl = document.querySelector('select[name="finance_method"]');
    if (!financeEl) return;

    var guarantorKeys = ['guarantor_relation', 'guarantor_name'];
    var guarantorGroups = guarantorKeys.map(function(k){
        return document.querySelector('.form-group[data-field-key="' + k + '"]');
    }).filter(Boolean);

    function applyGuarantorVisibility(){
        var v = (financeEl.value || '').toLowerCase();
        var show = (v === 'sponsor');
        guarantorGroups.forEach(function(g){
            g.style.display = show ? '' : 'none';
            var input = g.querySelector('input, select, textarea');
            if (input) {
                if (!show) {
                    input.removeAttribute('required');
                } else if (input.classList.contains('final-required')) {
                    input.setAttribute('required', 'required');
                }
            }
        });
    }

    financeEl.addEventListener('change', applyGuarantorVisibility);
    financeEl.addEventListener('input', applyGuarantorVisibility);
    applyGuarantorVisibility();
})();

// Conditional: accommodation_contact_status='yes' seçilirse şehir alanı görünür
(function(){
    var statusEl = document.querySelector('select[name="accommodation_contact_status"]');
    if (!statusEl) return;
    var cityGroup = document.querySelector('.form-group[data-field-key="accommodation_contact_city"]');
    if (!cityGroup) return;

    function apply(){
        var v = (statusEl.value || '').toLowerCase();
        var show = (v === 'yes');
        cityGroup.style.display = show ? '' : 'none';
    }
    statusEl.addEventListener('change', apply);
    statusEl.addEventListener('input', apply);
    apply();
})();
</script>

{{-- Flatpickr init — tüm date input'lara yıl/ay dropdown'lı picker bağla --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function initFlatpickrWhenReady(){
    if (typeof flatpickr === 'undefined') {
        // CDN henüz yüklenmediyse 100ms sonra tekrar dene (max 30 deneme = 3sn)
        if (window.__fpRetries === undefined) window.__fpRetries = 0;
        if (window.__fpRetries++ < 30) {
            setTimeout(initFlatpickrWhenReady, 100);
        }
        return;
    }

    document.querySelectorAll('input[type="date"]').forEach(function(el){
        // type="text"e çevirip flatpickr eklenir, native picker kapanır
        var minAttr = el.getAttribute('min');
        var maxAttr = el.getAttribute('max');
        var isBirth = el.name === 'birth_date';

        flatpickr(el, {
            locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.tr) ? flatpickr.l10ns.tr : 'default',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd.m.Y',
            allowInput: true,
            minDate: minAttr || null,
            maxDate: maxAttr || null,
            // Doğum tarihi için yıl 100 yıl geriye kadar açılır default
            defaultDate: el.value || (isBirth ? new Date(new Date().getFullYear() - 25, 0, 1) : null),
            disableMobile: false,
            // Yıl ve ay açılır menüsü her zaman görünür (varsayılan tema)
            monthSelectorType: 'dropdown',
        });
    });
})();
</script>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // Pill sync — observe panel active changes
    var panels = document.querySelectorAll('.grf-panel[data-step]');
    var pills = document.querySelectorAll('.grf-pill[data-step-pill]');
    var observer = new MutationObserver(function(){
        var activeIdx = -1;
        panels.forEach(function(p, i){ if(p.classList.contains('active')) activeIdx = i; });
        if(activeIdx < 0) return;
        pills.forEach(function(pill, i){
            pill.classList.remove('active','done');
            if(i < activeIdx) pill.classList.add('done');
            else if(i === activeIdx) pill.classList.add('active');
        });
        // Scroll active pill into view
        var ap = document.querySelector('.grf-pill.active');
        if(ap) ap.scrollIntoView({behavior:'smooth', block:'nearest', inline:'center'});
    });
    panels.forEach(function(p){ observer.observe(p, {attributes: true, attributeFilter: ['class']}); });

    // In-card nav → trigger existing hidden buttons
    document.addEventListener('click', function(e){
        if(e.target.closest('[data-grf-next]')) document.getElementById('btnNextStep')?.click();
        if(e.target.closest('[data-grf-prev]')) document.getElementById('btnPrevStep')?.click();
    });

    // Keyboard nav
    document.addEventListener('keydown', function(e){
        if(e.key === 'Enter' && !e.shiftKey && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault(); document.getElementById('btnNextStep')?.click();
        }
        if(e.key === 'Enter' && e.shiftKey) {
            e.preventDefault(); document.getElementById('btnPrevStep')?.click();
        }
    });

    // B8: filled state'i her input'ta toggle et (görsel filled class) ama
    // "kaydedildi" toast'unu HER KEYSTROKE'da gösterme — sadece "Devam Et"
    // basınca server save yapılınca göster (aşağıdaki click handler'da).
    var toast = document.getElementById('grfToast'), tt;
    document.addEventListener('input', function(e){
        if(!e.target.closest('#guestRegistrationForm')) return;
        var fg = e.target.closest('.form-group');
        if(fg) fg.classList.toggle('is-filled', e.target.value.trim() !== '');
    });
    document.addEventListener('change', function(e){
        var fg = e.target.closest('.form-group');
        if(fg) fg.classList.toggle('is-filled', e.target.value.trim() !== '');
    });

    // Toast'u yalnızca "Devam Et" butonuna basıldığında göster (server save'i tetikleyen).
    // guest-registration-form.js içinde bu buton click'i ajax-save fetch yapıyor; biz
    // burada paralel bir click handler ile toast'ı yönetiyoruz.
    var nextBtn = document.getElementById('btnNextStep');
    if (nextBtn && toast) {
        nextBtn.addEventListener('click', function () {
            // Saved state'i göster, 1.5sn sonra gizle
            toast.classList.add('show');
            clearTimeout(tt);
            tt = setTimeout(function () { toast.classList.remove('show'); }, 1500);
        });
    }

    // Step bar doluluk güncelle
    function updateStepBars(){
        document.querySelectorAll('.grf-panel[data-step]').forEach(function(panel, idx){
            var all = panel.querySelectorAll('.form-group[data-field-key]');
            var visible = Array.from(all).filter(function(fg){ return fg.style.display !== 'none'; });
            var total = visible.length;
            var filled = visible.filter(function(fg){
                var inp = fg.querySelector('input, select, textarea');
                return inp && (inp.value || '').toString().trim() !== '';
            }).length;
            var pct = total > 0 ? Math.round(filled / total * 100) : 0;
            var bar = document.querySelector('[data-step-bar="' + idx + '"]');
            if(bar) bar.style.width = pct + '%';
        });
    }
    updateStepBars();
    document.addEventListener('input', function(e){ if(e.target.closest('#guestRegistrationForm')) updateStepBars(); });
    document.addEventListener('change', function(e){ if(e.target.closest('#guestRegistrationForm')) updateStepBars(); });

    // B15: Anne/baba doğum tarihi çocuğun doğum tarihinden önce olmalı (ve en az 12 yıl fark).
    // Child dob değişince parent dob max'ını günceller; parent dob değişince çakışmayı native
    // setCustomValidity ile gösterir. Submit sırasında browser engeller.
    function _bdIso(v){ return (v || '').trim().slice(0, 10); }
    function _syncParentDobConstraints(){
        var f = document.getElementById('guestRegistrationForm');
        if(!f) return;
        var child = f.querySelector('[name="birth_date"]');
        if(!child) return;
        var childVal = _bdIso(child.value);
        if(!childVal) return;
        // Ebeveyn çocuğun doğumundan en az 12 yıl önce doğmuş olmalı.
        var d = new Date(childVal);
        if(isNaN(d)) return;
        var maxDate = new Date(d.getFullYear() - 12, d.getMonth(), d.getDate());
        var maxIso = maxDate.toISOString().slice(0, 10);
        ['father_birth_date','mother_birth_date'].forEach(function(name){
            var el = f.querySelector('[name="' + name + '"]');
            if(!el) return;
            el.max = maxIso;
            // Value şu an kötüyse hata göster
            var v = _bdIso(el.value);
            if(v && v > maxIso) {
                el.setCustomValidity('Doğum tarihi çocuğun doğum tarihinden önce ve en az 12 yıl büyük olmalı.');
            } else {
                el.setCustomValidity('');
            }
        });
    }
    document.addEventListener('change', function(e){
        if(!e.target.closest('#guestRegistrationForm')) return;
        var n = e.target.name || '';
        if(n === 'birth_date' || n === 'father_birth_date' || n === 'mother_birth_date') {
            _syncParentDobConstraints();
        }
    });
    _syncParentDobConstraints();

    // B12: Eğitim seviyesi, tamamlanan son kademeyi belirler. Üst kademelerin
    // alanları gizlenir + required kaldırılır ki submit'te zorunluluk tetiklenmesin.
    //   middle_school → göster: primary, middle          | gizle: high, university
    //   high_school   → göster: primary, middle, high    | gizle: university
    //   bachelor/mas. → göster: hepsi
    var _educationVisibility = {
        middle_school: ['high_', 'university_'],
        high_school:   ['university_'],
        bachelor:      [],
        master:        [],
    };
    function _isEducationField(key, prefixes){
        for(var i=0;i<prefixes.length;i++){ if(key.indexOf(prefixes[i])===0) return true; }
        return false;
    }
    function _applyEducationVisibility(){
        var f = document.getElementById('guestRegistrationForm');
        if(!f) return;
        var sel = f.querySelector('[name="education_level"]');
        if(!sel) return;
        var level = String(sel.value || '');
        var hidePrefixes = _educationVisibility[level] || [];
        // Only apply to fields in the education_history section
        f.querySelectorAll('[data-field-key]').forEach(function(fg){
            var k = fg.dataset.fieldKey || '';
            // Only manage the education kademe alanları (primary/middle/high/university)
            if(!/^(primary_|middle_|high_|university_)/.test(k)) return;
            // education_level select'i kendisini gizleme
            if(k === 'education_level') return;
            var shouldHide = _isEducationField(k, hidePrefixes);
            fg.style.display = shouldHide ? 'none' : '';
            // Required kaldır/geri al (server-side kontrol'ü ayrıca gereken alanları bypass eder)
            var inp = fg.querySelector('input, select, textarea');
            if(inp) {
                if(shouldHide) {
                    inp.dataset.origRequired = inp.dataset.required || '0';
                    inp.dataset.required = '0';
                    inp.classList.remove('final-required');
                    inp.removeAttribute('required');
                } else if(inp.dataset.origRequired === '1') {
                    inp.dataset.required = '1';
                    inp.classList.add('final-required');
                }
            }
        });
    }
    document.addEventListener('change', function(e){
        if(!e.target.closest('#guestRegistrationForm')) return;
        if((e.target.name || '') === 'education_level') _applyEducationVisibility();
    });
    _applyEducationVisibility();

    // B20 (spouse): marital_status === 'married' ise "Eşinizle İlgili Bilgiler"
    // section'ı ve alanları görünür, yoksa gizli + required kaldırılır.
    // children_count ayrıca has_children === 'yes' ise görünür + zorunlu.
    var _spouseFieldKeys = [
        'spouse_full_name','spouse_birth_date','spouse_nationality','spouse_occupation',
        'marriage_date','marriage_place','spouse_currently_in_germany','has_children','children_count'
    ];
    function _applySpouseVisibility(){
        var f = document.getElementById('guestRegistrationForm');
        if(!f) return;
        var sel = f.querySelector('[name="marital_status"]');
        var show = sel && String(sel.value || '') === 'married';
        // Section panel başlığı (spouse_info step) — data-field-key üzerinden değil,
        // direkt kademe alanlarını tek tek yönet
        _spouseFieldKeys.forEach(function(k){
            var fg = f.querySelector('[data-field-key="' + k + '"]');
            if(!fg) return;
            fg.style.display = show ? '' : 'none';
            var inp = fg.querySelector('input, select, textarea');
            if(!inp) return;
            if(show) {
                // Geri aç — orij required'ı data-attr'dan geri yükle
                if(inp.dataset.origSpouseRequired === '1') {
                    inp.dataset.required = '1';
                    inp.classList.add('final-required');
                }
            } else {
                inp.dataset.origSpouseRequired = inp.dataset.required || '0';
                inp.dataset.required = '0';
                inp.classList.remove('final-required');
                inp.removeAttribute('required');
                inp.setCustomValidity('');
            }
        });
        // Step panel'i (section) tamamen gizlemek için spouse_info panel'i bul
        // ve pill'i de bulup visibility toggle et. Evli değilse: panel display:none,
        // pill display:none → navigation step indeksi otomatik atlar (aşağıdaki
        // click hook'unda gerekirse tekrar next'i tetikle).
        f.querySelectorAll('.grf-panel').forEach(function(panel){
            var hasSpouseField = _spouseFieldKeys.some(function(k){
                return !!panel.querySelector('[data-field-key="' + k + '"]');
            });
            if(!hasSpouseField) return;
            panel.dataset.spouseSection = '1';
            panel.style.display = show ? '' : 'none';
            var step = panel.getAttribute('data-step');
            if(step !== null){
                var pill = document.querySelector('[data-step-pill="' + step + '"]');
                if(pill) pill.style.display = show ? '' : 'none';
            }
        });
    }
    // Navigasyon atlama: Evli değilse spouse panel'i "active" olursa
    // otomatik olarak btnNextStep'i tekrar tetikle (ya da prev yönünde prev'i).
    var _lastNavDirection = 1;
    document.getElementById('btnNextStep')?.addEventListener('click', function(){ _lastNavDirection = 1; }, true);
    document.getElementById('btnPrevStep')?.addEventListener('click', function(){ _lastNavDirection = -1; }, true);
    (function(){
        var observer = new MutationObserver(function(){
            var active = document.querySelector('.grf-panel.active[data-spouse-section="1"]');
            if(!active) return;
            if(active.style.display === 'none'){
                var btn = _lastNavDirection === -1
                    ? document.getElementById('btnPrevStep')
                    : document.getElementById('btnNextStep');
                if(btn && !btn.disabled) setTimeout(function(){ btn.click(); }, 0);
            }
        });
        document.querySelectorAll('.grf-panel[data-step]').forEach(function(p){
            observer.observe(p, {attributes: true, attributeFilter: ['class']});
        });
    })();
    // children_count: has_children === 'yes' ise görünür + number-only + required
    function _applyChildrenCountVisibility(){
        var f = document.getElementById('guestRegistrationForm');
        if(!f) return;
        var hcSel = f.querySelector('[name="has_children"]');
        var ccFg  = f.querySelector('[data-field-key="children_count"]');
        if(!ccFg) return;
        // spouse parent: marital_status != married ise children_count hâlâ gizli olmalı
        var maritalSel = f.querySelector('[name="marital_status"]');
        var spouseVisible = maritalSel && String(maritalSel.value || '') === 'married';
        var show = spouseVisible && hcSel && String(hcSel.value || '') === 'yes';
        ccFg.style.display = show ? '' : 'none';
        var inp = ccFg.querySelector('input, select, textarea');
        if(inp) {
            if(show) {
                inp.dataset.required = '1';
                inp.classList.add('final-required');
                // number-only pattern
                inp.setAttribute('inputmode', 'numeric');
                inp.setAttribute('pattern', '[0-9]+');
                inp.setAttribute('maxlength', '2');
                inp.setCustomValidity('');
            } else {
                inp.dataset.required = '0';
                inp.classList.remove('final-required');
                inp.removeAttribute('required');
                inp.setCustomValidity('');
            }
        }
    }

    document.addEventListener('change', function(e){
        if(!e.target.closest('#guestRegistrationForm')) return;
        var n = e.target.name || '';
        if(n === 'marital_status') {
            _applySpouseVisibility();
            _applyChildrenCountVisibility();
        }
        if(n === 'has_children') _applyChildrenCountVisibility();
    });
    _applySpouseVisibility();
    _applyChildrenCountVisibility();

    // B13: Eğitim tarihleri — hem sıralama hem her kademenin minimum süresi.
    //   İlkokul: en az 4 yıl | Ortaokul: en az 3 yıl | Lise: en az 3 yıl
    //   Ayrıca kademeler arası sıralama: middle_start >= primary_end vb.
    var _eduOrderChain = [
        ['primary_end_date', 'middle_start_date', 'Ortaokul başlama tarihi ilkokul bitiş tarihinden önce olamaz.'],
        ['middle_end_date', 'high_start_date', 'Lise başlama tarihi ortaokul bitiş tarihinden önce olamaz.'],
    ];
    // [start_key, end_key, minYears, label]
    var _eduDurationRules = [
        ['primary_start_date', 'primary_end_date', 4, 'İlkokul'],
        ['middle_start_date',  'middle_end_date',  3, 'Ortaokul'],
        ['high_start_date',    'high_end_date',    3, 'Lise'],
    ];
    function _isHidden(el){
        var g = el && el.closest('.form-group');
        return !!(g && g.style.display === 'none');
    }
    function _addYears(iso, years){
        if(!iso) return '';
        var d = new Date(iso);
        if(isNaN(d)) return '';
        return new Date(d.getFullYear() + years, d.getMonth(), d.getDate()).toISOString().slice(0,10);
    }
    function _setEduErr(el, msg){
        if(!el) return;
        var fg = el.closest('.form-group');
        var holder = fg ? fg.querySelector('.edu-err') : null;
        if(msg){
            el.style.borderColor = '#dc2626';
            if(!holder){
                holder = document.createElement('div');
                holder.className = 'edu-err';
                holder.style.cssText = 'color:#dc2626;font-size:12px;margin-top:4px;font-weight:500;';
                (fg || el.parentNode).appendChild(holder);
            }
            holder.textContent = msg;
        } else {
            el.style.borderColor = '';
            if(holder) holder.remove();
        }
    }
    // Maksimum bitiş tarihleri (İlkokul/Ortaokul bugün, Lise +4 yıl)
    function _todayIso(){
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }
    function _plusYearsIso(years){
        var d = new Date();
        d.setFullYear(d.getFullYear() + years);
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }
    var _eduMaxEnd = {
        'primary_end_date': { max: _todayIso(),        label: 'İlkokul bitiş tarihi bugünden sonra olamaz.' },
        'middle_end_date':  { max: _todayIso(),        label: 'Ortaokul bitiş tarihi bugünden sonra olamaz.' },
        'high_end_date':    { max: _plusYearsIso(4),   label: 'Lise bitiş tarihi en fazla 4 yıl sonra olabilir.' },
    };
    function _validateEduDates(){
        var f = document.getElementById('guestRegistrationForm');
        if(!f) return;
        // 0) Bitiş tarihi max sınırı (gelecekte olamaz - lise hariç +4 yıl)
        Object.keys(_eduMaxEnd).forEach(function(key){
            var el = f.querySelector('[name="' + key + '"]');
            if(!el) return;
            if(_isHidden(el)) { el.setCustomValidity(''); _setEduErr(el,''); return; }
            var rule = _eduMaxEnd[key];
            el.max = rule.max;
            var v = (el.value || '').trim();
            if(v && v > rule.max){
                el.setCustomValidity(rule.label);
                _setEduErr(el, rule.label);
            } else if(el.validity.customError && el.validationMessage === rule.label){
                el.setCustomValidity('');
                _setEduErr(el, '');
            }
        });
        // 1) Her kademe için min duration + dinamik start max (end - minYears)
        _eduDurationRules.forEach(function(r){
            var sEl = f.querySelector('[name="' + r[0] + '"]');
            var eEl = f.querySelector('[name="' + r[1] + '"]');
            if(!sEl || !eEl) return;
            if(_isHidden(sEl) || _isHidden(eEl)) { eEl.setCustomValidity(''); _setEduErr(eEl,''); return; }
            var s = (sEl.value || '').trim();
            var e = (eEl.value || '').trim();
            // Start seçildiyse end.min = start + minYears (ama end.max zaten yukarıda set edildi)
            if(s){
                var minEnd = _addYears(s, r[2]);
                if(minEnd) eEl.min = minEnd;
            }
            // End seçildiyse start.max = end - minYears
            if(e){
                var maxStart = _addYears(e, -r[2]);
                if(maxStart) sEl.max = maxStart;
            }
            if(!s || !e) return; // custom errors korunsun
            // Sadece duration error yoksa duration kontrolü yap
            if(eEl.validity.customError && _eduMaxEnd[r[1]] && eEl.validationMessage === _eduMaxEnd[r[1]].label) return;
            var minEnd2 = _addYears(s, r[2]);
            if(e < s) {
                var msg = r[3] + ' bitiş tarihi başlama tarihinden önce olamaz.';
                eEl.setCustomValidity(msg); _setEduErr(eEl, msg);
            } else if(minEnd2 && e < minEnd2) {
                var msg2 = r[3] + ' bitiş tarihi başlamadan en az ' + r[2] + ' yıl sonra olmalı.';
                eEl.setCustomValidity(msg2); _setEduErr(eEl, msg2);
            } else {
                eEl.setCustomValidity(''); _setEduErr(eEl,'');
            }
        });
        // 2) Kademeler arası sıralama (next kademe start >= prev kademe end)
        _eduOrderChain.forEach(function(pair){
            var aEl = f.querySelector('[name="' + pair[0] + '"]');
            var bEl = f.querySelector('[name="' + pair[1] + '"]');
            if(!aEl || !bEl) return;
            if(_isHidden(aEl) || _isHidden(bEl)) return;
            var a = (aEl.value || '').trim();
            var b = (bEl.value || '').trim();
            if(a) bEl.min = a;
            if(a && b && b < a) {
                bEl.setCustomValidity(pair[2]); _setEduErr(bEl, pair[2]);
            } else if(bEl.validity.customError && bEl.validationMessage === pair[2]) {
                bEl.setCustomValidity(''); _setEduErr(bEl,'');
            }
        });
    }
    document.addEventListener('change', function(e){
        if(!e.target.closest('#guestRegistrationForm')) return;
        var n = e.target.name || '';
        if(/_(start|end)_date$/.test(n)) _validateEduDates();
    });
    _validateEduDates();

    // Design toggle compat
    var _orig = window.__designToggle;
    window.__designToggle = function(d){ if(_orig) _orig(d); setTimeout(function(){ document.documentElement.classList.toggle('jm-minimalist', d==='minimalist'); }, 50); };
})();
</script>
@endsection

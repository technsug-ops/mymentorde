@extends('student.layouts.app')

@section('title', 'Kayıt Formu')
@section('page_title', 'Kayıt Süreci - Form')

@push('head')
<style>
/* ── srf-* Student Registration Form scoped ── */

/* Metrics strip */
.srf-metrics {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
@media(max-width:1024px){ .srf-metrics { grid-template-columns: 1fr 1fr; } }
@media(max-width:600px){ .srf-metrics { grid-template-columns: 1fr; } }
.srf-metric {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 14px;
    padding: 14px 18px;
}
.srf-metric-label { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--u-muted); margin-bottom: 6px; }
.srf-metric-val   { font-size: 28px; font-weight: 800; color: var(--u-text); line-height: 1; }

/* Progress */
.srf-progress-card {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 16px;
}
.srf-progress-label { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; color: var(--u-muted); margin-bottom: 6px; }
.srf-progress-bar { height: 8px; border-radius: 999px; background: var(--u-line); overflow: hidden; }
.srf-progress-fill { height: 100%; border-radius: 999px; background: var(--u-brand); transition: width .4s ease; }

/* Section nav */
.srf-nav {
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
.srf-nav button {
    white-space: nowrap;
    border: 1px solid var(--u-line);
    border-radius: 999px;
    padding: 5px 14px;
    color: var(--u-text);
    font-size: 14px; font-weight: 600;
    background: var(--u-card);
    cursor: pointer;
    display: inline-flex; align-items: center;
    transition: background .15s, border-color .15s, color .15s;
    flex-shrink: 0;
}
.srf-nav button:hover { background: var(--u-bg); border-color: var(--u-brand); color: var(--u-brand); }
.srf-nav button.active {
    background: var(--u-brand); border-color: var(--u-brand);
    color: #fff; font-weight: 700;
}
.srf-nav-meta {
    display: flex; justify-content: space-between; align-items: center;
    gap: 10px; margin-bottom: 14px;
}
.srf-nav-counter {
    font-weight: 700; color: var(--u-brand);
    background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 999px; padding: 5px 14px; font-size: 15px;
}

/* Step panels */
.srf-step-panels { position: relative; }
.form-section {
    border: 1px solid var(--u-line);
    border-radius: 14px;
    margin-bottom: 14px;
    background: var(--u-card);
    overflow: hidden;
    display: none;
}
.form-section.active { display: block; }
.srf-panel-head {
    padding: 14px 18px;
    background: var(--u-bg);
    border-bottom: 1px solid var(--u-line);
    font-size: 16px; font-weight: 700; color: var(--u-text);
}
.srf-panel-body { padding: 16px 18px; }

/* Form grid */
.srf-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}
@media(max-width:740px){ .srf-form-grid { grid-template-columns: 1fr; } }
.srf-form-group { display: grid; gap: 6px; }
.srf-label-row { display: flex; align-items: center; gap: 6px; }
.srf-label-row label { font-weight: 700; color: var(--u-text); font-size: 15px; }
.required-star { color: var(--u-danger); font-weight: 700; }

/* Form inputs */
.srf-panel-body .srf-form-group input,
.srf-panel-body .srf-form-group select,
.srf-panel-body .srf-form-group textarea {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid var(--u-line); border-radius: 8px;
    font-size: 16px; color: var(--u-text); background: var(--u-card);
    font-family: inherit; box-sizing: border-box;
    transition: border-color .15s, box-shadow .15s;
}
.srf-panel-body .srf-form-group input:focus,
.srf-panel-body .srf-form-group select:focus,
.srf-panel-body .srf-form-group textarea:focus {
    outline: none; border-color: var(--u-brand);
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.srf-field-error { color: var(--u-danger); font-size: 14px; }

/* Sticky footer */
.srf-sticky {
    position: sticky; bottom: 8px; z-index: 20;
    display: flex; justify-content: space-between; gap: 8px; align-items: center;
    flex-wrap: wrap;
    border: 1px solid var(--u-line); background: var(--u-card);
    border-radius: 12px; padding: 10px 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,.08);
}
.srf-sticky-note { color: var(--u-muted); font-size: 14px; }

/* ── Minimalist overrides ── */
.jm-minimalist .srf-panel-head {
    background: #e2e5ec !important;
    color: var(--u-text, #1a1a1a) !important;
    border-bottom: 1px solid rgba(0,0,0,.10) !important;
}
.jm-minimalist .srf-progress-fill { background: var(--u-brand, #111) !important; }
.jm-minimalist .srf-progress-fill { background: var(--u-brand, #111) !important; }
.jm-minimalist .srf-nav button:hover { background: var(--u-bg) !important; }
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
@endphp

{{-- ── Top Bar ── --}}
<div class="card" style="margin-bottom:14px;">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div class="muted" style="font-size:var(--tx-sm);">Adım adım ilerle. Her adımda kaydet, sonunda tek tıkla gönder.</div>
        <div style="display:flex;gap:8px;">
            <a class="btn alt" href="{{ route('student.registration.documents') }}" style="font-size:var(--tx-xs);">Belgelere Geç</a>
            <a class="btn alt" href="/student/dashboard" style="font-size:var(--tx-xs);">Dashboard</a>
        </div>
    </div>
</div>

{{-- ── Metrics Strip ── --}}
<div class="srf-metrics">
    <div class="srf-metric">
        <div class="srf-metric-label">Zorunlu Alan</div>
        <div class="srf-metric-val">{{ $requiredTotal }}</div>
    </div>
    <div class="srf-metric">
        <div class="srf-metric-label">Dolu Zorunlu</div>
        <div class="srf-metric-val">{{ $requiredFilled }}</div>
    </div>
    <div class="srf-metric">
        <div class="srf-metric-label">Tamamlama</div>
        <div class="srf-metric-val">%{{ $completionPct }}</div>
    </div>
    <div class="srf-metric">
        <div class="srf-metric-label">Son Kayıt</div>
        <div class="srf-metric-val" style="font-size:var(--tx-sm);line-height:1.3;">
            {{ $guestApplication?->registration_form_draft_saved_at ? \Carbon\Carbon::parse($guestApplication->registration_form_draft_saved_at)->format('d.m.Y H:i') : '-' }}
        </div>
    </div>
</div>

{{-- ── Progress ── --}}
<div class="srf-progress-card">
    <div class="srf-progress-label">
        <span>Form İlerlemesi (Zorunlu Alan Bazlı)</span>
        <span style="color:var(--u-brand);font-size:var(--tx-sm);font-weight:800;">%{{ $completionPct }}</span>
    </div>
    <div class="srf-progress-bar">
        <div class="srf-progress-fill" style="width:{{ $completionPct }}%;"></div>
    </div>
</div>

{{-- ── Form Card ── --}}
<div class="card">
    <div class="card-body">
        {{-- Section nav --}}
        <div class="srf-nav" id="sectionNav">
            @foreach(($registrationFieldGroups ?? []) as $i => $group)
                <button type="button" data-sec-index="{{ $i }}">
                    {{ $group['title'] ?? 'Bölüm' }}
                </button>
            @endforeach
        </div>
        <div class="srf-nav-meta">
            <div class="srf-nav-counter" id="sectionCounter">Adım 1 / {{ $groupCount }}</div>
            <div class="muted" style="font-size:var(--tx-xs);">Sekme geçişinde ekran sabit kalır; sadece ilgili bölüm görünür.</div>
        </div>

        <form method="POST" action="{{ route('student.registration.submit') }}">
            @csrf
            <div class="srf-step-panels" id="studentFormStage">
                @foreach(($registrationFieldGroups ?? []) as $i => $group)
                <section class="form-section {{ $i === 0 ? 'active' : '' }}" data-sec-index="{{ $i }}">
                    <div class="srf-panel-head">
                        Adım {{ $i + 1 }} / {{ $groupCount }} — {{ $group['title'] ?? 'Alanlar' }}
                    </div>
                    <div class="srf-panel-body">
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
                                $isWide      = $type === 'textarea' || !empty($field['full_width']);
                            @endphp
                            @if($key === '') @continue @endif
                            <div class="srf-form-group{{ $isWide ? ' srf-full' : '' }}"
                                 @if($isWide) style="grid-column:1/-1;" @endif
                                 data-field-key="{{ $key }}">
                                <div class="srf-label-row">
                                    <label>{{ $label }} @if($required)<span class="required-star">*</span>@endif</label>
                                </div>

                                @if($type === 'select')
                                    <select name="{{ $key }}" data-required="{{ $required ? '1' : '0' }}">
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
                                              data-required="{{ $required ? '1' : '0' }}">{{ (string)$value }}</textarea>
                                @elseif($type === 'date')
                                    <input type="date" name="{{ $key }}" value="{{ $value }}"
                                           data-required="{{ $required ? '1' : '0' }}">
                                @else
                                    <input type="{{ $type === 'email' ? 'email' : 'text' }}"
                                           name="{{ $key }}" value="{{ (string)$value }}"
                                           placeholder="{{ $placeholder }}"
                                           data-required="{{ $required ? '1' : '0' }}">
                                @endif

                                @error($key)
                                    <div class="srf-field-error">{{ $message }}</div>
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
                    Belge/sertifika yüklemeleri için <a href="{{ route('student.registration.documents') }}" style="color:var(--u-brand);font-weight:600;">Kayıt Belgeleri</a> ekranına geçebilirsin.
                </p>
            </div>

            {{-- Sticky action bar --}}
            <div class="srf-sticky">
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <button id="prevSectionBtn" class="btn" type="button">← Geri</button>
                    <button id="nextSectionBtn" class="btn ok" type="button">İleri →</button>
                    <button type="button" class="btn alt" onclick="
                        var fields = document.querySelectorAll('[data-required=\'1\']');
                        for(var i=0;i<fields.length;i++){
                            if(!fields[i].value.trim()){
                                var sec = fields[i].closest('.form-section');
                                if(sec){
                                    var idx = parseInt(sec.getAttribute('data-sec-index')||'0',10);
                                    var tab = document.querySelector('#sectionNav button[data-sec-index=\''+idx+'\']');
                                    if(tab) tab.click();
                                    setTimeout(function(f){return function(){f.focus();f.scrollIntoView({behavior:'smooth',block:'center'});};}(fields[i]),80);
                                }
                                break;
                            }
                        }
                    ">İlk Eksik Zorunlu</button>
                    <span class="srf-sticky-note" id="sectionCounter2"></span>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="submit" formaction="{{ route('student.registration.autosave') }}" class="btn">Taslak Kaydet</button>
                    <button type="submit" class="btn ok">Formu Gönder ✓</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script defer src="{{ Vite::asset('resources/js/student-registration-form.js') }}"></script>
@endsection

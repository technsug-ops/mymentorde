@extends('guest.layouts.app')

@section('title', 'Profilim')
@section('page_title', 'Profilim')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ══════ Hero (Option B + avatar) ══════ */
.gp-hero {
    color:#fff; border-radius:14px; margin-bottom:20px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.14); position:relative;
    background:#2563eb url('https://images.unsplash.com/photo-1557804506-669a67965ba0?w=1400&q=80') center/cover;
}
.gp-hero::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, rgba(37,99,235,.92) 0%, rgba(14,116,144,.85) 100%);
}
.gp-hero-body { position:relative; display:flex; align-items:center; gap:22px; padding:24px 26px; flex-wrap:wrap; }

.gp-avatar-wrap { position:relative; flex-shrink:0; }
.gp-avatar {
    width:80px; height:80px; border-radius:50%;
    background:rgba(255,255,255,.2);
    border:3px solid rgba(255,255,255,.45);
    overflow:hidden;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:800; font-size:26px; letter-spacing:-.5px;
    position:relative;
    box-shadow:0 4px 14px rgba(0,0,0,.25);
}
.gp-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
.gp-avatar-overlay {
    position:absolute; inset:0; border-radius:50%;
    background:rgba(0,0,0,.55); color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:22px; opacity:0; transition:opacity .2s; cursor:pointer; z-index:2;
}
.gp-avatar-wrap:hover .gp-avatar-overlay { opacity:1; }

.gp-hero-main { flex:1; min-width:200px; }
.gp-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.82; margin-bottom:4px; }
.gp-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.gp-hero-name { font-size:24px; font-weight:800; margin:0 0 4px; letter-spacing:-.3px; line-height:1.15; }
.gp-hero-email { font-size:12.5px; opacity:.8; margin-bottom:10px; word-break:break-all; }
.gp-hero-badges { display:flex; gap:6px; flex-wrap:wrap; }
.gp-hero-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 11px; border-radius:20px;
    background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600;
    border:1px solid rgba(255,255,255,.12);
}
.gp-hero-stats { display:flex; gap:14px; flex-wrap:wrap; padding-left:16px; border-left:1px solid rgba(255,255,255,.22); }
.gp-hstat { min-width:80px; }
.gp-hstat-val {
    font-size:13px; font-weight:700; line-height:1.2; margin-bottom:3px;
    display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden;
}
.gp-hstat-label { font-size:10.5px; opacity:.72; font-weight:500; }

/* Progress bar */
.gp-progress {
    position:relative;
    margin-top:14px; padding-top:14px;
    border-top:1px solid rgba(255,255,255,.22);
}
.gp-progress-head {
    display:flex; justify-content:space-between; align-items:baseline;
    margin-bottom:6px; font-size:11.5px;
}
.gp-progress-label { font-weight:700; letter-spacing:.4px; text-transform:uppercase; opacity:.85; }
.gp-progress-val { font-weight:800; font-size:13px; }
.gp-progress-bar {
    height:6px; border-radius:3px;
    background:rgba(255,255,255,.2); overflow:hidden;
}
.gp-progress-fill {
    height:100%; border-radius:3px;
    background:linear-gradient(to right, #ffffff, rgba(255,255,255,.8));
    transition:width 1s ease-out;
}

@media (max-width:720px){
    .gp-hero{border-radius:12px;}
    .gp-hero-body{gap:14px; padding:18px 18px 16px; align-items:flex-start;}
    .gp-avatar{width:64px; height:64px; font-size:20px; border-width:2.5px;}
    .gp-hero-name{font-size:19px;}
    .gp-hero-email{font-size:11.5px;}
    .gp-hero-badge{padding:3px 9px; font-size:10.5px;}
    .gp-hero-stats{padding-left:0; border-left:none; padding-top:12px; margin-top:4px; border-top:1px solid rgba(255,255,255,.2); width:100%; gap:10px; }
    .gp-hstat{min-width:70px;}
    .gp-hstat-val{font-size:12px;}
}

/* ══════ Form Sections ══════ */
.gp-section {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:14px; padding:20px 22px; margin-bottom:14px;
    transition:border-color .15s;
}
.gp-section:focus-within { border-color:color-mix(in srgb, var(--u-brand,#2563eb) 35%, var(--u-line)); }

.gp-section-head {
    display:flex; align-items:center; gap:10px;
    margin:0 0 16px; padding-bottom:12px;
    border-bottom:1px solid var(--u-line);
}
.gp-section-icon {
    width:32px; height:32px; border-radius:9px;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; flex-shrink:0;
    background:color-mix(in srgb, var(--u-brand,#2563eb) 10%, #fff);
    border:1px solid color-mix(in srgb, var(--u-brand,#2563eb) 20%, transparent);
    color:var(--u-brand,#2563eb);
}
.gp-section-title {
    font-size:14px; font-weight:800; color:var(--u-text);
    line-height:1.2;
}
.gp-section-sub {
    font-size:11.5px; color:var(--u-muted); font-weight:500;
    margin-top:2px;
}

/* Form grid */
.gp-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
@media(max-width:700px){ .gp-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:480px){ .gp-grid { grid-template-columns:1fr; } }
.gp-field label {
    display:block; font-size:11.5px; font-weight:700;
    color:var(--u-text); margin-bottom:6px;
    letter-spacing:.2px;
}
.gp-field .req { color:#dc2626; margin-left:2px; }
.gp-field input,
.gp-field select,
.gp-field textarea {
    width:100%; padding:10px 12px;
    border:1.5px solid var(--u-line); border-radius:9px;
    font-size:13.5px; color:var(--u-text); background:var(--u-card);
    transition:border-color .15s, box-shadow .15s;
    box-sizing:border-box; font-family:inherit;
}
.gp-field input:focus,
.gp-field select:focus,
.gp-field textarea:focus {
    outline:none; border-color:var(--u-brand);
    box-shadow:0 0 0 3px color-mix(in srgb, var(--u-brand,#2563eb) 15%, transparent);
}
.gp-field input:hover,
.gp-field select:hover { border-color:color-mix(in srgb, var(--u-brand,#2563eb) 30%, var(--u-line)); }
.gp-field.span2 { grid-column:span 2; }
.gp-field.span3 { grid-column:span 3; }
@media(max-width:700px){ .gp-field.span2, .gp-field.span3 { grid-column:span 2; } }
@media(max-width:480px){ .gp-field.span2, .gp-field.span3 { grid-column:span 1; } }

/* ══════ Save action bar ══════ */
.gp-save-bar {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:14px; padding:14px 18px; margin-bottom:16px;
    display:flex; align-items:center; gap:14px; justify-content:space-between;
    flex-wrap:wrap;
}
.gp-save-hint {
    font-size:12px; color:var(--u-muted);
    display:flex; align-items:center; gap:6px;
}
.gp-save-btn {
    padding:10px 24px; border:none; border-radius:22px;
    background:var(--u-brand,#2563eb); color:#fff;
    font-size:13.5px; font-weight:700; cursor:pointer;
    box-shadow:0 4px 14px color-mix(in srgb, var(--u-brand,#2563eb) 35%, transparent);
    transition:transform .15s, box-shadow .15s;
    display:inline-flex; align-items:center; gap:6px;
}
.gp-save-btn:hover { transform:translateY(-1px); box-shadow:0 6px 18px color-mix(in srgb, var(--u-brand,#2563eb) 45%, transparent); }

/* ══════ Alerts ══════ */
.gp-alert {
    border-radius:10px; padding:12px 16px;
    margin-bottom:16px;
    display:flex; align-items:flex-start; gap:10px;
    font-size:13px; line-height:1.45;
}
.gp-alert.ok { background:color-mix(in srgb, #16a34a 10%, var(--u-card)); border:1px solid color-mix(in srgb, #16a34a 25%, transparent); color:#166534; }
.gp-alert.err { background:color-mix(in srgb, #dc2626 8%, var(--u-card)); border:1px solid color-mix(in srgb, #dc2626 25%, transparent); color:#991b1b; }
.gp-alert-icon {
    width:22px; height:22px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:800; flex-shrink:0;
}
.gp-alert.ok .gp-alert-icon { background:#16a34a; color:#fff; }
.gp-alert.err .gp-alert-icon { background:#dc2626; color:#fff; }

/* ══════ Submission History ══════ */
.gp-snapshot-list { display:flex; flex-direction:column; gap:8px; }
.gp-snapshot {
    display:flex; align-items:center; gap:12px;
    padding:12px 14px; border-radius:10px;
    background:color-mix(in srgb, var(--u-brand,#2563eb) 3%, var(--u-bg));
    border:1px solid var(--u-line);
    transition:border-color .15s;
}
.gp-snapshot:hover { border-color:color-mix(in srgb, var(--u-brand,#2563eb) 30%, var(--u-line)); }
.gp-snapshot-ver {
    font-size:11px; font-weight:800; padding:3px 9px; border-radius:6px;
    background:var(--u-brand,#2563eb); color:#fff; flex-shrink:0;
}
.gp-snapshot-info { flex:1; min-width:0; }
.gp-snapshot-meta { font-size:11.5px; color:var(--u-muted); line-height:1.35; }
.gp-snapshot-time { font-size:12.5px; font-weight:600; color:var(--u-text); margin-bottom:2px; }

/* ══════ Language Skills add button ══════ */
.gp-lang-add {
    display:inline-flex; align-items:center; gap:6px;
    padding:9px 18px; border:none; border-radius:20px;
    background:color-mix(in srgb, var(--u-brand,#2563eb) 10%, #fff);
    color:var(--u-brand,#2563eb);
    border:1px solid color-mix(in srgb, var(--u-brand,#2563eb) 25%, transparent);
    font-size:13px; font-weight:700; cursor:pointer;
    transition:background .12s;
}
.gp-lang-add:hover { background:color-mix(in srgb, var(--u-brand,#2563eb) 18%, #fff); }

/* Minimalist overrides */
.jm-minimalist .gp-hero { background:#e2e5ec !important; color:var(--u-text,#1a1a1a) !important; border:1px solid rgba(0,0,0,.1) !important; }
.jm-minimalist .gp-hero::before { display:none !important; }
.jm-minimalist .gp-hero * { color:inherit !important; opacity:1 !important; }
.jm-minimalist .gp-field input:focus,
.jm-minimalist .gp-field select:focus,
.jm-minimalist .gp-field textarea:focus { box-shadow:none; }
</style>
@endpush

@section('content')
@php
    $fullName = trim(($guest?->first_name ?? '').' '.($guest?->last_name ?? ''));
    $initials = strtoupper(substr(trim(($guest?->first_name ?? 'G').' '.($guest?->last_name ?? 'U')), 0, 2));
    $photoUrl = trim((string)($guest?->profile_photo_path ?? '')) !== ''
                ? asset('storage/'.$guest->profile_photo_path) : '';
    $photoThumbUrl = $photoUrl !== '' ? asset('storage/'.str_replace('.webp','_thumb.webp',$guest->profile_photo_path)) : '';

    $contractStatusLabel = match((string)($guest?->contract_status ?? '')) {
        'not_requested' => 'Talep Edilmedi',
        'requested'     => 'Talep Edildi',
        'sent'          => 'Gönderildi',
        'signed'        => 'İmzalandı',
        default         => ($guest?->contract_status ?? '-'),
    };
    $appTypeLabel = match((string)($guest?->application_type ?? '')) {
        'bachelor' => 'Lisans',
        'master'   => 'Yüksek Lisans',
        'phd'      => 'Doktora',
        'language' => 'Dil Kursu',
        default    => ($guest?->application_type ?? '-'),
    };
    $skillsSummary = collect($guest?->language_skills ?? []);
    $langLabels = ['tr'=>'Türkçe','de'=>'Almanca','en'=>'İngilizce','fr'=>'Fransızca','es'=>'İspanyolca','it'=>'İtalyanca','ar'=>'Arapça','other'=>'Diğer'];
    $firstSkill = $skillsSummary->first();
    $firstLang = $firstSkill
        ? (($firstSkill['lang'] === 'other' ? ($firstSkill['custom'] ?: 'Diğer') : ($langLabels[$firstSkill['lang']] ?? $firstSkill['lang'])) . ' / ' . $firstSkill['level'])
        : ($guest?->language_level ?: '-');
    $extraLangs = max(0, $skillsSummary->count() - 1);
    $snapshots = $registrationSnapshots ?? collect();

    // Profil tamamlama oranı (basit heuristic)
    $fields = [
        !empty($guest?->first_name),
        !empty($guest?->last_name),
        !empty($guest?->email),
        !empty($guest?->phone),
        !empty($guest?->gender),
        !empty($guest?->communication_language),
        !empty($guest?->application_country),
        !empty($guest?->target_city),
        !empty($guest?->target_term),
        !empty($guest?->application_type),
        $photoUrl !== '',
        $skillsSummary->isNotEmpty() || !empty($guest?->language_level),
    ];
    $filled = count(array_filter($fields));
    $total = count($fields);
    $completion = $total > 0 ? (int) round(($filled / $total) * 100) : 0;
@endphp

{{-- ══════ Hero ══════ --}}
<div class="gp-hero">
    <div class="gp-hero-body">
        <div class="gp-avatar-wrap">
            <label for="profilePhotoInput" style="display:block;cursor:pointer;" title="Fotoğrafı değiştir">
                <div class="gp-avatar">
                    @if($photoUrl !== '')
                        <img src="{{ $photoUrl }}"
                             srcset="{{ $photoThumbUrl }} 200w, {{ $photoUrl }} 400w"
                             sizes="(max-width:768px) 80px, 120px"
                             alt="Profil" loading="lazy">
                    @else
                        {{ $initials }}
                    @endif
                </div>
                <div class="gp-avatar-overlay">📷</div>
            </label>
            <form method="POST" action="{{ route('guest.profile.photo') }}" enctype="multipart/form-data"
                  id="photoForm" style="position:absolute;width:0;height:0;overflow:hidden;">
                @csrf
                <input type="file" id="profilePhotoInput" name="profile_photo"
                    accept=".jpg,.jpeg,.png,.webp,image/*"
                    onchange="document.getElementById('photoForm').submit()">
            </form>
        </div>

        <div class="gp-hero-main">
            <div class="gp-hero-label"><span class="gp-hero-marker"></span>Aday Öğrenci Profili</div>
            <div class="gp-hero-name">{{ $fullName ?: 'İsim belirtilmedi' }}</div>
            <div class="gp-hero-email">{{ $guest?->email ?? '-' }}</div>
            <div class="gp-hero-badges">
                @if($appTypeLabel !== '-')<span class="gp-hero-badge">🎓 {{ $appTypeLabel }}</span>@endif
                @if($guest?->target_city)<span class="gp-hero-badge">📍 {{ $guest->target_city }}</span>@endif
                @if($guest?->target_term)<span class="gp-hero-badge">📅 {{ $guest->target_term }}</span>@endif
            </div>
        </div>

        <div class="gp-hero-stats">
            <div class="gp-hstat">
                <div class="gp-hstat-val">{{ $firstLang }}{{ $extraLangs > 0 ? ' +'.$extraLangs : '' }}</div>
                <div class="gp-hstat-label">Dil Becerileri</div>
            </div>
            <div class="gp-hstat">
                <div class="gp-hstat-val">{{ $guest?->selected_package_title ?: '—' }}</div>
                <div class="gp-hstat-label">Seçili Paket</div>
            </div>
            <div class="gp-hstat">
                <div class="gp-hstat-val">{{ $contractStatusLabel }}</div>
                <div class="gp-hstat-label">Sözleşme</div>
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="gp-progress" style="width:100%;">
            <div class="gp-progress-head">
                <span class="gp-progress-label">Profil Tamamlama</span>
                <span class="gp-progress-val">%{{ $completion }} · {{ $filled }}/{{ $total }} alan</span>
            </div>
            <div class="gp-progress-bar">
                <div class="gp-progress-fill" style="width:{{ $completion }}%"></div>
            </div>
        </div>
    </div>
</div>

{{-- Flash Messages --}}
@if(session('success'))
<div class="gp-alert ok">
    <div class="gp-alert-icon">✓</div>
    <div>{{ session('success') }}</div>
</div>
@endif
@if($errors->any())
<div class="gp-alert err">
    <div class="gp-alert-icon">!</div>
    <div>
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
</div>
@endif

{{-- ══════ Form ══════ --}}
<form method="POST" action="{{ route('guest.profile.update') }}">
    @csrf

    <div class="gp-section">
        <div class="gp-section-head">
            <div class="gp-section-icon">👤</div>
            <div>
                <div class="gp-section-title">Kişisel Bilgiler</div>
                <div class="gp-section-sub">İsim, iletişim ve temel bilgiler</div>
            </div>
        </div>
        <div class="gp-grid">
            <div class="gp-field">
                <label>Ad <span class="req">*</span></label>
                <input name="first_name" value="{{ old('first_name', $guest?->first_name ?? '') }}" required>
            </div>
            <div class="gp-field">
                <label>Soyad <span class="req">*</span></label>
                <input name="last_name" value="{{ old('last_name', $guest?->last_name ?? '') }}" required>
            </div>
            <div class="gp-field">
                <label>Telefon</label>
                <input name="phone" value="{{ old('phone', $guest?->phone ?? '') }}" placeholder="+49 123 456 7890">
            </div>
            <div class="gp-field">
                <label>Cinsiyet</label>
                @php $genderVal = old('gender', $guest?->gender ?? ''); @endphp
                <select name="gender">
                    <option value="">Seçiniz</option>
                    <option value="kadin" @selected($genderVal==='kadin')>Kadın</option>
                    <option value="erkek" @selected($genderVal==='erkek')>Erkek</option>
                    <option value="belirtmek_istemiyorum" @selected($genderVal==='belirtmek_istemiyorum')>Belirtmek istemiyorum</option>
                </select>
            </div>
            <div class="gp-field">
                <label>İletişim Dili</label>
                @php $langVal = old('communication_language', $guest?->communication_language ?? ''); @endphp
                <select name="communication_language">
                    <option value="">Seçiniz</option>
                    <option value="tr" @selected($langVal==='tr')>Türkçe</option>
                    <option value="de" @selected($langVal==='de')>Deutsch</option>
                    <option value="en" @selected($langVal==='en')>English</option>
                </select>
            </div>
        </div>
    </div>

    <div class="gp-section">
        <div class="gp-section-head">
            <div class="gp-section-icon">🎯</div>
            <div>
                <div class="gp-section-title">Başvuru Tercihleri</div>
                <div class="gp-section-sub">Hedef ülke, şehir ve dönem</div>
            </div>
        </div>
        <div class="gp-grid">
            <div class="gp-field">
                <label>Başvuru Ülkesi</label>
                @php $countryVal = old('application_country', $guest?->application_country ?? ''); @endphp
                <select name="application_country">
                    <option value="">Seçiniz</option>
                    @foreach(($applicationCountries ?? []) as $country)
                        <option value="{{ $country['label'] }}" @selected($countryVal === $country['label'])>
                            {{ $country['label'] }} ({{ $country['code'] }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="gp-field">
                <label>Hedef Şehir</label>
                <input name="target_city" value="{{ old('target_city', $guest?->target_city ?? '') }}" placeholder="ör. Berlin">
            </div>
            <div class="gp-field">
                <label>Hedef Dönem</label>
                <input name="target_term" value="{{ old('target_term', $guest?->target_term ?? '') }}" placeholder="ör. 2025 Kış">
            </div>
        </div>
    </div>

    <div class="gp-section">
        <div class="gp-section-head">
            <div class="gp-section-icon">🗣</div>
            <div>
                <div class="gp-section-title">Dil Becerileri</div>
                <div class="gp-section-sub">Bildiğin diller ve seviyeler (maks. 10)</div>
            </div>
        </div>
        <script type="application/json" id="langSkillsSeed">{{ json_encode(
            old('language_skills', $guest?->language_skills ?? [])
                ?: ($guest?->language_level ? [['lang'=>'de','level'=>$guest->language_level,'custom'=>'']] : [])
        , JSON_UNESCAPED_UNICODE) }}</script>
        <div id="langSkillsContainer" style="margin-bottom:12px;"></div>
        <button type="button" id="langAddBtn" class="gp-lang-add">+ Dil Ekle</button>
        <div style="font-size:11.5px;color:var(--u-muted);margin-top:8px;">
            "Diğer" seçeneğinde dil adını kendin yazabilirsin.
        </div>
    </div>

    {{-- Save bar --}}
    <div class="gp-save-bar">
        <div class="gp-save-hint">
            💾 Değişiklikleri kaydetmek için "Profili Kaydet" butonuna bas.
        </div>
        <button class="gp-save-btn" type="submit">
            Profili Kaydet <span>→</span>
        </button>
    </div>
</form>

{{-- ══════ Submission History ══════ --}}
@if(!$snapshots->isEmpty())
<div class="gp-section">
    <div class="gp-section-head">
        <div class="gp-section-icon">📜</div>
        <div>
            <div class="gp-section-title">Başvuru Gönderim Geçmişi</div>
            <div class="gp-section-sub">{{ $snapshots->count() }} kayıt bulundu</div>
        </div>
    </div>
    <div class="gp-snapshot-list">
        @foreach($snapshots as $snap)
            @php $warnings = (int)data_get($snap->meta_json, 'warnings_count', 0); @endphp
            <div class="gp-snapshot">
                <span class="gp-snapshot-ver">v{{ $snap->snapshot_version }}</span>
                <div class="gp-snapshot-info">
                    <div class="gp-snapshot-time">{{ $snap->submitted_at }}</div>
                    <div class="gp-snapshot-meta">{{ $snap->submitted_by_email ?: '—' }}</div>
                </div>
                <span class="badge {{ $warnings > 0 ? 'warn' : 'ok' }}" style="flex-shrink:0;">
                    {{ $warnings > 0 ? '⚠ '.$warnings.' uyarı' : '✓ Temiz' }}
                </span>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
(function(){
    var _orig=window.__designToggle;
    window.__designToggle=function(){
        if(_orig)_orig.apply(this,arguments);
        setTimeout(function(){
            document.documentElement.classList.toggle('jm-minimalist',localStorage.getItem('mentorde_design')==='minimalist');
        },50);
    };
})();

// Animate progress bar on page load
(function(){
    var fill = document.querySelector('.gp-progress-fill');
    if (!fill) return;
    var target = fill.style.width;
    fill.style.width = '0';
    setTimeout(function(){ fill.style.width = target; }, 100);
})();
</script>
<script defer src="{{ Vite::asset('resources/js/guest-language-skills.js') }}"></script>
@endpush

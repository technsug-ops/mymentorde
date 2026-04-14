@extends('guest.layouts.app')

@section('title', 'Profilim')
@section('page_title', 'Profilim')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ── gp-* Aday Öğrenci Profile scoped ── */
.gp-hero {
    background: linear-gradient(to right, var(--theme-hero-from-guest) 0%, var(--theme-hero-to-guest) 100%);
    border-radius: 16px;
    padding: 28px 28px 26px;
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
}
.gp-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.gp-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: 40%;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.gp-avatar-wrap { position: relative; flex-shrink: 0; z-index: 1; }
.gp-avatar {
    width: 90px; height: 90px;
    border-radius: 50%;
    background: rgba(255,255,255,.18);
    border: 3px solid rgba(255,255,255,.4);
    overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 28px;
    position: relative;
}
.gp-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
.gp-avatar-overlay {
    position: absolute; inset: 0; border-radius: 50%;
    background: rgba(0,0,0,.42); color: #fff;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    font-size: 20px; opacity: 0; transition: opacity .2s; cursor: pointer; z-index: 2;
}
.gp-avatar-wrap:hover .gp-avatar-overlay { opacity: 1; }
.gp-hero-info { flex: 1; min-width: 200px; z-index: 1; }
.gp-hero-name { font-size: 22px; font-weight: 800; color: #fff; margin: 0 0 4px; letter-spacing: -.2px; }
.gp-hero-email { font-size: 13px; color: rgba(255,255,255,.75); margin-bottom: 10px; }
.gp-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.gp-hero-badge {
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 3px 12px;
    font-size: 12px; color: #fff; font-weight: 600;
}
.gp-hero-stats { display: flex; gap: 20px; flex-wrap: wrap; margin-left: auto; z-index: 1; }
.gp-hstat { text-align: center; min-width: 70px; }
.gp-hstat-val { font-size: 14px; font-weight: 700; color: #fff; line-height: 1.2; margin-bottom: 4px; }
.gp-hstat-label { font-size: 11px; color: rgba(255,255,255,.65); font-weight: 500; }
.gp-hstat-sep { width: 1px; background: rgba(255,255,255,.2); align-self: stretch; }

/* Form sections */
.gp-section {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 14px;
    padding: 22px 24px;
    margin-bottom: 14px;
}
.gp-section-title {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px; color: var(--u-muted);
    margin: 0 0 16px; padding-bottom: 12px;
    border-bottom: 1px solid var(--u-line);
}
.gp-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}
@media(max-width:700px){ .gp-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:480px){ .gp-grid { grid-template-columns: 1fr; } }
.gp-field label {
    display: block; font-size: 12px; font-weight: 600;
    color: var(--u-text); margin-bottom: 6px;
}
.gp-field input,
.gp-field select,
.gp-field textarea {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid var(--u-line); border-radius: 8px;
    font-size: 14px; color: var(--u-text); background: var(--u-card);
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box; font-family: inherit;
}
.gp-field input:focus,
.gp-field select:focus,
.gp-field textarea:focus {
    outline: none; border-color: var(--u-brand);
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.gp-field.span2 { grid-column: span 2; }
.gp-field.span3 { grid-column: span 3; }
@media(max-width:700px){ .gp-field.span2, .gp-field.span3 { grid-column: span 2; } }
@media(max-width:480px){ .gp-field.span2, .gp-field.span3 { grid-column: span 1; } }

/* ════════════════════════════════════════
   MINIMALİST OVERRIDES
════════════════════════════════════════ */
.jm-minimalist .gp-hero::before,
.jm-minimalist .gp-hero::after { display: none; }

.jm-minimalist .gp-field input:focus,
.jm-minimalist .gp-field select:focus,
.jm-minimalist .gp-field textarea:focus { box-shadow: none; }
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
        'not_requested' => 'Sözleşme Talep Edilmedi',
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
@endphp

{{-- ── Profile Hero ── --}}
<div class="gp-hero">
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

    <div class="gp-hero-info">
        <div class="gp-hero-name">{{ $fullName ?: 'İsim belirtilmedi' }}</div>
        <div class="gp-hero-email">{{ $guest?->email ?? '-' }}</div>
        <div class="gp-hero-badges">
            @if($appTypeLabel !== '-')
                <span class="gp-hero-badge">{{ $appTypeLabel }}</span>
            @endif
            @if($guest?->target_city)
                <span class="gp-hero-badge">{{ $guest->target_city }}</span>
            @endif
            @if($guest?->target_term)
                <span class="gp-hero-badge">{{ $guest->target_term }}</span>
            @endif
        </div>
    </div>

    <div class="gp-hero-stats">
        <div class="gp-hstat">
            <div class="gp-hstat-val">{{ $firstLang }}{{ $extraLangs > 0 ? ' +'.$extraLangs : '' }}</div>
            <div class="gp-hstat-label">Dil Becerileri</div>
        </div>
        <div class="gp-hstat-sep"></div>
        <div class="gp-hstat">
            <div class="gp-hstat-val">{{ $guest?->selected_package_title ?: '-' }}</div>
            <div class="gp-hstat-label">Seçili Paket</div>
        </div>
        <div class="gp-hstat-sep"></div>
        <div class="gp-hstat">
            <div class="gp-hstat-val">{{ $contractStatusLabel }}</div>
            <div class="gp-hstat-label">Sözleşme</div>
        </div>
    </div>
</div>

{{-- Flash Messages --}}
@if(session('success'))
    <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;color:#166534;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
        <span>✓</span> {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;color:#991b1b;margin-bottom:16px;">
        @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
    </div>
@endif

{{-- ── Update Form ── --}}
<form method="POST" action="{{ route('guest.profile.update') }}">
    @csrf

    <div class="gp-section">
        <div class="gp-section-title">Kişisel Bilgiler</div>
        <div class="gp-grid">
            <div class="gp-field">
                <label>Ad <span style="color:var(--u-danger);">*</span></label>
                <input name="first_name" value="{{ old('first_name', $guest?->first_name ?? '') }}" required>
            </div>
            <div class="gp-field">
                <label>Soyad <span style="color:var(--u-danger);">*</span></label>
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
        <div class="gp-section-title">Başvuru Tercihleri</div>
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
        <div class="gp-section-title">Dil Becerileri</div>
        <script type="application/json" id="langSkillsSeed">{{ json_encode(
            old('language_skills', $guest?->language_skills ?? [])
                ?: ($guest?->language_level ? [['lang'=>'de','level'=>$guest->language_level,'custom'=>'']] : [])
        , JSON_UNESCAPED_UNICODE) }}</script>
        <div id="langSkillsContainer" style="margin-bottom:12px;"></div>
        <button type="button" id="langAddBtn"
            style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;
                   border:none;border-radius:8px;background:var(--u-brand);
                   color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
            + Dil Ekle
        </button>
        <div class="muted" style="font-size:var(--tx-xs);margin-top:8px;">Maks. 10 dil. "Diğer" seçeneğinde dil adını kendiniz yazın.</div>
    </div>

    <div style="margin-bottom:20px;">
        <button class="btn ok" type="submit" style="padding:10px 28px;">Profili Kaydet</button>
    </div>
</form>

{{-- ── Submission History ── --}}
@if(!$snapshots->isEmpty())
<div class="gp-section">
    <div class="gp-section-title">Başvuru Gönderim Geçmişi</div>
    <div class="list">
        @foreach($snapshots as $snap)
            @php $warnings = (int)data_get($snap->meta_json, 'warnings_count', 0); @endphp
            <div class="item">
                <div>
                    <strong>v{{ $snap->snapshot_version }}</strong>
                    <span class="muted" style="margin-left:8px;font-size:var(--tx-sm);">{{ $snap->submitted_at }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="muted" style="font-size:var(--tx-xs);">{{ $snap->submitted_by_email ?: '-' }}</span>
                    <span class="badge {{ $warnings > 0 ? 'warn' : 'ok' }}">{{ $warnings > 0 ? 'Uyarı: '.$warnings : 'Temiz' }}</span>
                </div>
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
</script>
<script defer src="{{ Vite::asset('resources/js/guest-language-skills.js') }}"></script>
@endpush

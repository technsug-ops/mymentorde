@extends('student.layouts.app')

@section('title', 'Öğrenci - Profil')
@section('page_title', 'Profil')

@push('head')
<style>
/* ── Profile Hero ── */
.prf-hero {
    background: linear-gradient(to right, #4c1d95 0%, #7c3aed 60%, #8b5cf6 100%);
    border-radius: 16px;
    padding: 32px 28px 28px;
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
}
.prf-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.prf-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: 40%;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
/* Avatar */
.prf-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}
.prf-avatar {
    width: 96px; height: 96px;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 3px solid rgba(255,255,255,.4);
    overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 30px;
    position: relative;
    z-index: 1;
}
.prf-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
.prf-avatar-upload {
    position: absolute;
    bottom: 2px; right: 2px;
    z-index: 2;
}
.prf-avatar-upload label {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: var(--u-card);
    color: #7c3aed;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,.25);
    font-size: 13px;
    border: 2px solid #e2e8f0;
    transition: background .15s;
}
.prf-avatar-upload label:hover { background: #ede9fe; }
/* Hero text */
.prf-hero-info { flex: 1; min-width: 200px; }
.prf-hero-name {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 4px;
    line-height: 1.2;
}
.prf-hero-email {
    font-size: 13px;
    color: rgba(255,255,255,.75);
    margin-bottom: 10px;
}
.prf-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.prf-hero-badge {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 3px 12px;
    font-size: 12px;
    color: #fff;
    font-weight: 600;
}
/* Hero stats row */
.prf-hero-stats {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-left: auto;
    z-index: 1;
}
.prf-hstat {
    text-align: center;
    min-width: 80px;
}
.prf-hstat-val {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    line-height: 1;
    margin-bottom: 4px;
}
.prf-hstat-label {
    font-size: 11px;
    color: rgba(255,255,255,.7);
    font-weight: 500;
    letter-spacing: .3px;
}
.prf-hstat-sep {
    width: 1px;
    background: rgba(255,255,255,.2);
    align-self: stretch;
}

/* ── Info Cards ── */
.prf-info-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .prf-info-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px) { .prf-info-grid { grid-template-columns: 1fr; } }
.prf-info-card {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 12px;
    padding: 14px 16px;
}
.prf-info-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--u-muted);
    margin-bottom: 6px;
}
.prf-info-value {
    font-size: 14px;
    font-weight: 700;
    color: var(--u-text);
    word-break: break-word;
}
/* Progress bar */
.prf-progress {
    margin-top: 8px;
    height: 5px;
    background: var(--u-line);
    border-radius: 999px;
    overflow: hidden;
}
.prf-progress-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #7c3aed, #6d28d9);
    transition: width .6s ease;
}
.prf-progress-fill.ok { background: linear-gradient(90deg, #059669, #34d399); }

/* ── Form Section ── */
.prf-form-section {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 14px;
    padding: 22px 24px;
    margin-bottom: 16px;
}
.prf-form-section-title {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: var(--u-muted);
    margin: 0 0 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--u-line);
    display: flex; align-items: center; gap: 8px;
}
.prf-form-section-title::before {
    content: '';
    display: inline-block; width: 3px; height: 14px;
    background: #7c3aed; border-radius: 2px; flex-shrink: 0;
}
.prf-field-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}
@media (max-width: 900px) { .prf-field-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px) { .prf-field-grid { grid-template-columns: 1fr; } }
.prf-field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--u-text);
    margin-bottom: 6px;
}
.prf-field input,
.prf-field select,
.prf-field textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid var(--u-line);
    border-radius: 8px;
    font-size: 14px;
    color: var(--u-text);
    background: var(--u-card);
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
}
.prf-field input:focus,
.prf-field select:focus,
.prf-field textarea:focus {
    outline: none;
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124,58,237,.12);
}
.prf-field.span2 { grid-column: span 2; }
.prf-field.span4 { grid-column: span 4; }
@media (max-width: 900px) {
    .prf-field.span2, .prf-field.span4 { grid-column: span 2; }
}
@media (max-width: 500px) {
    .prf-field.span2, .prf-field.span4 { grid-column: span 1; }
}

/* ── Status Cards ── */
.prf-status-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
@media (max-width: 700px) { .prf-status-grid { grid-template-columns: repeat(2, 1fr); } }
.prf-stat-card {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 12px;
    padding: 14px 16px;
}
.prf-stat-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--u-muted);
    margin-bottom: 6px;
}
.prf-stat-value {
    font-size: 14px;
    font-weight: 700;
    color: var(--u-text);
}

/* Photo upload flash area */
.prf-photo-form { display: none; }
</style>
@endpush

@section('content')
@php
    $guest = $guestApplication;
    $fullName = trim(($guest?->first_name ?? '').' '.($guest?->last_name ?? ''));
    $seedName = trim((string)(($guest?->first_name ?: '') ?: ($user?->name ?? 'ST')));
    $initials  = strtoupper(substr($seedName !== '' ? $seedName : 'ST', 0, 2));
    $photoPath = trim((string)($guest?->profile_photo_path ?? ''));
    $photoUrl      = $photoPath !== '' ? asset('storage/'.$photoPath) : '';
    $photoThumbUrl = $photoPath !== '' ? asset('storage/'.str_replace('.webp','_thumb.webp',$photoPath)) : '';
    $profileCompletion = (int)($progressPercent ?? 0);
    $reqDone  = (int) data_get($docSummary, 'required_done', 0);
    $reqTotal = (int) data_get($docSummary, 'required_total', 0);
    $docsProgress = (int) round(($reqDone / max(1, $reqTotal)) * 100);
    $displayName = $fullName ?: ($user?->name ?? '-');
@endphp

{{-- ── Hero ── --}}
<div class="prf-hero">
    {{-- Avatar + upload --}}
    <div class="prf-avatar-wrap">
        <div class="prf-avatar" id="prf-avatar-preview">
            @if($photoUrl !== '')
                <img src="{{ $photoUrl }}"
                     srcset="{{ $photoThumbUrl }} 200w, {{ $photoUrl }} 400w"
                     sizes="(max-width:768px) 80px, 120px"
                     alt="Profil" loading="lazy">
            @else
                {{ $initials }}
            @endif
        </div>
        <div class="prf-avatar-upload">
            <form id="prf-photo-form" method="POST" action="{{ route('student.profile.photo') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="profile_photo" id="prf-photo-input"
                       accept=".jpg,.jpeg,.png,.webp,image/*"
                       style="display:none;"
                       onchange="document.getElementById('prf-photo-form').submit()">
                <label for="prf-photo-input" title="Fotoğraf değiştir">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M15 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                        <polyline points="15 3 15 9 21 9"/>
                    </svg>
                </label>
            </form>
        </div>
    </div>

    {{-- Name + badges --}}
    <div class="prf-hero-info">
        <div class="prf-hero-name">{{ $displayName }}</div>
        <div class="prf-hero-email">{{ $user?->email ?? '-' }}</div>
        <div class="prf-hero-badges">
            <span class="prf-hero-badge">{{ $studentId ?: ($user?->student_id ?? '-') }}</span>
            @if($guest?->target_city)
                <span class="prf-hero-badge">{{ $guest->target_city }}</span>
            @endif
            @if($guest?->target_term)
                <span class="prf-hero-badge">{{ $guest->target_term }}</span>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="prf-hero-stats">
        <div class="prf-hstat">
            <div class="prf-hstat-val">%{{ $profileCompletion }}</div>
            <div class="prf-hstat-label">Süreç</div>
        </div>
        <div class="prf-hstat-sep"></div>
        <div class="prf-hstat">
            <div class="prf-hstat-val">{{ $reqDone }}/{{ $reqTotal }}</div>
            <div class="prf-hstat-label">Belgeler</div>
        </div>
        <div class="prf-hstat-sep"></div>
        <div class="prf-hstat">
            <div class="prf-hstat-val">{{ (int)($notificationCount ?? 0) }}</div>
            <div class="prf-hstat-label">Bildirim</div>
        </div>
    </div>
</div>

{{-- ── Info Cards ── --}}
<div class="prf-info-grid">
    <div class="prf-info-card">
        <div class="prf-info-label">Telefon</div>
        <div class="prf-info-value">{{ $guest?->phone ?: '-' }}</div>
    </div>
    <div class="prf-info-card">
        <div class="prf-info-label">Dil Seviyesi</div>
        <div class="prf-info-value">{{ $guest?->language_level ?: '-' }}</div>
    </div>
    <div class="prf-info-card">
        <div class="prf-info-label">Zorunlu Belge Tamamlama</div>
        <div class="prf-info-value">%{{ $docsProgress }}</div>
        <div class="prf-progress">
            <div class="prf-progress-fill {{ $docsProgress >= 100 ? 'ok' : '' }}" style="width:{{ $docsProgress }}%;"></div>
        </div>
    </div>
    <div class="prf-info-card">
        <div class="prf-info-label">Atanan Danışman</div>
        <div class="prf-info-value" style="font-size:var(--tx-xs);">{{ $assignment?->senior_email ?? '-' }}</div>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
    <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;color:#166534;font-weight:600;margin-bottom:16px;">
        {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;color:#991b1b;margin-bottom:16px;">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

{{-- ── Update Form ── --}}
<form method="POST" action="{{ route('student.profile.update') }}">
    @csrf

    <div class="prf-form-section">
        <div class="prf-form-section-title">Kişisel Bilgiler</div>
        <div class="prf-field-grid">
            <div class="prf-field">
                <label>Ad</label>
                <input name="first_name" value="{{ old('first_name', $guest?->first_name ?? '') }}" required>
            </div>
            <div class="prf-field">
                <label>Soyad</label>
                <input name="last_name" value="{{ old('last_name', $guest?->last_name ?? '') }}" required>
            </div>
            <div class="prf-field">
                <label>Telefon</label>
                <input name="phone" value="{{ old('phone', $guest?->phone ?? '') }}">
            </div>
            <div class="prf-field">
                <label>Cinsiyet</label>
                @php $genderVal = old('gender', $guest?->gender ?? ''); @endphp
                <select name="gender">
                    <option value="">Seçiniz</option>
                    <option value="kadin" @selected($genderVal === 'kadin')>Kadın</option>
                    <option value="erkek" @selected($genderVal === 'erkek')>Erkek</option>
                    <option value="belirtmek_istemiyorum" @selected($genderVal === 'belirtmek_istemiyorum')>Belirtmek istemiyorum</option>
                    <option value="not_specified" @selected($genderVal === 'not_specified')>Belirtilmemiş</option>
                </select>
            </div>
        </div>
    </div>

    <div class="prf-form-section">
        <div class="prf-form-section-title">Başvuru Tercihleri</div>
        <div class="prf-field-grid">
            <div class="prf-field">
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
            <div class="prf-field">
                <label>İletişim Dili</label>
                @php $commLang = old('communication_language', $guest?->communication_language ?? ''); @endphp
                <select name="communication_language">
                    <option value="">Seçiniz</option>
                    <option value="tr" @selected($commLang === 'tr')>Türkçe</option>
                    <option value="de" @selected($commLang === 'de')>Deutsch</option>
                    <option value="en" @selected($commLang === 'en')>English</option>
                </select>
            </div>
            <div class="prf-field">
                <label>Hedef Şehir</label>
                <input name="target_city" value="{{ old('target_city', $guest?->target_city ?? '') }}">
            </div>
            <div class="prf-field">
                <label>Hedef Dönem</label>
                <input name="target_term" value="{{ old('target_term', $guest?->target_term ?? '') }}">
            </div>
            <div class="prf-field">
                <label>Dil Seviyesi</label>
                <input name="language_level" value="{{ old('language_level', $guest?->language_level ?? '') }}">
            </div>
            <div class="prf-field span2">
                <label>Ek Not</label>
                <textarea name="notes" style="min-height:80px;">{{ old('notes', $guest?->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div style="margin-bottom:20px;">
        <button class="btn" type="submit" style="background:#7c3aed;color:#fff;padding:10px 24px;font-size:var(--tx-sm);">💾 Profili Kaydet</button>
    </div>
</form>

{{-- ── Süreç Durumu ── --}}
<div class="prf-form-section">
    <div class="prf-form-section-title">Süreç Durumu</div>
    <div class="prf-status-grid">
        <div class="prf-stat-card">
            <div class="prf-stat-label">Başvuru Tipi</div>
            <div class="prf-stat-value">{{ $guest?->application_type ?: '-' }}</div>
        </div>
        <div class="prf-stat-card">
            <div class="prf-stat-label">Sözleşme Durumu</div>
            <div class="prf-stat-value">{{ $guest?->contract_status ?: 'not_requested' }}</div>
        </div>
        <div class="prf-stat-card">
            <div class="prf-stat-label">Seçili Paket</div>
            <div class="prf-stat-value">{{ $guest?->selected_package_title ?: '-' }}</div>
        </div>
        <div class="prf-stat-card">
            <div class="prf-stat-label">Belgeler</div>
            <div class="prf-stat-value">
                <span style="color:#059669;">{{ (int) data_get($docSummary,'approved',0) }} onaylı</span>
                · {{ (int) data_get($docSummary,'uploaded',0) }} yüklendi
                @if((int) data_get($docSummary,'rejected',0) > 0)
                    · <span style="color:#dc2626;">{{ (int) data_get($docSummary,'rejected',0) }} reddedildi</span>
                @endif
            </div>
        </div>
        <div class="prf-stat-card">
            <div class="prf-stat-label">Durum Mesajı</div>
            <div class="prf-stat-value">{{ $guest?->status_message ?: '-' }}</div>
        </div>
        <div class="prf-stat-card">
            <div class="prf-stat-label">Bildirim Sayısı</div>
            <div class="prf-stat-value">{{ (int)($notificationCount ?? 0) }} kayıt</div>
        </div>
    </div>
</div>

{{-- ── Başvuru Gönderim Geçmişi ── --}}
@if(!($registrationSnapshots ?? collect())->isEmpty())
<div class="prf-form-section">
    <div class="prf-form-section-title">Başvuru Gönderim Geçmişi</div>
    <div class="list">
        @foreach($registrationSnapshots as $snap)
            @php $warnings = (int) data_get($snap->meta_json, 'warnings_count', 0); @endphp
            <div class="item">
                <div>
                    <strong>v{{ $snap->snapshot_version }}</strong>
                    <span class="muted" style="margin-left:8px;font-size:var(--tx-sm);">{{ $snap->submitted_at }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="muted" style="font-size:var(--tx-xs);">{{ $snap->submitted_by_email ?: '-' }}</span>
                    <span class="badge chip {{ $warnings > 0 ? 'warn' : 'ok' }}">{{ $warnings > 0 ? 'Uyarı: '.$warnings : 'Temiz' }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection

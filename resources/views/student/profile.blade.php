@extends('student.layouts.app')

@section('title', 'Öğrenci - Profil')
@section('page_title', 'Profil')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ══════ Hero (Option B + photo bg) ══════ */
.prf-hero {
    color:#fff; border-radius:14px; margin-bottom:20px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.14); position:relative;
    background:#4c1d95 url('https://images.unsplash.com/photo-1557804506-669a67965ba0?w=1400&q=80') center/cover;
}
.prf-hero::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, rgba(76,29,149,.92) 0%, rgba(124,58,237,.85) 100%);
}
.prf-hero-body { position:relative; display:flex; align-items:center; gap:22px; padding:24px 26px; flex-wrap:wrap; }

.prf-avatar-wrap { position:relative; flex-shrink:0; }
.prf-avatar {
    width:80px; height:80px; border-radius:50%;
    background:rgba(255,255,255,.2); border:3px solid rgba(255,255,255,.45);
    overflow:hidden;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:800; font-size:26px; letter-spacing:-.5px;
    position:relative;
    box-shadow:0 4px 14px rgba(0,0,0,.25);
}
.prf-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
.prf-avatar-upload { position:absolute; bottom:-2px; right:-2px; z-index:2; }
.prf-avatar-upload label {
    width:28px; height:28px; border-radius:50%;
    background:#fff; color:#7c3aed;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer;
    box-shadow:0 2px 8px rgba(0,0,0,.25);
    border:2px solid rgba(255,255,255,.9);
    transition:transform .15s, background .15s;
}
.prf-avatar-upload label:hover { background:#ede9fe; transform:scale(1.08); }

.prf-hero-main { flex:1; min-width:200px; }
.prf-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.82; margin-bottom:4px; }
.prf-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.prf-hero-name { font-size:24px; font-weight:800; margin:0 0 4px; letter-spacing:-.3px; line-height:1.15; }
.prf-hero-email { font-size:12.5px; opacity:.8; margin-bottom:10px; word-break:break-all; }
.prf-hero-badges { display:flex; gap:6px; flex-wrap:wrap; }
.prf-hero-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 11px; border-radius:20px;
    background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600;
    border:1px solid rgba(255,255,255,.12);
}
.prf-hero-stats { display:flex; gap:14px; flex-wrap:wrap; padding-left:16px; border-left:1px solid rgba(255,255,255,.22); }
.prf-hstat { min-width:70px; }
.prf-hstat-val { font-size:14px; font-weight:700; line-height:1.2; margin-bottom:3px; }
.prf-hstat-label { font-size:10.5px; opacity:.72; font-weight:500; }

@media (max-width:720px){
    .prf-hero{border-radius:12px;}
    .prf-hero-body{gap:14px; padding:18px 18px 16px; align-items:flex-start;}
    .prf-avatar{width:64px; height:64px; font-size:20px; border-width:2.5px;}
    .prf-hero-name{font-size:19px;}
    .prf-hero-email{font-size:11.5px;}
    .prf-hero-badge{padding:3px 9px; font-size:10.5px;}
    .prf-hero-stats{padding-left:0; border-left:none; padding-top:12px; margin-top:4px; border-top:1px solid rgba(255,255,255,.2); width:100%; gap:10px;}
    .prf-hstat{min-width:65px;}
    .prf-hstat-val{font-size:12.5px;}
}

/* ══════ Info cards ══════ */
.prf-info-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:20px; }
@media (max-width:900px){ .prf-info-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:480px){ .prf-info-grid { grid-template-columns:1fr; } }
.prf-info-card {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:12px; padding:13px 15px;
    transition:border-color .15s;
}
.prf-info-card:hover { border-color:color-mix(in srgb, var(--u-brand,#2563eb) 30%, var(--u-line)); }
.prf-info-label {
    font-size:10.5px; font-weight:700; color:var(--u-muted);
    letter-spacing:.4px; text-transform:uppercase; margin-bottom:4px;
}
.prf-info-value { font-size:13px; font-weight:700; color:var(--u-text); }
.prf-progress {
    height:5px; border-radius:3px; background:color-mix(in srgb, var(--u-brand,#2563eb) 12%, transparent);
    margin-top:8px; overflow:hidden;
}
.prf-progress-fill { height:100%; background:var(--u-brand,#2563eb); border-radius:3px; transition:width 1s ease-out; }
.prf-progress-fill.ok { background:#16a34a; }

/* ══════ Form sections ══════ */
.prf-section {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:14px; padding:20px 22px; margin-bottom:14px;
    transition:border-color .15s;
}
.prf-section:focus-within { border-color:color-mix(in srgb, var(--u-brand,#2563eb) 35%, var(--u-line)); }

.prf-section-head {
    display:flex; align-items:center; gap:10px;
    margin:0 0 16px; padding-bottom:12px;
    border-bottom:1px solid var(--u-line);
}
.prf-section-icon {
    width:32px; height:32px; border-radius:9px;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; flex-shrink:0;
    background:color-mix(in srgb, var(--u-brand,#2563eb) 10%, #fff);
    border:1px solid color-mix(in srgb, var(--u-brand,#2563eb) 20%, transparent);
    color:var(--u-brand,#2563eb);
}
.prf-section-title { font-size:14px; font-weight:800; color:var(--u-text); line-height:1.2; }
.prf-section-sub { font-size:11.5px; color:var(--u-muted); font-weight:500; margin-top:2px; }

.prf-field-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
@media(max-width:700px){ .prf-field-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:480px){ .prf-field-grid { grid-template-columns:1fr; } }
.prf-field label {
    display:block; font-size:11.5px; font-weight:700;
    color:var(--u-text); margin-bottom:6px; letter-spacing:.2px;
}
.prf-field .req { color:#dc2626; margin-left:2px; }
.prf-field input, .prf-field select, .prf-field textarea {
    width:100%; padding:10px 12px;
    border:1.5px solid var(--u-line); border-radius:9px;
    font-size:13.5px; color:var(--u-text); background:var(--u-card);
    transition:border-color .15s, box-shadow .15s;
    box-sizing:border-box; font-family:inherit;
}
.prf-field input:focus, .prf-field select:focus, .prf-field textarea:focus {
    outline:none; border-color:var(--u-brand);
    box-shadow:0 0 0 3px color-mix(in srgb, var(--u-brand,#2563eb) 15%, transparent);
}
.prf-field input:hover, .prf-field select:hover { border-color:color-mix(in srgb, var(--u-brand,#2563eb) 30%, var(--u-line)); }
.prf-field.span2 { grid-column:span 2; }
@media(max-width:480px){ .prf-field.span2 { grid-column:span 1; } }

/* Save bar */
.prf-save-bar {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:14px; padding:14px 18px; margin-bottom:16px;
    display:flex; align-items:center; gap:14px; justify-content:space-between;
    flex-wrap:wrap;
}
.prf-save-hint { font-size:12px; color:var(--u-muted); display:flex; align-items:center; gap:6px; }
.prf-save-btn {
    padding:10px 24px; border:none; border-radius:22px;
    background:var(--u-brand,#2563eb); color:#fff;
    font-size:13.5px; font-weight:700; cursor:pointer;
    box-shadow:0 4px 14px color-mix(in srgb, var(--u-brand,#2563eb) 35%, transparent);
    transition:transform .15s, box-shadow .15s;
    display:inline-flex; align-items:center; gap:6px;
}
.prf-save-btn:hover { transform:translateY(-1px); box-shadow:0 6px 18px color-mix(in srgb, var(--u-brand,#2563eb) 45%, transparent); }

/* Alerts */
.prf-alert {
    border-radius:10px; padding:12px 16px; margin-bottom:16px;
    display:flex; align-items:flex-start; gap:10px;
    font-size:13px; line-height:1.45;
}
.prf-alert.ok { background:color-mix(in srgb, #16a34a 10%, var(--u-card)); border:1px solid color-mix(in srgb, #16a34a 25%, transparent); color:#166534; }
.prf-alert.err { background:color-mix(in srgb, #dc2626 8%, var(--u-card)); border:1px solid color-mix(in srgb, #dc2626 25%, transparent); color:#991b1b; }
.prf-alert-icon {
    width:22px; height:22px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:800; flex-shrink:0;
}
.prf-alert.ok .prf-alert-icon { background:#16a34a; color:#fff; }
.prf-alert.err .prf-alert-icon { background:#dc2626; color:#fff; }

/* Status cards grid */
.prf-status-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
@media (max-width:700px){ .prf-status-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:480px){ .prf-status-grid { grid-template-columns:1fr; } }
.prf-stat-card {
    background:color-mix(in srgb, var(--u-brand,#2563eb) 3%, var(--u-bg));
    border:1px solid var(--u-line);
    border-radius:10px; padding:12px 14px;
}
.prf-stat-label {
    font-size:10.5px; font-weight:700; color:var(--u-muted);
    letter-spacing:.4px; text-transform:uppercase; margin-bottom:4px;
}
.prf-stat-value { font-size:13px; font-weight:700; color:var(--u-text); line-height:1.35; }

/* Snapshot history */
.prf-snapshot-list { display:flex; flex-direction:column; gap:8px; }
.prf-snapshot {
    display:flex; align-items:center; gap:12px;
    padding:12px 14px; border-radius:10px;
    background:color-mix(in srgb, var(--u-brand,#2563eb) 3%, var(--u-bg));
    border:1px solid var(--u-line);
    transition:border-color .15s;
}
.prf-snapshot:hover { border-color:color-mix(in srgb, var(--u-brand,#2563eb) 30%, var(--u-line)); }
.prf-snapshot-ver {
    font-size:11px; font-weight:800; padding:3px 9px; border-radius:6px;
    background:var(--u-brand,#2563eb); color:#fff; flex-shrink:0;
}
.prf-snapshot-info { flex:1; min-width:0; }
.prf-snapshot-meta { font-size:11.5px; color:var(--u-muted); line-height:1.35; }
.prf-snapshot-time { font-size:12.5px; font-weight:600; color:var(--u-text); margin-bottom:2px; }

/* Minimalist */
.jm-minimalist .prf-hero { background:#e2e5ec !important; color:var(--u-text,#1a1a1a) !important; border:1px solid rgba(0,0,0,.1) !important; }
.jm-minimalist .prf-hero::before { display:none !important; }
.jm-minimalist .prf-hero * { color:inherit !important; opacity:1 !important; }
.jm-minimalist .prf-field input:focus, .jm-minimalist .prf-field select:focus, .jm-minimalist .prf-field textarea:focus { box-shadow:none; }
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

{{-- ══════ Hero ══════ --}}
<div class="prf-hero">
    <div class="prf-hero-body">
        <div class="prf-avatar-wrap">
            <div class="prf-avatar">
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

        <div class="prf-hero-main">
            <div class="prf-hero-label"><span class="prf-hero-marker"></span>Öğrenci Profili</div>
            <div class="prf-hero-name">{{ $displayName }}</div>
            <div class="prf-hero-email">{{ $user?->email ?? '-' }}</div>
            <div class="prf-hero-badges">
                @if($studentId || $user?->student_id)
                    <span class="prf-hero-badge">🆔 {{ $studentId ?: $user?->student_id }}</span>
                @endif
                @if($guest?->target_city)<span class="prf-hero-badge">📍 {{ $guest->target_city }}</span>@endif
                @if($guest?->target_term)<span class="prf-hero-badge">📅 {{ $guest->target_term }}</span>@endif
            </div>
        </div>

        <div class="prf-hero-stats">
            <div class="prf-hstat">
                <div class="prf-hstat-val">%{{ $profileCompletion }}</div>
                <div class="prf-hstat-label">Süreç</div>
            </div>
            <div class="prf-hstat">
                <div class="prf-hstat-val">{{ $reqDone }}/{{ $reqTotal }}</div>
                <div class="prf-hstat-label">Belgeler</div>
            </div>
            <div class="prf-hstat">
                <div class="prf-hstat-val">{{ (int)($notificationCount ?? 0) }}</div>
                <div class="prf-hstat-label">Bildirim</div>
            </div>
        </div>
    </div>
</div>

{{-- ══════ Quick Info Cards ══════ --}}
<div class="prf-info-grid">
    <div class="prf-info-card">
        <div class="prf-info-label">Telefon</div>
        <div class="prf-info-value">{{ $guest?->phone ?: '—' }}</div>
    </div>
    <div class="prf-info-card">
        <div class="prf-info-label">Dil Seviyesi</div>
        <div class="prf-info-value">{{ $guest?->language_level ?: '—' }}</div>
    </div>
    <div class="prf-info-card">
        <div class="prf-info-label">Zorunlu Belgeler</div>
        <div class="prf-info-value">%{{ $docsProgress }}</div>
        <div class="prf-progress">
            <div class="prf-progress-fill {{ $docsProgress >= 100 ? 'ok' : '' }}" style="width:{{ $docsProgress }}%"></div>
        </div>
    </div>
    <div class="prf-info-card">
        <div class="prf-info-label">Atanan Danışman</div>
        <div class="prf-info-value" style="font-size:12px;">{{ $assignment?->senior_email ?: '—' }}</div>
    </div>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="prf-alert ok">
    <div class="prf-alert-icon">✓</div>
    <div>{{ session('success') }}</div>
</div>
@endif
@if($errors->any())
<div class="prf-alert err">
    <div class="prf-alert-icon">!</div>
    <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
</div>
@endif

{{-- ══════ Form ══════ --}}
<form method="POST" action="{{ route('student.profile.update') }}">
    @csrf

    <div class="prf-section">
        <div class="prf-section-head">
            <div class="prf-section-icon">👤</div>
            <div>
                <div class="prf-section-title">Kişisel Bilgiler</div>
                <div class="prf-section-sub">İsim, telefon ve iletişim</div>
            </div>
        </div>
        <div class="prf-field-grid">
            <div class="prf-field">
                <label>Ad <span class="req">*</span></label>
                <input name="first_name" value="{{ old('first_name', $guest?->first_name ?? '') }}" required>
            </div>
            <div class="prf-field">
                <label>Soyad <span class="req">*</span></label>
                <input name="last_name" value="{{ old('last_name', $guest?->last_name ?? '') }}" required>
            </div>
            <div class="prf-field">
                <label>Telefon</label>
                <input name="phone" value="{{ old('phone', $guest?->phone ?? '') }}" placeholder="+49 123 456 7890">
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

    <div class="prf-section">
        <div class="prf-section-head">
            <div class="prf-section-icon">🎯</div>
            <div>
                <div class="prf-section-title">Başvuru Tercihleri</div>
                <div class="prf-section-sub">Hedef ülke, şehir, dönem, dil seviyesi</div>
            </div>
        </div>
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
                <input name="target_city" value="{{ old('target_city', $guest?->target_city ?? '') }}" placeholder="ör. Berlin">
            </div>
            <div class="prf-field">
                <label>Hedef Dönem</label>
                <input name="target_term" value="{{ old('target_term', $guest?->target_term ?? '') }}" placeholder="ör. 2025 Kış">
            </div>
            <div class="prf-field">
                <label>Dil Seviyesi</label>
                <input name="language_level" value="{{ old('language_level', $guest?->language_level ?? '') }}" placeholder="ör. B2">
            </div>
            <div class="prf-field span2">
                <label>Ek Not</label>
                <textarea name="notes" style="min-height:80px;resize:vertical;">{{ old('notes', $guest?->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="prf-save-bar">
        <div class="prf-save-hint">💾 Değişiklikleri kaydetmek için "Profili Kaydet" butonuna bas.</div>
        <button class="prf-save-btn" type="submit">Profili Kaydet <span>→</span></button>
    </div>
</form>

{{-- ══════ Süreç Durumu ══════ --}}
<div class="prf-section">
    <div class="prf-section-head">
        <div class="prf-section-icon">📊</div>
        <div>
            <div class="prf-section-title">Süreç Durumu</div>
            <div class="prf-section-sub">Başvuru, sözleşme ve belge özetin</div>
        </div>
    </div>
    <div class="prf-status-grid">
        <div class="prf-stat-card">
            <div class="prf-stat-label">Başvuru Tipi</div>
            <div class="prf-stat-value">{{ $guest?->application_type ?: '—' }}</div>
        </div>
        <div class="prf-stat-card">
            <div class="prf-stat-label">Sözleşme Durumu</div>
            <div class="prf-stat-value">{{ $guest?->contract_status ?: 'not_requested' }}</div>
        </div>
        <div class="prf-stat-card">
            <div class="prf-stat-label">Seçili Paket</div>
            <div class="prf-stat-value">{{ $guest?->selected_package_title ?: '—' }}</div>
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
            <div class="prf-stat-value">{{ $guest?->status_message ?: '—' }}</div>
        </div>
        <div class="prf-stat-card">
            <div class="prf-stat-label">Bildirim Sayısı</div>
            <div class="prf-stat-value">{{ (int)($notificationCount ?? 0) }} kayıt</div>
        </div>
    </div>
</div>

{{-- ══════ Başvuru Geçmişi ══════ --}}
@if(!($registrationSnapshots ?? collect())->isEmpty())
<div class="prf-section">
    <div class="prf-section-head">
        <div class="prf-section-icon">📜</div>
        <div>
            <div class="prf-section-title">Başvuru Gönderim Geçmişi</div>
            <div class="prf-section-sub">{{ $registrationSnapshots->count() }} kayıt bulundu</div>
        </div>
    </div>
    <div class="prf-snapshot-list">
        @foreach($registrationSnapshots as $snap)
            @php $warnings = (int) data_get($snap->meta_json, 'warnings_count', 0); @endphp
            <div class="prf-snapshot">
                <span class="prf-snapshot-ver">v{{ $snap->snapshot_version }}</span>
                <div class="prf-snapshot-info">
                    <div class="prf-snapshot-time">{{ $snap->submitted_at }}</div>
                    <div class="prf-snapshot-meta">{{ $snap->submitted_by_email ?: '—' }}</div>
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
    var fill = document.querySelector('.prf-progress-fill');
    if (!fill) return;
    var target = fill.style.width;
    fill.style.width = '0';
    setTimeout(function(){ fill.style.width = target; }, 100);
})();
</script>
@endpush

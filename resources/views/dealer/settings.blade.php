@extends('dealer.layouts.app')

@section('title', 'Ayarlar')
@section('page_title', 'Ayarlar')
@section('page_subtitle', 'Dil, bildirim, şifre ve gizlilik')

@push('head')
<style>
/* Section title inside panel */
.set-section-title {
    font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    color:var(--muted,#64748b); margin:0 0 16px; padding-bottom:10px;
    border-bottom:1px solid var(--border,#e2e8f0);
    display:flex; align-items:center; gap:7px;
}

/* Status strip */
.set-stat-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:20px; }
@media(max-width:700px){ .set-stat-strip { grid-template-columns:1fr 1fr; } }
.set-stat {
    background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0);
    border-top:3px solid var(--border,#e2e8f0);
    border-radius:12px; padding:14px 16px;
}
.set-stat.c-lang   { border-top-color:#6366f1; }
.set-stat.c-email  { border-top-color:#16a34a; }
.set-stat.c-wa     { border-top-color:#25d366; }
.set-stat.c-inapp  { border-top-color:#0891b2; }
.set-stat-val   { font-size:22px; font-weight:900; color:var(--text,#0f172a); line-height:1; margin-bottom:4px; }
.set-stat-label { font-size:10px; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }
.set-stat-sub   { font-size:11px; margin-top:3px; font-weight:600; }

/* Fields */
.set-field { margin-bottom:14px; }
.set-field:last-child { margin-bottom:0; }
.set-field label {
    display:block; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em;
    color:var(--muted,#64748b); margin-bottom:6px;
}
.set-field input, .set-field select {
    width:100%; box-sizing:border-box;
    border:1.5px solid var(--border,#e2e8f0); border-radius:8px;
    padding:10px 12px; font-size:13px; color:var(--text,#0f172a);
    background:var(--surface,#fff); transition:border-color .15s;
}
.set-field input:focus, .set-field select:focus {
    outline:none; border-color:var(--c-accent,#16a34a);
    box-shadow:0 0 0 3px rgba(22,163,74,.12);
}
.set-field .set-hint { font-size:11px; color:var(--muted,#64748b); margin-top:4px; }
.set-field .set-err  { font-size:12px; color:var(--c-danger,#dc2626); margin-top:4px; }

/* Toggle rows */
.set-toggle { display:flex; flex-direction:column; gap:2px; }
.set-toggle-row {
    display:flex; align-items:flex-start; gap:12px;
    padding:11px 14px; border-radius:10px; cursor:pointer;
    transition:background .12s;
}
.set-toggle-row:hover { background:var(--bg,#f1f5f9); }
.set-toggle-row input[type=checkbox] { width:16px; height:16px; accent-color:var(--c-accent,#16a34a); flex-shrink:0; margin-top:2px; }
.set-toggle-title { font-size:13px; font-weight:700; color:var(--text,#0f172a); margin-bottom:2px; }
.set-toggle-desc  { font-size:12px; color:var(--muted,#64748b); }

/* KVKK row */
.set-kvkk {
    display:flex; align-items:center; justify-content:space-between;
    gap:16px; flex-wrap:wrap;
}
.set-kvkk-text { font-size:13px; color:var(--muted,#64748b); flex:1; min-width:200px; }
.set-kvkk-text strong { color:var(--text,#0f172a); }
</style>
@endpush

@section('content')
@php
    $s       = $prefs ?? [];
    $emailOn = (bool)($s['notify_email']??true);
    $waOn    = (bool)($s['notify_whatsapp']??false);
    $inappOn = (bool)($s['notify_inapp']??true);
@endphp

{{-- Anlık Durum --}}
<div class="set-stat-strip">
    <div class="set-stat c-lang">
        <div class="set-stat-label">Arayüz Dili</div>
        <div class="set-stat-val">{{ strtoupper((string)($s['preferred_locale'] ?? 'TR')) }}</div>
        <div class="set-stat-sub" style="color:#6366f1;">
            {{ match($s['preferred_locale']??'tr') { 'de'=>'Deutsch', 'en'=>'English', default=>'Türkçe' } }}
        </div>
    </div>
    <div class="set-stat c-email">
        <div class="set-stat-label">E-posta</div>
        <div class="set-stat-val" style="color:{{ $emailOn ? '#16a34a' : '#94a3b8' }}">
            {{ $emailOn ? '✓' : '✗' }}
        </div>
        <div class="set-stat-sub" style="color:{{ $emailOn ? '#15803d' : 'var(--muted,#94a3b8)' }}">
            {{ $emailOn ? 'Aktif' : 'Kapalı' }}
        </div>
    </div>
    <div class="set-stat c-wa">
        <div class="set-stat-label">WhatsApp</div>
        <div class="set-stat-val" style="color:{{ $waOn ? '#25d366' : '#94a3b8' }}">
            {{ $waOn ? '✓' : '✗' }}
        </div>
        <div class="set-stat-sub" style="color:{{ $waOn ? '#16a34a' : 'var(--muted,#94a3b8)' }}">
            {{ $waOn ? 'Aktif' : 'Kapalı' }}
        </div>
    </div>
    <div class="set-stat c-inapp">
        <div class="set-stat-label">Portal İçi</div>
        <div class="set-stat-val" style="color:{{ $inappOn ? '#0891b2' : '#94a3b8' }}">
            {{ $inappOn ? '✓' : '✗' }}
        </div>
        <div class="set-stat-sub" style="color:{{ $inappOn ? '#0e7490' : 'var(--muted,#94a3b8)' }}">
            {{ $inappOn ? 'Aktif' : 'Kapalı' }}
        </div>
    </div>
</div>

{{-- Dil + Bildirimler --}}
<div class="panel" style="margin-bottom:14px;">
    <div class="set-section-title">⚙️ Portal Tercihleri</div>
    <form method="POST" action="{{ route('dealer.settings.update') }}">
        @csrf
        <div class="grid2" style="margin-bottom:16px;">
            <div class="set-field" style="margin-bottom:0;">
                <label>Arayüz Dili</label>
                <select name="preferred_locale">
                    <option value="tr" @selected(($s['preferred_locale']??'tr')==='tr')>🇹🇷 Türkçe</option>
                    <option value="de" @selected(($s['preferred_locale']??'')==='de')>🇩🇪 Deutsch</option>
                    <option value="en" @selected(($s['preferred_locale']??'')==='en')>🇬🇧 English</option>
                </select>
                <div class="set-hint">Arayüz dilini belirler (MVP: kısmi etki).</div>
            </div>
            <div>
                <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:8px;">Bildirim Kanalları</div>
                <div class="set-toggle">
                    <label class="set-toggle-row">
                        <input type="checkbox" name="notify_email" value="1" @checked((bool)($s['notify_email']??true))>
                        <div>
                            <div class="set-toggle-title">✉️ E-posta</div>
                            <div class="set-toggle-desc">Lead, ticket ve operasyon bilgilendirmeleri.</div>
                        </div>
                    </label>
                    <label class="set-toggle-row">
                        <input type="checkbox" name="notify_whatsapp" value="1" @checked((bool)($s['notify_whatsapp']??false))>
                        <div>
                            <div class="set-toggle-title">💬 WhatsApp</div>
                            <div class="set-toggle-desc">Kısa operasyon uyarı mesajları.</div>
                        </div>
                    </label>
                    <label class="set-toggle-row">
                        <input type="checkbox" name="notify_inapp" value="1" @checked((bool)($s['notify_inapp']??true))>
                        <div>
                            <div class="set-toggle-title">🔔 Portal İçi</div>
                            <div class="set-toggle-desc">Bildirimlerim ekranında görünür.</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-primary" type="submit">Tercihleri Kaydet</button>
            <a class="btn alt" href="/dealer/profile">Profile Dön</a>
        </div>
    </form>
</div>

{{-- Şifre --}}
<div class="panel" style="margin-bottom:14px;">
    <div class="set-section-title">🔐 Şifre Değiştir</div>
    <form method="POST" action="{{ route('dealer.settings.password') }}">
        @csrf
        <div class="grid2" style="margin-bottom:14px;">
            <div class="set-field" style="margin-bottom:0;">
                <label>Mevcut Şifre *</label>
                <input type="password" name="current_password" required autocomplete="current-password" placeholder="••••••••">
                @error('current_password')<div class="set-err">{{ $message }}</div>@enderror
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div class="set-field" style="margin-bottom:0;">
                    <label>Yeni Şifre *</label>
                    <input type="password" name="new_password" required autocomplete="new-password" minlength="8" placeholder="En az 8 karakter">
                    @error('new_password')<div class="set-err">{{ $message }}</div>@enderror
                </div>
                <div class="set-field" style="margin-bottom:0;">
                    <label>Yeni Şifre (Tekrar) *</label>
                    <input type="password" name="new_password_confirmation" required autocomplete="new-password" placeholder="••••••••">
                </div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Şifreyi Güncelle</button>
    </form>
</div>

{{-- KVKK --}}
<div class="panel">
    <div class="set-section-title">🔒 Veri & Gizlilik (KVKK)</div>
    <div class="set-kvkk">
        <div class="set-kvkk-text">
            KVKK kapsamında hesabınıza ait <strong>kişisel veriler ve yönlendirme kayıtları</strong>nı JSON formatında indirebilirsiniz.
        </div>
        <a class="btn btn-primary" href="{{ route('dealer.settings.data-export') }}">⬇ Verilerimi İndir (JSON)</a>
    </div>
</div>

@include('dealer._partials.usage-guide', [
    'items' => [
        'Bildirim tercihleri e-posta / WhatsApp / portal içi kanalların yönetimini sağlar.',
        'Şifre değiştirme için mevcut şifreni doğru girmen gerekmektedir.',
        'KVKK JSON indirimi tüm kişisel veri ve yönlendirme kayıtlarını içerir.',
    ]
])

@endsection

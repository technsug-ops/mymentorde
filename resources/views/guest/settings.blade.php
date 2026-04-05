@extends('guest.layouts.app')

@section('title', 'Hesap Ayarları')
@section('page_title', 'Hesap Ayarları')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ── gst-* Guest Settings scoped ── */
.gst-hero {
    background: linear-gradient(to right, var(--theme-hero-from-guest) 0%, var(--theme-hero-to-guest) 100%);
    border-radius: 14px;
    padding: 22px 24px;
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    margin-bottom: 20px; position: relative; overflow: hidden;
}
.gst-hero::before {
    content: ''; position: absolute; top: -30px; right: -30px;
    width: 160px; height: 160px; border-radius: 50%;
    background: rgba(255,255,255,.05); pointer-events: none;
}
.gst-hero-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0; position: relative; z-index: 1;
}
.gst-hero-info { flex: 1; min-width: 180px; position: relative; z-index: 1; }
.gst-hero-title { font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 4px; }
.gst-hero-sub { font-size: 13px; color: rgba(255,255,255,.7); }
.gst-hero-meta { position: relative; z-index: 1; display: flex; gap: 8px; flex-wrap: wrap; }
.gst-hero-pill {
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22);
    border-radius: 999px; padding: 4px 14px;
    font-size: 12px; color: #fff; font-weight: 600;
}

/* Settings sections */
.gst-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; align-items: stretch; }
.gst-row2 > .gst-section { display: flex; flex-direction: column; }
.gst-row2 > .gst-section > .gst-section-body { flex: 1; }
@media(max-width:720px){ .gst-row2 { grid-template-columns: 1fr; } }
.gst-section { background: var(--u-card); border: 1px solid var(--u-line); border-radius: 14px; margin-bottom: 14px; overflow: hidden; }
.gst-section-hdr { padding: 14px 20px; border-bottom: 1px solid var(--u-line); display: flex; align-items: center; gap: 12px; }
.gst-section-icon { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
.gst-section-title { font-size: 14px; font-weight: 700; color: var(--u-text); margin: 0; }
.gst-section-sub   { font-size: 12px; color: var(--u-muted); margin-top: 1px; }
.gst-section-body  { padding: 18px 20px; }

/* Fields */
.gst-field { margin-bottom: 14px; }
.gst-field:last-child { margin-bottom: 0; }
.gst-field label { display: block; font-size: 12px; font-weight: 600; color: var(--u-text); margin-bottom: 6px; }
.gst-field select,
.gst-field input[type="password"] {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid var(--u-line); border-radius: 8px;
    font-size: 14px; color: var(--u-text); background: var(--u-card);
    font-family: inherit; box-sizing: border-box; transition: border-color .15s;
}
.gst-field select:focus,
.gst-field input:focus { outline: none; border-color: var(--u-brand); }
.gst-pw-wrap { position: relative; }
.gst-pw-wrap input { padding-right: 44px; }
.gst-pw-eye {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: var(--u-muted); font-size: 16px; padding: 0; line-height: 1;
}
.gst-pw-eye:hover { color: var(--u-text); }

/* Notification rows */
.gst-notif-row {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 11px 14px; border-radius: 10px;
    border: 1px solid var(--u-line); background: var(--u-bg);
    cursor: pointer; margin-bottom: 8px; transition: border-color .12s;
}
.gst-notif-row:last-child { margin-bottom: 0; }
.gst-notif-row:hover { border-color: var(--u-brand); }
.gst-notif-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--u-brand); flex-shrink: 0; margin-top: 2px; }
.gst-notif-icon { font-size: 18px; flex-shrink: 0; line-height: 1; margin-top: 1px; }
.gst-notif-label { font-size: 13px; font-weight: 600; color: var(--u-text); }
.gst-notif-sub   { font-size: 11px; color: var(--u-muted); margin-top: 2px; line-height: 1.4; }

/* Design theme cards */
.gst-theme-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.gst-theme-card {
    border: 2px solid var(--u-line); border-radius: 10px; padding: 10px;
    cursor: pointer; transition: border-color .15s; position: relative; overflow: hidden;
}
.gst-theme-card:hover { border-color: var(--u-brand); }
.gst-theme-card.active { border-color: var(--u-brand); background: rgba(37,99,235,.04); }
.gst-theme-card.active::after {
    content: '✓'; position: absolute; top: 10px; right: 12px;
    width: 20px; height: 20px; border-radius: 50%;
    background: var(--u-brand); color: #fff;
    font-size: 11px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
}
.gst-theme-preview {
    height: 36px; border-radius: 6px; margin-bottom: 8px;
    border: 1px solid var(--u-line); overflow: hidden; display: flex;
}
.gst-theme-preview-sidebar { width: 28%; background: #f8fafc; border-right: 1px solid #e2e8f0; }
.gst-theme-preview-main    { flex: 1; padding: 4px; display: flex; flex-direction: column; gap: 3px; }
.gst-theme-preview-bar     { height: 6px; border-radius: 3px; }
.gst-theme-preview.premium .gst-theme-preview-sidebar { background: linear-gradient(180deg,#1e3a5f,#2563eb); }
.gst-theme-preview.premium .gst-theme-preview-bar:nth-child(1) { background: linear-gradient(90deg,#6366f1,#8b5cf6); width: 70%; }
.gst-theme-preview.premium .gst-theme-preview-bar:nth-child(2) { background: linear-gradient(90deg,#3b82f6,#60a5fa); width: 50%; }
.gst-theme-preview.premium .gst-theme-preview-bar:nth-child(3) { background: #e2e8f0; width: 80%; }
.gst-theme-preview.minimalist .gst-theme-preview-sidebar { background: #fff; border-right: 1px solid #e5e7eb; }
.gst-theme-preview.minimalist .gst-theme-preview-bar:nth-child(1) { background: #1a1a1a; width: 70%; }
.gst-theme-preview.minimalist .gst-theme-preview-bar:nth-child(2) { background: #888; width: 50%; }
.gst-theme-preview.minimalist .gst-theme-preview-bar:nth-child(3) { background: #f0f0f0; width: 80%; }
.gst-theme-name { font-size: 12px; font-weight: 700; color: var(--u-text); }
.gst-theme-desc { font-size: 10px; color: var(--u-muted); margin-top: 1px; }

/* Dark mode row */
.gst-toggle-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 14px; border-radius: 10px;
    border: 1px solid var(--u-line); background: var(--u-bg);
}
.gst-toggle-switch {
    width: 42px; height: 24px; border-radius: 12px;
    background: var(--u-line); position: relative;
    cursor: pointer; transition: background .2s; flex-shrink: 0;
    border: none; outline: none; padding: 0;
}
.gst-toggle-switch.on { background: var(--u-brand); }
.gst-toggle-knob {
    width: 18px; height: 18px; border-radius: 50%; background: #fff;
    position: absolute; top: 3px; left: 3px;
    transition: left .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.gst-toggle-switch.on .gst-toggle-knob { left: 21px; }

/* Account info grid */
.gst-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.gst-info-tile {
    padding: 10px 14px; border-radius: 9px;
    background: var(--u-bg); border: 1px solid var(--u-line);
}
.gst-info-tile-label { font-size: 10px; color: var(--u-muted); text-transform: uppercase; letter-spacing: .04em; font-weight: 600; margin-bottom: 3px; }
.gst-info-tile-val   { font-size: 13px; font-weight: 700; color: var(--u-text); }

/* Privacy actions */
.gst-privacy-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 0; border-bottom: 1px solid var(--u-line);
}
.gst-privacy-row:last-child { border-bottom: none; padding-bottom: 0; }
.gst-privacy-label { font-size: 13px; font-weight: 600; color: var(--u-text); }
.gst-privacy-sub   { font-size: 11px; color: var(--u-muted); margin-top: 2px; }

/* Security note */
.gst-security-note {
    border: 1px solid #fde68a; background: #fffbeb;
    border-radius: 10px; padding: 10px 14px; margin-top: 14px;
    font-size: 12px; color: #92400e; display: flex; gap: 8px; align-items: flex-start;
}

/* Password strength */
.gst-pw-strength { height: 3px; border-radius: 2px; background: var(--u-line); margin-top: 6px; overflow: hidden; }
.gst-pw-strength-fill { height: 100%; border-radius: 2px; transition: width .3s, background .3s; width: 0%; }

/* ════════════ MINIMALİST OVERRIDES ════════════ */






.jm-minimalist .gst-field select:focus,
.jm-minimalist .gst-field input:focus { box-shadow: none; }
.jm-minimalist .gst-theme-card.active { background: rgba(0,0,0,.03); }
.jm-minimalist .gst-theme-card.active::after { background: var(--u-text, #111); }
</style>
@endpush

@section('content')
@php
    $pref  = old('preferred_locale', $guest?->preferred_locale ?? $guest?->communication_language ?? 'tr');
    $notif = (bool)old('notifications_enabled', $guest?->notifications_enabled ?? true);
    $createdAt = $user?->created_at ? \Carbon\Carbon::parse($user->created_at)->format('d.m.Y') : '-';
    $lastLogin = $user?->updated_at ? \Carbon\Carbon::parse($user->updated_at)->diffForHumans() : '-';
@endphp

{{-- ── Hero ── --}}
<div class="gst-hero">
    <div class="gst-hero-icon">⚙️</div>
    <div class="gst-hero-info">
        <div class="gst-hero-title">Hesap Ayarları</div>
        <div class="gst-hero-sub">Görünüm, bildirimler ve güvenlik tercihleri</div>
    </div>
    <div class="gst-hero-meta">
        <div class="gst-hero-pill">{{ $user?->email }}</div>
        <div class="gst-hero-pill">🗓 {{ $createdAt }}'den beri üye</div>
    </div>
</div>

{{-- Flash --}}
@if(session('success'))
    <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;color:#166534;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
        ✓ {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;color:#991b1b;margin-bottom:16px;">
        @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
    </div>
@endif

{{-- ════ PORTAL GÖRÜNÜMÜ ════ --}}
<div class="gst-section">
    <div class="gst-section-hdr">
        <div class="gst-section-icon" style="background:#f5f3ff;">🎨</div>
        <div>
            <div class="gst-section-title">Portal Görünümü</div>
            <div class="gst-section-sub">Tasarım teması ve renk modu tercihleri</div>
        </div>
    </div>
    <div class="gst-section-body">
        {{-- Design theme --}}
        <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-text);margin-bottom:10px;">Tasarım Teması</div>
        <div class="gst-theme-grid" style="margin-bottom:18px;">
            <div class="gst-theme-card" id="themeCardPremium" onclick="gstSetTheme('premium')">
                <div class="gst-theme-preview premium">
                    <div class="gst-theme-preview-sidebar"></div>
                    <div class="gst-theme-preview-main">
                        <div class="gst-theme-preview-bar"></div>
                        <div class="gst-theme-preview-bar"></div>
                        <div class="gst-theme-preview-bar"></div>
                    </div>
                </div>
                <div class="gst-theme-name">Premium</div>
                <div class="gst-theme-desc">Renkli gradyanlar, modern görünüm</div>
            </div>
            <div class="gst-theme-card" id="themeCardMinimalist" onclick="gstSetTheme('minimalist')">
                <div class="gst-theme-preview minimalist">
                    <div class="gst-theme-preview-sidebar"></div>
                    <div class="gst-theme-preview-main">
                        <div class="gst-theme-preview-bar"></div>
                        <div class="gst-theme-preview-bar"></div>
                        <div class="gst-theme-preview-bar"></div>
                    </div>
                </div>
                <div class="gst-theme-name">Minimalist</div>
                <div class="gst-theme-desc">Sade, siyah-beyaz, keskin çizgiler</div>
            </div>
        </div>

        {{-- Dark mode --}}
        <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-text);margin-bottom:10px;">Karanlık Mod</div>
        <div class="gst-toggle-row">
            <div>
                <div style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">🌙 Karanlık Mod</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">Gece kullanımı için koyu arka plan</div>
            </div>
            <button class="gst-toggle-switch" id="darkModeToggle" onclick="gstToggleDark()" type="button">
                <div class="gst-toggle-knob"></div>
            </button>
        </div>
    </div>
</div>

{{-- ════ HESAP TERCİHLERİ + BİLDİRİM TERCİHLERİ — yan yana ════ --}}
<div class="gst-row2">

{{-- Hesap Tercihleri --}}
<div class="gst-section" style="margin-bottom:0;">
    <div class="gst-section-hdr">
        <div class="gst-section-icon" style="background:#eff6ff;">🌐</div>
        <div>
            <div class="gst-section-title">Hesap Tercihleri</div>
            <div class="gst-section-sub">Dil ve iletişim ayarları</div>
        </div>
    </div>
    <div class="gst-section-body">
        <form method="POST" action="{{ route('guest.settings.update') }}">
            @csrf

            {{-- Account info tiles --}}
            <div class="gst-info-grid" style="margin-bottom:18px;">
                <div class="gst-info-tile">
                    <div class="gst-info-tile-label">E-posta</div>
                    <div class="gst-info-tile-val" style="font-size:var(--tx-xs);word-break:break-all;">{{ $user?->email }}</div>
                </div>
                <div class="gst-info-tile">
                    <div class="gst-info-tile-label">Rol</div>
                    <div class="gst-info-tile-val">{{ ucfirst((string)($user?->role ?? '-')) }}</div>
                </div>
                <div class="gst-info-tile">
                    <div class="gst-info-tile-label">Üyelik Tarihi</div>
                    <div class="gst-info-tile-val">{{ $createdAt }}</div>
                </div>
                <div class="gst-info-tile">
                    <div class="gst-info-tile-label">Paket</div>
                    <div class="gst-info-tile-val">{{ $guest?->selected_package_title ?: 'Seçilmedi' }}</div>
                </div>
            </div>

            <div class="gst-field">
                <label>Tercih Edilen Dil</label>
                <select name="preferred_locale">
                    <option value="tr" @selected($pref==='tr')>🇹🇷 Türkçe</option>
                    <option value="de" @selected($pref==='de')>🇩🇪 Almanca</option>
                    <option value="en" @selected($pref==='en')>🇬🇧 İngilizce</option>
                </select>
            </div>

            <button class="btn ok" type="submit" style="padding:9px 24px;">Kaydet</button>
        </form>
    </div>
</div>

{{-- Bildirim Tercihleri --}}
<div class="gst-section" style="margin-bottom:0;">
    <div class="gst-section-hdr">
        <div class="gst-section-icon" style="background:#fff7ed;">🔔</div>
        <div>
            <div class="gst-section-title">Bildirim Tercihleri</div>
            <div class="gst-section-sub">Hangi olaylarda bildirim almak istediğinizi seçin</div>
        </div>
    </div>
    <div class="gst-section-body">
        <form method="POST" action="{{ route('guest.settings.update') }}">
            @csrf
            @php
                $notifChannels = $guest?->notification_channels ?? [];
                $hasChannel = fn($ch) => in_array($ch, (array)$notifChannels) || $notif;
            @endphp

            <label class="gst-notif-row">
                <input type="checkbox" name="notifications_enabled" value="1" @checked($notif)>
                <span class="gst-notif-icon">📩</span>
                <div style="flex:1">
                    <div class="gst-notif-label">Tüm Bildirimler</div>
                    <div class="gst-notif-sub">E-posta ve sistem bildirimlerini etkinleştir / devre dışı bırak</div>
                </div>
            </label>

            @php
                $notifTypes = [
                    ['icon'=>'💬', 'label'=>'Yeni Mesaj',         'sub'=>'Danışmanınızdan yeni mesaj geldiğinde',         'key'=>'new_message'],
                    ['icon'=>'📄', 'label'=>'Belge Hatırlatıcı',  'sub'=>'Eksik veya yaklaşan belge son tarihleri',       'key'=>'document_reminder'],
                    ['icon'=>'📅', 'label'=>'Randevu Bildirimi',  'sub'=>'Randevu oluşturulduğunda veya değiştiğinde',    'key'=>'appointment'],
                    ['icon'=>'✍️', 'label'=>'Sözleşme Güncelleme','sub'=>'Sözleşme durumu değiştiğinde',                 'key'=>'contract_update'],
                ];
            @endphp
            @foreach($notifTypes as $nt)
            <label class="gst-notif-row">
                <input type="checkbox" name="notif_{{ $nt['key'] }}" value="1"
                       @checked(in_array($nt['key'], (array)($guest?->notification_channels ?? [])) || $notif)>
                <span class="gst-notif-icon">{{ $nt['icon'] }}</span>
                <div style="flex:1">
                    <div class="gst-notif-label">{{ $nt['label'] }}</div>
                    <div class="gst-notif-sub">{{ $nt['sub'] }}</div>
                </div>
            </label>
            @endforeach

            <div style="margin-top:14px;">
                <button class="btn ok" type="submit" style="padding:9px 24px;">Bildirimleri Kaydet</button>
            </div>
        </form>
    </div>
</div>

</div>{{-- /gst-row2 --}}

{{-- ════ ŞİFRE & GÜVENLİK + GİZLİLİK & VERİ — yan yana ════ --}}
<div class="gst-row2">

{{-- Şifre & Güvenlik --}}
<div class="gst-section" style="margin-bottom:0;">
    <div class="gst-section-hdr">
        <div class="gst-section-icon" style="background:#fef2f2;">🔒</div>
        <div>
            <div class="gst-section-title">Şifre & Güvenlik</div>
            <div class="gst-section-sub">Hesabınızı güvende tutmak için düzenli güncelleme önerilir</div>
        </div>
    </div>
    <div class="gst-section-body">
        <form method="POST" action="{{ route('guest.settings.password') }}">
            @csrf
            <div class="gst-field">
                <label>Mevcut Şifre</label>
                <div class="gst-pw-wrap">
                    <input type="password" name="current_password" id="pw0" placeholder="Mevcut şifreniz" required>
                    <button type="button" class="gst-pw-eye" onclick="gstTogglePw('pw0',this)">👁</button>
                </div>
            </div>
            <div class="gst-field">
                <label>Yeni Şifre</label>
                <div class="gst-pw-wrap">
                    <input type="password" name="new_password" id="pw1" placeholder="En az 8 karakter" required
                           oninput="gstPwStrength(this.value)">
                    <button type="button" class="gst-pw-eye" onclick="gstTogglePw('pw1',this)">👁</button>
                </div>
                <div class="gst-pw-strength"><div class="gst-pw-strength-fill" id="pwStrengthFill"></div></div>
                <div id="pwStrengthLabel" style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:3px;"></div>
            </div>
            <div class="gst-field">
                <label>Yeni Şifre (Tekrar)</label>
                <div class="gst-pw-wrap">
                    <input type="password" name="new_password_confirmation" id="pw2" placeholder="Şifreyi tekrar girin" required>
                    <button type="button" class="gst-pw-eye" onclick="gstTogglePw('pw2',this)">👁</button>
                </div>
            </div>
            <button class="btn" type="submit" style="padding:9px 24px;margin-bottom:10px;">Şifreyi Güncelle</button>
            <div class="gst-security-note">
                <span style="flex-shrink:0;font-size:var(--tx-base);">⚠️</span>
                <span>Şifreniz en az 8 karakter olmalı, büyük/küçük harf ve rakam içermesi önerilir.</span>
            </div>
        </form>
    </div>
</div>

{{-- Gizlilik & Veri --}}
<div class="gst-section" style="margin-bottom:0;">
    <div class="gst-section-hdr">
        <div class="gst-section-icon" style="background:#f0fdf4;">🛡️</div>
        <div>
            <div class="gst-section-title">Gizlilik & Veri</div>
            <div class="gst-section-sub">KVKK / GDPR kapsamında veri hakları</div>
        </div>
    </div>
    <div class="gst-section-body">
        <div class="gst-privacy-row">
            <div>
                <div class="gst-privacy-label">📥 Verilerimi İndir</div>
                <div class="gst-privacy-sub">Hesabınızdaki tüm kişisel veriyi JSON olarak indirin</div>
            </div>
            <a href="{{ route('guest.gdpr.export') }}" class="btn alt" style="font-size:var(--tx-xs);padding:7px 14px;flex-shrink:0;">İndir</a>
        </div>
        <div class="gst-privacy-row">
            <div>
                <div class="gst-privacy-label" style="color:var(--u-danger);">🗑️ Hesabı Sil</div>
                <div class="gst-privacy-sub">Tüm verinizi kalıcı olarak siler. Bu işlem geri alınamaz.</div>
            </div>
            <button class="btn warn" style="font-size:var(--tx-xs);padding:7px 14px;flex-shrink:0;"
                    onclick="if(confirm('Hesabınızı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.'))document.getElementById('deleteAccountForm').submit()">
                Sil
            </button>
        </div>
        <form id="deleteAccountForm" method="POST" action="{{ route('guest.gdpr.erasure') }}" style="display:none;">@csrf</form>
    </div>
</div>

</div>{{-- /gst-row2 --}}

{{-- ════ OTURUM GÜVENLİĞİ ════ --}}
<div class="gst-section">
    <div class="gst-section-hdr">
        <div class="gst-section-icon" style="background:#eff6ff;">💻</div>
        <div>
            <div class="gst-section-title">Oturum Güvenliği</div>
            <div class="gst-section-sub">Aktif cihazlar ve oturum yönetimi</div>
        </div>
    </div>
    <div class="gst-section-body">
        {{-- Current session info --}}
        <div class="gst-privacy-row">
            <div>
                <div class="gst-privacy-label">🖥️ Şu Anki Oturum</div>
                <div class="gst-privacy-sub">{{ request()->ip() }} · {{ now()->format('d.m.Y H:i') }}</div>
            </div>
            <span class="badge ok" style="flex-shrink:0;font-size:var(--tx-xs);">Aktif</span>
        </div>
        {{-- Last login --}}
        <div class="gst-privacy-row">
            <div>
                <div class="gst-privacy-label">🕐 Son Giriş</div>
                <div class="gst-privacy-sub">{{ $lastLogin }}</div>
            </div>
        </div>
        {{-- Logout all devices --}}
        <div class="gst-privacy-row" style="align-items:flex-start;flex-wrap:wrap;gap:10px;">
            <div style="flex:1;min-width:200px;">
                <div class="gst-privacy-label">🚪 Diğer Oturumları Kapat</div>
                <div class="gst-privacy-sub">Tüm diğer cihaz/tarayıcılardaki oturumları sonlandırır. Şifreniz gereklidir.</div>
            </div>
            <form method="POST" action="{{ route('guest.settings.logout-all') }}" style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
                @csrf
                <input type="password" name="password" placeholder="Şifreniz" required
                       style="width:150px;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:var(--tx-xs);font-family:inherit;color:var(--u-text);background:var(--u-card);">
                <button class="btn warn" type="submit" style="font-size:var(--tx-xs);padding:7px 16px;flex-shrink:0;">Kapat</button>
            </form>
        </div>
        @error('password')
            <div style="font-size:var(--tx-xs);color:var(--u-danger,#dc2626);margin-top:6px;">⚠ {{ $message }}</div>
        @enderror
    </div>
</div>

@endsection

@push('scripts')
<script>
/* ── Theme switcher ── */
function gstSetTheme(mode) {
    if (window.__designToggle) {
        var cur = localStorage.getItem('mentorde_design') || 'premium';
        if (cur !== mode) window.__designToggle();
    }
    gstSyncThemeCards();
}
function gstSyncThemeCards() {
    var cur = localStorage.getItem('mentorde_design') || 'premium';
    document.getElementById('themeCardPremium')?.classList.toggle('active', cur === 'premium');
    document.getElementById('themeCardMinimalist')?.classList.toggle('active', cur === 'minimalist');
}

/* ── Dark mode toggle ── */
function gstToggleDark() {
    if (window.__dmToggle) window.__dmToggle();
    gstSyncDarkToggle();
}
function gstSyncDarkToggle() {
    var isDark = localStorage.getItem('mentorde_dark') === 'true';
    var btn = document.getElementById('darkModeToggle');
    if (btn) btn.classList.toggle('on', isDark);
}

/* ── Password visibility ── */
function gstTogglePw(id, btn) {
    var input = document.getElementById(id);
    if (!input) return;
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? '🙈' : '👁';
}

/* ── Password strength ── */
function gstPwStrength(val) {
    var fill = document.getElementById('pwStrengthFill');
    var label = document.getElementById('pwStrengthLabel');
    if (!fill || !label) return;
    var score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    var map = [
        {w:'0%',   c:'transparent', t:''},
        {w:'25%',  c:'#ef4444',     t:'Çok zayıf'},
        {w:'45%',  c:'#f97316',     t:'Zayıf'},
        {w:'65%',  c:'#eab308',     t:'Orta'},
        {w:'85%',  c:'#22c55e',     t:'Güçlü'},
        {w:'100%', c:'#16a34a',     t:'Çok güçlü'},
    ];
    var s = map[Math.min(score, 5)];
    fill.style.width = s.w;
    fill.style.background = s.c;
    label.textContent = s.t;
    label.style.color = s.c;
}

/* ── Design toggle sync ── */
(function(){
    gstSyncThemeCards();
    gstSyncDarkToggle();
    var _orig = window.__designToggle;
    window.__designToggle = function(){
        if (_orig) _orig.apply(this, arguments);
        setTimeout(function(){
            document.documentElement.classList.toggle('jm-minimalist', localStorage.getItem('mentorde_design') === 'minimalist');
            gstSyncThemeCards();
        }, 50);
    };
    var _origDm = window.__dmToggle;
    window.__dmToggle = function(){
        if (_origDm) _origDm.apply(this, arguments);
        setTimeout(gstSyncDarkToggle, 50);
    };
})();
</script>
@endpush

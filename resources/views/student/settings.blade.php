@extends('student.layouts.app')
@section('title', 'Ayarlar')
@section('page_title', 'Ayarlar')

@push('head')
<style>
/* ── set-* Settings ── */
.set-header {
    display: flex; align-items: center; gap: 14px;
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    border-radius: 14px; padding: 14px 18px; margin-bottom: 20px; color: #fff;
}
.set-header-icon  { font-size: 24px; }
.set-header-title { font-size: 16px; font-weight: 800; }
.set-header-sub   { font-size: 12px; opacity: .75; }

.set-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 18px 20px;
}
.set-card-title {
    font-size: 12px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .6px; color: var(--u-muted);
    padding-bottom: 12px; margin-bottom: 16px;
    border-bottom: 1px solid var(--u-line);
    display: flex; align-items: center; gap: 8px;
}
.set-card-title::before {
    content: ''; display: inline-block; width: 3px; height: 14px;
    background: #7c3aed; border-radius: 2px; flex-shrink: 0;
}

/* 2-column page layout */
.set-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
    align-items: start;
}
@media(max-width:780px){ .set-layout { grid-template-columns: 1fr; } }

/* Fields */
.set-field { display: flex; flex-direction: column; gap: 5px; }
.set-field label { font-size: 12px; font-weight: 700; color: var(--u-muted); }
.set-field select,
.set-field input[type="password"] {
    padding: 9px 12px; border: 1px solid var(--u-line);
    border-radius: 8px; font-size: 13px; color: var(--u-text);
    background: var(--u-bg); outline: none; transition: border-color .15s, box-shadow .15s;
    width: 100%; box-sizing: border-box;
}
.set-field select:focus,
.set-field input[type="password"]:focus {
    border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.12);
}

/* Master toggle row */
.set-master-toggle {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 11px 14px; border-radius: 10px;
    border: 1px solid var(--u-line); background: var(--u-bg);
    margin-bottom: 12px;
}
.set-master-label { font-size: 13px; font-weight: 700; color: var(--u-text); }
.set-master-desc  { font-size: 11px; color: var(--u-muted); }

/* Channel cards grid — 3 per row */
.set-channel-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;
}
@media(max-width:500px){ .set-channel-grid { grid-template-columns: 1fr; } }

.set-channel-card {
    border: 1px solid var(--u-line); border-radius: 10px;
    padding: 12px; background: var(--u-bg);
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    text-align: center; transition: border-color .15s;
}
.set-channel-card.active { border-color: #7c3aed; background: rgba(124,58,237,.04); }
.set-channel-icon { font-size: 22px; }
.set-channel-lbl  { font-size: 12px; font-weight: 700; color: var(--u-text); }
.set-channel-desc { font-size: 10px; color: var(--u-muted); line-height: 1.4; }

/* CSS toggle */
.set-toggle { position: relative; display: inline-block; width: 40px; height: 22px; flex-shrink: 0; }
.set-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
.set-toggle-track {
    position: absolute; inset: 0; background: var(--u-line);
    border-radius: 999px; cursor: pointer; transition: background .2s;
}
.set-toggle input:checked + .set-toggle-track { background: #7c3aed; }
.set-toggle-track::after {
    content: ''; position: absolute; top: 3px; left: 3px;
    width: 16px; height: 16px; border-radius: 50%;
    background: #fff; transition: transform .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.set-toggle input:checked + .set-toggle-track::after { transform: translateX(18px); }

/* Password grid */
.set-pw-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media(max-width:480px){ .set-pw-grid { grid-template-columns: 1fr; } }

/* Strength bar */
.set-pw-strength-bar { height: 4px; background: var(--u-line); border-radius: 2px; overflow: hidden; margin: 6px 0 3px; }
.set-pw-strength-fill { height: 100%; border-radius: 2px; transition: width .3s, background .3s; }
.set-pw-strength-lbl  { font-size: 11px; }

/* Save btn */
.set-save-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 9px 22px; border-radius: 9px;
    background: #7c3aed; color: #fff; font-size: 13px; font-weight: 700;
    border: none; cursor: pointer; transition: background .15s; margin-top: 14px;
}
.set-save-btn:hover { background: #6d28d9; }

/* Tips */
.set-tip { display: flex; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--u-line); }
.set-tip:last-child { border-bottom: none; padding-bottom: 0; }
.set-tip-icon { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
.set-tip-text { font-size: 12px; color: var(--u-muted); line-height: 1.5; }
</style>
@endpush

@section('content')
@php
    $g = $guestApplication;
    $locale         = old('preferred_locale',      $g?->preferred_locale ?? 'tr');
    $timezone       = old('preferred_timezone',    $preferredTimezone ?? 'Europe/Berlin');
    $dateFmt        = old('preferred_date_format', $preferredDateFmt  ?? 'DD.MM.YYYY');
    $notifEnabled   = (bool) old('notifications_enabled', $g?->notifications_enabled ?? true);
    $notifyEmail    = (bool) old('notify_email',    $g?->notify_email    ?? true);
    $notifyWhatsapp = (bool) old('notify_whatsapp', $g?->notify_whatsapp ?? false);
    $notifyInapp    = (bool) old('notify_inapp',    $g?->notify_inapp    ?? true);
@endphp

{{-- Header --}}
<div class="set-header">
    <div class="set-header-icon">⚙️</div>
    <div>
        <div class="set-header-title">Ayarlar</div>
        <div class="set-header-sub">Dil, bildirim tercihleri ve hesap güvenliği</div>
    </div>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="badge ok" style="display:block;padding:10px 16px;border-radius:10px;margin-bottom:14px;font-size:var(--tx-sm);">
    ✓ {{ session('success') }}
</div>
@endif
@if($errors->any())
<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;color:#991b1b;margin-bottom:14px;font-size:var(--tx-sm);">
    @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

{{-- ROW 1: Dil (sol) + Bildirimler (sağ) --}}
<div class="set-layout">

    {{-- ── Dil ── --}}
    <form method="POST" action="{{ route('student.settings.update') }}" id="form-general">
        @csrf
        {{-- hidden notif fields so this form also saves them --}}
        <input type="hidden" name="notifications_enabled" value="0">
        <input type="hidden" name="notify_email"    value="0">
        <input type="hidden" name="notify_whatsapp" value="0">
        <input type="hidden" name="notify_inapp"    value="0">

        <div class="set-card">
            <div class="set-card-title">🌐 Dil ve Görünüm</div>
            <div style="display:flex;flex-direction:column;gap:12px;">

                <div class="set-field">
                    <label>Arayüz Dili</label>
                    <select name="preferred_locale" onchange="updateClockPreview()">
                        <option value="tr" @selected($locale==='tr')>🇹🇷 Türkçe</option>
                        <option value="de" @selected($locale==='de')>🇩🇪 Deutsch</option>
                        <option value="en" @selected($locale==='en')>🇬🇧 English</option>
                    </select>
                </div>

                <div class="set-field">
                    <label>Saat Dilimi</label>
                    <select name="preferred_timezone" id="tz-select" onchange="updateClockPreview()">
                        <optgroup label="Avrupa">
                            <option value="Europe/Berlin"    @selected($timezone==='Europe/Berlin')>🇩🇪 Almanya (CET/CEST)</option>
                            <option value="Europe/Istanbul"  @selected($timezone==='Europe/Istanbul')>🇹🇷 İstanbul (TRT)</option>
                            <option value="Europe/Vienna"    @selected($timezone==='Europe/Vienna')>🇦🇹 Avusturya (CET/CEST)</option>
                            <option value="Europe/Zurich"    @selected($timezone==='Europe/Zurich')>🇨🇭 İsviçre (CET/CEST)</option>
                            <option value="Europe/Amsterdam" @selected($timezone==='Europe/Amsterdam')>🇳🇱 Hollanda (CET/CEST)</option>
                            <option value="Europe/London"    @selected($timezone==='Europe/London')>🇬🇧 Londra (GMT/BST)</option>
                        </optgroup>
                        <optgroup label="Diğer">
                            <option value="UTC"              @selected($timezone==='UTC')>🌐 UTC</option>
                            <option value="America/New_York" @selected($timezone==='America/New_York')>🇺🇸 New York (EST)</option>
                        </optgroup>
                    </select>
                </div>

                <div class="set-field">
                    <label>Tarih Formatı</label>
                    <select name="preferred_date_format" id="fmt-select" onchange="updateClockPreview()">
                        <option value="DD.MM.YYYY" @selected($dateFmt==='DD.MM.YYYY')>DD.MM.YYYY &nbsp;(Almanya / Türkiye)</option>
                        <option value="YYYY-MM-DD" @selected($dateFmt==='YYYY-MM-DD')>YYYY-MM-DD &nbsp;(ISO 8601)</option>
                        <option value="MM/DD/YYYY" @selected($dateFmt==='MM/DD/YYYY')>MM/DD/YYYY &nbsp;(ABD)</option>
                    </select>
                </div>

                {{-- Önizleme satırı --}}
                <div style="display:flex;align-items:center;justify-content:space-between;
                            background:var(--u-bg);border:1px solid var(--u-line);
                            border-radius:9px;padding:9px 13px;">
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:700;text-transform:uppercase;letter-spacing:.4px;">
                        Önizleme
                    </div>
                    <div style="text-align:right;">
                        <div id="preview-clock" style="font-size:var(--tx-base);font-weight:800;color:#7c3aed;font-variant-numeric:tabular-nums;">
                            --:--:--
                        </div>
                        <div id="preview-date" style="font-size:var(--tx-xs);color:var(--u-muted);">--.--.----</div>
                    </div>
                </div>

            </div>
            <button type="submit" class="set-save-btn" style="margin-top:14px;">💾 Kaydet</button>
        </div>
    </form>

    {{-- ── Bildirimler ── --}}
    <form method="POST" action="{{ route('student.settings.update') }}" id="form-notif">
        @csrf
        <div class="set-card">
            <div class="set-card-title">🔔 Bildirim Tercihleri</div>

            {{-- Master toggle --}}
            <div class="set-master-toggle">
                <div>
                    <div class="set-master-label">Tüm Bildirimler</div>
                    <div class="set-master-desc">Kapatırsan yalnızca kritik uyarılar gönderilir</div>
                </div>
                <label class="set-toggle">
                    <input type="checkbox" name="notifications_enabled" value="1"
                           @checked($notifEnabled) onchange="toggleChannels(this.checked)">
                    <span class="set-toggle-track"></span>
                </label>
            </div>

            {{-- 3 channel cards --}}
            <div class="set-channel-grid" id="notif-channels"
                 style="{{ $notifEnabled ? '' : 'opacity:.4;pointer-events:none;' }}">

                <div class="set-channel-card {{ $notifyEmail ? 'active' : '' }}" id="card-email">
                    <div class="set-channel-icon">📧</div>
                    <div class="set-channel-lbl">E-posta</div>
                    <div class="set-channel-desc">Önemli güncellemeler</div>
                    <label class="set-toggle">
                        <input type="checkbox" name="notify_email" value="1"
                               @checked($notifyEmail) onchange="syncCardState('card-email',this.checked)">
                        <span class="set-toggle-track"></span>
                    </label>
                </div>

                <div class="set-channel-card {{ $notifyWhatsapp ? 'active' : '' }}" id="card-wa">
                    <div class="set-channel-icon">💬</div>
                    <div class="set-channel-lbl">WhatsApp</div>
                    <div class="set-channel-desc">Anlık mesajlar</div>
                    <label class="set-toggle">
                        <input type="checkbox" name="notify_whatsapp" value="1"
                               @checked($notifyWhatsapp) onchange="syncCardState('card-wa',this.checked)">
                        <span class="set-toggle-track"></span>
                    </label>
                </div>

                <div class="set-channel-card {{ $notifyInapp ? 'active' : '' }}" id="card-inapp">
                    <div class="set-channel-icon">🔔</div>
                    <div class="set-channel-lbl">Panel</div>
                    <div class="set-channel-desc">Portal bildirimleri</div>
                    <label class="set-toggle">
                        <input type="checkbox" name="notify_inapp" value="1"
                               @checked($notifyInapp) onchange="syncCardState('card-inapp',this.checked)">
                        <span class="set-toggle-track"></span>
                    </label>
                </div>
            </div>

            <button type="submit" class="set-save-btn">💾 Kaydet</button>
        </div>
    </form>

</div>

{{-- ROW 2: Şifre (sol) + İpuçları (sağ) --}}
<div class="set-layout">

    {{-- ── Şifre ── --}}
    <form method="POST" action="{{ route('student.settings.password') }}">
        @csrf
        <div class="set-card">
            <div class="set-card-title">🔒 Şifre Değiştir</div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div class="set-field">
                    <label>Mevcut Şifre</label>
                    <input type="password" name="current_password" autocomplete="current-password" placeholder="••••••••">
                </div>
                <div class="set-pw-grid">
                    <div class="set-field">
                        <label>Yeni Şifre</label>
                        <input type="password" name="new_password" autocomplete="new-password"
                               placeholder="En az 8 karakter" oninput="checkPwStrength(this.value)">
                        <div class="set-pw-strength-bar">
                            <div class="set-pw-strength-fill" id="pw-fill" style="width:0%;background:#dc2626;"></div>
                        </div>
                        <div class="set-pw-strength-lbl" id="pw-lbl" style="color:var(--u-muted);">Şifre girin</div>
                    </div>
                    <div class="set-field">
                        <label>Yeni Şifre Tekrar</label>
                        <input type="password" name="new_password_confirmation" autocomplete="new-password" placeholder="••••••••">
                    </div>
                </div>
            </div>
            <button type="submit" class="set-save-btn">🔒 Güncelle</button>
        </div>
    </form>

    {{-- ── İpuçları ── --}}
    <div class="set-card">
        <div class="set-card-title">💡 Bilgi</div>
        <div class="set-tip">
            <div class="set-tip-icon">🌐</div>
            <div class="set-tip-text">Dil ayarı portal arayüzünü ve danışmanınıza gönderilen iletişim dilini etkiler.</div>
        </div>
        <div class="set-tip">
            <div class="set-tip-icon">🔔</div>
            <div class="set-tip-text">Bildirimleri kapatsanız bile kritik uyarılar (vize tarihi, belge reddi) yine gönderilir.</div>
        </div>
        <div class="set-tip">
            <div class="set-tip-icon">🔒</div>
            <div class="set-tip-text">Güvenli şifre için büyük/küçük harf, rakam ve sembol kullanın. En az 8 karakter önerilir.</div>
        </div>
        <div class="set-tip">
            <div class="set-tip-icon">💬</div>
            <div class="set-tip-text">WhatsApp bildirimi için profilinizdeki telefon numarasının güncel olduğundan emin olun.</div>
        </div>
    </div>

</div>

<script>
// ── Clock preview ──────────────────────────────────────────
var _clockInterval = null;
function updateClockPreview() {
    clearInterval(_clockInterval);
    _clockInterval = setInterval(_tickClock, 1000);
    _tickClock();
}
function _tickClock() {
    var tz  = document.getElementById('tz-select')?.value || 'Europe/Berlin';
    var fmt = document.getElementById('fmt-select')?.value || 'DD.MM.YYYY';
    var now = new Date();
    // Time
    var timeStr = now.toLocaleTimeString('tr-TR', { timeZone: tz, hour12: false, hour:'2-digit', minute:'2-digit', second:'2-digit' });
    document.getElementById('preview-clock').textContent = timeStr;
    // Date
    var parts = new Intl.DateTimeFormat('tr-TR', { timeZone: tz, year:'numeric', month:'2-digit', day:'2-digit' }).formatToParts(now);
    var d = {}, p; for (p of parts) d[p.type] = p.value;
    var dateStr = fmt === 'YYYY-MM-DD' ? (d.year+'-'+d.month+'-'+d.day)
                : fmt === 'MM/DD/YYYY' ? (d.month+'/'+d.day+'/'+d.year)
                :                        (d.day+'.'+d.month+'.'+d.year);
    document.getElementById('preview-date').textContent = dateStr;
}
window.addEventListener('DOMContentLoaded', updateClockPreview);
// ── End clock ──────────────────────────────────────────────

function toggleChannels(on) {
    var el = document.getElementById('notif-channels');
    el.style.opacity = on ? '1' : '.4';
    el.style.pointerEvents = on ? '' : 'none';
}
function syncCardState(cardId, on) {
    var card = document.getElementById(cardId);
    if (on) card.classList.add('active');
    else card.classList.remove('active');
}
function checkPwStrength(val) {
    var fill = document.getElementById('pw-fill');
    var lbl  = document.getElementById('pw-lbl');
    if (!val) { fill.style.width='0%'; lbl.textContent='Şifre girin'; lbl.style.color='var(--u-muted)'; return; }
    var s = 0;
    if (val.length >= 8)  s++;
    if (val.length >= 12) s++;
    if (/[A-Z]/.test(val)) s++;
    if (/[0-9]/.test(val)) s++;
    if (/[^A-Za-z0-9]/.test(val)) s++;
    var lvl = [
        {w:'20%', bg:'#dc2626', t:'Çok zayıf'},
        {w:'40%', bg:'#d97706', t:'Zayıf'},
        {w:'60%', bg:'#ca8a04', t:'Orta'},
        {w:'80%', bg:'#16a34a', t:'İyi'},
        {w:'100%',bg:'#15803d', t:'Güçlü ✓'},
    ][Math.min(s, 4)];
    fill.style.width = lvl.w; fill.style.background = lvl.bg;
    lbl.textContent = lvl.t; lbl.style.color = lvl.bg;
}
</script>
@endsection

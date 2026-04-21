@extends('senior.layouts.app')
@section('title','Ayarlar')
@section('page_title','Ayarlar')

@push('head')
<style>
.sset-field label { display:block; font-size:11px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; }
.sset-field input[type="number"],
.sset-field input[type="password"],
.sset-field input[type="text"] {
    width:100%; padding:9px 12px; border:1.5px solid var(--u-line); border-radius:8px;
    font-size:14px; color:var(--u-text); background:var(--u-bg);
    transition:border-color .15s; box-sizing:border-box; font-family:inherit;
}
.sset-field input:focus { outline:none; border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.1); }
.sset-check label {
    display:flex; align-items:center; gap:10px;
    padding:10px 12px; border:1px solid var(--u-line); border-radius:8px;
    cursor:pointer; font-size:13px; font-weight:500; color:var(--u-text);
    background:var(--u-bg); transition:border-color .15s, background .15s;
}
.sset-check label:hover { border-color:#c4b5fd; background:var(--u-card); }
.sset-check input[type="checkbox"] { width:16px; height:16px; accent-color:#7c3aed; }
</style>
@endpush

@section('content')

{{-- Gradient Header --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;">
    <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;margin-bottom:4px;">⚙️ Ayarlar</div>
    <div style="font-size:var(--tx-sm);opacity:.8;">Bildirim, randevu ve şifre tercihleri</div>
</div>

@if(session('status'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;margin-bottom:12px;font-size:var(--tx-sm);font-weight:600;color:#15803d;">
    ✅ {{ session('status') }}
</div>
@endif
@if($errors->any())
<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 16px;margin-bottom:12px;font-size:var(--tx-sm);color:#dc2626;">
    ⚠ {{ $errors->first() }}
</div>
@endif

{{-- Portal Tercihleri --}}
<form method="POST" action="{{ route('senior.settings.update') }}">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">

        {{-- Bildirimler --}}
        <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:18px 20px;">
            <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px;">🔔 Bildirimler</div>
            <div style="display:flex;flex-direction:column;gap:8px;" class="sset-check">
                <label>
                    <input type="checkbox" name="notify_email" value="1"
                        @checked((bool) old('notify_email', data_get($portalPrefs ?? [], 'settings.notify_email', true)))>
                    <span>📧 E-posta bildirimleri</span>
                </label>
                <label>
                    <input type="checkbox" name="notify_ticket" value="1"
                        @checked((bool) old('notify_ticket', data_get($portalPrefs ?? [], 'settings.notify_ticket', true)))>
                    <span>🎫 Ticket bildirimleri</span>
                </label>
                <label>
                    <input type="checkbox" name="notify_appointment" value="1"
                        @checked((bool) old('notify_appointment', data_get($portalPrefs ?? [], 'settings.notify_appointment', true)))>
                    <span>📅 Randevu bildirimleri</span>
                </label>
                <label>
                    <input type="checkbox" name="notify_dm" value="1"
                        @checked((bool) old('notify_dm', data_get($portalPrefs ?? [], 'settings.notify_dm', true)))>
                    <span>💬 Danışman mesajları bildirimi</span>
                </label>
            </div>
        </div>

        {{-- Randevu Tercihleri --}}
        <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:18px 20px;">
            <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px;">📅 Randevu Tercihleri</div>
            <div class="sset-check" style="margin-bottom:14px;">
                <label>
                    <input type="checkbox" name="appointment_auto_confirm" value="1"
                        @checked((bool) old('appointment_auto_confirm', data_get($portalPrefs ?? [], 'settings.appointment_auto_confirm', false)))>
                    <span>✅ Randevuları otomatik onayla</span>
                </label>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                <div class="sset-field">
                    <label>Slot Süresi (dk)</label>
                    <input type="number" min="15" max="180" step="5" name="appointment_slot_minutes"
                        value="{{ old('appointment_slot_minutes', data_get($portalPrefs ?? [], 'settings.appointment_slot_minutes', 30)) }}">
                </div>
                <div class="sset-field">
                    <label>Buffer Süresi (dk)</label>
                    <input type="number" min="0" max="180" step="5" name="appointment_buffer_minutes"
                        value="{{ old('appointment_buffer_minutes', data_get($portalPrefs ?? [], 'settings.appointment_buffer_minutes', 15)) }}">
                </div>
            </div>
            <button type="submit"
                style="background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:10px 24px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">
                💾 Tercihleri Kaydet
            </button>
        </div>
    </div>
</form>

{{-- Şifre Değiştir --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:18px 20px;">
    <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:16px;">🔒 Şifre Değiştir</div>
    <form method="POST" action="{{ route('senior.settings.password') }}">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px;">
            <div class="sset-field">
                <label>Mevcut Şifre</label>
                <input type="password" name="current_password" placeholder="••••••••">
            </div>
            <div class="sset-field">
                <label>Yeni Şifre</label>
                <input type="password" name="new_password" placeholder="••••••••">
            </div>
            <div class="sset-field">
                <label>Yeni Şifre Tekrar</label>
                <input type="password" name="new_password_confirmation" placeholder="••••••••">
            </div>
        </div>
        <button type="submit"
            style="background:#6d28d9;color:#fff;border:none;border-radius:8px;padding:10px 24px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">
            🔑 Şifreyi Güncelle
        </button>
    </form>
</div>

{{-- ══════ Google Calendar Entegrasyonu ══════ --}}
<div class="card" style="margin-top:20px;padding:20px;">
    <h3 style="margin:0 0 16px;display:flex;align-items:center;gap:10px;">
        <span style="font-size:22px;">📅</span>
        Google Calendar Entegrasyonu
        @if($googleCalConn)
            <span style="background:rgba(22,163,74,.15);color:#16a34a;font-size:11px;padding:3px 10px;border-radius:10px;font-weight:700;">✓ Bağlı</span>
        @else
            <span style="background:rgba(148,163,184,.15);color:#64748b;font-size:11px;padding:3px 10px;border-radius:10px;font-weight:600;">Bağlı değil</span>
        @endif
    </h3>
    <p style="font-size:13px;color:var(--u-muted);margin:0 0 16px;line-height:1.6;">
        Öğrencilerinle oluşturduğun randevular otomatik olarak Google Takvim'ine eklenir, güncellenir ve iptal edildiğinde silinir. Online randevular için Google Meet linki de otomatik üretilir.
    </p>

    @if($googleCalConn)
        <div style="background:var(--u-bg,#f8fafc);border:1px solid var(--u-line);border-radius:10px;padding:14px 16px;margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <div>
                    <div style="font-size:13px;font-weight:700;color:var(--u-text);">{{ $googleCalConn->google_email }}</div>
                    <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">
                        Takvim: <code>{{ $googleCalConn->calendar_id }}</code>
                        @if($googleCalConn->last_synced_at)
                            · Son sync: {{ $googleCalConn->last_synced_at->diffForHumans() }}
                        @endif
                        @if($googleCalConn->last_sync_status === 'failed')
                            · <span style="color:#dc2626;font-weight:700;">⚠ Son sync başarısız</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <form method="POST" action="{{ route('integrations.google-calendar.sync-now') }}" style="margin:0;">
                        @csrf
                        <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;">
                            🔄 Şimdi Senkronize Et
                        </button>
                    </form>
                    <form method="POST" action="{{ route('integrations.google-calendar.disconnect') }}" style="margin:0;" onsubmit="return confirm('Google Calendar bağlantısını kaldır?');">
                        @csrf
                        <button type="submit" style="background:transparent;color:#dc2626;border:1px solid #fecaca;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;">
                            Bağlantıyı Kaldır
                        </button>
                    </form>
                </div>
            </div>

            {{-- Sync yönü toggles --}}
            <form method="POST" action="{{ route('integrations.google-calendar.toggle') }}" style="margin-top:10px;padding-top:10px;border-top:1px solid var(--u-line);display:flex;gap:18px;flex-wrap:wrap;">
                @csrf
                <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;">
                    <input type="checkbox" name="sync_push" value="1" {{ $googleCalConn->sync_push ? 'checked' : '' }} onchange="this.form.submit()">
                    <span><strong>Push</strong> (portal → Google) — yeni randevular otomatik Google Takvim'e gider</span>
                </label>
                <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;">
                    <input type="checkbox" name="sync_pull" value="1" {{ $googleCalConn->sync_pull ? 'checked' : '' }} onchange="this.form.submit()">
                    <span><strong>Pull</strong> (Google → portal) — Google'da yaptığın değişiklikler 15 dk'da bir çekilir</span>
                </label>
            </form>
            @if($googleCalConn->last_sync_error)
                <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--u-line);font-size:11px;color:#dc2626;">
                    Hata: {{ \Illuminate\Support\Str::limit($googleCalConn->last_sync_error, 200) }}
                </div>
            @endif
        </div>
    @else
        <a href="{{ route('integrations.google-calendar.connect') }}"
           style="display:inline-flex;align-items:center;gap:10px;padding:10px 20px;border:1.5px solid #dadce0;border-radius:10px;background:#fff;color:#3c4043;font-size:13px;font-weight:600;text-decoration:none;">
            <svg width="18" height="18" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Google Calendar'ı Bağla
        </a>
        <div style="font-size:11px;color:var(--u-muted);margin-top:8px;">
            Bağlantı sonrası randevu oluşturma/güncelleme/iptal işlemlerin otomatik olarak takvime yansır.
        </div>
    @endif
</div>

@endsection

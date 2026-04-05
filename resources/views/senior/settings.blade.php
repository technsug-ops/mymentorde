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
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
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
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <button type="submit"
            style="background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:10px 24px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">
            💾 Tercihleri Kaydet
        </button>
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

@endsection

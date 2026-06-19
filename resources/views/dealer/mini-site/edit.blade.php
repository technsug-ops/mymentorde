@extends('dealer.layouts.app')

@section('title', 'Mini-Site')
@section('page_title', 'Mini-Site (White-Label)')
@section('page_subtitle', 'Kendi marka ve renginle bir tanıtım sayfası — ziyaretçiler senin kodunla başvurur')

@section('content')

@php $d = $dealer; @endphp

@if(session('status'))
    <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px;">
        {{ session('status') }}
    </div>
@endif
@if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px;">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    </div>
@endif

{{-- Durum kartı --}}
<div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div style="font-size:14px;">
        <strong>Yayın Durumu:</strong>
        @if($d?->site_enabled)
            <span style="background:#ecfdf5;color:#065f46;padding:3px 10px;border-radius:999px;">Yayında</span>
        @else
            <span style="background:#fef9c3;color:#854d0e;padding:3px 10px;border-radius:999px;">Yayında değil (yönetici onayı bekliyor)</span>
        @endif
    </div>
    @if($d?->public_slug)
        <div style="font-size:13px;color:var(--muted,#64748b);">
            Adres: <a href="/p/{{ $d->public_slug }}?preview=1" target="_blank" style="color:var(--theme-accent-dealer,#1E3D6B);font-weight:600;">/p/{{ $d->public_slug }}</a>
            <span style="font-size:11px;">(önizleme)</span>
        </div>
    @endif
</div>

<form method="POST" action="/dealer/mini-site" enctype="multipart/form-data"
      style="max-width:640px;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:14px;padding:24px;">
    @csrf

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Sayfa Adresi (slug)</label>
        <div style="display:flex;align-items:center;gap:4px;">
            <span style="color:var(--muted,#64748b);font-size:14px;">/p/</span>
            <input type="text" name="public_slug" value="{{ old('public_slug', $d?->public_slug) }}" maxlength="64"
                   placeholder="ornek-bayi" pattern="[a-z0-9\-]+"
                   style="flex:1;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;font-family:ui-monospace,monospace;">
        </div>
        <small style="color:var(--muted,#64748b);font-size:12px;">Sadece küçük harf, rakam ve tire. Benzersiz olmalı.</small>
    </div>

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Logo (PNG/JPG/WEBP, max 2MB)</label>
        @if($d?->site_logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($d->site_logo_path) }}" alt="logo" style="height:48px;margin-bottom:8px;display:block;">
        @endif
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" style="font-size:13px;">
    </div>

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Marka Rengi</label>
        <input type="color" name="site_accent_color" value="{{ old('site_accent_color', $d?->site_accent_color ?: '#7e58bf') }}"
               style="width:60px;height:40px;border:1px solid var(--border,#cbd5e1);border-radius:8px;cursor:pointer;">
    </div>

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Hero Başlık</label>
        <input type="text" name="site_hero_title" value="{{ old('site_hero_title', $d?->site_hero_title) }}" maxlength="160"
               placeholder="Almanya'da Eğitim Hayalini Gerçekleştir"
               style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
    </div>

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Hero Alt Metin</label>
        <textarea name="site_hero_subtitle" rows="2" maxlength="300"
                  style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">{{ old('site_hero_subtitle', $d?->site_hero_subtitle) }}</textarea>
    </div>

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Hakkımda / Tanıtım</label>
        <textarea name="site_about_text" rows="4" maxlength="4000"
                  style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">{{ old('site_about_text', $d?->site_about_text) }}</textarea>
    </div>

    <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Telefon</label>
            <input type="text" name="site_phone" value="{{ old('site_phone', $d?->site_phone) }}" maxlength="50"
                   style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
        </div>
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">WhatsApp</label>
            <input type="text" name="site_whatsapp" value="{{ old('site_whatsapp', $d?->site_whatsapp) }}" maxlength="50"
                   style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
        </div>
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Instagram</label>
            <input type="text" name="site_instagram" value="{{ old('site_instagram', $d?->site_instagram) }}" maxlength="100"
                   style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
        </div>
    </div>

    <button type="submit"
            style="background:var(--theme-accent-dealer,#1E3D6B);color:#fff;padding:11px 22px;border:none;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;">
        Kaydet
    </button>
</form>

@endsection

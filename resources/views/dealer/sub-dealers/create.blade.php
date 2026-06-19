@extends('dealer.layouts.app')

@section('title', 'Yeni Alt Bayi')
@section('page_title', 'Yeni Alt Bayi')
@section('page_subtitle', 'Alt bayi oluştur — kendi hesabıyla giriş yapar, yönlendirdiği adaylar senin ağında görünür')

@section('content')

@if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px;">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    </div>
@endif

<div style="max-width:560px;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:14px;padding:24px;">
    <form method="POST" action="/dealer/sub-dealers">
        @csrf

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Ad Soyad / Bayi Adı</label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                   style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">E-posta (giriş + davet)</label>
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="255"
                   style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
            <small style="color:var(--muted,#64748b);font-size:12px;">Bu adrese şifre belirleme daveti gönderilir.</small>
        </div>

        <div style="display:flex;gap:12px;margin-bottom:16px;">
            <div style="flex:1;">
                <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Telefon</label>
                <input type="text" name="phone" value="{{ old('phone') }}" maxlength="50"
                       style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
            </div>
            <div style="flex:1;">
                <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">WhatsApp</label>
                <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" maxlength="50"
                       style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
            </div>
        </div>

        <div style="margin-bottom:20px;background:#f8fafc;border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:14px;">
            <label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer;font-size:13px;color:#475569;">
                <input type="checkbox" name="data_consent" value="1" style="margin-top:3px;" {{ old('data_consent') ? 'checked' : '' }}>
                <span>Bu alt bayinin yönlendirdiği aday/öğrenci verilerinin tamamını yalnızca operasyon amacıyla görüntüleyeceğimi ve KVKK kapsamında veri sorumlusunun (MentorDE) belirlediği şekilde işleyeceğimi kabul ederim.</span>
            </label>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit"
                    style="background:var(--theme-accent-dealer,#1E3D6B);color:#fff;padding:11px 22px;border:none;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;">
                Alt Bayi Oluştur
            </button>
            <a href="/dealer/sub-dealers" style="padding:11px 22px;border:1px solid var(--border,#cbd5e1);border-radius:10px;text-decoration:none;color:#475569;font-size:14px;">İptal</a>
        </div>
    </form>
</div>

@endsection

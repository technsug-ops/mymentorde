@extends('manager.layouts.app')

@section('page_title', 'Elle Aday Girişi')
@section('page_subtitle', 'Telefonda konuştuğunuz öğrenciyi doğrudan kaydedin')

@section('content')

@php
    $_leadCompany = app()->bound('current_company') ? app('current_company') : null;
@endphp

<div style="max-width:760px;">

    <div style="padding:12px 14px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-left:3px solid var(--u-primary,#5b2e91);border-radius:10px;margin-bottom:18px;font-size:13px;line-height:1.6;">
        Kayıt <strong>{{ $_leadCompany?->brand_name ?: ($_leadCompany?->name ?? 'mevcut firma') }}</strong>
        adına oluşturulacak.
        @if(auth()->user() && count(auth()->user()->visibleCompanyIds()) > 1)
            Başka bir firma adına girmek için üstteki firma seçicisini kullanın.
        @endif
        <br>Danışman otomatik atanır; operasyonu yürüten firmanın havuzundan seçilir.
    </div>

    @if($errors->any())
        <div style="padding:11px 14px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;margin-bottom:16px;font-size:13px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('manager.leads.store') }}"
          style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;padding:20px;">
        @csrf

        <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--u-muted,#64748b);margin-bottom:10px;">Kişisel Bilgiler</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Ad *</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required maxlength="120"
                       style="width:100%;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Soyad *</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" required maxlength="120"
                       style="width:100%;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">E-posta *</label>
                <input type="email" name="email" value="{{ old('email') }}" required maxlength="190"
                       placeholder="ogrenci@example.com"
                       style="width:100%;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
                <small style="font-size:11px;color:var(--u-muted,#64748b);">Portal hesabı bu adresle açılır — gerçek olmalı.</small>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Telefon</label>
                <input type="text" name="phone" value="{{ old('phone') }}" maxlength="60"
                       placeholder="+90 5XX XXX XXXX"
                       style="width:100%;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
            </div>
        </div>

        <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--u-muted,#64748b);margin:18px 0 10px;">Başvuru Detayı</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Başvuru Tipi *</label>
                <select name="application_type" required
                        style="width:100%;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 8px;font-size:13px;">
                    @foreach(['bachelor' => 'Bachelor (Lisans)', 'master' => 'Master (Yüksek Lisans)', 'language' => 'Dil Kursu', 'ausbildung' => 'Ausbildung', 'other' => 'Diğer'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('application_type') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Hedef Dönem</label>
                <input type="text" name="target_term" value="{{ old('target_term') }}" maxlength="60"
                       placeholder="2027 Summer"
                       style="width:100%;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Hedef Şehir</label>
                <input type="text" name="target_city" value="{{ old('target_city') }}" maxlength="100"
                       style="width:100%;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Kaynak</label>
                <input type="text" name="lead_source" value="{{ old('lead_source') }}" maxlength="64"
                       placeholder="telefon, tavsiye, fuar…"
                       style="width:100%;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Not</label>
            <textarea name="notes" rows="3" maxlength="2000"
                      placeholder="Görüşme notu, özel durum…"
                      style="width:100%;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:8px 10px;font-size:13px;resize:vertical;">{{ old('notes') }}</textarea>
        </div>

        <button type="submit"
                style="height:40px;padding:0 22px;border-radius:10px;border:0;background:var(--u-primary,#5b2e91);color:#fff;font-size:14px;font-weight:600;cursor:pointer;">
            Adayı Kaydet
        </button>
    </form>
</div>

@endsection

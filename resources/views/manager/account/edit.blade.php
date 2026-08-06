@extends('manager.layouts.app')

@section('page_title', 'Hesabım')
@section('page_subtitle', 'Giriş bilgilerinizi buradan güncelleyin')

@section('content')

<div style="max-width:560px;">

    @if(session('status'))
        <div style="padding:11px 14px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;margin-bottom:16px;font-size:13px;">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div style="padding:11px 14px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;margin-bottom:16px;font-size:13px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('manager.account.update') }}"
          style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;padding:20px;">
        @csrf

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Ad Soyad</label>
            <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required maxlength="120"
                   style="width:100%;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Giriş E-postası</label>
            <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required maxlength="190"
                   style="width:100%;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
            <small style="display:block;font-size:11px;color:var(--u-muted,#64748b);margin-top:4px;">
                Bu adresle giriş yaparsınız ve şifre sıfırlama linki buraya gelir.
                Değiştirirseniz eski adres artık çalışmaz.
            </small>
        </div>

        <div style="margin-bottom:18px;padding-top:14px;border-top:1px solid var(--u-line,#e5e7eb);">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Mevcut Şifreniz</label>
            <input type="password" name="current_password" required autocomplete="current-password"
                   style="width:100%;max-width:260px;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
            <small style="display:block;font-size:11px;color:var(--u-muted,#64748b);margin-top:4px;">
                Giriş bilgilerini değiştirmek için şifrenizi doğrulamanız gerekiyor.
            </small>
        </div>

        <button type="submit"
                style="height:40px;padding:0 22px;border-radius:10px;border:0;background:var(--u-primary,#5b2e91);color:#fff;font-size:14px;font-weight:600;cursor:pointer;">
            Kaydet
        </button>
    </form>

    <p style="font-size:12.5px;color:var(--u-muted,#64748b);line-height:1.7;margin-top:14px;">
        Şifrenizi değiştirmek için
        <a href="/forgot-password" style="color:var(--u-primary,#5b2e91);">Şifremi Unuttum</a>
        akışını kullanın — sıfırlama linki yukarıdaki adrese gider.
    </p>
</div>

@endsection

@extends('guest.layouts.app')
@section('title', 'Aday Öğrenci Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div style="max-width:720px;margin:40px auto;padding:0 16px;">
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:30px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.04);">
        <div style="font-size:48px;margin-bottom:16px;">⚠️</div>
        <h2 style="margin:0 0 12px;font-size:20px;color:#0f172a;">Başvuru Kaydı Bulunamadı</h2>
        <p style="font-size:14px;color:#475569;line-height:1.7;max-width:520px;margin:0 auto 20px;">
            Hesabın oluşturulmuş ({{ $userEmail }}) ama sistemde bağlı bir başvuru kaydı yok. Bu durumda panel içerikleri görüntülenemez.
        </p>
        <p style="font-size:13px;color:#64748b;line-height:1.7;max-width:520px;margin:0 auto 24px;">
            Genellikle iki sebepten olur:
        </p>
        <ol style="font-size:13px;color:#475569;line-height:1.8;text-align:left;max-width:480px;margin:0 auto 26px;padding-left:20px;">
            <li>Henüz kayıt formunu doldurmadın → aşağıdan başla.</li>
            <li>Email güncellendi ama eski başvuru kaydı yeni email'le eşleşmiyor → Manager'a iletmen gerekir, teknik destek düzeltecek.</li>
        </ol>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="{{ route('apply.create') }}" style="padding:12px 22px;background:#1e40af;color:#fff;border-radius:8px;font-weight:700;font-size:13px;text-decoration:none;">
                📝 Yeni Başvuru Oluştur
            </a>
            <a href="mailto:support@mentorde.com" style="padding:12px 22px;background:#f1f5f9;color:#0f172a;border:1px solid #e2e8f0;border-radius:8px;font-weight:700;font-size:13px;text-decoration:none;">
                ✉ Destek ile İletişime Geç
            </a>
            <a href="/logout" style="padding:12px 22px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:8px;font-weight:700;font-size:13px;text-decoration:none;">
                ⎆ Çıkış Yap
            </a>
        </div>
    </div>
</div>
@endsection

@extends('study-buddy.layout')

@section('title', 'MentorDE Study Buddy — Sana özel Almanya programı bul')

@section('content')
<div class="sb-hero">
    <span class="sb-hero-badge">✨ AKILLI EŞLEŞTIRME</span>
    <h1 class="sb-hero-title">Almanya'da sana en uygun programı bul</h1>
    <p class="sb-hero-subtitle">5 dakikalık akıllı sihirbazımız 13.000+ Almanya programı arasından profil ve hedeflerine en uygun olanları sıralar.</p>

    <a href="{{ route('study-buddy.start') }}" class="sb-btn sb-btn-primary sb-hero-cta">
        Hadi başlayalım
        <span style="font-size: 18px;">→</span>
    </a>

    <div class="sb-hero-meta">Ücretsiz · Login gerekmiyor · İstediğin zaman bırakabilirsin</div>

    <div class="sb-stats">
        <div class="sb-stat">
            <div class="sb-stat-num">13K+</div>
            <div class="sb-stat-label">Almanya Programı</div>
        </div>
        <div class="sb-stat">
            <div class="sb-stat-num">331</div>
            <div class="sb-stat-label">Üniversite</div>
        </div>
        <div class="sb-stat">
            <div class="sb-stat-num">5dk</div>
            <div class="sb-stat-label">Tahmini Süre</div>
        </div>
    </div>
</div>

<div style="max-width: 600px; margin: 60px auto; padding: 28px; background: #fff; border-radius: 16px; box-shadow: 0 4px 16px rgba(126, 88, 191, 0.06);">
    <h2 style="font-size: 18px; color: #7e58bf; margin-bottom: 16px;">Bu sihirbaz nasıl çalışıyor?</h2>
    <ol style="list-style: none; padding: 0; counter-reset: step-counter;">
        <li style="position: relative; padding-left: 36px; margin-bottom: 14px; counter-increment: step-counter; font-size: 14px; color: #1a1a1a;">
            <span style="position: absolute; left: 0; top: 0; background: rgba(126, 88, 191, 0.12); color: #7e58bf; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">1</span>
            Hedeflerini ve profilini soran <strong>kısa sorulara</strong> cevap ver
        </li>
        <li style="position: relative; padding-left: 36px; margin-bottom: 14px; counter-increment: step-counter; font-size: 14px; color: #1a1a1a;">
            <span style="position: absolute; left: 0; top: 0; background: rgba(126, 88, 191, 0.12); color: #7e58bf; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">2</span>
            Akıllı motorumuz cevaplarını <strong>13.000+ programla eşleştirir</strong>
        </li>
        <li style="position: relative; padding-left: 36px; margin-bottom: 14px; counter-increment: step-counter; font-size: 14px; color: #1a1a1a;">
            <span style="position: absolute; left: 0; top: 0; background: rgba(126, 88, 191, 0.12); color: #7e58bf; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">3</span>
            Sana en uygun <strong>10 programı sıralar</strong> ve neden uyduğunu açıklar
        </li>
        <li style="position: relative; padding-left: 36px; counter-increment: step-counter; font-size: 14px; color: #1a1a1a;">
            <span style="position: absolute; left: 0; top: 0; background: rgba(126, 88, 191, 0.12); color: #7e58bf; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">4</span>
            Hazır olduğunda <strong>tek tıkla MentorDE'ye kayıt</strong> ol — danışmanın yönlendirsin
        </li>
    </ol>
</div>
@endsection

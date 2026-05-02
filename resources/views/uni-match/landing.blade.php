@extends('uni-match.layout')

@section('title', 'MentorDE UniMatch — Sana özel Almanya programı bul')

@section('content')
@if(! empty($resume))
<div style="max-width:600px;margin:0 auto 24px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;padding:18px 22px;border-left:4px solid #d97706;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <div style="font-size:28px;flex-shrink:0;">⏸️</div>
    <div style="flex:1;min-width:180px;">
        <div style="font-size:14.5px;font-weight:700;color:#78350f;margin-bottom:2px;">Wizard'da yarıda kalmıştın</div>
        <div style="font-size:12.5px;color:#92400e;">{{ $resume['step'] }}/19 adım · %{{ $resume['progress_pct'] }} tamamlandı{{ $resume['started_at'] ? ' · ' . $resume['started_at'] : '' }}</div>
    </div>
    <a href="{{ route('uni-match.step', ['n' => $resume['step']]) }}"
       style="background:#92400e;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;">
        Devam et →
    </a>
</div>
@endif

<div class="sb-hero">
    <span class="sb-hero-badge">✨ AKILLI EŞLEŞTIRME</span>
    <h1 class="sb-hero-title">Almanya'da sana en uygun programı bul</h1>
    <p class="sb-hero-subtitle">5 dakikalık akıllı sihirbazımız 13.000+ Almanya programı arasından profil ve hedeflerine en uygun olanları sıralar.</p>

    <a href="{{ route('uni-match.start') }}" class="sb-btn sb-btn-primary sb-hero-cta">
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

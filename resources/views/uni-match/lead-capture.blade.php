@extends('uni-match.layout')

@php
    $effectiveStep = $effectiveStep ?? min((int) $response->current_step, 19);
    $isCompleted   = $isCompleted ?? false;
    $pdfRequested  = $pdfRequested ?? false;
@endphp

@section('title', ($isCompleted ? 'Sonuçların hazır' : 'Sonuçlarını kaybetme') . ' — UniMatch')

@section('content')
<div class="sb-progress-wrap">
    <div class="sb-progress-meta">
        @if($isCompleted)
            <span>✓ Wizard tamamlandı</span>
            <span>%100</span>
        @else
            <span>{{ $effectiveStep }} / 19 adım</span>
            <span>%{{ $progress }} tamamlandı</span>
        @endif
    </div>
    <div class="sb-progress-bar">
        <div class="sb-progress-fill" style="width: {{ $progress }}%;"></div>
    </div>
</div>

<div class="sb-card">
    <div style="text-align: center; padding: 8px 0 16px;">
        @if($isCompleted)
            <div style="font-size: 42px; margin-bottom: 6px;">🎉</div>
            <h1 class="sb-title" style="font-size: 22px; line-height: 1.3;">
                @if($pdfRequested)
                    PDF için son bir adım
                @else
                    Wizard tamamlandı — <strong style="color: #7e58bf;">son bir adım</strong> kaldı
                @endif
            </h1>
            <p class="sb-subtitle" style="font-size: 14px; max-width: 480px; margin: 8px auto 0;">
                @if($pdfRequested)
                    Sonuçlarını PDF olarak alabilmen için iletişim bilgini bırak — anında indir, e-posta ile de gönderelim.
                @else
                    Sana özel program listesini <strong>kaybetmemek</strong> için iletişim bilgini bırak — sonuçların e-posta veya WhatsApp ile sana ulaşır, danışmanın da seninle iletişime geçer.
                @endif
            </p>
        @else
            <div style="font-size: 42px; margin-bottom: 6px;">📬</div>
            <h1 class="sb-title" style="font-size: 22px; line-height: 1.3;">
                Tebrikler — wizard'ın <strong style="color: #7e58bf;">yarısını</strong> tamamladın!
            </h1>
            <p class="sb-subtitle" style="font-size: 14px; max-width: 460px; margin: 8px auto 0;">
                Sonuçlarını <strong>kaybetmemek</strong> için bilgilerini bırak — sana özel program listesi e-posta ile veya WhatsApp'tan ulaşır.
            </p>
        @endif
    </div>

    <form method="POST" action="{{ route('uni-match.lead-capture.submit') }}" id="leadForm">
        @csrf

        <label style="display: block; font-size: 12.5px; font-weight: 600; color: #6b5894; margin: 14px 0 6px;">
            Adın <span style="color: #9c8bb9; font-weight: 400;">(opsiyonel)</span>
        </label>
        <input type="text" name="first_name" maxlength="80"
               placeholder="Örn. Ayşe"
               style="width: 100%; padding: 12px 14px; font-size: 14px; border: 2px solid #d4c5e8; border-radius: 10px; background: #fff; outline: none; font-family: inherit;">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px;">
            <div>
                <label style="display: block; font-size: 12.5px; font-weight: 600; color: #6b5894; margin-bottom: 6px;">
                    📧 E-posta
                </label>
                <input type="email" name="email" maxlength="200"
                       placeholder="ayse@gmail.com"
                       style="width: 100%; padding: 12px 14px; font-size: 14px; border: 2px solid #d4c5e8; border-radius: 10px; background: #fff; outline: none; font-family: inherit;">
            </div>
            <div>
                <label style="display: block; font-size: 12.5px; font-weight: 600; color: #6b5894; margin-bottom: 6px;">
                    📱 WhatsApp <span style="color: #9c8bb9; font-weight: 400;">(opsiyonel)</span>
                </label>
                <input type="tel" name="phone" maxlength="30"
                       placeholder="+90 555 ..."
                       style="width: 100%; padding: 12px 14px; font-size: 14px; border: 2px solid #d4c5e8; border-radius: 10px; background: #fff; outline: none; font-family: inherit;">
            </div>
        </div>
        <div style="font-size: 11.5px; color: #9c8bb9; margin-top: 6px; text-align: center;">
            En az birini doldur — ikisi de işine yarar.
        </div>

        <label style="display: flex; align-items: flex-start; gap: 10px; margin-top: 18px; cursor: pointer; padding: 10px 12px; background: #f9f6fc; border-radius: 8px; border: 1px solid #ede5f7;">
            <input type="checkbox" name="consent" value="1" style="margin-top: 3px; width: 16px; height: 16px; accent-color: #7e58bf;">
            <span style="font-size: 12px; color: #6b5894; line-height: 1.5;">
                {{ config('brand.name') }}'nin Almanya eğitim danışmanlığı ile ilgili bilgilendirme e-postaları göndermesini kabul ediyorum.
                İstediğim zaman çıkarabilirim. <a href="/privacy" target="_blank" style="color: #7e58bf;">Gizlilik politikası</a>
            </span>
        </label>

        <div class="sb-nav" style="margin-top: 24px;">
            <a href="{{ route('uni-match.lead-capture.skip') }}"
               style="font-size: 13px; color: #9c8bb9; text-decoration: none; padding: 10px 14px;">
                @if($isCompleted)
                    Atla, sonuçları ekranda göster
                @else
                    Atla, sonuçları sadece ekranda görmek istiyorum
                @endif
            </a>
            <button type="submit" class="sb-btn sb-btn-primary">
                @if($pdfRequested)
                    PDF'i indir
                @else
                    Devam Et
                @endif
                <span style="font-size: 16px;">→</span>
            </button>
        </div>

        <div style="margin-top: 16px; padding: 12px 14px; background: #fff8e1; border-radius: 8px; border-left: 3px solid #f59e0b; font-size: 11.5px; color: #78350f; line-height: 1.5;">
            @if($isCompleted)
                <strong>🎁 Bonus:</strong> İletişim bilgini bırakırsan sana özel "10 Almanya Programa Başvuru Rehberi" PDF'ini ve danışmanın inceleyeceği detaylı analizi göndereceğiz.
            @else
                <strong>🎁 Bonus:</strong> Bilgilerini bırakırsan sana özel "10 Almanya Programa Başvuru Rehberi" PDF'ini de göndereceğiz.
            @endif
        </div>
    </form>
</div>
@endsection

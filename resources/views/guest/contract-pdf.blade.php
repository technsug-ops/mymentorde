<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sozlesme - {{ $guest->first_name ?? '' }} {{ $guest->last_name ?? '' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1a1a1a; line-height: 1.7; margin: 0; padding: 20px 30px; }
        .header { text-align: center; padding-bottom: 14px; border-bottom: 2px solid #333; margin-bottom: 20px; }
        .header h1 { font-size: 16pt; font-weight: 700; letter-spacing: 1px; margin: 0; }
        .header p { font-size: 9pt; color: #666; margin: 4px 0 0; }
        .contract-body { white-space: pre-wrap; font-size: 10pt; line-height: 1.7; }
        .annex { margin-top: 24px; page-break-before: auto; }
        .annex h3 { font-size: 11pt; font-weight: 700; border-bottom: 1px solid #999; padding-bottom: 4px; margin: 0 0 8px; }
        .annex-body { white-space: pre-wrap; font-size: 9pt; line-height: 1.6; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 8pt; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('brand.name', 'MentorDE') }}</h1>
        <p>{{ config('brand.tagline', 'Yurt Disi Egitim Danismanligi') }}</p>
    </div>

    <div class="contract-body">{{ $contractText ?? '' }}</div>

    @if(($annexKvkk ?? '') !== '')
    <div class="annex">
        <h3>Ek-1 - KVKK Aydinlatma Metni</h3>
        <div class="annex-body">{{ $annexKvkk }}</div>
    </div>
    @endif

    @if(($annexCommit ?? '') !== '')
    <div class="annex">
        <h3>Ek-2 - Taahhutname</h3>
        <div class="annex-body">{{ $annexCommit }}</div>
    </div>
    @endif

    @if(!empty($guest->contract_digital_signed_at))
    <div style="margin-top:20px;padding:12px;border:1px solid #333;border-radius:4px;">
        <strong>Dijital Imza Bilgisi</strong><br>
        Imzalayan: {{ $guest->first_name ?? '' }} {{ $guest->last_name ?? '' }}<br>
        Tarih: {{ optional($guest->contract_digital_signed_at)->format('d.m.Y H:i') }}<br>
        Yontem: Dijital imza (e-imza)
    </div>
    @endif

    <div class="footer">
        Bu belge {{ config('brand.name', 'MentorDE') }} sistemi uzerinden olusturulmustur. - {{ now()->format('d.m.Y') }}
    </div>
</body>
</html>

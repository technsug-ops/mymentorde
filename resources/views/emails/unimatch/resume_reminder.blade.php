<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>UniMatch'a devam et</title>
</head>
<body style="margin:0;padding:0;background:#f4f2ee;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<div style="max-width:600px;margin:32px auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #ede5f7;">
  <div style="background:linear-gradient(135deg,#7e58bf,#a07ed9);padding:28px 32px;color:#fff;">
    <div style="font-size:32px;margin-bottom:6px;">⏸️</div>
    <h1 style="margin:0;font-size:22px;font-weight:700;">Yarıda kaldın — kaldığın yerden devam et</h1>
    <p style="margin:6px 0 0;opacity:.92;font-size:14px;">UniMatch sihirbazını <strong>%{{ $progressPct }}</strong> tamamladın</p>
  </div>
  <div style="padding:28px 32px;color:#1a1a1a;font-size:15px;line-height:1.7;">
    <p>Merhaba {{ $firstName }},</p>
    <p>Almanya'daki en uygun programını bulmak için UniMatch sihirbazını başlattın ve <strong>{{ $currentStep }}/19 adıma</strong> kadar ilerledin. Sonra ara verdin.</p>

    <div style="background:#faf7fd;border-radius:10px;padding:18px;margin:20px 0;text-align:center;">
      <div style="font-size:13px;color:#6b5894;margin-bottom:10px;font-weight:600;">İlerlemen kayıtlı:</div>
      <div style="background:#e2e8f0;border-radius:8px;height:14px;overflow:hidden;margin:0 auto;max-width:400px;">
        <div style="background:linear-gradient(90deg,#7e58bf,#a07ed9);height:100%;width:{{ $progressPct }}%;"></div>
      </div>
      <div style="font-size:18px;font-weight:700;color:#7e58bf;margin-top:10px;">%{{ $progressPct }} tamamlandı</div>
      <div style="font-size:12.5px;color:#9c8bb9;margin-top:4px;">{{ $currentStep }}/19 adım</div>
    </div>

    <p>Cevapların güvenli — sıfırdan başlamana gerek yok. Tek tıkla kaldığın yerden devam edebilirsin:</p>

    <p style="text-align:center;margin:28px 0;">
      <a href="{{ $resumeUrl }}"
         style="display:inline-block;background:linear-gradient(135deg,#7e58bf,#a07ed9);color:#fff;padding:16px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:16px;">
        Wizard'a Devam Et →
      </a>
    </p>

    <p style="font-size:13px;color:#6b5894;margin-bottom:0;">
      Bitirmen <strong>~{{ max(1, (int) ceil((19 - $currentStep) / 4)) }} dakika</strong> sürer. Sonunda sana özel <strong>10 program önerisi</strong> ve detaylı yol haritası seni bekliyor.
    </p>
  </div>
  <div style="background:#f9f6fc;padding:18px 32px;font-size:11.5px;color:#9c8bb9;text-align:center;border-top:1px solid #ede5f7;">
    Bu e-postayı UniMatch'a girip onay verdiğin için aldın.<br>
    <a href="{{ config('app.url') }}/privacy" style="color:#7e58bf;">Gizlilik politikası</a>
  </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Ücretsiz 30dk danışmanlık görüşmesi</title>
</head>
<body style="margin:0;padding:0;background:#f4f2ee;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<div style="max-width:600px;margin:32px auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #ede5f7;">
  <div style="background:linear-gradient(135deg,#7e58bf,#a07ed9);padding:28px 32px;color:#fff;">
    <div style="font-size:32px;margin-bottom:6px;">💬</div>
    <h1 style="margin:0;font-size:22px;font-weight:700;">Son şans — ücretsiz görüşme</h1>
    <p style="margin:6px 0 0;opacity:.92;font-size:14px;">Danışmanın seninle 30 dakikalık görüşme rica ediyor</p>
  </div>
  <div style="padding:28px 32px;color:#1a1a1a;font-size:15px;line-height:1.7;">
    <p>Merhaba {{ $firstName }},</p>
    <p>2 hafta önce UniMatch sihirbazını tamamladın. Sonraki adım için karar vermek zor olabiliyor — bu yüzden danışmanlarımızdan biri seninle <strong>ücretsiz 30 dakikalık görüşme</strong> rica ediyor.</p>

    <div style="background:#faf7fd;border-radius:10px;padding:18px;margin:20px 0;">
      <p style="margin:0 0 10px;font-weight:700;color:#7e58bf;">Görüşmede ne olacak:</p>
      <ul style="margin:0;padding-left:20px;color:#1a1a1a;">
        <li style="margin-bottom:6px;">UniMatch sonuçlarındaki 10 program arasından sana en uygun 2-3 tanesi belirlenir</li>
        <li style="margin-bottom:6px;">Başvuru takvimi (APS, dil sınavı, Sperrkonto, vize) — 12 aylık plan</li>
        <li style="margin-bottom:6px;">Bütçe planlaması — toplam maliyet kalemleri</li>
        <li>Tüm sorularını sorabilirsin</li>
      </ul>
    </div>

    <p style="text-align:center;margin:28px 0;">
      <a href="{{ $returnUrl }}"
         style="display:inline-block;background:linear-gradient(135deg,#7e58bf,#a07ed9);color:#fff;padding:16px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:16px;">
        Ücretsiz Görüşmeyi Talep Et →
      </a>
    </p>

    <p style="font-size:13px;color:#6b5894;margin-bottom:16px;">
      Eğer bu son hatırlatmadır — bundan sonra başka mail göndermeyeceğiz.<br>
      <strong>Almanya yolculuğuna başlamaya hazır mısın?</strong>
    </p>

    <p style="font-size:12.5px;color:#9c8bb9;border-top:1px solid #ede5f7;padding-top:14px;">
      Eğer artık ilgilenmiyorsan, bu maili görmezden gel — bir sonraki kullanıcıya yer açacağız.
    </p>
  </div>
  <div style="background:#f9f6fc;padding:18px 32px;font-size:11.5px;color:#9c8bb9;text-align:center;border-top:1px solid #ede5f7;">
    Bu e-postayı UniMatch sihirbazını tamamladığında onay verdiğin için aldın.<br>
    <a href="{{ config('app.url') }}/privacy" style="color:#7e58bf;">Gizlilik politikası</a>
  </div>
</div>
</body>
</html>

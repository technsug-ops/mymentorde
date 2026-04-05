<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Randevu Hatırlatması</title>
<style>
  body { margin:0; padding:0; background:#f4f6fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width:600px; margin:32px auto; background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; }
  .header { background:#1a3c6b; padding:24px 32px; }
  .header h1 { margin:0; color:#ffffff; font-size:20px; font-weight:700; letter-spacing:0.5px; }
  .header p { margin:4px 0 0; color:#a8c4e8; font-size:13px; }
  .body { padding:32px; color:#1e293b; font-size:15px; line-height:1.7; }
  .body h2 { margin:0 0 16px; font-size:18px; color:#1a3c6b; }
  .info-box { background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:16px 20px; margin:16px 0; }
  .info-box p { margin:4px 0; font-size:14px; }
  .info-box strong { color:#0369a1; }
  .cta { display:inline-block; margin:20px 0 0; padding:12px 28px; background:#1a3c6b; color:#fff; border-radius:6px; text-decoration:none; font-weight:700; font-size:14px; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:16px 32px; color:#94a3b8; font-size:12px; text-align:center; }
  .footer a { color:#64748b; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>MentorDE</h1>
    <p>Almanya Danışmanlık Platformu</p>
  </div>
  <div class="body">
    <h2>📅 Randevu Hatırlatması</h2>
    <p>Yaklaşan bir randevunuz bulunmaktadır. Lütfen aşağıdaki bilgileri kontrol edin:</p>
    <div class="info-box">
      <p><strong>Tarih &amp; Saat:</strong> {{ $appointment->scheduled_at?->format('d.m.Y H:i') ?? '-' }}</p>
      @if($appointment->meeting_link)
      <p><strong>Toplantı Linki:</strong> <a href="{{ $appointment->meeting_link }}" style="color:#0369a1;">{{ $appointment->meeting_link }}</a></p>
      @endif
      @if($appointment->notes)
      <p><strong>Notlar:</strong> {{ $appointment->notes }}</p>
      @endif
    </div>
    <p>Randevuya zamanında katılmaya özen gösterin. Katılamayacaksanız lütfen önceden danışmanınızı bilgilendirin.</p>
    <a class="cta" href="{{ config('app.url') }}/student/appointments">Randevularımı Görüntüle</a>
  </div>
  <div class="footer">
    Bu e-posta MentorDE platformu tarafından otomatik olarak gönderilmiştir.<br>
    <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
  </div>
</div>
</body>
</html>

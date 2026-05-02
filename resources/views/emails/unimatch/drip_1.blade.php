<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Sana özel programlarını incele</title>
</head>
<body style="margin:0;padding:0;background:#f4f2ee;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<div style="max-width:600px;margin:32px auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #ede5f7;">
  <div style="background:linear-gradient(135deg,#7e58bf,#a07ed9);padding:28px 32px;color:#fff;">
    <div style="font-size:32px;margin-bottom:6px;">🎯</div>
    <h1 style="margin:0;font-size:22px;font-weight:700;">Sana özel 10 programı seçtik</h1>
    <p style="margin:6px 0 0;opacity:.92;font-size:14px;">UniMatch sihirbazı tamamlandı — sonuçların hazır</p>
  </div>
  <div style="padding:28px 32px;color:#1a1a1a;font-size:15px;line-height:1.7;">
    <p>Merhaba {{ $firstName }},</p>
    <p>3 gün önce <strong>{{ config('brand.name') }} UniMatch</strong> sihirbazını tamamladın. Sana en uygun 10 Almanya programını seçtik ama henüz incelemedin.</p>

    @if(! empty($recommendations))
      <p style="margin:24px 0 12px;font-weight:700;color:#6b5894;">İlk 3 önerin:</p>
      <div style="background:#faf7fd;border-radius:10px;padding:18px;border-left:3px solid #7e58bf;">
        @foreach($recommendations as $i => $rec)
          <div style="margin-bottom:{{ $i === count($recommendations) - 1 ? 0 : 14 }}px;{{ $i > 0 ? 'border-top:1px solid #ede5f7;padding-top:14px;' : '' }}">
            <div style="font-size:15px;font-weight:700;color:#1a1a1a;margin-bottom:2px;">{{ $rec['course_name'] ?? '?' }}</div>
            <div style="font-size:13px;color:#6b5894;">{{ $rec['university_name'] ?? '' }} @if(! empty($rec['location'])) · {{ $rec['location'] }} @endif</div>
          </div>
        @endforeach
      </div>
    @endif

    <p style="margin-top:24px;text-align:center;">
      <a href="{{ $returnUrl }}"
         style="display:inline-block;background:linear-gradient(135deg,#7e58bf,#a07ed9);color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;">
        Tüm 10 Programı İncele →
      </a>
    </p>

    <p style="margin-top:24px;font-size:13px;color:#6b5894;">
      İstersen {{ config('brand.name') }}'ye kayıt ol, danışmanın bu programlar arasından sana en uygun olanını birlikte değerlendirin.<br>
      <strong>%100 ücretsiz</strong> ilk görüşme.
    </p>
  </div>
  <div style="background:#f9f6fc;padding:18px 32px;font-size:11.5px;color:#9c8bb9;text-align:center;border-top:1px solid #ede5f7;">
    Bu e-postayı UniMatch sihirbazını tamamladığında bilgi paylaşımına onay verdiğin için aldın.<br>
    <a href="{{ config('app.url') }}/privacy" style="color:#7e58bf;">Gizlilik politikası</a>
  </div>
</div>
</body>
</html>

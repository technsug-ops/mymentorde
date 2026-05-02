<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>UniMatch Günlük Özet</title>
</head>
<body style="margin:0;padding:0;background:#f4f2ee;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<div style="max-width:680px;margin:32px auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #ede5f7;">
  <div style="background:linear-gradient(135deg,#7e58bf,#a07ed9);padding:28px 32px;color:#fff;">
    <div style="font-size:32px;margin-bottom:6px;">📊</div>
    <h1 style="margin:0;font-size:22px;font-weight:700;">UniMatch Günlük Özet</h1>
    <p style="margin:6px 0 0;opacity:.92;font-size:14px;">{{ $stats['date'] }}</p>
  </div>
  <div style="padding:28px 32px;color:#1a1a1a;font-size:15px;line-height:1.7;">
    <p>Merhaba {{ $firstName }},</p>
    <p>UniMatch wizard'ı önceki gün şu performansı verdi:</p>

    <div style="display:table;width:100%;margin:18px 0;border-collapse:collapse;">
      <div style="display:table-row;">
        <div style="display:table-cell;width:25%;padding:14px;background:#faf7fd;text-align:center;border-radius:8px 0 0 8px;border-right:1px solid #fff;">
          <div style="font-size:32px;font-weight:800;color:#7e58bf;line-height:1;">{{ $stats['started'] }}</div>
          <div style="font-size:11px;color:#6b5894;margin-top:4px;text-transform:uppercase;letter-spacing:.5px;">Başlatan</div>
        </div>
        <div style="display:table-cell;width:25%;padding:14px;background:#faf7fd;text-align:center;border-right:1px solid #fff;">
          <div style="font-size:32px;font-weight:800;color:#7e58bf;line-height:1;">{{ $stats['leads'] }}</div>
          <div style="font-size:11px;color:#6b5894;margin-top:4px;text-transform:uppercase;letter-spacing:.5px;">Lead</div>
        </div>
        <div style="display:table-cell;width:25%;padding:14px;background:#faf7fd;text-align:center;border-right:1px solid #fff;">
          <div style="font-size:32px;font-weight:800;color:#7e58bf;line-height:1;">{{ $stats['completed'] }}</div>
          <div style="font-size:11px;color:#6b5894;margin-top:4px;text-transform:uppercase;letter-spacing:.5px;">Tamamlanan</div>
        </div>
        <div style="display:table-cell;width:25%;padding:14px;background:#16a34a;color:#fff;text-align:center;border-radius:0 8px 8px 0;">
          <div style="font-size:32px;font-weight:800;line-height:1;">{{ $stats['converted'] }}</div>
          <div style="font-size:11px;margin-top:4px;text-transform:uppercase;letter-spacing:.5px;opacity:.9;">Kayıt</div>
        </div>
      </div>
    </div>

    @if(count($leads) > 0)
    <h2 style="font-size:15px;color:#7e58bf;margin:24px 0 10px;">📬 Yeni Leadler ({{ count($leads) }})</h2>
    <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
      <thead>
        <tr style="background:#f1f5f9;">
          <th style="padding:8px 10px;text-align:left;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;">İsim</th>
          <th style="padding:8px 10px;text-align:left;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;">İletişim</th>
          <th style="padding:8px 10px;text-align:left;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;">Adım</th>
          <th style="padding:8px 10px;text-align:left;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;">Convert</th>
        </tr>
      </thead>
      <tbody>
        @foreach($leads as $lead)
        <tr style="border-bottom:1px solid #ede5f7;">
          <td style="padding:8px 10px;font-weight:600;">{{ $lead->lead_first_name ?: '—' }}</td>
          <td style="padding:8px 10px;font-family:monospace;font-size:11.5px;">
            {{ $lead->lead_email ?: $lead->lead_phone ?: '—' }}
          </td>
          <td style="padding:8px 10px;">{{ $lead->current_step }}/19</td>
          <td style="padding:8px 10px;">
            @if($lead->converted_at)<span style="color:#16a34a;font-weight:700;">✓</span>@else—@endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    <p style="margin-top:24px;text-align:center;">
      <a href="{{ $funnelUrl }}"
         style="display:inline-block;background:linear-gradient(135deg,#7e58bf,#a07ed9);color:#fff;padding:12px 28px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;">
        📊 Detaylı Funnel + CSV →
      </a>
    </p>

    <p style="margin-top:18px;font-size:12px;color:#9c8bb9;">
      İpucu: Tüm lead listesini CSV olarak indirip Excel'de açabilirsin.
    </p>
  </div>
  <div style="background:#f9f6fc;padding:18px 32px;font-size:11px;color:#9c8bb9;text-align:center;border-top:1px solid #ede5f7;">
    Bu rapor günlük 08:00'de otomatik gönderilir. {{ config('app.url') }}
  </div>
</div>
</body>
</html>

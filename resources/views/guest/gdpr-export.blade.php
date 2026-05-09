<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>MentorDE — Kişisel Verilerim ({{ $today }})</title>
<style>
* { box-sizing: border-box; }
body { font-family: -apple-system, 'Segoe UI', Arial, sans-serif; max-width: 880px; margin: 32px auto; padding: 0 24px; color: #1e293b; line-height: 1.55; }
header { border-bottom: 3px solid #1e40af; padding-bottom: 18px; margin-bottom: 28px; }
h1 { color: #1e40af; margin: 0 0 6px; font-size: 26px; }
.subtitle { color: #64748b; font-size: 13px; }
h2 { color: #0f172a; border-left: 4px solid #1e40af; padding-left: 10px; margin: 28px 0 12px; font-size: 18px; }
table { width: 100%; border-collapse: collapse; margin: 8px 0 16px; font-size: 13px; }
th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
th { background: #f1f5f9; font-weight: 600; color: #475569; width: 30%; }
td { color: #1e293b; word-break: break-word; }
.empty { color: #94a3b8; font-style: italic; padding: 12px; background: #f8fafc; border-radius: 6px; }
.meta-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px 18px; margin-bottom: 22px; font-size: 12.5px; color: #1e3a8a; }
.meta-card strong { color: #1e40af; }
ul.list-clean { list-style: none; padding: 0; margin: 0; }
ul.list-clean li { padding: 8px 12px; background: #f8fafc; border-radius: 6px; margin-bottom: 6px; font-size: 13px; }
.print-btn { background: #1e40af; color: #fff; border: none; border-radius: 8px; padding: 10px 18px; font-size: 13px; cursor: pointer; margin-top: 12px; }
@media print { .print-btn { display: none; } body { margin: 0; max-width: none; } }
.footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 11px; }
</style>
</head>
<body>

<header>
    <h1>📋 MentorDE — Kişisel Veri Raporu</h1>
    <div class="subtitle">{{ $data['meta']['gdpr_article'] ?? 'Madde 20' }} kapsamında oluşturuldu — {{ $today }}</div>
    <button class="print-btn" onclick="window.print()">🖨 Yazdır / PDF olarak kaydet</button>
</header>

<div class="meta-card">
    📅 <strong>Oluşturma:</strong> {{ $data['meta']['export_date'] ?? '—' }} ·
    📦 <strong>Format:</strong> {{ $data['meta']['data_format'] ?? 'HTML' }} ·
    🛡 <strong>Konu:</strong> {{ $data['meta']['subject'] ?? 'MentorDE Kişisel Veri' }}
</div>

@if(!empty($data['user_profile']))
<h2>👤 Kullanıcı Profili</h2>
<table>
    @foreach($data['user_profile'] as $key => $val)
    <tr>
        <th>{{ $key }}</th>
        <td>{{ is_scalar($val) ? $val : json_encode($val, JSON_UNESCAPED_UNICODE) }}</td>
    </tr>
    @endforeach
</table>
@endif

@if(!empty($data['application']))
<h2>📝 Başvuru Bilgileri</h2>
<table>
    @foreach($data['application'] as $key => $val)
    <tr>
        <th>{{ $key }}</th>
        <td>{{ is_scalar($val) || $val === null ? ($val ?? '—') : json_encode($val, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</td>
    </tr>
    @endforeach
</table>
@endif

@if(!empty($data['documents']))
<h2>📄 Belgelerim ({{ count($data['documents']) }})</h2>
<table>
    <tr><th style="width:25%">Kategori</th><th>Dosya Adı</th><th style="width:15%">Durum</th><th style="width:18%">Yüklendi</th></tr>
    @foreach($data['documents'] as $doc)
    <tr>
        <td>{{ $doc['category'] ?? '—' }}</td>
        <td>{{ $doc['filename'] ?? '—' }}</td>
        <td>{{ $doc['status'] ?? '—' }}</td>
        <td>{{ $doc['uploaded_at'] ?? '—' }}</td>
    </tr>
    @endforeach
</table>
@else
<h2>📄 Belgelerim</h2>
<div class="empty">Henüz belge yüklenmedi.</div>
@endif

@if(!empty($data['support_tickets']))
<h2>💬 Destek Talepleri ({{ count($data['support_tickets']) }})</h2>
<table>
    <tr><th>Konu</th><th style="width:15%">Durum</th><th style="width:15%">Öncelik</th><th style="width:18%">Oluşturuldu</th></tr>
    @foreach($data['support_tickets'] as $t)
    <tr>
        <td>{{ $t['subject'] ?? '—' }}</td>
        <td>{{ $t['status'] ?? '—' }}</td>
        <td>{{ $t['priority'] ?? '—' }}</td>
        <td>{{ $t['created_at'] ?? '—' }}</td>
    </tr>
    @endforeach
</table>
@endif

@if(!empty($data['consent_records']))
<h2>✅ Onay Kayıtları ({{ count($data['consent_records']) }})</h2>
<table>
    <tr><th>Onay Tipi</th><th style="width:15%">Versiyon</th><th style="width:25%">Tarih</th></tr>
    @foreach($data['consent_records'] as $c)
    <tr>
        <td>{{ $c['consent_type'] ?? '—' }}</td>
        <td>{{ $c['version'] ?? '—' }}</td>
        <td>{{ $c['accepted_at'] ?? '—' }}</td>
    </tr>
    @endforeach
</table>
@endif

@if(!empty($data['appointments']))
<h2>📅 Randevular ({{ count($data['appointments']) }})</h2>
<table>
    <tr><th>Konu</th><th style="width:25%">Tarih</th><th style="width:15%">Durum</th></tr>
    @foreach($data['appointments'] as $a)
    <tr>
        <td>{{ $a['title'] ?? '—' }}</td>
        <td>{{ $a['scheduled_at'] ?? '—' }}</td>
        <td>{{ $a['status'] ?? '—' }}</td>
    </tr>
    @endforeach
</table>
@endif

<div class="footer">
    Bu rapor MentorDE platformunda saklanan kişisel verilerinizin GDPR Madde 20 (Veri Taşınabilirliği) kapsamında bir özetidir.<br>
    Verilerinizi başka bir hizmete aktarmak veya yedeklemek için bu HTML dosyasını saklayabilir, yazıcıdan PDF olarak da çıktı alabilirsiniz.<br>
    Ham JSON formatı için: <code>/guest/gdpr/export?format=json</code> adresini kullanın.
</div>

</body>
</html>

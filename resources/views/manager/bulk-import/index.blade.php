@extends('manager.layouts.app')
@section('title', 'Toplu Kayıt İçeri Aktar')
@section('page_title', 'Toplu Kayıt İçeri Aktar')

@section('content')
<div class="page-header">
    <div>
        <h1>📥 Toplu Aday Öğrenci Aktarımı</h1>
        <div class="muted">Eski kayıtlarınızı CSV/Excel dosyasıyla sisteme aktarın</div>
    </div>
    <a href="{{ route('manager.bulk-import.template') }}" class="btn" style="background:#16a34a;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:600;">
        ⬇ CSV Şablonu İndir
    </a>
</div>

@if(session('status'))
<div style="margin-bottom:14px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif
@if($errors->any())
<div style="margin-bottom:14px;padding:10px 16px;border-radius:8px;background:#fee2e2;color:#991b1b;font-weight:600;font-size:13px;border:1px solid #fecaca;">
    @foreach($errors->all() as $err)<div>⚠ {{ $err }}</div>@endforeach
</div>
@endif

{{-- Kullanım Kılavuzu --}}
<div class="card" style="padding:16px;margin-bottom:16px;border-left:4px solid #3b82f6;">
    <div class="card-title">📘 Nasıl Kullanılır?</div>
    <ol style="font-size:13px;line-height:1.9;padding-left:20px;margin:8px 0 0;">
        <li>Yukarıdan <strong>CSV Şablonu İndir</strong> butonuna tıklayın.</li>
        <li>İndirilen dosyayı Excel ile açın (UTF-8 uyumlu), eski kayıtlarınızı her satıra ekleyin.</li>
        <li><strong>Zorunlu alanlar:</strong> <code>first_name</code>, <code>last_name</code>, <code>email</code>.</li>
        <li>Opsiyonel: <code>phone</code>, <code>birth_date</code> (YYYY-MM-DD), <code>gender</code> (male/female/not_specified), <code>dealer_code</code>, <code>application_country</code>, <code>notes</code> vb.</li>
        <li>Dosyayı <strong>CSV (UTF-8)</strong> olarak kaydedin ve aşağıdan yükleyin.</li>
        <li>Önizleme ekranında hataları kontrol edip <strong>Onayla & Kaydet</strong> butonuna basın.</li>
    </ol>
</div>

@if(!$preview)
{{-- Upload Form --}}
<div class="card" style="padding:20px;">
    <form method="POST" action="{{ route('manager.bulk-import.preview') }}" enctype="multipart/form-data">
        @csrf
        <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">CSV Dosyası Seçin (maks 4 MB)</label>
        <input type="file" name="file" accept=".csv,.txt" required
               style="display:block;width:100%;padding:12px;border:2px dashed var(--u-line);border-radius:8px;background:var(--u-bg);cursor:pointer;font-size:13px;">
        <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;">
            <span class="muted" style="font-size:12px;">Virgül (,) veya noktalı virgül (;) ayraçlı CSV desteklenir.</span>
            <button type="submit" class="btn" style="background:#1e40af;color:#fff;padding:10px 20px;border-radius:8px;font-weight:600;">🔍 Önizle</button>
        </div>
    </form>
</div>
@else
{{-- Preview Results --}}
<div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
    <div style="padding:14px 18px;background:linear-gradient(to right, #eff6ff, transparent);border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <div>
            <div style="font-size:14px;font-weight:700;color:#1e40af;">🧪 Önizleme Sonuçları ({{ $preview['total'] }} satır)</div>
            <div class="muted" style="font-size:11px;">Yüklendi: {{ $preview['uploaded_at'] }}</div>
        </div>
        <div style="display:flex;gap:18px;font-size:13px;font-weight:700;">
            <span style="color:#16a34a;">✓ {{ $preview['ok'] }} geçerli</span>
            @if($preview['err'] > 0)<span style="color:#dc2626;">⚠ {{ $preview['err'] }} hatalı</span>@endif
            @if($preview['duplicates'] > 0)<span style="color:#d97706;">↪ {{ $preview['duplicates'] }} mükerrer</span>@endif
        </div>
    </div>

    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead style="background:var(--u-bg);">
            <tr>
                <th style="padding:8px 10px;text-align:center;width:50px;">Satır</th>
                <th style="padding:8px 10px;text-align:left;">Ad Soyad</th>
                <th style="padding:8px 10px;text-align:left;">Email</th>
                <th style="padding:8px 10px;text-align:left;">Telefon</th>
                <th style="padding:8px 10px;text-align:left;">Ülke</th>
                <th style="padding:8px 10px;text-align:center;">Durum</th>
            </tr>
        </thead>
        <tbody>
        @foreach($preview['rows'] as $item)
            @php $hasErr = !empty($item['errors']); @endphp
            <tr style="border-top:1px solid var(--u-line);background:{{ $hasErr ? 'rgba(239,68,68,.04)' : 'transparent' }};">
                <td style="padding:8px 10px;text-align:center;font-family:monospace;color:var(--u-muted);">{{ $item['line'] }}</td>
                <td style="padding:8px 10px;font-weight:600;">{{ $item['data']['first_name'] ?? '—' }} {{ $item['data']['last_name'] ?? '' }}</td>
                <td style="padding:8px 10px;font-family:monospace;font-size:11px;">{{ $item['data']['email'] ?? '—' }}</td>
                <td style="padding:8px 10px;font-size:11px;">{{ $item['data']['phone'] ?? '—' }}</td>
                <td style="padding:8px 10px;font-size:11px;">{{ $item['data']['application_country'] ?? '—' }}</td>
                <td style="padding:8px 10px;text-align:center;">
                    @if($item['duplicate'])
                        <span style="background:#fef3c7;color:#92400e;padding:3px 9px;border-radius:4px;font-size:11px;font-weight:700;">↪ MÜKERRER</span>
                    @elseif($hasErr)
                        <span style="background:#fee2e2;color:#991b1b;padding:3px 9px;border-radius:4px;font-size:11px;font-weight:700;" title="{{ implode(' | ', $item['errors']) }}">⚠ HATA</span>
                    @else
                        <span style="background:#dcfce7;color:#166534;padding:3px 9px;border-radius:4px;font-size:11px;font-weight:700;">✓ GEÇERLİ</span>
                    @endif
                </td>
            </tr>
            @if($hasErr)
            <tr style="background:rgba(239,68,68,.04);">
                <td></td>
                <td colspan="5" style="padding:4px 10px 8px;font-size:11px;color:#991b1b;">
                    @foreach($item['errors'] as $err)<div>⚠ {{ $err }}</div>@endforeach
                </td>
            </tr>
            @endif
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<div style="display:flex;gap:12px;justify-content:flex-end;">
    <form method="POST" action="{{ route('manager.bulk-import.reset') }}" style="display:inline;">
        @csrf
        <button type="submit" class="btn alt" style="padding:10px 20px;border-radius:8px;">↺ Temizle & Yeni Dosya</button>
    </form>
    @if($preview['ok'] > 0)
    <form method="POST" action="{{ route('manager.bulk-import.commit') }}" style="display:inline;"
          onsubmit="return confirm('{{ $preview['ok'] }} kayıt sisteme eklenecek. Devam edilsin mi?');">
        @csrf
        <button type="submit" class="btn" style="background:#16a34a;color:#fff;padding:10px 22px;border-radius:8px;font-weight:700;">
            ✓ Onayla & {{ $preview['ok'] }} Kaydı Ekle
        </button>
    </form>
    @endif
</div>
@endif
@endsection

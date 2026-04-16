@extends('manager.layouts.app')
@section('title', 'Şablon Önizleme — ' . $tpl->name)
@section('page_title', 'Şablon Önizleme')

@section('content')
<div class="page-header">
    <div>
        <h1>📄 {{ $tpl->name }}</h1>
        <div class="muted">
            {{ \App\Models\DocumentBuilderTemplate::$docTypeLabels[$tpl->doc_type] ?? $tpl->doc_type }} ·
            {{ strtoupper($tpl->language) }} · v{{ $tpl->version }}
            @if($tpl->is_default) · <span style="color:#854d0e;font-weight:700;">⭐ Varsayılan</span> @endif
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('manager.doc-templates.download', $tpl) }}" class="btn" style="background:#16a34a;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:600;">⬇ PDF İndir</a>
        <a href="{{ route('manager.doc-templates.edit', $tpl) }}" class="btn alt" style="padding:8px 14px;border-radius:8px;text-decoration:none;">✏️ Düzenle</a>
        <a href="{{ route('manager.doc-templates.index') }}" class="btn alt" style="padding:8px 14px;border-radius:8px;text-decoration:none;">← Geri</a>
    </div>
</div>

<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:12px;color:#1e3a8a;line-height:1.6;">
    ℹ️ <strong>Bilgi:</strong> Aşağıdaki önizleme örnek verilerle doldurulmuştur (Mustafa Yılmaz, TU Berlin vb.).
    Öğrenci belgesini oluştururken <code>{{ '{{first_name}}' }}</code> gibi placeholder'lar kendi bilgileriyle otomatik değiştirilir.
</div>

<div style="background:#fff;border:1px solid var(--u-line);border-radius:10px;padding:40px 50px;box-shadow:0 2px 8px rgba(0,0,0,.06);max-width:800px;margin:0 auto;font-family:'Segoe UI', Tahoma, sans-serif;font-size:13px;line-height:1.75;color:#1a1a1a;">
    <pre style="white-space:pre-wrap;word-wrap:break-word;font-family:inherit;font-size:inherit;line-height:inherit;margin:0;">{{ $rendered }}</pre>
</div>
@endsection

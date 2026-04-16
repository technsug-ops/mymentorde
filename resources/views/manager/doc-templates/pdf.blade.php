<!DOCTYPE html>
<html lang="{{ $tpl->language }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $tpl->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.6; margin: 0; padding: 30px 40px; }
        .hdr { border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; }
        .title { font-size: 16px; font-weight: 700; color: #0f766e; margin-bottom: 2px; }
        .meta  { font-size: 9px; color: #6b7280; }
        .body  { white-space: pre-wrap; word-wrap: break-word; }
        .footer { margin-top: 28px; border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="hdr">
        <div class="title">{{ $tpl->name }}</div>
        <div class="meta">
            {{ \App\Models\DocumentBuilderTemplate::$docTypeLabels[$tpl->doc_type] ?? $tpl->doc_type }}
            &middot; {{ strtoupper($tpl->language) }}
            &middot; v{{ $tpl->version }}
            &middot; {{ now()->format('d.m.Y') }}
        </div>
    </div>
    <div class="body">{{ $rendered }}</div>
    <div class="footer">{{ config('brand.name', 'MentorDE') }} &middot; Şablon örnek verilerle doldurulmuştur</div>
</body>
</html>

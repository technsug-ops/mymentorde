<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 2.5cm 2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; line-height: 1.6; color: #1f2937; }
        h1 { font-size: 20pt; color: #0f172a; border-bottom: 2px solid #5b2e91; padding-bottom: 8px; margin-bottom: 20px; }
        h2 { font-size: 14pt; color: #0f172a; margin-top: 22px; margin-bottom: 10px; }
        h3 { font-size: 12pt; color: #0f172a; margin-top: 18px; margin-bottom: 8px; }
        p { margin: 8px 0; }
        strong { color: #0f172a; }
        ul { padding-left: 22px; }
        li { margin: 4px 0; }
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9pt; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    {!! $content !!}
    <div class="footer">
        {{ $aiLabsName ?? 'MentorDE AI Labs' }} — {{ now()->format('d.m.Y') }}
    </div>
</body>
</html>

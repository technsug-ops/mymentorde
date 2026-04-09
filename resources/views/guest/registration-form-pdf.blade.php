<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayit Formu - {{ $guest->first_name }} {{ $guest->last_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.6; margin: 0; padding: 20px 30px; }
        h1 { font-size: 18px; color: #0f766e; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #666; margin-bottom: 20px; }
        h2 { font-size: 13px; color: #0f766e; border-bottom: 2px solid #e2e8f0; padding-bottom: 4px; margin-top: 24px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        td { padding: 5px 8px; vertical-align: top; border-bottom: 1px solid #f1f5f9; }
        td.label { width: 35%; font-weight: 600; color: #475569; font-size: 10px; }
        td.value { color: #1a1a1a; }
        td.empty { color: #94a3b8; font-style: italic; }
        .footer { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <h1>Kayit Formu</h1>
    <div class="meta">
        {{ $guest->first_name }} {{ $guest->last_name }} &middot;
        #{{ $guest->id }} &middot;
        {{ now()->format('d.m.Y H:i') }}
    </div>

    @foreach($groups as $group)
        <h2>{{ $group['title'] ?? 'Bolum' }}</h2>
        <table>
            @foreach($group['fields'] ?? [] as $field)
                @php
                    $key = $field['key'] ?? '';
                    $val = trim((string) ($draft[$key] ?? ($guest->{$key} ?? '')));
                    $label = $field['label'] ?? $key;
                @endphp
                <tr>
                    <td class="label">{{ $label }}{{ !empty($field['required']) ? ' *' : '' }}</td>
                    <td class="{{ $val !== '' ? 'value' : 'empty' }}">{{ $val !== '' ? $val : '-' }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

    <div class="footer">
        {{ config('brand.name', 'MentorDE') }} &middot; Bu belge {{ now()->format('d.m.Y H:i') }} tarihinde olusturulmustur.
    </div>
</body>
</html>

<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Snapshot Print</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
        h1 { margin: 0 0 8px; font-size: 24px; }
        h2 { margin: 16px 0 8px; font-size: 18px; }
        .meta { color: #4b5563; font-size: 13px; margin-bottom: 5px; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin: 8px 0; }
        .card { border: 1px solid #d1d5db; border-radius: 8px; padding: 10px; }
        .k { color: #6b7280; font-size: 12px; }
        .v { font-size: 20px; font-weight: 700; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .actions { margin-bottom: 14px; }
        @media print { .actions { display: none; } body { margin: 10mm; font-size: 12px; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Yazdir / PDF Kaydet</button>
        <a href="/manager/dashboard/snapshot/{{ $report->id }}">Detaya Don</a>
    </div>

    <h1>{{ config('brand.name', 'MentorDE') }} Snapshot Raporu #{{ $report->id }}</h1>
    <div class="meta">Tip: {{ $report->report_type }} | Dönem: {{ optional($report->period_start)->toDateString() }} - {{ optional($report->period_end)->toDateString() }}</div>
    <div class="meta">Advisory: {{ $report->senior_email ?: 'tum advisoryler' }} | Oluşturan: {{ $report->created_by ?: '-' }} | Tarih: {{ $report->created_at }}</div>
    <div class="meta">Alicilar: {{ is_array($report->sent_to) && count($report->sent_to) ? implode(', ', $report->sent_to) : '-' }}</div>

    <h2>KPI</h2>
    <div class="grid">
        <div class="card"><div class="k">Aylik Gelir</div><div class="v">{{ number_format((float)($stats['monthly_revenue'] ?? 0), 2, ',', '.') }} EUR</div></div>
        <div class="card"><div class="k">Aktif Öğrenci</div><div class="v">{{ (int)($stats['active_students'] ?? 0) }}</div></div>
        <div class="card"><div class="k">Risk</div><div class="v">{{ strtoupper((string)($stats['risk_level'] ?? 'good')) }} ({{ (int)($stats['risk_score'] ?? 0) }})</div></div>
    </div>

    <h2>Funnel</h2>
    <table>
        <thead><tr><th>Adım</th><th>Adet</th><th>Oran %</th></tr></thead>
        <tbody>
            @forelse ($funnel as $row)
                <tr>
                    <td>{{ $row['label'] ?? '-' }}</td>
                    <td>{{ $row['count'] ?? 0 }}</td>
                    <td>{{ number_format((float)($row['rate'] ?? 0), 1, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Funnel verisi yok.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Trend</h2>
    <table>
        <thead><tr><th>Ay</th><th>Gelir</th><th>Approval</th></tr></thead>
        <tbody>
            @forelse ($trend as $row)
                <tr>
                    <td>{{ $row['label'] ?? '-' }}</td>
                    <td>{{ number_format((float)($row['revenue'] ?? 0), 2, ',', '.') }} EUR</td>
                    <td>{{ (int)($row['approval_count'] ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Trend verisi yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

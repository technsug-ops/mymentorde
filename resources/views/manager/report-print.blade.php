<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manager Report Print</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
        h1 { margin: 0 0 8px; font-size: 24px; }
        h2 { margin: 18px 0 8px; font-size: 18px; }
        .meta { color: #4b5563; font-size: 13px; margin-bottom: 8px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .card { border: 1px solid #d1d5db; border-radius: 8px; padding: 10px; }
        .k { color: #6b7280; font-size: 12px; }
        .v { font-size: 22px; font-weight: 700; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .actions { margin-bottom: 14px; }
        .sign-block { margin-top: 12px; break-inside: auto; }
        .sign-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .line-box { margin-top: 20px; border-top: 1px solid #111827; padding-top: 6px; font-size: 12px; }
        .notes-box { margin-top: 12px; }
        .notes-line { border-bottom: 1px dashed #9ca3af; height: 14px; margin-top: 4px; }
        @media print {
            .actions { display: none; }
            body { margin: 8mm; font-size: 12px; }
            h1 { font-size: 20px; margin: 0 0 6px; }
            h2 { font-size: 16px; margin: 12px 0 6px; }
            .meta { margin-bottom: 4px; font-size: 11px; }
            .grid { gap: 6px; }
            .card { padding: 8px; }
            .k { font-size: 11px; }
            .v { font-size: 18px; }
            table { font-size: 11px; }
            th, td { padding: 4px 6px; }
            .sign-block { margin-top: 8px; }
            .sign-grid { gap: 12px; }
            .line-box { margin-top: 12px; font-size: 11px; }
            .notes-line { height: 10px; margin-top: 3px; }
            .notes-box .notes-line:nth-of-type(n+3) { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Yazdir / PDF Kaydet</button>
        <a href="/manager/dashboard">Dashboard'a Don</a>
    </div>

    <h1>{{ config('brand.name', 'MentorDE') }} Manager Raporu</h1>
    <div class="meta">Dönem: {{ $filters['start_date'] }} - {{ $filters['end_date'] }} | Advisory: {{ $filters['senior_email'] !== '' ? $filters['senior_email'] : 'tum advisoryler' }}</div>
    <div class="meta">Uretim zamani: {{ $generatedAt }}</div>
    <div class="meta">Risk: {{ strtoupper($stats['risk_level']) }} ({{ $stats['risk_score'] }}) | pending %{{ number_format($stats['risk_breakdown']['pending_rate'], 1, ',', '.') }} | overdue %{{ number_format($stats['risk_breakdown']['overdue_rate'], 1, ',', '.') }} | tahsilat riski %{{ number_format($stats['risk_breakdown']['collection_rate'], 1, ',', '.') }}</div>

    <h2>KPI Ozeti</h2>
    <div class="grid">
        <div class="card"><div class="k">Aylik Gelir</div><div class="v">{{ number_format($stats['monthly_revenue'], 2, ',', '.') }} EUR</div></div>
        <div class="card"><div class="k">Aktif Öğrenci</div><div class="v">{{ $stats['active_students'] }}</div></div>
        <div class="card"><div class="k">Pending Approvals</div><div class="v">{{ $stats['pending_approvals'] }}</div></div>
        <div class="card"><div class="k">Acik Tahsilat</div><div class="v">{{ number_format($stats['open_pending_amount'], 2, ',', '.') }} EUR</div></div>
        <div class="card"><div class="k">Dönüşüm Orani</div><div class="v">%{{ number_format($stats['conversion_rate'], 1, ',', '.') }}</div></div>
        <div class="card"><div class="k">Risk Skoru</div><div class="v">{{ $stats['risk_score'] }}</div></div>
    </div>

    <h2>Funnel</h2>
    <table>
        <thead>
            <tr><th>Adım</th><th>Adet</th><th>Oran %</th></tr>
        </thead>
        <tbody>
            @foreach ($funnel as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['count'] }}</td>
                    <td>{{ number_format($row['rate'], 1, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Gelir & Approval Trendi</h2>
    <table>
        <thead>
            <tr><th>Ay</th><th>Gelir (EUR)</th><th>Approval</th></tr>
        </thead>
        <tbody>
            @forelse ($trend as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ number_format($row['revenue'], 2, ',', '.') }}</td>
                    <td>{{ $row['approval_count'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Trend verisi yok</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Advisory Performans</h2>
    <table>
        <thead>
            <tr><th>Advisory</th><th>Email</th><th>Cozulen Onay</th><th>Yazilan Not</th><th>Son Aktivite</th></tr>
        </thead>
        <tbody>
            @forelse ($seniorPerformance as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['email'] }}</td>
                    <td>{{ $row['resolved_approvals'] }}</td>
                    <td>{{ $row['notes_written'] }}</td>
                    <td>{{ $row['last_action_at'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Advisory verisi yok</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Onay Bekleyenler</h2>
    <table>
        <thead>
            <tr><th>ID</th><th>Rule</th><th>Öğrenci</th><th>Field</th><th>Olusma</th></tr>
        </thead>
        <tbody>
            @forelse ($pendingApprovals as $row)
                <tr>
                    <td>#{{ $row->id }}</td>
                    <td>{{ $row->rule_id }}</td>
                    <td>{{ $row->student_id ?: '-' }}</td>
                    <td>{{ $row->triggered_field }}</td>
                    <td>{{ $row->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Pending approval yok</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Geciken Outcome Kayıtlari</h2>
    <table>
        <thead>
            <tr><th>ID</th><th>Öğrenci</th><th>Step</th><th>Outcome</th><th>Deadline</th><th>Added By</th></tr>
        </thead>
        <tbody>
            @forelse ($overdueOutcomes as $row)
                <tr>
                    <td>#{{ $row->id }}</td>
                    <td>{{ $row->student_id }}</td>
                    <td>{{ $row->process_step }}</td>
                    <td>{{ $row->outcome_type }}</td>
                    <td>{{ $row->deadline }}</td>
                    <td>{{ $row->added_by ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Geciken outcome yok</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="sign-block">
        <h2>Imza / Not Alani</h2>
        <div class="sign-grid">
            <div>
                <div class="line-box">Hazirlayan (Ad Soyad / Imza / Tarih)</div>
            </div>
            <div>
                <div class="line-box">Onaylayan (Ad Soyad / Imza / Tarih)</div>
            </div>
        </div>
        <div class="notes-box">
            <div style="font-size:var(--tx-xs);color:#4b5563;">Notlar</div>
            <div class="notes-line"></div>
            <div class="notes-line"></div>
            <div class="notes-line"></div>
            <div class="notes-line"></div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior Performans Raporu</title>
    <style>
        body { font-family: Arial, sans-serif; color:#1d2a3a; margin:24px; }
        .top { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:16px; }
        h1 { margin:0 0 6px; font-size:22px; }
        .muted { color:#5b6b82; font-size:12px; }
        .grid { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin:14px 0; }
        .card { border:1px solid #d8e2f0; border-radius:10px; padding:12px; }
        .label { color:#5b6b82; font-size:12px; margin-bottom:4px; }
        .value { font-size:26px; font-weight:700; color:#0d3b79; }
        .section { margin-top:16px; }
        .section h2 { margin:0 0 8px; font-size:16px; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { border:1px solid #d8e2f0; padding:8px; text-align:left; }
        th { background:#f5f8fd; }
        .pill { display:inline-block; padding:2px 8px; border-radius:999px; border:1px solid #ccd8eb; font-size:12px; }
        .bar { height:8px; background:#e8eef8; border-radius:999px; overflow:hidden; }
        .bar > span { display:block; height:100%; background:#1d66d6; }
        @media print {
            body { margin:10mm; }
            .noprint { display:none !important; }
        }
    </style>
</head>
<body>
    <div class="top">
        <div>
            <h1>Senior Performans Raporu</h1>
            <div class="muted">Senior: {{ $reportSeniorEmail ?? '-' }}</div>
            <div class="muted">Uretim zamani: {{ $reportGeneratedAt ?? '-' }}</div>
        </div>
        <div class="noprint">
            <button onclick="window.print()">Yazdir / PDF</button>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <div class="label">Toplam Öğrenci</div>
            <div class="value">{{ $totalStudents ?? 0 }}</div>
            <div class="muted">Aktif: {{ $activeStudents ?? 0 }} | Arsiv: {{ $archivedStudents ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="label">Outcome Metrikleri</div>
            <div class="value">{{ $outcomeCount ?? 0 }}</div>
            <div class="muted">Bu ay: {{ $outcomeThisMonth ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="label">Bekleyen Belge Onayi</div>
            <div class="value">{{ $pendingDocApprovals ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="label">Bekleyen Randevu</div>
            <div class="value">{{ $pendingAppointments ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="label">Atanan Guest</div>
            <div class="value">{{ $guestCount ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="label">Guest Dönüşüm</div>
            <div class="value">{{ $guestConverted ?? 0 }}</div>
            <div class="muted">Oran: %{{ $conversionRate ?? 0 }}</div>
            <div class="bar" style="margin-top:6px;"><span style="width:{{ min(100, max(0, (int) ($conversionRate ?? 0))) }}%"></span></div>
        </div>
    </div>

    <div class="section">
        <h2>Risk Dagilimi</h2>
        <table>
            <thead>
                <tr>
                    <th>Risk</th>
                    <th>Öğrenci Sayisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($riskBreakdown ?? []) as $risk => $count)
                    <tr>
                        <td><span class="pill">{{ $risk }}</span></td>
                        <td>{{ (int) $count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">Risk verisi yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Outcome Adım Dagilimi</h2>
        <table>
            <thead>
                <tr>
                    <th>Adım</th>
                    <th>Kayıt</th>
                    <th>Oran</th>
                </tr>
            </thead>
            <tbody>
                @php $totalOutcomes = (int) ($outcomeCount ?? 0); @endphp
                @forelse(($outcomeByStep ?? collect()) as $row)
                    @php
                        $cnt = (int) ($row->cnt ?? 0);
                        $pct = $totalOutcomes > 0 ? round(($cnt / $totalOutcomes) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>{{ $row->process_step ?? '-' }}</td>
                        <td>{{ $cnt }}</td>
                        <td>%{{ $pct }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Outcome verisi yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>

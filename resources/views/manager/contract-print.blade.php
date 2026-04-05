<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sözleşme — {{ trim(($guest->first_name ?? '').' '.($guest->last_name ?? '')) }} — {{ $guest->converted_student_id ?: 'GST-'.$guest->id }}</title>
    <style>
        /* ── Genel ── */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 13px;
            color: #111827;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
        }

        /* ── Toolbar (ekranda görünür, print'te gizlenir) ── */
        .no-print {
            background: #1e3a5f;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .no-print strong { font-size: 14px; }
        .no-print .btn-print {
            background: #22c55e;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .no-print .btn-print:hover { background: #16a34a; }
        .no-print .btn-back {
            color: #93c5fd;
            text-decoration: none;
            font-size: 13px;
        }
        .no-print .status-badge {
            margin-left: auto;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: rgba(255,255,255,.15);
        }

        /* ── Sayfa kapsayıcı ── */
        .page-wrap {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.12);
            overflow: hidden;
        }

        /* ── Print header/footer HTML ── */
        .print-header { padding: 20px 32px 0; }
        .print-footer { padding: 0 32px 20px; border-top: 1px solid #e5e7eb; margin-top: 24px; }

        /* ── Sözleşme gövdesi ── */
        .contract-body {
            padding: 24px 32px;
        }
        .contract-meta {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .contract-text {
            white-space: pre-wrap;
            line-height: 1.75;
            font-size: 13px;
            color: #111827;
        }

        /* ── Ekler ── */
        .annex-block {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            page-break-before: always;
        }
        .annex-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e3a5f;
            margin: 0 0 12px;
        }
        .annex-text {
            white-space: pre-wrap;
            line-height: 1.75;
            font-size: 12px;
            color: #1f2937;
        }

        /* ── İmza bloğu ── */
        .signature-block {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .sig-side .sig-label { font-size: 12px; color: #6b7280; margin-bottom: 40px; }
        .sig-side .sig-line { border-top: 1px solid #374151; padding-top: 6px; font-size: 12px; color: #374151; }

        /* ── @print ── */
        @media print {
            body { background: #fff; font-size: 11pt; }
            .no-print { display: none !important; }
            .page-wrap { margin: 0; border-radius: 0; box-shadow: none; }
            .contract-body { padding: 12mm 18mm; }
            .print-header { padding: 10mm 18mm 0; }
            .print-footer { padding: 0 18mm 10mm; }
            .annex-block { page-break-before: always; }
            .signature-block { gap: 24mm; }
            @page { margin: 12mm; size: A4; }
        }
    </style>
</head>
<body>

{{-- ── Toolbar ── --}}
<div class="no-print">
    <a class="btn-back" href="{{ route('manager.contract-template.show', ['guest_id' => $guest->id, 'q' => $guest->converted_student_id ?: $guest->email]) }}">← Geri Dön</a>
    <strong>Sözleşme Yazdır / PDF</strong>
    <span>{{ trim(($guest->first_name ?? '').' '.($guest->last_name ?? '')) }} — {{ $guest->converted_student_id ?: 'GST-'.$guest->id }}</span>
    <button class="btn-print" onclick="window.print()">Yazdır / PDF Kaydet</button>
    <span class="status-badge">Durum: {{ $contractStatus }}</span>
</div>

<div class="page-wrap">

    {{-- ── Print Header HTML (firma branding) ── --}}
    @if(trim($printHeaderHtml) !== '')
        <div class="print-header">{!! $printHeaderHtml !!}</div>
    @endif

    <div class="contract-body">

        {{-- ── Meta bilgi satırı ── --}}
        <div class="contract-meta">
            <span>
                Öğrenci: <strong>{{ trim(($guest->first_name ?? '').' '.($guest->last_name ?? '')) }}</strong>
                &nbsp;|&nbsp; ID: <strong>{{ $guest->converted_student_id ?: 'GST-'.$guest->id }}</strong>
                &nbsp;|&nbsp; Email: {{ $guest->email }}
            </span>
            <span>
                @if($templateCode !== '') Şablon: {{ $templateCode }} @endif
                @if($generatedAt) &nbsp;|&nbsp; Oluşturulma: {{ \Carbon\Carbon::parse($generatedAt)->format('d.m.Y H:i') }} @endif
            </span>
        </div>

        {{-- ── Ana Sözleşme Metni ── --}}
        @if($contractText !== '')
            <div class="contract-text">{{ $contractText }}</div>
        @else
            <div style="text-align:center;padding:40px;color:#9ca3af;">
                Bu misafir için henüz sözleşme metni oluşturulmamış.<br>
                Önce "Sözleşmeyi Manuel Başlat" işlemini yapın.
            </div>
        @endif

        {{-- ── İmza Bloğu ── --}}
        @if($contractText !== '')
        <div class="signature-block">
            <div class="sig-side">
                <div class="sig-label">Danışmanlık Firması Yetkilisi</div>
                <div class="sig-line">Ad Soyad / İmza / Kaşe</div>
            </div>
            <div class="sig-side">
                <div class="sig-label">Öğrenci / Yasal Temsilci</div>
                <div class="sig-line">Ad Soyad / İmza &nbsp;&nbsp;&nbsp; Tarih: _____________</div>
            </div>
        </div>
        @endif

        {{-- ── EK-1: KVKK ── --}}
        @if($annexKvkkText !== '')
        <div class="annex-block">
            <div class="annex-title">EK-1 — KVKK Aydınlatma Metni</div>
            <div class="annex-text">{{ $annexKvkkText }}</div>
        </div>
        @endif

        {{-- ── EK-2: Taahhütname ── --}}
        @if($annexCommitText !== '')
        <div class="annex-block">
            <div class="annex-title">EK-2 — Hizmet Paketi Taahhütnamesi</div>
            <div class="annex-text">{{ $annexCommitText }}</div>
        </div>
        @endif

        {{-- ── EK-3: Ödeme Planı ── --}}
        @if($annexPaymentText !== '')
        <div class="annex-block">
            <div class="annex-title">EK-3 — Ödeme Planı</div>
            <div class="annex-text">{{ $annexPaymentText }}</div>
        </div>
        @endif

    </div>{{-- /contract-body --}}

    {{-- ── Print Footer HTML (firma branding) ── --}}
    @if(trim($printFooterHtml) !== '')
        <div class="print-footer">{!! $printFooterHtml !!}</div>
    @endif

</div>{{-- /page-wrap --}}

<script>
    // Toolbar'daki print butonuna klavye kısayolu
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            // Tarayıcının varsayılan print'ine bırak — CSS zaten handle eder
        }
    });
</script>

</body>
</html>

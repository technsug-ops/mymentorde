<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>UniMatch Sonuçlarım — {{ $brand }}</title>
    <style>
        @page { margin: 30px 28px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.5;
            margin: 0; padding: 0;
        }
        .header {
            background: #7e58bf;
            color: #fff;
            padding: 22px 24px;
            margin-bottom: 18px;
        }
        .header h1 { margin: 0 0 4px; font-size: 22px; font-weight: 700; }
        .header .meta { font-size: 11px; opacity: 0.9; }

        .intro {
            background: #faf7fd;
            border-left: 3px solid #7e58bf;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 11px;
        }

        .program-card {
            border: 1px solid #ede5f7;
            border-radius: 8px;
            margin-bottom: 12px;
            padding: 12px 14px;
            page-break-inside: avoid;
        }
        .program-card .rank {
            display: inline-block;
            background: rgba(126, 88, 191, 0.12);
            color: #7e58bf;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 8px;
            letter-spacing: 0.5px;
        }
        .program-card .title {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 4px 0 2px;
        }
        .program-card .uni {
            font-size: 11px;
            color: #6b5894;
            margin-bottom: 6px;
        }
        .program-card .meta {
            font-size: 10px;
            color: #444;
            margin-bottom: 4px;
        }
        .program-card .score {
            float: right;
            font-size: 22px;
            font-weight: 800;
            color: #7e58bf;
            margin-top: -28px;
        }
        .badge-uni-assist {
            display: inline-block;
            background: rgba(217, 119, 6, 0.12);
            color: #92400e;
            padding: 1px 7px;
            border-radius: 5px;
            font-size: 9px;
            font-weight: 700;
        }
        .badge-direkt {
            display: inline-block;
            background: rgba(5, 150, 105, 0.12);
            color: #065f46;
            padding: 1px 7px;
            border-radius: 5px;
            font-size: 9px;
            font-weight: 700;
        }
        .reasons {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #ede5f7;
            font-size: 10px;
            color: #6b5894;
        }

        .summary-section {
            background: #f4f2ee;
            padding: 14px;
            margin-top: 16px;
            border-radius: 6px;
            page-break-inside: avoid;
        }
        .summary-section h2 { font-size: 13px; color: #7e58bf; margin: 0 0 8px; }
        .answers-grid { display: table; width: 100%; }
        .answers-row { display: table-row; }
        .answers-row > div { display: table-cell; padding: 3px 6px; font-size: 10px; }
        .answers-row > div:first-child { color: #6b5894; font-weight: 600; width: 38%; }

        .footer {
            margin-top: 24px;
            padding-top: 14px;
            border-top: 2px solid #7e58bf;
            font-size: 9.5px;
            color: #6b5894;
            text-align: center;
        }
        .cta {
            background: #7e58bf;
            color: #fff;
            padding: 12px 16px;
            border-radius: 6px;
            text-align: center;
            margin: 16px 0;
            page-break-inside: avoid;
        }
        .cta a { color: #fff; text-decoration: underline; font-weight: 700; }
    </style>
</head>
<body>
<div class="header">
    <h1>🎯 UniMatch Sonuçlarım</h1>
    <div class="meta">
        {{ $brand }} · {{ $generatedAt->format('d.m.Y H:i') }} · {{ $firstName }}
    </div>
</div>

<div class="intro">
    <strong>Merhaba {{ $firstName }},</strong> Almanya'da sana en uygun {{ count($recommendations) }} programı seçtik.
    13.000+ program arasından profil ve hedeflerine göre <strong>9-faktör akıllı eşleştirme</strong> ile sıraladık.
    Her programın yanında "neden uyduğu" ve başvuru tipi (uni-assist veya direkt) belirtilmiştir.
</div>

@foreach($recommendations as $i => $rec)
    <div class="program-card">
        <span class="score">{{ $rec['match_score'] ?? '?' }}</span>
        <span class="rank">#{{ $i + 1 }} ÖNERİ · {{ $rec['match_score'] ?? '?' }}/100 MATCH</span>
        <div class="title">{{ $rec['course_name'] ?? '?' }}</div>
        <div class="uni">{{ $rec['university_name'] ?? '?' }}@if(! empty($rec['location'])) · {{ $rec['location'] }}@endif</div>
        <div class="meta">
            @if(! empty($rec['degree_specification'])){{ $rec['degree_specification'] }}@endif
            @if(! empty($rec['languages_raw'])) · {{ implode(', ', (array) $rec['languages_raw']) }}@endif
            @if(! empty($rec['duration_semesters'])) · {{ $rec['duration_semesters'] }} sömestr @endif
            @if(($rec['tuition_eur'] ?? null) === 0) · ✓ Ücretsiz
            @elseif(! empty($rec['tuition_eur'])) · {{ $rec['tuition_eur'] }} €/sömestr
            @endif
            ·
            @if(! empty($rec['is_uni_assist_member']))
                <span class="badge-uni-assist">📨 uni-assist</span>
            @else
                <span class="badge-direkt">✓ Direkt başvuru</span>
            @endif
        </div>
        @if(! empty($rec['reasons']))
        <div class="reasons">
            @foreach($rec['reasons'] as $reason)
                · {{ $reason }}<br>
            @endforeach
        </div>
        @endif
    </div>
@endforeach

<div class="summary-section">
    <h2>📋 Senin Profilin (Wizard Cevapların)</h2>
    @php
        $a = is_array($response->answers) ? $response->answers : [];
        $profileFields = [
            'target_degree' => 'Hedef Derece',
            'target_field' => 'Alan',
            'study_language' => 'Dil',
            'german_level' => 'Almanca Seviye',
            'english_level' => 'İngilizce Seviye',
            'gpa_range' => 'Diploma Notu',
            'finance_method' => 'Finansman',
            'monthly_budget' => 'Bütçe',
            'preferred_cities' => 'Tercih Şehirler',
            'has_aps' => 'APS Sertifikası',
        ];
    @endphp
    <div class="answers-grid">
        @foreach($profileFields as $key => $label)
            @if(isset($a[$key]) && $a[$key])
                <div class="answers-row">
                    <div>{{ $label }}:</div>
                    <div>{{ is_array($a[$key]) ? implode(', ', $a[$key]) : $a[$key] }}</div>
                </div>
            @endif
        @endforeach
    </div>
</div>

<div class="cta">
    <strong>Hadi adım atalım!</strong><br>
    {{ config('app.url') }} adresinden MentorDE'ye kayıt ol — danışmanın seninle ücretsiz görüşür ve bu programlar arasından sana en uygun olanını birlikte değerlendirin.
</div>

<div class="footer">
    Bu PDF {{ $brand }} UniMatch · {{ $generatedAt->format('d.m.Y') }} tarihinde oluşturuldu.<br>
    {{ config('app.url') }} · Tüm hakları saklıdır.
</div>

</body>
</html>

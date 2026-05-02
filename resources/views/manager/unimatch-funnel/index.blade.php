@extends('manager.layouts.app')

@section('title', 'UniMatch Funnel — ' . config('brand.name', 'MentorDE'))

@section('content')
<div class="page-header" style="margin-bottom:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
        <div>
            <h1 style="margin:0;font-size:22px;font-weight:700;color:#0f172a;">🎯 UniMatch Funnel Analytics</h1>
            <p style="margin:4px 0 0;font-size:13.5px;color:#64748b;">Wizard'da nerede kullanıcı kaybediyoruz · Son {{ $days }} gün</p>
        </div>
        <form method="GET" style="display:flex;gap:8px;">
            @foreach([7, 30, 90, 365] as $d)
                <a href="?days={{ $d }}"
                   style="padding:6px 14px;border-radius:8px;font-size:12.5px;font-weight:600;text-decoration:none;
                          {{ $days == $d ? 'background:#1e40af;color:#fff;' : 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;' }}">
                    {{ $d }} gün
                </a>
            @endforeach
        </form>
    </div>
</div>

{{-- ── Top KPI'lar ── --}}
<div class="grid4" style="margin-bottom:24px;">
    <div class="panel">
        <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;font-weight:600;">Wizard Başlatan</div>
        <div style="font-size:30px;font-weight:800;color:#0f172a;margin-top:6px;letter-spacing:-1px;">{{ number_format($totalStarted) }}</div>
        @if($avgDurationMin)
        <div style="font-size:11.5px;color:#64748b;margin-top:6px;">Ortalama süre: {{ $avgDurationMin }} dk</div>
        @endif
    </div>
    <div class="panel">
        <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;font-weight:600;">Lead Yakalandı</div>
        <div style="font-size:30px;font-weight:800;color:#7c3aed;margin-top:6px;letter-spacing:-1px;">{{ number_format($leadCaptured) }}</div>
        <div style="font-size:11.5px;color:#64748b;margin-top:6px;">
            @if($totalStarted > 0)
                %{{ round(($leadCaptured/$totalStarted)*100, 1) }} dönüşüm
            @endif
            · {{ $leadConsented }} marketing onay
        </div>
    </div>
    <div class="panel">
        <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;font-weight:600;">Sonuç Gördü</div>
        <div style="font-size:30px;font-weight:800;color:#0891b2;margin-top:6px;letter-spacing:-1px;">{{ number_format($resultViewed) }}</div>
        <div style="font-size:11.5px;color:#64748b;margin-top:6px;">
            @if($totalStarted > 0)
                %{{ round(($resultViewed/$totalStarted)*100, 1) }} bitiren
            @endif
        </div>
    </div>
    <div class="panel">
        <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;font-weight:600;">Conversion (Kayıt)</div>
        <div style="font-size:30px;font-weight:800;color:#16a34a;margin-top:6px;letter-spacing:-1px;">{{ number_format($converted) }}</div>
        <div style="font-size:11.5px;color:#64748b;margin-top:6px;">
            Lead → Convert: <strong>%{{ $leadToConvert }}</strong>
        </div>
    </div>
</div>

{{-- ── Step Funnel ── --}}
<div class="panel" style="margin-bottom:24px;">
    <h2 style="font-size:15px;font-weight:700;color:#0f172a;margin:0 0 16px;">📊 Step Drop-off Funnel</h2>

    @php
        $stepLabels = [
            1 => '1. Wizard Açıldı',
            6 => '6. Eğitim Seviyesi (kritik karar)',
            12 => '12. Yaşam Masrafı (lead gate öncesi)',
            13 => '13. Bütçe (lead gate sonrası)',
            17 => '17. APS Sertifikası (TR-spesifik)',
            19 => '19. Wizard Tamam',
        ];
    @endphp

    <div style="display:grid;gap:10px;">
        @foreach($stepFunnel as $step => $data)
            <div style="display:grid;grid-template-columns:240px 1fr 80px 100px;gap:12px;align-items:center;padding:10px 14px;background:#f8fafc;border-radius:8px;">
                <div style="font-size:13px;font-weight:600;color:#0f172a;">{{ $stepLabels[$step] ?? "Step {$step}" }}</div>
                <div style="background:#e2e8f0;border-radius:8px;height:28px;overflow:hidden;position:relative;">
                    <div style="background:linear-gradient(90deg,#1e40af,#3b82f6);height:100%;width:{{ $data['pct'] }}%;border-radius:8px;transition:width 0.3s;"></div>
                </div>
                <div style="font-size:14px;font-weight:700;color:#1e40af;text-align:right;">%{{ $data['pct'] }}</div>
                <div style="font-size:13px;color:#64748b;text-align:right;">{{ number_format($data['count']) }} kişi</div>
            </div>
        @endforeach
    </div>

    <div style="margin-top:14px;padding:12px 16px;background:#fef3c7;border-left:3px solid #d97706;border-radius:6px;font-size:12.5px;color:#78350f;">
        <strong>Yorum:</strong>
        @if($totalStarted < 10)
            Henüz yeterli veri yok ({{ $totalStarted }} kullanıcı). En az 50-100 başlatma sonrası anlamlı yorum yapılır.
        @else
            Step 17 (APS) genellikle en yüksek drop-off noktası — Türk öğrencileri APS hakkında bilgisiz olduğu için tereddüt eder.
            Lead gate (step 12 sonrası) %{{ $totalStarted > 0 ? round(($leadCaptured/$totalStarted)*100, 1) : 0 }} dönüşüm sağlıyor.
        @endif
    </div>
</div>

{{-- ── Son leadler ── --}}
@if(count($recentLeads) > 0)
<div class="panel">
    <h2 style="font-size:15px;font-weight:700;color:#0f172a;margin:0 0 16px;">📬 Son 10 Lead</h2>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f1f5f9;">
                <th style="padding:8px 12px;text-align:left;font-weight:700;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.3px;">İsim</th>
                <th style="padding:8px 12px;text-align:left;font-weight:700;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.3px;">İletişim</th>
                <th style="padding:8px 12px;text-align:left;font-weight:700;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.3px;">Onay</th>
                <th style="padding:8px 12px;text-align:left;font-weight:700;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.3px;">Bıraktığı Adım</th>
                <th style="padding:8px 12px;text-align:left;font-weight:700;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.3px;">Durum</th>
                <th style="padding:8px 12px;text-align:left;font-weight:700;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.3px;">Bırakma</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentLeads as $lead)
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:10px 12px;font-weight:600;">{{ $lead->lead_first_name ?: '—' }}</td>
                    <td style="padding:10px 12px;font-family:monospace;font-size:12px;">
                        @if($lead->lead_email)<div>📧 {{ $lead->lead_email }}</div>@endif
                        @if($lead->lead_phone)<div>📱 {{ $lead->lead_phone }}</div>@endif
                    </td>
                    <td style="padding:10px 12px;">
                        @if($lead->lead_consent_marketing)
                            <span style="background:rgba(22,163,74,0.12);color:#15803d;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">✓ Mailing</span>
                        @else
                            <span style="color:#94a3b8;font-size:11px;">Onay yok</span>
                        @endif
                    </td>
                    <td style="padding:10px 12px;">
                        @if($lead->completed_at)
                            <span style="color:#16a34a;font-weight:600;">19/19 ✓</span>
                        @else
                            <span style="color:#d97706;">{{ $lead->current_step }}/19</span>
                        @endif
                    </td>
                    <td style="padding:10px 12px;">
                        @if($lead->converted_at)
                            <span style="background:rgba(22,163,74,0.12);color:#15803d;padding:3px 9px;border-radius:5px;font-size:11px;font-weight:600;">✓ Kayıt oldu</span>
                        @elseif($lead->completed_at)
                            <span style="background:rgba(217,119,6,0.12);color:#b45309;padding:3px 9px;border-radius:5px;font-size:11px;font-weight:600;">Drip kuyruk</span>
                        @else
                            <span style="background:rgba(148,163,184,0.12);color:#475569;padding:3px 9px;border-radius:5px;font-size:11px;font-weight:600;">Wizard yarım</span>
                        @endif
                    </td>
                    <td style="padding:10px 12px;color:#64748b;font-size:12px;">
                        {{ $lead->lead_captured_at?->diffForHumans() ?? '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif
@endsection

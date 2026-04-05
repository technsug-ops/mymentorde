@extends('senior.layouts.app')

@section('title', 'Başvuru Detayı')
@section('page_title', 'Başvuru Detayı')

@section('content')

<div style="margin-bottom:12px;">
    <a href="javascript:history.back()" style="font-size:var(--tx-sm);color:#7c3aed;font-weight:700;text-decoration:none;">← Geri</a>
</div>

@if($guest->converted_to_student)
<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:10px 16px;margin-bottom:14px;font-size:var(--tx-sm);">
    <strong style="color:#16a34a;">✓ Öğrenciye Dönüştü</strong>
    @if($guest->converted_student_id)
        — <a href="/senior/process-tracking?student_id={{ $guest->converted_student_id }}" style="color:#7c3aed;font-weight:700;">{{ $guest->converted_student_id }} — Süreç Takibine Git →</a>
    @endif
</div>
@endif

<div class="grid2">

{{-- SOL: Kişisel Bilgiler --}}
<div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:16px 18px;margin-bottom:12px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:12px;">Kişisel Bilgiler</div>
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <tr><td style="padding:5px 0;color:var(--u-muted);width:140px;">ID</td><td>#{{ $guest->id }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Ad Soyad</td><td><strong>{{ $guest->first_name }} {{ $guest->last_name }}</strong></td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">E-posta</td><td>{{ $guest->email }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Telefon</td><td>{{ $guest->phone ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Cinsiyet</td><td>{{ $guest->gender ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Ülke</td><td>{{ $guest->application_country ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Dil</td><td>{{ $guest->communication_language ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Başvuru Türü</td><td>{{ $guest->application_type ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Kayıt Tarihi</td><td>{{ optional($guest->created_at)->format('d.m.Y H:i') }}</td></tr>
        </table>
    </div>

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:16px 18px;margin-bottom:12px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:12px;">Hedef & Tercihler</div>
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <tr><td style="padding:5px 0;color:var(--u-muted);width:140px;">Hedef Dönem</td><td>{{ $guest->target_term ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Hedef Şehir</td><td>{{ $guest->target_city ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Dil Seviyesi</td><td>{{ $guest->language_level ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Şube</td><td>{{ $guest->branch ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Öncelik</td><td>{{ $guest->priority ?: '–' }}</td></tr>
        </table>
    </div>

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:16px 18px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:12px;">Paket & Sözleşme</div>
        @php
            [$csLbl, $csCls] = match($guest->contract_status ?? '') {
                'not_requested' => ['Talep Edilmedi', 'badge'],
                'requested'     => ['Talep Edildi', 'warn'],
                'sent'          => ['Gönderildi', 'info'],
                'signed'        => ['İmzalandı', 'info'],
                'approved'      => ['Onaylandı', 'ok'],
                'rejected'      => ['Reddedildi', 'danger'],
                default         => ['–', 'badge'],
            };
        @endphp
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <tr><td style="padding:5px 0;color:var(--u-muted);width:140px;">Paket</td><td>{{ $guest->selected_package_title ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Fiyat</td><td>{{ $guest->selected_package_price ? number_format((float)$guest->selected_package_price,2,',','.') . ' EUR' : '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Sözleşme</td><td><span class="badge {{ $csCls }}">{{ $csLbl }}</span></td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Sözleşme Talep</td><td>{{ optional($guest->contract_requested_at)->format('d.m.Y') ?: '–' }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Sözleşme İmza</td><td>{{ optional($guest->contract_signed_at)->format('d.m.Y') ?: '–' }}</td></tr>
        </table>
    </div>
</div>

{{-- SAĞ: Durum (salt okunur) --}}
<div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:16px 18px;margin-bottom:12px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:12px;">Lead Durumu</div>
        @php
            $badgeClass = match($guest->lead_status ?? '') {
                'new'       => 'info', 'contacted' => 'warn',
                'converted' => 'ok',  'lost'       => 'danger',
                default     => 'badge',
            };
            $leadLabel = match($guest->lead_status ?? '') {
                'new'       => 'Yeni', 'contacted' => 'İletişime Geçildi',
                'qualified' => 'Nitelikli', 'converted' => 'Dönüştü',
                'lost'      => 'Kayboldu', default => ($guest->lead_status ?: '–'),
            };
        @endphp
        <span class="badge {{ $badgeClass }}">{{ $leadLabel }}</span>
        @if($guest->assigned_senior_email)
        <div style="margin-top:10px;font-size:var(--tx-sm);color:var(--u-muted);">Senior: <strong style="color:var(--u-text);">{{ $guest->assigned_senior_email }}</strong></div>
        @endif
        @if($guest->notes)
        <div style="margin-top:10px;padding:10px 12px;background:var(--u-bg);border-radius:8px;font-size:var(--tx-sm);color:var(--u-text);">{{ $guest->notes }}</div>
        @endif
    </div>

    @if($student)
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:16px 18px;margin-bottom:12px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:12px;">Dönüşen Öğrenci</div>
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <tr><td style="padding:5px 0;color:var(--u-muted);width:120px;">Student ID</td>
                <td><a href="/senior/process-tracking?student_id={{ urlencode($student->student_id) }}" style="color:#7c3aed;font-weight:700;">{{ $student->student_id }}</a></td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Ödeme</td><td>{{ $student->payment_status ?: '–' }}</td></tr>
        </table>
    </div>
    @endif

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:16px 18px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:12px;">Onay & Belge</div>
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <tr><td style="padding:5px 0;color:var(--u-muted);width:140px;">KVKK</td>
                <td><span class="badge {{ $guest->kvkk_consent ? 'ok' : 'danger' }}">{{ $guest->kvkk_consent ? 'Verildi' : 'Verilmedi' }}</span></td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Belgeler Hazır</td>
                <td><span class="badge {{ $guest->docs_ready ? 'ok' : '' }}">{{ $guest->docs_ready ? 'Evet' : 'Hayır' }}</span></td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Form Gönderildi</td>
                <td>{{ optional($guest->registration_form_submitted_at)->format('d.m.Y H:i') ?: '–' }}</td></tr>
        </table>
    </div>
</div>

</div>

@endsection

@extends('manager.layouts.app')

@section('title', 'Manager – Öğrenci Detay')
@section('page_title', 'Öğrenci Detay')

@push('head')
<style>
/* Shared detail layout — guest-detail ve student-detail için tutarlı stil */
.gd-panel { padding:14px 16px !important; margin-bottom:12px !important; }
.gd-panel h2 { font-size:13px !important; font-weight:700 !important; color:var(--u-text,#0f172a); margin:0 0 10px; padding-bottom:8px; border-bottom:1px solid var(--u-line,#e5e9f0); letter-spacing:.2px; }
.gd-table { width:100%; border-collapse:collapse; font-size:12px; }
.gd-table td { padding:6px 0; vertical-align:top; }
.gd-table td.lbl { color:var(--u-muted,#64748b); width:140px; font-weight:500; }
.gd-table td strong, .gd-table td a { color:var(--u-text,#0f172a); }
.gd-table code { font-size:10px; background:var(--u-bg,#f5f7fa); padding:1px 5px; border-radius:3px; }

.gd-field { margin-bottom:10px; }
.gd-field label { display:block; font-size:11px; font-weight:600; color:var(--u-muted,#64748b); margin-bottom:4px; text-transform:uppercase; letter-spacing:.3px; }
.gd-field select, .gd-field input[type=text], .gd-field input[type=email], .gd-field textarea {
    width:100%; box-sizing:border-box; font-size:12px !important; padding:7px 10px !important;
    border:1px solid var(--u-line,#e5e9f0); border-radius:6px; background:#fff;
    color:var(--u-text,#0f172a); line-height:1.4; min-height:32px !important;
}
.gd-field textarea { min-height:64px !important; resize:vertical; font-family:inherit; }
.gd-field select:focus, .gd-field input:focus, .gd-field textarea:focus {
    outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.12);
}
.gd-readonly { font-size:12px; color:var(--u-text,#0f172a); padding:7px 10px; background:var(--u-bg,#f5f7fa); border-radius:6px; margin-bottom:10px; }
.gd-readonly .muted { font-size:10px; color:var(--u-muted,#64748b); }
.gd-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.gd-actions .btn { font-size:12px !important; padding:7px 16px !important; min-height:32px !important; }

/* Mini revenue stat tiles (student-detail specific) */
.gd-mini-stats { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px; }
.gd-mini-stat { background:var(--u-bg,#f5f7fa); border:1px solid var(--u-line,#e5e9f0); border-radius:6px; padding:8px 10px; }
.gd-mini-stat .muted { font-size:10px; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; margin-bottom:3px; }
.gd-mini-stat .val { font-size:16px; font-weight:700; color:var(--u-text,#0f172a); }
</style>
@endpush

@section('content')

<div style="margin-bottom:10px;">
    <a class="btn" href="/manager/students">← Öğrenci Listesi</a>
</div>

@if($assignment->is_archived)
    <div class="panel" style="background:#fffbf0;border-color:var(--u-warn,#d97706);margin-bottom:12px;">
        <strong>Bu öğrenci arşivlenmiştir.</strong>
        @if($assignment->archived_at)
            — {{ optional($assignment->archived_at)->format('d.m.Y') }}
        @endif
        @if($assignment->archived_by)
            — Arşivleyen: {{ $assignment->archived_by }}
        @endif
    </div>
@endif

<div class="grid2">

    {{-- SOL: Bilgiler --}}
    <div>
        <section class="panel gd-panel">
            <h2>Atama Bilgileri</h2>
            @php
                $riskClass = match($assignment->risk_level) {
                    'high'   => 'danger',
                    'medium' => 'warn',
                    'low'    => 'ok',
                    default  => 'badge',
                };
                $payClass = match($assignment->payment_status) {
                    'paid'    => 'ok',
                    'partial' => 'warn',
                    'pending' => 'info',
                    'overdue' => 'danger',
                    default   => 'badge',
                };
            @endphp
            <table class="gd-table">
                <tr><td class="lbl">Öğrenci ID</td>
                    <td><strong style="font-size:var(--tx-base);">{{ $studentId }}</strong></td></tr>
                <tr><td class="lbl">Eğitim Danışmanı</td>
                    <td>
                        @if($assignment->senior_email)
                            <a href="/manager/seniors/{{ urlencode($assignment->senior_email) }}">{{ $assignment->senior_email }}</a>
                        @else –
                        @endif
                    </td></tr>
                <tr><td class="lbl">Şube</td>
                    <td>{{ $assignment->branch ?: '–' }}</td></tr>
                <tr><td class="lbl">Öğrenci Tipi</td>
                    <td>{{ $assignment->student_type ?: '–' }}</td></tr>
                <tr><td class="lbl">Risk Seviyesi</td>
                    <td>
                        @if($assignment->risk_level)
                            <span class="badge {{ $riskClass }}">{{ ucfirst($assignment->risk_level) }}</span>
                        @else <span class="muted">–</span> @endif
                    </td></tr>
                <tr><td class="lbl">Ödeme Durumu</td>
                    <td>
                        @if($assignment->payment_status)
                            <span class="badge {{ $payClass }}">{{ ucfirst($assignment->payment_status) }}</span>
                        @else <span class="muted">–</span> @endif
                    </td></tr>
                <tr><td class="lbl">Dealer</td>
                    <td>
                        @if($assignment->dealer_id)
                            <a href="/manager/dealers/{{ $assignment->dealer_id }}">{{ $assignment->dealer_id }}</a>
                        @else –
                        @endif
                    </td></tr>
                <tr><td class="lbl">Son Güncelleme</td>
                    <td>{{ optional($assignment->updated_at)->format('d.m.Y H:i') }}</td></tr>
            </table>
        </section>

        {{-- Gelir / Komisyon --}}
        @if($revenue)
            <section class="panel gd-panel">
                <h2>Dealer Gelir Bilgisi</h2>
                <div class="gd-mini-stats">
                    <div class="gd-mini-stat">
                        <div class="muted">Kazanılan</div>
                        <div class="val">{{ number_format((float)$revenue->total_earned, 2, ',', '.') }} EUR</div>
                    </div>
                    <div class="gd-mini-stat">
                        <div class="muted">Bekleyen</div>
                        <div class="val" style="color:#d97706;">{{ number_format((float)$revenue->total_pending, 2, ',', '.') }} EUR</div>
                    </div>
                </div>
                @if($revenue->milestone_progress)
                    <div class="muted" style="font-size:var(--tx-xs);margin-bottom:4px;">Milestone İlerleme</div>
                    <div class="list">
                        @foreach((array)$revenue->milestone_progress as $key => $val)
                            <div class="item" style="font-size:var(--tx-xs);">
                                <strong>{{ $key }}</strong>
                                <span class="muted">: {{ is_array($val) ? json_encode($val) : $val }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- Orijinal Aday Öğrenci --}}
        @if($guest)
            <section class="panel gd-panel">
                <h2>Orijinal Başvuru (Aday Öğrenci)</h2>
                <table class="gd-table">
                    <tr><td class="lbl">Aday Öğrenci ID</td>
                        <td><a href="/manager/guests/{{ $guest->id }}">#{{ $guest->id }}</a></td></tr>
                    <tr><td class="lbl">Ad Soyad</td>
                        <td>{{ $guest->first_name }} {{ $guest->last_name }}</td></tr>
                    <tr><td class="lbl">E-posta</td>
                        <td>{{ $guest->email }}</td></tr>
                    <tr><td class="lbl">Telefon</td>
                        <td>{{ $guest->phone ?: '–' }}</td></tr>
                    <tr><td class="lbl">Başvuru Tarihi</td>
                        <td>{{ optional($guest->created_at)->format('d.m.Y H:i') }}</td></tr>
                    <tr><td class="lbl">Lead Kaynağı</td>
                        <td>{{ $guest->lead_source ?: '–' }}</td></tr>
                    <tr><td class="lbl">Paket</td>
                        <td>{{ $guest->selected_package_title ?: '–' }}</td></tr>
                </table>
            </section>
        @endif
    </div>

    {{-- SAĞ: Güncelleme Formu --}}
    <div>
        <section class="panel gd-panel">
            <h2>Bilgileri Güncelle</h2>
            <form method="POST" action="/manager/students/{{ urlencode($studentId) }}/update">
                @csrf @method('PATCH')
                <div class="gd-field">
                    <label>Eğitim Danışmanı E-posta</label>
                    <select name="senior_email">
                        <option value="">– Seç –</option>
                        @foreach($seniorOptions as $e)
                            <option value="{{ $e }}" @selected($assignment->senior_email === $e)>{{ $e }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="gd-field">
                    <label>Şube</label>
                    <select name="branch">
                        <option value="">– Seç –</option>
                        @foreach($branchOptions as $b)
                            <option value="{{ $b }}" @selected($assignment->branch === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="gd-field">
                    <label>Risk Seviyesi</label>
                    <select name="risk_level">
                        <option value="">– Seç –</option>
                        <option value="low"    @selected($assignment->risk_level === 'low')>Düşük</option>
                        <option value="medium" @selected($assignment->risk_level === 'medium')>Orta</option>
                        <option value="high"   @selected($assignment->risk_level === 'high')>Yüksek</option>
                    </select>
                </div>
                <div class="gd-field">
                    <label>Ödeme Durumu</label>
                    <select name="payment_status">
                        <option value="">– Seç –</option>
                        <option value="pending" @selected($assignment->payment_status === 'pending')>Bekliyor</option>
                        <option value="partial" @selected($assignment->payment_status === 'partial')>Kısmi Ödendi</option>
                        <option value="paid"    @selected($assignment->payment_status === 'paid')>Ödendi</option>
                        <option value="overdue" @selected($assignment->payment_status === 'overdue')>Gecikmiş</option>
                    </select>
                </div>
                <div class="gd-actions">
                    <button class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </section>

        {{-- Vize Durumu --}}
        <section class="panel gd-panel">
            <h2>🛂 Vize Durumu</h2>
            @if($visa)
            <table class="gd-table">
                <tr><td class="lbl">Vize Türü</td>
                    <td style="padding:5px 0;font-weight:600;">{{ \App\Models\StudentVisaApplication::VISA_TYPE_LABELS[$visa->visa_type] ?? $visa->visa_type }}</td></tr>
                <tr><td class="lbl">Durum</td>
                    <td style="padding:5px 0;"><span class="badge {{ $visa->statusBadge() }}">{{ $visa->statusLabel() }}</span></td></tr>
                @if($visa->consulate_city)
                <tr><td class="lbl">Konsolosluk</td>
                    <td style="padding:5px 0;font-weight:600;">{{ $visa->consulate_city }}</td></tr>
                @endif
                @if($visa->appointment_date)
                <tr><td class="lbl">Randevu</td>
                    <td style="padding:5px 0;font-weight:600;">{{ $visa->appointment_date->format('d.m.Y') }}</td></tr>
                @endif
                @if($visa->valid_until)
                <tr><td class="lbl">Geçerlilik</td>
                    <td style="padding:5px 0;font-weight:600;">{{ $visa->valid_from?->format('d.m.Y') }} – {{ $visa->valid_until->format('d.m.Y') }}</td></tr>
                @endif
            </table>
            @else
            <p style="font-size:var(--tx-sm);color:var(--u-muted);">Vize kaydı girilmemiş.</p>
            @endif
        </section>

        {{-- Konut Durumu --}}
        <section class="panel gd-panel">
            <h2>🏠 Konut Durumu</h2>
            @if($accommodation)
            <table class="gd-table">
                <tr><td class="lbl">Tür</td>
                    <td style="padding:5px 0;font-weight:600;">{{ \App\Models\StudentAccommodation::TYPE_LABELS[$accommodation->type] ?? $accommodation->type }}</td></tr>
                <tr><td class="lbl">Durum</td>
                    <td style="padding:5px 0;"><span class="badge {{ $accommodation->statusBadge() }}">{{ $accommodation->statusLabel() }}</span></td></tr>
                @if($accommodation->city)
                <tr><td class="lbl">Şehir</td>
                    <td style="padding:5px 0;font-weight:600;">{{ $accommodation->city }}</td></tr>
                @endif
                @if($accommodation->monthly_cost_eur)
                <tr><td class="lbl">Aylık Kira</td>
                    <td style="padding:5px 0;font-weight:600;">€{{ number_format($accommodation->monthly_cost_eur, 0) }}</td></tr>
                @endif
                @if($accommodation->move_in_date)
                <tr><td class="lbl">Taşınma</td>
                    <td style="padding:5px 0;font-weight:600;">{{ $accommodation->move_in_date->format('d.m.Y') }}</td></tr>
                @endif
            </table>
            @else
            <p style="font-size:var(--tx-sm);color:var(--u-muted);">Konut kaydı girilmemiş.</p>
            @endif
        </section>

        {{-- Üniversite Başvuruları --}}
        <section class="panel gd-panel">
            <h2>🏛 Üniversite Başvuruları</h2>
            @if($uniApplications->isEmpty())
                <p style="font-size:var(--tx-sm);color:var(--u-muted);">Başvuru kaydı girilmemiş.</p>
            @else
                <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($uniApplications as $uniApp)
                @php
                    $uBadge = \App\Models\StudentUniversityApplication::STATUS_BADGE[$uniApp->status] ?? 'info';
                    $uLabel = \App\Models\StudentUniversityApplication::STATUS_LABELS[$uniApp->status] ?? $uniApp->status;
                    $uDeg   = \App\Models\StudentUniversityApplication::DEGREE_LABELS[$uniApp->degree_type] ?? $uniApp->degree_type;
                @endphp
                <div style="border:1px solid var(--u-line);border-radius:8px;padding:10px 12px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;flex-wrap:wrap;">
                        <div>
                            <div style="font-weight:700;font-size:var(--tx-sm);">{{ $uniApp->university_name }}@if($uniApp->city) <span style="font-weight:400;color:var(--u-muted);">· {{ $uniApp->city }}</span>@endif</div>
                            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">{{ $uniApp->department_name }} · {{ $uDeg }}@if($uniApp->semester) · {{ $uniApp->semester }}@endif</div>
                            @if($uniApp->deadline)
                            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">Son: {{ $uniApp->deadline->format('d.m.Y') }}@if($uniApp->result_at) · Sonuç: {{ $uniApp->result_at->format('d.m.Y') }}@endif</div>
                            @endif
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0;">
                            <span class="badge {{ $uBadge }}">{{ $uLabel }}</span>
                            <div style="display:flex;gap:4px;">
                                @if($uniApp->is_visible_to_student)<span style="font-size:var(--tx-xs);color:#16a34a;font-weight:700;">✓ Öğrenci</span>@endif
                                @if($uniApp->is_visible_to_dealer)<span style="font-size:var(--tx-xs);color:#2563eb;font-weight:700;">✓ Bayi</span>@endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
                </div>
            @endif
        </section>

        {{-- Hızlı Linkler --}}
        <section class="panel gd-panel">
            <h2>Hızlı Linkler</h2>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @if($assignment->senior_email)
                    <a class="btn" href="/manager/seniors/{{ urlencode($assignment->senior_email) }}">Eğitim Danışmanı Profili →</a>
                @endif
                @if($assignment->dealer_id)
                    <a class="btn" href="/manager/dealers/{{ $assignment->dealer_id }}">Bayi Detay →</a>
                @endif
                @if($guest)
                    <a class="btn" href="/manager/guests/{{ $guest->id }}">Orijinal Başvuru →</a>
                @endif
                <a class="btn" href="/manager/preview/student/{{ urlencode($studentId) }}" target="_blank">Öğrenci Önizleme</a>
            </div>
        </section>
    </div>

</div>

@endsection

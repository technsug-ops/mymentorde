@extends('manager.layouts.app')

@section('title', 'Manager – Öğrenci Detay')
@section('page_title', 'Öğrenci Detay')

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
        <section class="panel" style="margin-bottom:12px;">
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
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                <tr><td class="muted" style="padding:5px 0;width:140px;">Öğrenci ID</td>
                    <td><strong style="font-size:var(--tx-base);">{{ $studentId }}</strong></td></tr>
                <tr><td class="muted" style="padding:5px 0;">Eğitim Danışmanı</td>
                    <td>
                        @if($assignment->senior_email)
                            <a href="/manager/seniors/{{ urlencode($assignment->senior_email) }}">{{ $assignment->senior_email }}</a>
                        @else –
                        @endif
                    </td></tr>
                <tr><td class="muted" style="padding:5px 0;">Şube</td>
                    <td>{{ $assignment->branch ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Öğrenci Tipi</td>
                    <td>{{ $assignment->student_type ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Risk Seviyesi</td>
                    <td>
                        @if($assignment->risk_level)
                            <span class="badge {{ $riskClass }}">{{ ucfirst($assignment->risk_level) }}</span>
                        @else <span class="muted">–</span> @endif
                    </td></tr>
                <tr><td class="muted" style="padding:5px 0;">Ödeme Durumu</td>
                    <td>
                        @if($assignment->payment_status)
                            <span class="badge {{ $payClass }}">{{ ucfirst($assignment->payment_status) }}</span>
                        @else <span class="muted">–</span> @endif
                    </td></tr>
                <tr><td class="muted" style="padding:5px 0;">Dealer</td>
                    <td>
                        @if($assignment->dealer_id)
                            <a href="/manager/dealers/{{ $assignment->dealer_id }}">{{ $assignment->dealer_id }}</a>
                        @else –
                        @endif
                    </td></tr>
                <tr><td class="muted" style="padding:5px 0;">Son Güncelleme</td>
                    <td>{{ optional($assignment->updated_at)->format('d.m.Y H:i') }}</td></tr>
            </table>
        </section>

        {{-- Gelir / Komisyon --}}
        @if($revenue)
            <section class="panel" style="margin-bottom:12px;">
                <h2>Dealer Gelir Bilgisi</h2>
                <div class="grid2" style="margin-bottom:10px;">
                    <div class="panel"><div class="muted">Kazanılan</div>
                        <div class="kpi" style="font-size:var(--tx-xl);">{{ number_format((float)$revenue->total_earned, 2, ',', '.') }} EUR</div></div>
                    <div class="panel"><div class="muted">Bekleyen</div>
                        <div class="kpi" style="font-size:var(--tx-xl);color:var(--u-warn,#d97706);">{{ number_format((float)$revenue->total_pending, 2, ',', '.') }} EUR</div></div>
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
            <section class="panel">
                <h2>Orijinal Başvuru (Aday Öğrenci)</h2>
                <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                    <tr><td class="muted" style="padding:5px 0;width:140px;">Aday Öğrenci ID</td>
                        <td><a href="/manager/guests/{{ $guest->id }}">#{{ $guest->id }}</a></td></tr>
                    <tr><td class="muted" style="padding:5px 0;">Ad Soyad</td>
                        <td>{{ $guest->first_name }} {{ $guest->last_name }}</td></tr>
                    <tr><td class="muted" style="padding:5px 0;">E-posta</td>
                        <td>{{ $guest->email }}</td></tr>
                    <tr><td class="muted" style="padding:5px 0;">Telefon</td>
                        <td>{{ $guest->phone ?: '–' }}</td></tr>
                    <tr><td class="muted" style="padding:5px 0;">Başvuru Tarihi</td>
                        <td>{{ optional($guest->created_at)->format('d.m.Y H:i') }}</td></tr>
                    <tr><td class="muted" style="padding:5px 0;">Lead Kaynağı</td>
                        <td>{{ $guest->lead_source ?: '–' }}</td></tr>
                    <tr><td class="muted" style="padding:5px 0;">Paket</td>
                        <td>{{ $guest->selected_package_title ?: '–' }}</td></tr>
                </table>
            </section>
        @endif
    </div>

    {{-- SAĞ: Güncelleme Formu --}}
    <div>
        <section class="panel" style="margin-bottom:12px;">
            <h2>Bilgileri Güncelle</h2>
            <form method="POST" action="/manager/students/{{ urlencode($studentId) }}/update">
                @csrf @method('PATCH')
                <div style="margin-bottom:8px;">
                    <label class="muted">Eğitim Danışmanı E-posta</label>
                    <select name="senior_email" style="width:100%;">
                        <option value="">– Seç –</option>
                        @foreach($seniorOptions as $e)
                            <option value="{{ $e }}" @selected($assignment->senior_email === $e)>{{ $e }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom:8px;">
                    <label class="muted">Şube</label>
                    <select name="branch" style="width:100%;">
                        <option value="">– Seç –</option>
                        @foreach($branchOptions as $b)
                            <option value="{{ $b }}" @selected($assignment->branch === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom:8px;">
                    <label class="muted">Risk Seviyesi</label>
                    <select name="risk_level" style="width:100%;">
                        <option value="">– Seç –</option>
                        <option value="low"    @selected($assignment->risk_level === 'low')>Düşük</option>
                        <option value="medium" @selected($assignment->risk_level === 'medium')>Orta</option>
                        <option value="high"   @selected($assignment->risk_level === 'high')>Yüksek</option>
                    </select>
                </div>
                <div style="margin-bottom:12px;">
                    <label class="muted">Ödeme Durumu</label>
                    <select name="payment_status" style="width:100%;">
                        <option value="">– Seç –</option>
                        <option value="pending" @selected($assignment->payment_status === 'pending')>Bekliyor</option>
                        <option value="partial" @selected($assignment->payment_status === 'partial')>Kısmi Ödendi</option>
                        <option value="paid"    @selected($assignment->payment_status === 'paid')>Ödendi</option>
                        <option value="overdue" @selected($assignment->payment_status === 'overdue')>Gecikmiş</option>
                    </select>
                </div>
                <button class="btn btn-primary">Kaydet</button>
            </form>
        </section>

        {{-- Vize Durumu --}}
        <section class="panel">
            <h2>🛂 Vize Durumu</h2>
            @if($visa)
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                <tr><td class="muted" style="padding:5px 0;">Vize Türü</td>
                    <td style="padding:5px 0;font-weight:600;">{{ \App\Models\StudentVisaApplication::VISA_TYPE_LABELS[$visa->visa_type] ?? $visa->visa_type }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Durum</td>
                    <td style="padding:5px 0;"><span class="badge {{ $visa->statusBadge() }}">{{ $visa->statusLabel() }}</span></td></tr>
                @if($visa->consulate_city)
                <tr><td class="muted" style="padding:5px 0;">Konsolosluk</td>
                    <td style="padding:5px 0;font-weight:600;">{{ $visa->consulate_city }}</td></tr>
                @endif
                @if($visa->appointment_date)
                <tr><td class="muted" style="padding:5px 0;">Randevu</td>
                    <td style="padding:5px 0;font-weight:600;">{{ $visa->appointment_date->format('d.m.Y') }}</td></tr>
                @endif
                @if($visa->valid_until)
                <tr><td class="muted" style="padding:5px 0;">Geçerlilik</td>
                    <td style="padding:5px 0;font-weight:600;">{{ $visa->valid_from?->format('d.m.Y') }} – {{ $visa->valid_until->format('d.m.Y') }}</td></tr>
                @endif
            </table>
            @else
            <p style="font-size:var(--tx-sm);color:var(--u-muted);">Vize kaydı girilmemiş.</p>
            @endif
        </section>

        {{-- Konut Durumu --}}
        <section class="panel">
            <h2>🏠 Konut Durumu</h2>
            @if($accommodation)
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                <tr><td class="muted" style="padding:5px 0;">Tür</td>
                    <td style="padding:5px 0;font-weight:600;">{{ \App\Models\StudentAccommodation::TYPE_LABELS[$accommodation->type] ?? $accommodation->type }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Durum</td>
                    <td style="padding:5px 0;"><span class="badge {{ $accommodation->statusBadge() }}">{{ $accommodation->statusLabel() }}</span></td></tr>
                @if($accommodation->city)
                <tr><td class="muted" style="padding:5px 0;">Şehir</td>
                    <td style="padding:5px 0;font-weight:600;">{{ $accommodation->city }}</td></tr>
                @endif
                @if($accommodation->monthly_cost_eur)
                <tr><td class="muted" style="padding:5px 0;">Aylık Kira</td>
                    <td style="padding:5px 0;font-weight:600;">€{{ number_format($accommodation->monthly_cost_eur, 0) }}</td></tr>
                @endif
                @if($accommodation->move_in_date)
                <tr><td class="muted" style="padding:5px 0;">Taşınma</td>
                    <td style="padding:5px 0;font-weight:600;">{{ $accommodation->move_in_date->format('d.m.Y') }}</td></tr>
                @endif
            </table>
            @else
            <p style="font-size:var(--tx-sm);color:var(--u-muted);">Konut kaydı girilmemiş.</p>
            @endif
        </section>

        {{-- Üniversite Başvuruları --}}
        <section class="panel" style="margin-bottom:12px;">
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
        <section class="panel">
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

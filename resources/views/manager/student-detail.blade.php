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

<div style="margin-bottom:10px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
    <a class="btn" href="/manager/students">← Öğrenci Listesi</a>
    @if(! empty($studentId))
        <a href="{{ route('manager.student.uni-assist-guide.show', $studentId) }}"
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:linear-gradient(135deg,#c8102e,#9f1239); color:#fff; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; margin-left:auto;">
            🎓 Uni-Assist Rehberi →
        </a>
    @endif
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
                @module('doc_request')
                    @can('doc_request.use')
                        <button type="button" id="docReqOpenBtn"
                                style="padding:8px 14px;border:none;border-radius:8px;font-size:12px;font-weight:700;color:#fff;background:linear-gradient(135deg,#1e40af,#3b5fcc);cursor:pointer;display:inline-flex;align-items:center;gap:6px;justify-content:center;">
                            📲 Belge Talep Et
                        </button>
                    @endcan
                @endmodule
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

@module('doc_request')
@can('doc_request.use')
{{-- ── Belge Talep Linki Modal (öğrenci versiyonu) ─────────────────────────── --}}
<div id="docReqModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:14px;max-width:520px;width:100%;max-height:92vh;overflow:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="padding:18px 22px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <strong style="font-size:15px;">📲 Belge Talep Linki Oluştur</strong>
            <button type="button" id="docReqCloseBtn" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <div style="padding:18px 22px;">
            <p style="font-size:13px;color:#475569;line-height:1.5;margin:0 0 14px;">
                <strong>{{ $assignment->display_name ?: $studentId }}</strong> için tek-kullanımlık belge yükleme linki oluştur.
            </p>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">İstenen Belge</span>
                    <select id="docReqCategory" style="padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;">
                        <option value="">— Yükleniyor —</option>
                    </select>
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">Geçerlilik Süresi</span>
                    <select id="docReqExpiry" style="padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;">
                        <option value="24">24 saat</option>
                        <option value="48" selected>48 saat (önerilen)</option>
                        <option value="72">3 gün</option>
                        <option value="168">7 gün</option>
                    </select>
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">Özel Mesaj (opsiyonel)</span>
                    <textarea id="docReqMessage" rows="2" maxlength="500"
                        style="padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;font-family:inherit;resize:vertical;"
                        placeholder="Örn: Pasaportunuzu net çekin."></textarea>
                </label>
            </div>
            <button type="button" id="docReqGenBtn"
                    style="margin-top:16px;width:100%;padding:12px 18px;border:none;border-radius:10px;background:linear-gradient(135deg,#1e40af,#3b5fcc);color:#fff;font-size:14px;font-weight:700;cursor:pointer;">
                🔗 Linki Oluştur
            </button>
            <div id="docReqResult" style="display:none;margin-top:16px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
                <div style="font-size:12px;font-weight:700;color:#166534;margin-bottom:8px;">✅ Link hazır — öğrenciye gönder:</div>
                <input type="text" id="docReqUrl" readonly
                       style="width:100%;padding:8px 10px;border:1px solid #bbf7d0;border-radius:6px;font-family:ui-monospace,monospace;font-size:11.5px;background:#fff;margin-bottom:10px;">
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="button" id="docReqCopyBtn"
                            style="flex:1;min-width:100px;padding:8px 12px;border:1px solid #16a34a;background:#fff;color:#166534;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">
                        📋 Kopyala
                    </button>
                    <a id="docReqWhatsAppBtn" target="_blank" href="#"
                       style="flex:1;min-width:100px;padding:8px 12px;background:#25d366;color:#fff;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;text-align:center;">
                        💬 WhatsApp'la Gönder
                    </a>
                </div>
                <div style="font-size:11px;color:#65a30d;margin-top:8px;">
                    Bu link tek-kullanımlık. Öğrenci yüklediğinde otomatik geçersizleşir.
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var openBtn = document.getElementById('docReqOpenBtn');
    var modal   = document.getElementById('docReqModal');
    var closeBtn = document.getElementById('docReqCloseBtn');
    var catSelect = document.getElementById('docReqCategory');
    var expirySelect = document.getElementById('docReqExpiry');
    var messageInput = document.getElementById('docReqMessage');
    var genBtn = document.getElementById('docReqGenBtn');
    var resultBox = document.getElementById('docReqResult');
    var urlInput = document.getElementById('docReqUrl');
    var copyBtn = document.getElementById('docReqCopyBtn');
    var waBtn = document.getElementById('docReqWhatsAppBtn');
    if (!openBtn) return;

    var CSRF = '{{ csrf_token() }}';
    var INDEX_URL = "{{ route('manager.student.document-tokens.index', $studentId) }}";
    var STORE_URL = "{{ route('manager.student.document-tokens.store', $studentId) }}";

    function loadCategories(){
        catSelect.innerHTML = '<option value="">— Yükleniyor —</option>';
        fetch(INDEX_URL, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                var groups = {};
                (data.categories || []).forEach(function(c){
                    var top = c.top_category_code || 'diger';
                    if (!groups[top]) groups[top] = [];
                    groups[top].push(c);
                });
                catSelect.innerHTML = '<option value="">Belge seç...</option>';
                var labelMap = { uni_assist:'Uni Asist', vize:'Vize', vize_surec:'Vize Süreç Belgeleri', dil_okulu:'Dil Okulu', uni_kayit:'Üniversite Kayıt', yurt:'İkamet', diger:'Diğer' };
                Object.keys(labelMap).forEach(function(top){
                    if (!groups[top] || !groups[top].length) return;
                    var og = document.createElement('optgroup'); og.label = labelMap[top];
                    groups[top].forEach(function(c){
                        var opt = document.createElement('option');
                        opt.value = c.code;
                        opt.textContent = c.name_tr + (c.name_de ? ' / ' + c.name_de : '');
                        og.appendChild(opt);
                    });
                    catSelect.appendChild(og);
                });
            })
            .catch(() => { catSelect.innerHTML = '<option value="">Yükleme hatası</option>'; });
    }
    function openModal(){ modal.style.display='flex'; resultBox.style.display='none'; messageInput.value=''; loadCategories(); }
    function closeModal(){ modal.style.display='none'; }
    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

    genBtn.addEventListener('click', function(){
        var cat = catSelect.value;
        if (!cat) { alert('Lütfen bir belge seç.'); return; }
        genBtn.disabled = true; genBtn.textContent = '⏳ Oluşturuluyor...';
        fetch(STORE_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({
                category_code: cat,
                expires_hours: parseInt(expirySelect.value, 10) || 48,
                custom_message: messageInput.value || null,
            })
        })
        .then(r => r.json().then(d => ({ ok:r.ok, data:d })))
        .then(res => {
            genBtn.disabled = false; genBtn.textContent = '🔗 Linki Oluştur';
            if (!res.ok) { alert(res.data.error || 'Hata oluştu.'); return; }
            urlInput.value = res.data.url;
            var msg = "Merhaba, MentorDE'den belge talebimiz var. Lütfen linke tıklayıp belgeyi yükleyin:\n\n" + res.data.url;
            waBtn.href = 'https://wa.me/?text=' + encodeURIComponent(msg);
            resultBox.style.display = 'block';
        })
        .catch(() => { genBtn.disabled = false; genBtn.textContent = '🔗 Linki Oluştur'; alert('Bağlantı hatası.'); });
    });

    copyBtn.addEventListener('click', function(){
        urlInput.select();
        navigator.clipboard.writeText(urlInput.value).then(function(){
            copyBtn.textContent = '✓ Kopyalandı';
            setTimeout(function(){ copyBtn.textContent = '📋 Kopyala'; }, 2000);
        }).catch(function(){ document.execCommand('copy'); });
    });
})();
</script>
@endcan
@endmodule

@endsection

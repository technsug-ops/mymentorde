@extends('manager.layouts.app')

@section('title', 'Manager – Aday Öğrenci Detay #' . $guest->id)
@section('page_title', 'Aday Öğrenci Detay')

@push('head')
<style>
/* Guest detail — sol (tablo) ve sağ (form) kolonun tutarlı görünmesi için */
.gd-panel { padding:14px 16px !important; margin-bottom:12px !important; }
.gd-panel h2 { font-size:13px !important; font-weight:700 !important; color:var(--u-text,#0f172a); margin:0 0 10px; padding-bottom:8px; border-bottom:1px solid var(--u-line,#e5e9f0); letter-spacing:.2px; }
.gd-table { width:100%; border-collapse:collapse; font-size:12px; }
.gd-table td { padding:6px 0; vertical-align:top; }
.gd-table td.lbl { color:var(--u-muted,#64748b); width:130px; font-weight:500; }
.gd-table td strong, .gd-table td a { color:var(--u-text,#0f172a); }
.gd-table code { font-size:10px; background:var(--u-bg,#f5f7fa); padding:1px 5px; border-radius:3px; }

/* Right-column forms → match left column density */
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
</style>
@endpush

@section('content')

<div style="margin-bottom:10px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
    <a class="btn" href="/manager/guests">← Aday Öğrenci Listesi</a>
    {{-- Başvuru rehberleri operasyonun araçları — partner firma bunları
         yürütmüyor, öğrenciyi devredip süreci izliyor. Adresler ayrıca
         RestrictPartnerPanel::DENIED ile kapalı. --}}
    @unlesspartnerPanel
    <a href="{{ route('manager.uni-assist-guide.show', $guest->id) }}"
       style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:linear-gradient(135deg,#c8102e,#9f1239); color:#fff; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; margin-left:auto;">
        🎓 Uni-Assist →
    </a>
    <a href="{{ route('manager.visa-guide.show', $guest->id) }}"
       style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:linear-gradient(135deg,#003c8f,#002966); color:#fff; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none;">
        🛂 Vize →
    </a>
    {{-- Application Guides — addon partial, hata olsa bile guest-detail çalışır --}}
    @includeIf('manager.partials.application-guides-buttons', ['guestId' => $guest->id, 'studentId' => null])
    @endpartnerPanel
</div>

{{-- Dönüşüm Bandı --}}
@if($guest->converted_to_student)
    <div class="panel" style="background:#f0faf4;border-color:var(--u-ok,#21a861);margin-bottom:12px;">
        <strong style="color:var(--u-ok,#21a861);">✓ Öğrenciye Dönüştü</strong>
        @if($guest->converted_student_id)
            — Öğrenci ID:
            <a href="/manager/students/{{ urlencode($guest->converted_student_id) }}" style="font-weight:600;">
                {{ $guest->converted_student_id }}
            </a>
        @endif
    </div>
@endif

<div class="grid2">

    {{-- SOL: Kişisel Bilgiler --}}
    <div>
        <section class="panel gd-panel">
            <h2>Kişisel Bilgiler</h2>
            <table class="gd-table">
                <tr><td class="lbl">ID / Token</td>
                    <td>#{{ $guest->id }} / <code style="font-size:var(--tx-xs);">{{ $guest->tracking_token }}</code></td></tr>
                <tr><td class="lbl">Ad Soyad</td>
                    <td><strong>{{ $guest->first_name }} {{ $guest->last_name }}</strong></td></tr>
                <tr><td class="lbl">E-posta</td>
                    <td>{{ $guest->email }}</td></tr>
                <tr><td class="lbl">Telefon</td>
                    <td>{{ $guest->phone ?: '–' }}</td></tr>
                <tr><td class="lbl">Cinsiyet</td>
                    <td>{{ $guest->gender ?: '–' }}</td></tr>
                <tr><td class="lbl">Ülke</td>
                    <td>{{ $guest->application_country ?: '–' }}</td></tr>
                <tr><td class="lbl">Dil</td>
                    <td>{{ $guest->communication_language ?: '–' }}</td></tr>
                <tr><td class="lbl">Başvuru Türü</td>
                    <td>{{ $guest->application_type ?: '–' }}</td></tr>
                <tr><td class="lbl">Kayıt Tarihi</td>
                    <td>{{ optional($guest->created_at)->format('d.m.Y H:i') }}</td></tr>
            </table>
        </section>

        <section class="panel gd-panel">
            <h2>Hedef & Tercihler</h2>
            <table class="gd-table">
                <tr><td class="lbl">Hedef Dönem</td>
                    <td>{{ $guest->target_term ?: '–' }}</td></tr>
                <tr><td class="lbl">Hedef Şehir</td>
                    <td>{{ $guest->target_city ?: '–' }}</td></tr>
                <tr><td class="lbl">Dil Seviyesi</td>
                    <td>{{ $guest->language_level ?: '–' }}</td></tr>
                <tr><td class="lbl">Şube</td>
                    <td>{{ $guest->branch ?: '–' }}</td></tr>
                <tr><td class="lbl">Öncelik</td>
                    <td>{{ $guest->priority ?: '–' }}</td></tr>
                <tr><td class="lbl">Risk</td>
                    <td>{{ $guest->risk_level ?: '–' }}</td></tr>
            </table>
        </section>

        <section class="panel gd-panel">
            <h2>Paket & Sözleşme</h2>
            <table class="gd-table">
                <tr><td class="lbl">Paket Kodu</td>
                    <td>{{ $guest->selected_package_code ?: '–' }}</td></tr>
                <tr><td class="lbl">Paket Adı</td>
                    <td>{{ $guest->selected_package_title ?: '–' }}</td></tr>
                <tr><td class="lbl">Paket Fiyatı</td>
                    <td>{{ $guest->selected_package_price ? number_format((float)$guest->selected_package_price, 2, ',', '.') . ' EUR' : '–' }}</td></tr>
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
                <tr><td class="lbl">Sözleşme Durumu</td>
                    <td><span class="badge {{ $csCls }}">{{ $csLbl }}</span></td></tr>
                <tr><td class="lbl">Sözleşme Talep</td>
                    <td>{{ optional($guest->contract_requested_at)->format('d.m.Y') ?: '–' }}</td></tr>
                <tr><td class="lbl">Sözleşme İmza</td>
                    <td>{{ optional($guest->contract_signed_at)->format('d.m.Y') ?: '–' }}</td></tr>
                <tr><td class="lbl">Sözleşme Onay</td>
                    <td>{{ optional($guest->contract_approved_at)->format('d.m.Y') ?: '–' }}</td></tr>
                @php
                    $hasSignedFile = !empty($guest->contract_signed_file_path);
                    $hasDigitalSig = !empty($guest->contract_digital_signed_at);
                    $hasSnapshot   = trim((string) ($guest->contract_snapshot_text ?? '')) !== '';
                    $canViewSigned = $hasSignedFile || $hasDigitalSig || $hasSnapshot;
                @endphp
                @if($canViewSigned)
                <tr><td class="lbl">İmzalı Belge</td>
                    <td>
                        <a href="{{ route('manager.contract-template.signed-file', ['guest' => $guest->id]) }}"
                           target="_blank"
                           class="badge ok"
                           style="text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                            📄 İmzalı Sözleşmeyi Görüntüle / İndir
                        </a>
                        @if($hasDigitalSig && !$hasSignedFile)
                            <div style="font-size:11px;color:#64748b;margin-top:4px;">
                                Dijital imza ({{ optional($guest->contract_digital_signed_at)->format('d.m.Y H:i') }})
                                — PDF anlık olarak metin + ek + imza bilgisinden üretilir.
                            </div>
                        @endif
                    </td></tr>
                @endif
            </table>
        </section>

        <section class="panel gd-panel">
            <h2>UTM / Kaynak İzleme</h2>
            <table class="gd-table">
                <tr><td class="lbl">Lead Kaynağı</td>
                    <td>{{ $guest->lead_source ?: '–' }}</td></tr>
                <tr><td class="lbl">Dealer Kodu</td>
                    <td>
                        @if($guest->dealer_code)
                            <a href="/manager/dealers/{{ $guest->dealer_code }}">{{ $guest->dealer_code }}</a>
                        @else –
                        @endif
                    </td></tr>
                <tr><td class="lbl">UTM Source</td>
                    <td>{{ $guest->utm_source ?: '–' }}</td></tr>
                <tr><td class="lbl">UTM Medium</td>
                    <td>{{ $guest->utm_medium ?: '–' }}</td></tr>
                <tr><td class="lbl">UTM Campaign</td>
                    <td>{{ $guest->utm_campaign ?: '–' }}</td></tr>
                <tr><td class="lbl">Campaign Kodu</td>
                    <td>{{ $guest->campaign_code ?: '–' }}</td></tr>
            </table>
        </section>
    </div>

    {{-- SAĞ: Aksiyonlar --}}
    <div>

        {{-- Lead Score (Core servisi — Marketing Admin'den bağımsız) --}}
        <section class="panel gd-panel">
            <h2>Lead Score</h2>
            @php
                $tier = $guest->lead_score_tier ?? 'cold';
                $tierColors = [
                    'champion'    => ['#7c3aed', '#faf5ff', 'Şampiyon'],
                    'sales_ready' => ['#16a34a', '#f0fdf4', 'Satışa Hazır'],
                    'hot'         => ['#dc2626', '#fef2f2', 'Sıcak'],
                    'warm'        => ['#d97706', '#fffbeb', 'Ilık'],
                    'cold'        => ['#2563eb', '#eff6ff', 'Soğuk'],
                ];
                [$tFg, $tBg, $tLabel] = $tierColors[$tier] ?? $tierColors['cold'];
                $score = (int) ($guest->lead_score ?? $scoreTotal ?? 0);
                $behavioral = (int) ($scoreBreakdown['behavioral'] ?? 0);
                $demographic = (int) ($scoreBreakdown['demographic'] ?? 0);
                $decay = (int) ($scoreBreakdown['decay'] ?? 0);
            @endphp
            <div style="display:flex;align-items:center;gap:14px;padding:10px 12px;background:{{ $tBg }};border-radius:8px;border:1px solid {{ $tFg }}33;margin-bottom:10px;">
                <div style="font-size:32px;font-weight:800;color:{{ $tFg }};line-height:1;">{{ $score }}</div>
                <div style="flex:1;">
                    <div style="font-size:11px;font-weight:700;color:{{ $tFg }};text-transform:uppercase;letter-spacing:.05em;">{{ $tLabel }}</div>
                    <div style="font-size:10px;color:var(--u-muted,#64748b);margin-top:2px;">Lead tier · puan toplamı</div>
                </div>
            </div>
            <table class="gd-table" style="font-size:11px;">
                <tr>
                    <td class="lbl">Davranışsal</td>
                    <td><strong style="color:{{ $behavioral >= 0 ? '#16a34a' : '#dc2626' }};">{{ $behavioral > 0 ? '+' : '' }}{{ $behavioral }}</strong> <span style="color:var(--u-muted,#64748b);">puan</span></td>
                </tr>
                <tr>
                    <td class="lbl">Demografik</td>
                    <td><strong style="color:{{ $demographic >= 0 ? '#16a34a' : '#dc2626' }};">{{ $demographic > 0 ? '+' : '' }}{{ $demographic }}</strong> <span style="color:var(--u-muted,#64748b);">puan</span></td>
                </tr>
                @if($decay !== 0)
                <tr>
                    <td class="lbl">Inaktivite Düşüşü</td>
                    <td><strong style="color:#dc2626;">{{ $decay }}</strong> <span style="color:var(--u-muted,#64748b);">puan</span></td>
                </tr>
                @endif
            </table>
            @if($score === 0 && $behavioral === 0 && $demographic === 0)
                <div style="font-size:10px;color:var(--u-muted,#64748b);margin-top:6px;">Henüz aktivite yok — form gönderildiğinde puan başlar.</div>
            @endif
        </section>

        {{-- Durum & Lead Bilgisi --}}
        <section class="panel gd-panel">
            <h2>Lead Durumu</h2>
            @php
                $badgeClass = match($guest->lead_status) {
                    'new'       => 'info',
                    'contacted' => 'warn',
                    'qualified' => 'badge',
                    'converted' => 'ok',
                    'lost'      => 'danger',
                    default     => 'badge',
                };
                $leadStatusLabel = match($guest->lead_status ?? '') {
                    'new'       => 'Yeni',
                    'contacted' => 'İletişime Geçildi',
                    'qualified' => 'Nitelikli',
                    'converted' => 'Dönüştü',
                    'lost'      => 'Kayboldu',
                    default     => ($guest->lead_status ?: '–'),
                };
            @endphp
            <div class="gd-readonly">
                Mevcut Durum: <span class="badge {{ $badgeClass }}">{{ $leadStatusLabel }}</span>
            </div>

            <form method="POST" action="/manager/guests/{{ $guest->id }}/status">
                @csrf @method('PATCH')
                <div class="gd-field">
                    <label>Durum Güncelle</label>
                    <select name="lead_status">
                        <option value="">– Seç –</option>
                        @foreach(['new'=>'Yeni','contacted'=>'İletişime Geçildi','qualified'=>'Nitelikli','converted'=>'Dönüştü','lost'=>'Kayboldu'] as $sv => $sl)
                            <option value="{{ $sv }}" @selected($guest->lead_status === $sv)>{{ $sl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="gd-field">
                    <label>Öncelik</label>
                    <select name="priority">
                        <option value="">– Seç –</option>
                        <option value="low"    @selected($guest->priority === 'low')>Düşük</option>
                        <option value="normal" @selected($guest->priority === 'normal')>Normal</option>
                        <option value="high"   @selected($guest->priority === 'high')>Yüksek</option>
                    </select>
                </div>
                <div class="gd-field">
                    <label>Notlar</label>
                    <textarea name="notes" rows="4">{{ $guest->notes }}</textarea>
                </div>
                <div class="gd-actions">
                    <button class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </section>

        {{-- 🔐 Şifre Sıfırlama — addon partial, hata olsa bile guest-detail çalışır --}}
        @includeIf('manager.partials.password-reset-card', [
            'email'       => $guestUser?->email ?? trim((string) $guest->email),
            'name'        => trim($guest->first_name . ' ' . $guest->last_name) ?: ($guestUser?->email ?? $guest->email),
            'defaultRole' => 'guest',
            'personLabel' => 'Aday Öğrenci',
            'idSuffix'    => 'guest' . $guest->id,
        ])

        {{-- Eğitim Danışmanı Atama

             Partner danışmanı GÖRÜR ama ATAYAMAZ: danışman üst firmanın
             elemanı, partner firma ona dışarıdan görev veremez. Atama formu
             gizli, adres de RestrictPartnerPanel::DENIED ile kapalı. --}}
        <section class="panel gd-panel">
            <h2>@partnerPanel Eğitim Danışmanı @else Eğitim Danışmanı Ataması @endpartnerPanel</h2>
            @if($guest->assigned_senior_email)
                <div class="gd-readonly">
                    Mevcut: <strong>{{ $guest->assigned_senior_email }}</strong>
                    @if($guest->assigned_at)
                        <span class="muted">({{ optional($guest->assigned_at)->format('d.m.Y H:i') }})</span>
                    @endif
                    @if($guest->assigned_by)
                        <span class="muted"> – atan: {{ $guest->assigned_by }}</span>
                    @endif
                </div>
            @else
                <div class="gd-readonly muted">
                    Henüz eğitim danışmanı atanmamış.@partnerPanel Atamayı operasyonu yürüten firma yapar.@endpartnerPanel
                </div>
            @endif

            @unlesspartnerPanel
            <form method="POST" action="/manager/guests/{{ $guest->id }}/assign">
                @csrf @method('PATCH')
                <div class="gd-field">
                    <label>Eğitim Danışmanı Seç</label>
                    <select name="assigned_senior_email">
                        <option value="">– Atamayı Kaldır –</option>
                        @foreach($seniorOptions as $email => $label)
                            <option value="{{ $email }}" @selected($guest->assigned_senior_email === $email)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="gd-actions">
                    <button class="btn btn-primary">Ata</button>
                </div>
            </form>
            @endpartnerPanel
        </section>

        {{-- Dönüşen Öğrenci --}}
        @if($student)
            <section class="panel gd-panel">
                <h2>Dönüşen Öğrenci</h2>
                <table class="gd-table">
                    <tr><td class="lbl">Öğrenci ID</td>
                        <td><a href="/manager/students/{{ urlencode($student->student_id) }}"><strong>{{ $student->student_id }}</strong></a></td></tr>
                    <tr><td class="lbl">Eğitim Danışmanı</td>
                        <td>{{ $student->senior_email ?: '–' }}</td></tr>
                    <tr><td class="lbl">Şube</td>
                        <td>{{ $student->branch ?: '–' }}</td></tr>
                    <tr><td class="lbl">Risk</td>
                        <td>{{ $student->risk_level ?: '–' }}</td></tr>
                    <tr><td class="lbl">Ödeme</td>
                        <td>{{ $student->payment_status ?: '–' }}</td></tr>
                </table>
            </section>
        @endif

        {{-- KVKK & Belge --}}
        <section class="panel gd-panel">
            <h2>Onay & Belge</h2>
            <table class="gd-table">
                <tr><td class="lbl">KVKK Onayı</td>
                    <td>
                        @if($guest->kvkk_consent)
                            <span class="badge ok">Verildi</span>
                        @else
                            <span class="badge danger">Verilmedi</span>
                        @endif
                    </td></tr>
                <tr><td class="lbl">Belgeler Hazır</td>
                    <td>
                        @if($guest->docs_ready)
                            <span class="badge ok">Evet</span>
                        @else
                            <span class="badge">Hayır</span>
                        @endif
                    </td></tr>
                <tr><td class="lbl">Form Gönderildi</td>
                    <td>{{ optional($guest->registration_form_submitted_at)->format('d.m.Y H:i') ?: '–' }}</td></tr>
            </table>
        </section>

    </div>
</div>

{{-- ── Belgeler ── --}}
@php
    $docOwnerId = trim((string) ($guest->converted_student_id ?? ''));
    if ($docOwnerId === '') {
        $docOwnerId = 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
    }
    $documents = \App\Models\Document::where('student_id', $docOwnerId)->with('category')->latest()->limit(50)->get();
@endphp
<div style="margin-top:16px;">
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <div style="font-weight:700;font-size:var(--tx-base);">Yüklenen Belgeler</div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $documents->count() }} belge</span>
                @module('doc_request')
                    @if(\Illuminate\Support\Facades\Route::has('manager.guest.document-tokens.store')
                        && \Illuminate\Support\Facades\Route::has('manager.guest.document-tokens.index'))
                    <button type="button" id="docReqOpenBtn"
                            style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;font-size:var(--tx-xs);font-weight:600;color:#fff;background:linear-gradient(135deg,#1e40af,#3b5fcc);border:none;border-radius:6px;cursor:pointer;">
                        📲 Belge Talep Et
                    </button>
                    @endif
                @endmodule
                @if($documents->isNotEmpty())
                    <a href="{{ route('manager.guest.documents.zip', $guest->id) }}"
                       style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;font-size:var(--tx-xs);font-weight:600;color:#fff;background:#7c3aed;border-radius:6px;text-decoration:none;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        ZIP
                    </a>
                @endif
            </div>
        </div>
        @php
            $ocrSchemas = app(\App\Services\DocumentOcrSchemas::class);
        @endphp
        @forelse($documents as $doc)
            @php
                $mime = strtolower((string) ($doc->mime_type ?? ''));
                $canPreview = str_starts_with($mime, 'image/') || $mime === 'application/pdf';
                $catCode = (string) ($doc->category->code ?? '');
                $ocrSchema = $ocrSchemas->getSchemaForCategory($catCode);
                $ocrStatus = (string) ($doc->extraction_status ?? '');
                $hasOcr    = $ocrSchema !== null;
                $extData   = is_array($doc->extracted_data) ? $doc->extracted_data : [];
                $confidence = $doc->extraction_confidence !== null ? (float) $doc->extraction_confidence : null;
            @endphp
            <div style="border-bottom:1px solid var(--u-line);">
                <div style="padding:10px 18px;display:flex;align-items:center;gap:10px;font-size:var(--tx-sm);">
                    <span style="font-size:16px;">
                        @if($doc->status === 'approved') ✅
                        @elseif(in_array($doc->status, ['review','uploaded'])) ⏳
                        @else ❌
                        @endif
                    </span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $doc->title ?? $doc->original_file_name ?? $doc->document_code ?? 'Belge' }}</div>
                        <div style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $doc->category->name ?? $doc->category->code ?? '' }} · {{ $doc->updated_at?->format('d.m.Y H:i') }}</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                        @if($hasOcr)
                            <span class="ocr-status-badge"
                                  data-doc-id="{{ $doc->id }}"
                                  style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;font-size:var(--tx-xs);font-weight:600;border-radius:5px;
                                  {{ $ocrStatus === 'completed' ? 'background:#dcfce7;color:#166534;' :
                                     ($ocrStatus === 'failed' ? 'background:#fee2e2;color:#991b1b;' :
                                     ($ocrStatus === 'processing' || $ocrStatus === 'pending' ? 'background:#fef3c7;color:#92400e;' : 'background:#f1f5f9;color:#475569;')) }}"
                                  title="AI veri çıkarımı durumu">
                                <x-icon name="sparkles" size="12" />
                                <span class="ocr-status-label">
                                    @switch($ocrStatus)
                                        @case('completed') {{ $confidence !== null ? round($confidence * 100) . '%' : 'OK' }} @break
                                        @case('failed') Hata @break
                                        @case('processing') İşleniyor @break
                                        @case('pending') Kuyrukta @break
                                        @default Hazır değil
                                    @endswitch
                                </span>
                            </span>
                        @endif
                        <span class="badge {{ match($doc->status) { 'approved' => 'ok', 'review', 'uploaded' => 'warn', default => 'danger' } }}">
                            {{ match($doc->status) { 'approved' => 'Onaylandı', 'review' => 'İncelemede', 'uploaded' => 'Yüklendi', default => 'Bekliyor' } }}
                        </span>
                        @if($hasOcr)
                            <button type="button" class="ocr-toggle-btn"
                                    data-doc-id="{{ $doc->id }}"
                                    style="padding:3px 8px;font-size:var(--tx-xs);background:var(--u-bg);border:1px solid var(--u-line);border-radius:5px;cursor:pointer;color:var(--u-text);display:inline-flex;align-items:center;gap:3px;"
                                    title="Çıkarılan veriler">
                                <x-icon name="file-text" size="14" />
                            </button>
                        @endif
                        @if($canPreview)
                            <button type="button" class="doc-preview-btn"
                                    data-url="{{ route('manager.guest.document.serve', [$guest->id, $doc->id]) }}"
                                    data-mime="{{ $mime }}"
                                    data-name="{{ $doc->title ?? $doc->original_file_name ?? 'Belge' }}"
                                    style="padding:3px 8px;font-size:var(--tx-xs);background:var(--u-bg);border:1px solid var(--u-line);border-radius:5px;cursor:pointer;color:var(--u-text);"
                                    title="Önizle">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        @endif
                        <a href="{{ route('manager.guest.document.download', [$guest->id, $doc->id]) }}"
                           style="padding:3px 8px;font-size:var(--tx-xs);background:var(--u-bg);border:1px solid var(--u-line);border-radius:5px;text-decoration:none;color:var(--u-text);"
                           title="İndir">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </a>
                    </div>
                </div>
                @if($hasOcr)
                    {{-- OCR çıkarılan veri paneli — toggle ile açılır kapanır --}}
                    <div class="ocr-panel"
                         data-doc-id="{{ $doc->id }}"
                         data-category-label="{{ $ocrSchema['category_label'] ?? '' }}"
                         data-extract-url="{{ route('manager.documents.re-extract', $doc->id) }}"
                         data-show-url="{{ route('manager.documents.extraction.show', $doc->id) }}"
                         style="display:none;padding:12px 18px;background:var(--u-bg);border-top:1px dashed var(--u-line);font-size:var(--tx-xs);">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;gap:8px;flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:6px;font-weight:600;color:var(--u-text);">
                                <x-icon name="sparkles" size="14" />
                                AI Çıkarılan Veriler — {{ $ocrSchema['category_label'] }}
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;">
                                @if($ocrStatus === 'completed')
                                    <button type="button" class="ocr-action-btn ocr-approve-btn"
                                            style="display:inline-flex;align-items:center;gap:3px;padding:4px 10px;font-size:var(--tx-xs);font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:5px;cursor:pointer;">
                                        <x-icon name="check" size="12" />
                                        Verileri Onayla
                                    </button>
                                    <button type="button" class="ocr-action-btn ocr-edit-btn"
                                            style="display:inline-flex;align-items:center;gap:3px;padding:4px 10px;font-size:var(--tx-xs);font-weight:600;background:var(--u-card);color:var(--u-text);border:1px solid var(--u-line);border-radius:5px;cursor:pointer;">
                                        <x-icon name="pencil" size="12" />
                                        Düzenle
                                    </button>
                                @endif
                                <button type="button" class="ocr-action-btn ocr-reextract-btn"
                                        style="display:inline-flex;align-items:center;gap:3px;padding:4px 10px;font-size:var(--tx-xs);font-weight:600;background:var(--u-card);color:var(--u-text);border:1px solid var(--u-line);border-radius:5px;cursor:pointer;">
                                    <x-icon name="refresh-cw" size="12" />
                                    Belgeden Tekrar Çıkar
                                </button>
                            </div>
                        </div>

                        <div class="ocr-panel-body">
                            @if($ocrStatus === 'completed' && !empty($extData))
                                <table style="width:100%;border-collapse:collapse;">
                                    <thead>
                                        <tr style="background:var(--u-card);">
                                            <th style="text-align:left;padding:6px 10px;border-bottom:1px solid var(--u-line);font-weight:600;width:35%;">Alan</th>
                                            <th style="text-align:left;padding:6px 10px;border-bottom:1px solid var(--u-line);font-weight:600;">Değer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ocrSchema['fields'] as $field)
                                            @php
                                                $val = $extData[$field['key']] ?? null;
                                                $display = is_array($val) ? implode(', ', array_map('strval', $val)) : (string) ($val ?? '');
                                                $isEmpty = $display === '' || $val === null;
                                            @endphp
                                            <tr>
                                                <td style="padding:6px 10px;border-bottom:1px solid var(--u-line);color:var(--u-muted);">
                                                    {{ $field['label'] }}
                                                    @if(!empty($field['required']))
                                                        <span style="color:#dc2626;" title="Zorunlu">*</span>
                                                    @endif
                                                </td>
                                                <td style="padding:6px 10px;border-bottom:1px solid var(--u-line);">
                                                    @if($isEmpty)
                                                        <span style="color:var(--u-muted);font-style:italic;">—</span>
                                                    @else
                                                        <span style="font-family:'SF Mono', Monaco, Menlo, monospace;">{{ $display }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if($confidence !== null)
                                    <div style="margin-top:8px;font-size:var(--tx-xs);color:var(--u-muted);">
                                        Güven: <strong>{{ round($confidence * 100) }}%</strong>
                                        · Çıkarım: {{ $doc->extracted_at?->format('d.m.Y H:i') ?? '—' }}
                                    </div>
                                @endif
                            @elseif($ocrStatus === 'failed')
                                <div style="padding:12px;background:#fee2e2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;">
                                    <strong>Çıkarım başarısız.</strong>
                                    @if(!empty($doc->extraction_error))
                                        <div style="margin-top:4px;font-family:'SF Mono', Monaco, Menlo, monospace;font-size:11px;opacity:.85;">
                                            {{ \Illuminate\Support\Str::limit($doc->extraction_error, 200) }}
                                        </div>
                                    @endif
                                    <div style="margin-top:6px;">"Belgeden Tekrar Çıkar" butonu ile yeniden deneyebilirsiniz.</div>
                                </div>
                            @elseif($ocrStatus === 'processing' || $ocrStatus === 'pending')
                                <div style="padding:12px;background:#fef3c7;border:1px solid #fde68a;border-radius:6px;color:#92400e;">
                                    AI veri çıkarımı işleniyor… Sonuç birkaç saniye içinde burada görüntülenecek.
                                </div>
                            @else
                                <div style="padding:12px;background:var(--u-card);border:1px dashed var(--u-line);border-radius:6px;color:var(--u-muted);text-align:center;">
                                    Bu belge için henüz çıkarım yapılmadı. Başlatmak için yukarıdaki <strong>Belgeden Tekrar Çıkar</strong> butonuna basın.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div style="padding:20px 18px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">
                Henüz belge yüklenmemiş.
            </div>
        @endforelse
    </div>
</div>

{{-- OCR panel toggle + re-extract + poll script (CSP-safe, nonce'lu) --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Panel aç/kapa
    document.querySelectorAll('.ocr-toggle-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            const id = btn.getAttribute('data-doc-id');
            const panel = document.querySelector('.ocr-panel[data-doc-id="' + id + '"]');
            if (!panel) return;
            panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ? 'block' : 'none';
        });
    });

    // Re-extract → POST endpoint + polling
    document.querySelectorAll('.ocr-panel').forEach(function(panel){
        const docId = panel.getAttribute('data-doc-id');
        const extractUrl = panel.getAttribute('data-extract-url');
        const showUrl = panel.getAttribute('data-show-url');
        const reBtn = panel.querySelector('.ocr-reextract-btn');
        const body = panel.querySelector('.ocr-panel-body');
        const badge = document.querySelector('.ocr-status-badge[data-doc-id="' + docId + '"]');

        let pollTimer = null;

        function setBadge(status, confidence){
            if (!badge) return;
            const label = badge.querySelector('.ocr-status-label');
            const styles = {
                completed:  {bg:'#dcfce7', fg:'#166534', text: confidence !== null ? Math.round(confidence*100) + '%' : 'OK'},
                failed:     {bg:'#fee2e2', fg:'#991b1b', text: 'Hata'},
                processing: {bg:'#fef3c7', fg:'#92400e', text: 'İşleniyor'},
                pending:    {bg:'#fef3c7', fg:'#92400e', text: 'Kuyrukta'},
            };
            const s = styles[status] || {bg:'#f1f5f9', fg:'#475569', text:'Hazır değil'};
            badge.style.background = s.bg;
            badge.style.color = s.fg;
            if (label) label.textContent = s.text;
        }

        function render(json){
            const status = json.extraction_status || '';
            const data = json.extracted_data || {};
            const fields = json.schema_fields || [];
            const confidence = json.extraction_confidence;
            setBadge(status, confidence);

            if (status === 'completed') {
                let html = '<table style="width:100%;border-collapse:collapse;"><thead><tr style="background:var(--u-card);">' +
                    '<th style="text-align:left;padding:6px 10px;border-bottom:1px solid var(--u-line);font-weight:600;width:35%;">Alan</th>' +
                    '<th style="text-align:left;padding:6px 10px;border-bottom:1px solid var(--u-line);font-weight:600;">Değer</th></tr></thead><tbody>';
                fields.forEach(function(f){
                    const v = data[f.key];
                    const display = Array.isArray(v) ? v.join(', ') : (v == null ? '' : String(v));
                    const isEmpty = display === '';
                    const reqMark = f.required ? ' <span style="color:#dc2626;">*</span>' : '';
                    const val = isEmpty
                        ? '<span style="color:var(--u-muted);font-style:italic;">—</span>'
                        : '<span style="font-family:\'SF Mono\', Monaco, Menlo, monospace;">' + escapeHtml(display) + '</span>';
                    html += '<tr><td style="padding:6px 10px;border-bottom:1px solid var(--u-line);color:var(--u-muted);">' + escapeHtml(f.label) + reqMark +
                        '</td><td style="padding:6px 10px;border-bottom:1px solid var(--u-line);">' + val + '</td></tr>';
                });
                html += '</tbody></table>';
                if (confidence !== null && confidence !== undefined) {
                    html += '<div style="margin-top:8px;font-size:var(--tx-xs);color:var(--u-muted);">Güven: <strong>' + Math.round(confidence*100) + '%</strong></div>';
                }
                body.innerHTML = html;
            } else if (status === 'failed') {
                const err = json.extraction_error ? '<div style="margin-top:4px;font-family:\'SF Mono\',monospace;font-size:11px;opacity:.85;">' + escapeHtml(json.extraction_error.substring(0,200)) + '</div>' : '';
                body.innerHTML = '<div style="padding:12px;background:#fee2e2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;"><strong>Çıkarım başarısız.</strong>' + err + '<div style="margin-top:6px;">"Belgeden Tekrar Çıkar" butonu ile yeniden deneyebilirsiniz.</div></div>';
            } else if (status === 'processing' || status === 'pending') {
                body.innerHTML = '<div style="padding:12px;background:#fef3c7;border:1px solid #fde68a;border-radius:6px;color:#92400e;">AI veri çıkarımı işleniyor… Sonuç birkaç saniye içinde burada görüntülenecek.</div>';
            }
        }

        function escapeHtml(s){
            return String(s).replace(/[&<>"']/g, function(c){
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
            });
        }

        function poll(){
            fetch(showUrl, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
                .then(function(r){ return r.json(); })
                .then(function(json){
                    if (!json.ok) return;
                    render(json);
                    if (json.extraction_status === 'processing' || json.extraction_status === 'pending') {
                        pollTimer = setTimeout(poll, 3000);
                    }
                })
                .catch(function(){});
        }

        if (reBtn) {
            reBtn.addEventListener('click', function(){
                reBtn.disabled = true;
                reBtn.style.opacity = '.6';
                fetch(extractUrl, {
                    method: 'POST',
                    headers: {'Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},
                })
                .then(function(r){ return r.json(); })
                .then(function(json){
                    if (json.ok) {
                        setBadge('pending', null);
                        body.innerHTML = '<div style="padding:12px;background:#fef3c7;border:1px solid #fde68a;border-radius:6px;color:#92400e;">AI veri çıkarımı kuyruğa alındı…</div>';
                        if (pollTimer) clearTimeout(pollTimer);
                        pollTimer = setTimeout(poll, 2500);
                    } else {
                        alert(json.error || 'Yeniden çıkarım başlatılamadı.');
                    }
                })
                .catch(function(){ alert('Sunucuya ulaşılamadı.'); })
                .finally(function(){
                    reBtn.disabled = false;
                    reBtn.style.opacity = '1';
                });
            });
        }

        // Approve / Edit — şimdilik placeholder (manager düzenleme akışı sonraki sprint)
        const approveBtn = panel.querySelector('.ocr-approve-btn');
        if (approveBtn) {
            approveBtn.addEventListener('click', function(){
                alert('Veri onay akışı sonraki sprint\'te aktifleştirilecek. Şimdilik manuel olarak işlem yapabilirsiniz.');
            });
        }
        const editBtn = panel.querySelector('.ocr-edit-btn');
        if (editBtn) {
            editBtn.addEventListener('click', function(){
                alert('Manuel düzenleme akışı sonraki sprint\'te aktifleştirilecek.');
            });
        }

        // Sayfa açıldığında pending/processing varsa otomatik poll başlat
        const initialBadge = badge?.querySelector('.ocr-status-label')?.textContent || '';
        if (initialBadge === 'İşleniyor' || initialBadge === 'Kuyrukta') {
            pollTimer = setTimeout(poll, 2500);
        }
    });
})();
</script>

{{-- ══════════════════════════════════════════════════════════
     C7: Aday Form Cevapları (Level 1 + Level 2 ayrı section'larda)
══════════════════════════════════════════════════════════ --}}
@php
    $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
    $formLevelStatus = (string) ($guest->registration_form_level ?? 'level_1_pending');

    // Catalog'dan level grupları
    $level1Groups = \App\Support\GuestRegistrationFormCatalog::groupsByLevel(1);
    $level2AllGroups = \App\Support\GuestRegistrationFormCatalog::groupsByLevel(2);
    $level1Keys = collect(\App\Support\GuestRegistrationFormCatalog::flatFieldsByLevel(1))->pluck('key')->all();

    // Level 2'ye özgü grupları filtrele (Level 1'de olmayan field'ları içeren bölümler)
    $level2OnlyGroups = collect($level2AllGroups)->map(function ($g) use ($level1Keys) {
        $g['fields'] = collect($g['fields'] ?? [])
            ->reject(fn ($f) => in_array($f['key'] ?? '', $level1Keys, true))
            ->values()
            ->all();
        return $g;
    })->filter(fn ($g) => !empty($g['fields']))->values()->all();

    $hasLevel1Data = collect($level1Keys)->some(fn ($k) => !empty($draft[$k] ?? null) || !empty($guest?->{$k} ?? null));
    $hasLevel2Data = in_array($formLevelStatus, ['level_2_pending', 'level_2_done'], true);

    // Helper: field value formatla
    $formatVal = function ($field, $val) {
        if ($val === null || (is_string($val) && trim($val) === '') || (is_array($val) && empty($val))) {
            return '<em style="color:var(--u-muted,#94a3b8);">—</em>';
        }
        if (is_array($val)) {
            // checkbox_group / multi-select → label'lar
            $opts = collect($field['options'] ?? [])->keyBy('value');
            return collect($val)->map(fn ($v) => e($opts[$v]['label'] ?? $v))->join(', ');
        }
        // select → label döndür (varsa)
        if (($field['type'] ?? '') === 'select' && !empty($field['options'])) {
            $opts = collect($field['options'])->keyBy('value');
            $hit = $opts[$val] ?? null;
            if ($hit) return e($hit['label']);
        }
        return e((string) $val);
    };
@endphp

@if($hasLevel1Data || $hasLevel2Data)
<div style="margin-top:16px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
        <h2 style="font-size:14px;font-weight:800;color:var(--u-text,#0f172a);margin:0;">📋 Form Cevapları</h2>
        @php
            $levelBadge = match($formLevelStatus) {
                'level_2_done'    => ['Tam Form (Level 2) — Tamamlandı', '#16a34a', '#dcfce7'],
                'level_2_pending' => ['Tam Form (Level 2) — Devam Ediyor', '#d97706', '#fef3c7'],
                'level_1_done'    => ['Aday Form (Level 1) — Tamamlandı', '#16a34a', '#dcfce7'],
                default           => ['Aday Form (Level 1) — Devam Ediyor', '#d97706', '#fef3c7'],
            };
        @endphp
        <span style="font-size:10px;font-weight:700;padding:3px 9px;border-radius:10px;color:{{ $levelBadge[1] }};background:{{ $levelBadge[2] }};">{{ $levelBadge[0] }}</span>
    </div>

    {{-- Level 1 cevapları (her zaman görünür eğer veri varsa) --}}
    <details class="panel gd-panel" style="margin-bottom:10px;" open>
        <summary style="cursor:pointer;font-weight:700;font-size:13px;padding:6px 0;color:var(--u-text,#0f172a);">
            🎓 Aday Öğrenci Formu (Level 1)
            <span style="font-size:11px;font-weight:500;color:var(--u-muted,#64748b);margin-left:6px;">{{ collect($level1Groups)->sum(fn($g) => count($g['fields'] ?? [])) }} alan</span>
        </summary>
        <div style="padding-top:10px;">
            @foreach($level1Groups as $group)
                <div style="margin-bottom:14px;">
                    <h3 style="font-size:11px;font-weight:700;color:var(--u-brand,#2563eb);text-transform:uppercase;letter-spacing:.04em;margin:0 0 6px;border-bottom:1px solid var(--u-line,#e5e9f0);padding-bottom:4px;">{{ $group['title'] ?? '' }}</h3>
                    <table class="gd-table" style="font-size:12px;">
                        @foreach(($group['fields'] ?? []) as $f)
                            @php $v = $draft[$f['key']] ?? ($guest?->{$f['key']} ?? null); @endphp
                            <tr>
                                <td class="lbl" style="width:200px;">{{ trim(rtrim($f['label'] ?? $f['key'], ' *')) }}</td>
                                <td>{!! $formatVal($f, $v) !!}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach
        </div>
    </details>

    {{-- Level 2 ek cevaplar — sadece Level 2'ye özgü field'lar --}}
    @if($hasLevel2Data && !empty($level2OnlyGroups))
    <details class="panel gd-panel" style="margin-bottom:10px;" {{ $formLevelStatus === 'level_2_done' ? 'open' : '' }}>
        <summary style="cursor:pointer;font-weight:700;font-size:13px;padding:6px 0;color:var(--u-text,#0f172a);">
            📚 Tam Başvuru Formu — Ek Bilgiler (Level 2)
            <span style="font-size:11px;font-weight:500;color:var(--u-muted,#64748b);margin-left:6px;">{{ collect($level2OnlyGroups)->sum(fn($g) => count($g['fields'] ?? [])) }} alan</span>
        </summary>
        <div style="padding-top:10px;">
            @foreach($level2OnlyGroups as $group)
                <div style="margin-bottom:14px;">
                    <h3 style="font-size:11px;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.04em;margin:0 0 6px;border-bottom:1px solid var(--u-line,#e5e9f0);padding-bottom:4px;">{{ $group['title'] ?? '' }}</h3>
                    <table class="gd-table" style="font-size:12px;">
                        @foreach(($group['fields'] ?? []) as $f)
                            @php $v = $draft[$f['key']] ?? ($guest?->{$f['key']} ?? null); @endphp
                            <tr>
                                <td class="lbl" style="width:200px;">{{ trim(rtrim($f['label'] ?? $f['key'], ' *')) }}</td>
                                <td>{!! $formatVal($f, $v) !!}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach
        </div>
    </details>
    @endif
</div>
@endif

{{-- 🔐 Şifre Sıfırla JS handler — artık partial içinde, burası temizlendi --}}

{{-- ── Belge Önizleme Modal ── --}}
<div id="doc-preview-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.7);align-items:center;justify-content:center;">
    <div style="position:relative;background:var(--u-card,#fff);border-radius:12px;width:90vw;max-width:900px;height:85vh;display:flex;flex-direction:column;overflow:hidden;">
        <div style="padding:12px 18px;border-bottom:1px solid var(--u-line,#e5e7eb);display:flex;justify-content:space-between;align-items:center;">
            <span id="doc-preview-title" style="font-weight:700;font-size:var(--tx-sm);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
            <button id="doc-preview-close" type="button" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--u-text,#333);line-height:1;padding:0 4px;">✕</button>
        </div>
        <div id="doc-preview-body" style="flex:1;overflow:auto;display:flex;align-items:center;justify-content:center;padding:12px;background:var(--u-bg,#f9fafb);"></div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var modal = document.getElementById('doc-preview-modal');
    var body  = document.getElementById('doc-preview-body');
    var title = document.getElementById('doc-preview-title');

    document.querySelectorAll('.doc-preview-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var url  = this.getAttribute('data-url');
            var mime = this.getAttribute('data-mime');
            var name = this.getAttribute('data-name');
            title.textContent = name;
            body.innerHTML = '';

            if (mime === 'application/pdf') {
                body.innerHTML = '<iframe src="' + url + '" style="width:100%;height:100%;border:none;"></iframe>';
            } else {
                body.innerHTML = '<img src="' + url + '" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:6px;" alt="' + name + '">';
            }

            modal.style.display = 'flex';
        });
    });

    document.getElementById('doc-preview-close').addEventListener('click', function(){
        modal.style.display = 'none';
        body.innerHTML = '';
    });

    modal.addEventListener('click', function(e){
        if (e.target === modal) {
            modal.style.display = 'none';
            body.innerHTML = '';
        }
    });

    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            modal.style.display = 'none';
            body.innerHTML = '';
        }
    });
})();
</script>

@module('doc_request')
@if(\Illuminate\Support\Facades\Route::has('manager.guest.document-tokens.store')
    && \Illuminate\Support\Facades\Route::has('manager.guest.document-tokens.index'))
{{-- ── Belge Talep Linki Modal (Premium: doc_request) ─────────────────────── --}}
<div id="docReqModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:14px;max-width:520px;width:100%;max-height:92vh;overflow:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="padding:18px 22px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <strong style="font-size:15px;">📲 Belge Talep Linki Oluştur</strong>
            <button type="button" id="docReqCloseBtn" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <div id="docReqBody" style="padding:18px 22px;">
            <p style="font-size:13px;color:#475569;line-height:1.5;margin:0 0 14px;">
                Aday öğrenciye gönderilecek tek-kullanımlık link oluştur. Aday telefonunda açıp belgeyi fotoğraflayabilir.
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
                        placeholder="Örn: Pasaportunuzu net çekin, köşeleri görünsün."></textarea>
                </label>

                {{-- D7: hatırlatma için email --}}
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">
                        Alıcı E-posta <span style="color:#94a3b8;font-weight:500;text-transform:none;letter-spacing:0;">(opsiyonel — hatırlatma)</span>
                    </span>
                    <input type="email" id="docReqEmail" maxlength="180"
                           value="{{ $guest->email ?? '' }}"
                           placeholder="aday@example.com"
                           style="padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;">
                </label>

                {{-- D6: WhatsApp direkt aç için telefon --}}
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">
                        WhatsApp Telefon <span style="color:#94a3b8;font-weight:500;text-transform:none;letter-spacing:0;">(opsiyonel)</span>
                    </span>
                    <input type="tel" id="docReqPhone" maxlength="50"
                           value="{{ $guest->phone ?? '' }}"
                           placeholder="+905551234567"
                           style="padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;">
                </label>
            </div>

            <button type="button" id="docReqGenBtn"
                    style="margin-top:16px;width:100%;padding:12px 18px;border:none;border-radius:10px;background:linear-gradient(135deg,#1e40af,#3b5fcc);color:#fff;font-size:14px;font-weight:700;cursor:pointer;">
                🔗 Linki Oluştur
            </button>

            <div id="docReqResult" style="display:none;margin-top:16px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
                <div style="font-size:12px;font-weight:700;color:#166534;margin-bottom:8px;">✅ Link hazır — adaya gönder:</div>
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
                <div style="font-size:11px;color:#65a30d;margin-top:8px;line-height:1.5;">
                    Bu link tek-kullanımlık. Aday yüklediğinde otomatik geçersizleşir.
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
    var emailInput = document.getElementById('docReqEmail');
    var phoneInput = document.getElementById('docReqPhone');
    var genBtn = document.getElementById('docReqGenBtn');
    var resultBox = document.getElementById('docReqResult');
    var urlInput = document.getElementById('docReqUrl');
    var copyBtn = document.getElementById('docReqCopyBtn');
    var waBtn = document.getElementById('docReqWhatsAppBtn');

    if (!openBtn) return;

    var GUEST_ID = {{ $guest->id }};
    var CSRF = '{{ csrf_token() }}';
    var INDEX_URL = "{{ route('manager.guest.document-tokens.index', $guest->id) }}";
    var STORE_URL = "{{ route('manager.guest.document-tokens.store', $guest->id) }}";

    function loadCategories(){
        catSelect.innerHTML = '<option value="">— Yükleniyor —</option>';
        fetch(INDEX_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
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
                    var og = document.createElement('optgroup');
                    og.label = labelMap[top];
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

    function openModal(){
        modal.style.display = 'flex';
        resultBox.style.display = 'none';
        messageInput.value = '';
        loadCategories();
    }
    function closeModal(){ modal.style.display = 'none'; }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

    genBtn.addEventListener('click', function(){
        var cat = catSelect.value;
        if (!cat) { alert('Lütfen bir belge seç.'); return; }
        genBtn.disabled = true;
        genBtn.textContent = '⏳ Oluşturuluyor...';
        fetch(STORE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                category_code: cat,
                expires_hours: parseInt(expirySelect.value, 10) || 48,
                custom_message: messageInput.value || null,
                recipient_email: (emailInput && emailInput.value) ? emailInput.value.trim() : null,
                recipient_phone: (phoneInput && phoneInput.value) ? phoneInput.value.trim() : null,
            })
        })
        .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
        .then(res => {
            genBtn.disabled = false;
            genBtn.textContent = '🔗 Linki Oluştur';
            if (!res.ok) { alert(res.data.error || 'Hata oluştu.'); return; }
            urlInput.value = res.data.url;
            var msg = 'Merhaba, MentorDE\'den belge talebimiz var. Lütfen aşağıdaki linke tıklayıp belgeyi yükleyin:\n\n' + res.data.url;
            // D6: telefon varsa direkt o numaraya yönlendir (E.164 normalize)
            var phoneRaw = (phoneInput && phoneInput.value) ? phoneInput.value.trim() : '';
            var phoneDigits = phoneRaw.replace(/[^0-9]/g, '');
            if (phoneDigits.length > 0) {
                if (phoneDigits.charAt(0) === '0') phoneDigits = phoneDigits.substring(1);
                if (phoneDigits.length === 10) phoneDigits = '90' + phoneDigits;
                waBtn.href = 'https://wa.me/' + phoneDigits + '?text=' + encodeURIComponent(msg);
            } else {
                waBtn.href = 'https://wa.me/?text=' + encodeURIComponent(msg);
            }
            resultBox.style.display = 'block';
        })
        .catch(() => {
            genBtn.disabled = false;
            genBtn.textContent = '🔗 Linki Oluştur';
            alert('Bağlantı hatası.');
        });
    });

    copyBtn.addEventListener('click', function(){
        urlInput.select();
        navigator.clipboard.writeText(urlInput.value).then(function(){
            copyBtn.textContent = '✓ Kopyalandı';
            setTimeout(function(){ copyBtn.textContent = '📋 Kopyala'; }, 2000);
        }).catch(function(){
            document.execCommand('copy');
        });
    });
})();
</script>
@endif
@endmodule

@endpush

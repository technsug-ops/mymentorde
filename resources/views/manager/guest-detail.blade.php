@extends('manager.layouts.app')

@section('title', 'Manager – Guest Detay #' . $guest->id)
@section('page_title', 'Guest Detay')

@section('content')

<div style="margin-bottom:10px;">
    <a class="btn" href="/manager/guests">← Guest Listesi</a>
</div>

{{-- Dönüşüm Bandı --}}
@if($guest->converted_to_student)
    <div class="panel" style="background:#f0faf4;border-color:var(--u-ok,#21a861);margin-bottom:12px;">
        <strong style="color:var(--u-ok,#21a861);">✓ Öğrenciye Dönüştü</strong>
        @if($guest->converted_student_id)
            — Student ID:
            <a href="/manager/students/{{ urlencode($guest->converted_student_id) }}" style="font-weight:600;">
                {{ $guest->converted_student_id }}
            </a>
        @endif
    </div>
@endif

<div class="grid2">

    {{-- SOL: Kişisel Bilgiler --}}
    <div>
        <section class="panel" style="margin-bottom:12px;">
            <h2>Kişisel Bilgiler</h2>
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                <tr><td class="muted" style="padding:5px 0;width:140px;">ID / Token</td>
                    <td>#{{ $guest->id }} / <code style="font-size:var(--tx-xs);">{{ $guest->tracking_token }}</code></td></tr>
                <tr><td class="muted" style="padding:5px 0;">Ad Soyad</td>
                    <td><strong>{{ $guest->first_name }} {{ $guest->last_name }}</strong></td></tr>
                <tr><td class="muted" style="padding:5px 0;">E-posta</td>
                    <td>{{ $guest->email }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Telefon</td>
                    <td>{{ $guest->phone ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Cinsiyet</td>
                    <td>{{ $guest->gender ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Ülke</td>
                    <td>{{ $guest->application_country ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Dil</td>
                    <td>{{ $guest->communication_language ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Başvuru Türü</td>
                    <td>{{ $guest->application_type ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Kayıt Tarihi</td>
                    <td>{{ optional($guest->created_at)->format('d.m.Y H:i') }}</td></tr>
            </table>
        </section>

        <section class="panel" style="margin-bottom:12px;">
            <h2>Hedef & Tercihler</h2>
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                <tr><td class="muted" style="padding:5px 0;width:140px;">Hedef Dönem</td>
                    <td>{{ $guest->target_term ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Hedef Şehir</td>
                    <td>{{ $guest->target_city ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Dil Seviyesi</td>
                    <td>{{ $guest->language_level ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Şube</td>
                    <td>{{ $guest->branch ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Öncelik</td>
                    <td>{{ $guest->priority ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Risk</td>
                    <td>{{ $guest->risk_level ?: '–' }}</td></tr>
            </table>
        </section>

        <section class="panel" style="margin-bottom:12px;">
            <h2>Paket & Sözleşme</h2>
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                <tr><td class="muted" style="padding:5px 0;width:140px;">Paket Kodu</td>
                    <td>{{ $guest->selected_package_code ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Paket Adı</td>
                    <td>{{ $guest->selected_package_title ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Paket Fiyatı</td>
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
                <tr><td class="muted" style="padding:5px 0;">Sözleşme Durumu</td>
                    <td><span class="badge {{ $csCls }}">{{ $csLbl }}</span></td></tr>
                <tr><td class="muted" style="padding:5px 0;">Sözleşme Talep</td>
                    <td>{{ optional($guest->contract_requested_at)->format('d.m.Y') ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Sözleşme İmza</td>
                    <td>{{ optional($guest->contract_signed_at)->format('d.m.Y') ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Sözleşme Onay</td>
                    <td>{{ optional($guest->contract_approved_at)->format('d.m.Y') ?: '–' }}</td></tr>
            </table>
        </section>

        <section class="panel">
            <h2>UTM / Kaynak İzleme</h2>
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                <tr><td class="muted" style="padding:5px 0;width:140px;">Lead Kaynağı</td>
                    <td>{{ $guest->lead_source ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Dealer Kodu</td>
                    <td>
                        @if($guest->dealer_code)
                            <a href="/manager/dealers/{{ $guest->dealer_code }}">{{ $guest->dealer_code }}</a>
                        @else –
                        @endif
                    </td></tr>
                <tr><td class="muted" style="padding:5px 0;">UTM Source</td>
                    <td>{{ $guest->utm_source ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">UTM Medium</td>
                    <td>{{ $guest->utm_medium ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">UTM Campaign</td>
                    <td>{{ $guest->utm_campaign ?: '–' }}</td></tr>
                <tr><td class="muted" style="padding:5px 0;">Campaign Kodu</td>
                    <td>{{ $guest->campaign_code ?: '–' }}</td></tr>
            </table>
        </section>
    </div>

    {{-- SAĞ: Aksiyonlar --}}
    <div>

        {{-- Durum & Lead Bilgisi --}}
        <section class="panel" style="margin-bottom:12px;">
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
            <div style="margin-bottom:10px;">
                Mevcut Durum: <span class="badge {{ $badgeClass }}">{{ $leadStatusLabel }}</span>
            </div>

            <form method="POST" action="/manager/guests/{{ $guest->id }}/status">
                @csrf @method('PATCH')
                <div style="margin-bottom:8px;">
                    <label class="muted">Durum Güncelle</label>
                    <select name="lead_status" style="width:100%;">
                        <option value="">– Seç –</option>
                        @foreach(['new'=>'Yeni','contacted'=>'İletişime Geçildi','qualified'=>'Nitelikli','converted'=>'Dönüştü','lost'=>'Kayboldu'] as $sv => $sl)
                            <option value="{{ $sv }}" @selected($guest->lead_status === $sv)>{{ $sl }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom:8px;">
                    <label class="muted">Öncelik</label>
                    <select name="priority" style="width:100%;">
                        <option value="">– Seç –</option>
                        <option value="low"    @selected($guest->priority === 'low')>Düşük</option>
                        <option value="normal" @selected($guest->priority === 'normal')>Normal</option>
                        <option value="high"   @selected($guest->priority === 'high')>Yüksek</option>
                    </select>
                </div>
                <div style="margin-bottom:8px;">
                    <label class="muted">Notlar</label>
                    <textarea name="notes" rows="4" style="width:100%;box-sizing:border-box;">{{ $guest->notes }}</textarea>
                </div>
                <button class="btn btn-primary">Kaydet</button>
            </form>
        </section>

        {{-- Senior Atama --}}
        <section class="panel" style="margin-bottom:12px;">
            <h2>Senior Ataması</h2>
            @if($guest->assigned_senior_email)
                <div style="margin-bottom:8px;">
                    <span class="muted">Mevcut Senior:</span>
                    <strong>{{ $guest->assigned_senior_email }}</strong>
                    @if($guest->assigned_at)
                        <span class="muted" style="font-size:var(--tx-xs);">({{ optional($guest->assigned_at)->format('d.m.Y H:i') }})</span>
                    @endif
                    @if($guest->assigned_by)
                        <span class="muted" style="font-size:var(--tx-xs);"> – atan: {{ $guest->assigned_by }}</span>
                    @endif
                </div>
            @else
                <div class="muted" style="margin-bottom:8px;">Henüz senior atanmamış.</div>
            @endif

            <form method="POST" action="/manager/guests/{{ $guest->id }}/assign">
                @csrf @method('PATCH')
                <div style="margin-bottom:8px;">
                    <label class="muted">Senior Seç</label>
                    <select name="assigned_senior_email" style="width:100%;">
                        <option value="">– Atamayı Kaldır –</option>
                        @foreach($seniorOptions as $e)
                            <option value="{{ $e }}" @selected($guest->assigned_senior_email === $e)>{{ $e }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary">Ata</button>
            </form>
        </section>

        {{-- Dönüşen Öğrenci --}}
        @if($student)
            <section class="panel" style="margin-bottom:12px;">
                <h2>Dönüşen Öğrenci</h2>
                <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                    <tr><td class="muted" style="padding:5px 0;width:120px;">Student ID</td>
                        <td><a href="/manager/students/{{ urlencode($student->student_id) }}"><strong>{{ $student->student_id }}</strong></a></td></tr>
                    <tr><td class="muted" style="padding:5px 0;">Senior</td>
                        <td>{{ $student->senior_email ?: '–' }}</td></tr>
                    <tr><td class="muted" style="padding:5px 0;">Şube</td>
                        <td>{{ $student->branch ?: '–' }}</td></tr>
                    <tr><td class="muted" style="padding:5px 0;">Risk</td>
                        <td>{{ $student->risk_level ?: '–' }}</td></tr>
                    <tr><td class="muted" style="padding:5px 0;">Ödeme</td>
                        <td>{{ $student->payment_status ?: '–' }}</td></tr>
                </table>
            </section>
        @endif

        {{-- KVKK & Belge --}}
        <section class="panel">
            <h2>Onay & Belge</h2>
            <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
                <tr><td class="muted" style="padding:5px 0;width:140px;">KVKK Onayı</td>
                    <td>
                        @if($guest->kvkk_consent)
                            <span class="badge ok">Verildi</span>
                        @else
                            <span class="badge danger">Verilmedi</span>
                        @endif
                    </td></tr>
                <tr><td class="muted" style="padding:5px 0;">Belgeler Hazır</td>
                    <td>
                        @if($guest->docs_ready)
                            <span class="badge ok">Evet</span>
                        @else
                            <span class="badge">Hayır</span>
                        @endif
                    </td></tr>
                <tr><td class="muted" style="padding:5px 0;">Form Gönderildi</td>
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
                @if($documents->isNotEmpty())
                    <a href="{{ route('manager.guest.documents.zip', $guest->id) }}"
                       style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;font-size:var(--tx-xs);font-weight:600;color:#fff;background:#7c3aed;border-radius:6px;text-decoration:none;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        ZIP
                    </a>
                @endif
            </div>
        </div>
        @forelse($documents as $doc)
            @php
                $mime = strtolower((string) ($doc->mime_type ?? ''));
                $canPreview = str_starts_with($mime, 'image/') || $mime === 'application/pdf';
            @endphp
            <div style="padding:10px 18px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--u-line);font-size:var(--tx-sm);">
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
                    <span class="badge {{ match($doc->status) { 'approved' => 'ok', 'review', 'uploaded' => 'warn', default => 'danger' } }}">
                        {{ match($doc->status) { 'approved' => 'Onaylandı', 'review' => 'İncelemede', 'uploaded' => 'Yüklendi', default => 'Bekliyor' } }}
                    </span>
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
        @empty
            <div style="padding:20px 18px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">
                Henüz belge yüklenmemiş.
            </div>
        @endforelse
    </div>
</div>

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
@endpush

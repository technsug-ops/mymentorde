@extends('senior.layouts.app')

@section('title', 'Başvuru Detayı')
@section('page_title', 'Başvuru Detayı')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:10px;flex-wrap:wrap;">
    <a href="javascript:history.back()" style="font-size:var(--tx-sm);color:#7c3aed;font-weight:700;text-decoration:none;">← Geri</a>
    @if(\Illuminate\Support\Facades\Route::has('senior.messages.with-guest')
        && strtolower(trim((string) $guest->assigned_senior_email)) === strtolower(trim((string) auth()->user()?->email)))
    <a href="{{ route('senior.messages.with-guest', $guest->id) }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 2px 4px rgba(124,58,237,.3);">
        💬 Aday Öğrenciyle Mesajlaş
    </a>
    @endif
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
        <div style="margin-top:10px;font-size:var(--tx-sm);color:var(--u-muted);">Eğitim Danışmanı: <strong style="color:var(--u-text);">{{ $guest->assigned_senior_email }}</strong></div>
        @endif
        @if($guest->notes)
        <div style="margin-top:10px;padding:10px 12px;background:var(--u-bg);border-radius:8px;font-size:var(--tx-sm);color:var(--u-text);">{{ $guest->notes }}</div>
        @endif
    </div>

    @if($student)
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:16px 18px;margin-bottom:12px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:12px;">Dönüşen Öğrenci</div>
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <tr><td style="padding:5px 0;color:var(--u-muted);width:120px;">Öğrenci ID</td>
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
                    @can('doc_request.use')
                        <button type="button" id="docReqOpenBtn"
                                style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;font-size:var(--tx-xs);font-weight:600;color:#fff;background:linear-gradient(135deg,#1e40af,#3b5fcc);border:none;border-radius:6px;cursor:pointer;">
                            📲 Belge Talep Et
                        </button>
                    @endcan
                @endmodule
                @if($documents->isNotEmpty())
                    <a href="{{ route('senior.guest.documents.zip', $guest->id) }}"
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
                                data-url="{{ route('senior.guest.document.serve', [$guest->id, $doc->id]) }}"
                                data-mime="{{ $mime }}"
                                data-name="{{ $doc->title ?? $doc->original_file_name ?? 'Belge' }}"
                                style="padding:3px 8px;font-size:var(--tx-xs);background:var(--u-bg);border:1px solid var(--u-line);border-radius:5px;cursor:pointer;color:var(--u-text);"
                                title="Önizle">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    @endif
                    <a href="{{ route('senior.guest.document.download', [$guest->id, $doc->id]) }}"
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

@module('doc_request')
@can('doc_request.use')
{{-- ── Belge Talep Linki Modal (Premium + permission) ─────────────────────── --}}
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
    var genBtn = document.getElementById('docReqGenBtn');
    var resultBox = document.getElementById('docReqResult');
    var urlInput = document.getElementById('docReqUrl');
    var copyBtn = document.getElementById('docReqCopyBtn');
    var waBtn = document.getElementById('docReqWhatsAppBtn');

    if (!openBtn) return;

    var CSRF = '{{ csrf_token() }}';
    var INDEX_URL = "{{ route('senior.guest.document-tokens.index', $guest->id) }}";
    var STORE_URL = "{{ route('senior.guest.document-tokens.store', $guest->id) }}";

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
            })
        })
        .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
        .then(res => {
            genBtn.disabled = false;
            genBtn.textContent = '🔗 Linki Oluştur';
            if (!res.ok) { alert(res.data.error || 'Hata oluştu.'); return; }
            urlInput.value = res.data.url;
            var msg = 'Merhaba, MentorDE\'den belge talebimiz var. Lütfen aşağıdaki linke tıklayıp belgeyi yükleyin:\n\n' + res.data.url;
            waBtn.href = 'https://wa.me/?text=' + encodeURIComponent(msg);
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
@endcan
@endmodule

@endpush

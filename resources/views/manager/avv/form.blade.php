@extends('manager.layouts.app')

@section('title', $avv ? 'AVV: ' . $avv->provider_name : 'Yeni AVV Kaydı')
@section('page_title', $avv ? 'AVV Düzenle: ' . $avv->provider_name : 'Yeni AVV Kaydı')

@push('head')
<style>
.af-wrap { max-width:880px; margin:0 auto; padding:0 0 32px; }
.af-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:24px 28px; }
.af-back { font-size:12.5px; color:#64748b; text-decoration:none; display:inline-flex; align-items:center; gap:5px; margin-bottom:14px; }
.af-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.af-row { display:flex; flex-direction:column; gap:6px; }
.af-row.full { grid-column:1/-1; }
.af-row label { font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.5px; }
.af-row input, .af-row select, .af-row textarea {
    padding:9px 11px; border-radius:8px; border:1px solid #cbd5e1;
    font-size:13px; font-family:inherit; background:#fff;
}
.af-row input:focus, .af-row select:focus, .af-row textarea:focus {
    outline:none; border-color:#3b5fcc; box-shadow:0 0 0 3px rgba(59,95,204,.12);
}
.af-row textarea { min-height:60px; resize:vertical; }
.af-row .hint { font-size:11px; color:#94a3b8; }
.af-actions { margin-top:24px; display:flex; gap:10px; justify-content:flex-end; }
.af-btn { padding:10px 22px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:700; text-decoration:none; }
.af-btn.cancel { background:#f1f5f9; color:#475569; }
.af-btn.save { background:linear-gradient(135deg,#1e40af,#3b5fcc); color:#fff; }
.current-pdf { padding:8px 12px; background:#dbeafe; border-radius:6px; font-size:12px; color:#1e40af; margin-top:4px; }
@media (max-width:740px) { .af-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@php($gdprActive = 'avv')
@include('manager._partials.gdpr-tabs')

<div class="af-wrap">
    <a href="{{ route('manager.avv.index') }}" class="af-back">← AVV Listesine Dön</a>

    <div class="af-card">
        <h2 style="font-size:18px;font-weight:800;margin:0 0 6px;">
            {{ $avv ? '✏️ AVV Kaydını Düzenle' : '➕ Yeni AVV Kaydı' }}
        </h2>
        <p style="font-size:12.5px;color:#64748b;margin:0 0 18px;line-height:1.5;">
            Bu sağlayıcıyla imzalanan veri işleme sözleşmesinin (AVV/DPA) bilgilerini gir + PDF'i yükle.
        </p>

        @if($errors->any())
            <div style="background:#fef2f2;border-left:3px solid #dc2626;padding:10px 14px;color:#991b1b;font-size:12px;margin-bottom:14px;border-radius:6px;">
                @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        @endif

        <form method="post" enctype="multipart/form-data"
              action="{{ $avv ? route('manager.avv.update', $avv) : route('manager.avv.store') }}">
            @csrf
            @if($avv)@method('PUT')@endif

            <div class="af-grid">
                <div class="af-row full">
                    <label>Sağlayıcı Adı *</label>
                    <input type="text" name="provider_name" required maxlength="190"
                           value="{{ old('provider_name', $avv?->provider_name) }}"
                           placeholder="Brevo, Google Workspace, IONOS, AWS, Stripe...">
                </div>
                <div class="af-row">
                    <label>Web Sitesi (URL)</label>
                    <input type="url" name="provider_url" maxlength="255"
                           value="{{ old('provider_url', $avv?->provider_url) }}"
                           placeholder="https://...">
                </div>
                <div class="af-row">
                    <label>İletişim E-posta</label>
                    <input type="email" name="contact_email" maxlength="190"
                           value="{{ old('contact_email', $avv?->contact_email) }}"
                           placeholder="dpo@provider.com">
                </div>
                <div class="af-row">
                    <label>İmza Tarihi</label>
                    <input type="date" name="signed_date"
                           value="{{ old('signed_date', $avv?->signed_date?->format('Y-m-d')) }}">
                </div>
                <div class="af-row">
                    <label>Bitiş / Yenileme Tarihi</label>
                    <input type="date" name="expires_date"
                           value="{{ old('expires_date', $avv?->expires_date?->format('Y-m-d')) }}">
                    <span class="hint">60 gün öncesinden uyarı verilir.</span>
                </div>
                <div class="af-row">
                    <label>Veri Merkezi Ülkesi</label>
                    <input type="text" name="country" maxlength="100"
                           value="{{ old('country', $avv?->country) }}"
                           placeholder="Almanya, İrlanda, ABD...">
                </div>
                <div class="af-row">
                    <label>EU Tabanlı mı?</label>
                    <select name="eu_based">
                        <option value="1" @selected(old('eu_based', $avv?->eu_based ?? true))>Evet (AB içinde)</option>
                        <option value="0" @selected(!old('eu_based', $avv?->eu_based ?? true))>Hayır (3. ülke - SCC gerekli)</option>
                    </select>
                </div>
                <div class="af-row">
                    <label>Durum *</label>
                    <select name="status" required>
                        @foreach(\App\Models\DataProcessingAgreement::STATUS_LABELS as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $avv?->status ?? 'active') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="af-row full">
                    <label>İşlem Amacı (Kısa Açıklama)</label>
                    <input type="text" name="purpose_summary" maxlength="500"
                           value="{{ old('purpose_summary', $avv?->purpose_summary) }}"
                           placeholder="örn. Newsletter gönderimi + müşteri segmentasyonu">
                </div>
                <div class="af-row full">
                    <label>İşlenen Veri Kategorileri (her satıra bir)</label>
                    <textarea name="processed_categories_text" rows="3"
                              placeholder="email&#10;ad soyad&#10;ödeme bilgisi">{{ old('processed_categories_text', is_array($avv?->processed_categories) ? implode("\n", $avv->processed_categories) : '') }}</textarea>
                </div>
                <div class="af-row full">
                    <label>AVV PDF</label>
                    <input type="file" name="avv_pdf" accept="application/pdf">
                    @if($avv?->avv_pdf_path)
                        <div class="current-pdf">
                            📄 Mevcut PDF: <a href="{{ route('manager.avv.download', $avv) }}" style="font-weight:700;">İndir</a>
                            <span style="color:#64748b;">— Yeni dosya seçersen mevcut silinir.</span>
                        </div>
                    @endif
                </div>
                <div class="af-row full">
                    <label>Notlar</label>
                    <textarea name="notes" rows="3" maxlength="5000">{{ old('notes', $avv?->notes) }}</textarea>
                </div>
            </div>

            <div class="af-actions">
                <a href="{{ route('manager.avv.index') }}" class="af-btn cancel">İptal</a>
                <button type="submit" class="af-btn save">{{ $avv ? '💾 Güncelle' : '➕ Ekle' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

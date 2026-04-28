@extends('manager.layouts.app')

@section('title', $activity ? 'ROPA: ' . $activity->name : 'Yeni İşlem Aktivitesi')
@section('page_title', $activity ? 'ROPA Düzenle: ' . $activity->name : 'Yeni İşlem Aktivitesi')

@push('head')
<style>
.rf-wrap { max-width:880px; margin:0 auto; padding:0 0 32px; }
.rf-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:24px 28px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
.rf-back { font-size:12.5px; color:#64748b; text-decoration:none; display:inline-flex; align-items:center; gap:5px; margin-bottom:14px; }
.rf-back:hover { color:#1e40af; text-decoration:none; }
.rf-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.rf-row { display:flex; flex-direction:column; gap:6px; }
.rf-row.full { grid-column:1/-1; }
.rf-row label { font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.5px; }
.rf-row label .req { color:#dc2626; margin-left:3px; }
.rf-row input, .rf-row select, .rf-row textarea {
    padding:9px 11px; border-radius:8px; border:1px solid #cbd5e1;
    font-size:13px; font-family:inherit; background:#fff;
}
.rf-row input:focus, .rf-row select:focus, .rf-row textarea:focus {
    outline:none; border-color:#3b5fcc; box-shadow:0 0 0 3px rgba(59,95,204,.12);
}
.rf-row textarea { min-height:70px; resize:vertical; }
.rf-row .hint { font-size:11px; color:#94a3b8; line-height:1.4; }
.rf-actions { margin-top:24px; display:flex; gap:10px; justify-content:flex-end; }
.rf-btn { padding:10px 22px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:700; text-decoration:none; }
.rf-btn.cancel { background:#f1f5f9; color:#475569; }
.rf-btn.save { background:linear-gradient(135deg,#1e40af,#3b5fcc); color:#fff; }
.rf-toggle { display:flex; align-items:center; gap:10px; padding:10px 12px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; }
.rf-toggle input { width:18px; height:18px; cursor:pointer; }
.rf-toggle label { margin:0; text-transform:none; letter-spacing:0; font-size:13px; cursor:pointer; }
@media (max-width:740px) { .rf-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@php($gdprActive = 'ropa')
@include('manager._partials.gdpr-tabs')

<div class="rf-wrap">
    <a href="{{ route('manager.ropa.index') }}" class="rf-back">← ROPA Listesine Dön</a>

    <div class="rf-card">
        <h2 style="font-size:18px;font-weight:800;margin:0 0 6px;">
            {{ $activity ? '✏️ İşlem Aktivitesini Düzenle' : '➕ Yeni İşlem Aktivitesi' }}
        </h2>
        <p style="font-size:12.5px;color:#64748b;margin:0 0 18px;line-height:1.5;">
            DSGVO Art. 30 kapsamında bu aktivitenin tüm metadata'sını gir. Tipik örnek: "Newsletter gönderimi · email+isim · Brevo · 2 yıl saklama".
        </p>

        @if($errors->any())
            <div style="background:#fef2f2;border-left:3px solid #dc2626;padding:10px 14px;color:#991b1b;font-size:12px;margin-bottom:14px;border-radius:6px;">
                <strong>Doğrulama hatası:</strong>
                <ul style="margin:6px 0 0;padding-left:18px;">
                    @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ $activity ? route('manager.ropa.update', $activity) : route('manager.ropa.store') }}">
            @csrf
            @if($activity)@method('PUT')@endif

            <div class="rf-grid">
                <div class="rf-row full">
                    <label>Aktivite Adı <span class="req">*</span></label>
                    <input type="text" name="name" required maxlength="190"
                           value="{{ old('name', $activity?->name) }}"
                           placeholder="Örn: Newsletter gönderimi">
                </div>

                <div class="rf-row">
                    <label>Sorumlu (Verantwortlicher)</label>
                    <input type="text" name="responsible" maxlength="190"
                           value="{{ old('responsible', $activity?->responsible) }}"
                           placeholder="Örn: Marketing Müdürü">
                </div>

                <div class="rf-row">
                    <label>Hukuki Dayanak</label>
                    <select name="legal_basis">
                        <option value="">— Seçin —</option>
                        @foreach($legalBasis as $key => $label)
                            <option value="{{ $key }}" @selected(old('legal_basis', $activity?->legal_basis) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rf-row full">
                    <label>İşleme Amacı</label>
                    <textarea name="purpose" maxlength="5000"
                              placeholder="Bu işlem hangi amaçla yapılıyor? (örn. Müşterilere haftalık newsletter göndermek, satış fırsatlarını takip etmek...)">{{ old('purpose', $activity?->purpose) }}</textarea>
                </div>

                <div class="rf-row full">
                    <label>Veri Kategorileri (her satıra bir kategori)</label>
                    <textarea name="data_categories_text" rows="3" placeholder="email&#10;ad soyad&#10;telefon&#10;IP adresi"
                              data-tags-target="data_categories">{{ old('data_categories_text', is_array($activity?->data_categories) ? implode("\n", $activity->data_categories) : '') }}</textarea>
                    <span class="hint">Bu işlemde işlenen veri tipleri (örn. email, ad-soyad, IP, ödeme bilgisi).</span>
                </div>

                <div class="rf-row full">
                    <label>Etkilenen Kişi Kategorileri (her satıra bir)</label>
                    <textarea name="subject_categories_text" rows="2" placeholder="müşteriler&#10;çalışanlar&#10;web ziyaretçileri"
                              data-tags-target="subject_categories">{{ old('subject_categories_text', is_array($activity?->subject_categories) ? implode("\n", $activity->subject_categories) : '') }}</textarea>
                </div>

                <div class="rf-row full">
                    <label>Alıcılar / 3. Taraf İşleyiciler (her satıra bir)</label>
                    <textarea name="recipients_text" rows="3" placeholder="Brevo (newsletter)&#10;DATEV (muhasebe)&#10;AWS (hosting)"
                              data-tags-target="recipients">{{ old('recipients_text', is_array($activity?->recipients) ? implode("\n", $activity->recipients) : '') }}</textarea>
                    <span class="hint">Bu veriyi alan/işleyen sağlayıcılar. AVV imzaladığın 3. taraflar.</span>
                </div>

                <div class="rf-row">
                    <label>Saklama Süresi (gün)</label>
                    <input type="number" name="retention_days" min="0" max="36500"
                           value="{{ old('retention_days', $activity?->retention_days) }}"
                           placeholder="örn. 730 (2 yıl)">
                    <span class="hint">Kayıt bu süreden sonra silinmeli. HGB için 6/10 yıl = 2190/3650 gün.</span>
                </div>

                <div class="rf-row">
                    <label>3. Ülke Aktarımı?</label>
                    <select name="third_country_transfer" id="thirdCountryToggle">
                        <option value="0" @selected(!old('third_country_transfer', $activity?->third_country_transfer))>Hayır (sadece AB)</option>
                        <option value="1" @selected(old('third_country_transfer', $activity?->third_country_transfer))>Evet</option>
                    </select>
                </div>

                <div class="rf-row full" id="thirdCountryRow" style="display:{{ old('third_country_transfer', $activity?->third_country_transfer) ? 'flex' : 'none' }};">
                    <label>3. Ülke (Hangi ülke?)</label>
                    <input type="text" name="third_country_country" maxlength="100"
                           value="{{ old('third_country_country', $activity?->third_country_country) }}"
                           placeholder="örn. ABD (Privacy Shield/SCC), Hindistan, vb.">
                    <span class="hint">EU dışına aktarım varsa hangi ülke + uygunluk dayanağı (SCC, BCR, vb.)</span>
                </div>

                <div class="rf-row full">
                    <label>Güvenlik Önlemleri (Bu Aktivite İçin)</label>
                    <textarea name="security_measures" rows="3" maxlength="5000"
                              placeholder="TLS 1.3 zorunlu&#10;Brevo Pro plan + DPA imzalı&#10;Onay opt-out linki her mailde"
                    >{{ old('security_measures', $activity?->security_measures) }}</textarea>
                </div>

                <div class="rf-row full">
                    <label>Notlar</label>
                    <textarea name="notes" rows="2" maxlength="5000"
                              placeholder="Ek bilgi, son inceleme notu, vb.">{{ old('notes', $activity?->notes) }}</textarea>
                </div>

                <div class="rf-row full">
                    <div class="rf-toggle">
                        <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $activity?->is_active ?? true))>
                        <label for="is_active">Aktivite aktif (devam ediyor)</label>
                    </div>
                </div>
            </div>

            <div class="rf-actions">
                <a href="{{ route('manager.ropa.index') }}" class="rf-btn cancel">İptal</a>
                <button type="submit" class="rf-btn save">{{ $activity ? '💾 Kaydet' : '➕ Ekle' }}</button>
            </div>
        </form>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var thirdCountrySelect = document.getElementById('thirdCountryToggle');
    var thirdCountryRow = document.getElementById('thirdCountryRow');
    if (thirdCountrySelect && thirdCountryRow) {
        thirdCountrySelect.addEventListener('change', function(){
            thirdCountryRow.style.display = (this.value === '1') ? 'flex' : 'none';
        });
    }

    // Textarea → array (form submit öncesi data_categories[], recipients[] vs. yarat)
    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(){
            ['data_categories', 'subject_categories', 'recipients'].forEach(function(field){
                var ta = document.querySelector('[data-tags-target="' + field + '"]');
                if (!ta) return;
                ta.value.split(/\r?\n/).map(function(l){ return l.trim(); }).filter(Boolean).forEach(function(val, i){
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = field + '[' + i + ']';
                    hidden.value = val;
                    form.appendChild(hidden);
                });
            });
        });
    }
})();
</script>
@endsection

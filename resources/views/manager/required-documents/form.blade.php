@extends('manager.layouts.app')

@section('title', $doc ? 'Belge Düzenle' : 'Yeni Belge Ekle')
@section('page_title', $doc ? 'Belge Düzenle' : 'Yeni Belge Ekle')

@push('head')
<style>
.rdf-wrap { max-width:880px; margin:0 auto; padding:0 0 32px; }
.rdf-card {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    padding:24px 28px; box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.rdf-back { font-size:12.5px; color:#64748b; text-decoration:none; display:inline-flex; align-items:center; gap:5px; margin-bottom:14px; }
.rdf-back:hover { color:#1e40af; text-decoration:none; }
.rdf-title { font-size:18px; font-weight:800; margin:0 0 6px; color:#0f172a; }
.rdf-subtitle { font-size:12.5px; color:#64748b; margin:0 0 20px; line-height:1.5; }

.rdf-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.rdf-row { display:flex; flex-direction:column; gap:6px; }
.rdf-row.full { grid-column:1/-1; }
.rdf-row label {
    font-size:11.5px; font-weight:700; color:#475569;
    text-transform:uppercase; letter-spacing:.5px;
}
.rdf-row label .req { color:#dc2626; margin-left:3px; }
.rdf-row input[type="text"],
.rdf-row input[type="number"],
.rdf-row select,
.rdf-row textarea {
    padding:9px 11px; border-radius:8px; border:1px solid #cbd5e1;
    font-size:13px; font-family:inherit; background:#fff;
    transition:border-color .15s, box-shadow .15s;
}
.rdf-row input:focus, .rdf-row select:focus, .rdf-row textarea:focus {
    outline:none; border-color:#3b5fcc; box-shadow:0 0 0 3px rgba(59,95,204,.12);
}
.rdf-row textarea { min-height:70px; resize:vertical; }
.rdf-row .hint { font-size:11px; color:#94a3b8; line-height:1.4; }
.rdf-row .err { font-size:11px; color:#dc2626; line-height:1.4; }

.rdf-tags {
    display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-top:6px;
}
.rdf-tag {
    border:1.5px solid #e2e8f0; border-radius:8px; padding:9px 12px;
    cursor:pointer; transition:all .15s; display:flex; align-items:center; gap:8px;
    font-size:13px; font-weight:600; color:#475569; background:#fff;
}
.rdf-tag:hover { border-color:#a5b4fc; background:#eef2ff; }
.rdf-tag input { margin:0; cursor:pointer; }
.rdf-tag.checked { border-color:#3b5fcc; background:#eef2ff; color:#1e40af; box-shadow:0 1px 3px rgba(59,95,204,.15); }

.rdf-toggle {
    display:flex; align-items:center; gap:10px; padding:10px 12px;
    background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;
}
.rdf-toggle input { width:18px; height:18px; cursor:pointer; }
.rdf-toggle label { margin:0; text-transform:none; letter-spacing:0; font-size:13px; cursor:pointer; }

.rdf-actions { margin-top:24px; display:flex; gap:10px; justify-content:flex-end; }
.rdf-btn {
    padding:10px 22px; border-radius:8px; border:none; cursor:pointer;
    font-size:13px; font-weight:700; text-decoration:none;
    display:inline-flex; align-items:center; gap:6px;
}
.rdf-btn.cancel { background:#f1f5f9; color:#475569; }
.rdf-btn.cancel:hover { background:#e2e8f0; color:#0f172a; text-decoration:none; }
.rdf-btn.save { background:linear-gradient(135deg,#1e40af,#3b5fcc); color:#fff; }
.rdf-btn.save:hover { transform:translateY(-1px); box-shadow:0 4px 14px rgba(30,64,175,.25); }

.rdf-info {
    background:#eff6ff; border-left:3px solid #3b82f6;
    padding:10px 14px; border-radius:6px; margin-bottom:18px;
    font-size:12px; color:#1e40af; line-height:1.5;
}

@media (max-width:740px) {
    .rdf-grid { grid-template-columns:1fr; }
    .rdf-tags { grid-template-columns:1fr 1fr; }
    .rdf-card { padding:18px; }
}
</style>
@endpush

@section('content')
<div class="rdf-wrap">

    <a href="{{ route('manager.required-documents.index', ['application_type' => $currentAppType, 'stage' => $currentStage]) }}" class="rdf-back">← Belge Listesine Dön</a>

    <div class="rdf-card">
        <h2 class="rdf-title">{{ $doc ? '✏️ Belge Düzenle' : '➕ Yeni Belge Ekle' }}</h2>
        <p class="rdf-subtitle">
            {{ $doc ? 'Belgenin bilgilerini güncelle. Birden fazla kategori seçersen aynı dosya yüklendiğinde tüm sekmelerde otomatik ✓ olur.' : 'Yeni bir zorunlu belge tanımla. Birden fazla kategori seçebilirsin (multi-tag) — örn. Pasaport: Uni Asist + Vize + İkamet.' }}
        </p>

        <div class="rdf-info">
            💡 <strong>Çoklu kategori tag'i:</strong> Aynı belge birden fazla başvuru sürecinde gerekebilir. Tag'leri seçtiğinde
            öğrenci dosyayı bir kez yükler, seçtiğin tüm sekmelerde aynı anda ✓ olarak görünür.
        </div>

        @if($errors->any())
            <div class="rdf-info" style="background:#fef2f2;border-left-color:#dc2626;color:#991b1b;">
                <strong>Doğrulama hatası:</strong>
                <ul style="margin:6px 0 0;padding-left:18px;">
                    @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ $doc ? route('manager.required-documents.update', $doc) : route('manager.required-documents.store') }}">
            @csrf
            @if($doc)
                @method('PUT')
            @endif

            <div class="rdf-grid">

                <div class="rdf-row">
                    <label>Başvuru Tipi <span class="req">*</span></label>
                    <select name="application_type" required>
                        @foreach($applicationTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('application_type', $doc?->application_type ?? $currentAppType) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rdf-row">
                    <label>Aşama <span class="req">*</span></label>
                    <select name="stage" required>
                        @foreach($stages as $key => $label)
                            <option value="{{ $key }}" @selected(old('stage', $doc?->stage ?? $currentStage) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rdf-row full">
                    <label>Belge Adı (Türkçe) <span class="req">*</span></label>
                    <input type="text" name="name" required maxlength="190"
                           value="{{ old('name', $doc?->name ?? '') }}"
                           placeholder="Örn: Pasaport, Üniversite Transkripti, Sağlık Sigortası...">
                    <span class="hint">Öğrenci portalında bu isim görünecek.</span>
                </div>

                <div class="rdf-row full">
                    <label>Almanca Karşılığı (popup'ta gösterilir)</label>
                    <input type="text" name="name_de" maxlength="190"
                           value="{{ old('name_de', $doc?->name_de ?? '') }}"
                           placeholder="Örn: Reisepass, Transkript, Krankenversicherung">
                    <span class="hint">Öğrenci "i" butonuna tıkladığında popup'ta Almanca isim olarak gösterilir.</span>
                </div>

                <div class="rdf-row">
                    <label>Belge Kodu (DOC-XXXX) <span class="req">*</span></label>
                    <input type="text" name="document_code" required maxlength="64"
                           value="{{ old('document_code', $doc?->document_code ?? '') }}"
                           pattern="[A-Za-z0-9_-]+"
                           placeholder="Örn: DOC-PASS, DOC-CV__">
                    <span class="hint">Sistem içi benzersiz kod. Aynı kodla birden fazla kategori = multi-tag.</span>
                </div>

                <div class="rdf-row">
                    <label>Uni-Assist Kategorisi (varsa)</label>
                    <input type="text" name="uni_assist_category" maxlength="80"
                           value="{{ old('uni_assist_category', $doc?->uni_assist_category ?? '') }}"
                           placeholder="Örn: Schulzeugnis, Studienzeugnis, Lebenslauf, Passkopie">
                    <span class="hint">Uni-Assist'e yüklenirken hangi kategoriye girer? Sadece uni-assist sekmesindeki belgeler için.</span>
                </div>

                <div class="rdf-row full">
                    <label>Açıklama (popup'ta gösterilir)</label>
                    <textarea name="description" maxlength="500" placeholder="Belgenin nasıl hazırlanacağı, dikkat edilecek noktalar...">{{ old('description', $doc?->description ?? '') }}</textarea>
                    <span class="hint">Öğrenci "i" butonuna tıkladığında bu açıklama gösterilir.</span>
                </div>

                <div class="rdf-row full">
                    <label>Hangi Kategorilerde Görünsün? <span class="req">*</span></label>
                    <div class="rdf-tags">
                        @foreach($topCategories as $code => $label)
                            <label class="rdf-tag {{ in_array($code, old('top_category_codes', $preselectedTags), true) ? 'checked' : '' }}">
                                <input type="checkbox" name="top_category_codes[]" value="{{ $code }}"
                                       @checked(in_array($code, old('top_category_codes', $preselectedTags), true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <span class="hint">Bir veya birden fazla kategori seç. Multi-tag: tek dosya, birden fazla sekmede ✓.</span>
                </div>

                <div class="rdf-row">
                    <label>Sıra Numarası</label>
                    <input type="number" name="sort_order" min="0" max="9999"
                           value="{{ old('sort_order', $doc?->sort_order ?? 100) }}">
                    <span class="hint">Düşük sayı önce gösterilir. 10, 20, 30 gibi 10'arlı git.</span>
                </div>

                <div class="rdf-row">
                    <label>Maks. Dosya Boyutu (MB)</label>
                    <input type="number" name="max_mb" min="1" max="50"
                           value="{{ old('max_mb', $doc?->max_mb ?? 10) }}">
                    <span class="hint">Genelde 10. Hafif belgeler için 5.</span>
                </div>

                <div class="rdf-row full">
                    <label>Kabul Edilen Formatlar</label>
                    <input type="text" name="accepted" maxlength="120"
                           value="{{ old('accepted', $doc?->accepted ?? 'pdf,jpg,png') }}"
                           placeholder="pdf,jpg,png">
                    <span class="hint">Virgülle ayrılmış uzantı listesi. Varsayılan: pdf,jpg,png</span>
                </div>

                <div class="rdf-row full">
                    <div class="rdf-toggle">
                        <input type="checkbox" id="is_required" name="is_required" value="1" @checked(old('is_required', $doc?->is_required ?? true))>
                        <label for="is_required">Zorunlu belge — yüklenmediğinde uyarı gösterilsin ve sonraki adıma geçişi engellesin</label>
                    </div>
                </div>

            </div>

            <div class="rdf-actions">
                <a href="{{ route('manager.required-documents.index', ['application_type' => $currentAppType, 'stage' => $currentStage]) }}" class="rdf-btn cancel">İptal</a>
                <button type="submit" class="rdf-btn save">{{ $doc ? '💾 Değişiklikleri Kaydet' : '➕ Belgeyi Ekle' }}</button>
            </div>
        </form>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    document.querySelectorAll('.rdf-tag input[type="checkbox"]').forEach(function(inp){
        inp.addEventListener('change', function(){
            this.closest('.rdf-tag').classList.toggle('checked', this.checked);
        });
    });
})();
</script>
@endsection

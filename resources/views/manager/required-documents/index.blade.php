@extends('manager.layouts.app')

@section('title', 'Zorunlu Belge Listesi')
@section('page_title', 'Zorunlu Belge Listesi')

@push('head')
<style>
.rdl-wrap { max-width:1200px; margin:0 auto; padding:0 0 32px; }
.rdl-head {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    padding:20px 24px; margin-bottom:18px; box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.rdl-head h2 { font-size:18px; font-weight:800; margin:0 0 4px; color:#0f172a; }
.rdl-head p  { font-size:13px; color:#64748b; margin:0; line-height:1.5; }
.rdl-toolbar { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; margin-top:14px; }
.rdl-toolbar label { display:flex; flex-direction:column; gap:4px; font-size:11px; font-weight:600; color:#475569; text-transform:uppercase; letter-spacing:.5px; }
.rdl-toolbar select {
    padding:7px 10px; border-radius:8px; border:1px solid #cbd5e1;
    font-size:13px; background:#fff; min-width:180px;
}
.rdl-add-btn {
    margin-left:auto; padding:9px 18px; border-radius:8px; border:none;
    background:linear-gradient(135deg,#1e40af,#3b5fcc); color:#fff; cursor:pointer;
    font-size:13px; font-weight:700; text-decoration:none;
    display:inline-flex; align-items:center; gap:6px;
}
.rdl-add-btn:hover { transform:translateY(-1px); box-shadow:0 4px 14px rgba(30,64,175,.25); color:#fff; }

.rdl-flash {
    background:#dcfce7; border:1px solid #86efac; color:#166534;
    padding:10px 16px; border-radius:8px; margin-bottom:14px; font-size:13px;
    display:flex; align-items:center; gap:8px;
}

.rdl-cat {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    margin-bottom:14px; box-shadow:0 1px 3px rgba(0,0,0,.04); overflow:hidden;
}
.rdl-cat-head {
    padding:12px 18px; background:linear-gradient(90deg,#f8fafc 0%,#fff 100%);
    border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:10px;
}
.rdl-cat-head h3 { font-size:14px; font-weight:700; margin:0; color:#1e293b; }
.rdl-cat-count { background:#dbeafe; color:#1e40af; font-size:11px; padding:2px 8px; border-radius:99px; font-weight:700; }

.rdl-row {
    display:grid; grid-template-columns:36px 1fr 100px 120px 120px;
    gap:12px; align-items:center; padding:10px 18px;
    border-bottom:1px solid #f1f5f9; font-size:13px;
}
.rdl-row:last-child { border-bottom:none; }
.rdl-row:hover { background:#f8fafc; }
.rdl-order { font-size:11px; font-weight:700; color:#94a3b8; text-align:center; }
.rdl-name { display:flex; flex-direction:column; gap:2px; min-width:0; }
.rdl-name .nm-tr { font-weight:600; color:#0f172a; }
.rdl-name .nm-de { font-size:11px; color:#64748b; }
.rdl-name .nm-meta { display:flex; gap:6px; flex-wrap:wrap; margin-top:3px; }
.rdl-name .nm-tag {
    font-size:10px; font-weight:600; padding:1px 7px; border-radius:99px;
    background:#ede9fe; color:#6d28d9;
}
.rdl-name .nm-tag.muti { background:#fef3c7; color:#92400e; }
.rdl-code { font-family:ui-monospace,monospace; font-size:11px; color:#64748b; }
.rdl-req { text-align:center; }
.rdl-req .req-yes { background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:99px; font-size:10px; font-weight:700; }
.rdl-req .req-no  { background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:99px; font-size:10px; font-weight:600; }
.rdl-actions { display:flex; gap:6px; justify-content:flex-end; }
.rdl-btn {
    padding:5px 11px; border-radius:6px; border:1px solid #cbd5e1; background:#fff;
    color:#475569; cursor:pointer; font-size:11.5px; font-weight:600;
    text-decoration:none; display:inline-flex; align-items:center; gap:4px;
}
.rdl-btn:hover { background:#f1f5f9; color:#0f172a; text-decoration:none; }
.rdl-btn.danger { color:#dc2626; border-color:#fecaca; }
.rdl-btn.danger:hover { background:#fef2f2; color:#7f1d1d; }

.rdl-empty {
    background:#fff; border:1px dashed #cbd5e1; border-radius:14px;
    padding:40px 24px; text-align:center; color:#64748b;
}
.rdl-empty h3 { font-size:15px; font-weight:700; margin:0 0 6px; color:#475569; }
.rdl-empty p { font-size:12.5px; margin:0; }

@media (max-width:740px) {
    .rdl-row { grid-template-columns:1fr; gap:6px; padding:12px 14px; }
    .rdl-order, .rdl-code, .rdl-req { display:none; }
    .rdl-actions { justify-content:flex-start; }
}
</style>
@endpush

@section('content')
<div class="rdl-wrap">

    <div class="rdl-head">
        <h2>📦 Zorunlu Belge Listesi</h2>
        <p>Öğrenciden istenen belgelerin tanımlandığı katalog. Bir belge birden fazla kategoride etiketlenebilir (örn. Pasaport: Uni Asist + Vize + İkamet).
        Yeni belge eklediğinde öğrenci portalında otomatik görünür.</p>

        <form method="get" class="rdl-toolbar">
            <label>
                Başvuru Tipi
                <select name="application_type" onchange="this.form.submit()">
                    @foreach($applicationTypes as $key => $label)
                        <option value="{{ $key }}" @selected($currentAppType === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Aşama
                <select name="stage" onchange="this.form.submit()">
                    @foreach($stages as $key => $label)
                        <option value="{{ $key }}" @selected($currentStage === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <a href="{{ route('manager.required-documents.create', ['application_type' => $currentAppType, 'stage' => $currentStage]) }}"
               class="rdl-add-btn">+ Yeni Belge Ekle</a>
        </form>
    </div>

    @if(session('flash_success'))
        <div class="rdl-flash">✅ {{ session('flash_success') }}</div>
    @endif

    @php
        $rowsByTag = $rows->groupBy('top_category_code');
    @endphp

    @forelse($topCategories as $tagCode => $tagLabel)
        @php
            $items = $rowsByTag->get($tagCode, collect());
        @endphp
        @if($items->count() > 0)
            <div class="rdl-cat">
                <div class="rdl-cat-head">
                    <h3>{{ $tagLabel }}</h3>
                    <span class="rdl-cat-count">{{ $items->count() }} belge</span>
                </div>
                @foreach($items as $r)
                    @php
                        $tagCount = $multiTagCount[$r->document_code] ?? 1;
                    @endphp
                    <div class="rdl-row">
                        <div class="rdl-order">{{ $r->sort_order }}</div>
                        <div class="rdl-name">
                            <span class="nm-tr">{{ $r->name }}</span>
                            @if($r->name_de)
                                <span class="nm-de">🇩🇪 {{ $r->name_de }}</span>
                            @endif
                            <div class="nm-meta">
                                @if($r->uni_assist_category)
                                    <span class="nm-tag">{{ $r->uni_assist_category }}</span>
                                @endif
                                @if($tagCount > 1)
                                    <span class="nm-tag muti">⚙ {{ $tagCount }} kategoride</span>
                                @endif
                            </div>
                        </div>
                        <div class="rdl-code">{{ $r->document_code }}</div>
                        <div class="rdl-req">
                            @if($r->is_required)
                                <span class="req-yes">Zorunlu</span>
                            @else
                                <span class="req-no">Opsiyonel</span>
                            @endif
                        </div>
                        <div class="rdl-actions">
                            <a href="{{ route('manager.required-documents.edit', $r) }}" class="rdl-btn">✏️ Düzenle</a>
                            <form method="post" action="{{ route('manager.required-documents.destroy', $r) }}"
                                  style="display:inline;margin:0;"
                                  onsubmit="return confirm('Bu belgeyi listeden kaldırmak istediğinizden emin misiniz?\n({{ $tagCount }} kategorideki tüm tag\'leri silinecek)');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rdl-btn danger">🗑️ Sil</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @empty
    @endforelse

    @if($rows->count() === 0)
        <div class="rdl-empty">
            <h3>Henüz belge tanımlanmamış</h3>
            <p>Bu kombinasyon için henüz zorunlu belge eklenmedi. Üstte "Yeni Belge Ekle" butonunu kullanabilirsin.</p>
        </div>
    @endif

</div>
@endsection

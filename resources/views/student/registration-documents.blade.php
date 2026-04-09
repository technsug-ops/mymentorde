@extends('student.layouts.app')

@section('title', 'Student - Belgeler')
@section('page_title', 'Kayıt Belgeleri')

@push('head')
<style>
    .docs-page { --docs-font:13px; --docs-gap:8px; font-size:var(--docs-font); }
    .docs-page * { box-sizing: border-box; }
    .docs-page section.panel { min-height:auto !important; }
    .docs-page .panel {
        padding:10px 12px !important; border-radius:12px !important;
        background:var(--u-card) !important; border:1px solid var(--u-line) !important;
        margin-bottom:10px;
    }
    .docs-page .btn { min-height:32px !important; padding:6px 10px !important; font-size:12px !important; border-radius:10px !important; }
    .docs-page .doc-header-panel { padding:8px 10px !important; min-height:auto !important; margin-bottom:8px; }
    .docs-page .doc-header-panel::before,
    .docs-page .doc-header-panel::after { display:none !important; content:none !important; }
    .docs-page .doc-header-row { display:flex; justify-content:space-between; gap:8px; align-items:flex-start; flex-wrap:wrap; margin:0 !important; }
    .docs-page .doc-header-row > * { min-height:auto !important; margin:0 !important; }
    .docs-page .doc-header-main { flex:1 1 320px; min-width:260px; }
    .doc-header-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .docs-page .doc-header-actions .btn { margin:0 !important; }
    .docs-page .doc-note { margin-top:2px; font-size:11px; line-height:1.25; }
    .docs-page .doc-subtle { font-size:12px; color:#5a6f8f; line-height:1.2; }
    .docs-page .docs-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin-bottom:8px; }
    .docs-page .metric .v { font-size:20px; font-weight:800; color:var(--u-brand); }
    .docs-page .metric.panel {
        padding:14px 18px !important; min-height:auto !important;
        background:var(--u-card) !important; border:1px solid var(--u-line) !important;
        border-radius:14px !important; box-shadow:var(--u-shadow) !important;
    }
    .docs-page .metric .muted { font-size:11px !important; line-height:1.1; margin-bottom:2px; }
    .docs-page .group { border:1px solid var(--u-line); border-radius:12px; margin-bottom:10px; background:var(--u-card); overflow:hidden; }
    .docs-page .group-h { display:flex; justify-content:space-between; align-items:center; gap:8px; padding:8px 10px; border-bottom:none; background:var(--u-bg); }
    .docs-page .group-h strong { font-size:16px; color:var(--u-text); }
    .docs-page .group-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:10px; }
    .docs-page .empty-group-note { margin:0 10px 10px; padding:8px 10px; border:1px dashed #cfdcf0; border-radius:10px; background:#f8fbff; color:#4e678a; font-size:12px; }
    .docs-page .doc { border:1px solid var(--u-line); border-radius:10px; padding:10px; background:var(--u-card); }
    .docs-page .doc.missing { border-color:#f0d4d4; background:#fffefe; }
    .docs-page .dchip { display:inline-flex; align-items:center; justify-content:center; gap:4px; padding:2px 7px; min-height:22px; line-height:1; border-radius:999px; border:1px solid #d6dfeb; font-size:10px; font-weight:600; white-space:nowrap; background:#fff; }
    .docs-page .dchip.ok { border-color:#bfe2ca; color:#1e6a40; background:#eefcf2; }
    .docs-page .dchip.wait { border-color:#f1d5a8; color:#8a5a15; background:#fff8ee; }
    .docs-page .dchip.danger { border-color:#efc8c8; color:#b4232b; background:#fff8f8; }
    .docs-page .code { display:inline-block; font-size:11px; font-weight:700; color:var(--u-brand); background:var(--u-bg); border:1px solid var(--u-line); border-radius:999px; padding:2px 7px; }
    .docs-page .drop { margin-top:8px; border:1px dashed var(--u-line); border-radius:8px; padding:8px; background:var(--u-bg); display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .docs-page .drop input[type="file"] { max-width:280px; }
    .docs-page .drop label.btn {
        display:inline-flex; align-items:center; padding:5px 12px; min-height:30px;
        background:var(--u-card); border:1px solid var(--u-line) !important;
        border-radius:8px; color:var(--u-text) !important; font-size:12px;
        font-weight:600; cursor:pointer; transition:all .15s;
    }
    .docs-page .drop label.btn:hover { border-color:var(--u-brand) !important; color:var(--u-brand) !important; }
    .docs-page .drop button.btn.primary {
        background:var(--u-brand) !important; color:#fff !important;
        border-color:transparent !important; border-radius:8px;
        padding:5px 12px; min-height:30px; font-size:12px; font-weight:600;
    }
    .docs-page .drop button.btn.primary:hover { opacity:.88; }
    .docs-page .list { max-height:260px; overflow:auto; border:1px solid #d6dfeb; border-radius:10px; }
    .docs-page .uploaded-row { padding:8px; border-bottom:none; }
    .docs-page .uploaded-row:last-child { border-bottom:none; }
    .docs-page .bar { height:8px; border-radius:999px; background:var(--u-line); overflow:hidden; }
    .docs-page .bar > span { display:block; height:100%; background:var(--u-brand); }
    .docs-page .tools { display:flex; gap:6px; flex-wrap:wrap; margin:0; align-items:center; }
    .docs-page .tools button { border:1px solid var(--u-line); background:var(--u-card); color:var(--u-text); border-radius:999px; padding:4px 9px; min-height:28px; font-size:11px; cursor:pointer; line-height:1; }
    .docs-page .docs-legend { display:flex; gap:6px; flex-wrap:wrap; margin:0; align-items:center; justify-content:flex-end; }
    .docs-page .docs-legend .dchip {
        display:inline-flex !important; width:auto !important; height:auto !important; min-width:0 !important;
        min-height:22px !important; padding:2px 8px !important; font-size:10px !important; line-height:1 !important;
        border-radius:999px !important; white-space:nowrap !important; margin:0 !important;
    }
    .docs-page .progress-line { display:grid; grid-template-columns:auto 1fr; gap:10px; align-items:center; }
    .docs-page .progress-line .muted { margin:0 !important; font-size:12px; }
    .docs-page .hidden { display:none !important; }
    .docs-page .filters-panel { padding:8px 10px !important; min-height:auto !important; }
    .docs-page .filters-top { display:flex; align-items:center; gap:10px; margin:0 0 8px 0; min-height:auto !important; flex-wrap:wrap; }
    .docs-page .filters-top .progress-line { flex:1 1 520px; min-width:220px; }
    .docs-page .filters-inline { display:grid; gap:8px; min-height:auto !important; }
    .docs-page .filter-row { display:grid; grid-template-columns:auto minmax(0,1fr); gap:10px; align-items:center; }
    .docs-page .filter-row .tools-wrap { min-width:0; overflow:auto hidden; padding-bottom:2px; }
    .docs-page .filter-row .tools { min-width:max-content; flex-wrap:nowrap; }
    .docs-page .filters-inline .tools, .docs-page .filters-inline .docs-legend { margin-top:0; }
    .docs-page .doc-cat-nav { position:sticky; top:8px; z-index:25; display:flex; gap:10px; align-items:center; overflow:hidden; padding:8px 10px 10px; border:1px solid var(--u-line); border-radius:12px; background:var(--u-card); box-shadow:0 2px 8px rgba(0,0,0,.06); margin-bottom:10px; }
    .docs-page .doc-cat-nav .row-label { font-size:11px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
    .docs-page .doc-cat-nav .category-tabs-wrap { flex:1; min-width:0; overflow:auto hidden; padding-bottom:2px; }
    .docs-page .category-tabs { display:flex; gap:6px; flex-wrap:nowrap; margin:0; min-width:max-content; }
    .docs-page .category-tabs button { border:1px solid var(--u-line); background:var(--u-card); color:var(--u-text); border-radius:999px; padding:5px 12px; min-height:28px; font-size:12px; cursor:pointer; line-height:1; }
    .docs-page .category-tabs button:hover { background:var(--u-bg); border-color:var(--u-brand); color:var(--u-brand); }
    .docs-page .category-tabs button.active { background:var(--u-brand); border-color:var(--u-brand); color:#fff; font-weight:700; }
    .docs-page .docs-legend.compact { justify-content:flex-end; }
    .docs-page .filters-inline .tools button.active-filter { background:var(--u-bg); border-color:var(--u-brand); color:var(--u-brand); font-weight:700; }
    .docs-page .group-summary { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .docs-page .filters-panel > * { min-height:auto !important; }
    .docs-page .filters-panel .panel,
    .docs-page .filters-panel .row { min-height:auto !important; }
    .docs-page .list .btn { min-height:28px !important; padding:4px 8px !important; font-size:11px !important; }
    .docs-page .row strong { font-size:13px; line-height:1.25; }
    .docs-page .group-h strong { font-size:14px; }
    .docs-page .group-h { padding:7px 9px; }
    .docs-page .doc h3 { font-size:14px !important; line-height:1.25; }
    .docs-page .muted { font-size:12px !important; }
    .docs-page .doc-header-row form { margin:0; }
    @media (max-width:1024px) { .docs-page .docs-kpis { grid-template-columns:1fr 1fr; } }
    @media (max-width:980px) { .docs-page .docs-legend{justify-content:flex-start;} }
    @media (max-width:900px) {
        .docs-page .doc-cat-nav { flex-direction:column; align-items:flex-start; gap:6px; }
        .docs-page .doc-cat-nav .category-tabs-wrap { width:100%; }
        .docs-page .filter-row { grid-template-columns:1fr; gap:6px; }
        .docs-page .docs-legend.compact { justify-content:flex-start; }
    }
    @media (max-width:740px) { .docs-page .group-grid,.docs-page .docs-kpis { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
    <div class="docs-page">
    <section class="panel doc-header-panel">
        <div class="doc-header-row">
            <div class="doc-header-main">
                <div class="doc-subtle">Her karttan ilgili belgeyi yükleyebilirsin. Zorunlu belgeler kırmızı çerçeve ile gösterilir.</div>
                @if(!empty($builderOnly))
                    <div class="doc-note muted">
                        Aktif filtre: <span class="dchip" style="border-color:#bfd5f2;color:#1d4f8f;">Builder Çıktıları</span>
                    </div>
                @endif
            </div>
            <div class="doc-header-actions">
                @if(!empty($builderOnly))
                    <a class="btn alt" href="/student/registration/documents">Tüm Belgeleri Göster</a>
                @else
                    <a class="btn alt" href="/student/registration/documents?builder_only=1">Builder Çıktıları</a>
                @endif
                <a class="btn alt" href="/student/registration">Forma Dön</a>
            </div>
        </div>
    </section>

    @php
        $check = collect($requiredDocumentChecklist ?? []);
        $all = $check->count();
        $uploaded = $check->where('uploaded', true)->count();
        $requiredTotal = $check->where('is_required', true)->count();
        $requiredUploaded = $check->where('is_required', true)->where('uploaded', true)->count();
        $missingRequired = max(0, $requiredTotal - $requiredUploaded);
        $pct = $all > 0 ? (int) round(($uploaded / $all) * 100) : 0;
        $groupedRaw = $check->groupBy(fn($x) => (string) ($x['top_category_code'] ?? 'diger_dokumanlar'));
        $preferredGroupOrder = [
            'uni_assist_dokumanlari',
            'kisisel_dokumanlar',
            'vize_dokumanlari',
            'dil_okulu_dokumanlari',
            'ikamet_kaydi_dokumanlari',
            'almanya_burokrasi_dokumanlari',
            'partner_dokumanlari',
            'diger_dokumanlar',
        ];
        $grouped = collect();
        foreach ($preferredGroupOrder as $code) {
            if ($groupedRaw->has($code)) {
                $grouped->put($code, $groupedRaw->get($code));
            }
        }
        foreach ($groupedRaw as $code => $items) {
            if (!$grouped->has($code)) {
                $grouped->put($code, $items);
            }
        }
    @endphp

    <section class="docs-kpis">
        <div class="panel metric"><div class="muted">Toplam</div><div class="v">{{ $all }}</div></div>
        <div class="panel metric"><div class="muted">Yüklenen</div><div class="v">{{ $uploaded }}</div></div>
        <div class="panel metric"><div class="muted">Zorunlu</div><div class="v">{{ $requiredUploaded }}/{{ $requiredTotal }}</div></div>
        <div class="panel metric"><div class="muted">Eksik Zorunlu</div><div class="v" style="color:#b4232b;">{{ $missingRequired }}</div></div>
    </section>
    <section class="panel filters-panel">
        <div class="filters-top">
            <div class="progress-line">
                <div class="muted">Genel ilerleme: %{{ $pct }}</div>
                <div class="bar"><span style="width: {{ $pct }}%"></span></div>
            </div>
            <div class="docs-legend compact">
                <span class="dchip ok">Yüklendi</span>
                <span class="dchip wait">Bekliyor</span>
                <span class="dchip danger">Zorunlu</span>
            </div>
        </div>
        <div class="filters-inline">
            <div class="filter-row">
                <div class="row-label">Filtre</div>
                <div class="tools-wrap">
                    <div class="tools">
                        <button type="button" class="active-filter" data-doc-filter="all">Tüm Belgeler</button>
                        <button type="button" data-doc-filter="required">Sadece Zorunlu</button>
                        <button type="button" data-doc-filter="missing">Sadece Eksik</button>
                        <button type="button" data-doc-filter="missing_required">Zorunlu + Eksik</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(empty($builderOnly) && $grouped->count() > 0)
    <section class="doc-cat-nav">
        <div class="row-label">Kategori</div>
        <div class="category-tabs-wrap">
            <div class="category-tabs" id="categoryTabs">
                @foreach($grouped as $top => $items)
                    <button type="button" data-cat-tab="{{ $top }}">
                        {{ $documentTopCategoryLabels[$top] ?? $top }}
                    </button>
                @endforeach
                <button type="button" data-cat-tab="all">Tüm Kategoriler</button>
            </div>
        </div>
    </section>
    @endif

    @if(empty($builderOnly))
    @foreach($grouped as $top => $items)
        @php $label = $documentTopCategoryLabels[$top] ?? $top; @endphp
        @php
            $groupMissingRequired = $items->where('is_required', true)->where('uploaded', false)->count();
            $startCollapsed = false; // Varsayilan acik: öğrenci belge sutunlarini hemen gorsun.
        @endphp
        <section class="group" data-group data-group-code="{{ $top }}" data-start-collapsed="0">
            <header class="group-h">
                <strong>{{ $label }}</strong>
                <div class="group-summary">
                    <span class="dchip">{{ $items->count() }} belge alanı</span>
                    <span class="dchip">{{ $items->where('uploaded', true)->count() }}/{{ $items->count() }} yüklendi</span>
                    @if($groupMissingRequired > 0)
                        <span class="dchip danger">Eksik zorunlu: {{ $groupMissingRequired }}</span>
                    @endif
                    <span class="dchip" style="font-weight:700;">Açık</span>
                </div>
            </header>
            <div class="group-grid">
                @foreach($items as $doc)
                    <article class="doc {{ (!empty($doc['is_required']) && empty($doc['uploaded'])) ? 'missing' : '' }}"
                        data-required="{{ !empty($doc['is_required']) ? '1' : '0' }}"
                        data-uploaded="{{ !empty($doc['uploaded']) ? '1' : '0' }}">
                        <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                            <div>
                                <span class="code">{{ $doc['document_code'] ?: $doc['category_code'] }}</span>
                                <h3 style="margin:6px 0 4px;font-size:var(--tx-base);">{{ $doc['name'] ?: '-' }}</h3>
                                <div class="muted">Kabul: {{ $doc['accepted'] ?? 'pdf,jpg,png' }} | Max {{ (int)($doc['max_mb'] ?? 10) }}MB</div>
                            </div>
                            <div>
                                @if(!empty($doc['uploaded']))
                                    <span class="dchip ok">Yüklendi</span>
                                @else
                                    <span class="dchip wait">Bekliyor</span>
                                @endif
                                @if(!empty($doc['is_required']))
                                    <span class="dchip danger" style="margin-left:4px;">Zorunlu</span>
                                @endif
                            </div>
                        </div>
                        <form method="post" action="{{ route('student.registration.documents.upload') }}" enctype="multipart/form-data" class="drop">
                            @csrf
                            @php $fid = 'f-'.preg_replace('/[^a-z0-9\-]/', '-', strtolower((string) ($doc['category_code'] ?? 'doc'))); @endphp
                            <input type="hidden" name="category_code" value="{{ $doc['category_code'] }}">
                            <label class="btn" for="{{ $fid }}" style="cursor:pointer;">Dosya Seç</label>
                            <input type="file" name="file" id="{{ $fid }}" required style="display:none;"
                                onchange="document.getElementById('{{ $fid }}-name').textContent = this.files[0]?.name || 'Seçilmedi'">
                            <span id="{{ $fid }}-name" class="muted" style="font-size:var(--tx-xs);">Seçilmedi</span>
                            <button class="btn primary" type="submit">{{ !empty($doc['uploaded']) ? 'Güncelle' : 'Yükle' }}</button>
                        </form>
                    </article>
                @endforeach
            </div>
            <div class="empty-group-note hidden">Bu kategoride aktif filtreye uygun belge görünmüyor. "Tüm Belgeler" filtresine dön.</div>
        </section>
    @endforeach
    @endif

    <section class="panel">
        <h3 style="margin:0 0 8px;">Yüklenen Belgelerim</h3>
        <div class="list">
            @forelse($documents as $d)
                @php
                    $tags = collect(is_array($d->process_tags ?? null) ? $d->process_tags : [])->map(fn($x) => strtolower((string)$x));
                    $isBuilder = $tags->contains('student_document_builder') || \Illuminate\Support\Str::startsWith(strtolower((string)($d->document_id ?? '')), 'doc-stb-');
                    $isRejected = (string) $d->status === 'rejected';
                @endphp
                <div class="uploaded-row" @if($isRejected) style="border-left:3px solid #f87171;background:#fff8f8;border-radius:0 8px 8px 0;padding-left:10px;" @endif>
                    <strong>
                        {{ $d->document_id ?: ($d->document_code ?: ($d->category->code ?? '-')) }}
                        - {{ $d->title ?: ($d->category->name_tr ?? '-') }}
                    </strong>
                    <div class="muted">
                        @if($isRejected)
                            <span class="dchip danger">Reddedildi</span>
                        @elseif((string) $d->status === 'approved')
                            <span class="dchip ok">Onaylandı</span>
                        @elseif((string) $d->status === 'generated')
                            <span class="dchip" style="border-color:#bfd5f2;color:#1d4f8f;">Oluşturuldu</span>
                        @else
                            <span class="dchip wait">{{ $d->status }}</span>
                        @endif
                        | {{ $d->updated_at }}
                        @if($isBuilder)
                            | <span class="dchip" style="border-color:#bfd5f2;color:#1d4f8f;">Builder Çıktısı</span>
                        @endif
                    </div>
                    @if($isRejected)
                        <div style="margin-top:5px;font-size:12px;color:#b4232b;line-height:1.5;">
                            ❌ <strong>Red sebebi:</strong> {{ $d->review_note ?: 'İnceleme notu girilmedi.' }}
                        </div>
                        <div style="margin-top:4px;font-size:11px;color:#78350f;background:#fef3c7;border-radius:6px;padding:5px 10px;display:inline-block;">
                            ↑ Yukarıdaki ilgili belgeden yeniden yükleyebilirsiniz.
                        </div>
                    @endif
                    <div style="margin-top:6px; display:flex; gap:8px; flex-wrap:wrap;">
                        <a class="btn alt" href="{{ route('student.registration.documents.download', $d->id) }}">Aç / İndir</a>
                        @php $previewable = in_array(strtolower((string)($d->mime_type??'')), ['application/pdf','image/jpeg','image/png','image/webp','image/gif'], true) || str_starts_with(strtolower((string)($d->mime_type??'')), 'image/'); @endphp
                        @if($previewable)
                        <button class="btn alt" type="button" onclick="previewDoc({{ $d->id }})">👁 Önizle</button>
                        @endif
                        <form method="post" action="{{ route('student.registration.documents.delete', $d->id) }}" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button class="btn warn" type="submit">Sil</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="row muted">Belge yok.</div>
            @endforelse
        </div>
    </section>

    <section class="panel">
        <h3 style="margin:0 0 8px;">Kullanım Kılavuzu</h3>
        <ol class="muted" style="margin:0;padding-left:18px;">
            <li>Zorunlu belgeleri önce tamamla; "Sadece Zorunlu" filtresiyle hızlı ilerle.</li>
            <li>Her kartta doğrudan yükle/güncelle yapabilirsin, ekstra belge için alttaki listeyi kullan.</li>
            <li>Sözleşme adımına geçebilmek için zorunlu belgelerin tümü yüklenmiş olmalıdır.</li>
        </ol>
    </section>

    <script defer src="{{ Vite::asset('resources/js/student-registration-documents.js') }}"></script>
    </div>

{{-- Belge Önizleme Modal --}}
<div id="preview-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;max-width:860px;width:100%;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #e5e7eb;">
            <strong id="preview-filename" style="font-size:var(--tx-sm);color:#111827;"></strong>
            <button onclick="closePreview()" style="background:none;border:none;font-size:var(--tx-xl);cursor:pointer;color:#6b7280;line-height:1;">✕</button>
        </div>
        <div id="preview-container" style="flex:1;overflow:auto;display:flex;align-items:center;justify-content:center;background:#f9fafb;padding:12px;min-height:300px;"></div>
        <div id="preview-review-note" style="display:none;padding:10px 16px;background:#fef3c7;border-top:1px solid #fde68a;font-size:var(--tx-xs);color:#92400e;"></div>
        <div style="padding:10px 16px;border-top:1px solid #e5e7eb;">
            <span id="preview-status" class="badge" style="font-size:var(--tx-xs);"></span>
        </div>
    </div>
</div>

<script>
function openPreviewModal() {
    const m = document.getElementById('preview-modal');
    m.style.display = 'flex';
}
function closePreview() {
    document.getElementById('preview-modal').style.display = 'none';
    document.getElementById('preview-container').innerHTML = '';
    document.getElementById('preview-review-note').style.display = 'none';
}
async function previewDoc(docId) {
    document.getElementById('preview-container').innerHTML = '<div style="color:#9ca3af;font-size:var(--tx-sm);">Yükleniyor...</div>';
    document.getElementById('preview-filename').textContent = '';
    document.getElementById('preview-review-note').style.display = 'none';
    openPreviewModal();

    try {
        const res = await fetch('/student/documents/' + docId + '/preview', {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) { document.getElementById('preview-container').innerHTML = '<div style="color:#dc2626;font-size:var(--tx-sm);">Önizleme yüklenemedi.</div>'; return; }
        const data = await res.json();
        document.getElementById('preview-filename').textContent = data.filename || 'Belge';

        const badgeMap = { approved: 'ok', rejected: 'danger', uploaded: 'info', pending: 'pending' };
        const statusEl = document.getElementById('preview-status');
        statusEl.textContent = data.status || '';
        statusEl.className = 'badge ' + (badgeMap[data.status] || '');

        const container = document.getElementById('preview-container');
        if (data.mime === 'application/pdf') {
            container.innerHTML = '<iframe src="' + data.url + '" style="width:100%;height:600px;border:none;border-radius:8px;"></iframe>';
        } else {
            container.innerHTML = '<img src="' + data.url + '" alt="' + (data.filename || '') + '" style="max-width:100%;max-height:70vh;border-radius:8px;object-fit:contain;">';
        }

        if (data.review_note) {
            const note = document.getElementById('preview-review-note');
            note.textContent = 'İnceleme notu: ' + data.review_note;
            note.style.display = 'block';
        }
    } catch (e) {
        document.getElementById('preview-container').innerHTML = '<div style="color:#dc2626;font-size:var(--tx-sm);">Bir hata oluştu.</div>';
    }
}
document.getElementById('preview-modal').addEventListener('click', function(e) {
    if (e.target === this) closePreview();
});
</script>

@if(session('docs_complete'))
<div id="docsCompleteModal" style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--u-card,#fff);border-radius:20px;max-width:420px;width:100%;padding:32px 28px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.2);animation:dcPop .4s cubic-bezier(.34,1.56,.64,1);">
        <div style="font-size:56px;margin-bottom:12px;">🎉</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-text);margin-bottom:8px;">Tebrikler!</div>
        <div style="font-size:14px;color:var(--u-muted);line-height:1.6;margin-bottom:24px;">
            Tum belgeler basariyla yuklendi.<br>
            Simdi hizmet paketini secebilirsin.
        </div>
        <a href="{{ route('student.services') }}"
           style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;border-radius:12px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;font-size:15px;font-weight:700;text-decoration:none;box-shadow:0 4px 14px rgba(13,148,136,.3);">
            Hizmetlere Git →
        </a>
        <div style="margin-top:14px;">
            <button type="button" onclick="document.getElementById('docsCompleteModal').style.display='none'"
                    style="background:none;border:none;font-size:13px;color:var(--u-muted);cursor:pointer;padding:4px 8px;">
                Sonra bakarim
            </button>
        </div>
    </div>
</div>
<style>@keyframes dcPop{0%{transform:scale(.8);opacity:0}100%{transform:scale(1);opacity:1}}</style>
@endif

@endsection

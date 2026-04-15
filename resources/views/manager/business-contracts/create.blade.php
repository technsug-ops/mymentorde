@extends('manager.layouts.app')

@php $initType = old('contract_type', request('type', '')); @endphp

@section('title', 'Yeni Sözleşme')
@section('page_title', 'Yeni Sözleşme Oluştur')
@section('page_subtitle', 'Adım adım sözleşme oluşturma')

@push('head')
<style>
/* ── Wizard Layout ─────────────────────────────────────── */
.wz-layout  { display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start; }
@media(max-width:1100px){ .wz-layout { grid-template-columns:1fr; } }

/* ── Steps ─────────────────────────────────────────────── */
.wz-steps { display:flex; gap:0; margin-bottom:24px; }
.wz-step  {
    flex:1; display:flex; align-items:center; gap:10px;
    padding:12px 16px; background:var(--surface); border:1px solid var(--border);
    border-right:none; position:relative; cursor:default;
}
.wz-step:first-child { border-radius:10px 0 0 10px; }
.wz-step:last-child  { border-right:1px solid var(--border); border-radius:0 10px 10px 0; }
.wz-step.active { background:rgba(30,64,175,.06); border-color:#1e40af; z-index:1; }
.wz-step.done   { background:rgba(22,163,74,.05); border-color:rgba(22,163,74,.4); }
.wz-step-num {
    width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:800; flex-shrink:0;
    background:var(--border); color:var(--muted);
}
.wz-step.active .wz-step-num { background:#1e40af; color:#fff; }
.wz-step.done   .wz-step-num { background:#16a34a; color:#fff; }
.wz-step-label  { font-size:12px; font-weight:600; color:var(--muted); line-height:1.3; }
.wz-step.active .wz-step-label { color:#1e40af; }
.wz-step.done   .wz-step-label { color:#15803d; }
.wz-step-sub    { font-size:10px; color:var(--muted); font-weight:400; }
@media(max-width:700px){ .wz-step-sub { display:none; } .wz-step-label { font-size:11px; } }

/* ── Section Cards ──────────────────────────────────────── */
.wz-section {
    background:var(--surface); border:1px solid var(--border);
    border-radius:12px; margin-bottom:16px; overflow:hidden;
}
.wz-section-head {
    display:flex; align-items:center; gap:12px;
    padding:14px 20px; border-bottom:1px solid var(--border);
    background:var(--subtle);
}
.wz-section-icon {
    width:32px; height:32px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    font-size:14px; flex-shrink:0;
}
.wz-section-icon.blue   { background:rgba(30,64,175,.1); }
.wz-section-icon.violet { background:rgba(124,58,237,.1); }
.wz-section-icon.cyan   { background:rgba(8,145,178,.1); }
.wz-section-icon.green  { background:rgba(22,163,74,.1); }
.wz-section-icon.amber  { background:rgba(217,119,6,.1); }
.wz-section-num {
    width:20px; height:20px; border-radius:50%; background:#1e40af; color:#fff;
    font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.wz-section-title { font-size:14px; font-weight:700; color:var(--text); }
.wz-section-desc  { font-size:12px; color:var(--muted); margin-top:1px; }
.wz-section-body  { padding:20px; }

/* ── Type Selector ──────────────────────────────────────── */
.type-cards  { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.type-card   {
    display:block; border:2px solid var(--border); border-radius:10px;
    padding:16px; cursor:pointer; transition:all .15s; background:var(--surface);
}
.type-card:hover { border-color:#1e40af; box-shadow:0 0 0 3px rgba(30,64,175,.08); }
.type-card:has(input:checked) {
    border-color:#1e40af; background:rgba(30,64,175,.04);
    box-shadow:0 0 0 3px rgba(30,64,175,.1);
}
.type-card input { position:absolute; opacity:0; pointer-events:none; }
.type-card-icon { font-size:26px; margin-bottom:8px; }
.type-card-title { font-size:14px; font-weight:700; color:var(--text); margin-bottom:3px; }
.type-card-sub   { font-size:11px; color:var(--muted); }

/* ── Party Selector ─────────────────────────────────────── */
.party-select-wrap {
    display:flex; align-items:center; gap:10px;
    border:1.5px solid var(--border); border-radius:10px; padding:10px 14px;
    background:var(--surface); transition:border .15s; cursor:pointer;
}
.party-select-wrap:focus-within { border-color:#1e40af; box-shadow:0 0 0 3px rgba(30,64,175,.08); }
.party-select-wrap select {
    flex:1; border:none; outline:none; background:transparent;
    font-size:13px; color:var(--text); cursor:pointer;
}
.party-select-icon { font-size:18px; flex-shrink:0; }

/* ── Template Cards ─────────────────────────────────────── */
.tpl-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.tpl-card {
    display:block; border:1.5px solid var(--border); border-radius:10px;
    padding:14px 16px; cursor:pointer; transition:all .15s; position:relative;
    background:var(--surface);
}
.tpl-card:hover { border-color:#1e40af; }
.tpl-card:has(input:checked) {
    border-color:#1e40af; background:rgba(30,64,175,.04);
}
.tpl-card:has(input:checked)::after {
    content:'✓'; position:absolute; top:8px; right:10px;
    width:20px; height:20px; background:#1e40af; color:#fff;
    border-radius:50%; font-size:11px; font-weight:800;
    display:flex; align-items:center; justify-content:center;
}
.tpl-card input { position:absolute; opacity:0; pointer-events:none; }
.tpl-card-name  { font-size:13px; font-weight:700; color:var(--text); margin-bottom:4px; padding-right:24px; }
.tpl-card-note  { font-size:11px; color:var(--muted); line-height:1.4; }
.tpl-hint {
    text-align:center; padding:32px; color:var(--muted); font-size:13px;
    border:2px dashed var(--border); border-radius:10px;
}

/* ── Form Fields ─────────────────────────────────────────── */
.f-group     { margin-bottom:16px; }
.f-label     { display:block; font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; }
.f-input     {
    width:100%; height:38px; padding:0 12px;
    border:1.5px solid var(--border); border-radius:8px;
    font-size:13px; color:var(--text); background:var(--surface);
    outline:none; transition:all .15s; box-sizing:border-box;
}
.f-input:focus { border-color:#1e40af; box-shadow:0 0 0 3px rgba(30,64,175,.08); }
textarea.f-input { height:auto; padding:10px 12px; resize:vertical; }
select.f-input   { cursor:pointer; }
.f-input::placeholder { color:var(--muted); opacity:.6; }
.f-row { display:grid; gap:12px; margin-bottom:16px; }
.f-row.col2 { grid-template-columns:1fr 1fr; }
.f-row.col3 { grid-template-columns:1fr 1fr 1fr; }
@media(max-width:600px){ .f-row.col2,.f-row.col3 { grid-template-columns:1fr; } }

/* ── Divider ─────────────────────────────────────────────── */
.f-divider {
    display:flex; align-items:center; gap:10px; margin:20px 0 18px;
    font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.05em;
}
.f-divider::before,.f-divider::after { content:''; flex:1; height:1px; background:var(--border); }

/* ── Sub-block toggle ─────────────────────────────────────── */
.sub-block { display:none; }

/* ── Body Textarea ────────────────────────────────────────── */
.body-textarea {
    width:100%; min-height:340px; padding:14px;
    border:1.5px solid var(--border); border-radius:8px;
    font-family:'Courier New',monospace; font-size:12px; line-height:1.75;
    color:var(--text); background:var(--surface); outline:none; resize:vertical;
    transition:border .15s; box-sizing:border-box;
}
.body-textarea:focus { border-color:#1e40af; box-shadow:0 0 0 3px rgba(30,64,175,.08); }
.body-toolbar {
    display:flex; align-items:center; justify-content:space-between;
    padding:8px 12px; background:var(--subtle); border:1.5px solid var(--border);
    border-bottom:none; border-radius:8px 8px 0 0; font-size:11px; color:var(--muted);
}
.body-textarea-wrap .body-textarea { border-radius:0 0 8px 8px; }

/* ── Sticky Summary Panel ─────────────────────────────────── */
.wz-summary { position:sticky; top:80px; }
.summary-card {
    background:var(--surface); border:1px solid var(--border); border-radius:12px; overflow:hidden;
}
.summary-head {
    padding:14px 18px; background:linear-gradient(135deg,#0f172a,#1e40af);
    color:#fff;
}
.summary-head-title { font-size:13px; font-weight:700; margin-bottom:2px; }
.summary-head-sub   { font-size:11px; opacity:.7; }
.summary-body { padding:16px 18px; }
.summary-row {
    display:flex; justify-content:space-between; align-items:flex-start;
    padding:8px 0; border-bottom:1px solid var(--border); font-size:12px; gap:8px;
}
.summary-row:last-child { border-bottom:none; }
.summary-row-key   { color:var(--muted); flex-shrink:0; }
.summary-row-val   { font-weight:600; color:var(--text); text-align:right; word-break:break-word; }
.summary-empty     { color:var(--muted); font-size:12px; font-style:italic; }
.summary-tip {
    margin:12px 18px; padding:10px 12px;
    background:rgba(30,64,175,.06); border:1px solid rgba(30,64,175,.15);
    border-radius:8px; font-size:11px; color:#1e40af; line-height:1.5;
}
.action-panel {
    background:var(--surface); border:1px solid var(--border); border-radius:12px;
    padding:18px; margin-top:14px;
}
.btn-create {
    width:100%; padding:12px; background:linear-gradient(135deg,#1e40af,#2563eb);
    color:#fff; border:none; border-radius:10px; font-size:14px; font-weight:700;
    cursor:pointer; transition:all .15s; display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-create:hover { opacity:.92; transform:translateY(-1px); box-shadow:0 4px 16px rgba(30,64,175,.3); }
.btn-create:active{ transform:translateY(0); }
.btn-cancel {
    width:100%; padding:9px; background:transparent; color:var(--muted);
    border:1.5px solid var(--border); border-radius:10px; font-size:13px; font-weight:600;
    cursor:pointer; margin-top:8px; text-decoration:none; display:block; text-align:center;
    transition:all .15s;
}
.btn-cancel:hover { border-color:#1e40af; color:#1e40af; }
</style>
@endpush

@section('topbar-actions')
    <a href="{{ route('manager.business-contracts.index', ['type' => $initType ?: null]) }}" class="btn alt">← Geri</a>
@endsection

@section('content')

{{-- Hata --}}
@if($errors->any())
<div style="background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.25);border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:var(--tx-sm);color:#b91c1c;">
    @foreach($errors->all() as $e)<div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">• {{ $e }}</div>@endforeach
</div>
@endif

{{-- Steps Bar --}}
<div class="wz-steps">
    <div class="wz-step active" id="step1">
        <div class="wz-step-num">1</div>
        <div><div class="wz-step-label">Tip & Taraf</div><div class="wz-step-sub">Sözleşme türü seçin</div></div>
    </div>
    <div class="wz-step" id="step2">
        <div class="wz-step-num">2</div>
        <div><div class="wz-step-label">Şablon</div><div class="wz-step-sub">Şablon seçin</div></div>
    </div>
    <div class="wz-step" id="step3">
        <div class="wz-step-num">3</div>
        <div><div class="wz-step-label">Değerler</div><div class="wz-step-sub">Alanları doldurun</div></div>
    </div>
    <div class="wz-step" id="step4">
        <div class="wz-step-num">4</div>
        <div><div class="wz-step-label">Metin</div><div class="wz-step-sub">Düzenleyin & kaydedin</div></div>
    </div>
</div>

<form method="POST" action="{{ route('manager.business-contracts.store') }}" id="bcForm">
@csrf

<div class="wz-layout">

{{-- ── SOL: Form ── --}}
<div>

{{-- BÖLÜM 1: Tip & Taraf --}}
<div class="wz-section">
    <div class="wz-section-head">
        <div class="wz-section-icon blue">🎯</div>
        <div class="wz-section-num">1</div>
        <div>
            <div class="wz-section-title">Sözleşme Tipi & Taraf</div>
            <div class="wz-section-desc">Dealer veya staff sözleşmesi, ardından tarafı seçin</div>
        </div>
    </div>
    <div class="wz-section-body">
        <div class="f-label" style="margin-bottom:10px;">Sözleşme Türü *</div>
        <div class="type-cards">
            <label class="type-card">
                <input type="radio" name="contract_type" value="dealer"
                       @checked($initType==='dealer') onchange="onTypeChange(this)">
                <div class="type-card-icon">🤝</div>
                <div class="type-card-title">Dealer Sözleşmesi</div>
                <div class="type-card-sub">Bayi ortaklık, referans veya operasyon sözleşmesi</div>
            </label>
            <label class="type-card">
                <input type="radio" name="contract_type" value="staff"
                       @checked($initType==='staff') onchange="onTypeChange(this)">
                <div class="type-card-icon">👤</div>
                <div class="type-card-title">Staff Sözleşmesi</div>
                <div class="type-card-sub">İş sözleşmesi, freelance veya sabit süreli</div>
            </label>
        </div>

        {{-- Dealer Seçici — 3 kategori (Link Dağıtan / Freelance / Operasyon) + Diğer --}}
        <div id="dealerSelectorBlock" style="display:{{ $initType==='dealer' ? 'block' : 'none' }};margin-top:16px;">
            <div class="f-label">Dealer Seç <span style="font-weight:400;color:#94a3b8;font-size:11px;">(kategorilere göre gruplanmış)</span></div>
            <div class="party-select-wrap">
                <span class="party-select-icon">🏢</span>
                <select name="dealer_id" id="dealerSelect" onchange="prefillDealer(this)">
                    <option value="">Dealer seçin...</option>
                    @foreach(($dealersByCategory ?? []) as $catKey => $cat)
                        @if($cat['dealers']->isNotEmpty())
                        <optgroup label="{{ $cat['label'] }}">
                            @foreach($cat['dealers'] as $d)
                                <option value="{{ $d->id }}"
                                    data-firma="{{ $d->name }}"
                                    data-yetkili="{{ $d->contact_name ?? '' }}"
                                    data-adres="{{ $d->address ?? '' }}"
                                    data-vergi="{{ $d->tax_no ?? '' }}"
                                    data-tel="{{ $d->phone ?? '' }}"
                                    data-eposta="{{ $d->email ?? '' }}"
                                    data-kategori="{{ $catKey }}"
                                    data-tip-kodu="{{ $d->dealer_type_code ?? '' }}"
                                    data-suggest-template="{{ $cat['template'] ?? '' }}"
                                    @selected(old('dealer_id')==$d->id || (isset($selectedDealer) && $selectedDealer->id==$d->id))
                                >{{ $d->name }}@if($d->code) · {{ $d->code }}@endif</option>
                            @endforeach
                        </optgroup>
                        @endif
                    @endforeach
                </select>
            </div>
            <div id="dealerCategoryHint" style="margin-top:6px;font-size:11px;color:#64748b;display:none;">
                Seçilen bayi kategorisi: <strong id="dealerCategoryLabel" style="color:#0f172a;">—</strong>
            </div>
        </div>

        {{-- Staff Seçici --}}
        <div id="userSelectorBlock" style="display:{{ $initType==='staff' ? 'block' : 'none' }};margin-top:16px;">
            <div class="f-label">Çalışan Seç</div>
            <div class="party-select-wrap">
                <span class="party-select-icon">👤</span>
                <select name="user_id" id="userSelect" onchange="prefillUser(this)">
                    <option value="">Kullanıcı seçin...</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}"
                            data-isci="{{ $u->name }}"
                            data-eposta="{{ $u->email }}"
                            @selected(old('user_id')==$u->id)
                        >{{ $u->name }} — {{ $u->role }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- BÖLÜM 2: Şablon --}}
<div class="wz-section">
    <div class="wz-section-head">
        <div class="wz-section-icon violet">📋</div>
        <div class="wz-section-num">2</div>
        <div>
            <div class="wz-section-title">Şablon Seç</div>
            <div class="wz-section-desc">Sözleşme metni şablondan otomatik doldurulur</div>
        </div>
    </div>
    <div class="wz-section-body">
        <div id="tplHint" class="tpl-hint" style="{{ $initType ? 'display:none;' : '' }}">
            ← Önce sözleşme türü seçin
        </div>
        <div id="tplGrid" class="tpl-grid" style="{{ $initType ? '' : 'display:none;' }}">
            @foreach($templates as $t)
            <label class="tpl-card" data-type="{{ $t->contract_type }}"
                   style="{{ $initType && $t->contract_type !== $initType ? 'display:none;' : '' }}">
                <input type="radio" name="template_id" value="{{ $t->id }}"
                       data-type="{{ $t->contract_type }}"
                       data-code="{{ $t->template_code }}"
                       onchange="onTemplateChange({{ $t->id }}, '{{ $t->contract_type }}', '{{ $t->template_code }}')"
                       @checked(old('template_id')==$t->id)>
                <div class="tpl-card-name">{{ $t->name }}</div>
                @if($t->notes)
                <div class="tpl-card-note">{{ $t->notes }}</div>
                @endif
                <div style="margin-top:8px;">
                    <span class="badge {{ $t->contract_type==='staff' ? 'info' : 'pending' }}" style="font-size:var(--tx-xs);">
                        {{ $t->contract_type==='staff' ? '👤 Staff' : '🤝 Dealer' }}
                    </span>
                </div>
            </label>
            @endforeach
        </div>
    </div>
</div>

{{-- BÖLÜM 3: Değerler --}}
<div class="wz-section" id="metaSection">
    <div class="wz-section-head">
        <div class="wz-section-icon cyan">⚙️</div>
        <div class="wz-section-num">3</div>
        <div>
            <div class="wz-section-title">Sözleşme Değerleri</div>
            <div class="wz-section-desc">&#123;&#123;placeholder&#125;&#125; alanlarını doldurun — sözleşme metnine otomatik işlenir</div>
        </div>
    </div>
    <div class="wz-section-body">

        {{-- Ortak Alanlar --}}
        <div class="f-divider">Genel</div>
        <div class="f-row col2">
            <div class="f-group">
                <label class="f-label">Sözleşme No</label>
                <input type="text" name="meta[sozlesme_no]" class="f-input" placeholder="Otomatik üretilir" value="{{ old('meta.sozlesme_no') }}">
            </div>
            <div class="f-group">
                <label class="f-label">Sözleşme Tarihi</label>
                <input type="text" name="meta[sozlesme_tarihi]" class="f-input" value="{{ old('meta.sozlesme_tarihi', now()->format('d.m.Y')) }}">
            </div>
            <div class="f-group">
                <label class="f-label">Yetkili Mahkeme</label>
                <input type="text" name="meta[yetkili_mahkeme]" class="f-input" value="{{ old('meta.yetkili_mahkeme', 'İstanbul') }}">
            </div>
            @php $bcBrand = config('brand.name', 'MentorDE'); @endphp
            <div class="f-group">
                <label class="f-label">{{ $bcBrand }} Yetkili</label>
                <input type="text" name="meta[mentorde_yetkili]" class="f-input" value="{{ old('meta.mentorde_yetkili') }}">
            </div>
            <div class="f-group">
                <label class="f-label">{{ $bcBrand }} Adres</label>
                <input type="text" name="meta[mentorde_adres]" class="f-input" value="{{ old('meta.mentorde_adres', config('brand.address', 'İstanbul, Türkiye')) }}">
            </div>
            <div class="f-group">
                <label class="f-label">{{ $bcBrand }} Vergi No</label>
                <input type="text" name="meta[mentorde_vergi_no]" class="f-input" value="{{ old('meta.mentorde_vergi_no', config('brand.tax_id', '')) }}">
            </div>
        </div>

        {{-- ─── DEALER META ─── --}}
        <div id="dealerMetaBlock" class="sub-block">
            <div class="f-divider">🏢 Dealer Bilgileri</div>
            <div class="f-row col2">
                <div class="f-group"><label class="f-label">Firma Adı</label>
                    <input type="text" name="meta[dealer_firma_adi]" id="f_dealer_firma" class="f-input" value="{{ old('meta.dealer_firma_adi') }}"></div>
                <div class="f-group"><label class="f-label">Yetkili Adı</label>
                    <input type="text" name="meta[dealer_yetkili_adi]" id="f_dealer_yetkili" class="f-input" value="{{ old('meta.dealer_yetkili_adi') }}"></div>
                <div class="f-group"><label class="f-label">Adres</label>
                    <input type="text" name="meta[dealer_adres]" id="f_dealer_adres" class="f-input" value="{{ old('meta.dealer_adres') }}"></div>
                <div class="f-group"><label class="f-label">Vergi No</label>
                    <input type="text" name="meta[dealer_vergi_no]" id="f_dealer_vergi" class="f-input" value="{{ old('meta.dealer_vergi_no') }}"></div>
                <div class="f-group"><label class="f-label">Telefon</label>
                    <input type="text" name="meta[dealer_telefon]" id="f_dealer_tel" class="f-input" value="{{ old('meta.dealer_telefon') }}"></div>
                <div class="f-group"><label class="f-label">E-posta</label>
                    <input type="email" name="meta[dealer_eposta]" id="f_dealer_eposta" class="f-input" value="{{ old('meta.dealer_eposta') }}"></div>
            </div>

            <div id="referralFields" class="sub-block">
                <div class="f-divider">💰 Komisyon Yapısı</div>
                <div class="f-row col2">
                    <div class="f-group"><label class="f-label">Tier 1 Max Adet</label>
                        <input type="text" name="meta[komisyon_tier1_adet]" class="f-input" value="{{ old('meta.komisyon_tier1_adet','5') }}"></div>
                    <div class="f-group"><label class="f-label">Tier 1 Oran (%)</label>
                        <input type="text" name="meta[komisyon_tier1_oran]" class="f-input" value="{{ old('meta.komisyon_tier1_oran','10') }}"></div>
                    <div class="f-group"><label class="f-label">Tier 2 Alt – Üst</label>
                        <div style="display:flex;gap:6px;">
                            <input type="text" name="meta[komisyon_tier2_alt]" class="f-input" placeholder="6" value="{{ old('meta.komisyon_tier2_alt','6') }}">
                            <input type="text" name="meta[komisyon_tier2_ust]" class="f-input" placeholder="15" value="{{ old('meta.komisyon_tier2_ust','15') }}">
                        </div></div>
                    <div class="f-group"><label class="f-label">Tier 2 Oran (%)</label>
                        <input type="text" name="meta[komisyon_tier2_oran]" class="f-input" value="{{ old('meta.komisyon_tier2_oran','12') }}"></div>
                    <div class="f-group"><label class="f-label">Tier 3 Alt – Üst</label>
                        <div style="display:flex;gap:6px;">
                            <input type="text" name="meta[komisyon_tier3_alt]" class="f-input" placeholder="16" value="{{ old('meta.komisyon_tier3_alt','16') }}">
                            <input type="text" name="meta[komisyon_tier3_ust]" class="f-input" placeholder="30" value="{{ old('meta.komisyon_tier3_ust','30') }}">
                        </div></div>
                    <div class="f-group"><label class="f-label">Tier 3 Oran (%)</label>
                        <input type="text" name="meta[komisyon_tier3_oran]" class="f-input" value="{{ old('meta.komisyon_tier3_oran','15') }}"></div>
                    <div class="f-group"><label class="f-label">Tier 4 Alt – Üst</label>
                        <div style="display:flex;gap:6px;">
                            <input type="text" name="meta[komisyon_tier4_alt]" class="f-input" placeholder="31" value="{{ old('meta.komisyon_tier4_alt','31') }}">
                            <input type="text" name="meta[komisyon_tier4_ust]" class="f-input" placeholder="50" value="{{ old('meta.komisyon_tier4_ust','50') }}">
                        </div></div>
                    <div class="f-group"><label class="f-label">Tier 4 Oran (%)</label>
                        <input type="text" name="meta[komisyon_tier4_oran]" class="f-input" value="{{ old('meta.komisyon_tier4_oran','17') }}"></div>
                    <div class="f-group"><label class="f-label">Tier 5 Alt</label>
                        <input type="text" name="meta[komisyon_tier5_alt]" class="f-input" value="{{ old('meta.komisyon_tier5_alt','51') }}"></div>
                    <div class="f-group"><label class="f-label">Tier 5 Oran (%)</label>
                        <input type="text" name="meta[komisyon_tier5_oran]" class="f-input" value="{{ old('meta.komisyon_tier5_oran','20') }}"></div>
                    <div class="f-group"><label class="f-label">Min Ödeme Eşiği (TL)</label>
                        <input type="text" name="meta[min_odeme_esigi]" class="f-input" value="{{ old('meta.min_odeme_esigi','500') }}"></div>
                    <div class="f-group"><label class="f-label">Bitiş Tarihi</label>
                        <input type="text" name="meta[sozlesme_bitis_tarihi]" class="f-input" value="{{ old('meta.sozlesme_bitis_tarihi', now()->addYear()->format('d.m.Y')) }}"></div>
                </div>
            </div>

            <div id="opsFields" class="sub-block">
                <div class="f-divider">🔧 Operasyon Parametreleri</div>
                <div class="f-row col2">
                    <div class="f-group"><label class="f-label">Acil Yanıt (saat)</label>
                        <input type="text" name="meta[max_yanit_suresi_saat]" class="f-input" value="{{ old('meta.max_yanit_suresi_saat','2') }}"></div>
                    <div class="f-group"><label class="f-label">Veri İhlali Bildirim (saat)</label>
                        <input type="text" name="meta[veri_ihlali_bildirim_saat]" class="f-input" value="{{ old('meta.veri_ihlali_bildirim_saat','72') }}"></div>
                    <div class="f-group"><label class="f-label">Eğitim Yenileme (ay)</label>
                        <input type="text" name="meta[egitim_yenileme_ay]" class="f-input" value="{{ old('meta.egitim_yenileme_ay','6') }}"></div>
                    <div class="f-group"><label class="f-label">Denetim Bildirimi (iş günü)</label>
                        <input type="text" name="meta[denetim_bildirim_gun]" class="f-input" value="{{ old('meta.denetim_bildirim_gun','5') }}"></div>
                    <div class="f-group"><label class="f-label">Rakip Bildirim (takvim günü)</label>
                        <input type="text" name="meta[rakip_bildirim_gun]" class="f-input" value="{{ old('meta.rakip_bildirim_gun','15') }}"></div>
                </div>
            </div>
        </div>

        {{-- ─── STAFF META ─── --}}
        <div id="staffMetaBlock" class="sub-block">
            @php $bcBrand2 = config('brand.name', 'MentorDE'); @endphp
            <div class="f-divider">🏢 İşveren ({{ $bcBrand2 }})</div>
            <div class="f-row col2">
                <div class="f-group"><label class="f-label">İşveren Adı</label>
                    <input type="text" name="meta[isverenler_adi]" class="f-input" value="{{ old('meta.isverenler_adi', $bcBrand2) }}"></div>
                <div class="f-group"><label class="f-label">İşveren Adresi</label>
                    <input type="text" name="meta[isverenler_adres]" class="f-input" value="{{ old('meta.isverenler_adres','İstanbul, Türkiye') }}"></div>
                <div class="f-group"><label class="f-label">İşveren Vergi No</label>
                    <input type="text" name="meta[isverenler_vergi_no]" class="f-input" value="{{ old('meta.isverenler_vergi_no') }}"></div>
                <div class="f-group"><label class="f-label">İşveren Telefon</label>
                    <input type="text" name="meta[isverenler_telefon]" class="f-input" value="{{ old('meta.isverenler_telefon') }}"></div>
            </div>

            <div class="f-divider">👤 İşçi Bilgileri</div>
            <div class="f-row col2">
                <div class="f-group"><label class="f-label">Adı Soyadı</label>
                    <input type="text" name="meta[isci_adi]" id="f_isci_adi" class="f-input" value="{{ old('meta.isci_adi') }}"></div>
                <div class="f-group"><label class="f-label">TC Kimlik No</label>
                    <input type="text" name="meta[isci_kimlik_no]" class="f-input" value="{{ old('meta.isci_kimlik_no') }}"></div>
                <div class="f-group"><label class="f-label">Doğum Tarihi</label>
                    <input type="text" name="meta[isci_dogum_tarihi]" class="f-input" placeholder="gg.aa.yyyy" value="{{ old('meta.isci_dogum_tarihi') }}"></div>
                <div class="f-group"><label class="f-label">Adres</label>
                    <input type="text" name="meta[isci_adres]" class="f-input" value="{{ old('meta.isci_adres') }}"></div>
                <div class="f-group"><label class="f-label">Telefon</label>
                    <input type="text" name="meta[isci_telefon]" id="f_isci_tel" class="f-input" value="{{ old('meta.isci_telefon') }}"></div>
            </div>

            <div class="f-divider">💼 İş Bilgileri</div>
            <div class="f-row col2">
                <div class="f-group"><label class="f-label">Pozisyon</label>
                    <input type="text" name="meta[pozisyon]" class="f-input" value="{{ old('meta.pozisyon') }}"></div>
                <div class="f-group"><label class="f-label">Departman</label>
                    <input type="text" name="meta[departman]" class="f-input" value="{{ old('meta.departman') }}"></div>
                <div class="f-group"><label class="f-label">Yönetici</label>
                    <input type="text" name="meta[yonetici_adi]" class="f-input" value="{{ old('meta.yonetici_adi') }}"></div>
                <div class="f-group"><label class="f-label">Başlangıç Tarihi</label>
                    <input type="text" name="meta[baslangic_tarihi]" class="f-input" value="{{ old('meta.baslangic_tarihi', now()->format('d.m.Y')) }}"></div>
                <div class="f-group"><label class="f-label">Çalışma Yeri</label>
                    <input type="text" name="meta[calisma_yeri]" class="f-input" value="{{ old('meta.calisma_yeri','İstanbul') }}"></div>
                <div class="f-group"><label class="f-label">Meslek / Unvan</label>
                    <input type="text" name="meta[meslek]" class="f-input" value="{{ old('meta.meslek') }}"></div>
            </div>

            <div id="staffRegSalaryBlock">
                <div class="f-divider">💵 Ücret Bilgileri</div>
                <div class="f-row col2">
                    <div class="f-group"><label class="f-label">Aylık Maaş Brüt (TRY)</label>
                        <input type="text" name="meta[aylik_maas]" class="f-input" value="{{ old('meta.aylik_maas') }}"></div>
                    <div class="f-group"><label class="f-label">Sabit Ücret (TRY)</label>
                        <input type="text" name="meta[sabit_ucret]" class="f-input" value="{{ old('meta.sabit_ucret') }}"></div>
                    <div class="f-group"><label class="f-label">Sağlık/Ulaşım Yardımı (TRY)</label>
                        <input type="text" name="meta[yardim]" class="f-input" placeholder="0" value="{{ old('meta.yardim','0') }}"></div>
                    <div class="f-group"><label class="f-label">Diğer Yardımlar (TRY)</label>
                        <input type="text" name="meta[diger_yardim]" class="f-input" placeholder="0" value="{{ old('meta.diger_yardim','0') }}"></div>
                </div>
            </div>

            <div id="staffFixedBlock" class="sub-block">
                <div class="f-divider">📅 Belirli Süreli / Yarı Zamanlı</div>
                <div class="f-row col2">
                    <div class="f-group"><label class="f-label">Bitiş Tarihi</label>
                        <input type="text" name="meta[bitis_tarihi]" class="f-input" placeholder="gg.aa.yyyy" value="{{ old('meta.bitis_tarihi') }}"></div>
                    <div class="f-group"><label class="f-label">Haftalık Saat</label>
                        <input type="text" name="meta[haftalik_saat]" class="f-input" placeholder="20" value="{{ old('meta.haftalik_saat') }}"></div>
                </div>
            </div>

            <div id="staffCommBlock" class="sub-block">
                <div class="f-divider">📊 Komisyon Parametreleri</div>
                <div class="f-row col2">
                    <div class="f-group"><label class="f-label">Komisyon Oranı (%)</label>
                        <input type="text" name="meta[komisyon_oran]" class="f-input" placeholder="15" value="{{ old('meta.komisyon_oran') }}"></div>
                    <div class="f-group"><label class="f-label">Aylık Hedef Adet</label>
                        <input type="text" name="meta[hedef_adet]" class="f-input" placeholder="10" value="{{ old('meta.hedef_adet') }}"></div>
                    <div class="f-group"><label class="f-label">Min. Ödeme Eşiği (TRY)</label>
                        <input type="text" name="meta[komisyon_esigi]" class="f-input" placeholder="500" value="{{ old('meta.komisyon_esigi','500') }}"></div>
                    <div class="f-group"><label class="f-label">Ödeme Periyodu</label>
                        <input type="text" name="meta[odeme_periyodu]" class="f-input" placeholder="Aylık" value="{{ old('meta.odeme_periyodu','Aylık') }}"></div>
                </div>
            </div>

            <div id="staffFreelanceBlock" class="sub-block">
                <div class="f-divider">🔨 Proje / Freelance Detayları</div>
                <div class="f-row col2">
                    <div class="f-group"><label class="f-label">Proje Adı</label>
                        <input type="text" name="meta[proje_adi]" class="f-input" value="{{ old('meta.proje_adi') }}"></div>
                    <div class="f-group"><label class="f-label">Toplam Ücret (TRY)</label>
                        <input type="text" name="meta[toplam_ucret]" class="f-input" value="{{ old('meta.toplam_ucret') }}"></div>
                    <div class="f-group" style="grid-column:span 2;"><label class="f-label">Proje Açıklaması</label>
                        <textarea name="meta[proje_aciklamasi]" class="f-input" rows="2">{{ old('meta.proje_aciklamasi') }}</textarea></div>
                    <div class="f-group"><label class="f-label">Km 1 Tarih</label>
                        <input type="text" name="meta[km1_tarih]" class="f-input" placeholder="gg.aa.yyyy" value="{{ old('meta.km1_tarih') }}"></div>
                    <div class="f-group"><label class="f-label">Km 1 Ücret (TRY)</label>
                        <input type="text" name="meta[km1_ucret]" class="f-input" value="{{ old('meta.km1_ucret') }}"></div>
                    <div class="f-group"><label class="f-label">Km 2 Tarih</label>
                        <input type="text" name="meta[km2_tarih]" class="f-input" placeholder="gg.aa.yyyy" value="{{ old('meta.km2_tarih') }}"></div>
                    <div class="f-group"><label class="f-label">Km 2 Ücret (TRY)</label>
                        <input type="text" name="meta[km2_ucret]" class="f-input" value="{{ old('meta.km2_ucret') }}"></div>
                    <div class="f-group"><label class="f-label">Bitiş Tarihi</label>
                        <input type="text" name="meta[bitis_tarihi]" class="f-input" placeholder="gg.aa.yyyy" value="{{ old('meta.bitis_tarihi') }}"></div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- BÖLÜM 4: Metin --}}
<div class="wz-section">
    <div class="wz-section-head">
        <div class="wz-section-icon green">📝</div>
        <div class="wz-section-num">4</div>
        <div>
            <div class="wz-section-title">Sözleşme Metni</div>
            <div class="wz-section-desc">Şablon seçilince buraya gelir — serbestçe düzenleyebilirsiniz</div>
        </div>
    </div>
    <div class="wz-section-body" style="padding:0;">
        <div class="body-toolbar">
            <span>📄 Sözleşme metni — monospace · düzenlenebilir</span>
            <span id="charCount" style="font-family:monospace;color:var(--muted);font-size:var(--tx-xs);">0 karakter</span>
        </div>
        <div class="body-textarea-wrap" style="padding:0 20px 20px;">
            <textarea name="body_text_override" id="bodyTextOverride" class="body-textarea"
                      placeholder="Şablon seçilince sözleşme metni buraya gelir. İstediğiniz gibi düzenleyebilirsiniz.">{{ old('body_text_override') }}</textarea>
        </div>
    </div>
</div>

{{-- BÖLÜM 5: Notlar --}}
<div class="wz-section">
    <div class="wz-section-head">
        <div class="wz-section-icon amber">🗒️</div>
        <div>
            <div class="wz-section-title">Dahili Notlar</div>
            <div class="wz-section-desc">İsteğe bağlı — yalnızca iç ekip görür</div>
        </div>
    </div>
    <div class="wz-section-body">
        <textarea name="notes" class="f-input" rows="3"
                  placeholder="Sözleşmeyle ilgili dahili notlar...">{{ old('notes') }}</textarea>
    </div>
</div>

</div>{{-- /sol --}}

{{-- ── SAĞ: Özet Panel ── --}}
<div class="wz-summary">
    <div class="summary-card">
        <div class="summary-head">
            <div class="summary-head-title">📋 Sözleşme Özeti</div>
            <div class="summary-head-sub">Oluşturulmadan önce kontrol edin</div>
        </div>
        <div class="summary-body">
            <div class="summary-row">
                <span class="summary-row-key">Tür</span>
                <span class="summary-row-val" id="sum-type"><span class="summary-empty">Seçilmedi</span></span>
            </div>
            <div class="summary-row">
                <span class="summary-row-key">Taraf</span>
                <span class="summary-row-val" id="sum-party"><span class="summary-empty">Seçilmedi</span></span>
            </div>
            <div class="summary-row">
                <span class="summary-row-key">Şablon</span>
                <span class="summary-row-val" id="sum-template"><span class="summary-empty">Seçilmedi</span></span>
            </div>
            <div class="summary-row">
                <span class="summary-row-key">Metin</span>
                <span class="summary-row-val" id="sum-body"><span class="summary-empty">Boş</span></span>
            </div>
        </div>
        <div class="summary-tip">
            💡 Sözleşme önce <strong>Taslak</strong> olarak kaydedilir. Ardından taraflara gönderilebilir.
        </div>
    </div>

    <div class="action-panel">
        <button type="submit" class="btn-create">
            💾 Taslak Olarak Oluştur
        </button>
        <a href="{{ route('manager.business-contracts.index', ['type' => $initType ?: null]) }}"
           class="btn-cancel">İptal</a>
    </div>
</div>

</div>{{-- /wz-layout --}}
</form>

<script>
const _templateBodies = @json($templates->pluck('body_text', 'id'));

/* ── Yardımcı ─────────────────────────────────── */
function show(id){ var el=document.getElementById(id); if(el) el.style.display='block'; }
function hide(id){ var el=document.getElementById(id); if(el) el.style.display='none'; }

/* ── Özet güncelle ─────────────────────────────── */
function updateSummary() {
    var type = document.querySelector('input[name=contract_type]:checked');
    var tpl  = document.querySelector('input[name=template_id]:checked');
    var partyEl = type?.value==='dealer'
        ? document.getElementById('dealerSelect')
        : document.getElementById('userSelect');
    var body = (document.getElementById('bodyTextOverride')?.value || '').trim();

    document.getElementById('sum-type').innerHTML    = type ? '<strong>'+{dealer:'🤝 Dealer',staff:'👤 Staff'}[type.value]+'</strong>' : '<span class="summary-empty">Seçilmedi</span>';
    document.getElementById('sum-party').innerHTML   = (partyEl?.value && partyEl.options[partyEl.selectedIndex]?.text.trim())
        ? '<strong>'+partyEl.options[partyEl.selectedIndex].text.trim()+'</strong>'
        : '<span class="summary-empty">Seçilmedi</span>';
    document.getElementById('sum-template').innerHTML = tpl
        ? '<strong>'+tpl.closest('.tpl-card').querySelector('.tpl-card-name').textContent+'</strong>'
        : '<span class="summary-empty">Seçilmedi</span>';
    document.getElementById('sum-body').innerHTML = body
        ? '<strong style="color:var(--c-ok)">'+body.length+' karakter ✓</strong>'
        : '<span class="summary-empty">Boş</span>';
}

/* ── Adım göstergesi güncelle ─────────────────── */
function updateSteps() {
    var type = document.querySelector('input[name=contract_type]:checked');
    var tpl  = document.querySelector('input[name=template_id]:checked');
    var body = (document.getElementById('bodyTextOverride')?.value || '').trim();

    var steps = [
        { el:'step1', done: !!type },
        { el:'step2', done: !!tpl  },
        { el:'step3', done: !!(type) }, // değerler bölümü tip seçince açılıyor
        { el:'step4', done: body.length > 0 },
    ];
    steps.forEach(function(s,i){
        var el = document.getElementById(s.el);
        if (!el) return;
        el.classList.remove('active','done');
        if (s.done && i < steps.length-1 && steps[i+1] && (i===0 ? !!tpl : true)) {
            // basit: done = önceki tamamlandı
        }
        if (s.done) el.querySelector('.wz-step-num').textContent = '✓';
    });
}

/* ── Tip değişti ────────────────────────────────── */
function onTypeChange(radio) {
    var type = radio.value;

    hide('dealerSelectorBlock'); hide('userSelectorBlock');
    if (type==='dealer') show('dealerSelectorBlock');
    else                 show('userSelectorBlock');

    // Şablon kartları filtrele
    var anyVisible = false;
    document.querySelectorAll('.tpl-card').forEach(function(card){
        var show_ = card.dataset.type === type;
        card.style.display = show_ ? 'block' : 'none';
        if (show_) anyVisible = true;
        card.querySelector('input').checked = false;
    });
    var hint = document.getElementById('tplHint');
    var grid = document.getElementById('tplGrid');
    if (hint) hint.style.display = anyVisible ? 'none' : 'block';
    if (grid) grid.style.display = anyVisible ? 'grid'  : 'none';

    // Meta blokları sıfırla
    hide('dealerMetaBlock'); hide('staffMetaBlock');
    hide('referralFields'); hide('opsFields');
    hide('staffFixedBlock'); hide('staffCommBlock'); hide('staffFreelanceBlock');
    show('staffRegSalaryBlock');

    document.getElementById('bodyTextOverride').value = '';
    updateSummary();
}

/* ── Şablon seçildi ─────────────────────────────── */
function onTemplateChange(id, type, code) {
    var ta = document.getElementById('bodyTextOverride');
    if (ta && _templateBodies && _templateBodies[id])
        ta.value = _templateBodies[id];
    updateCharCount();

    hide('dealerMetaBlock'); hide('staffMetaBlock');
    hide('referralFields'); hide('opsFields');
    hide('staffFixedBlock'); hide('staffCommBlock'); hide('staffFreelanceBlock');
    show('staffRegSalaryBlock');

    if (type==='dealer') {
        show('dealerMetaBlock');
        if (code==='dealer_operations_v1') show('opsFields');
        else show('referralFields');
    } else if (type==='staff') {
        show('staffMetaBlock');
        if (code==='staff_freelance_v1') {
            hide('staffRegSalaryBlock');
            show('staffFreelanceBlock');
        } else {
            if (code.indexOf('fixed')>-1 || code.indexOf('commission')>-1 || code.indexOf('parttime')>-1)
                show('staffFixedBlock');
            if (code.indexOf('commission')>-1)
                show('staffCommBlock');
        }
    }
    updateSummary();
}

/* ── Dealer prefill ─────────────────────────────── */
function prefillDealer(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('f_dealer_firma').value   = opt.dataset.firma   || '';
    document.getElementById('f_dealer_yetkili').value = opt.dataset.yetkili || '';
    document.getElementById('f_dealer_adres').value   = opt.dataset.adres   || '';
    document.getElementById('f_dealer_vergi').value   = opt.dataset.vergi   || '';
    document.getElementById('f_dealer_tel').value     = opt.dataset.tel     || '';
    document.getElementById('f_dealer_eposta').value  = opt.dataset.eposta  || '';

    // Kategori hint'i göster — parent optgroup label'ından al
    var hintEl  = document.getElementById('dealerCategoryHint');
    var labelEl = document.getElementById('dealerCategoryLabel');
    if (hintEl && labelEl) {
        if (opt.value && opt.parentElement && opt.parentElement.label) {
            labelEl.textContent = opt.parentElement.label;
            hintEl.style.display = 'block';
        } else {
            hintEl.style.display = 'none';
        }
    }

    // Önerilen template'i otomatik seç ve işaretle
    var suggestCode = opt.dataset.suggestTemplate || '';
    if (suggestCode) {
        var radio = document.querySelector('input[name="template_id"][data-code="' + suggestCode + '"]');
        if (radio && !radio.checked) {
            radio.checked = true;
            // onchange handler'ı tetikle
            if (typeof onTemplateChange === 'function') {
                onTemplateChange(parseInt(radio.value), radio.dataset.type, radio.dataset.code);
            } else {
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }
    updateSummary();
}

/* ── User prefill ───────────────────────────────── */
function prefillUser(sel) {
    var opt = sel.options[sel.selectedIndex];
    var el  = document.getElementById('f_isci_adi');
    if (el) el.value = opt.dataset.isci || '';
    updateSummary();
}

/* ── Karakter sayacı ────────────────────────────── */
function updateCharCount() {
    var ta  = document.getElementById('bodyTextOverride');
    var cnt = document.getElementById('charCount');
    if (ta && cnt) cnt.textContent = (ta.value||'').length + ' karakter';
}

/* ── Init ───────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    var ta = document.getElementById('bodyTextOverride');
    if (ta) ta.addEventListener('input', function(){ updateCharCount(); updateSummary(); });

    var type = document.querySelector('input[name=contract_type]:checked');
    if (type) onTypeChange(type);

    var tpl = document.querySelector('input[name=template_id]:checked');
    if (tpl) onTemplateChange(tpl.value, tpl.dataset.type, tpl.dataset.code);

    ['dealerSelect','userSelect'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', updateSummary);
    });

    updateCharCount();
    updateSummary();
});
</script>
@endsection

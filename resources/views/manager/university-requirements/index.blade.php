@extends('manager.layouts.app')

@section('title', 'Üniversite Belge Haritası')
@section('page_title', 'Üniversite Belge Haritası')

@push('head')
<style>
.mgr-filter-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }

/* Harita kartı */
.umap-card { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-top:3px solid #1e40af; border-radius:10px; padding:16px; margin-bottom:10px; }
.umap-card.inactive { border-top-color:#94a3b8; opacity:.85; }
.umap-title { font-size:15px; font-weight:700; color:var(--text,#0f172a); margin-bottom:3px; }
.umap-sub   { font-size:12px; color:var(--muted,#64748b); margin-bottom:8px; }

/* Belge listesi */
.doc-list { display:flex; flex-direction:column; gap:4px; }
.doc-row  { display:flex; align-items:baseline; gap:8px; font-size:12px; }
.doc-code { background:#f1f5f9; border:1px solid var(--border,#e2e8f0); padding:1px 7px; border-radius:4px; font-size:11px; font-family:monospace; color:#1e40af; flex-shrink:0; }
.doc-code.rec { color:var(--muted,#64748b); border-color:transparent; }

/* Edit toggle */
.edit-toggle { font-size:12px; font-weight:600; color:#1e40af; cursor:pointer; text-decoration:none; }
summary { list-style:none; }
summary::-webkit-details-marker { display:none; }

/* ─── Düzenleme / Yeni Harita formu ─── */
.ur-form { background:var(--bg,#f8fafc); border:1px solid var(--border,#e2e8f0); border-radius:10px; padding:16px; }
.ur-section { margin-bottom:14px; }
.ur-section:last-child { margin-bottom:0; }
.ur-section-title { font-size:10px; font-weight:700; color:#1e40af; text-transform:uppercase; letter-spacing:.06em; margin:0 0 8px; padding-bottom:5px; border-bottom:1px solid #dbe4f2; }

/* Form field grid */
.ur-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
@media(max-width:1100px){ .ur-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:700px){ .ur-grid { grid-template-columns:1fr; } }
.ur-field { display:flex; flex-direction:column; gap:4px; }
.ur-field.full { grid-column:1/-1; }
.ur-field label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }
.ur-field select, .ur-field input[type=text], .ur-field input[type=number], .ur-field textarea {
    font-size:12px !important; padding:7px 10px !important; min-height:34px !important;
    border:1px solid var(--border,#e2e8f0); border-radius:6px; background:#fff;
    color:var(--text,#0f172a); line-height:1.3; font-family:inherit;
}
.ur-field textarea { min-height:60px !important; resize:vertical; }
.ur-field select:focus, .ur-field input:focus, .ur-field textarea:focus { outline:none; border-color:#1e40af; box-shadow:0 0 0 2px rgba(30,64,175,.12); }

/* Date pair (ay / gün) */
.ur-date-pair { display:flex; gap:6px; }
.ur-date-pair input { flex:1; min-width:0; }

/* Aktif checkbox */
.ur-active-row { display:flex; align-items:center; gap:8px; padding:8px 12px; background:#fff; border:1px solid var(--border,#e2e8f0); border-radius:6px; }
.ur-active-row label { font-size:12px; font-weight:500; color:var(--text,#0f172a); cursor:pointer; margin:0; }

/* Checkbox grid — daha scannable */
.cb-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:6px; background:#fff; padding:10px; border:1px solid var(--border,#e2e8f0); border-radius:8px; max-height:280px; overflow-y:auto; }
.cb-item { display:flex; align-items:center; gap:6px; font-size:11px; padding:5px 8px; border-radius:5px; cursor:pointer; border:1px solid transparent; transition:all .1s; color:var(--text,#0f172a); user-select:none; font-family:monospace; letter-spacing:.3px; }
.cb-item:hover { background:#eef4ff; border-color:#dbe4f2; }
.cb-item input[type=checkbox] { cursor:pointer; margin:0; flex-shrink:0; }
.cb-item:has(input:checked) { background:#dbeafe; border-color:#1e40af; color:#1e40af; font-weight:700; }

/* Form actions */
.ur-actions { display:flex; gap:8px; padding-top:12px; margin-top:14px; border-top:1px solid var(--border,#e2e8f0); }
.ur-btn-save { padding:8px 20px; background:#16a34a; color:#fff; border:none; border-radius:7px; font-size:12px; font-weight:700; cursor:pointer; transition:background .12s; }
.ur-btn-save:hover { background:#15803d; }
.ur-btn-cancel { padding:8px 16px; border:1px solid var(--border,#e2e8f0); background:#fff; color:var(--muted,#64748b); border-radius:7px; font-size:12px; font-weight:600; cursor:pointer; }
.ur-btn-cancel:hover { background:#f8fafc; color:var(--text,#0f172a); }
</style>
@endpush

@section('content')

@if(session('status'))
    <div style="padding:10px 14px;margin-bottom:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:var(--tx-sm);color:#15803d;font-weight:500;">
        ✓ {{ session('status') }}
    </div>
@endif

{{-- Filtreler --}}
<section class="panel" style="margin-bottom:12px;">
    <form method="GET" action="{{ route('manager.university-requirements') }}" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Üniversite</label>
            <select name="university_code" style="min-width:200px;">
                <option value="">Tüm Üniversiteler</option>
                @foreach($catalog as $code => $uni)
                    <option value="{{ $code }}" {{ request('university_code') === $code ? 'selected' : '' }}>{{ $uni['name_tr'] }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Bölüm Kodu</label>
            <input type="text" name="department_code" value="{{ request('department_code') }}" placeholder="INFORMATIK" style="width:130px;">
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Derece</label>
            <select name="degree_type">
                <option value="">Tümü</option>
                <option value="master"    {{ request('degree_type')==='master'    ? 'selected' : '' }}>Master</option>
                <option value="bachelor"  {{ request('degree_type')==='bachelor'  ? 'selected' : '' }}>Lisans</option>
                <option value="phd"       {{ request('degree_type')==='phd'       ? 'selected' : '' }}>Doktora</option>
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" style="padding:6px 16px;background:#1e40af;color:#fff;border:none;border-radius:7px;font-size:var(--tx-xs);font-weight:600;cursor:pointer;">Filtrele</button>
            <a href="{{ route('manager.university-requirements') }}" style="padding:6px 12px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:var(--tx-xs);color:var(--muted,#64748b);text-decoration:none;background:var(--surface,#fff);">Temizle</a>
        </div>
    </form>
</section>

{{-- Mevcut Haritalar --}}
@if($maps->isEmpty())
    <div style="padding:40px;text-align:center;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;margin-bottom:12px;">
        <div style="font-size:var(--tx-2xl);margin-bottom:8px;">🗺️</div>
        <div style="color:var(--muted,#64748b);">Henüz belge haritası tanımlanmamış.</div>
        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:4px;">Aşağıdan yeni harita ekleyebilirsiniz.</div>
    </div>
@else
    @php
        $portalLabels = ['uni_assist'=>'Uni-Assist','direct'=>'Direkt','hochschulstart'=>'Hochschulstart','other'=>'Diğer'];
        $semLabels    = ['WS'=>'Kış (WS)','SS'=>'Yaz (SS)','both'=>'Her İki Dönem'];
    @endphp
    @foreach($maps as $map)
    @php
        $uniName  = $catalog[$map->university_code]['name_tr'] ?? $map->university_code;
        $deptName = $map->department_code
            ? ($catalog[$map->university_code]['departments'][$map->department_code]['name_tr'] ?? $map->department_code)
            : 'Genel';
    @endphp
    <div class="umap-card {{ $map->is_active ? '' : 'inactive' }}">
        {{-- Başlık satırı --}}
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <div class="umap-title">{{ $uniName }}</div>
                <div class="umap-sub">{{ $deptName }} &middot; {{ \App\Models\StudentUniversityApplication::DEGREE_LABELS[$map->degree_type] ?? $map->degree_type }} &middot; {{ $semLabels[$map->semester] ?? $map->semester }}</div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                    <span class="badge pending">{{ $portalLabels[$map->portal_name] ?? $map->portal_name }}</span>
                    @if($map->deadline_month_ws)
                        <span class="badge warn" style="font-size:var(--tx-xs);">WS: {{ $map->deadlineWsLabel() }}</span>
                    @endif
                    @if($map->deadline_month_ss)
                        <span class="badge warn" style="font-size:var(--tx-xs);">SS: {{ $map->deadlineSsLabel() }}</span>
                    @endif
                    @if($map->language_requirement)
                        <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);">🗣️ {{ $map->language_requirement }}</span>
                    @endif
                    @if(!$map->is_active)
                        <span class="badge danger">Pasif</span>
                    @endif
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;">
                <button onclick="document.getElementById('edit-map-{{ $map->id }}').open=!document.getElementById('edit-map-{{ $map->id }}').open"
                    style="padding:5px 12px;font-size:var(--tx-xs);font-weight:600;color:#1e40af;border:1px solid rgba(30,64,175,.3);border-radius:6px;background:rgba(30,64,175,.06);cursor:pointer;">
                    Düzenle
                </button>
                <form method="POST" action="{{ route('manager.university-requirements.delete', $map) }}"
                    onsubmit="return confirm('Bu haritayı silmek istiyor musunuz?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="padding:5px 12px;font-size:var(--tx-xs);font-weight:600;color:#b91c1c;border:1px solid rgba(220,38,38,.3);border-radius:6px;background:rgba(220,38,38,.05);cursor:pointer;">
                        Sil
                    </button>
                </form>
            </div>
        </div>

        {{-- Belge listeleri --}}
        <div style="margin-top:12px;display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;">
            <div style="flex:1;min-width:240px;">
                <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">
                    Zorunlu Belgeler ({{ count($map->required_document_codes) }})
                </div>
                <div class="doc-list">
                    @foreach($map->required_document_codes as $code)
                    <div class="doc-row">
                        <span style="color:#16a34a;font-size:var(--tx-xs);">●</span>
                        <span class="doc-code">{{ $code }}</span>
                        <span style="color:var(--text,#0f172a);">{{ $docCatalog[$code]['label_tr'] ?? $code }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @if(!empty($map->recommended_document_codes))
            <div style="flex:1;min-width:200px;">
                <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">
                    Tavsiye Edilen
                </div>
                <div class="doc-list">
                    @foreach($map->recommended_document_codes as $code)
                    <div class="doc-row">
                        <span style="color:var(--muted,#64748b);font-size:var(--tx-xs);">○</span>
                        <span class="doc-code rec">{{ $code }}</span>
                        <span style="color:var(--muted,#64748b);">{{ $docCatalog[$code]['label_tr'] ?? $code }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        @if($map->notes)
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border,#e2e8f0);font-size:var(--tx-xs);color:var(--muted,#64748b);font-style:italic;">
            📝 {{ $map->notes }}
        </div>
        @endif

        {{-- Düzenleme formu --}}
        <details id="edit-map-{{ $map->id }}" style="margin-top:12px;">
            <summary></summary>
            <div style="padding-top:12px;">
                <form method="POST" action="{{ route('manager.university-requirements.update', $map) }}" class="ur-form">
                    @csrf @method('PUT')

                    <div class="ur-section">
                        <div class="ur-section-title">Başvuru Bilgileri</div>
                        <div class="ur-grid">
                            <div class="ur-field">
                                <label>Portal</label>
                                <select name="portal_name">
                                    @foreach(['uni_assist'=>'Uni-Assist','direct'=>'Direkt','hochschulstart'=>'Hochschulstart','other'=>'Diğer'] as $val=>$lbl)
                                        <option value="{{ $val }}" {{ $map->portal_name===$val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ur-field">
                                <label>Dönem</label>
                                <select name="semester">
                                    <option value="WS"   {{ $map->semester==='WS'   ? 'selected' : '' }}>Kış (WS)</option>
                                    <option value="SS"   {{ $map->semester==='SS'   ? 'selected' : '' }}>Yaz (SS)</option>
                                    <option value="both" {{ $map->semester==='both' ? 'selected' : '' }}>Her İki Dönem</option>
                                </select>
                            </div>
                            <div class="ur-field">
                                <label>Durum</label>
                                <div class="ur-active-row">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" id="active-{{ $map->id }}" value="1" {{ $map->is_active ? 'checked' : '' }}>
                                    <label for="active-{{ $map->id }}">Aktif</label>
                                </div>
                            </div>
                            <div class="ur-field">
                                <label>WS Son Başvuru (Ay / Gün)</label>
                                <div class="ur-date-pair">
                                    <input type="number" name="deadline_month_ws" value="{{ $map->deadline_month_ws }}" placeholder="Ay" min="1" max="12">
                                    <input type="number" name="deadline_day_ws"   value="{{ $map->deadline_day_ws }}"   placeholder="Gün" min="1" max="31">
                                </div>
                            </div>
                            <div class="ur-field">
                                <label>SS Son Başvuru (Ay / Gün)</label>
                                <div class="ur-date-pair">
                                    <input type="number" name="deadline_month_ss" value="{{ $map->deadline_month_ss }}" placeholder="Ay" min="1" max="12">
                                    <input type="number" name="deadline_day_ss"   value="{{ $map->deadline_day_ss }}"   placeholder="Gün" min="1" max="31">
                                </div>
                            </div>
                            <div class="ur-field full">
                                <label>Dil Gereksinimi</label>
                                <input type="text" name="language_requirement" value="{{ $map->language_requirement }}" placeholder="ör. DSH-2 oder TestDaF 4x4">
                            </div>
                        </div>
                    </div>

                    <div class="ur-section">
                        <div class="ur-section-title">Zorunlu Belgeler *</div>
                        <div class="cb-grid">
                            @foreach($docCatalog as $code => $doc)
                            <label class="cb-item" title="{{ $doc['label_tr'] }}">
                                <input type="checkbox" name="required_document_codes[]" value="{{ $code }}"
                                    {{ in_array($code, $map->required_document_codes) ? 'checked' : '' }}>
                                {{ $code }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="ur-section">
                        <div class="ur-section-title">Tavsiye Edilen Belgeler</div>
                        <div class="cb-grid">
                            @foreach($docCatalog as $code => $doc)
                            <label class="cb-item" title="{{ $doc['label_tr'] }}">
                                <input type="checkbox" name="recommended_document_codes[]" value="{{ $code }}"
                                    {{ in_array($code, $map->recommended_document_codes ?? []) ? 'checked' : '' }}>
                                {{ $code }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="ur-section">
                        <div class="ur-section-title">Notlar</div>
                        <div class="ur-field full">
                            <textarea name="notes" rows="3" placeholder="Önemli ek bilgiler...">{{ $map->notes }}</textarea>
                        </div>
                    </div>

                    <div class="ur-actions">
                        <button type="submit" class="ur-btn-save">Kaydet</button>
                        <button type="button" class="ur-btn-cancel" onclick="document.getElementById('edit-map-{{ $map->id }}').open=false">İptal</button>
                    </div>
                </form>
            </div>
        </details>
    </div>
    @endforeach
@endif

{{-- Yeni Harita Ekle --}}
<details style="margin-bottom:12px;">
    <summary style="cursor:pointer;display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#1e40af;color:#fff;border-radius:8px;font-size:var(--tx-sm);font-weight:600;list-style:none;">
        + Yeni Belge Haritası Ekle
    </summary>
    <div style="margin-top:8px;">
        <form method="POST" action="{{ route('manager.university-requirements.store') }}" class="ur-form">
            @csrf

            <div class="ur-section">
                <div class="ur-section-title">Hedef Bilgileri</div>
                <div class="ur-grid">
                    <div class="ur-field">
                        <label>Üniversite *</label>
                        <select name="university_code" required>
                            <option value="">Seçin...</option>
                            @foreach($catalog as $code => $uni)
                                <option value="{{ $code }}">{{ $uni['name_tr'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ur-field">
                        <label>Bölüm Kodu (boş = tüm bölümler)</label>
                        <input type="text" name="department_code" placeholder="INFORMATIK">
                    </div>
                    <div class="ur-field">
                        <label>Derece *</label>
                        <select name="degree_type" required>
                            <option value="master">Master</option>
                            <option value="bachelor">Lisans</option>
                            <option value="phd">Doktora</option>
                            <option value="ausbildung">Ausbildung</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="ur-section">
                <div class="ur-section-title">Başvuru Bilgileri</div>
                <div class="ur-grid">
                    <div class="ur-field">
                        <label>Portal *</label>
                        <select name="portal_name" required>
                            <option value="uni_assist">Uni-Assist</option>
                            <option value="direct">Direkt</option>
                            <option value="hochschulstart">Hochschulstart</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>
                    <div class="ur-field">
                        <label>Dönem *</label>
                        <select name="semester" required>
                            <option value="WS">Kış (WS)</option>
                            <option value="SS">Yaz (SS)</option>
                            <option value="both">Her İki Dönem</option>
                        </select>
                    </div>
                    <div class="ur-field">
                        <label>Dil Gereksinimi</label>
                        <input type="text" name="language_requirement" placeholder="DSH-2 oder TestDaF 4x4">
                    </div>
                    <div class="ur-field">
                        <label>WS Son Başvuru (Ay / Gün)</label>
                        <div class="ur-date-pair">
                            <input type="number" name="deadline_month_ws" placeholder="Ay" min="1" max="12">
                            <input type="number" name="deadline_day_ws"   placeholder="Gün" min="1" max="31">
                        </div>
                    </div>
                    <div class="ur-field">
                        <label>SS Son Başvuru (Ay / Gün)</label>
                        <div class="ur-date-pair">
                            <input type="number" name="deadline_month_ss" placeholder="Ay" min="1" max="12">
                            <input type="number" name="deadline_day_ss"   placeholder="Gün" min="1" max="31">
                        </div>
                    </div>
                </div>
            </div>

            <div class="ur-section">
                <div class="ur-section-title">Zorunlu Belgeler * (en az 1)</div>
                <div class="cb-grid">
                    @foreach($docCatalog as $code => $doc)
                    <label class="cb-item" title="{{ $doc['label_tr'] }}">
                        <input type="checkbox" name="required_document_codes[]" value="{{ $code }}">
                        {{ $code }}
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="ur-section">
                <div class="ur-section-title">Tavsiye Edilen Belgeler</div>
                <div class="cb-grid">
                    @foreach($docCatalog as $code => $doc)
                    <label class="cb-item" title="{{ $doc['label_tr'] }}">
                        <input type="checkbox" name="recommended_document_codes[]" value="{{ $code }}">
                        {{ $code }}
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="ur-section">
                <div class="ur-section-title">Notlar</div>
                <div class="ur-field full">
                    <textarea name="notes" rows="3" placeholder="Önemli ek bilgiler..."></textarea>
                </div>
            </div>

            <div class="ur-actions">
                <button type="submit" class="ur-btn-save" style="background:#1e40af;">Harita Ekle</button>
            </div>
        </form>
    </div>
</details>

{{-- Belge Katalogu Referansı --}}
<details>
    <summary style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:var(--tx-xs);font-weight:600;color:var(--muted,#64748b);background:var(--surface,#fff);list-style:none;">
        📋 APP-* Belge Katalogu Referansı
    </summary>
    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:16px;margin-top:8px;">
        @foreach($docCategories as $catKey => $catLabel)
            @php $catDocs = collect($docCatalog)->filter(fn($d) => ($d['category'] ?? '') === $catKey); @endphp
            @if($catDocs->isNotEmpty())
            <div style="margin-bottom:12px;">
                <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">{{ $catLabel }}</div>
                <div class="doc-list">
                    @foreach($catDocs as $code => $doc)
                    <div class="doc-row">
                        <span class="doc-code">{{ $code }}</span>
                        <span>{{ $doc['label_tr'] }}</span>
                        @if(!empty($doc['notes']))
                            <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);">— {{ $doc['notes'] }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    </div>
</details>

@endsection

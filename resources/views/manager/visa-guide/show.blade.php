@extends('manager.layouts.app')

@section('title', 'Vize Rehberi · '.$guest->first_name.' '.$guest->last_name)
@section('page_title', '🛂 Almanya Vize Başvuru Rehberi (VIDEX)')
@section('page_subtitle', $guest->first_name.' '.$guest->last_name.' · '.($guest->email ?? '—').' — Auswärtiges Amt Auslandsportal\'ın 7 bölümünü birebir görür, her alanı tek tıkla kopyalarsın')

@push('head')
<style>
/* VIDEX tema klonu — mavi accent (#003c8f), tek sütun form, 7 sekmeli */
.vg-wrap { max-width:1100px; margin:0 auto; }
.vg-banner {
    background:linear-gradient(135deg,#fff,#eff6ff);
    border:1px solid #bfdbfe; border-radius:12px;
    padding:14px 18px; margin-bottom:18px;
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
}
.vg-banner-icon { font-size:32px; }
.vg-banner-title { font-weight:700; color:#1e3a8a; font-size:15px; }
.vg-banner-sub { font-size:12.5px; color:#1e40af; line-height:1.55; }
.vg-banner-link {
    margin-left:auto; padding:8px 14px;
    background:#003c8f; color:#fff; border-radius:8px; font-size:12px; font-weight:700;
    text-decoration:none;
}
.vg-banner-link:hover { background:#002966; }

/* Sekme bar */
.vg-tabs {
    display:flex; gap:0; border-bottom:2px solid var(--u-line);
    margin-bottom:24px; flex-wrap:wrap;
}
.vg-tab {
    padding:12px 16px; cursor:pointer; background:transparent; border:none;
    font-family:inherit; font-size:12px; font-weight:600; letter-spacing:.5px;
    color:var(--u-muted); text-transform:uppercase;
    border-bottom:3px solid transparent; margin-bottom:-2px;
}
.vg-tab.active {
    color:#003c8f; border-bottom-color:#003c8f;
}
.vg-tab small { display:block; font-size:9.5px; color:var(--u-muted); margin-top:3px; text-transform:none; letter-spacing:0; }
.vg-tab.active small { color:#1e3a8a; }
.vg-tab-warn { background:rgba(217,119,6,.15); color:rgb(180,83,9); border-radius:999px; padding:1px 7px; font-size:10px; font-weight:800; margin-left:6px; vertical-align:top; }

.vg-panel { display:none; }
.vg-panel.active { display:block; }

/* Field card */
.vg-field {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:10px;
    padding:14px 18px; margin-bottom:10px; transition:border-color .15s;
}
.vg-field.missing { border-left:3px solid #d97706; background:rgba(217,119,6,.04); }
.vg-field.editable { border-left:3px solid #003c8f; }
.vg-field-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
.vg-field-label {
    flex:1; min-width:240px;
}
.vg-field-label .label-de { font-size:13.5px; font-weight:700; color:var(--u-text); display:block; margin-bottom:2px; }
.vg-field-label .label-tr { font-size:11.5px; color:var(--u-muted); }
.vg-field-label .req { color:#003c8f; }
.vg-field-value {
    flex:1.4; min-width:300px;
    display:flex; align-items:center; gap:8px;
}
.vg-value-text {
    flex:1; padding:9px 14px; background:var(--u-bg); border:1px solid var(--u-line);
    border-radius:7px; font-family:monospace; font-size:13.5px; color:var(--u-text);
    word-break:break-word;
}
.vg-value-text.empty { color:#94a3b8; font-style:italic; font-family:inherit; }
.vg-copy-btn {
    padding:9px 14px; background:#003c8f; color:#fff; border:none; border-radius:7px;
    font-size:12px; font-weight:700; cursor:pointer; flex-shrink:0; min-width:90px;
    transition:background .15s;
}
.vg-copy-btn:hover { background:#002966; }
.vg-copy-btn:disabled { background:#cbd5e1; cursor:not-allowed; color:#475569; }
.vg-copy-btn.copied { background:#16a34a; }
.vg-field-hint {
    font-size:11px; color:var(--u-muted); margin-top:6px; line-height:1.5;
    background:var(--u-bg); padding:6px 10px; border-radius:5px;
    border-left:2px solid var(--u-line);
}
.vg-source-tag {
    display:inline-block; background:rgba(0,60,143,.1); color:#003c8f;
    padding:2px 8px; border-radius:999px; font-size:10px; font-weight:700;
    text-transform:uppercase; letter-spacing:.5px;
}

/* Editable input */
.vg-meta-input {
    flex:1; padding:8px 12px; border:1px solid var(--u-line); border-radius:7px;
    font-family:inherit; font-size:13px; background:#fff; color:var(--u-text); width:100%;
}
.vg-meta-input:focus { border-color:#003c8f; outline:none; box-shadow:0 0 0 3px rgba(0,60,143,.15); }
.vg-meta-save {
    padding:9px 16px; background:#003c8f; color:#fff; border:none; border-radius:7px;
    font-size:13px; font-weight:700; cursor:pointer;
}
.vg-meta-save:hover { background:#002966; }

/* Eksik alanlar paneli */
.vg-missing-panel {
    background:linear-gradient(135deg,rgba(217,119,6,.06),rgba(217,119,6,.02));
    border:1px solid rgba(217,119,6,.4); border-radius:12px;
    padding:18px 22px; margin-bottom:20px;
}
.vg-missing-panel h3 { margin:0 0 10px; color:rgb(120,53,15); font-size:14px; text-transform:uppercase; letter-spacing:.5px; }
.vg-missing-list { list-style:none; padding:0; margin:0 0 14px; max-height:240px; overflow:auto; }
.vg-missing-list li { padding:5px 0; font-size:13px; color:rgb(120,53,15); }
.vg-missing-list li::before { content:'⚠ '; color:#d97706; font-weight:800; }
.vg-missing-actions { display:flex; gap:10px; flex-wrap:wrap; }
.vg-missing-btn {
    padding:9px 16px; border:none; border-radius:8px; cursor:pointer;
    font-size:13px; font-weight:700; text-decoration:none;
}
.vg-missing-btn.mail { background:#d97706; color:#fff; }
.vg-missing-btn.mail:hover { background:rgb(180,83,9); }
.vg-missing-btn.doc-link { background:#003c8f; color:#fff; }
.vg-missing-btn.doc-link:hover { background:#002966; }
.vg-missing-btn:disabled { background:#cbd5e1; cursor:not-allowed; color:#64748b; }

.vg-tab-meta { font-size:11.5px; color:var(--u-muted); margin-bottom:14px; }

/* Manager edit form blok */
.vg-edit-block {
    background:rgba(0,60,143,.04); border:1px solid rgba(0,60,143,.25); border-radius:10px;
    padding:14px 16px; margin-bottom:16px;
}
.vg-edit-block h4 {
    margin:0 0 6px; font-size:13px; color:#003c8f; font-weight:800;
    text-transform:uppercase; letter-spacing:.4px;
}
.vg-edit-block .vg-edit-hint { font-size:11.5px; color:var(--u-muted); margin-bottom:10px; line-height:1.5; }
.vg-edit-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px; margin-bottom:10px; }
.vg-edit-field label { font-size:11px; color:var(--u-muted); font-weight:600; display:block; margin-bottom:4px; }
.vg-edit-actions { display:flex; gap:10px; align-items:center; justify-content:flex-end; padding-top:8px; border-top:1px dashed var(--u-line); }
</style>
@endpush

@section('content')
@php
    $vertretungOpts    = \App\Services\VisaFieldMapperService::VERTRETUNG_OPTIONS;
    $geschlechtOpts    = \App\Services\VisaFieldMapperService::GESCHLECHT_OPTIONS;
    $familienstandOpts = \App\Services\VisaFieldMapperService::FAMILIENSTAND_OPTIONS;
    $zweckOpts         = \App\Services\VisaFieldMapperService::ZWECK_OPTIONS;
    $luTypeOpts        = \App\Services\VisaFieldMapperService::LEBENSUNTERHALT_OPTIONS;
    $unterbringungOpts = \App\Services\VisaFieldMapperService::UNTERBRINGUNG_OPTIONS;
    $refTypeOpts       = \App\Services\VisaFieldMapperService::REFERENCE_TYPE_OPTIONS;
    $jaNein = ['Ja' => 'Ja (Evet)', 'Nein' => 'Nein (Hayır)'];
    $metaArr = is_array($guest->application_meta) ? $guest->application_meta : [];
    $vMeta = is_array($metaArr['visa'] ?? null) ? $metaArr['visa'] : [];
    $vGet = fn(string $k, ?string $default = null) => ($vMeta[$k] ?? null) !== null && ($vMeta[$k] ?? null) !== '' ? $vMeta[$k] : $default;
@endphp

<div class="vg-wrap">

    @if(session('success'))<div style="background:rgba(22,163,74,.08); color:#15803d; border:1px solid rgba(22,163,74,.3); padding:10px 14px; border-radius:10px; margin-bottom:14px;">✅ {{ session('success') }}</div>@endif
    @if($errors->any())
        <div style="background:rgba(220,38,38,.08); color:rgb(185,28,28); border:1px solid rgba(220,38,38,.3); padding:10px 14px; border-radius:10px; margin-bottom:14px;">
            @foreach($errors->all() as $e) ⚠ {{ $e }}<br> @endforeach
        </div>
    @endif

    <div class="vg-banner">
        <div class="vg-banner-icon">🛂</div>
        <div>
            <div class="vg-banner-title">VIDEX (Visa Daten Explorer) — 7 Bölüm Doldurma Rehberi</div>
            <div class="vg-banner-sub">Aşağıdaki 7 sekme Auswärtiges Amt Auslandsportal'ın birebir kopyasıdır. Her alanın yanındaki <strong>"Kopyala"</strong> ile değer panoya kopyalanır → portal'a yapıştır.</div>
        </div>
        <a href="https://auslandsportal.diplo.de" target="_blank" rel="noopener" class="vg-banner-link">Vize Portali Aç →</a>
    </div>

    @if(count($missing) > 0)
        <div class="vg-missing-panel">
            <h3>⚠ {{ count($missing) }} eksik alan var — başvuruya başlamadan önce tamamla</h3>
            <ul class="vg-missing-list">
                @foreach($missing as $m)
                    <li><strong>{{ $m['label'] }}</strong> ({{ $m['label_tr'] }}) — <em>{{ $m['tab'] }}</em></li>
                @endforeach
            </ul>
            <div class="vg-missing-actions">
                <button type="button" class="vg-missing-btn mail" onclick="document.getElementById('vg-mail-modal').showModal()">
                    📧 Öğrenciye Mail Gönder ({{ count($missing) }} alan)
                </button>
                @if($canRequestDocs)
                    {{-- Vize Rehberi student aşamasında — student belge talep route'u kullanılır --}}
                    @if($studentId)
                        <a class="vg-missing-btn doc-link" href="{{ route('manager.student.document-tokens.index', $studentId) }}">
                            🔗 Belge Talep Linki Oluştur (Premium)
                        </a>
                    @else
                        {{-- Legacy fallback: guest aşamasında çağrı (geriye uyumluluk) --}}
                        <a class="vg-missing-btn doc-link" href="{{ route('manager.guest.document-tokens.index', $guest) }}">
                            🔗 Belge Talep Linki Oluştur (Premium)
                        </a>
                    @endif
                @else
                    <button type="button" class="vg-missing-btn doc-link" disabled title="Bu yetki bayide aktif değil">
                        🔒 Belge Talep Linki (Premium)
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- ═══ TAB BAR ═══ --}}
    <div class="vg-tabs">
        @foreach($tabs as $tabKey => $tab)
            @php
                $missingInTab = collect($tab['fields'])->where('missing', true)->where('required', true)->count();
            @endphp
            <button type="button" class="vg-tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $tabKey }}">
                {{ $tab['title'] }}
                @if($missingInTab > 0)
                    <span class="vg-tab-warn">{{ $missingInTab }}</span>
                @endif
                <small>{{ $tab['title_tr'] }}</small>
            </button>
        @endforeach
    </div>

    {{-- ═══ PANELS ═══ --}}
    @foreach($tabs as $tabKey => $tab)
        <div class="vg-panel {{ $loop->first ? 'active' : '' }}" data-panel="{{ $tabKey }}">
            <div class="vg-tab-meta">{{ count($tab['fields']) }} alan · VIDEX bölümü: <strong>{{ $tab['title'] }}</strong></div>

            {{-- Manager girişi alan blokları (tab başına) --}}
            @if($tabKey === 'vertretung')
                <form method="POST" action="{{ route('manager.visa-guide.save-meta', $guest) }}">
                    @csrf
                    <div class="vg-edit-block">
                        <h4>⚙ Temsili Tipi</h4>
                        <div class="vg-edit-hint">Default: Ich besitze das Konto und stelle den Visaantrag für mich (öğrenci kendisi başvuruyor).</div>
                        <div class="vg-edit-grid">
                            <div class="vg-edit-field">
                                <label>Vertretung</label>
                                <select name="visa[vertretung]" class="vg-meta-input">
                                    @foreach($vertretungOpts as $key => $label)
                                        <option value="{{ $key }}" {{ ($vMeta['vertretung'] ?? 'self') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="vg-edit-actions"><button type="submit" class="vg-meta-save">💾 Kaydet</button></div>
                    </div>
                </form>
            @endif

            @if($tabKey === 'person')
                <form method="POST" action="{{ route('manager.visa-guide.save-meta', $guest) }}">
                    @csrf
                    <div class="vg-edit-block">
                        <h4>⚙ Kişisel Detay (manuel)</h4>
                        <div class="vg-edit-hint">Geschlecht / Familienstand / Çocuk durumu / Meslek + ebeveyn bilgileri.</div>
                        <div class="vg-edit-grid">
                            <div class="vg-edit-field">
                                <label>Geschlecht</label>
                                <select name="visa[geschlecht]" class="vg-meta-input">
                                    <option value="">— Auto (öğrenci datası) —</option>
                                    @foreach($geschlechtOpts as $key => $label)
                                        <option value="{{ $key }}" {{ ($vMeta['geschlecht'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field">
                                <label>Familienstand</label>
                                <select name="visa[familienstand]" class="vg-meta-input">
                                    <option value="">—</option>
                                    @foreach($familienstandOpts as $key => $label)
                                        <option value="{{ $key }}" {{ ($vMeta['familienstand'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field">
                                <label>Geburtsname (varsa)</label>
                                <input type="text" name="visa[geburtsname]" class="vg-meta-input" value="{{ $vGet('geburtsname', '') }}" placeholder="Evlilik öncesi soyad">
                            </div>
                            <div class="vg-edit-field">
                                <label>Frühere Staatsang. (varsa)</label>
                                <input type="text" name="visa[frueher_staatsang]" class="vg-meta-input" value="{{ $vGet('frueher_staatsang', '') }}">
                            </div>
                            <div class="vg-edit-field">
                                <label>Çocuğunuz var mı?</label>
                                <select name="visa[has_children]" class="vg-meta-input">
                                    @foreach($jaNein as $k => $l)
                                        <option value="{{ $k }}" {{ ($vMeta['has_children'] ?? 'Nein') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field">
                                <label>Erlernter Beruf</label>
                                <input type="text" name="visa[beruf_erlernt]" class="vg-meta-input" value="{{ $vGet('beruf_erlernt', 'Student/in, Praktikant/-in') }}">
                            </div>
                            <div class="vg-edit-field">
                                <label>Aktuelle Tätigkeit (varsa)</label>
                                <input type="text" name="visa[beruf_aktuell]" class="vg-meta-input" value="{{ $vGet('beruf_aktuell', '') }}">
                            </div>
                        </div>

                        <div style="margin-top:14px; padding-top:14px; border-top:1px dashed var(--u-line);">
                            <div style="font-size:12px; font-weight:700; color:#003c8f; margin-bottom:8px;">👨 Baba Bilgileri</div>
                            <div class="vg-edit-grid">
                                <div class="vg-edit-field"><label>Familienname</label><input type="text" name="visa[vater_nachname]" class="vg-meta-input" value="{{ $vGet('vater_nachname', '') }}"></div>
                                <div class="vg-edit-field"><label>Vorname(n)</label><input type="text" name="visa[vater_vorname]" class="vg-meta-input" value="{{ $vGet('vater_vorname', '') }}"></div>
                                <div class="vg-edit-field"><label>Staatsangehörigkeit</label><input type="text" name="visa[vater_staatsang]" class="vg-meta-input" value="{{ $vGet('vater_staatsang', 'Türkei') }}"></div>
                                <div class="vg-edit-field"><label>Geburtsdatum (TT.MM.JJJJ)</label><input type="text" name="visa[vater_geburtsdatum]" class="vg-meta-input" value="{{ $vGet('vater_geburtsdatum', '') }}" placeholder="01.05.1970"></div>
                                <div class="vg-edit-field"><label>Geburtsort</label><input type="text" name="visa[vater_geburtsort]" class="vg-meta-input" value="{{ $vGet('vater_geburtsort', '') }}"></div>
                                <div class="vg-edit-field"><label>Wohnort</label><input type="text" name="visa[vater_wohnort]" class="vg-meta-input" value="{{ $vGet('vater_wohnort', '') }}"></div>
                            </div>
                        </div>

                        <div style="margin-top:14px; padding-top:14px; border-top:1px dashed var(--u-line);">
                            <div style="font-size:12px; font-weight:700; color:#003c8f; margin-bottom:8px;">👩 Anne Bilgileri</div>
                            <div class="vg-edit-grid">
                                <div class="vg-edit-field"><label>Familienname</label><input type="text" name="visa[mutter_nachname]" class="vg-meta-input" value="{{ $vGet('mutter_nachname', '') }}"></div>
                                <div class="vg-edit-field"><label>Vorname(n)</label><input type="text" name="visa[mutter_vorname]" class="vg-meta-input" value="{{ $vGet('mutter_vorname', '') }}"></div>
                                <div class="vg-edit-field"><label>Staatsangehörigkeit</label><input type="text" name="visa[mutter_staatsang]" class="vg-meta-input" value="{{ $vGet('mutter_staatsang', 'Türkei') }}"></div>
                                <div class="vg-edit-field"><label>Geburtsdatum (TT.MM.JJJJ)</label><input type="text" name="visa[mutter_geburtsdatum]" class="vg-meta-input" value="{{ $vGet('mutter_geburtsdatum', '') }}" placeholder="01.05.1972"></div>
                                <div class="vg-edit-field"><label>Geburtsort</label><input type="text" name="visa[mutter_geburtsort]" class="vg-meta-input" value="{{ $vGet('mutter_geburtsort', '') }}"></div>
                                <div class="vg-edit-field"><label>Wohnort</label><input type="text" name="visa[mutter_wohnort]" class="vg-meta-input" value="{{ $vGet('mutter_wohnort', '') }}"></div>
                            </div>
                        </div>

                        <div class="vg-edit-actions"><button type="submit" class="vg-meta-save">💾 Tüm Kişisel Bilgileri Kaydet</button></div>
                    </div>
                </form>
            @endif

            @if($tabKey === 'kontakt')
                <form method="POST" action="{{ route('manager.visa-guide.save-meta', $guest) }}">
                    @csrf
                    <div class="vg-edit-block">
                        <h4>⚙ İletişim Detay (manuel)</h4>
                        <div class="vg-edit-hint">Hausnummer ve "Başka ülkede ikamet" bilgisi.</div>
                        <div class="vg-edit-grid">
                            <div class="vg-edit-field">
                                <label>Hausnummer</label>
                                <input type="text" name="visa[hausnummer]" class="vg-meta-input" value="{{ $vGet('hausnummer', '') }}" placeholder="örn: 5">
                            </div>
                            <div class="vg-edit-field">
                                <label>Başka ülkede ikamet?</label>
                                <select name="visa[wohnsitz_anderes_land]" class="vg-meta-input">
                                    @foreach($jaNein as $k => $l)
                                        <option value="{{ $k }}" {{ ($vMeta['wohnsitz_anderes_land'] ?? 'Nein') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="vg-edit-actions"><button type="submit" class="vg-meta-save">💾 Kaydet</button></div>
                    </div>
                </form>
            @endif

            @if($tabKey === 'ausweis')
                <form method="POST" action="{{ route('manager.visa-guide.save-meta', $guest) }}">
                    @csrf
                    <div class="vg-edit-block">
                        <h4>⚙ Pasaport Detay (manuel)</h4>
                        <div class="vg-edit-hint">"Ausgestellt von" — pasaportu veren kaymakamlık ilçesi (örn: ATASEHIR).</div>
                        <div class="vg-edit-grid">
                            <div class="vg-edit-field">
                                <label>Ausgestellt von</label>
                                <input type="text" name="visa[reisedoc_ausgestellt_von]" class="vg-meta-input" value="{{ $vGet('reisedoc_ausgestellt_von', '') }}" placeholder="ATASEHIR">
                            </div>
                        </div>
                        <div class="vg-edit-actions"><button type="submit" class="vg-meta-save">💾 Kaydet</button></div>
                    </div>
                </form>
            @endif

            @if($tabKey === 'reise')
                <form method="POST" action="{{ route('manager.visa-guide.save-meta', $guest) }}">
                    @csrf
                    <div class="vg-edit-block">
                        <h4>⚙ Seyahat Detay (manuel)</h4>
                        <div class="vg-edit-hint">Zweck (amaç), tarih aralığı, yan iş niyeti.</div>
                        <div class="vg-edit-grid">
                            <div class="vg-edit-field">
                                <label>Zweck</label>
                                <select name="visa[travel_zweck]" class="vg-meta-input">
                                    <option value="">—</option>
                                    @foreach($zweckOpts as $key => $label)
                                        <option value="{{ $key }}" {{ ($vMeta['travel_zweck'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field">
                                <label>Yan iş niyeti (varsa)</label>
                                <input type="text" name="visa[travel_erwerb]" class="vg-meta-input" value="{{ $vGet('travel_erwerb', '') }}">
                            </div>
                            <div class="vg-edit-field">
                                <label>Von (TT.MM.JJJJ)</label>
                                <input type="text" name="visa[travel_von]" class="vg-meta-input" value="{{ $vGet('travel_von', '') }}" placeholder="01.10.2026">
                            </div>
                            <div class="vg-edit-field">
                                <label>Bis (TT.MM.JJJJ)</label>
                                <input type="text" name="visa[travel_bis]" class="vg-meta-input" value="{{ $vGet('travel_bis', '') }}" placeholder="01.10.2027">
                            </div>
                            <div class="vg-edit-field">
                                <label>12 ay'dan uzun mu?</label>
                                <select name="visa[travel_ueber12]" class="vg-meta-input">
                                    @foreach($jaNein as $k => $l)
                                        <option value="{{ $k }}" {{ ($vMeta['travel_ueber12'] ?? 'Ja') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="vg-edit-actions"><button type="submit" class="vg-meta-save">💾 Kaydet</button></div>
                    </div>
                </form>
            @endif

            @if($tabKey === 'referenz')
                <form method="POST" action="{{ route('manager.visa-guide.save-meta', $guest) }}">
                    @csrf
                    <div class="vg-edit-block">
                        <h4>⚙ Referans Bilgileri (manuel — dil okulu / üniversite)</h4>
                        <div class="vg-edit-hint">Kabul mektubundaki bilgileri yaz. Ansprechperson = kabul mektubunu imzalayan kişi.</div>
                        <div class="vg-edit-grid">
                            <div class="vg-edit-field">
                                <label>Art der Referenz</label>
                                <select name="visa[reference_type]" class="vg-meta-input">
                                    @foreach($refTypeOpts as $key => $label)
                                        <option value="{{ $key }}" {{ ($vMeta['reference_type'] ?? 'Bildungseinrichtung') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field"><label>Name</label><input type="text" name="visa[reference_name]" class="vg-meta-input" value="{{ $vGet('reference_name', '') }}" placeholder="örn: INTERKULTURELLES BILDUNG ZENTRUM"></div>
                            <div class="vg-edit-field"><label>Sitz: Ort</label><input type="text" name="visa[reference_city]" class="vg-meta-input" value="{{ $vGet('reference_city', '') }}" placeholder="Essen"></div>
                            <div class="vg-edit-field"><label>Aufgabenstellung</label><input type="text" name="visa[reference_aufgabe]" class="vg-meta-input" value="{{ $vGet('reference_aufgabe', '') }}" placeholder="Sprachschule"></div>
                            <div class="vg-edit-field"><label>Register Name</label><input type="text" name="visa[reference_register_name]" class="vg-meta-input" value="{{ $vGet('reference_register_name', '') }}" placeholder="Handelsregister"></div>
                            <div class="vg-edit-field"><label>Register-Ort</label><input type="text" name="visa[reference_register_ort]" class="vg-meta-input" value="{{ $vGet('reference_register_ort', '') }}"></div>
                            <div class="vg-edit-field"><label>Registernummer</label><input type="text" name="visa[reference_register_nr]" class="vg-meta-input" value="{{ $vGet('reference_register_nr', '') }}"></div>
                        </div>
                        <div style="margin-top:14px; padding-top:14px; border-top:1px dashed var(--u-line);">
                            <div style="font-size:12px; font-weight:700; color:#003c8f; margin-bottom:8px;">📞 Ansprechperson</div>
                            <div class="vg-edit-grid">
                                <div class="vg-edit-field"><label>Familienname</label><input type="text" name="visa[reference_contact_lastname]" class="vg-meta-input" value="{{ $vGet('reference_contact_lastname', '') }}"></div>
                                <div class="vg-edit-field"><label>Vorname(n)</label><input type="text" name="visa[reference_contact_firstname]" class="vg-meta-input" value="{{ $vGet('reference_contact_firstname', '') }}"></div>
                                <div class="vg-edit-field"><label>Adresse (tam)</label><input type="text" name="visa[reference_contact_address]" class="vg-meta-input" value="{{ $vGet('reference_contact_address', '') }}"></div>
                                <div class="vg-edit-field"><label>Telefon</label><input type="text" name="visa[reference_contact_phone]" class="vg-meta-input" value="{{ $vGet('reference_contact_phone', '') }}"></div>
                                <div class="vg-edit-field"><label>E-Mail</label><input type="email" name="visa[reference_contact_email]" class="vg-meta-input" value="{{ $vGet('reference_contact_email', '') }}"></div>
                            </div>
                        </div>
                        <div class="vg-edit-actions"><button type="submit" class="vg-meta-save">💾 Referans Bilgilerini Kaydet</button></div>
                    </div>
                </form>
            @endif

            @if($tabKey === 'lebensunterhalt')
                <form method="POST" action="{{ route('manager.visa-guide.save-meta', $guest) }}">
                    @csrf
                    <div class="vg-edit-block">
                        <h4>⚙ Geçim + Konaklama + Erklärung (manuel)</h4>
                        <div class="vg-edit-hint">Sperrkonto en yaygın seçim. Erklärung 3 Nein/Ja sorusu — genelde Nein.</div>
                        <div class="vg-edit-grid">
                            <div class="vg-edit-field">
                                <label>Lebensunterhalt</label>
                                <select name="visa[lebensunterhalt_type]" class="vg-meta-input">
                                    @foreach($luTypeOpts as $key => $label)
                                        <option value="{{ $key }}" {{ ($vMeta['lebensunterhalt_type'] ?? 'Sperrkonto') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field">
                                <label>Verpflichtungserklärung?</label>
                                <select name="visa[lebensunterhalt_verpflicht]" class="vg-meta-input">
                                    @foreach($jaNein as $k => $l)
                                        <option value="{{ $k }}" {{ ($vMeta['lebensunterhalt_verpflicht'] ?? 'Nein') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field"><label>Aufenthaltsort: Straße + Nr</label><input type="text" name="visa[aufenthalt_strasse]" class="vg-meta-input" value="{{ $vGet('aufenthalt_strasse', '') }}"></div>
                            <div class="vg-edit-field"><label>Aufenthaltsort: PLZ + Ort</label><input type="text" name="visa[aufenthalt_plz_ort]" class="vg-meta-input" value="{{ $vGet('aufenthalt_plz_ort', '') }}" placeholder="60435 FRANKFURT A.M"></div>
                            <div class="vg-edit-field">
                                <label>Konaklama Tipi</label>
                                <select name="visa[unterbringung]" class="vg-meta-input">
                                    @foreach($unterbringungOpts as $key => $label)
                                        <option value="{{ $key }}" {{ ($vMeta['unterbringung'] ?? 'Einzelzimmer') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field"><label>Konaklama Notu</label><input type="text" name="visa[unterbringung_note]" class="vg-meta-input" value="{{ $vGet('unterbringung_note', '') }}" placeholder="WOHNUNG GEHÖRT MEINER TANTE"></div>
                            <div class="vg-edit-field">
                                <label>TR İkametgah Devam?</label>
                                <select name="visa[staendig_ausland]" class="vg-meta-input">
                                    @foreach($jaNein as $k => $l)
                                        <option value="{{ $k }}" {{ ($vMeta['staendig_ausland'] ?? 'Ja') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field">
                                <label>Aile de Geliyor mu?</label>
                                <select name="visa[familie_einreise]" class="vg-meta-input">
                                    @foreach($jaNein as $k => $l)
                                        <option value="{{ $k }}" {{ ($vMeta['familie_einreise'] ?? 'Nein') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field">
                                <label>Krankenversicherung Var?</label>
                                <select name="visa[krankenversicherung]" class="vg-meta-input">
                                    @foreach($jaNein as $k => $l)
                                        <option value="{{ $k }}" {{ ($vMeta['krankenversicherung'] ?? 'Ja') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vg-edit-field">
                                <label>Daha Önce Almanya'da?</label>
                                <select name="visa[fruehere_aufenthalte]" class="vg-meta-input">
                                    @foreach($jaNein as $k => $l)
                                        <option value="{{ $k }}" {{ ($vMeta['fruehere_aufenthalte'] ?? 'Nein') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div style="margin-top:14px; padding-top:14px; border-top:1px dashed var(--u-line);">
                            <div style="font-size:12px; font-weight:700; color:#003c8f; margin-bottom:8px;">📋 Erklärung — 3 Zorunlu Soru</div>
                            <div class="vg-edit-grid">
                                <div class="vg-edit-field">
                                    <label>Adli Sicil Var Mı?</label>
                                    <select name="visa[erkl_vorbestraft]" class="vg-meta-input">
                                        @foreach($jaNein as $k => $l)
                                            <option value="{{ $k }}" {{ ($vMeta['erkl_vorbestraft'] ?? 'Nein') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="vg-edit-field">
                                    <label>Sınır Dışı / Ret Edildiniz mi?</label>
                                    <select name="visa[erkl_abgeschoben]" class="vg-meta-input">
                                        @foreach($jaNein as $k => $l)
                                            <option value="{{ $k }}" {{ ($vMeta['erkl_abgeschoben'] ?? 'Nein') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="vg-edit-field">
                                    <label>Bulaşıcı Hastalık (Polio/Cholera vb.)?</label>
                                    <select name="visa[erkl_krankheiten]" class="vg-meta-input">
                                        @foreach($jaNein as $k => $l)
                                            <option value="{{ $k }}" {{ ($vMeta['erkl_krankheiten'] ?? 'Nein') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="vg-edit-actions"><button type="submit" class="vg-meta-save">💾 Geçim + Konaklama + Erklärung Kaydet</button></div>
                    </div>
                </form>
            @endif

            {{-- Field listesi (read + copy) --}}
            @foreach($tab['fields'] as $f)
                <div class="vg-field {{ $f['missing'] ? 'missing' : '' }}">
                    <div class="vg-field-row">
                        <div class="vg-field-label">
                            <span class="label-de">{{ $f['label'] }} @if($f['required'])<span class="req">*</span>@endif</span>
                            <span class="label-tr">{{ $f['label_tr'] }}</span>
                            @if($f['format'])
                                <span class="vg-source-tag" style="margin-left:6px;">format: {{ $f['format'] }}</span>
                            @endif
                        </div>
                        <div class="vg-field-value">
                            @if($f['value'] !== null)
                                <span class="vg-value-text">{{ $f['value'] }}</span>
                                <button type="button" class="vg-copy-btn" data-copy="{{ $f['value'] }}">📋 Kopyala</button>
                            @else
                                <span class="vg-value-text empty">— Eksik (öğrenci/manager doldurmamış)</span>
                                <button type="button" class="vg-copy-btn" disabled>—</button>
                            @endif
                        </div>
                    </div>
                    @if($f['hint'])
                        <div class="vg-field-hint">💡 {{ $f['hint'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
</div>

{{-- Eksik alan mail modal --}}
<dialog id="vg-mail-modal" style="border:none; border-radius:14px; padding:0; max-width:560px; width:92%; box-shadow:0 24px 60px rgba(0,0,0,.3);">
    <form method="POST" action="{{ route('manager.visa-guide.request-missing', $guest) }}" style="padding:24px;">
        @csrf
        <h2 style="margin:0 0 8px; font-size:20px;">📧 Vize Eksik Alan Maili</h2>
        <p style="font-size:13px; color:var(--u-muted); margin:0 0 18px; line-height:1.5;">
            <strong>{{ $guest->first_name }} {{ $guest->last_name }}</strong> ({{ $guest->email }}) adresine gönderilecek.
        </p>
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 14px; font-size:12px; color:#1e3a8a; margin-bottom:14px; line-height:1.55; max-height:240px; overflow:auto;">
            <strong>📋 Mail içeriğindeki alanlar:</strong>
            <ul style="margin:6px 0 0 18px; padding:0;">
                @foreach($missing as $m)
                    <li><input type="checkbox" name="fields[]" value="{{ $m['label'] }} ({{ $m['label_tr'] }})" checked style="margin-right:6px;"> {{ $m['label_tr'] }}</li>
                @endforeach
            </ul>
        </div>
        <div style="margin-bottom:16px;">
            <label style="font-size:12px; font-weight:600; color:var(--u-text); display:block; margin-bottom:4px;">Ek not (opsiyonel)</label>
            <textarea name="note" rows="2" maxlength="1000" style="width:100%; padding:8px 12px; border:1px solid var(--u-line); border-radius:7px; font-family:inherit; font-size:13px;" placeholder="Öğrenciye iletmek istediğin özel bir not..."></textarea>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" id="vg-mail-cancel" style="padding:9px 18px; background:var(--u-bg); border:1px solid var(--u-line); border-radius:7px; cursor:pointer; font-weight:600;">İptal</button>
            <button type="submit" style="padding:9px 18px; background:#003c8f; color:#fff; border:none; border-radius:7px; cursor:pointer; font-weight:700;">📤 Mail Gönder</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Tab switch
document.querySelectorAll('.vg-tab').forEach(function(t){
    t.addEventListener('click', function(){
        var key = t.getAttribute('data-tab');
        document.querySelectorAll('.vg-tab').forEach(function(tt){ tt.classList.toggle('active', tt === t); });
        document.querySelectorAll('.vg-panel').forEach(function(p){ p.classList.toggle('active', p.getAttribute('data-panel') === key); });
    });
});

// Copy to clipboard
document.querySelectorAll('.vg-copy-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var val = btn.getAttribute('data-copy');
        if (!val) return;
        var done = function(){
            var orig = btn.textContent;
            btn.textContent = '✓ Kopyalandı!';
            btn.classList.add('copied');
            setTimeout(function(){ btn.textContent = orig; btn.classList.remove('copied'); }, 1300);
        };
        if (navigator.clipboard) {
            navigator.clipboard.writeText(val).then(done).catch(function(){
                var ta = document.createElement('textarea'); ta.value = val; document.body.appendChild(ta);
                ta.select(); document.execCommand('copy'); document.body.removeChild(ta); done();
            });
        } else {
            var ta = document.createElement('textarea'); ta.value = val; document.body.appendChild(ta);
            ta.select(); document.execCommand('copy'); document.body.removeChild(ta); done();
        }
    });
});

// Mail modal cancel
var __vgMailCancel = document.getElementById('vg-mail-cancel');
if (__vgMailCancel) {
    __vgMailCancel.addEventListener('click', function(){
        document.getElementById('vg-mail-modal').close();
    });
}
</script>
@endpush

@endsection

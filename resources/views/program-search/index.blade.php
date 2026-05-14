@php
    // Layout seçimi: public mode (guest) → uni-match layout, internal → role layout
    $publicMode = $publicMode ?? false;
    if ($publicMode) {
        $layout = 'uni-match.layout';
    } else {
        $role = auth()->user()->role ?? 'manager';
        $layout = match ($role) {
            'senior', 'mentor' => 'senior.layouts.app',
            default            => 'manager.layouts.app',
        };
    }

    // Aktif filtre sayısı (advanced section sayacı için)
    $advancedFilterCount = 0;
    if (!empty($filters['university']))      $advancedFilterCount++;
    if (!empty($filters['big_city']))        $advancedFilterCount++;
    if (!empty($filters['small_city']))      $advancedFilterCount++;
    if (!empty($filters['top_uni']))         $advancedFilterCount++;
    if (!empty($filters['subject']))         $advancedFilterCount++;
    if (!empty($filters['tuition_max']))     $advancedFilterCount++;
    if (!empty($filters['fields']))          $advancedFilterCount += count($filters['fields']);

    $hasAdvanced = $advancedFilterCount > 0;
@endphp

@extends($layout)

@section('title', ($publicMode ? 'Program Kataloğu' : 'Program Arama') . ' — ' . config('brand.name', 'MentorDE'))
@section('page_title', $publicMode ? 'Almanya Program Kataloğu' : 'Program Arama')
@section('page_subtitle', $publicMode ? 'Bölüm, şehir, dil ile filtrele' : 'Wizard bypass — doğrudan filtre')

@if($publicMode)
@push('head')
<script nonce="{{ $cspNonce ?? '' }}">
    document.documentElement.classList.add('sb-catalog-active');
</script>
@endpush
@endif

@section('content')
<style>
/* ════════════════════════════════════════════════════════════
   Hochschulkompass-tarzı arama: progressive disclosure + 2-tier
   ════════════════════════════════════════════════════════════ */

:root {
    --ps-purple: #7e58bf;
    --ps-purple-dark: #5b2e91;
    --ps-line: var(--u-line, #e2e8f0);
    --ps-text: var(--u-text, #1a1a1a);
    --ps-muted: var(--u-muted, #64748b);
    --ps-bg: var(--u-bg, #f8fafc);
    --ps-card: var(--u-card, #fff);
}
@if($publicMode)
:root { --ps-primary: var(--ps-purple); }
@else
:root { --ps-primary: var(--ps-purple-dark); }
@endif

.ps-wrap { max-width: 1200px; margin: 0 auto; padding: 0 4px; }

/* ═══ Hero — search + 3 ana select + 2 CTA ═══ */
.ps-hero { background: var(--ps-card); border: 1px solid var(--ps-line); border-radius: 14px; padding: 22px 24px; margin-bottom: 18px; box-shadow: 0 2px 8px rgba(15,23,42,.04); }
.ps-hero-search { position: relative; margin-bottom: 12px; }
.ps-hero-search input { width: 100%; box-sizing: border-box; padding: 14px 18px 14px 46px; font-size: 15px; border: 1.5px solid var(--ps-line); border-radius: 10px; background: var(--ps-card); color: var(--ps-text); outline: none; font-family: inherit; transition: border-color .15s, box-shadow .15s; }
.ps-hero-search input:focus { border-color: var(--ps-primary); box-shadow: 0 0 0 4px rgba(126,88,191,.1); }
.ps-hero-search::before { content: "🔍"; position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 16px; pointer-events: none; opacity: .55; }

.ps-hero-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px; }
.ps-hero-row .ps-select { position: relative; }
.ps-hero-row select { width: 100%; box-sizing: border-box; padding: 11px 14px; font-size: 13.5px; border: 1.5px solid var(--ps-line); border-radius: 9px; background: var(--ps-card); color: var(--ps-text); cursor: pointer; appearance: none; -webkit-appearance: none; padding-right: 36px; font-family: inherit; outline: none; transition: border-color .15s; }
.ps-hero-row select:focus { border-color: var(--ps-primary); }
.ps-hero-row .ps-select::after { content: "▾"; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--ps-muted); font-size: 11px; }

/* CTA satırı — büyük sonuç butonu + secondary Top 10 */
.ps-hero-cta { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.ps-hero-cta-primary { flex: 1 1 auto; min-width: 240px; padding: 14px 22px; background: var(--ps-primary); color: #fff; border: none; border-radius: 10px; font-size: 14.5px; font-weight: 800; cursor: pointer; transition: transform .12s, box-shadow .12s; letter-spacing: .02em; text-transform: uppercase; }
.ps-hero-cta-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(126,88,191,.25); }
.ps-hero-cta-primary strong { font-size: 16px; }
.ps-hero-cta-secondary { padding: 12px 18px; background: rgba(245,158,11,.1); color: #b45309; border: 1.5px solid rgba(245,158,11,.5); border-radius: 10px; font-size: 12.5px; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; transition: all .12s; }
.ps-hero-cta-secondary:hover { background: rgba(245,158,11,.18); border-color: #d97706; }
.ps-hero-cta-secondary.is-active { background: #d97706; color: #fff; border-color: #d97706; }

/* ═══ Advanced toggle ═══ */
.ps-advanced-toggle { display: inline-flex; align-items: center; gap: 6px; margin-top: 14px; padding: 6px 10px; background: transparent; color: var(--ps-primary); border: none; cursor: pointer; font-size: 13px; font-weight: 600; font-family: inherit; }
.ps-advanced-toggle:hover { text-decoration: underline; }
.ps-advanced-toggle .ps-badge { padding: 1px 7px; background: var(--ps-primary); color: #fff; border-radius: 999px; font-size: 10px; font-weight: 800; }
details.ps-advanced > summary { list-style: none; cursor: pointer; }
details.ps-advanced > summary::-webkit-details-marker { display: none; }
details.ps-advanced[open] .ps-advanced-toggle-chevron { transform: rotate(180deg); }
.ps-advanced-toggle-chevron { display: inline-block; transition: transform .2s; }

/* ═══ Advanced filters ═══ */
details.ps-advanced { margin-top: 0; }
.ps-advanced-body { margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--ps-line); }
.ps-advanced-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.ps-group { background: var(--ps-bg); border: 1px solid var(--ps-line); border-radius: 10px; padding: 12px 14px; }
.ps-group > summary { list-style: none; cursor: pointer; font-size: 11.5px; font-weight: 800; color: var(--ps-primary); text-transform: uppercase; letter-spacing: .06em; padding-bottom: 8px; margin-bottom: 8px; border-bottom: 1px dashed var(--ps-line); display: flex; align-items: center; justify-content: space-between; }
.ps-group > summary::-webkit-details-marker { display: none; }
.ps-group[open] .ps-group-chev { transform: rotate(180deg); }
.ps-group-chev { transition: transform .2s; font-size: 10px; }
.ps-group-body { display: flex; flex-direction: column; gap: 9px; }
.ps-field label { display: block; font-size: 10.5px; font-weight: 700; color: var(--ps-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
.ps-field input, .ps-field select { width: 100%; box-sizing: border-box; padding: 8px 10px; font-size: 13px; border: 1px solid var(--ps-line); border-radius: 7px; background: var(--ps-card); color: var(--ps-text); outline: none; font-family: inherit; }
.ps-field input:focus, .ps-field select:focus { border-color: var(--ps-primary); }

.ps-fieldlist { max-height: 240px; overflow-y: auto; display: flex; flex-direction: column; gap: 1px; margin-top: 6px; padding: 4px; background: var(--ps-card); border: 1px solid var(--ps-line); border-radius: 7px; }
.ps-fieldlist label { display: flex; align-items: center; gap: 8px; padding: 5px 8px; border-radius: 5px; font-size: 12px; cursor: pointer; transition: background .1s; margin: 0; text-transform: none; letter-spacing: 0; font-weight: 500; color: var(--ps-text); }
.ps-fieldlist label:hover { background: var(--ps-bg); }
.ps-fieldlist label input { width: auto; padding: 0; margin: 0; accent-color: var(--ps-primary); flex-shrink: 0; }
.ps-fieldlist label.is-active { background: rgba(126,88,191,.08); color: var(--ps-primary); font-weight: 600; }
.ps-fieldlist .ps-fl-count { margin-left: auto; font-size: 10.5px; color: var(--ps-muted); font-weight: 600; flex-shrink: 0; }

.ps-advanced-actions { display: flex; gap: 8px; margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--ps-line); }
.ps-btn-apply { padding: 9px 16px; background: var(--ps-primary); color: #fff; border: none; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; }
.ps-btn-reset { padding: 9px 14px; background: transparent; color: var(--ps-muted); border: 1px solid var(--ps-line); border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; }

/* ═══ Sonuç sayım header ═══ */
.ps-results-head { display: flex; align-items: center; gap: 14px; margin-bottom: 10px; flex-wrap: wrap; }
.ps-results-head h2 { margin: 0; font-size: 16px; color: var(--ps-text); font-weight: 700; }
.ps-results-head .ps-results-count { color: var(--ps-primary); }
.ps-results-head select { padding: 7px 11px; font-size: 12px; border: 1px solid var(--ps-line); border-radius: 7px; background: var(--ps-card); color: var(--ps-text); cursor: pointer; margin-left: auto; }

/* ═══ Result cards ═══ */
.ps-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.ps-card { background: var(--ps-card); border: 1px solid var(--ps-line); border-radius: 10px; padding: 14px 16px; display: flex; flex-direction: column; gap: 6px; transition: border-color .15s, box-shadow .15s; }
.ps-card:hover { border-color: var(--ps-primary); box-shadow: 0 2px 8px rgba(126,88,191,.08); }
.ps-card-uni { font-size: 11.5px; color: var(--ps-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.ps-card-title { font-size: 15px; font-weight: 700; color: var(--ps-text); line-height: 1.35; }
.ps-card-title a { color: inherit; text-decoration: none; }
.ps-card-title a:hover { color: var(--ps-primary); }
.ps-card-meta { display: flex; gap: 6px; flex-wrap: wrap; font-size: 11px; color: var(--ps-muted); margin-top: 4px; }
.ps-card-meta .ps-pill { padding: 3px 8px; background: var(--ps-bg); border-radius: 5px; }
.ps-card-meta .ps-pill.degree { background: rgba(126,88,191,.1); color: var(--ps-primary); font-weight: 600; }
.ps-card-meta .ps-pill.lang { background: rgba(14,165,233,.1); color: #0369a1; font-weight: 600; }
.ps-card-meta .ps-pill.tuition { background: rgba(16,185,129,.1); color: #047857; font-weight: 600; }
.ps-card-meta .ps-pill.curated { background: rgba(245,158,11,.15); color: #b45309; font-weight: 700; }
.ps-card-fields { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px; font-size: 11px; color: #94a3b8; }
.ps-card-fields .ps-field-chip { padding: 2px 7px; border: 1px dashed var(--ps-line); border-radius: 4px; }
.ps-card-actions { display: flex; gap: 6px; margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--ps-line); }
.ps-card-actions a { font-size: 11.5px; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-weight: 600; }
.ps-card-actions .btn-detail { background: var(--ps-primary); color: #fff; }
.ps-card-actions .btn-uni { background: var(--ps-bg); color: var(--ps-text); }

.ps-empty { padding: 60px 20px; text-align: center; background: var(--ps-card); border: 1px dashed var(--ps-line); border-radius: 12px; }
.ps-empty-icon { font-size: 40px; margin-bottom: 12px; }
.ps-empty-title { font-size: 15px; font-weight: 700; margin-bottom: 6px; color: var(--ps-text); }
.ps-empty-sub { font-size: 12.5px; color: var(--ps-muted); }

/* ═══ Mobile breakpoint ═══ */
@media (max-width: 760px) {
    .ps-hero { padding: 16px 14px; border-radius: 12px; }
    .ps-hero-search input { padding: 12px 14px 12px 40px; font-size: 14px; }
    .ps-hero-search::before { left: 12px; }
    .ps-hero-row { grid-template-columns: 1fr 1fr; gap: 8px; }
    .ps-hero-row .ps-select:nth-child(3) { grid-column: 1 / -1; }
    .ps-hero-cta { flex-direction: column; align-items: stretch; gap: 8px; }
    .ps-hero-cta-primary { width: 100%; min-width: 0; padding: 13px 18px; font-size: 13.5px; }
    .ps-hero-cta-primary strong { font-size: 15px; }
    .ps-hero-cta-secondary { width: 100%; text-align: center; }
    .ps-advanced-grid { grid-template-columns: 1fr; gap: 10px; }
    .ps-fieldlist { max-height: 200px; }
    .ps-grid { grid-template-columns: 1fr; }
    .ps-card { padding: 12px 14px; }
    .ps-card-title { font-size: 14px; }
    .ps-results-head select { margin-left: 0; width: 100%; }
}

/* Info bar (üst) */
.ps-info-bar { padding: 10px 14px; background: rgba(126,88,191,.06); border: 1px solid rgba(126,88,191,.18); border-radius: 10px; margin-bottom: 14px; font-size: 12.5px; color: var(--ps-text); }
.ps-info-bar strong { color: var(--ps-primary); }
@media (max-width: 760px) { .ps-info-bar { font-size: 12px; padding: 8px 12px; } }

/* Modal */
.ps-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
.ps-modal-overlay:target { display: flex; }
.ps-modal { background: var(--ps-card); border-radius: 14px; max-width: 600px; width: 100%; max-height: 85vh; overflow-y: auto; padding: 24px 26px; box-shadow: 0 20px 60px rgba(0,0,0,.25); position: relative; z-index: 1; }
.ps-modal h3 { margin: 0 0 12px; font-size: 17px; color: var(--ps-text); }
.ps-modal p, .ps-modal li { font-size: 13px; line-height: 1.55; color: var(--ps-text); }
.ps-modal-close { position: absolute; right: 18px; top: 14px; background: none; border: none; font-size: 22px; line-height: 1; color: var(--ps-muted); cursor: pointer; text-decoration: none; }

/* Top10 secondary toggle URL builder */
</style>

@php
    // Top 10 secondary buton için: mevcut top_uni kaldırılmışsa →ekle, varsa →kaldır
    $top10Active = ($filters['top_uni'] ?? '') === 'top10';
    $top10Params = $filters;
    if ($top10Active) {
        $top10Params['top_uni'] = '';
    } else {
        $top10Params['top_uni'] = 'top10';
    }
    // İçi boş array'leri temizle
    $top10Params = array_filter($top10Params, fn ($v) => $v !== '' && $v !== [] && $v !== null);
    $top10Url = $formAction = $publicMode ? route('uni-match.programs') : route('program-search');
    $top10Url .= '?' . http_build_query($top10Params);

    // Top 10 toplam sayı (cache-able)
    $top10Count = \App\Models\Program::query()->active()
        ->whereIn('university_name_cached', (array) config('germany_geo.top_10', []))
        ->count();
@endphp

<div class="ps-wrap">

    {{-- Info banner --}}
    @if($publicMode)
        <div class="ps-info-bar">
            🎓 <strong>{{ number_format($totalAll) }}+ Almanya programı</strong> — sana en uygun olanı bulmak için <a href="{{ route('uni-match.start') }}" style="color:var(--ps-primary);font-weight:700;">5 dakikalık UniMatch sihirbazını</a> da deneyebilirsin.
        </div>
    @else
        <div class="ps-info-bar">
            🔍 <strong>Internal Arama</strong> — UniMatch wizard'ı atla, doğrudan filtre. Toplam <strong>{{ number_format($totalAll) }}</strong> aktif program.
        </div>
    @endif

    <form method="GET" action="{{ $formAction }}">

        {{-- ═══ HERO ═══ --}}
        <div class="ps-hero">
            {{-- Genel arama --}}
            <div class="ps-hero-search">
                <input type="text" name="q" value="{{ $filters['q'] }}"
                       placeholder="Program adı, üniversite veya anahtar kelime…"
                       autocomplete="off">
            </div>

            {{-- 3 ana select: Derece, Dil, Eyalet --}}
            <div class="ps-hero-row">
                <div class="ps-select">
                    <select name="degree" aria-label="Derece">
                        <option value="">🎓 Tüm Dereceler</option>
                        @foreach($facets['degrees'] as $val => $cnt)
                            <option value="{{ $val }}" @selected($filters['degree'] === $val)>{{ ucfirst($val) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ps-select">
                    <select name="language" aria-label="Dil">
                        <option value="">🌐 Tüm Diller</option>
                        @foreach($facets['languages'] as $val => $cnt)
                            @php $label = ['de' => '🇩🇪 Almanca', 'en' => '🇬🇧 İngilizce', 'both' => '🇩🇪🇬🇧 İkisi'][$val] ?? $val; @endphp
                            <option value="{{ $val }}" @selected($filters['language'] === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ps-select">
                    <select name="state" aria-label="Eyalet">
                        <option value="">🗺️ Tüm Eyaletler</option>
                        @foreach($facets['states'] as $key => $label)
                            <option value="{{ $key }}" @selected($filters['state'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- CTA satırı --}}
            <div class="ps-hero-cta">
                <button type="submit" class="ps-hero-cta-primary">
                    🔍 <strong>{{ number_format($rows->total()) }}</strong> SONUCU GÖSTER
                </button>
                <a href="{{ $top10Url }}" class="ps-hero-cta-secondary {{ $top10Active ? 'is-active' : '' }}">
                    ⭐ {{ number_format($top10Count) }} TOP 10 ÜNİVERSİTE
                </a>
            </div>

            {{-- Gelişmiş Filtreler --}}
            <details class="ps-advanced" {{ $hasAdvanced ? 'open' : '' }}>
                <summary>
                    <span class="ps-advanced-toggle">
                        <span class="ps-advanced-toggle-chevron">▾</span>
                        Gelişmiş Filtreler
                        @if($hasAdvanced)
                            <span class="ps-badge">{{ $advancedFilterCount }}</span>
                        @endif
                    </span>
                </summary>

                <div class="ps-advanced-body">
                    <div class="ps-advanced-grid">

                        {{-- ═══ PROGRAM ═══ --}}
                        <details class="ps-group" open>
                            <summary>
                                <span>📚 Program</span>
                                <span class="ps-group-chev">▾</span>
                            </summary>
                            <div class="ps-group-body">
                                <div class="ps-field">
                                    <label>🏆 Sıralama</label>
                                    <select name="top_uni">
                                        <option value="">Tümü</option>
                                        <option value="top10" @selected($filters['top_uni'] === 'top10')>🥇 Top 10</option>
                                        <option value="top20" @selected($filters['top_uni'] === 'top20')>🥈 Top 20</option>
                                        <option value="top40" @selected($filters['top_uni'] === 'top40')>🥉 Top 40</option>
                                    </select>
                                </div>
                                <div class="ps-field">
                                    <label>📖 Bölüm / Konu</label>
                                    <input type="text" name="subject" value="{{ $filters['subject'] }}" placeholder="Medizin, Informatik…" list="ps-subject-suggestions" autocomplete="off">
                                    <datalist id="ps-subject-suggestions">
                                        <option value="Psychologie">Psikoloji</option>
                                        <option value="Medizin">Tıp</option>
                                        <option value="Zahnmedizin">Diş Hekimliği</option>
                                        <option value="Pharmazie">Eczacılık</option>
                                        <option value="Ingenieurwesen">Mühendislik</option>
                                        <option value="Maschinenbau">Makine Mühendisliği</option>
                                        <option value="Elektrotechnik">Elektrik-Elektronik</option>
                                        <option value="Bauingenieurwesen">İnşaat</option>
                                        <option value="Informatik">Bilgisayar</option>
                                        <option value="Computer Science">Computer Science (EN)</option>
                                        <option value="Architektur">Mimarlık</option>
                                        <option value="Rechtswissenschaft">Hukuk</option>
                                        <option value="Betriebswirtschaft">İşletme (BWL)</option>
                                        <option value="Wirtschaftswissenschaft">İktisat</option>
                                        <option value="Mathematik">Matematik</option>
                                        <option value="Physik">Fizik</option>
                                        <option value="Chemie">Kimya</option>
                                        <option value="Biologie">Biyoloji</option>
                                        <option value="Pädagogik">Pedagoji</option>
                                        <option value="Soziale Arbeit">Sosyal Hizmet</option>
                                        <option value="Data Science">Data Science (EN)</option>
                                    </datalist>
                                </div>

                                <div class="ps-field">
                                    <label>🗂 Bölüm Kategorisi <span style="font-weight:500;color:var(--ps-muted);text-transform:none;letter-spacing:0;">(çoklu)</span></label>
                                    <div class="ps-fieldlist">
                                        @foreach($facets['fields'] as $fname => $fcnt)
                                            @php $fActive = in_array($fname, $filters['fields'] ?? [], true); @endphp
                                            <label class="{{ $fActive ? 'is-active' : '' }}">
                                                <input type="checkbox" name="fields[]" value="{{ $fname }}" @checked($fActive)>
                                                <span>{{ $fname }}</span>
                                                <span class="ps-fl-count">{{ number_format($fcnt) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </details>

                        {{-- ═══ ÜNİVERSİTE & ŞEHİR ═══ --}}
                        <details class="ps-group" {{ ($filters['university'] || $filters['big_city'] || $filters['small_city']) ? 'open' : '' }}>
                            <summary>
                                <span>🏛️ Üniversite & Şehir</span>
                                <span class="ps-group-chev">▾</span>
                            </summary>
                            <div class="ps-group-body">
                                <div class="ps-field">
                                    <label>🏛️ Üniversite Adı</label>
                                    <input type="text" name="university" value="{{ $filters['university'] }}" placeholder="Tüm üniversiteler (547)" list="ps-university-suggestions" autocomplete="off">
                                    <datalist id="ps-university-suggestions">
                                        @foreach($facets['universities'] as $name => $cnt)
                                            <option value="{{ $name }}">{{ $cnt }} program</option>
                                        @endforeach
                                    </datalist>
                                </div>
                                <div class="ps-field">
                                    <label>🏙️ Büyük Şehir</label>
                                    <select name="big_city">
                                        <option value="">Tümü</option>
                                        @foreach($facets['big_cities'] as $name => $cnt)
                                            <option value="{{ $name }}" @selected($filters['big_city'] === $name)>{{ $name }} ({{ $cnt }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ps-field">
                                    <label>🏘️ Küçük Şehir</label>
                                    <input type="text" name="small_city" value="{{ $filters['small_city'] }}" placeholder="{{ count($facets['small_cities']) }} şehir" list="ps-small-city-suggestions" autocomplete="off">
                                    <datalist id="ps-small-city-suggestions">
                                        @foreach($facets['small_cities'] as $name => $cnt)
                                            <option value="{{ $name }}">{{ $cnt }} program</option>
                                        @endforeach
                                    </datalist>
                                </div>
                            </div>
                        </details>

                        {{-- ═══ BÜTÇE ═══ --}}
                        <details class="ps-group" {{ $filters['tuition_max'] ? 'open' : '' }}>
                            <summary>
                                <span>💶 Bütçe</span>
                                <span class="ps-group-chev">▾</span>
                            </summary>
                            <div class="ps-group-body">
                                <div class="ps-field">
                                    <label>Dönem Başı Ücret (Üst Sınır)</label>
                                    <select name="tuition_max">
                                        <option value="">Tümü</option>
                                        <option value="0" @selected($filters['tuition_max'] === '0')>🆓 Ücretsiz</option>
                                        <option value="500" @selected($filters['tuition_max'] === '500')>≤ €500 / dönem</option>
                                        <option value="1500" @selected($filters['tuition_max'] === '1500')>≤ €1500 / dönem</option>
                                        <option value="5000" @selected($filters['tuition_max'] === '5000')>≤ €5000 / dönem</option>
                                    </select>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div class="ps-advanced-actions">
                        <button type="submit" class="ps-btn-apply">✓ Filtreleri Uygula</button>
                        <a href="{{ $formAction }}" class="ps-btn-reset">↻ Sıfırla</a>
                    </div>
                </div>
            </details>
        </div>

        {{-- ═══ Sonuç başlığı + sort ═══ --}}
        <div class="ps-results-head">
            <h2>📊 <span class="ps-results-count">{{ number_format($rows->total()) }}</span> program bulundu</h2>
            <select name="sort" onchange="this.form.submit()">
                <option value="relevance" @selected($filters['sort'] === 'relevance')>Sırala: Alaka</option>
                <option value="quality" @selected($filters['sort'] === 'quality')>Sırala: Kalite</option>
                <option value="name" @selected($filters['sort'] === 'name')>Sırala: A-Z</option>
                <option value="recent" @selected($filters['sort'] === 'recent')>Sırala: Yeni</option>
            </select>
        </div>
    </form>

    {{-- Modal: derece sayıları açıklaması --}}
    <div class="ps-modal-overlay" id="ps-info-modal">
        <a href="#" aria-hidden="true" style="position:absolute;inset:0;"></a>
        <div class="ps-modal">
            <a href="#" class="ps-modal-close">×</a>
            <h3>📊 Filtre Sayıları Hakkında</h3>
            <p>Dropdown sayıları <strong>tüm aktif kataloğu</strong> esas alır. Diğer filtreleri uyguladığında gerçek sonuç sayısı değişir.</p>
            <p><strong>İpucu:</strong> "Bölüm / Konu" alanına Türkçe değil <em>Almanca/İngilizce</em> yaz (Tıp → Medizin, Mühendislik → Ingenieurwesen). Datalist'ten seçince doğru terim gelir.</p>
        </div>
    </div>

    {{-- ═══ SONUÇLAR ═══ --}}
    @if($rows->isEmpty())
        <div class="ps-empty">
            <div class="ps-empty-icon">🔍</div>
            <div class="ps-empty-title">Sonuç bulunamadı</div>
            @if(!empty($filters['university']))
                @php
                    $uniOnlyUrl = $formAction . '?' . http_build_query(['university' => $filters['university']]);
                    $uniProgramCount = $facets['universities'][$filters['university']] ?? 0;
                @endphp
                <div class="ps-empty-sub">
                    Seçili filtreler <strong>{{ $filters['university'] }}</strong> için sonuç vermedi.
                    Bu üniversitenin toplam <strong>{{ $uniProgramCount }}</strong> programı var.
                    <br><br>
                    <a href="{{ $uniOnlyUrl }}" style="color:var(--ps-primary);font-weight:600;">→ Sadece bu üniversitenin tüm programlarını göster</a><br>
                    <a href="{{ $formAction }}" style="color:var(--ps-muted);">Tüm filtreleri temizle</a>
                </div>
            @else
                <div class="ps-empty-sub">Filtreleri gevşet veya farklı bir bölüm/şehir dene. Tüm 15K+ programı görmek için <a href="{{ $formAction }}" style="color:var(--ps-primary);">filtreleri temizle</a>.</div>
            @endif
        </div>
    @else
        <div class="ps-grid">
            @foreach($rows as $p)
                @php
                    $tuitionLabel = match (true) {
                        $p->tuition_eur_per_semester === null => '— ücret bilgisi yok',
                        (int) $p->tuition_eur_per_semester === 0 => 'Ücretsiz',
                        default => '€' . number_format((int) $p->tuition_eur_per_semester, 0, ',', '.') . '/dönem',
                    };
                    $langLabel = ['de' => '🇩🇪 DE', 'en' => '🇬🇧 EN', 'both' => '🇩🇪🇬🇧 DE+EN'][$p->language] ?? $p->language;
                @endphp
                <div class="ps-card">
                    <div class="ps-card-uni">{{ $p->university_name_cached ?: '—' }}</div>
                    <div class="ps-card-title">
                        <a href="{{ route('program.show', $p->id) }}" target="_blank">{{ $p->course_name }}</a>
                    </div>
                    <div class="ps-card-meta">
                        @if($p->degree_type)<span class="ps-pill degree">{{ ucfirst($p->degree_type) }}</span>@endif
                        @if($p->degree_specification)<span class="ps-pill">{{ $p->degree_specification }}</span>@endif
                        <span class="ps-pill lang">{{ $langLabel }}</span>
                        <span class="ps-pill tuition">{{ $tuitionLabel }}</span>
                        @if($p->location)<span class="ps-pill">📍 {{ $p->location }}</span>@endif
                        @if($p->is_manually_curated && !$publicMode)<span class="ps-pill curated">✓ Manuel</span>@endif
                        @if($p->duration_semesters)<span class="ps-pill">⏱ {{ $p->duration_semesters }} dönem</span>@endif
                    </div>
                    @if(!empty($p->study_fields) && is_array($p->study_fields))
                        <div class="ps-card-fields">
                            @foreach(array_slice($p->study_fields, 0, 4) as $f)
                                <span class="ps-field-chip">{{ $f }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="ps-card-actions">
                        <a href="{{ route('program.show', $p->id) }}" target="_blank" class="btn-detail">📄 Program Detayı</a>
                        @if($p->university && $p->university->id)
                            <a href="{{ route('program.show', $p->id) }}#university-info" target="_blank" class="btn-uni">🏛 Üniversite</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($rows->hasPages())
            <div style="margin-top:16px;">{{ $rows->links() }}</div>
        @endif
    @endif
</div>
@endsection

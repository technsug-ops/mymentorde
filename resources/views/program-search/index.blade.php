@php
    // Layout seçimi role'e göre — internal role + portal layout uyumu
    $role = auth()->user()->role ?? 'manager';
    $layout = match ($role) {
        'senior', 'mentor' => 'senior.layouts.app',
        default            => 'manager.layouts.app',
    };
@endphp

@extends($layout)

@section('title', 'Program Arama — ' . config('brand.name', 'MentorDE'))
@section('page_title', 'Program Arama')
@section('page_subtitle', 'Almanya üniversite programları — wizard bypass, doğrudan filtrele')

@section('content')
<style>
.ps-wrap { max-width: 1280px; margin: 0 auto; padding: 0 4px; }
.ps-info-bar { padding: 10px 14px; background: rgba(126,88,191,.06); border: 1px solid rgba(126,88,191,.2); border-radius: 10px; margin-bottom: 14px; font-size: 12.5px; color: #334155; }
.ps-info-bar strong { color: #5b2e91; }

/* Sticky filter bar */
.ps-filters { position: sticky; top: 14px; background: var(--u-card,#fff); border: 1px solid var(--u-line,#e2e8f0); border-radius: 12px; padding: 14px; margin-bottom: 14px; z-index: 10; box-shadow: 0 1px 6px rgba(0,0,0,.04); }
.ps-filters-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr; gap: 8px; align-items: end; }
@media (max-width: 980px) { .ps-filters-row { grid-template-columns: 1fr 1fr; } }
.ps-field label { display: block; font-size: 10.5px; font-weight: 700; color: var(--u-muted,#64748b); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
.ps-field input, .ps-field select { width: 100%; box-sizing: border-box; padding: 8px 10px; font-size: 13px; border: 1px solid var(--u-line,#cbd5e1); border-radius: 7px; background: var(--u-card,#fff); color: var(--u-text); outline: none; font-family: inherit; }
.ps-field input:focus, .ps-field select:focus { border-color: #5b2e91; box-shadow: 0 0 0 3px rgba(91,46,145,.1); }
.ps-actions { display: flex; gap: 8px; margin-top: 10px; align-items: center; flex-wrap: wrap; }
.ps-btn-primary { padding: 8px 18px; background: #5b2e91; color: #fff; border: none; border-radius: 7px; font-size: 12.5px; font-weight: 700; cursor: pointer; }
.ps-btn-primary:hover { background: #4a2578; }
.ps-btn-ghost { padding: 8px 14px; background: transparent; color: var(--u-muted,#64748b); border: 1px solid var(--u-line,#cbd5e1); border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; }
.ps-stat { margin-left: auto; font-size: 11.5px; color: var(--u-muted,#64748b); padding: 4px 10px; background: var(--u-bg,#f1f5f9); border-radius: 6px; }

/* Result cards */
.ps-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 760px) { .ps-grid { grid-template-columns: 1fr; } }
.ps-card { background: var(--u-card,#fff); border: 1px solid var(--u-line,#e2e8f0); border-radius: 10px; padding: 14px 16px; display: flex; flex-direction: column; gap: 6px; transition: border-color .15s, box-shadow .15s; }
.ps-card:hover { border-color: #5b2e91; box-shadow: 0 2px 8px rgba(91,46,145,.08); }
.ps-card-uni { font-size: 11.5px; color: var(--u-muted,#64748b); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.ps-card-title { font-size: 15px; font-weight: 700; color: var(--u-text); line-height: 1.35; }
.ps-card-title a { color: inherit; text-decoration: none; }
.ps-card-title a:hover { color: #5b2e91; }
.ps-card-meta { display: flex; gap: 6px; flex-wrap: wrap; font-size: 11px; color: var(--u-muted,#64748b); margin-top: 4px; }
.ps-card-meta .ps-pill { padding: 3px 8px; background: var(--u-bg,#f1f5f9); border-radius: 5px; }
.ps-card-meta .ps-pill.degree { background: rgba(91,46,145,.1); color: #5b2e91; font-weight: 600; }
.ps-card-meta .ps-pill.lang { background: rgba(14,165,233,.1); color: #0369a1; font-weight: 600; }
.ps-card-meta .ps-pill.tuition { background: rgba(16,185,129,.1); color: #047857; font-weight: 600; }
.ps-card-meta .ps-pill.curated { background: rgba(245,158,11,.15); color: #b45309; font-weight: 700; }
.ps-card-fields { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px; font-size: 11px; color: var(--u-muted,#94a3b8); }
.ps-card-fields .ps-field-chip { padding: 2px 7px; border: 1px dashed var(--u-line,#cbd5e1); border-radius: 4px; }
.ps-card-actions { display: flex; gap: 6px; margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--u-line,#e2e8f0); }
.ps-card-actions a { font-size: 11.5px; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-weight: 600; }
.ps-card-actions .btn-detail { background: #5b2e91; color: #fff; }
.ps-card-actions .btn-detail:hover { background: #4a2578; }
.ps-card-actions .btn-uni { background: var(--u-bg,#f1f5f9); color: var(--u-text); }
.ps-card-actions .btn-uni:hover { background: var(--u-line,#e2e8f0); }

.ps-empty { padding: 60px 20px; text-align: center; background: var(--u-card,#fff); border: 1px dashed var(--u-line,#cbd5e1); border-radius: 12px; }
.ps-empty-icon { font-size: 40px; margin-bottom: 12px; }
.ps-empty-title { font-size: 15px; font-weight: 700; margin-bottom: 6px; color: var(--u-text); }
.ps-empty-sub { font-size: 12.5px; color: var(--u-muted,#64748b); }
</style>

<div class="ps-wrap">
    <div class="ps-info-bar">
        🔍 <strong>Internal Program Arama</strong> — UniMatch wizard'ı atlayıp doğrudan filtrele. Toplam <strong>{{ number_format($totalAll) }}</strong> canonical program. Bölüm (Psikoloji, Mühendislik vb.) yazarak ara, üniversite/şehir/ücret/dil ile daralt.
    </div>

    <form method="GET" action="{{ route('program-search') }}" class="ps-filters">
        <div class="ps-filters-row">
            <div class="ps-field">
                <label>🏛️ Üniversite</label>
                <input type="text" name="university" value="{{ $filters['university'] }}" placeholder="Tüm üniversiteler (547)" list="ps-university-suggestions" autocomplete="off">
                <datalist id="ps-university-suggestions">
                    @foreach($facets['universities'] as $name => $cnt)
                        <option value="{{ $name }}">{{ $cnt }} program</option>
                    @endforeach
                </datalist>
            </div>
            <div class="ps-field">
                <label>Genel Arama</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="program adı, anahtar kelime…">
            </div>
            <div class="ps-field">
                <label>Bölüm / Konu</label>
                <input type="text" name="subject" value="{{ $filters['subject'] }}" placeholder="Psikoloji, Mühendislik…" list="ps-subject-suggestions">
                <datalist id="ps-subject-suggestions">
                    <option value="Psikoloji">
                    <option value="Mühendislik">
                    <option value="Bilgisayar">
                    <option value="Mimarlık">
                    <option value="Tıp">
                    <option value="Hukuk">
                    <option value="İşletme">
                    <option value="Ekonomi">
                    <option value="Pedagoji">
                    <option value="Felsefe">
                    <option value="Biyoloji">
                    <option value="Kimya">
                    <option value="Fizik">
                    <option value="Sanat">
                    <option value="Müzik">
                </datalist>
            </div>
            <div class="ps-field">
                <label>Derece</label>
                <select name="degree">
                    <option value="">Tümü</option>
                    @foreach($facets['degrees'] as $val => $cnt)
                        <option value="{{ $val }}" @selected($filters['degree'] === $val)>{{ ucfirst($val) }} ({{ $cnt }})</option>
                    @endforeach
                </select>
            </div>
            <div class="ps-field">
                <label>Dil</label>
                <select name="language">
                    <option value="">Tümü</option>
                    @foreach($facets['languages'] as $val => $cnt)
                        @php $label = ['de' => '🇩🇪 Almanca', 'en' => '🇬🇧 İngilizce', 'both' => '🇩🇪🇬🇧 İkisi'][$val] ?? $val; @endphp
                        <option value="{{ $val }}" @selected($filters['language'] === $val)>{{ $label }} ({{ $cnt }})</option>
                    @endforeach
                </select>
            </div>
            <div class="ps-field">
                <label>Şehir</label>
                <select name="city">
                    <option value="">Tümü</option>
                    @foreach($facets['cities'] as $val => $cnt)
                        <option value="{{ $val }}" @selected($filters['city'] === $val)>{{ $val }} ({{ $cnt }})</option>
                    @endforeach
                </select>
            </div>
            <div class="ps-field">
                <label>Ücret (üst)</label>
                <select name="tuition_max">
                    <option value="">Tümü</option>
                    <option value="0" @selected($filters['tuition_max'] === '0')>🆓 Ücretsiz</option>
                    <option value="500" @selected($filters['tuition_max'] === '500')>≤ €500/dönem</option>
                    <option value="1500" @selected($filters['tuition_max'] === '1500')>≤ €1500/dönem</option>
                    <option value="5000" @selected($filters['tuition_max'] === '5000')>≤ €5000/dönem</option>
                </select>
            </div>
        </div>
        <div class="ps-actions">
            <button type="submit" class="ps-btn-primary">🔍 Filtrele</button>
            <a href="{{ route('program-search') }}" class="ps-btn-ghost">✕ Temizle</a>
            <select name="sort" onchange="this.form.submit()" style="padding:7px 10px; font-size:12px; border:1px solid var(--u-line,#cbd5e1); border-radius:6px; background:var(--u-card,#fff);">
                <option value="relevance" @selected($filters['sort'] === 'relevance')>Sırala: Alaka</option>
                <option value="quality" @selected($filters['sort'] === 'quality')>Sırala: Kalite skoru</option>
                <option value="name" @selected($filters['sort'] === 'name')>Sırala: Ad (A-Z)</option>
                <option value="recent" @selected($filters['sort'] === 'recent')>Sırala: Son güncellenen</option>
            </select>
            <span class="ps-stat">📊 <strong>{{ number_format($rows->total()) }}</strong> sonuç</span>
        </div>
    </form>

    @if($rows->isEmpty())
        <div class="ps-empty">
            <div class="ps-empty-icon">🔍</div>
            <div class="ps-empty-title">Sonuç bulunamadı</div>
            @if($filters['university'] !== '')
                @php
                    $uniOnlyUrl = route('program-search') . '?' . http_build_query(['university' => $filters['university']]);
                    $uniProgramCount = $facets['universities'][$filters['university']] ?? 0;
                @endphp
                <div class="ps-empty-sub">
                    Seçili filtreler <strong>{{ $filters['university'] }}</strong> için sonuç vermedi.
                    Bu üniversitenin toplam <strong>{{ $uniProgramCount }}</strong> programı var
                    (örn. <em>{{ $filters['city'] ? '"'.$filters['city'].'" şehir filtresi üniversite ile çelişiyor olabilir' : 'diğer filtreleri gevşet' }}</em>).
                    <br><br>
                    <a href="{{ $uniOnlyUrl }}" style="color:#5b2e91;font-weight:600;">→ Sadece bu üniversitenin tüm programlarını göster</a><br>
                    <a href="{{ route('program-search') }}" style="color:#64748b;">Tüm filtreleri temizle</a>
                </div>
            @else
                <div class="ps-empty-sub">Filtreleri gevşet veya farklı bir bölüm/şehir dene. Tüm 15K+ programı görmek için <a href="{{ route('program-search') }}" style="color:#5b2e91;">filtreleri temizle</a>.</div>
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
                        @if($p->is_manually_curated)<span class="ps-pill curated">✓ Manuel</span>@endif
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
                            <a href="{{ route('program.show', $p->id) }}#university-info" target="_blank" class="btn-uni">🏛 Üniversite Bilgisi</a>
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

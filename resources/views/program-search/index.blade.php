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

/* Hero search — büyük ortalanmış genel arama */
.ps-hero { max-width: 760px; margin: 0 auto 18px; text-align: center; }
.ps-hero-input { width: 100%; box-sizing: border-box; padding: 14px 20px; font-size: 16px; border: 2px solid var(--u-line,#cbd5e1); border-radius: 12px; background: var(--u-card,#fff); color: var(--u-text); outline: none; font-family: inherit; text-align: center; transition: border-color .15s, box-shadow .15s; }
.ps-hero-input:focus { border-color: #5b2e91; box-shadow: 0 0 0 4px rgba(91,46,145,.12); }
.ps-hero-hint { margin-top: 6px; font-size: 11.5px; color: var(--u-muted,#94a3b8); }

/* Sticky filter bar */
.ps-filters { position: sticky; top: 14px; background: var(--u-card,#fff); border: 1px solid var(--u-line,#e2e8f0); border-radius: 12px; padding: 14px; margin-bottom: 14px; z-index: 10; box-shadow: 0 1px 6px rgba(0,0,0,.04); }
.ps-filters-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; align-items: end; margin-bottom: 8px; }
.ps-filters-row:last-of-type { margin-bottom: 0; }
.ps-filters-group-label { font-size: 10px; font-weight: 800; color: #5b2e91; text-transform: uppercase; letter-spacing: .08em; margin: 8px 0 4px; padding-bottom: 3px; border-bottom: 1px dashed rgba(91,46,145,.2); }
.ps-filters-group-label:first-child { margin-top: 0; }
@media (max-width: 980px) { .ps-filters-row { grid-template-columns: 1fr 1fr; } }

/* Info modal trigger + modal */
.ps-info-btn { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border-radius: 50%; background: rgba(91,46,145,.1); color: #5b2e91; font-size: 10px; font-weight: 700; border: none; cursor: pointer; vertical-align: middle; margin-left: 4px; text-decoration: none; font-style: italic; font-family: serif; }
.ps-info-btn:hover { background: #5b2e91; color: #fff; }
.ps-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
.ps-modal-overlay:target { display: flex; }
.ps-modal { background: var(--u-card,#fff); border-radius: 14px; max-width: 600px; width: 100%; max-height: 85vh; overflow-y: auto; padding: 24px 26px; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
.ps-modal h3 { margin: 0 0 12px; font-size: 17px; color: var(--u-text); }
.ps-modal p, .ps-modal li { font-size: 13px; line-height: 1.55; color: var(--u-text); }
.ps-modal ul { padding-left: 20px; margin: 8px 0 14px; }
.ps-modal-close { float: right; background: none; border: none; font-size: 22px; line-height: 1; color: var(--u-muted,#64748b); cursor: pointer; text-decoration: none; padding: 0 4px; }
.ps-modal-close:hover { color: #5b2e91; }
.ps-modal table { width: 100%; border-collapse: collapse; font-size: 12.5px; margin: 8px 0; }
.ps-modal th, .ps-modal td { padding: 6px 8px; border-bottom: 1px solid var(--u-line,#e2e8f0); text-align: left; }
.ps-modal th { background: rgba(91,46,145,.06); color: #5b2e91; font-weight: 700; }
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

/* ── 2-sütun layout: sol bölüm sidebar + sağ içerik ── */
.ps-layout { display: grid; grid-template-columns: 260px 1fr; gap: 14px; align-items: start; }
@media (max-width: 960px) { .ps-layout { grid-template-columns: 1fr; } }

.ps-sidebar { background: var(--u-card,#fff); border: 1px solid var(--u-line,#e2e8f0); border-radius: 12px; padding: 14px; position: sticky; top: 14px; max-height: calc(100vh - 30px); overflow-y: auto; }
.ps-sidebar h4 { margin: 0 0 8px; font-size: 12px; font-weight: 800; color: #5b2e91; text-transform: uppercase; letter-spacing: .06em; }
.ps-sidebar-search { width: 100%; box-sizing: border-box; padding: 8px 10px; font-size: 12.5px; border: 1px solid var(--u-line,#cbd5e1); border-radius: 7px; margin-bottom: 10px; background: var(--u-bg,#f8fafc); }
.ps-sidebar-search:focus { outline: none; border-color: #5b2e91; background: var(--u-card,#fff); }
.ps-field-list { display: flex; flex-direction: column; gap: 2px; }
.ps-field-row { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 6px; font-size: 12.5px; cursor: pointer; transition: background .12s; }
.ps-field-row:hover { background: var(--u-bg,#f1f5f9); }
.ps-field-row input { margin: 0; cursor: pointer; accent-color: #5b2e91; }
.ps-field-row .ps-field-label { flex: 1; color: var(--u-text); line-height: 1.3; }
.ps-field-row .ps-field-count { font-size: 11px; color: var(--u-muted,#94a3b8); font-weight: 600; }
.ps-field-row.is-active { background: rgba(91,46,145,.08); }
.ps-field-row.is-active .ps-field-label { color: #5b2e91; font-weight: 600; }
.ps-field-empty { padding: 16px 8px; text-align: center; font-size: 11.5px; color: var(--u-muted,#94a3b8); font-style: italic; }
.ps-sidebar-actions { margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--u-line,#e2e8f0); display: flex; gap: 6px; }
.ps-sidebar-actions button { flex: 1; padding: 6px 8px; font-size: 11px; border: 1px solid var(--u-line,#cbd5e1); background: var(--u-card,#fff); color: var(--u-muted,#64748b); border-radius: 6px; cursor: pointer; font-weight: 600; }
.ps-sidebar-actions button:hover { border-color: #5b2e91; color: #5b2e91; }
</style>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var search = document.getElementById('ps-field-search');
    var list = document.getElementById('ps-field-list');
    if (!search || !list) return;
    var rows = list.querySelectorAll('.ps-field-row');
    search.addEventListener('input', function(e){
        var q = (e.target.value || '').toLowerCase().trim();
        rows.forEach(function(row){
            var name = (row.getAttribute('data-field-name') || '').toLowerCase();
            row.style.display = (q === '' || name.indexOf(q) !== -1) ? '' : 'none';
        });
    });
})();
</script>
@endpush


<div class="ps-wrap">
    <div class="ps-info-bar">
        🔍 <strong>Internal Program Arama</strong> — UniMatch wizard'ı atlayıp doğrudan filtrele. Toplam <strong>{{ number_format($totalAll) }}</strong> canonical program. Bölüm (Psikoloji, Mühendislik vb.) yazarak ara, üniversite/şehir/ücret/dil ile daralt.
    </div>

    <form method="GET" action="{{ route('program-search') }}">
        {{-- Genel arama: filtreden bağımsız, ortalanmış, büyük --}}
        <div class="ps-hero">
            <input type="text" name="q" value="{{ $filters['q'] }}"
                   class="ps-hero-input"
                   placeholder="🔍 Program adı, anahtar kelime ile genel arama…"
                   autocomplete="off">
            <div class="ps-hero-hint">Bu alan filtrelerden bağımsızdır — istediğin terimi gir, aşağıdaki filtrelerle daraltabilirsin</div>
        </div>

        <div class="ps-layout">
            {{-- Sol sidebar: bölüm kategorileri (uni-assist tarzı) --}}
            <aside class="ps-sidebar">
                <h4>📚 Bölüm Kategorisi</h4>
                <input type="search" id="ps-field-search" class="ps-sidebar-search" placeholder="Bölüm ara... (örn. Engineering)" autocomplete="off">
                <div class="ps-field-list" id="ps-field-list">
                    @forelse($facets['fields'] as $field => $cnt)
                        @php $isActive = in_array($field, $filters['fields'], true); @endphp
                        <label class="ps-field-row {{ $isActive ? 'is-active' : '' }}" data-field-name="{{ strtolower($field) }}">
                            <input type="checkbox" name="fields[]" value="{{ $field }}" @checked($isActive)>
                            <span class="ps-field-label">{{ $field }}</span>
                            <span class="ps-field-count">{{ number_format($cnt) }}</span>
                        </label>
                    @empty
                        <div class="ps-field-empty">Kategori yok</div>
                    @endforelse
                </div>
                <div class="ps-sidebar-actions">
                    <button type="submit">✓ Uygula</button>
                    <a href="{{ route('program-search') }}" style="flex:1;padding:6px 8px;font-size:11px;border:1px solid var(--u-line,#cbd5e1);background:var(--u-card,#fff);color:var(--u-muted,#64748b);border-radius:6px;font-weight:600;text-align:center;text-decoration:none;">Sıfırla</a>
                </div>
            </aside>

            <div class="ps-content">
        {{-- Filtreler --}}
        <div class="ps-filters">
        <div class="ps-filters-group-label">📍 Lokasyon</div>
        <div class="ps-filters-row">
            <div class="ps-field">
                <label>🗺️ Eyalet</label>
                <select name="state">
                    <option value="">Tüm eyaletler (16)</option>
                    @foreach($facets['states'] as $key => $label)
                        <option value="{{ $key }}" @selected($filters['state'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ps-field">
                <label>🏙️ Büyük şehir</label>
                <select name="big_city">
                    <option value="">Tümü</option>
                    @foreach($facets['big_cities'] as $name => $cnt)
                        <option value="{{ $name }}" @selected($filters['big_city'] === $name)>{{ $name }} ({{ $cnt }})</option>
                    @endforeach
                </select>
            </div>
            <div class="ps-field">
                <label>🏘️ Küçük şehir</label>
                <input type="text" name="small_city" value="{{ $filters['small_city'] }}" placeholder="{{ count($facets['small_cities']) }} şehir — yaz ve seç" list="ps-small-city-suggestions" autocomplete="off">
                <datalist id="ps-small-city-suggestions">
                    @foreach($facets['small_cities'] as $name => $cnt)
                        <option value="{{ $name }}">{{ $cnt }} program</option>
                    @endforeach
                </datalist>
            </div>
            <div class="ps-field">
                <label>🏛️ Üniversite</label>
                <input type="text" name="university" value="{{ $filters['university'] }}" placeholder="Tüm üniversiteler (547)" list="ps-university-suggestions" autocomplete="off">
                <datalist id="ps-university-suggestions">
                    @foreach($facets['universities'] as $name => $cnt)
                        <option value="{{ $name }}">{{ $cnt }} program</option>
                    @endforeach
                </datalist>
            </div>
        </div>{{-- /Row 1: Lokasyon --}}

        <div class="ps-filters-group-label">🎓 Üniversite Sıralaması & Akademik</div>
        <div class="ps-filters-row">
            <div class="ps-field">
                <label>🏆 Top Üniversiteler</label>
                <select name="top_uni">
                    <option value="">Tümü</option>
                    <option value="top10" @selected($filters['top_uni'] === 'top10')>🥇 Top 10</option>
                    <option value="top20" @selected($filters['top_uni'] === 'top20')>🥈 Top 20</option>
                    <option value="top40" @selected($filters['top_uni'] === 'top40')>🥉 Top 40</option>
                </select>
            </div>
            <div class="ps-field">
                <label>📚 Bölüm / Konu</label>
                <input type="text" name="subject" value="{{ $filters['subject'] }}" placeholder="Psychologie, Medizin…" list="ps-subject-suggestions" autocomplete="off">
                <datalist id="ps-subject-suggestions">
                    {{-- Almanca/EN değerler — DB'de bölümler bu dilde, Türkçe arandığında bulunmaz --}}
                    <option value="Psychologie">Psikoloji</option>
                    <option value="Medizin">Tıp</option>
                    <option value="Zahnmedizin">Diş Hekimliği</option>
                    <option value="Pharmazie">Eczacılık</option>
                    <option value="Tiermedizin">Veteriner</option>
                    <option value="Ingenieurwesen">Mühendislik (genel)</option>
                    <option value="Maschinenbau">Makine Mühendisliği</option>
                    <option value="Elektrotechnik">Elektrik-Elektronik Müh.</option>
                    <option value="Bauingenieurwesen">İnşaat Mühendisliği</option>
                    <option value="Informatik">Bilgisayar / Informatik</option>
                    <option value="Computer Science">Computer Science (EN)</option>
                    <option value="Wirtschaftsinformatik">Yönetim Bilişim Sistemleri</option>
                    <option value="Architektur">Mimarlık</option>
                    <option value="Architecture">Architecture (EN)</option>
                    <option value="Rechtswissenschaft">Hukuk</option>
                    <option value="Jura">Hukuk (Jura)</option>
                    <option value="Betriebswirtschaft">İşletme (BWL)</option>
                    <option value="Business Administration">Business Administration (EN)</option>
                    <option value="Wirtschaftswissenschaft">İktisat / Ekonomi</option>
                    <option value="Economics">Economics (EN)</option>
                    <option value="Finance">Finans (EN)</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Mathematik">Matematik</option>
                    <option value="Physik">Fizik</option>
                    <option value="Chemie">Kimya</option>
                    <option value="Biologie">Biyoloji</option>
                    <option value="Biotechnologie">Biyoteknoloji</option>
                    <option value="Pädagogik">Pedagoji / Eğitim</option>
                    <option value="Erziehungswissenschaft">Eğitim Bilimleri</option>
                    <option value="Sozialwissenschaft">Sosyal Bilimler</option>
                    <option value="Soziale Arbeit">Sosyal Hizmet</option>
                    <option value="Politikwissenschaft">Siyaset Bilimi</option>
                    <option value="Geschichte">Tarih</option>
                    <option value="Philosophie">Felsefe</option>
                    <option value="Theologie">Teoloji / İlahiyat</option>
                    <option value="Kunst">Sanat</option>
                    <option value="Design">Tasarım</option>
                    <option value="Musik">Müzik</option>
                    <option value="Sport">Spor Bilimleri</option>
                    <option value="Anglistik">İngiliz Dili / Edebiyatı</option>
                    <option value="Germanistik">Alman Dili / Edebiyatı</option>
                    <option value="Romanistik">Roman Dilleri</option>
                    <option value="Übersetzen">Çeviribilim</option>
                    <option value="Data Science">Data Science (EN)</option>
                    <option value="Artificial Intelligence">Yapay Zeka (EN)</option>
                    <option value="Robotics">Robotik (EN)</option>
                    <option value="Renewable Energy">Yenilenebilir Enerji (EN)</option>
                    <option value="Environmental">Çevre Bilimleri (EN)</option>
                    <option value="Geographie">Coğrafya</option>
                    <option value="Geologie">Jeoloji</option>
                    <option value="Pflege">Hemşirelik / Bakım</option>
                </datalist>
            </div>
            <div class="ps-field">
                <label>
                    🎓 Derece
                    <a href="#ps-info-modal" class="ps-info-btn" title="Sayılar hakkında bilgi">i</a>
                </label>
                <select name="degree">
                    <option value="">Tümü</option>
                    @foreach($facets['degrees'] as $val => $cnt)
                        <option value="{{ $val }}" @selected($filters['degree'] === $val)>{{ ucfirst($val) }} ({{ $cnt }})</option>
                    @endforeach
                </select>
            </div>
            <div class="ps-field">
                <label>🌐 Dil</label>
                <select name="language">
                    <option value="">Tümü</option>
                    @foreach($facets['languages'] as $val => $cnt)
                        @php $label = ['de' => '🇩🇪 Almanca', 'en' => '🇬🇧 İngilizce', 'both' => '🇩🇪🇬🇧 İkisi (DE+EN)'][$val] ?? $val; @endphp
                        <option value="{{ $val }}" @selected($filters['language'] === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>{{-- /Row 2: Akademik --}}

        <div class="ps-filters-row">
            <div class="ps-field">
                <label>💶 Ücret (üst)</label>
                <select name="tuition_max">
                    <option value="">Tümü</option>
                    <option value="0" @selected($filters['tuition_max'] === '0')>🆓 Ücretsiz</option>
                    <option value="500" @selected($filters['tuition_max'] === '500')>≤ €500/dönem</option>
                    <option value="1500" @selected($filters['tuition_max'] === '1500')>≤ €1500/dönem</option>
                    <option value="5000" @selected($filters['tuition_max'] === '5000')>≤ €5000/dönem</option>
                </select>
            </div>
        </div>{{-- /Row 3: Bütçe --}}
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
        </div>{{-- /ps-filters --}}
            </div>{{-- /ps-content --}}
        </div>{{-- /ps-layout --}}
    </form>

    {{-- Bilgi modalı — derece sayıları açıklaması (CSS-only :target modal, JS yok) --}}
    <div class="ps-modal-overlay" id="ps-info-modal">
        <a href="#" class="ps-modal-bg-close" aria-hidden="true" style="position:absolute;inset:0;display:block;"></a>
        <div class="ps-modal" style="position:relative;z-index:1;">
            <a href="#" class="ps-modal-close" aria-label="Kapat">×</a>
            <h3>📊 Sayılar Hakkında</h3>
            <p>Filtre dropdown'larındaki parantez içi sayılar (örn. <strong>Bachelor (6221)</strong>) <strong>tüm aktif kataloğu</strong> esas alır — diğer filtreleri uyguladığında gerçek sonuç sayısı değişir.</p>
            <p><strong>Örnek dağılımlar:</strong></p>
            <table>
                <thead><tr><th>Kategori</th><th>Bachelor</th><th>Master</th><th>PhD</th></tr></thead>
                <tbody>
                    <tr><td>🇩🇪 Sadece Almanca</td><td>~4900</td><td>~3200</td><td>~200</td></tr>
                    <tr><td>🇬🇧 Sadece İngilizce</td><td>~600</td><td>~3300</td><td>~150</td></tr>
                    <tr><td>🇩🇪🇬🇧 İkisi (DE+EN)</td><td>~700</td><td>~2200</td><td>~40</td></tr>
                </tbody>
            </table>
            <p><strong>Yaygın bölüm kategorileri (DB'de Almanca/İngilizce geçer):</strong></p>
            <ul>
                <li><strong>Mühendislik</strong> — Ingenieurwesen / Maschinenbau / Elektrotechnik / Bauingenieurwesen</li>
                <li><strong>Tıp & sağlık</strong> — Medizin / Zahnmedizin / Pharmazie / Pflege</li>
                <li><strong>Hukuk</strong> — Rechtswissenschaft / Jura</li>
                <li><strong>İşletme & ekonomi</strong> — Betriebswirtschaft (BWL) / Wirtschaftswissenschaft / Business / Economics</li>
                <li><strong>Bilgisayar</strong> — Informatik / Computer Science / Data Science</li>
                <li><strong>Sosyal bilimler</strong> — Sozialwissenschaft / Pädagogik / Politikwissenschaft / Psychologie</li>
                <li><strong>Doğa bilimleri</strong> — Mathematik / Physik / Chemie / Biologie</li>
                <li><strong>Sanat & dil</strong> — Kunst / Musik / Anglistik / Germanistik</li>
            </ul>
            <p style="background:rgba(91,46,145,.06);padding:10px;border-radius:8px;font-size:12.5px;">
                💡 <strong>İpucu:</strong> Bölüm filtresine <em>Türkçe</em> değil <em>Almanca/İngilizce</em> yaz (örn. "Tıp" yerine "Medizin"). Datalist'ten seçince doğru terim otomatik gelir.
            </p>
        </div>
    </div>

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

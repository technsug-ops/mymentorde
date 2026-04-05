@extends('student.layouts.app')
@section('title', 'CV / Doküman Asistanı')
@section('page_title', 'CV / Doküman Asistanı')

@push('head')
    @vite('resources/js/student-document-builder.jsx')
    <style>
/* ── db-* Document Builder ── */

/* Nav bar: tabs left, KPI right */
.db-nav {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 10px; padding: 8px 12px; margin-bottom: 12px; flex-wrap: wrap;
}
.db-tabs { display: flex; gap: 5px; flex-wrap: wrap; }
.db-tab {
    padding: 6px 13px; border-radius: 7px; border: 1px solid var(--u-line);
    background: var(--u-bg); font-size: 12px; font-weight: 600; cursor: pointer;
    color: var(--u-muted); transition: all .15s; white-space: nowrap;
}
.db-tab:hover  { border-color: #7c3aed; color: #7c3aed; }
.db-tab.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }

.db-kpi-strip {
    display: flex; gap: 0; flex-wrap: wrap;
    border: 1px solid var(--u-line); border-radius: 8px; overflow: hidden;
}
.db-kpi-item {
    text-align: center; padding: 6px 14px;
    border-right: 1px solid var(--u-line); background: var(--u-bg);
}
.db-kpi-item:last-child { border-right: none; }
.db-kpi-num { font-size: 16px; font-weight: 800; color: #7c3aed; line-height: 1; }
.db-kpi-lbl { font-size: 10px; color: var(--u-muted); margin-top: 1px; white-space: nowrap; }

/* Panels */
.db-panel      { display: none; }
.db-panel.active { display: block; }

/* CV Panel */
#panel-cv {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 12px;
    padding: 20px;
}
#student-cv-builder-root { min-height: 720px; }
#student-cv-builder-root .max-w-7xl { max-width: none !important; }

/* Motivasyon / Referans panel */
.db-wiz-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 18px 20px; margin-bottom: 14px;
}
.db-wiz-card-title {
    font-size: 12px; font-weight: 800; color: var(--u-muted);
    text-transform: uppercase; letter-spacing: .6px;
    padding-bottom: 10px; margin-bottom: 12px;
    border-bottom: 1px solid var(--u-line);
    display: flex; align-items: center; gap: 8px;
}
.db-wiz-card-title::before {
    content: ''; display: inline-block; width: 3px; height: 14px;
    background: #7c3aed; border-radius: 2px; flex-shrink: 0;
}
.db-wiz-card-sub { font-size: 12px; color: var(--u-muted); margin-bottom: 14px; }

.db-section-sep {
    font-size: 10px; font-weight: 800; color: var(--u-muted); text-transform: uppercase;
    letter-spacing: .6px; padding-bottom: 6px; border-bottom: 1px solid var(--u-line);
    margin: 12px 0 10px;
}

/* Wizard grid */
.wizard-grid { display: grid; grid-template-columns: 1.15fr .85fr; gap: 16px; align-items: start; }
.wizard-fields { display: flex; flex-direction: column; gap: 10px; }
.wizard-field label {
    display: block; font-size: 11px; font-weight: 700; color: var(--u-muted);
    text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px;
}
.wizard-field label span.q-hint { font-weight: 400; text-transform: none; letter-spacing: 0; margin-left: 4px; opacity: .7; }
.wizard-field input,
.wizard-field textarea,
.wizard-field select {
    width: 100%; border: 1px solid var(--u-line); border-radius: 8px; padding: 8px 10px;
    font: inherit; font-size: 13px; background: var(--u-bg); color: var(--u-text); resize: vertical;
    transition: border-color .15s, box-shadow .15s; box-sizing: border-box;
}
.wizard-field input:focus,
.wizard-field textarea:focus,
.wizard-field select:focus {
    outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1);
}
.wizard-field textarea { min-height: 68px; }
.wizard-fields .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.wizard-preview { position: sticky; top: 8px; display: flex; flex-direction: column; gap: 10px; }
.wizard-preview-box {
    border: 1px solid var(--u-line); border-radius: 12px; background: var(--u-bg); padding: 14px;
}
.wizard-preview-label {
    font-size: 11px; font-weight: 700; color: var(--u-muted); text-transform: uppercase;
    letter-spacing: .04em; margin-bottom: 10px;
}
.wizard-preview textarea {
    width: 100%; min-height: 340px; border: 1px solid var(--u-line); border-radius: 8px;
    padding: 10px; font: inherit; font-size: 13px; line-height: 1.6; resize: vertical;
    background: var(--u-card); color: var(--u-text); box-sizing: border-box;
}
.wizard-ai-btns { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
.wizard-status  { font-size: 12px; font-weight: 600; display: none; }
.wizard-status.ai  { color: #16a34a; }
.wizard-status.tpl { color: #b45309; }
.wizard-status.err { color: #dc2626; }
.wizard-loading { display: none; font-size: 12px; color: #7c3aed; margin: 4px 0; }
.wizard-save-wrap { border-top: 1px solid var(--u-line); padding-top: 10px; margin-top: 8px; }

/* Örnek şablon */
.wizard-example {
    margin-bottom: 12px; border-radius: 10px; overflow: hidden;
    border: 1.5px solid rgba(124,58,237,.2); background: rgba(124,58,237,.03);
}
.wizard-example summary {
    list-style: none; cursor: pointer; padding: 10px 14px; font-size: 13px;
    font-weight: 700; color: #7c3aed; display: flex; align-items: center; gap: 8px; user-select: none;
}
.wizard-example summary::-webkit-details-marker { display: none; }
.wizard-example .ex-label { flex: 1; }
.wizard-example .ex-sub   { font-size: 11px; font-weight: 400; color: var(--u-muted); }
.wizard-example .ex-chevron {
    font-size: 11px; color: #7c3aed; border: 1px solid #7c3aed; border-radius: 4px;
    padding: 1px 5px; transition: transform .2s; background: var(--u-card); opacity: .6;
}
.wizard-example[open] summary .ex-chevron { transform: rotate(90deg); }
.wizard-example-body { padding: 0 14px 14px; }
.wizard-example-body textarea {
    width: 100%; min-height: 180px; border: 1px solid var(--u-line); border-radius: 8px;
    padding: 10px; font: inherit; font-size: 12px; line-height: 1.7;
    background: var(--u-card); color: var(--u-text); resize: vertical; box-sizing: border-box;
}
.wizard-example-hint {
    font-size: 11px; color: #7c3aed; background: rgba(124,58,237,.08);
    border-radius: 6px; padding: 5px 9px; margin-bottom: 8px;
}

/* Buttons */
.db-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    border: 1px solid var(--u-line); border-radius: 9px; padding: 8px 14px;
    background: var(--u-card); color: var(--u-text); font-size: 13px;
    font-weight: 600; text-decoration: none; cursor: pointer; transition: border-color .15s, color .15s;
}
.db-btn:hover { border-color: #7c3aed; color: #7c3aed; }
.db-btn.primary { background: #7c3aed; border-color: #7c3aed; color: #fff; }
.db-btn.primary:hover { background: #6d28d9; }
.db-btn:disabled { opacity: .5; pointer-events: none; }

/* Outputs */
.db-output-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-bottom: 1px solid var(--u-line);
}
.db-output-item:last-child { border-bottom: none; }
.db-output-icon {
    width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 16px;
    background: rgba(124,58,237,.08);
}

/* Modals */
.wiz-modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45);
    z-index: 9000; align-items: center; justify-content: center; padding: 16px;
}
.wiz-modal-overlay.open { display: flex; }
.wiz-modal {
    background: var(--u-card); border-radius: 16px; max-width: 560px; width: 100%;
    max-height: 80vh; display: flex; flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,.2); border: 1px solid var(--u-line);
}
.wiz-modal-head {
    padding: 16px 20px 12px; border-bottom: 1px solid var(--u-line);
    display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
}
.wiz-modal-head h4 { margin: 0; font-size: 15px; color: var(--u-text); }
.wiz-modal-close {
    border: none; background: none; cursor: pointer; font-size: 20px;
    color: var(--u-muted); line-height: 1; padding: 2px 6px; border-radius: 6px;
}
.wiz-modal-close:hover { background: var(--u-bg); color: #7c3aed; }
.wiz-modal-body { padding: 16px 20px; overflow-y: auto; flex: 1; }
.wiz-modal-section-title {
    font-size: 11px; font-weight: 700; color: var(--u-muted);
    text-transform: uppercase; letter-spacing: .04em; margin: 14px 0 8px;
}
.wiz-modal-section-title:first-child { margin-top: 0; }
.wiz-guide-list { margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 6px; }
.wiz-guide-list li { font-size: 13px; color: var(--u-text); line-height: 1.5; }
.wiz-rule-pills { display: flex; flex-wrap: wrap; gap: 6px; }
.wiz-rule-pill {
    font-size: 11px; color: #7c3aed; background: rgba(124,58,237,.08);
    border: 1px solid rgba(124,58,237,.2); border-radius: 999px; padding: 3px 10px; line-height: 1.4;
}

@media (max-width: 900px) {
    .wizard-grid { grid-template-columns: 1fr; }
    .wizard-fields .row2 { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .db-nav { flex-direction: column; align-items: flex-start; }
    .db-kpi-strip { gap: 10px; }
}
    </style>
@endpush

@section('content')
@php
    $builderCount     = ($builderDocuments ?? collect())->count();
    $cvCount          = ($builderDocuments ?? collect())->filter(fn($d) => collect(is_array($d->process_tags) ? $d->process_tags : [])->contains('cv'))->count();
    $motivationCount  = ($builderDocuments ?? collect())->filter(fn($d) => collect(is_array($d->process_tags) ? $d->process_tags : [])->contains('motivation'))->count();
    $referenceCount   = ($builderDocuments ?? collect())->filter(fn($d) => collect(is_array($d->process_tags) ? $d->process_tags : [])->contains('reference'))->count();
    $motivationExists = trim((string) data_get($builderDraft ?? [], 'motivation_text', '')) !== '';
    $referenceExists  = trim((string) data_get($builderDraft ?? [], 'reference_teacher_contact', '')) !== '';

    $motivationGuideQuestions = [
        'Kendini kısa tanıt: şu an nerede yaşıyorsun, akademik geçmişin nedir?',
        'Hangi karakter özelliklerın seni bu programa uygun yapıyor?',
        'En sevdiğin dersler hangileriydi ve neden?',
        'Bu bölümü/programı seçme nedenin ne?',
        'Almanya\'da eğitim alma motivasyonun ne?',
        'Dil seviyen nedir? Gelişim için neler yapıyorsun?',
        'Gelecek hedefin ne? Bu eğitimin sana hangi kapıları açmasını bekliyorsun?',
    ];
    $motivationQualityRules = [
        'Çıktı dili her zaman Almanca (DE) üretilir.',
        'Kişisel örnekler kullanılabilir; sahte/abartılı bilgi girilmemeli.',
        'Tarih, okul, program ve seviyeler net yazılmalı.',
        'Kısa ve net bloklar; tekrar eden cümleleri temizleyin.',
        'Mektup 1 sayfayı aşmamalı.',
    ];
    $referenceGuideQuestions = [
        'A) Referans verenin adı, ünvanı, kurumu ve iletişim bilgileri?',
        'B) Bu kişi sizi ne kadar süredir, hangi bağlamda tanıyor?',
        'C) Akademik başarı düzeyiniz (not/sınıf sıralaması)?',
        'D) Sizi tanımlayan 3 temel özellik ve somut dayanakları?',
        'E) Sınıf içi tutumunuz, merakınız nasıl bir fark yaratıyordu?',
        'F) Somut proje/ödev/sunum örneği var mı?',
        'G) Takım çalışması ve liderlikte rolünüz?',
        'H) Zorluklar karşısında nasıl davrandığınızı gösteren bir örnek?',
        'I) Hedef bölüme/Almanya\'ya neden uygun olduğunuzu nasıl açıklar?',
        'J) Kapanışta tavsiye düzeyi (güçlü/şiddetle vb.)?',
    ];
    $referenceQualityRules = [
        'Referans mektubu referans veren kişinin gözlem diliyle yazılmalı.',
        'Genel övgü yerine somut gözlem + örnek olay tercih edilmeli.',
        'Abartılı veya doğrulanamaz iddialardan kaçınılmalı.',
        'Çıktı dili tercihen Almanca (DE) kullanılmalı.',
        'Ünvan, kurum ve iletişim bilgisi eksik bırakılmamalı.',
    ];
@endphp

{{-- ── Nav: modern card ── --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:14px;overflow:hidden;margin-bottom:14px;">

    {{-- Gradient header --}}
    <div style="background:linear-gradient(135deg,#7c3aed,#6d28d9);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <div style="font-size:var(--tx-base);font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;">
                📄 CV / Doküman Asistanı
            </div>
            <div style="font-size:var(--tx-xs);color:rgba(255,255,255,.75);margin-top:3px;">
                Profesyonel Almanca CV · Motivasyon Mektubu · Referans Mektubu
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @if($motivationExists || $referenceExists)
            <span style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:5px 12px;font-size:var(--tx-xs);font-weight:700;">
                {{ $motivationExists ? '✉️' : '' }}{{ $referenceExists ? ' 👤' : '' }} Taslak var
            </span>
            @endif
            <a href="{{ url('/student/documents') }}"
               style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                📂 Belgelerim
            </a>
        </div>
    </div>

    {{-- Tabs + KPI row --}}
    <div style="padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        {{-- Tab pills --}}
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            @php
                $dbTabs = [
                    ['cv',           '📝', 'CV Modülü'],
                    ['motivation',   '✉️', 'Motivasyon'],
                    ['reference',    '👤', 'Referans'],
                    ['cover_letter', '📨', 'Başvuru Mektubu'],
                    ['sperrkonto',   '🏦', 'Sperrkonto'],
                    ['housing',      '🏠', 'Yurt Başvurusu'],
                    ['outputs',      '📂', 'Belgeler ('.$builderCount.')'],
                ];
            @endphp
            @foreach($dbTabs as [$tab, $icon, $label])
            <button
                class="db-tab{{ $tab === 'cv' ? ' active' : '' }}"
                data-dbtab="{{ $tab }}"
                style="border-radius:20px;padding:7px 15px;font-size:var(--tx-xs);font-weight:700;white-space:nowrap;display:inline-flex;align-items:center;gap:5px;">
                {{ $icon }} {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- KPI strip --}}
        <div style="display:flex;gap:0;border:1px solid var(--u-line);border-radius:10px;overflow:hidden;flex-shrink:0;">
            @foreach([
                ['CV','#7c3aed',$cvCount,'📝'],
                ['Motivasyon','#d97706',$motivationCount,'✉️'],
                ['Referans','#16a34a',$referenceCount,'👤'],
                ['Toplam','#64748b',$builderCount,'🎫'],
            ] as [$lbl,$clr,$val,$ico])
            <div style="text-align:center;padding:8px 14px;border-right:1px solid var(--u-line);background:var(--u-bg);">
                <div style="font-size:var(--tx-base);font-weight:800;color:{{ $clr }};line-height:1;">{{ $val }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;white-space:nowrap;">{{ $ico }} {{ $lbl }}</div>
            </div>
            @endforeach
            <div style="text-align:center;padding:8px 14px;background:var(--u-bg);">
                <div style="font-size:var(--tx-base);font-weight:800;color:var(--u-text);line-height:1;">
                    {{ $motivationExists ? '✓' : '—' }}
                </div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;white-space:nowrap;">✉️ Taslak</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Panel 1: CV (React Builder) ── --}}
<div class="db-panel active" id="panel-cv">
    <div id="student-cv-builder-root"></div>
</div>

{{-- ── Panel 2: Motivasyon Mektubu ── --}}
<div class="db-panel" id="panel-motivation">
    <div class="db-wiz-card">
        <div class="db-wiz-card-title">
            ✉️ Motivasyon Mektubu
            <button type="button" class="db-btn" id="mot-guide-btn" style="margin-left:auto;font-size:var(--tx-xs);padding:5px 10px;border-color:#a5b4fc;color:#4338ca;background:#eef2ff;">
                📋 Nasıl Yazılır?
            </button>
            <span style="font-size:var(--tx-xs);padding:3px 9px;border-radius:6px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-weight:700;">✨ AI · DE</span>
        </div>
        <div class="db-wiz-card-sub">
            Her soruyu Türkçe yanıtla → <strong>AI ile Almanca Üret</strong> → önizlemeyi gözden geçir → Kaydet.
        </div>

        <details class="wizard-example">
            <summary>
                <span>📄</span>
                <span class="ex-label">Anonim Örnek Motivasyon Mektubu <span class="ex-sub">— referans için aç</span></span>
                <span class="ex-chevron">▸</span>
            </summary>
            <div class="wizard-example-body">
                <div class="wizard-example-hint">💡 Kendi bilgilerini doldurup "AI ile Üret" butonuna bas.</div>
                <textarea readonly>Sehr geehrte Damen und Herren,

mein Name ist [Vorname Nachname], ich komme aus [Stadt, Land] und möchte mich für den Studiengang [Programmname] bewerben.

Schon während meiner Schulzeit habe ich besonderes Interesse an [Fach/Felder] entwickelt. Besonders [Lieblingsthemen/Projekte] haben meine Denkweise geprägt.

Ich habe mich für Deutschland entschieden, weil mich die Verbindung von wissenschaftlicher Qualität und praxisorientierter Ausbildung überzeugt.

Mit freundlichen Grüßen
[Vorname Nachname]</textarea>
            </div>
        </details>

        <div class="wizard-grid">
            <div class="wizard-fields">
                <div class="wizard-field row2">
                    <div>
                        <label>Hedef Program <span class="q-hint">· Almanca yazabilirsin</span></label>
                        <input type="text" id="mot-program" placeholder="örn: Informatik B.Sc." value="{{ data_get($builderDraft ?? [], 'target_program', '') }}">
                    </div>
                    <div>
                        <label>Dil Seviyesi</label>
                        <select id="mot-lang-level">
                            <option value="">Seçin</option>
                            @foreach(['A1','A2','B1','B2','C1','C2'] as $lvl)
                            <option value="{{ $lvl }}">{{ $lvl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="wizard-field">
                    <label>Akademik geçmişin <span class="q-hint">· Hangi okuldan mezun oldun?</span></label>
                    <textarea id="mot-background" rows="2" placeholder="örn: İstanbul'da Makine Mühendisliği 2. sınıf öğrencisiyim."></textarea>
                </div>
                <div class="wizard-field">
                    <label>Bu programı neden seçtin? <span class="q-hint">· Hangi deneyim bu kararı etkiledi?</span></label>
                    <textarea id="mot-why-program" rows="2" placeholder="örn: Yapay zeka ve veri bilimi beni çekiyor çünkü..."></textarea>
                </div>
                <div class="wizard-field">
                    <label>Güçlü yönlerin <span class="q-hint">· 2-3 özellik + somut kanıt</span></label>
                    <textarea id="mot-strengths" rows="2" placeholder="örn: Analitik düşünce — lise bitirme projesinde 50+ veri seti analiz ettim."></textarea>
                </div>
                <div class="wizard-field">
                    <label>Somut proje / deneyim</label>
                    <textarea id="mot-concrete" rows="2" placeholder="örn: Okul kulübünde mobil uygulama geliştirdik."></textarea>
                </div>
                <div class="wizard-field row2">
                    <div>
                        <label>Almanya'yı neden seçtin?</label>
                        <textarea id="mot-why-germany" rows="2" placeholder="örn: Mühendislik alanındaki dünya standartları..."></textarea>
                    </div>
                    <div>
                        <label>Kariyer hedefin</label>
                        <textarea id="mot-career-goal" rows="2" placeholder="örn: Yazılım mühendisi olmak..."></textarea>
                    </div>
                </div>
            </div>

            <div class="wizard-preview">
                <div class="wizard-preview-box">
                    <div class="wizard-preview-label">Almanca Taslak Önizleme</div>
                    <div class="wizard-ai-btns">
                        <button type="button" class="db-btn primary" id="mot-ai-btn" data-doc-type="motivation">✨ AI ile Üret</button>
                        <button type="button" class="db-btn" id="mot-tpl-btn" data-doc-type="motivation" data-tpl-only="1">📋 AI'sız</button>
                    </div>
                    <div class="wizard-loading" id="mot-loading">⏳ AI taslak oluşturuluyor...</div>
                    <div class="wizard-status" id="mot-status"></div>
                    <textarea class="wizard-preview-ta" id="mot-preview" data-preview-for="motivation"
                              style="margin-top:10px;" placeholder="Sorulara cevap verdikten sonra 'AI ile Üret' butonuna bas."></textarea>
                    <div class="wizard-save-wrap">
                        <form method="POST" action="{{ route('student.document-builder.generate') }}" id="mot-save-form">
                            @csrf
                            <input type="hidden" name="document_type" value="motivation">
                            <input type="hidden" name="language" value="de">
                            <input type="hidden" name="ai_mode" value="final_text">
                            <input type="hidden" name="target_program" id="mot-save-program">
                            <input type="hidden" name="final_text_content" id="mot-save-content">
                            <div style="display:flex;gap:6px;margin-bottom:6px;">
                                <select name="output_format" id="mot-format" style="flex:1;height:32px;font-size:12px;border-radius:7px;padding:0 8px;">
                                    <option value="docx">📄 DOCX (Word)</option>
                                    <option value="pdf">📕 PDF</option>
                                </select>
                                <button type="submit" class="db-btn primary" id="mot-save-btn" disabled style="flex:2;">
                                    💾 Kaydet ve İndir
                                </button>
                            </div>
                        </form>
                        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;">Taslak oluştuktan sonra aktif olur.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Panel 3: Referans Mektubu ── --}}
<div class="db-panel" id="panel-reference">
    <div class="db-wiz-card">
        <div class="db-wiz-card-title">
            👤 Referans Mektubu
            <button type="button" class="db-btn" id="ref-guide-btn" style="margin-left:auto;font-size:var(--tx-xs);padding:5px 10px;border-color:#a5b4fc;color:#4338ca;background:#eef2ff;">
                📋 Nasıl Yazılır?
            </button>
            <span style="font-size:var(--tx-xs);padding:3px 9px;border-radius:6px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-weight:700;">✨ AI · DE</span>
        </div>
        <div class="db-wiz-card-sub">
            Referans veren öğretmenin bilgilerini gir → <strong>AI ile Almanca Üret</strong> → gözden geçir → Kaydet.
        </div>

        <details class="wizard-example">
            <summary>
                <span>📄</span>
                <span class="ex-label">Anonim Örnek Referans Mektubu <span class="ex-sub">— referans için aç</span></span>
                <span class="ex-chevron">▸</span>
            </summary>
            <div class="wizard-example-body">
                <div class="wizard-example-hint">💡 Sol alanları doldur, sonra "AI ile Üret" butonuna bas.</div>
                <textarea readonly>Empfehlungsschreiben

[Name der empfehlenden Person]
[Funktion / Titel], [Schule / Institution]

Sehr geehrte Damen und Herren,

es ist mir eine große Freude, [Vorname Nachname] für das Studium im Bereich [Studiengang] zu empfehlen.

[Vorname] zeichnet sich insbesondere durch [Stärke 1], [Stärke 2] und [Stärke 3] aus.

Ich empfehle [Vorname Nachname] daher uneingeschränkt.

Mit freundlichen Grüßen
[Name der empfehlenden Person]</textarea>
            </div>
        </details>

        <div class="wizard-grid">
            <div class="wizard-fields">
                <div class="db-section-sep">Referans Veren Kişi</div>
                <div class="wizard-field row2">
                    <div>
                        <label>Ad Soyad</label>
                        <input type="text" id="ref-name" placeholder="Prof. Dr. Ahmet Yılmaz" value="{{ data_get($builderDraft ?? [], 'ref_name', '') }}">
                    </div>
                    <div>
                        <label>Ünvan / Görev</label>
                        <input type="text" id="ref-title" placeholder="Matematik Öğretmeni" value="{{ data_get($builderDraft ?? [], 'ref_title', '') }}">
                    </div>
                </div>
                <div class="wizard-field row2">
                    <div>
                        <label>Kurum / Okul</label>
                        <input type="text" id="ref-institution" placeholder="Özel ABC Lisesi">
                    </div>
                    <div>
                        <label>E-posta / Telefon</label>
                        <input type="text" id="ref-contact" placeholder="ahmet@school.edu">
                    </div>
                </div>
                <div class="db-section-sep">Öğrenci Hakkında Gözlemler</div>
                <div class="wizard-field">
                    <label>Tanışma süresi ve bağlamı</label>
                    <textarea id="ref-how-long" rows="2" placeholder="örn: 2 yıldır Matematik dersimde öğrencim."></textarea>
                </div>
                <div class="wizard-field">
                    <label>Akademik performans <span class="q-hint">· Not, sınıf sıralaması</span></label>
                    <textarea id="ref-academic" rows="2" placeholder="örn: Genel notu 90/100 üzeri. Sınıfın ilk 5'inde."></textarea>
                </div>
                <div class="wizard-field">
                    <label>3 temel özellik + somut kanıt</label>
                    <textarea id="ref-strengths" rows="3" placeholder="örn: 1) Analitik düşünce — olimpiyatta 2. oldu. 2) Sorumluluk — teslim tarihi hiç geçirmedi."></textarea>
                </div>
                <div class="wizard-field row2">
                    <div>
                        <label>Somut proje / sunum örneği</label>
                        <textarea id="ref-example" rows="2" placeholder="örn: Fizik fuarında bireysel proje, birincilik."></textarea>
                    </div>
                    <div>
                        <label>Takım çalışması / liderlik</label>
                        <textarea id="ref-teamwork" rows="2" placeholder="örn: Grup çalışmalarında çatışmaları çözüyor."></textarea>
                    </div>
                </div>
                <div class="wizard-field">
                    <label>Tavsiye düzeyi</label>
                    <select id="ref-recommendation">
                        <option value="">Seçin</option>
                        <option value="Şiddetle tavsiye ediyorum (en yüksek düzey)">Şiddetle tavsiye ediyorum (en yüksek)</option>
                        <option value="Güçlü şekilde tavsiye ediyorum">Güçlü şekilde tavsiye ediyorum</option>
                        <option value="Tavsiye ediyorum">Tavsiye ediyorum</option>
                    </select>
                </div>
            </div>

            <div class="wizard-preview">
                <div class="wizard-preview-box">
                    <div class="wizard-preview-label">Almanca Taslak Önizleme</div>
                    <div class="wizard-ai-btns">
                        <button type="button" class="db-btn primary" id="ref-ai-btn" data-doc-type="reference">✨ AI ile Üret</button>
                        <button type="button" class="db-btn" id="ref-tpl-btn" data-doc-type="reference" data-tpl-only="1">📋 AI'sız</button>
                    </div>
                    <div class="wizard-loading" id="ref-loading">⏳ AI taslak oluşturuluyor...</div>
                    <div class="wizard-status" id="ref-status"></div>
                    <textarea class="wizard-preview-ta" id="ref-preview" data-preview-for="reference"
                              style="margin-top:10px;" placeholder="Bilgileri girdikten sonra 'AI ile Üret' butonuna bas."></textarea>
                    <div class="wizard-save-wrap">
                        <form method="POST" action="{{ route('student.document-builder.generate') }}" id="ref-save-form">
                            @csrf
                            <input type="hidden" name="document_type" value="reference">
                            <input type="hidden" name="language" value="de">
                            <input type="hidden" name="ai_mode" value="final_text">
                            <input type="hidden" name="final_text_content" id="ref-save-content">
                            <div style="display:flex;gap:6px;margin-bottom:6px;">
                                <select name="output_format" id="ref-format" style="flex:1;height:32px;font-size:12px;border-radius:7px;padding:0 8px;">
                                    <option value="docx">📄 DOCX (Word)</option>
                                    <option value="pdf">📕 PDF</option>
                                </select>
                                <button type="submit" class="db-btn primary" id="ref-save-btn" disabled style="flex:2;">
                                    💾 Kaydet ve İndir
                                </button>
                            </div>
                        </form>
                        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;">Taslak oluştuktan sonra aktif olur.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Panel 4: Başvuru / Anschreiben ── --}}
<div class="db-panel" id="panel-cover_letter">
    <div class="db-wiz-card">
        <div class="db-wiz-card-title">
            📨 Başvuru Mektubu (Anschreiben)
            <span style="font-size:var(--tx-xs);padding:3px 9px;border-radius:6px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-weight:700;">✨ AI · DE</span>
        </div>
        <div class="db-wiz-card-sub">
            İş veya program başvurusu için Almanca mektup oluştur.
        </div>
        <div class="wizard-grid">
            <div class="wizard-fields">
                <div class="db-section-sep">Başvuru Bilgileri</div>
                <div class="wizard-field row2">
                    <div><label>Başvurulan Pozisyon / Program</label>
                        <input type="text" id="cl-position" placeholder="örn: Masterstudiengang Informatik"></div>
                    <div><label>Kurum / Şirket Adı</label>
                        <input type="text" id="cl-company" placeholder="örn: TU München"></div>
                </div>
                <div class="db-section-sep">Kişisel Bilgi</div>
                <div class="wizard-field row2">
                    <div><label>Ad Soyad</label>
                        <input type="text" id="cl-name" placeholder="{{ $guest?->first_name }} {{ $guest?->last_name }}" value="{{ $guest?->first_name }} {{ $guest?->last_name }}"></div>
                    <div><label>Şehir (yazışma şehri)</label>
                        <input type="text" id="cl-city" placeholder="Istanbul" value="{{ $guest?->target_city ?? '' }}"></div>
                </div>
                <div class="wizard-field">
                    <label>Başvuru gerekçesi ve güçlü yönler</label>
                    <textarea id="cl-motivation" rows="4" placeholder="Bu pozisyona neden başvuruyorsunuz? Hangi deneyimleriniz uygun?"></textarea>
                </div>
                <div class="wizard-field">
                    <label>Ek notlar (opsiyonel)</label>
                    <textarea id="cl-notes" rows="2" placeholder="Başka eklemek istediğiniz bilgiler"></textarea>
                </div>
            </div>
            <div class="wizard-preview">
                <div class="wizard-preview-box">
                    <div class="wizard-preview-label">Almanca Taslak Önizleme</div>
                    <div class="wizard-ai-btns">
                        <button type="button" class="db-btn primary" id="cl-ai-btn" data-doc-type="cover_letter">✨ AI ile Üret</button>
                        <button type="button" class="db-btn" id="cl-tpl-btn" data-doc-type="cover_letter" data-tpl-only="1">📋 AI'sız</button>
                    </div>
                    <div class="wizard-loading" id="cl-loading">⏳ Oluşturuluyor...</div>
                    <div class="wizard-status" id="cl-status"></div>
                    <textarea class="wizard-preview-ta" id="cl-preview" data-preview-for="cover_letter"
                              style="margin-top:10px;" placeholder="Bilgileri girdikten sonra 'AI ile Üret' butonuna bas."></textarea>
                    <div class="wizard-save-wrap">
                        <form method="POST" action="{{ route('student.document-builder.generate') }}" id="cl-save-form">
                            @csrf
                            <input type="hidden" name="document_type" value="cover_letter">
                            <input type="hidden" name="language" value="de">
                            <input type="hidden" name="ai_mode" value="final_text">
                            <input type="hidden" name="final_text_content" id="cl-save-content">
                            <div style="display:flex;gap:6px;margin-bottom:6px;">
                                <select name="output_format" style="flex:1;height:32px;font-size:12px;border-radius:7px;padding:0 8px;">
                                    <option value="docx">📄 DOCX</option>
                                    <option value="pdf">📕 PDF</option>
                                </select>
                                <button type="submit" class="db-btn primary" id="cl-save-btn" disabled style="flex:2;">💾 Kaydet ve İndir</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Panel 5: Sperrkonto (Bloke Hesap Başvurusu) ── --}}
<div class="db-panel" id="panel-sperrkonto">
    <div class="db-wiz-card">
        <div class="db-wiz-card-title">🏦 Sperrkonto-Antrag (Bloke Hesap Başvurusu)</div>
        <div class="db-wiz-card-sub">
            Almanya'da öğrenci vizesi için gerekli bloke hesap başvuru mektubu.
        </div>
        <div class="wizard-grid">
            <div class="wizard-fields">
                <div class="db-section-sep">Kişisel Bilgiler</div>
                <div class="wizard-field row2">
                    <div><label>Ad Soyad</label>
                        <input type="text" id="sk-name" value="{{ $guest?->first_name }} {{ $guest?->last_name }}"></div>
                    <div><label>Doğum Tarihi</label>
                        <input type="text" id="sk-birth" placeholder="TT.MM.JJJJ"></div>
                </div>
                <div class="wizard-field row2">
                    <div><label>Pasaport No</label>
                        <input type="text" id="sk-passport" placeholder="U 00000000"></div>
                    <div><label>Adres (Türkiye)</label>
                        <input type="text" id="sk-address" placeholder="Musterstr. 1, Istanbul"></div>
                </div>
                <div class="db-section-sep">Banka Bilgileri</div>
                <div class="wizard-field row2">
                    <div><label>Banka Adı</label>
                        <input type="text" id="sk-bank" placeholder="Cortal Consors / Deutsche Bank"></div>
                    <div><label>Hesap Tutarı (€)</label>
                        <input type="text" id="sk-amount" placeholder="11.208 €" value="11.208 €"></div>
                </div>
                <div class="db-section-sep">Üniversite Bilgisi</div>
                <div class="wizard-field row2">
                    <div><label>Üniversite</label>
                        <input type="text" id="sk-university" placeholder="Technische Universität Berlin"></div>
                    <div><label>Başlangıç Dönemi</label>
                        <input type="text" id="sk-semester" placeholder="Wintersemester 2026/27"></div>
                </div>
                <div class="wizard-field">
                    <label>Ek Notlar</label>
                    <textarea id="sk-notes" rows="2" placeholder="Varsa ek bilgiler..."></textarea>
                </div>
            </div>
            <div class="wizard-preview">
                <div class="wizard-preview-box">
                    <div class="wizard-preview-label">Almanca Mektup Önizleme</div>
                    <div class="wizard-ai-btns">
                        <button type="button" class="db-btn primary" id="sk-tpl-btn" data-doc-type="sperrkonto" data-tpl-only="1">📋 Şablon ile Oluştur</button>
                    </div>
                    <div class="wizard-status" id="sk-status"></div>
                    <textarea class="wizard-preview-ta" id="sk-preview" style="margin-top:10px;"
                              placeholder="Bilgileri girdikten sonra 'Şablon ile Oluştur' butonuna bas."></textarea>
                    <div class="wizard-save-wrap">
                        <form method="POST" action="{{ route('student.document-builder.generate') }}" id="sk-save-form">
                            @csrf
                            <input type="hidden" name="document_type" value="sperrkonto">
                            <input type="hidden" name="language" value="de">
                            <input type="hidden" name="ai_mode" value="final_text">
                            <input type="hidden" name="final_text_content" id="sk-save-content">
                            <div style="display:flex;gap:6px;margin-bottom:6px;">
                                <select name="output_format" style="flex:1;height:32px;font-size:12px;border-radius:7px;padding:0 8px;">
                                    <option value="docx">📄 DOCX</option>
                                    <option value="pdf">📕 PDF</option>
                                </select>
                                <button type="submit" class="db-btn primary" id="sk-save-btn" disabled style="flex:2;">💾 Kaydet ve İndir</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Panel 6: Yurt Başvurusu (Wohnheimsantrag) ── --}}
<div class="db-panel" id="panel-housing">
    <div class="db-wiz-card">
        <div class="db-wiz-card-title">🏠 Wohnheimsantrag (Yurt Başvurusu)</div>
        <div class="db-wiz-card-sub">
            Üniversite yurdu (Studentenwohnheim) başvurusu için Almanca mektup.
        </div>
        <div class="wizard-grid">
            <div class="wizard-fields">
                <div class="db-section-sep">Kişisel Bilgiler</div>
                <div class="wizard-field row2">
                    <div><label>Ad Soyad</label>
                        <input type="text" id="hw-name" value="{{ $guest?->first_name }} {{ $guest?->last_name }}"></div>
                    <div><label>Doğum Tarihi</label>
                        <input type="text" id="hw-birth" placeholder="TT.MM.JJJJ"></div>
                </div>
                <div class="wizard-field row2">
                    <div><label>E-posta</label>
                        <input type="email" id="hw-email" placeholder="{{ $guest?->email ?? '' }}" value="{{ $guest?->email ?? '' }}"></div>
                    <div><label>Telefon</label>
                        <input type="text" id="hw-phone" value="{{ $guest?->phone ?? '' }}"></div>
                </div>
                <div class="db-section-sep">Başvuru Detayları</div>
                <div class="wizard-field row2">
                    <div><label>Üniversite / Şehir</label>
                        <input type="text" id="hw-university" placeholder="TU Dresden, Dresden"></div>
                    <div><label>Başlangıç Tarihi</label>
                        <input type="text" id="hw-start" placeholder="01.10.2026"></div>
                </div>
                <div class="wizard-field">
                    <label>Bütçe / Tercihler</label>
                    <textarea id="hw-preferences" rows="2" placeholder="örn: Maks. 350€/ay, tek kişilik oda tercih edilir."></textarea>
                </div>
                <div class="wizard-field">
                    <label>Ek Açıklama</label>
                    <textarea id="hw-notes" rows="2" placeholder="Varsa ek bilgiler..."></textarea>
                </div>
            </div>
            <div class="wizard-preview">
                <div class="wizard-preview-box">
                    <div class="wizard-preview-label">Almanca Mektup Önizleme</div>
                    <div class="wizard-ai-btns">
                        <button type="button" class="db-btn primary" id="hw-ai-btn" data-doc-type="housing">✨ AI ile Üret</button>
                        <button type="button" class="db-btn" id="hw-tpl-btn" data-doc-type="housing" data-tpl-only="1">📋 AI'sız</button>
                    </div>
                    <div class="wizard-loading" id="hw-loading">⏳ Oluşturuluyor...</div>
                    <div class="wizard-status" id="hw-status"></div>
                    <textarea class="wizard-preview-ta" id="hw-preview" style="margin-top:10px;"
                              placeholder="Bilgileri girdikten sonra butona bas."></textarea>
                    <div class="wizard-save-wrap">
                        <form method="POST" action="{{ route('student.document-builder.generate') }}" id="hw-save-form">
                            @csrf
                            <input type="hidden" name="document_type" value="housing">
                            <input type="hidden" name="language" value="de">
                            <input type="hidden" name="ai_mode" value="final_text">
                            <input type="hidden" name="final_text_content" id="hw-save-content">
                            <div style="display:flex;gap:6px;margin-bottom:6px;">
                                <select name="output_format" style="flex:1;height:32px;font-size:12px;border-radius:7px;padding:0 8px;">
                                    <option value="docx">📄 DOCX</option>
                                    <option value="pdf">📕 PDF</option>
                                </select>
                                <button type="submit" class="db-btn primary" id="hw-save-btn" disabled style="flex:2;">💾 Kaydet ve İndir</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Panel 7: Oluşturulan Belgeler ── --}}
<div class="db-panel" id="panel-outputs">
    <div class="db-wiz-card">
        <div class="db-wiz-card-title">📂 Oluşturulan Belgeler (Builder)</div>
        @forelse(($builderDocuments ?? collect()) as $doc)
        @php
            $tags = collect(is_array($doc->process_tags) ? $doc->process_tags : []);
            $type = $tags->first(fn($t) => in_array((string)$t, ['cv','motivation','reference'], true)) ?: '-';
            $lang = $tags->first(fn($t) => in_array((string)$t, ['tr','de','en'], true)) ?: '-';
            $name = (string) ($doc->standard_file_name ?: $doc->original_file_name ?: ('#'.$doc->id));
            $typeIcon = ['cv'=>'📝','motivation'=>'✉️','reference'=>'👤'][$type] ?? '📄';
        @endphp
        <div class="db-output-item">
            <div class="db-output-icon">{{ $typeIcon }}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">{{ $name }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">
                    Tip: <strong>{{ $type }}</strong> · Dil: {{ strtoupper($lang) }} · Durum: {{ $doc->status ?: '-' }}
                </div>
            </div>
            @if(!empty($doc->storage_path))
            <a class="db-btn" style="font-size:var(--tx-xs);padding:6px 12px;"
               href="{{ route('student.registration.documents.download', $doc->id) }}">⬇ İndir</a>
            @endif
        </div>
        @empty
        <div style="text-align:center;padding:40px 20px;color:var(--u-muted);">
            <div style="font-size:36px;margin-bottom:8px;">📄</div>
            <div style="font-weight:700;margin-bottom:4px;">Henüz belge oluşturulmamış</div>
            <div style="font-size:var(--tx-xs);">CV, Motivasyon veya Referans sekmesinden belge oluşturun.</div>
        </div>
        @endforelse
    </div>
</div>

{{-- ── Motivasyon Modal ── --}}
<div class="wiz-modal-overlay" id="modal-mot-guide">
    <div class="wiz-modal">
        <div class="wiz-modal-head">
            <h4>📋 Motivasyon Mektubu — Nasıl Yazılır?</h4>
            <button type="button" class="wiz-modal-close" data-close-modal="modal-mot-guide">×</button>
        </div>
        <div class="wiz-modal-body">
            <div class="wiz-modal-section-title">Yanıtlaman Gereken Sorular</div>
            <ol class="wiz-guide-list">
                @foreach($motivationGuideQuestions as $q)<li>{{ $q }}</li>@endforeach
            </ol>
            <div class="wiz-modal-section-title">Kalite Kuralları</div>
            <div class="wiz-rule-pills">
                @foreach($motivationQualityRules as $r)<span class="wiz-rule-pill">{{ $r }}</span>@endforeach
            </div>
        </div>
    </div>
</div>

{{-- ── Referans Modal ── --}}
<div class="wiz-modal-overlay" id="modal-ref-guide">
    <div class="wiz-modal">
        <div class="wiz-modal-head">
            <h4>📋 Referans Mektubu — Nasıl Yazılır?</h4>
            <button type="button" class="wiz-modal-close" data-close-modal="modal-ref-guide">×</button>
        </div>
        <div class="wiz-modal-body">
            <div class="wiz-modal-section-title">Doldurmam Gereken Bilgiler (A → J)</div>
            <ol class="wiz-guide-list">
                @foreach($referenceGuideQuestions as $q)<li>{{ $q }}</li>@endforeach
            </ol>
            <div class="wiz-modal-section-title">Kalite Kuralları</div>
            <div class="wiz-rule-pills">
                @foreach($referenceQualityRules as $r)<span class="wiz-rule-pill">{{ $r }}</span>@endforeach
            </div>
        </div>
    </div>
</div>

<script>
window.__STUDENT_CV_BUILDER__ = @json(array_merge($documentBuilderBridge ?? [], ['csrfToken' => csrf_token()]));
window.__DOC_BUILDER_CFG__ = {
    csrfToken: '{{ csrf_token() }}',
    aiDraftUrl: '{{ route('student.document-builder.ai-draft') }}',
};

// ── Tab switching ──────────────────────────────────────────
document.querySelectorAll('.db-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        var target = this.dataset.dbtab;
        document.querySelectorAll('.db-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.db-panel').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        var panel = document.getElementById('panel-' + target);
        if (panel) panel.classList.add('active');
    });
});

// ── Modal helpers ──────────────────────────────────────────
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.getElementById('mot-guide-btn')?.addEventListener('click', () => openModal('modal-mot-guide'));
document.getElementById('ref-guide-btn')?.addEventListener('click', () => openModal('modal-ref-guide'));
document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
});
document.querySelectorAll('.wiz-modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });
});
</script>
<script defer src="{{ Vite::asset('resources/js/senior-document-builder.js') }}"></script>
<script defer src="{{ Vite::asset('resources/js/document-builder-ai.js') }}"></script>
@endsection

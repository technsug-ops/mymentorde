@extends('senior.layouts.app')

@section('title', 'CV / Doküman Asistani')
@section('page_title', 'CV / Doküman Asistani')

@push('head')
    @vite('resources/js/student-document-builder.jsx')
    <style>
        #student-cv-builder-root { min-height: 720px; }
        .student-cv-builder-shell {
            border: 1px solid var(--u-line);
            border-radius: 14px;
            overflow: hidden;
            background: var(--u-bg);
        }
        .doc-hero {
            border: 1px solid var(--u-line);
            border-radius: 14px;
            background: var(--u-card);
            padding: 14px;
            margin-bottom: 12px;
        }
        .doc-hero h2 { margin: 0; font-size: 22px; color: var(--u-text); }
        .doc-hero p { margin: 6px 0 0; color: var(--u-muted); font-size: 13px; }
        .doc-overview-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-top:12px; }
        .doc-overview-box { border:1px solid var(--u-line); border-radius:10px; background:var(--u-card); padding:10px; }
        .doc-overview-box .v { font-size: 24px; font-weight: 800; line-height: 1.1; color:var(--u-text); }
        .doc-overview-box .k { font-size: 12px; color:var(--u-muted); }
        .doc-grid { display:grid; grid-template-columns:1.15fr .85fr; gap:12px; margin-top:12px; }
        .doc-card { border:1px solid var(--u-line); border-radius:12px; background:var(--u-card); padding:12px; }
        .doc-card h3 { margin:0 0 8px; font-size:18px; }
        .doc-muted { color:var(--u-muted); font-size:12px; }
        .doc-fields { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .doc-fields.single { grid-template-columns:1fr; }
        .doc-card input, .doc-card select, .doc-card textarea {
            width:100%; border:1px solid var(--u-line); border-radius:10px; padding:10px; background:var(--u-bg);
            font: inherit; color: var(--u-text);
        }
        .doc-card textarea { min-height:110px; resize:vertical; }
        .doc-pillbar { display:flex; gap:8px; flex-wrap:wrap; margin:0 0 10px; }
        .doc-pill { border:1px solid var(--u-line); border-radius:999px; padding:5px 10px; font-size:12px; background:var(--u-bg); color:var(--u-muted); }
        .doc-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .doc-btn {
            display:inline-flex; align-items:center; justify-content:center; gap:6px;
            border:1px solid var(--u-line); border-radius:10px; padding:8px 12px; background:var(--u-card); color:var(--u-text);
            font-weight:600; text-decoration:none; cursor:pointer;
        }
        .doc-btn.primary { background:#7c3aed; border-color:#7c3aed; color:#fff; }
        .doc-mini-list { max-height:260px; overflow:auto; border:1px solid var(--u-line); border-radius:10px; }
        .doc-mini-item { padding:10px; border-bottom:1px solid var(--u-line); }
        .doc-mini-item:last-child { border-bottom:none; }
        .doc-kpi { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:12px; }
        .doc-kpi .box { border:1px solid var(--u-line); border-radius:10px; background:var(--u-card); padding:10px; }
        .doc-module { margin-top:12px; border:1px solid var(--u-line); border-radius:12px; background:var(--u-card); overflow:hidden; }
        .doc-module summary {
            list-style:none; cursor:pointer; padding:12px; display:flex; justify-content:space-between; align-items:center; gap:10px;
            background:var(--u-bg); border-bottom:1px solid var(--u-line);
        }
        .doc-module summary::-webkit-details-marker { display:none; }
        .doc-module .head-left { display:flex; flex-direction:column; gap:3px; }
        .doc-module .head-title { font-size:16px; font-weight:700; color:var(--u-text); }
        .doc-module .head-desc { font-size:12px; color:var(--u-muted); }
        @media(max-width:600px){
            .doc-module summary { padding:10px; }
            .doc-module .head-title { font-size:13px; }
            .doc-module .head-desc { font-size:10px; }
            .doc-module .doc-pill { font-size:10px !important; padding:2px 8px !important; }
        }
        .doc-module .head-right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .doc-module-body { padding:12px; background:var(--u-card); }
        .doc-jumpbar { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
        .doc-jumpbar a, .doc-jumpbar button { text-decoration:none; }
        .doc-tab-btn.active { background:#7c3aed; border-color:#7c3aed; color:#fff; }
        .doc-tab-panel { display:none; }
        .doc-tab-panel.active { display:block; }
        .doc-section-gap { margin-top:12px; }
        @media (max-width: 1100px) {
            .doc-overview-grid { grid-template-columns:1fr 1fr; }
            .doc-grid { grid-template-columns:1fr; }
            .doc-fields { grid-template-columns:1fr; }
            .doc-kpi { grid-template-columns:1fr; }
        }
        @media (max-width: 640px) {
            .doc-overview-grid { grid-template-columns:1fr; }
        }
    </style>
@endpush

@section('content')
    @php
        $builderCount = ($builderDocuments ?? collect())->count();
        $motivationExists = trim((string) data_get($builderDraft ?? [], 'motivation_text', '')) !== '';
        $referenceExists = trim((string) data_get($builderDraft ?? [], 'reference_teacher_contact', '')) !== '';
        $cvCount = ($builderDocuments ?? collect())->filter(function ($d) {
            $tags = collect(is_array($d->process_tags) ? $d->process_tags : []);
            return $tags->contains('cv');
        })->count();
        $motivationCount = ($builderDocuments ?? collect())->filter(function ($d) {
            $tags = collect(is_array($d->process_tags) ? $d->process_tags : []);
            return $tags->contains('motivation');
        })->count();
        $referenceCount = ($builderDocuments ?? collect())->filter(function ($d) {
            $tags = collect(is_array($d->process_tags) ? $d->process_tags : []);
            return $tags->contains('reference');
        })->count();
        $motivationGuideQuestions = [
            'Kendini kısa tanıt: şu an nerede yaşıyorsun, akademik geçmişin nedir?',
            'Hangi karakter özellikleriniz seni bu programa uygun yapıyor? (liderlik, disiplin, yaratıcılık vb.)',
            'En sevdiğin dersler hangileriydi ve neden? Bu dersler düşünce yapını nasıl etkiledi?',
            'Bu bölümü/programı seçme nedenin ne? Hangi deneyimler/içerikler bu kararı etkiledi?',
            'Almanya\'da eğitim alma motivasyonun ne? (eğitim kalitesi, teori-pratik dengesi, kariyer hedefi)',
            'Dil seviyen nedir? Gelişim için neler yapıyorsun?',
            'Gelecek hedefin ne? Bu eğitimin sana hangi kapıları açmasını bekliyorsun?',
        ];
        $motivationQualityRules = [
            'Çıktı dili her zaman Almanca (DE) üretilir.',
            'Kişisel örnekler kullanılabilir; ancak sahte/abartılı bilgi girilmemelidir.',
            'Tarih, okul, program ve seviyeler net yazılmalı; belirsiz ifadelerden kaçının.',
            'Aynı paragrafta birden fazla fikir yığmak yerine kısa ve net bloklar kullanın.',
            'Mektup 1 sayfayı aşmamalı; tekrar eden cümleleri temizleyin.',
        ];
        $referenceGuideQuestions = [
            'A) Referans veren kişinin adı-soyadı, unvanı, kurumu ve iletişim bilgileri nedir?',
            'B) Bu kişi sizi ne kadar süredir tanıyor, hangi bağlamda (ders/proje/kulüp)?',
            'C) Akademik başarı düzeyiniz nasıl? (not/sınıf içi sıralama/genel performans)',
            'D) Sizi tanımlayan 3 temel özellik nedir ve bunların somut dayanakları neler?',
            'E) Sınıf içi tutumunuz, merakınız ve katılımınız nasıl bir fark yaratıyordu?',
            'F) Somut proje/ödev/sunum/lab çalışması örneği var mı? Kısa açıklayın.',
            'G) Takım çalışması ve liderlikte rolünüz neydi? (inisiyatif, kriz çözme, araştırma vb.)',
            'H) Zorluklar ve eleştiri karşısında nasıl davrandığınızı gösteren bir örnek var mı?',
            'I) Hedef bölüme / Almanya\'da eğitime neden uygun olduğunuzu referans kişisi nasıl açıklar?',
            'J) Kapanışta hangi derecede tavsiye edilmeli? (güçlü tavsiye / şiddetle tavsiye vb.)',
        ];
        $referenceQualityRules = [
            'Referans mektubu/özeti öğrencinin değil, referans veren kişinin gözlem diliyle yazılmalıdır.',
            'Genel övgü yerine somut gözlem + örnek olay tercih edilmelidir.',
            'Abartılı veya doğrulanamayacak iddialardan kaçınılmalıdır.',
            'Çıktı dili tercihen Almanca (DE) kullanılmalıdır.',
            'Unvan, kurum ve iletişim bilgisi eksik bırakılmamalıdır.',
        ];
    @endphp

    {{-- ── Hero Card ── --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:14px;overflow:hidden;margin-bottom:14px;">

        {{-- Gradient header strip --}}
        <div style="background:linear-gradient(135deg,#7c3aed,#6d28d9);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-size:var(--tx-base);font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;">
                    📄 Belge Üretim Merkezi
                </div>
                <div style="font-size:var(--tx-xs);color:rgba(255,255,255,.75);margin-top:3px;">
                    CV · Motivasyon Mektubu · Referans Mektubu — belgeler oluşturulunca belge merkezine kaydedilir.
                </div>
            </div>
            <a href="/senior/registration-documents"
               style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                📂 Belge Merkezi
            </a>
        </div>

        {{-- Student selector row --}}
        <div style="padding:14px 20px;border-bottom:1px solid var(--u-line);display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="display:block;font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                    🎓 Öğrenci Seçimi
                </label>
                <select id="seniorBuilderStudent"
                    style="width:100%;border:1px solid var(--u-line);border-radius:8px;padding:9px 12px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);font-family:inherit;cursor:pointer;">
                    @foreach(($students ?? collect()) as $student)
                        <option value="{{ $student->id }}" @selected((int)($selectedGuestId ?? 0) === (int)$student->id)>
                            {{ $student->converted_student_id }} — {{ $student->first_name }} {{ $student->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                    🔍 Arama
                </label>
                <input type="text" placeholder="ID / isim / email" oninput="filterSeniorStudents(this.value)"
                    style="width:100%;border:1px solid var(--u-line);border-radius:8px;padding:9px 12px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);font-family:inherit;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#7c3aed';this.style.boxShadow='0 0 0 3px rgba(124,58,237,.1)'"
                    onblur="this.style.borderColor='';this.style.boxShadow=''">
            </div>
        </div>

        {{-- Tabs + KPI --}}
        <div style="padding:12px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            {{-- Tab pills --}}
            <div class="doc-tabs-bar" style="display:flex;gap:6px;flex-wrap:wrap;">
                @php
                    $tabs = [
                        ['cv',         '📝', 'CV'],
                        ['motivation', '✉️', 'Motivasyon'],
                        ['reference',  '👤', 'Referans'],
                        ['outputs',    '📂', 'Belgeler ('.$builderCount.')'],
                    ];
                @endphp
                @foreach($tabs as [$target,$icon,$label])
                <button type="button"
                    class="doc-tab-btn{{ $target === 'cv' ? ' active' : '' }}"
                    data-doc-tab-target="{{ $target }}"
                    style="{{ $target === 'cv' ? 'background:#7c3aed;border-color:#7c3aed;color:#fff;' : 'background:var(--u-bg);border:1px solid var(--u-line);color:var(--u-muted);' }}border-radius:20px;padding:6px 14px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .15s;white-space:nowrap;">
                    {{ $icon }} {{ $label }}
                </button>
                @endforeach
            </div>

            {{-- KPI strip --}}
            <div class="doc-kpi-strip" style="display:flex;gap:0;border:1px solid var(--u-line);border-radius:10px;overflow:hidden;flex-shrink:0;">
                @foreach([
                    ['Toplam','#7c3aed',$builderCount,'🎫'],
                    ['CV','#7c3aed',$cvCount,'📝'],
                    ['Motivasyon','#d97706',$motivationCount,'✉️'],
                    ['Referans','#16a34a',$referenceCount,'👤'],
                ] as [$lbl,$clr,$val,$ico])
                <div style="text-align:center;padding:8px 14px;border-right:1px solid var(--u-line);background:var(--u-bg);flex:1;min-width:0;">
                    <div style="font-size:var(--tx-base);font-weight:800;color:{{ $clr }};line-height:1;">{{ $val }}</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ico }} {{ $lbl }}</div>
                </div>
                @endforeach
                <div class="hide-mobile" style="text-align:center;padding:8px 14px;background:var(--u-bg);">
                    <div style="font-size:var(--tx-base);font-weight:800;color:var(--u-text);line-height:1;">{{ $builderCount > 0 ? round(($cvCount/$builderCount)*100) : 0 }}%</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;white-space:nowrap;">📊 CV Oranı</div>
                </div>
            </div>
        </div>
    </div>

    <details class="doc-module doc-tab-panel active" id="module-cv" data-doc-tab-panel="cv" open>
        <summary>
            <div class="head-left">
                <div class="head-title">1) CV Modülü</div>
                <div class="head-desc">Profesyonel Almanca CV/Lebenslauf oluşturma akışı</div>
            </div>
            <div class="head-right">
                <span class="doc-pill">{{ $cvCount }} çıktı</span>
            </div>
        </summary>
        <div class="doc-module-body">
            <div class="student-cv-builder-shell">
                <div id="student-cv-builder-root"></div>
            </div>
        </div>
    </details>

    <section class="doc-card doc-tab-panel doc-section-gap" id="module-motivation" data-doc-tab-panel="motivation">
            <h3>Motivasyon Mektubu Üretimi (DE çıktı)</h3>
            <div class="doc-muted" style="margin-bottom:10px;">TR cevap/girdi kullanabilirsin; sistem mektubu her zaman Almanca oluşturur ve belge merkezine kaydeder.</div>

            <div class="doc-grid" style="margin-top:0; margin-bottom:12px;">
                <div class="doc-card" style="padding:10px;">
                    <h3 style="font-size:var(--tx-base); margin-bottom:6px;">TR Hazırlık Soruları (Yol Haritası)</h3>
                    <ol style="margin:0; padding-left:18px; display:grid; gap:5px;">
                        @foreach($motivationGuideQuestions as $q)
                            <li style="font-size:var(--tx-sm); color:#334155;">{{ $q }}</li>
                        @endforeach
                    </ol>
                </div>
                <div class="doc-card" style="padding:10px;">
                    <h3 style="font-size:var(--tx-base); margin-bottom:6px;">Anonim Örnek Yapı (DE)</h3>
                    <div class="doc-muted" style="margin-bottom:6px;">Aşağıdaki iskelet örneklerden anonimleştirilerek çıkarıldı.</div>
                    <textarea readonly style="min-height:220px; background:#f8fbff;">Sehr geehrte Damen und Herren,

mein Name ist [Vorname Nachname], ich komme aus [Stadt, Land] und mochte mich fur den Studiengang [Programmname] bewerben.

Schon wahrend meiner Schulzeit habe ich besonderes Interesse an [Fach/Felder] entwickelt. Besonders [Lieblingsfächer/Projekte] haben meine analytische und kreative Denkweise gepragt.

Ich habe mich fur Deutschland entschieden, weil mich die Verbindung von wissenschaftlicher Qualitat, praxisorientierter Ausbildung und internationalem Studienumfeld uberzeugt.

Mit diesem Studium mochte ich meine Kenntnisse in [Kompetenzfeld] vertiefen und mich langfristig in [Karriereziel] entwickeln.

Ich arbeite kontinuierlich an meinen Deutschkenntnissen und bin hoch motiviert, mich akademisch wie personlich weiterzuentwickeln.

Mit freundlichen Grussen
[Vorname Nachname]</textarea>
                </div>
            </div>

            <div class="doc-card" style="padding:10px; margin-bottom:12px;">
                <h3 style="font-size:var(--tx-base); margin-bottom:6px;">Kalite Kuralları</h3>
                <div class="doc-pillbar" style="margin-bottom:0;">
                    @foreach($motivationQualityRules as $rule)
                        <span class="doc-pill">{{ $rule }}</span>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('senior.document-builder.generate') }}">
                @csrf
                <input type="hidden" name="guest_application_id" class="senior-guest-id" value="{{ (int) ($selectedGuestId ?? 0) }}">
                <input type="hidden" name="document_type" value="motivation">
                <input type="hidden" name="language" value="de">
                <div class="doc-fields">
                    <div>
                        <label class="doc-muted">Çıktı Dili</label>
                        <input type="text" value="Almanca (DE) - Sabit" readonly>
                    </div>
                    <div>
                        <label class="doc-muted">Format</label>
                        <select name="output_format">
                            <option value="docx">DOCX (önerilen)</option>
                            <option value="md">Markdown</option>
                        </select>
                    </div>
                    <div>
                        <label class="doc-muted">AI Destek Modu</label>
                        <select name="ai_mode">
                            <option value="template">Template</option>
                            <option value="ai_assist">AI Assist</option>
                        </select>
                    </div>
                    <div>
                        <label class="doc-muted">Belge Başlığı (isteğe bağlı)</label>
                        <input type="text" name="title" placeholder="örn: Motivationsschreiben - Anonim Aday">
                    </div>
                    <div>
                        <label class="doc-muted">Hedef Program</label>
                        <input type="text" name="target_program" value="{{ old('target_program', data_get($builderDraft ?? [], 'target_program', '')) }}" placeholder="orn: Informatik B.Sc.">
                    </div>
                    <div>
                        <label class="doc-muted">Ek Notlar</label>
                        <input type="text" name="notes" placeholder="Tonlama, vurgulanacak güçler, özel not...">
                    </div>
                </div>
                <div class="doc-fields single" style="margin-top:10px;">
                    <div>
                        <label class="doc-muted">Motivasyon Metni (TR giriş / notlar)</label>
                        <textarea name="motivation_text" placeholder="Yukarıdaki sorulara cevaplarını Türkçe yaz. Sistem mektubu Almanca çıktırır.">{{ old('motivation_text', data_get($builderDraft ?? [], 'motivation_text', '')) }}</textarea>
                    </div>
                </div>
                <div class="doc-actions" style="margin-top:10px;">
                    <button type="submit" class="doc-btn primary">Motivasyon Mektubu Oluştur</button>
                    <a class="doc-btn" href="/senior/students">Kayıt Formuna Dön</a>
                </div>
            </form>
    </section>

    <section class="doc-card doc-tab-panel doc-section-gap" id="module-reference" data-doc-tab-panel="reference">
            <h3>Referans Mektubu Üretimi (DE çıktı)</h3>
            <div class="doc-muted" style="margin-bottom:10px;">Aşağıdaki TR sorulara göre notlarını hazırla; sistem referans mektubunu her zaman Almanca oluşturur ve belge merkezine kaydeder.</div>

            <div class="doc-grid" style="margin-top:0; margin-bottom:12px;">
                <div class="doc-card" style="padding:10px;">
                    <h3 style="font-size:var(--tx-base); margin-bottom:6px;">Referans Hazırlık Soruları (TR)</h3>
                    <ol style="margin:0; padding-left:18px; display:grid; gap:5px;">
                        @foreach($referenceGuideQuestions as $q)
                            <li style="font-size:var(--tx-sm); color:#334155;">{{ $q }}</li>
                        @endforeach
                    </ol>
                </div>
                <div class="doc-card" style="padding:10px;">
                    <h3 style="font-size:var(--tx-base); margin-bottom:6px;">Anonim Referans Mektubu İskeleti (DE)</h3>
                    <textarea readonly style="min-height:300px; background:#f8fbff;">Empfehlungsschreiben (Muster - anonymisiert)

Absender:
[Name der empfehlenden Person]
[Funktion / Titel], [Schule / Institution]
[E-Mail]
[Telefon]

Ort, Datum: [Stadt], [Datum]

An den Zulassungsausschuss der [Name der Universitat]

Betreff: Empfehlungsschreiben fur [Vorname Nachname]

Sehr geehrte Damen und Herren,

es ist mir eine große Freude, [Vorname Nachname] fur das Bachelorstudium im Bereich [Studiengang] an Ihrer Universitat zu empfehlen. Ich kenne [Vorname] seit [Zeitraum] als [Lehrer/in / Projektbetreuer/in] an [Institution]. In dieser Zeit habe ich [sie/ihn] als engagierte, analytische und zielorientierte Person kennengelernt.

[Vorname] zeichnet sich insbesondere durch [3 Kernstarken] aus. Diese Eigenschaften zeigen sich sowohl in der akademischen Leistung als auch im praktischen Arbeiten. Besonders bemerkenswert ist [konkretes Beispiel: Projekt / Aufgabe / Initiative], bei dem [Vorname] [kurze Beobachtung zu Verantwortung, Kreativitat oder Problemlosung].

Im Unterricht / Projektumfeld fiel [Vorname] durch [Neugier, aktive Beteiligung, kritisches Denken] auf. [Sie/Er] stellt weiterfuhrende Fragen, denkt interdisziplinar und versucht, theoretische Inhalte in reale Anwendungen zu ubertragen.

Auch in Gruppenarbeiten zeigte [Vorname] ein hohes Maß an Teamfahigkeit und Zuverlassigkeit. In anspruchsvollen Situationen reagierte [sie/er] reflektiert, lernbereit und losungsorientiert.

Aufgrund der fachlichen Eignung, der charakterlichen Reife und der hohen Motivation bin ich uberzeugt, dass [Vorname Nachname] ein Studium in Deutschland erfolgreich absolvieren und Ihre Fakultat bereichern wird.

Ich empfehle [Vorname Nachname] daher uneingeschrankt und mit voller Uberzeugung. Fur Ruckfragen stehe ich Ihnen gerne zur Verfugung.

Mit freundlichen Grussen

[Unterschrift]
[Name der empfehlenden Person]
[Titel / Position]
[Institution]</textarea>
                </div>
            </div>

            <div class="doc-card" style="padding:10px; margin-bottom:12px;">
                <h3 style="font-size:var(--tx-base); margin-bottom:6px;">Kalite Kuralları</h3>
                <div class="doc-pillbar" style="margin-bottom:0;">
                    @foreach($referenceQualityRules as $rule)
                        <span class="doc-pill">{{ $rule }}</span>
                    @endforeach
                </div>
            </div>
            <form method="POST" action="{{ route('senior.document-builder.generate') }}">
                @csrf
                <input type="hidden" name="guest_application_id" class="senior-guest-id" value="{{ (int) ($selectedGuestId ?? 0) }}">
                <input type="hidden" name="document_type" value="reference">
                <input type="hidden" name="language" value="de">
                <div class="doc-fields">
                    <div>
                        <label class="doc-muted">Çıktı Dili</label>
                        <input type="text" value="Almanca (DE) - Sabit" readonly>
                    </div>
                    <div>
                        <label class="doc-muted">Format</label>
                        <select name="output_format">
                            <option value="docx">DOCX (önerilen)</option>
                            <option value="md">Markdown</option>
                        </select>
                    </div>
                    <div>
                        <label class="doc-muted">Belge Başlığı (isteğe bağlı)</label>
                        <input type="text" name="title" placeholder="örn: Referenz Übersicht - Halil Demoreren">
                    </div>
                    <div>
                        <label class="doc-muted">Referans / Öğretmen İletişim Bilgisi</label>
                        <input type="text" name="reference_teacher_contact" value="{{ old('reference_teacher_contact', data_get($builderDraft ?? [], 'reference_teacher_contact', '')) }}" placeholder="Ad Soyad - gorev - email/telefon">
                    </div>
                </div>
                <div class="doc-fields single" style="margin-top:10px;">
                    <div>
                        <label class="doc-muted">Referans Notları / Gözlemler (TR giriş)</label>
                        <textarea name="notes" placeholder="Yukarıdaki A-J maddelerine göre notlarını yaz. Somut örnekler ve referans veren kişinin gözlemleri öncelikli olsun.">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="doc-actions" style="margin-top:10px;">
                    <button type="submit" class="doc-btn primary">Referans Mektubu Oluştur</button>
                    <a class="doc-btn" href="/im">Mesaj Merkezi</a>
                </div>
            </form>
    </section>

    <section class="panel doc-tab-panel" id="module-outputs" data-doc-tab-panel="outputs" style="margin-top:12px;">
        <h3 style="margin:0 0 8px;">Oluşturulan Belgeler (Builder)</h3>
        <div class="doc-mini-list">
            @forelse(($builderDocuments ?? collect()) as $doc)
                @php
                    $tags = collect(is_array($doc->process_tags) ? $doc->process_tags : []);
                    $type = $tags->first(fn($t) => in_array((string)$t, ['cv','motivation','reference'], true)) ?: '-';
                    $lang = $tags->first(fn($t) => in_array((string)$t, ['tr','de','en'], true)) ?: '-';
                    $name = (string) ($doc->standard_file_name ?: $doc->original_file_name ?: ('#'.$doc->id));
                @endphp
                <div class="doc-mini-item">
                    <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                        <div>
                            <strong>{{ $doc->document_id ?: ('#'.$doc->id) }}</strong>
                            <div>{{ $name }}</div>
                            <div class="doc-muted">tip: {{ $type }} | dil: {{ $lang }} | status: {{ $doc->status ?: '-' }}</div>
                        </div>
                        @if(!empty($doc->storage_path))
                            <a class="doc-btn" href="{{ route('senior.registration.documents.download', $doc->id) }}">Aç / İndir</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="doc-mini-item doc-muted">Henüz builder belgesi oluşturulmamış.</div>
            @endforelse
        </div>
    </section>

    <script>
    window.__STUDENT_CV_BUILDER__ = @json(array_merge($documentBuilderBridge ?? [], [
        'csrfToken' => csrf_token(),
    ]));
    </script>
    <script defer src="{{ Vite::asset('resources/js/senior-document-builder.js') }}"></script>
@endsection



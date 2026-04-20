{{-- ══════════════════════════════════════════════════════════════════════════
  Shared Document Guide partial — Guest + Student portals.
═══════════════════════════════════════════════════════════════════════════ --}}

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
.jm-minimalist .card[style*="gradient"],
.jm-minimalist .dg-hero,
.jm-minimalist .dg-cta {
    background: #e2e5ec !important;
    color: var(--u-text, #1a1a1a) !important;
    border: 1px solid rgba(0,0,0,.10) !important;
}
.jm-minimalist .card[style*="gradient"] [style*="opacity"] { color: var(--u-muted, #666) !important; opacity: 1 !important; }

/* ── Document Guide Hero (compact) ── */
.dg-hero {
    color: #fff; border-radius: 14px; margin-bottom: 20px; overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
}
.dg-hero-body {
    display: flex; align-items: center; gap: 24px; padding: 26px 28px;
}
.dg-hero-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px; }
.dg-hero-label {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 11px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
    opacity: .82;
}
.dg-hero-marker {
    display: inline-block; width: 5px; height: 16px;
    background: rgba(255,255,255,.75); border-radius: 3px;
}
.dg-hero-title {
    font-size: 32px; font-weight: 800; line-height: 1.1; margin: 0;
    letter-spacing: -.5px;
}
.dg-hero-overview {
    font-size: 14px; opacity: .92; line-height: 1.55;
    max-width: 600px; margin-top: 2px;
}
.dg-hero-stats {
    display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;
    padding-top: 12px; border-top: 1px solid rgba(255,255,255,.18);
}
.dg-hero-stat {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 20px;
    background: rgba(255,255,255,.18);
    font-size: 12px; font-weight: 600; line-height: 1;
    border: 1px solid rgba(255,255,255,.12);
}
.dg-hero-stat-ico { font-size: 13px; }
.dg-hero-icon {
    font-size: 72px; line-height: 1; flex-shrink: 0;
    opacity: .85; filter: drop-shadow(0 4px 12px rgba(0,0,0,.25));
}

/* ── En Sık Yapılan Hatalar ── */
.dg-mistake-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.dg-mistake {
    display: flex; gap: 12px; align-items: flex-start;
    padding: 14px 16px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-left: 3px solid var(--sev-color);
    border-radius: 10px;
    transition: transform .12s, box-shadow .12s, border-color .12s;
}
.dg-mistake:hover {
    transform: translateX(2px);
    box-shadow: 0 4px 14px rgba(0,0,0,.06);
    border-color: color-mix(in srgb, var(--sev-color) 35%, var(--u-line));
    border-left-color: var(--sev-color);
}
.dg-mistake-ico {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
    background: color-mix(in srgb, var(--sev-color) 10%, #fff);
    border: 1px solid color-mix(in srgb, var(--sev-color) 22%, transparent);
}
.dg-mistake-body { flex: 1; min-width: 0; }
.dg-mistake-head { display: flex; align-items: center; gap: 8px; margin-bottom: 3px; flex-wrap: wrap; }
.dg-mistake-title { font-weight: 700; font-size: 13.5px; color: var(--u-text); line-height: 1.25; }
.dg-mistake-chip {
    font-size: 9.5px; font-weight: 800; letter-spacing: .6px;
    padding: 2px 7px; border-radius: 4px; line-height: 1.3;
    color: var(--sev-color);
    background: color-mix(in srgb, var(--sev-color) 11%, transparent);
    flex-shrink: 0;
}
.dg-mistake-desc { font-size: 12px; color: var(--u-muted); line-height: 1.5; }

@media (max-width: 720px) {
    .dg-mistake-grid { grid-template-columns: 1fr; gap: 8px; }
    .dg-mistake { padding: 12px 13px; gap: 10px; }
    .dg-mistake-ico { width: 34px; height: 34px; font-size: 18px; border-radius: 9px; }
    .dg-mistake-title { font-size: 13px; }
    .dg-mistake-desc { font-size: 11.5px; }
}

/* ── Belge Türleri — visual cards ── */
.dg-doc-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
.dg-doc-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; overflow: hidden;
    transition: transform .15s, box-shadow .15s, border-color .15s;
    position: relative;
}
.dg-doc-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: var(--doc-color); border-radius: 14px 14px 0 0;
}
.dg-doc-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(0,0,0,.08);
    border-color: color-mix(in srgb, var(--doc-color) 40%, var(--u-line));
}
.dg-doc-head {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 18px 14px;
    background: color-mix(in srgb, var(--doc-color) 6%, transparent);
    border-bottom: 1px solid var(--u-line);
}
.dg-doc-icon {
    width: 42px; height: 42px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
    background: color-mix(in srgb, var(--doc-color) 15%, #fff);
    border: 1px solid color-mix(in srgb, var(--doc-color) 25%, transparent);
}
.dg-doc-meta { flex: 1; min-width: 0; }
.dg-doc-chip {
    display: inline-block; font-size: 9.5px; font-weight: 700;
    letter-spacing: .6px; text-transform: uppercase;
    padding: 2px 7px; border-radius: 4px; margin-bottom: 3px;
    color: var(--doc-color);
    background: color-mix(in srgb, var(--doc-color) 12%, transparent);
}
.dg-doc-title { font-size: 14.5px; font-weight: 800; color: var(--u-text); line-height: 1.2; }
.dg-doc-count {
    font-size: 11px; font-weight: 700; color: var(--doc-color);
    background: color-mix(in srgb, var(--doc-color) 12%, transparent);
    border-radius: 20px; padding: 4px 10px;
    flex-shrink: 0;
}
.dg-doc-list {
    list-style: none; padding: 8px 14px 14px; margin: 0;
    display: flex; flex-direction: column; gap: 2px;
}
.dg-doc-item {
    display: flex; gap: 10px; align-items: flex-start;
    padding: 8px 6px; border-radius: 8px;
    font-size: 13px; line-height: 1.45;
    transition: background .12s;
}
.dg-doc-item:hover { background: color-mix(in srgb, var(--doc-color) 5%, transparent); }
.dg-doc-num {
    flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800;
    color: var(--doc-color);
    background: color-mix(in srgb, var(--doc-color) 14%, #fff);
    border: 1.5px solid color-mix(in srgb, var(--doc-color) 30%, transparent);
    margin-top: 1px;
}
.dg-doc-text { color: var(--u-text); }

@media (max-width: 720px) {
    .dg-doc-grid { grid-template-columns: 1fr; gap: 12px; }
    .dg-doc-head { padding: 13px 14px 11px; }
    .dg-doc-icon { width: 38px; height: 38px; font-size: 20px; border-radius: 10px; }
    .dg-doc-title { font-size: 13.5px; }
    .dg-doc-list { padding: 6px 10px 12px; }
    .dg-doc-item { font-size: 12.5px; padding: 7px 5px; gap: 9px; }
    .dg-doc-num { width: 20px; height: 20px; font-size: 10.5px; }
}

@media (max-width: 720px) {
    .dg-hero { border-radius: 12px; }
    .dg-hero-body { gap: 14px; padding: 18px; align-items: flex-start; }
    .dg-hero-title { font-size: 22px; letter-spacing: -.3px; }
    .dg-hero-overview { font-size: 12.5px; line-height: 1.45; max-width: none; }
    .dg-hero-stats {
        gap: 6px; margin-top: 12px; padding-top: 12px;
        display: flex; flex-wrap: wrap;
    }
    .dg-hero-stat {
        padding: 4px 9px; font-size: 11px; gap: 4px;
        background: rgba(255,255,255,.16);
    }
    .dg-hero-stat-ico { font-size: 11px; }
    .dg-hero-icon { font-size: 42px; align-self: flex-start; margin-top: 2px; }
    .dg-hero-label { font-size: 10px; letter-spacing: .5px; }
    .dg-hero-marker { height: 12px; width: 3px; }
}

/* CTA */
.dg-cta {
    border-radius: 14px; color: #fff; margin-bottom: 8px;
    background: linear-gradient(135deg, #2563eb, #0891b2);
    padding: 20px 24px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 14px;
}
.dg-cta-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 20px; border-radius: 22px;
    background: #fff; color: #2563eb; font-weight: 700; font-size: 13.5px;
    text-decoration: none; box-shadow: 0 4px 14px rgba(0,0,0,.15);
    transition: transform .15s;
}
.dg-cta-btn:hover { transform: translateY(-1px); text-decoration: none; }
</style>
@endpush

@php
$dgIsStudent = request()->is('student/*');
$dgDocsRoute = $dgIsStudent ? route('student.registration.documents') : route('guest.registration.documents');
$dgDashboardRoute = $dgIsStudent ? route('student.dashboard') : route('guest.dashboard');
$dgUniRoute = $dgIsStudent ? route('student.info.university-guide') : route('guest.university-guide');
@endphp

{{-- Hero (compact + data-forward) --}}
<div class="dg-hero" style="background:linear-gradient(135deg,#2563eb,#0891b2);">
    <div class="dg-hero-body">
        <div class="dg-hero-main">
            <div class="dg-hero-label">
                <span class="dg-hero-marker"></span>
                Almanya Üniversite Başvurusu
            </div>
            <h1 class="dg-hero-title">Belge Hazırlama Rehberi</h1>
            <div class="dg-hero-overview">
                Eksiksiz ve doğru hazırlanmış belgeler başvurunu hızlandırır, kabul şansını doğrudan etkiler.
            </div>
            <div class="dg-hero-stats">
                <span class="dg-hero-stat"><span class="dg-hero-stat-ico">📑</span>4 belge türü</span>
                <span class="dg-hero-stat"><span class="dg-hero-stat-ico">⏱</span>~30 gün hazırlık</span>
                <span class="dg-hero-stat"><span class="dg-hero-stat-ico">✅</span>Kabul oranını artırır</span>
            </div>
        </div>
        <div class="dg-hero-icon">📋</div>
    </div>
</div>

{{-- Neden Önemli --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Neden Bu Kadar Önemli?</div>
<div class="col3" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">⏱️</div>
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:6px;">Süreç Hızlanır</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;">
                Eksiksiz başvurular 2–4 haftada değerlendirilir. Eksik belgeli başvurular aylarca askıda kalabilir ya da doğrudan reddedilir.
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">✅</div>
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:6px;">Kabul Şansı Artar</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;">
                Motivasyon mektubu ve CV kalitesi, akademik notlardan bağımsız olarak komisyon kararını doğrudan etkiler.
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">🛂</div>
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:6px;">Vize Kolaylaşır</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;">
                Konsolosluk başvuru paketini bütünlük açısından değerlendirir. Eksik veya tutarsız belgeler vize reddine yol açar.
            </div>
        </div>
    </div>
</div>

{{-- En Sık Yapılan Hatalar --}}
@php
    $sevMap = [
        'danger' => ['label' => 'KRİTİK',  'color' => '#dc2626'],
        'warn'   => ['label' => 'DİKKAT',  'color' => '#f59e0b'],
        'info'   => ['label' => 'BİLGİ',   'color' => '#0891b2'],
    ];
    $mistakes = [
        ['📜', 'Apostil eksikliği',         'Türkiye\'den alınan diplomalar için Dışişleri Bakanlığı apostili zorunludur. Notere gitmek yetmez.', 'danger'],
        ['📅', 'Eski tarihli belgeler',     'Bazı üniversiteler son 6 ay içinde düzenlenmiş belge ister. Tarihlere dikkat et.', 'warn'],
        ['🤖', 'Makine çevirisi kullanımı', 'Yeminli tercüman olmadan yapılan çeviriler kabul edilmez. Mutlaka resmi tercüman kullan.', 'danger'],
        ['📋', 'Genel motivasyon mektubu',  'Her üniversiteye aynı metni gönderme. Komisyon bunu hemen fark eder — özgün yaz.', 'warn'],
        ['🎨', 'CV format uyumsuzluğu',     'Almanya\'da Europass veya sade format tercih edilir. Renkli tasarım CV\'ler olumsuz karşılanır.', 'info'],
        ['✍️', 'Eksik imza veya mühür',     'Resmi belgelerde okul müdürü imzası veya kurumsal mühür olmadan belge geçersiz sayılabilir.', 'danger'],
    ];
@endphp
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">En Sık Yapılan Hatalar</div>
<div class="dg-mistake-grid" style="margin-bottom:24px;">
    @foreach($mistakes as [$ico, $title, $desc, $sev])
    @php $sv = $sevMap[$sev]; @endphp
    <div class="dg-mistake" style="--sev-color: {{ $sv['color'] }};">
        <div class="dg-mistake-ico">{{ $ico }}</div>
        <div class="dg-mistake-body">
            <div class="dg-mistake-head">
                <span class="dg-mistake-title">{{ $title }}</span>
                <span class="dg-mistake-chip">{{ $sv['label'] }}</span>
            </div>
            <div class="dg-mistake-desc">{{ $desc }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Belge Türleri --}}
@php
    $docTypes = [
        [
            'icon' => '📝', 'title' => 'Motivasyon Mektubu', 'color' => '#7c3aed', 'chip' => 'ZORUNLU',
            'items' => [
                'Neden bu program, neden bu üniversite — somut yanıtla',
                'Akademik geçmişin ile programın bağlantısını kur',
                'Gelecek hedeflerini kısa ve net anlat (1–1,5 sayfa)',
                'Almanca veya İngilizce — programa göre dil seç',
                'Ana dili konuşan birine okutmadan gönderme',
            ],
        ],
        [
            'icon' => '👤', 'title' => 'Özgeçmiş (Lebenslauf)', 'color' => '#2563eb', 'chip' => 'ZORUNLU',
            'items' => [
                'Europass veya sade tablo formatı kullan',
                'Fotoğraf ekle — Almanya\'da hâlâ beklenir',
                'Kronolojik sıra: en yeni deneyim en üstte',
                'Dil seviyeleri CEFR skalasıyla belirt (B2, C1...)',
                'Gönüllülük ve hobiler ekle — komisyon dikkat eder',
            ],
        ],
        [
            'icon' => '🎓', 'title' => 'Diploma & Transkript', 'color' => '#0891b2', 'chip' => 'ZORUNLU',
            'items' => [
                'Lise diplomanı apostil ile onaylat (Dışişleri Bakanlığı)',
                'Yeminli tercüman ile Almancaya çevirt',
                'Not döküm belgesi (transkript) de aynı işlemden geçmeli',
                'Türkiye\'den başvuruyorsan APS sertifikası da gerekli',
                'Aslını değil onaylı fotokopisini gönder — aslını sakla',
            ],
        ],
        [
            'icon' => '🗣', 'title' => 'Dil Belgesi', 'color' => '#f59e0b', 'chip' => 'ZORUNLU',
            'items' => [
                'Almanca program: DSH, TestDaF veya Goethe C1/B2',
                'İngilizce program: IELTS 6.0+ veya TOEFL iBT 80+',
                'Sınav tarihini iyi planla — sonuç 4–6 hafta alır',
                'Bazı üniversiteler kendi dil sınavını kabul eder',
                'Geçerlilik süresi: çoğu sınav için 2 yıl',
            ],
        ],
    ];
@endphp

<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Belge Türleri ve Dikkat Edilecekler</div>
<div class="dg-doc-grid" style="margin-bottom:24px;">
    @foreach($docTypes as $doc)
    <div class="dg-doc-card" style="--doc-color: {{ $doc['color'] }};">
        <div class="dg-doc-head">
            <div class="dg-doc-icon">{{ $doc['icon'] }}</div>
            <div class="dg-doc-meta">
                <div class="dg-doc-chip">{{ $doc['chip'] }}</div>
                <div class="dg-doc-title">{{ $doc['title'] }}</div>
            </div>
            <div class="dg-doc-count">{{ count($doc['items']) }}</div>
        </div>
        <ol class="dg-doc-list">
            @foreach($doc['items'] as $i => $item)
            <li class="dg-doc-item">
                <span class="dg-doc-num">{{ $i + 1 }}</span>
                <span class="dg-doc-text">{{ $item }}</span>
            </li>
            @endforeach
        </ol>
    </div>
    @endforeach
</div>

{{-- Hazırlık Takvimi --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-body" style="padding:20px 24px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:16px;">🗓 Belge Hazırlık Takvimi</div>
        <div class="col2" style="margin-bottom:0;">
            @foreach([
                ['6+ ay önce','Dil sınavına kaydol ve hazırlanmaya başla. TestDaF / IELTS için erken kayıt şart.','warn'],
                ['4–5 ay önce','Apostil işlemlerini başlat. Yeminli tercüman bul. APS başvurusunu yap.','warn'],
                ['3 ay önce','Motivasyon mektubunu yaz, danışmanına göster, revize et.','info'],
                ['2 ay önce','CV\'yi tamamla. Varsa referans mektuplarını topla.','info'],
                ['1 ay önce','Tüm belgeleri kontrol listesiyle denetle. Eksik varsa hemen tamamla.','ok'],
                ['Başvuru öncesi','Belge setinin dijital kopyasını sakla. Orijinalleri koru.','ok'],
            ] as [$zaman, $desc, $badge])
            <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--u-line,#e2e8f0);align-items:flex-start;">
                <span class="badge {{ $badge }}" style="font-size:var(--tx-xs);flex-shrink:0;white-space:nowrap;margin-top:1px;">{{ $zaman }}</span>
                <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.5;">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- CTA --}}
<div class="dg-cta">
    <div>
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:4px;">Belgelerini Yüklemeye Hazır mısın?</div>
        <div style="font-size:var(--tx-sm);opacity:.9;">Danışmanın belgelerini inceleyecek ve eksik olanlar için seni yönlendirecek.</div>
    </div>
    <a href="{{ $dgDocsRoute }}" class="dg-cta-btn">
        Belgelerime Git <span>→</span>
    </a>
</div>

<div style="text-align:center;padding:12px 0;">
    <a href="{{ $dgDashboardRoute }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Dashboard</a>
    &nbsp;·&nbsp;
    <a href="{{ $dgUniRoute }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Üniversite Rehberi →</a>
</div>

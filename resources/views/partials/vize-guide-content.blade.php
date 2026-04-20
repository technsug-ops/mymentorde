{{-- ══════════════════════════════════════════════════════════════════════════
  Shared Vize & Sperrkonto Guide partial — Guest + Student portals.
═══════════════════════════════════════════════════════════════════════════ --}}

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
.jm-minimalist .vg-hero { background: #e2e5ec !important; color: var(--u-text,#1a1a1a) !important; border: 1px solid rgba(0,0,0,.10) !important; }
.jm-minimalist .vg-hero::before { display: none !important; }
.jm-minimalist .vg-hero * { color: inherit !important; opacity: 1 !important; }

/* ══════ Hero ══════ */
.vg-hero {
    color:#fff; border-radius:14px; margin-bottom:20px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.14); position:relative;
    background:#0891b2 url('https://images.unsplash.com/photo-1501139083538-0139583c060f?w=1400&q=80') center/cover;
}
.vg-hero::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, rgba(8,145,178,.92) 0%, rgba(14,116,144,.88) 100%);
}
.vg-hero-body { position:relative; display:flex; align-items:center; gap:24px; padding:26px 28px; }
.vg-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:8px; }
.vg-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.82; }
.vg-hero-marker { display:inline-block; width:5px; height:16px; background:rgba(255,255,255,.75); border-radius:3px; }
.vg-hero-title { font-size:32px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.5px; }
.vg-hero-overview { font-size:14px; opacity:.92; line-height:1.55; max-width:600px; margin-top:2px; }
.vg-hero-stats { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.vg-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:20px; background:rgba(255,255,255,.18); font-size:12px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.vg-hero-stat-ico { font-size:13px; }
.vg-hero-icon { font-size:64px; line-height:1; flex-shrink:0; opacity:.9; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }

@media (max-width:720px){
    .vg-hero{border-radius:12px;}
    .vg-hero-body{gap:14px; padding:18px; align-items:flex-start;}
    .vg-hero-title{font-size:22px; letter-spacing:-.3px;}
    .vg-hero-overview{font-size:12.5px; line-height:1.45; max-width:none;}
    .vg-hero-stats{gap:6px; margin-top:10px; padding-top:10px;}
    .vg-hero-stat{padding:4px 9px; font-size:11px;}
    .vg-hero-icon{font-size:40px; align-self:flex-start; margin-top:2px;}
    .vg-hero-label{font-size:10px; letter-spacing:.5px;}
    .vg-hero-marker{height:12px; width:3px;}
}

/* ══════ Section Title ══════ */
.vg-section-title { font-weight:700; font-size:var(--tx-base); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.vg-section-title::before {
    content:''; display:inline-block; width:4px; height:16px;
    background:var(--u-brand,#2563eb); border-radius:2px;
}

/* ══════ Steps Timeline ══════ */
.vg-steps-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-bottom:24px; }
.vg-steps-col {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:14px; padding:8px 8px; overflow:hidden;
}
.vg-step {
    --s-color: #6366f1;
    display:flex; gap:14px; padding:14px 14px;
    border-radius:10px; position:relative;
    transition:background .12s;
}
.vg-step:hover { background:color-mix(in srgb, var(--s-color) 5%, transparent); }
.vg-step-rail { display:flex; flex-direction:column; align-items:center; flex-shrink:0; }
.vg-step-num {
    width:36px; height:36px; border-radius:50%;
    background:var(--s-color); color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:14px; font-weight:800;
    box-shadow:0 2px 8px color-mix(in srgb, var(--s-color) 40%, transparent);
    flex-shrink:0;
}
.vg-step-line {
    width:2px; flex:1; background:color-mix(in srgb, var(--s-color) 20%, var(--u-line));
    margin:6px auto; min-height:14px;
}
.vg-step-body { flex:1; min-width:0; padding-top:5px; }
.vg-step-head { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px; }
.vg-step-ico { font-size:16px; }
.vg-step-title { font-weight:700; font-size:13.5px; color:var(--u-text); line-height:1.25; }
.vg-step-badge {
    font-size:9.5px; font-weight:800; letter-spacing:.4px; text-transform:uppercase;
    padding:2px 7px; border-radius:4px;
    color:var(--s-color);
    background:color-mix(in srgb, var(--s-color) 12%, transparent);
}
.vg-step-desc { font-size:12px; color:var(--u-muted); line-height:1.5; }

@media (max-width:720px){
    .vg-steps-grid{grid-template-columns:1fr; gap:12px;}
    .vg-step{padding:12px;}
    .vg-step-num{width:30px; height:30px; font-size:12px;}
    .vg-step-title{font-size:13px;}
    .vg-step-desc{font-size:11.5px;}
}

/* ══════ Docs + Notes 2-col ══════ */
.vg-side-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; }
.vg-panel {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:14px; padding:18px 20px; overflow:hidden;
}
.vg-panel-title {
    font-weight:700; font-size:14px; margin-bottom:12px;
    display:flex; align-items:center; gap:8px;
}
.vg-panel-title::before {
    content:''; display:inline-block; width:4px; height:16px;
    background:var(--u-brand,#2563eb); border-radius:2px;
}

/* Doc checklist */
.vg-doc-list { display:flex; flex-direction:column; gap:2px; }
.vg-doc {
    display:flex; align-items:flex-start; gap:10px;
    padding:9px 10px; border-radius:8px;
    font-size:13px; line-height:1.4;
    transition:background .12s;
}
.vg-doc:hover { background:color-mix(in srgb, var(--u-brand,#2563eb) 4%, transparent); }
.vg-doc-icon {
    flex-shrink:0; width:22px; height:22px; border-radius:6px;
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:800;
    margin-top:1px;
}
.vg-doc-icon.req { background:color-mix(in srgb, #16a34a 15%, #fff); color:#16a34a; border:1px solid color-mix(in srgb, #16a34a 25%, transparent); }
.vg-doc-icon.cond { background:color-mix(in srgb, #0891b2 15%, #fff); color:#0891b2; border:1px solid color-mix(in srgb, #0891b2 25%, transparent); }
.vg-doc-icon.maybe { background:color-mix(in srgb, #f59e0b 15%, #fff); color:#f59e0b; border:1px solid color-mix(in srgb, #f59e0b 25%, transparent); }
.vg-doc-text { color:var(--u-text); }
.vg-doc-legend {
    margin-top:10px; padding:9px 12px;
    background:color-mix(in srgb, var(--u-brand,#2563eb) 4%, var(--u-bg));
    border-radius:8px; font-size:11px; color:var(--u-muted);
    display:flex; gap:12px; flex-wrap:wrap;
}

/* Notes list */
.vg-note-list { display:flex; flex-direction:column; gap:2px; }
.vg-note {
    --n-color: var(--u-brand, #2563eb);
    display:flex; gap:11px; align-items:flex-start;
    padding:9px 10px; border-radius:8px;
    transition:background .12s;
}
.vg-note:hover { background:color-mix(in srgb, var(--n-color) 5%, transparent); }
.vg-note-ico {
    width:34px; height:34px; border-radius:9px;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; flex-shrink:0;
    background:color-mix(in srgb, var(--n-color) 12%, #fff);
    border:1px solid color-mix(in srgb, var(--n-color) 22%, transparent);
}
.vg-note-title { font-weight:700; font-size:13px; color:var(--u-text); line-height:1.25; margin-bottom:2px; }
.vg-note-desc { font-size:11.5px; color:var(--u-muted); line-height:1.45; }

@media (max-width:900px){ .vg-side-grid{grid-template-columns:1fr;} }

/* ══════ FAQ ══════ */
.vg-faq-card { background:var(--u-card); border:1px solid var(--u-line); border-radius:14px; padding:20px 22px; margin-bottom:24px; }
.vg-faq-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.vg-faq {
    padding:12px 14px; border-radius:10px;
    background:color-mix(in srgb, var(--u-brand,#2563eb) 3%, var(--u-bg));
    border:1px solid var(--u-line);
    transition:border-color .15s, transform .15s;
}
.vg-faq:hover {
    transform:translateY(-1px);
    border-color:color-mix(in srgb, var(--u-brand,#2563eb) 35%, var(--u-line));
}
.vg-faq-q {
    font-weight:700; font-size:13px; color:var(--u-text);
    line-height:1.35; margin-bottom:6px;
    display:flex; align-items:flex-start; gap:7px;
}
.vg-faq-q::before {
    content:'❓'; font-size:14px; flex-shrink:0; opacity:.7;
}
.vg-faq-a { font-size:11.5px; color:var(--u-muted); line-height:1.55; padding-left:23px; }

@media (max-width:720px){ .vg-faq-grid{grid-template-columns:1fr;} }

/* ══════ CTA ══════ */
.vg-cta {
    background:linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
    color:#fff; border-radius:14px; padding:22px 26px;
    margin-bottom:16px; position:relative; overflow:hidden;
    display:flex; align-items:center; justify-content:space-between;
    gap:16px; flex-wrap:wrap;
}
.vg-cta::before {
    content:'🛂'; position:absolute; top:-14px; right:-10px;
    font-size:120px; opacity:.09; pointer-events:none;
}
.vg-cta-text { position:relative; }
.vg-cta-title { font-weight:800; font-size:18px; margin:0 0 4px; letter-spacing:-.2px; }
.vg-cta-sub { font-size:13px; opacity:.9; line-height:1.5; max-width:460px; }
.vg-cta-btn {
    position:relative;
    display:inline-flex; align-items:center; gap:7px;
    padding:10px 20px; border-radius:22px;
    background:#fff; color:#0891b2; font-weight:700; font-size:13px;
    text-decoration:none; box-shadow:0 4px 14px rgba(0,0,0,.18);
    transition:transform .15s, box-shadow .15s;
    flex-shrink:0;
}
.vg-cta-btn:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(0,0,0,.24); text-decoration:none; }

@media (max-width:560px){
    .vg-cta{padding:18px; border-radius:12px;}
    .vg-cta-title{font-size:16px;}
    .vg-cta-sub{font-size:12px;}
}
</style>
@endpush

@php
$vgIsStudent = request()->is('student/*');
$vgDashboardRoute = $vgIsStudent ? route('student.dashboard') : route('guest.dashboard');
$vgUniRoute = $vgIsStudent ? route('student.info.university-guide') : route('guest.university-guide');
$vgMessagesRoute = $vgIsStudent ? route('student.messages') : route('guest.messages');
$rate = $eurTryRate ?? null;
@endphp

{{-- ══════ Hero ══════ --}}
<div class="vg-hero">
    <div class="vg-hero-body">
        <div class="vg-hero-main">
            <div class="vg-hero-label"><span class="vg-hero-marker"></span>Almanya Öğrenci Vizesi &amp; Bloke Hesap</div>
            <h1 class="vg-hero-title">Vize &amp; Sperrkonto Rehberi</h1>
            <div class="vg-hero-overview">
                Almanya öğrenci vizesi başvurusu, Sperrkonto (bloke hesap) açılışı ve gerekli belgeler — adım adım rehber.
            </div>
            <div class="vg-hero-stats">
                <span class="vg-hero-stat"><span class="vg-hero-stat-ico">💶</span>Sperrkonto €11.208</span>
                <span class="vg-hero-stat"><span class="vg-hero-stat-ico">📅</span>4–16 hafta randevu</span>
                <span class="vg-hero-stat"><span class="vg-hero-stat-ico">✈️</span>İlk vize 3 ay</span>
                @if($rate)
                <span class="vg-hero-stat"><span class="vg-hero-stat-ico">💱</span>≈ ₺ {{ number_format(11208 * $rate, 0, ',', '.') }}</span>
                @endif
            </div>
        </div>
        <div class="vg-hero-icon">🛂</div>
    </div>
</div>

{{-- ══════ Steps ══════ --}}
@php
$steps = [
    ['no'=>'1','title'=>'Kabul Mektubunu Al','desc'=>'Üniversiteden kesin kabul (Zulassungsbescheid) alınmalıdır. uni-assist üzerinden başvurduysan sonuç 6–8 hafta sürebilir.','icon'=>'🎓','color'=>'#7c3aed','badge'=>'Başlangıç'],
    ['no'=>'2','title'=>'APS Sertifikası Al','desc'=>'Türkiye\'den başvuranlar için çoğu üniversite APS sertifikası ister. Ankara Alman Büyükelçiliği\'nden alınır, 4–8 hafta sürer.','icon'=>'📜','color'=>'#0891b2','badge'=>'TR\'ye özgü'],
    ['no'=>'3','title'=>'Sperrkonto Aç','desc'=>'Fintiba, Coracle veya Deutsche Bank gibi onaylı sağlayıcılarda bloke hesap aç. Yıllık €11.208 yatırılmalıdır (€934 × 12 ay).','icon'=>'🏦','color'=>'#059669','badge'=>'Zorunlu'],
    ['no'=>'4','title'=>'Belgeleri Hazırla','desc'=>'Pasaport, biyometrik fotoğraf, kabul mektubu, dil belgesi, Sperrkonto belgesi, sağlık sigortası, diploma çevirileri.','icon'=>'📋','color'=>'#d97706','badge'=>'Kritik'],
    ['no'=>'5','title'=>'Konsolosluğa Randevu Al','desc'=>'Türkiye\'deki Alman Büyükelçiliği veya Başkonsolosluğundan online randevu alınır. Bekleme 4–16 hafta olabilir — erken al!','icon'=>'📅','color'=>'#dc2626','badge'=>'Erken Alın'],
    ['no'=>'6','title'=>'Vize Görüşmesi','desc'=>'Randevu günü tüm belgelerle konsolosluğa git. Görüşme 15–30 dakika sürer. Eksik belge olmamasına dikkat et.','icon'=>'🏛','color'=>'#475569','badge'=>'Görüşme'],
    ['no'=>'7','title'=>'Vize Onayı &amp; Seyahat','desc'=>'Vize genellikle 4–8 haftada sonuçlanır. Onaylandıktan sonra Almanya\'ya gidebilirsin.','icon'=>'✈️','color'=>'#7c3aed','badge'=>'Son Adım'],
];
$stepsLeft  = array_slice($steps, 0, 4);
$stepsRight = array_slice($steps, 4);
@endphp

<div class="vg-section-title">Vize Başvuru Süreci — Adım Adım</div>
<div class="vg-steps-grid">
    <div class="vg-steps-col">
        @foreach($stepsLeft as $step)
        <div class="vg-step" style="--s-color:{{ $step['color'] }};">
            <div class="vg-step-rail">
                <div class="vg-step-num">{{ $step['no'] }}</div>
                @if(!$loop->last)<div class="vg-step-line"></div>@endif
            </div>
            <div class="vg-step-body">
                <div class="vg-step-head">
                    <span class="vg-step-ico">{{ $step['icon'] }}</span>
                    <span class="vg-step-title">{{ $step['title'] }}</span>
                    <span class="vg-step-badge">{{ $step['badge'] }}</span>
                </div>
                <div class="vg-step-desc">{!! $step['desc'] !!}</div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="vg-steps-col">
        @foreach($stepsRight as $step)
        <div class="vg-step" style="--s-color:{{ $step['color'] }};">
            <div class="vg-step-rail">
                <div class="vg-step-num">{{ $step['no'] }}</div>
                @if(!$loop->last)<div class="vg-step-line"></div>@endif
            </div>
            <div class="vg-step-body">
                <div class="vg-step-head">
                    <span class="vg-step-ico">{{ $step['icon'] }}</span>
                    <span class="vg-step-title">{{ $step['title'] }}</span>
                    <span class="vg-step-badge">{{ $step['badge'] }}</span>
                </div>
                <div class="vg-step-desc">{!! $step['desc'] !!}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ══════ Docs + Notes ══════ --}}
<div class="vg-side-grid">

    <div class="vg-panel">
        <div class="vg-panel-title">📋 Gerekli Belgeler</div>
        <div class="vg-doc-list">
            @foreach([
                ['Geçerli pasaport (6+ ay geçerliliği)',           'req'],
                ['Biyometrik fotoğraf (Alman standardı)',           'req'],
                ['Doldurulmuş vize başvuru formu',                  'req'],
                ['Üniversite kabul mektubu (Zulassungsbescheid)',   'req'],
                ['Sperrkonto bloke belgesi (€11.208)',              'req'],
                ['Sağlık sigortası belgesi',                        'req'],
                ['Dil belgesi (Almanca B2 / İngilizce C1)',         'req'],
                ['Lise diploması + noter onaylı Almanca çeviri',    'req'],
                ['Transkript + noter onaylı Almanca çeviri',        'req'],
                ['APS sertifikası (TR\'den başvuranlar)',           'cond'],
                ['Motivasyon mektubu (bazı konsolosluklarda)',      'maybe'],
            ] as [$doc, $kind])
            <div class="vg-doc">
                <span class="vg-doc-icon {{ $kind }}">{{ $kind === 'req' ? '✓' : ($kind === 'cond' ? 'i' : '?') }}</span>
                <span class="vg-doc-text">{{ $doc }}</span>
            </div>
            @endforeach
        </div>
        <div class="vg-doc-legend">
            <span><strong style="color:#16a34a;">✓</strong> Zorunlu</span>
            <span><strong style="color:#0891b2;">i</strong> Koşullu</span>
            <span><strong style="color:#f59e0b;">?</strong> Konsolosluğa göre</span>
        </div>
    </div>

    <div class="vg-panel">
        <div class="vg-panel-title">💡 Önemli Notlar</div>
        <div class="vg-note-list">
            @foreach([
                ['💰','#16a34a','Sperrkonto tutarı','2024: yıllık €11.208 (aylık €934). Her yıl Ocak\'ta güncellenir.'],
                ['⏰','#dc2626','Randevu bekleme','Türkiye\'de 4–16 hafta bekleme olabilir. En erken tarihi al.'],
                ['📋','#0891b2','Vize geçerliliği','İlk vize 3 ay verilir. Almanya\'da Ausländerbehörde\'de uzatılır.'],
                ['🏧','#7c3aed','Para çekme','Almanya\'ya geldikten sonra Sperrkonto\'dan aylık €934 çekilir.'],
                ['🏥','#e11d48','Sigorta','Vize ve üniversite kaydı için zorunlu. TK, AOK, DAK önerilir.'],
                ['🎓','#f59e0b','APS','Türkiye\'den başvuranların büyük çoğunluğu için gereklidir.'],
            ] as [$ni, $color, $nl, $nv])
            <div class="vg-note" style="--n-color:{{ $color }};">
                <div class="vg-note-ico">{{ $ni }}</div>
                <div>
                    <div class="vg-note-title">{{ $nl }}</div>
                    <div class="vg-note-desc">{{ $nv }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ══════ FAQ ══════ --}}
<div class="vg-section-title">Sık Sorulan Sorular</div>
<div class="vg-faq-card">
    <div class="vg-faq-grid">
        @foreach([
            ['Sperrkonto olmadan vize alınır mı?','Hayır. Almanya öğrenci vizesi için Sperrkonto zorunludur. Nakit veya banka ekstresi kabul edilmez.'],
            ['Vize reddedilirse ne olur?','Ret gerekçesini öğrenip eksik belgeyi tamamlayarak yeniden başvurabilirsin. Danışmanınla süreci yönet.'],
            ['Hangi konsolosluğa başvurmalıyım?','İkamet adresine göre belirlenir. Türkiye\'de İstanbul, Ankara ve İzmir konsolosluğu bulunmaktadır.'],
            ['Para yatırmak ne kadar sürer?','Banka havalesinden sonra 3–7 iş günü. Bloke belgesi 1–3 gün ek süre alır.'],
            ['Dil belgesi şart mı?','Çoğu program için evet. Almanca program: B2/DSH/TestDaF; İngilizce: IELTS 6.0 veya TOEFL 80+.'],
            ['Öğrenci vizesiyle çalışabilir miyim?','Evet. Yılda 120 tam gün (240 yarım gün) çalışabilirsin. Ayrıca izin gerekmez.'],
        ] as [$fq, $fa])
        <div class="vg-faq">
            <div class="vg-faq-q">{{ $fq }}</div>
            <div class="vg-faq-a">{{ $fa }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- ══════ CTA ══════ --}}
<div class="vg-cta">
    <div class="vg-cta-text">
        <div class="vg-cta-title">Danışmanınla Konuş</div>
        <div class="vg-cta-sub">Vize veya Sperrkonto konusunda takıldığın bir nokta mı var? Danışmanın sana özel rehberlik sağlar.</div>
    </div>
    <a href="{{ $vgMessagesRoute }}" class="vg-cta-btn">
        Danışmana Yaz <span>→</span>
    </a>
</div>

<div style="text-align:center;padding:8px 0 16px;">
    <a href="{{ $vgDashboardRoute }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Dashboard</a>
    &nbsp;·&nbsp;
    <a href="{{ $vgUniRoute }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Üniversite Rehberi →</a>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
  Sağlık Sigortası Rehberi — TK / AOK / Mawista / DR-WALTER karşılaştırma
  Almanya öğrenci sigortası zorunlu — bu sayfa nasıl seçim yapılır.
══════════════════════════════════════════════════════════════════════════ --}}

@push('head')
<style>
.hi-hero {
    color:#fff; border-radius:14px; margin-bottom:20px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.14); position:relative;
    background:linear-gradient(135deg, #064e3b 0%, #047857 55%, #10b981 100%);
}
.hi-hero::before {
    content:''; position:absolute; inset:0;
    background:
        radial-gradient(circle at 18% 22%, rgba(255,255,255,.16), transparent 38%),
        radial-gradient(circle at 82% 78%, rgba(16,185,129,.30), transparent 45%);
    pointer-events:none;
}
.hi-hero::after {
    content:''; position:absolute; top:0; right:0; width:42%; height:100%;
    background-image:
        linear-gradient(rgba(255,255,255,.07) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.07) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: linear-gradient(to left, rgba(0,0,0,.8), transparent 100%);
    -webkit-mask-image: linear-gradient(to left, rgba(0,0,0,.8), transparent 100%);
    pointer-events:none;
}
.hi-hero-body { position:relative; padding:26px 28px; }
.hi-hero-label { font-size:11px; font-weight:700; letter-spacing:.18em; text-transform:uppercase; opacity:.85; }
.hi-hero-title { font-size:30px; font-weight:800; margin:8px 0 6px; line-height:1.1; letter-spacing:-.5px; }
.hi-hero-sub { font-size:14px; opacity:.92; line-height:1.55; max-width:620px; }

.hi-section-title { font-weight:800; font-size:18px; margin:28px 0 12px; letter-spacing:-.3px; display:flex; align-items:center; gap:8px; }
.hi-section-title small { font-weight:600; font-size:12px; color:var(--u-muted); letter-spacing:0; text-transform:none; }

.hi-decision {
    background:linear-gradient(135deg, #ecfdf5, #f0fdfa);
    border:1px solid #6ee7b7; border-left:4px solid #059669;
    border-radius:12px; padding:18px 22px; margin-bottom:24px;
}
.hi-decision-title { font-weight:800; font-size:15px; color:#065f46; margin-bottom:6px; }
.hi-decision-body { color:#064e3b; font-size:13.5px; line-height:1.6; }
.hi-decision-body strong { color:#064e3b; }

/* Type cards (Gesetzlich vs Privat) */
.hi-types { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:14px; margin-bottom:24px; }
.hi-type {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:14px;
    padding:18px 22px; border-top:3px solid var(--hi-color, #16a34a);
}
.hi-type-badge {
    display:inline-block; padding:3px 10px; border-radius:999px;
    background:color-mix(in srgb, var(--hi-color) 12%, transparent);
    color:var(--hi-color); font-size:11px; font-weight:800; letter-spacing:.05em; text-transform:uppercase;
    margin-bottom:8px;
}
.hi-type-title { font-weight:800; font-size:17px; margin-bottom:4px; color:var(--u-text); letter-spacing:-.3px; }
.hi-type-price { font-size:22px; font-weight:800; color:var(--hi-color, #16a34a); margin-bottom:8px; letter-spacing:-.5px; }
.hi-type-price small { font-size:13px; font-weight:600; color:var(--u-muted); letter-spacing:0; }
.hi-type-desc { font-size:13px; color:var(--u-muted); line-height:1.6; margin-bottom:12px; }
.hi-type-pros, .hi-type-cons { font-size:12.5px; line-height:1.65; }
.hi-type-pros li { color:#15803d; margin-bottom:3px; }
.hi-type-cons li { color:#b45309; margin-bottom:3px; }
.hi-type-pros, .hi-type-cons { padding-left:20px; }

/* Provider comparison table */
.hi-table-wrap { overflow-x:auto; border-radius:14px; border:1px solid var(--u-line); margin-bottom:24px; }
.hi-table { width:100%; border-collapse:collapse; min-width:720px; background:var(--u-card); }
.hi-table th, .hi-table td { padding:12px 14px; text-align:left; border-bottom:1px solid var(--u-line); font-size:13px; }
.hi-table th { background:var(--u-bg); font-weight:800; font-size:11.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--u-muted); }
.hi-table td:first-child { font-weight:700; color:var(--u-text); }
.hi-badge {
    display:inline-block; padding:3px 9px; border-radius:999px;
    font-size:10.5px; font-weight:700; letter-spacing:.04em;
}
.hi-badge-best { background:#dcfce7; color:#15803d; }
.hi-badge-ok   { background:#dbeafe; color:#1e40af; }
.hi-badge-warn { background:#fef3c7; color:#854d0e; }

.hi-flow {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:14px;
    padding:18px 22px; margin-bottom:24px;
}
.hi-flow-step { display:flex; gap:14px; padding:12px 0; border-bottom:1px solid var(--u-line); }
.hi-flow-step:last-child { border-bottom:none; }
.hi-flow-num {
    flex-shrink:0; width:30px; height:30px; border-radius:50%;
    background:#10b981; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:13px;
}
.hi-flow-body { flex:1; }
.hi-flow-title { font-weight:700; font-size:13.5px; color:var(--u-text); margin-bottom:2px; }
.hi-flow-text { font-size:12.5px; color:var(--u-muted); line-height:1.55; }
.hi-flow-text strong { color:var(--u-text); }

.hi-faq { background:var(--u-card); border:1px solid var(--u-line); border-radius:14px; padding:6px 18px; }
.hi-faq-item { border-bottom:1px solid var(--u-line); padding:14px 0; }
.hi-faq-item:last-child { border-bottom:none; }
.hi-faq-q {
    font-weight:700; font-size:14px; color:var(--u-text);
    cursor:pointer; display:flex; justify-content:space-between; align-items:center;
    list-style:none;
}
.hi-faq-q::after { content:"+"; color:#059669; font-size:24px; font-weight:300; }
.hi-faq-item[open] .hi-faq-q::after { content:"−"; }
.hi-faq-a { color:var(--u-muted); font-size:13px; line-height:1.6; margin-top:8px; }
.hi-faq-a strong { color:var(--u-text); }
</style>
@endpush

<div class="hi-hero">
    <div class="hi-hero-body">
        <div class="hi-hero-label">🩺 SAĞLIK SİGORTASI REHBERİ</div>
        <div class="hi-hero-title">TK · AOK · Mawista · DR-WALTER karşılaştırma</div>
        <div class="hi-hero-sub">Almanya'da öğrenci olarak sağlık sigortası ZORUNLU — vize alamazsın. Doğru sigortayı seçmen aylık €20–€110 fark eder.</div>
    </div>
</div>

<div class="hi-decision">
    <div class="hi-decision-title">⚖️ İlk karar: Devlet (Gesetzlich) mı, Özel (Privat) mi?</div>
    <div class="hi-decision-body">
        <strong>30 yaş altıysan + dil/Studienkolleg değil tam öğrenci statüsündeysen</strong> → Devlet sigortası (TK, AOK vb.) sana en avantajlı, <strong>€110/ay sabit</strong>.<br>
        <strong>Dil kursu, Studienkolleg, vize başvurusu aşamasındaysan</strong> → Özel sigorta (Mawista, DR-WALTER) <strong>€30–55/ay</strong>, sadece bu aşama için geçerli.<br>
        <strong>30 yaş üstüysen</strong> → Devlet sigortası kabul etmiyor, sadece özel sigorta seçeneğin var.
    </div>
</div>

{{-- ════════ Gesetzlich vs Privat ════════ --}}
<div class="hi-section-title"><x-icon name="shield" size="18" /> Sigorta Türleri</div>
<div class="hi-types">
    <div class="hi-type" style="--hi-color:#16a34a;">
        <div class="hi-type-badge">Gesetzlich (Devlet)</div>
        <div class="hi-type-title">Yasal Sağlık Sigortası</div>
        <div class="hi-type-price">€110,80 <small>/ ay (2026)</small></div>
        <div class="hi-type-desc">TK, AOK, Barmer, DAK gibi devlet kasaları. Tam öğrenci statüsü + 30 yaş altı için zorunlu seçenek. Aile geçmişi de kapsam altında.</div>
        <strong style="font-size:11px;color:#15803d;">✓ Avantajlar</strong>
        <ul class="hi-type-pros">
            <li>Sabit aylık ücret, sürpriz yok</li>
            <li>Tüm hastaneler/doktorlar kabul</li>
            <li>Diş + göz dahil (sınırlı)</li>
            <li>Hamile/aile reçetelerini öder</li>
        </ul>
        <strong style="font-size:11px;color:#b45309;margin-top:8px;display:block;">✗ Dezavantajlar</strong>
        <ul class="hi-type-cons">
            <li>Dil kursu/Studienkolleg için kabul etmez</li>
            <li>30 yaş üstüne kapalı</li>
            <li>Önce immatrikulation (kayıt) gerekli</li>
        </ul>
    </div>
    <div class="hi-type" style="--hi-color:#0891b2;">
        <div class="hi-type-badge">Privat (Özel)</div>
        <div class="hi-type-title">Özel Sağlık Sigortası</div>
        <div class="hi-type-price">€30–55 <small>/ ay</small></div>
        <div class="hi-type-desc">Mawista, DR-WALTER, Care Concept, Expatrio. Dil kursu, Studienkolleg, vize başvurusu için ideal. Vize alımı için yeterli.</div>
        <strong style="font-size:11px;color:#15803d;">✓ Avantajlar</strong>
        <ul class="hi-type-pros">
            <li>Vize başvurusu için yeterli</li>
            <li>Daha ucuz (€30-55)</li>
            <li>Dil kursu öğrencileri için ideal</li>
            <li>Yaş sınırı esnek</li>
        </ul>
        <strong style="font-size:11px;color:#b45309;margin-top:8px;display:block;">✗ Dezavantajlar</strong>
        <ul class="hi-type-cons">
            <li>Üniversite kabul ettiğinde TK'ya geçmeli</li>
            <li>Kapsamı dar (acil + temel)</li>
            <li>Bazı doktorlar kabul etmez</li>
            <li>Diş/göz genelde dahil değil</li>
        </ul>
    </div>
</div>

{{-- ════════ Provider Karşılaştırma ════════ --}}
<div class="hi-section-title"><x-icon name="users" size="18" /> Provider Karşılaştırma <small>2026 fiyatlar (yaklaşık)</small></div>
<div class="hi-table-wrap">
    <table class="hi-table">
        <thead>
            <tr>
                <th>Sağlayıcı</th>
                <th>Tür</th>
                <th>Aylık</th>
                <th>Yıllık</th>
                <th>Uygun</th>
                <th>Not</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>TK (Techniker)</strong></td>
                <td>Gesetzlich</td>
                <td>€110,80</td>
                <td>€1.329</td>
                <td><span class="hi-badge hi-badge-best">Önerilen</span></td>
                <td>İngilizce destek, hızlı kayıt, en popüler öğrenci kasası</td>
            </tr>
            <tr>
                <td><strong>AOK</strong></td>
                <td>Gesetzlich</td>
                <td>€110,80</td>
                <td>€1.329</td>
                <td><span class="hi-badge hi-badge-ok">İyi</span></td>
                <td>Bölgesel — Bavyera'da AOK Bayern, NRW'de AOK Nordrhein</td>
            </tr>
            <tr>
                <td><strong>Barmer</strong></td>
                <td>Gesetzlich</td>
                <td>€110,80</td>
                <td>€1.329</td>
                <td><span class="hi-badge hi-badge-ok">İyi</span></td>
                <td>Online işlemler iyi, mobil app işlevsel</td>
            </tr>
            <tr>
                <td><strong>Mawista</strong></td>
                <td>Privat</td>
                <td>€33–47</td>
                <td>€396–564</td>
                <td><span class="hi-badge hi-badge-best">Dil/Vize</span></td>
                <td>Vize başvurusu için en yaygın kabul edilen, online imza</td>
            </tr>
            <tr>
                <td><strong>DR-WALTER</strong></td>
                <td>Privat</td>
                <td>€36–55</td>
                <td>€432–660</td>
                <td><span class="hi-badge hi-badge-ok">Dil/Studienkolleg</span></td>
                <td>Tıp öğrencileri için ek paket var, kapsamı geniş</td>
            </tr>
            <tr>
                <td><strong>Care Concept</strong></td>
                <td>Privat</td>
                <td>€30–42</td>
                <td>€360–504</td>
                <td><span class="hi-badge hi-badge-ok">Dil kursu</span></td>
                <td>En ucuz seçenek, vize için yeterli ama sınırlı kapsam</td>
            </tr>
            <tr>
                <td><strong>Expatrio (One-stop)</strong></td>
                <td>Privat</td>
                <td>€45–80</td>
                <td>€540–960</td>
                <td><span class="hi-badge hi-badge-warn">Premium</span></td>
                <td>Sperrkonto + sigorta paket — toplam ödeme</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ════════ Süreç ════════ --}}
<div class="hi-section-title"><x-icon name="list-checks" size="18" /> Adım Adım Süreç</div>
<div class="hi-flow">
    <div class="hi-flow-step">
        <div class="hi-flow-num">1</div>
        <div class="hi-flow-body">
            <div class="hi-flow-title">Vize başvurusu öncesi: Özel sigorta al</div>
            <div class="hi-flow-text">Mawista, DR-WALTER veya Care Concept üzerinden online başvuru — <strong>1 saatte</strong> sigorta poliçesi alırsın. Vize randevusuna götür.</div>
        </div>
    </div>
    <div class="hi-flow-step">
        <div class="hi-flow-num">2</div>
        <div class="hi-flow-body">
            <div class="hi-flow-title">Almanya'ya geldikten sonra: Anmeldung yap</div>
            <div class="hi-flow-text">Bürgeramt'tan adres tescili (<strong>Anmeldebestätigung</strong>) → bu olmadan TK kayıt olmaz. Genelde varıştan sonra 1-2 hafta içinde.</div>
        </div>
    </div>
    <div class="hi-flow-step">
        <div class="hi-flow-num">3</div>
        <div class="hi-flow-body">
            <div class="hi-flow-title">Üniversite kaydı (Immatrikulation)</div>
            <div class="hi-flow-text">Üniversite seni öğrenci olarak kayda alır. <strong>Krankenkasse-Mitgliedsbescheinigung</strong> (sağlık sigortası üyelik belgesi) ister — özel sigortandan veya TK'dan al.</div>
        </div>
    </div>
    <div class="hi-flow-step">
        <div class="hi-flow-num">4</div>
        <div class="hi-flow-body">
            <div class="hi-flow-title">TK'ya geçiş (öğrenci statüsünde)</div>
            <div class="hi-flow-text">Online tk.de üzerinden başvuru → 10 dk. Üyelik belgesi 24-48 saatte e-postaya gelir. Özel sigortanı iptal et (önce TK belgesi, sonra iptal).</div>
        </div>
    </div>
    <div class="hi-flow-step">
        <div class="hi-flow-num">5</div>
        <div class="hi-flow-body">
            <div class="hi-flow-title">Aylık ödeme + doktor seçimi</div>
            <div class="hi-flow-text">TK aylık €110,80 → SEPA-Lastschrift (otomatik kesinti). Doktor için tk.de'den arama. Birinci adım: <strong>Hausarzt</strong> (aile hekimi) seç.</div>
        </div>
    </div>
</div>

{{-- ════════ FAQ ════════ --}}
<div class="hi-section-title"><x-icon name="help-circle" size="18" /> Sık Sorulanlar</div>
<div class="hi-faq">
    <details class="hi-faq-item">
        <summary class="hi-faq-q">Vize için hangi sigorta yeter?</summary>
        <div class="hi-faq-a">Konsolosluk sigorta belgesinde <strong>minimum kapsama</strong> şartlarını arar: <strong>€30.000 yıllık kapsam, repatriation (vatan iadesi), ABS dahil</strong>. Mawista, DR-WALTER, Care Concept hepsi bu kriterleri karşılıyor — vize için yeterli.</div>
    </details>
    <details class="hi-faq-item">
        <summary class="hi-faq-q">Özel sigortayla başlayıp TK'ya geçmek ekstra para mı?</summary>
        <div class="hi-faq-a"><strong>Hayır, çok mantıklı bir yol.</strong> Vize → varış → Anmeldung → Immatrikulation tamamlanana kadar 1-2 ay özel sigorta (€30-50/ay). Sonra TK'ya geç → ömür boyu Gesetzlich. <strong>Toplam ekstra:</strong> 1-2 ay × €30-50 = €30-100. Vize alımı için zorunlu.</div>
    </details>
    <details class="hi-faq-item">
        <summary class="hi-faq-q">30 yaşımı geçmişim, devlet sigortası olamıyor muyum?</summary>
        <div class="hi-faq-a">İlkesel olarak öğrenci kasası <strong>30 yaş veya 14. sömestre kadar</strong> kabul ediyor. Geçtiysen <strong>"Freiwillige Versicherung"</strong> (gönüllü) ile devlet sigortasına geçebilirsin — €180-220/ay. Veya özel sigorta — €60-150/ay (yaşa göre).</div>
    </details>
    <details class="hi-faq-item">
        <summary class="hi-faq-q">Acil durumda ne yapmalıyım?</summary>
        <div class="hi-faq-a"><strong>Hayati tehlike:</strong> 112'yi ara — ücretsiz, sigorta türünden bağımsız. <strong>Akşam/hafta sonu doktor:</strong> 116 117 — Ärztlicher Bereitschaftsdienst. <strong>Çalışma saatlerinde:</strong> Hausarzt'ına git veya Online-Sprechstunde (TK app'i ile video).</div>
    </details>
    <details class="hi-faq-item">
        <summary class="hi-faq-q">Diş tedavisi ne kadar kapsanır?</summary>
        <div class="hi-faq-a"><strong>Gesetzlich:</strong> Temel tedavi (dolgu, çekim) kapsanır. Krone, implant, ortodonti kısmen — <strong>%30-60</strong>'ı kendi öder. <strong>Privat (öğrenci):</strong> Genelde sadece acil — krone/implant <strong>kapsanmaz</strong>. Diş yapacaksan Türkiye'de yapmak çok daha ucuz.</div>
    </details>
    <details class="hi-faq-item">
        <summary class="hi-faq-q">Erasmus/staj döneminde sigorta nasıl?</summary>
        <div class="hi-faq-a">AB içi (Erasmus) → <strong>EHIC kartı</strong> (Europäische Krankenversicherungskarte) — TK ücretsiz veriyor, başka ülkede de geçerli. AB dışı → ek seyahat sigortası (€20-50, 1 ay).</div>
    </details>
</div>

<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:16px 20px;font-size:13.5px;color:#1e3a8a;line-height:1.55;margin-top:20px;">
    <strong>💡 MentorDE önerisi:</strong> En güvenli yol — <strong>Mawista (vize için)</strong> → Almanya'ya gel → <strong>Anmeldung + Immatrikulation</strong> → <strong>TK'ya geç</strong>. Toplam ekstra maliyet €50-80, başın hiç ağrımıyor.
</div>

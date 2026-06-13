{{-- ══════════════════════════════════════════════════════════════════════════
  Studienkolleg Rehberi — TR lisesinden Almanya'ya direkt giremeyen
  öğrenciler için 1 yıllık hazırlık programı rehberi.
══════════════════════════════════════════════════════════════════════════ --}}

@push('head')
<style>
.sk-hero {
    color:#fff; border-radius:14px; margin-bottom:20px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.14); position:relative;
    background:linear-gradient(135deg, #7c2d12 0%, #b45309 55%, #f59e0b 100%);
}
.sk-hero::before {
    content:''; position:absolute; inset:0;
    background:
        radial-gradient(circle at 18% 22%, rgba(255,255,255,.16), transparent 38%),
        radial-gradient(circle at 82% 78%, rgba(180,83,9,.30), transparent 45%);
    pointer-events:none;
}
.sk-hero::after {
    content:''; position:absolute; top:0; right:0; width:42%; height:100%;
    background-image:
        linear-gradient(rgba(255,255,255,.07) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.07) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: linear-gradient(to left, rgba(0,0,0,.8), transparent 100%);
    -webkit-mask-image: linear-gradient(to left, rgba(0,0,0,.8), transparent 100%);
    pointer-events:none;
}
.sk-hero-body { position:relative; padding:26px 28px; }
.sk-hero-label { font-size:11px; font-weight:700; letter-spacing:.18em; text-transform:uppercase; opacity:.85; }
.sk-hero-title { font-size:30px; font-weight:800; margin:8px 0 6px; line-height:1.1; letter-spacing:-.5px; }
.sk-hero-sub { font-size:14px; opacity:.92; line-height:1.55; max-width:620px; }

.sk-section-title { font-weight:800; font-size:18px; margin:28px 0 12px; letter-spacing:-.3px; display:flex; align-items:center; gap:8px; }
.sk-section-title small { font-weight:600; font-size:12px; color:var(--u-muted); letter-spacing:0; text-transform:none; }

.sk-alert {
    background:linear-gradient(135deg, #fef3c7, #fffbeb);
    border:1px solid #fcd34d; border-left:4px solid #d97706;
    border-radius:12px; padding:16px 20px; margin-bottom:18px;
    font-size:13.5px; color:#78350f; line-height:1.55;
}
.sk-alert strong { color:#451a03; }

.sk-types {
    display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:14px; margin-bottom:24px;
}
.sk-type {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:14px;
    padding:18px 20px; border-top:3px solid var(--sk-color, #d97706);
    transition:transform .15s, box-shadow .15s;
}
.sk-type:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(0,0,0,.08); }
.sk-type-code { font-weight:800; font-size:24px; color:var(--sk-color, #d97706); letter-spacing:-.5px; margin-bottom:2px; }
.sk-type-name { font-weight:700; font-size:13px; color:var(--u-text); margin-bottom:6px; }
.sk-type-desc { font-size:12.5px; color:var(--u-muted); line-height:1.55; margin-bottom:10px; }
.sk-type-target {
    display:inline-block; padding:3px 10px; border-radius:999px;
    background:color-mix(in srgb, var(--sk-color, #d97706) 12%, transparent);
    color:var(--sk-color, #d97706); font-size:11px; font-weight:700;
}

.sk-steps {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:14px;
    padding:18px 22px; margin-bottom:24px;
}
.sk-step { display:flex; gap:14px; padding:14px 0; border-bottom:1px solid var(--u-line); }
.sk-step:last-child { border-bottom:none; }
.sk-step-num {
    flex-shrink:0; width:34px; height:34px; border-radius:50%;
    background:linear-gradient(135deg, #f59e0b, #d97706);
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:14px;
    box-shadow:0 4px 12px rgba(217,119,6,.25);
}
.sk-step-body { flex:1; }
.sk-step-title { font-weight:800; font-size:14.5px; margin-bottom:3px; color:var(--u-text); }
.sk-step-text { font-size:13px; color:var(--u-muted); line-height:1.6; }
.sk-step-text strong { color:var(--u-text); }

.sk-costs {
    display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
    gap:12px; margin-bottom:24px;
}
.sk-cost {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:12px;
    padding:16px 18px;
}
.sk-cost-label { font-size:11px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.sk-cost-val { font-size:20px; font-weight:800; color:var(--u-text); letter-spacing:-.3px; }
.sk-cost-note { font-size:11.5px; color:var(--u-muted); margin-top:3px; }

.sk-faq {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:14px;
    padding:6px 18px; margin-bottom:24px;
}
.sk-faq-item { border-bottom:1px solid var(--u-line); padding:14px 0; }
.sk-faq-item:last-child { border-bottom:none; }
.sk-faq-q {
    font-weight:700; font-size:14px; color:var(--u-text);
    cursor:pointer; display:flex; justify-content:space-between; align-items:center;
    list-style:none;
}
.sk-faq-q::after { content:"+"; color:#d97706; font-size:24px; font-weight:300; }
.sk-faq-item[open] .sk-faq-q::after { content:"−"; }
.sk-faq-a { color:var(--u-muted); font-size:13px; line-height:1.6; margin-top:8px; padding-right:30px; }
.sk-faq-a strong { color:var(--u-text); }
</style>
@endpush

<div class="sk-hero">
    <div class="sk-hero-body">
        <div class="sk-hero-label">🇩🇪 STUDIENKOLLEG REHBERİ</div>
        <div class="sk-hero-title">Türk lise mezunları için 1 yıllık hazırlık programı</div>
        <div class="sk-hero-sub">Türkiye'de lise mezunusun ama doğrudan Almanya üniversitesine giremiyorsun? Studienkolleg seninle ilgili — adım adım rehber.</div>
    </div>
</div>

<div class="sk-alert">
    <strong>⚠️ Studienkolleg sana lazım mı?</strong><br>
    Türkiye'de <strong>düz lise mezunuysan</strong> (Anadolu/Düz/İmam Hatip) → Çoğu Alman üniversitesine doğrudan başvuramazsın, önce Studienkolleg gerekli. <strong>Anadolu/Fen lisesi mezunu + en az 2 yıl üniversite okumuşsan</strong> → genelde Studienkolleg gerekmez. <strong>YKS sonucun ile</strong> bazı federal eyaletlerde direkt başvuru mümkün — kontrol edilmeli.
</div>

{{-- ════════ Studienkolleg Türleri ════════ --}}
<div class="sk-section-title"><x-icon name="bookmark" size="18" /> Studienkolleg Kursları <small>5 ana tür</small></div>
<div class="sk-types">
    <div class="sk-type" style="--sk-color:#2563eb;">
        <div class="sk-type-code">T-Kurs</div>
        <div class="sk-type-name">Technik (Teknik)</div>
        <div class="sk-type-desc">Matematik, Fizik, Kimya, Bilişim, İnformatik. Almanca + 2 yan ders.</div>
        <span class="sk-type-target">→ Mühendislik, Matematik, Bilgisayar</span>
    </div>
    <div class="sk-type" style="--sk-color:#16a34a;">
        <div class="sk-type-code">M-Kurs</div>
        <div class="sk-type-name">Medizin (Tıp)</div>
        <div class="sk-type-desc">Biyoloji, Kimya, Fizik, Matematik. En zorlu kurslardan biri.</div>
        <span class="sk-type-target">→ Tıp, Diş Hekimliği, Veteriner</span>
    </div>
    <div class="sk-type" style="--sk-color:#7c3aed;">
        <div class="sk-type-code">W-Kurs</div>
        <div class="sk-type-name">Wirtschaft (Ekonomi)</div>
        <div class="sk-type-desc">Matematik, Ekonomi, Almanca, İngilizce, Tarih.</div>
        <span class="sk-type-target">→ İşletme, Ekonomi, BWL</span>
    </div>
    <div class="sk-type" style="--sk-color:#dc2626;">
        <div class="sk-type-code">G-Kurs</div>
        <div class="sk-type-name">Geisteswiss. (Sosyal)</div>
        <div class="sk-type-desc">Tarih, Almanca, Edebiyat, Sosyoloji, Felsefe.</div>
        <span class="sk-type-target">→ Sosyal Bilimler, Edebiyat</span>
    </div>
    <div class="sk-type" style="--sk-color:#0891b2;">
        <div class="sk-type-code">S-Kurs</div>
        <div class="sk-type-name">Sprachen (Dil)</div>
        <div class="sk-type-desc">Diller, Edebiyat, Tarih. Daha az okul açıyor.</div>
        <span class="sk-type-target">→ Dilbilim, Çeviri, Edebiyat</span>
    </div>
</div>

{{-- ════════ Adım Adım Süreç ════════ --}}
<div class="sk-section-title"><x-icon name="list-checks" size="18" /> Adım Adım Süreç <small>başvuru ipucu — ortalama 8-12 ay</small></div>
<div class="sk-steps">
    <div class="sk-step">
        <div class="sk-step-num">1</div>
        <div class="sk-step-body">
            <div class="sk-step-title">Almanca B1/B2 seviyesine ulaş</div>
            <div class="sk-step-text">Studienkolleg giriş sınavı (<strong>Feststellungsprüfung — FSP</strong>) için <strong>en az B2 Almanca</strong> şart. Türkiye'de Goethe-Institut veya özel kurs ile 6-9 ay yoğun hazırlık. Önce başvuru, sonra dil eğitimi DEĞİL — önce dil, sonra başvuru.</div>
        </div>
    </div>
    <div class="sk-step">
        <div class="sk-step-num">2</div>
        <div class="sk-step-body">
            <div class="sk-step-title">Hedef üniversite + bölüm seç</div>
            <div class="sk-step-text">Hangi üniversite/bölüme gideceksin? Buna göre Studienkolleg türünü (<strong>T/M/W/G/S</strong>) belirle. Üniversite + bölüm + Studienkolleg türü zinciri uyumlu olmalı.</div>
        </div>
    </div>
    <div class="sk-step">
        <div class="sk-step-num">3</div>
        <div class="sk-step-body">
            <div class="sk-step-title">Devlet Studienkolleg veya özel?</div>
            <div class="sk-step-text"><strong>Devlet:</strong> Ücretsiz / sembolik (€100-300/dönem) ama kontenjan sınırlı, sınav zor. <strong>Özel:</strong> €4.000-8.000/yıl, kontenjan geniş ama bazı üniversiteler kabul etmiyor — listede olmasına dikkat.</div>
        </div>
    </div>
    <div class="sk-step">
        <div class="sk-step-num">4</div>
        <div class="sk-step-body">
            <div class="sk-step-title">uni-assist üzerinden başvuru</div>
            <div class="sk-step-text">Lise diploması + transkript + Almanca sertifikası + APS belgesi → <strong>uni-assist.de</strong>. Devlet Studienkolleg'ler genelde üniversite üzerinden, özel olanlar doğrudan başvuruyu kabul edebilir.</div>
        </div>
    </div>
    <div class="sk-step">
        <div class="sk-step-num">5</div>
        <div class="sk-step-body">
            <div class="sk-step-title">Giriş sınavı (Aufnahmeprüfung)</div>
            <div class="sk-step-text">Devlet Studienkolleg'lerde <strong>Almanca + matematik</strong> sınavı. Türkiye'den gelenler için en yoğun rekabet alanı. Ortalamada 3-5 kişiden 1'i kazanır.</div>
        </div>
    </div>
    <div class="sk-step">
        <div class="sk-step-num">6</div>
        <div class="sk-step-body">
            <div class="sk-step-title">1 yıllık eğitim + FSP sınavı</div>
            <div class="sk-step-text">2 sömestr ders + dönem sonu <strong>Feststellungsprüfung (FSP)</strong>. Başarılı olursan üniversite başvurusuna devam — FSP notu uni-assist'e eklenir, üniversite kabul kararı verir.</div>
        </div>
    </div>
</div>

{{-- ════════ Bütçe ════════ --}}
<div class="sk-section-title"><x-icon name="euro" size="18" /> Yıllık Bütçe Tahmini</div>
<div class="sk-costs">
    <div class="sk-cost">
        <div class="sk-cost-label">Devlet okul</div>
        <div class="sk-cost-val">€300–600</div>
        <div class="sk-cost-note">Dönem ücreti (Semesterbeitrag)</div>
    </div>
    <div class="sk-cost">
        <div class="sk-cost-label">Özel okul</div>
        <div class="sk-cost-val">€4.000–8.000</div>
        <div class="sk-cost-note">Tüm yıl, ön ödeme</div>
    </div>
    <div class="sk-cost">
        <div class="sk-cost-label">Yaşam masrafı</div>
        <div class="sk-cost-val">€10.000–12.000</div>
        <div class="sk-cost-note">Kira + gıda + sigorta (Sperrkonto)</div>
    </div>
    <div class="sk-cost">
        <div class="sk-cost-label">Toplam (Devlet)</div>
        <div class="sk-cost-val">~€11.000</div>
        <div class="sk-cost-note">Ortalama 1 yıllık</div>
    </div>
</div>

{{-- ════════ Sık Sorulanlar ════════ --}}
<div class="sk-section-title"><x-icon name="help-circle" size="18" /> Sık Sorulanlar</div>
<div class="sk-faq">
    <details class="sk-faq-item">
        <summary class="sk-faq-q">YKS girdim, sınava girmedim — Studienkolleg gerekir mi?</summary>
        <div class="sk-faq-a">Çoğu eyalette <strong>EVET</strong>. YKS girmediysen veya yetersiz puan aldıysan Türkiye'deki bir üniversiteye giriş hakkı kazanamamış sayılırsın → Studienkolleg gerekir. <strong>Bavyera (Bayern)</strong> ve <strong>Baden-Württemberg</strong> bazı durumlarda esnek davranıyor — kontrol et.</div>
    </details>
    <details class="sk-faq-item">
        <summary class="sk-faq-q">Türkiye'de üniversite okuyorum (2 yıl bitirdim) — direkt başvurabilir miyim?</summary>
        <div class="sk-faq-a"><strong>Genelde EVET</strong> — Anadolu/Fen lisesi + 2 yıl üniversite (yani 4 sömestr başarılı) ile çoğu Alman üniversitesine doğrudan başvurabilirsin. Studienkolleg gerekmez. <strong>Önemli:</strong> Bölüm denkliği uyumlu olmalı (mühendislik → mühendislik vb.).</div>
    </details>
    <details class="sk-faq-item">
        <summary class="sk-faq-q">Studienkolleg başarısız olursam ne olur?</summary>
        <div class="sk-faq-a">FSP'yi <strong>bir kez tekrarlama hakkın</strong> var. İkinci de başarısız → Almanya'da Studienkolleg yolu kapanır. Alternatifler: özel Studienkolleg'de tekrar dene, Ausbildung'a yönel, veya Türkiye'ye geri dönüp YKS ile yeni denkleme git.</div>
    </details>
    <details class="sk-faq-item">
        <summary class="sk-faq-q">Studienkolleg yaparken çalışabilir miyim?</summary>
        <div class="sk-faq-a"><strong>Sınırlı</strong>. Studienkolleg öğrencisi statüsü öğrenci vizesiyle aynı kuralı taşır: <strong>yılda 120 tam gün</strong> veya <strong>240 yarım gün</strong>. Ama Studienkolleg yoğun program — pratikte zaman bulmak zor.</div>
    </details>
    <details class="sk-faq-item">
        <summary class="sk-faq-q">Devlet Studienkolleg'e giremezsem ne yaparım?</summary>
        <div class="sk-faq-a"><strong>1)</strong> Özel Studienkolleg (Hochschule Bremen, Carl-Duisberg gibi) — pahalı ama kontenjan rahat. <strong>2)</strong> Almanca'nı yükselt (C1) → bazı üniversiteler doğrudan kabul ediyor (Almanca C1 + yeterli not ile). <strong>3)</strong> Avusturya/İsviçre alternatif — kabul kriterleri farklı.</div>
    </details>
</div>

<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:16px 20px;font-size:13.5px;color:#1e3a8a;line-height:1.55;">
    <strong>💡 MentorDE önerisi:</strong> Studienkolleg sürecini tek başına yürütmek çok zorlu — özellikle uni-assist + devlet okul başvuru iletişimi. Danışmanlık almak %95 başarı oranına çıkartıyor. <strong>İlk adım: Almanca seviye tespit + hedef bölüm belirleme.</strong>
</div>

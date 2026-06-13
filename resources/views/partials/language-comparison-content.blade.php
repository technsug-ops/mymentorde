{{-- ══════════════════════════════════════════════════════════════════════════
  Almanca Dil Sertifikası Karşılaştırma + İnteraktif Quiz
  Guest + Student portallerinde ortak partial. Alpine.js ile dinamik.
══════════════════════════════════════════════════════════════════════════ --}}

@push('head')
<style>
.lc-hero {
    color:#fff; border-radius:14px; margin-bottom:20px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.14); position:relative;
    background:linear-gradient(135deg, #0e7490 0%, #2563eb 55%, #4f46e5 100%);
}
.lc-hero::before {
    content:''; position:absolute; inset:0;
    background:
        radial-gradient(circle at 18% 22%, rgba(255,255,255,.16), transparent 38%),
        radial-gradient(circle at 82% 78%, rgba(79,70,229,.30), transparent 45%);
    pointer-events:none;
}
.lc-hero::after {
    content:''; position:absolute; top:0; right:0; width:42%; height:100%;
    background-image:
        linear-gradient(rgba(255,255,255,.07) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.07) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: linear-gradient(to left, rgba(0,0,0,.8), transparent 100%);
    -webkit-mask-image: linear-gradient(to left, rgba(0,0,0,.8), transparent 100%);
    pointer-events:none;
}
.lc-hero-body { position:relative; padding:26px 28px; }
.lc-hero-label { font-size:11px; font-weight:700; letter-spacing:.18em; text-transform:uppercase; opacity:.85; }
.lc-hero-title { font-size:30px; font-weight:800; margin:8px 0 6px; line-height:1.1; letter-spacing:-.5px; }
.lc-hero-sub { font-size:14px; opacity:.92; line-height:1.55; max-width:620px; }

/* Quiz */
.lc-quiz {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:14px;
    padding:22px 24px; margin-bottom:24px;
}
.lc-quiz-title { font-weight:800; font-size:17px; margin:0 0 6px; letter-spacing:-.3px; }
.lc-quiz-sub { color:var(--u-muted); font-size:13px; margin-bottom:18px; }
.lc-q { margin-bottom:18px; }
.lc-q-label { font-size:13px; font-weight:700; color:var(--u-text); margin-bottom:8px; }
.lc-q-options { display:flex; gap:8px; flex-wrap:wrap; }
.lc-q-opt {
    padding:9px 14px; border-radius:10px; border:1.5px solid var(--u-line);
    background:var(--u-card); color:var(--u-text); cursor:pointer;
    font-size:13px; font-weight:600; transition:all .15s; font-family:inherit;
}
.lc-q-opt:hover { border-color:#2563eb; color:#2563eb; }
.lc-q-opt.active { border-color:#2563eb; background:#2563eb; color:#fff; }

.lc-result {
    background:linear-gradient(135deg, #eff6ff, #fff);
    border:1px solid #bfdbfe; border-left:4px solid #2563eb;
    border-radius:12px; padding:18px 22px; margin-top:6px;
}
.lc-result-pick { font-weight:800; font-size:18px; color:#1e40af; margin-bottom:6px; }
.lc-result-reason { color:#1e3a8a; font-size:13.5px; line-height:1.55; }

/* Comparison Table */
.lc-section-title { font-weight:800; font-size:18px; margin:28px 0 12px; letter-spacing:-.3px; display:flex; align-items:center; gap:8px; }
.lc-table-wrap { overflow-x:auto; border-radius:14px; border:1px solid var(--u-line); margin-bottom:24px; }
.lc-table { width:100%; border-collapse:collapse; min-width:620px; background:var(--u-card); }
.lc-table th, .lc-table td { padding:12px 14px; text-align:left; border-bottom:1px solid var(--u-line); font-size:13px; }
.lc-table th { background:var(--u-bg); font-weight:800; font-size:11.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--u-muted); }
.lc-table td:first-child { font-weight:700; color:var(--u-text); }
.lc-table .lc-badge {
    display:inline-block; padding:3px 9px; border-radius:999px;
    font-size:10.5px; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
}
.lc-badge-green { background:#dcfce7; color:#166534; }
.lc-badge-blue  { background:#dbeafe; color:#1e3a8a; }
.lc-badge-amber { background:#fef3c7; color:#854d0e; }

/* Score Converter */
.lc-converter {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:14px;
    padding:22px 24px; margin-bottom:24px;
}
.lc-conv-row { display:grid; grid-template-columns:1fr auto 1fr; gap:14px; align-items:end; margin-top:14px; }
@media(max-width:560px) { .lc-conv-row { grid-template-columns:1fr; } }
.lc-conv-field label { font-size:11.5px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:6px; }
.lc-conv-field input, .lc-conv-field select {
    width:100%; padding:11px 13px; border-radius:10px; border:1.5px solid var(--u-line);
    background:var(--u-card); color:var(--u-text); font-size:14px; font-family:inherit;
    font-weight:600;
}
.lc-conv-arrow { font-size:24px; color:var(--u-muted); text-align:center; padding-bottom:10px; }
.lc-conv-out {
    background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px;
    padding:13px 16px; margin-top:14px; font-size:13px; color:#14532d; line-height:1.55;
}

/* Tips */
.lc-tips { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:14px; margin-bottom:24px; }
.lc-tip {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:12px;
    padding:16px 18px; border-top:3px solid var(--accent, #2563eb);
}
.lc-tip-title { font-weight:800; font-size:14px; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
.lc-tip-body { font-size:12.5px; color:var(--u-muted); line-height:1.55; }
</style>
@endpush

<div class="lc-hero">
    <div class="lc-hero-body">
        <div class="lc-hero-label">🇩🇪 ALMANCA DİL SERTİFİKASI</div>
        <div class="lc-hero-title">Hangi sınav sana göre?</div>
        <div class="lc-hero-sub">DSH, TestDaF, Goethe ve telc karşılaştırması — 4 soruluk quiz ile sana en uygun sınavı bul. Bonus: puanlarını eşdeğer seviyelere dönüştür.</div>
    </div>
</div>

{{-- ════════ Interaktif Quiz ════════ --}}
<div class="lc-quiz" x-data="lcQuiz()">
    <div class="lc-quiz-title">🎯 Sana en uygun sınavı bul</div>
    <div class="lc-quiz-sub">4 soruyu yanıtla, kişiselleştirilmiş öneriyi al.</div>

    <div class="lc-q">
        <div class="lc-q-label">1. Almanya'da hangi alanda çalışmak istiyorsun?</div>
        <div class="lc-q-options">
            <button type="button" class="lc-q-opt" :class="{active: q1==='uni'}" @click="q1='uni'">Üniversite</button>
            <button type="button" class="lc-q-opt" :class="{active: q1==='studienkolleg'}" @click="q1='studienkolleg'">Studienkolleg</button>
            <button type="button" class="lc-q-opt" :class="{active: q1==='ausbildung'}" @click="q1='ausbildung'">Ausbildung / Meslek</button>
            <button type="button" class="lc-q-opt" :class="{active: q1==='visa'}" @click="q1='visa'">Sadece vize / aile birleşimi</button>
        </div>
    </div>

    <div class="lc-q">
        <div class="lc-q-label">2. Şu anki Almanca seviyen nedir?</div>
        <div class="lc-q-options">
            <button type="button" class="lc-q-opt" :class="{active: q2==='a1a2'}" @click="q2='a1a2'">A1–A2 (Başlangıç)</button>
            <button type="button" class="lc-q-opt" :class="{active: q2==='b1'}" @click="q2='b1'">B1 (Orta)</button>
            <button type="button" class="lc-q-opt" :class="{active: q2==='b2'}" @click="q2='b2'">B2 (Orta-İleri)</button>
            <button type="button" class="lc-q-opt" :class="{active: q2==='c1'}" @click="q2='c1'">C1+ (İleri)</button>
        </div>
    </div>

    <div class="lc-q">
        <div class="lc-q-label">3. Sınava ne zaman girmeyi planlıyorsun?</div>
        <div class="lc-q-options">
            <button type="button" class="lc-q-opt" :class="{active: q3==='1m'}" @click="q3='1m'">1 ay içinde</button>
            <button type="button" class="lc-q-opt" :class="{active: q3==='3m'}" @click="q3='3m'">2–3 ay içinde</button>
            <button type="button" class="lc-q-opt" :class="{active: q3==='6m'}" @click="q3='6m'">4–6 ay içinde</button>
            <button type="button" class="lc-q-opt" :class="{active: q3==='12m'}" @click="q3='12m'">6+ ay var</button>
        </div>
    </div>

    <div class="lc-q">
        <div class="lc-q-label">4. Bütçen ne durumda?</div>
        <div class="lc-q-options">
            <button type="button" class="lc-q-opt" :class="{active: q4==='low'}" @click="q4='low'">Mümkün olduğunca ucuz</button>
            <button type="button" class="lc-q-opt" :class="{active: q4==='mid'}" @click="q4='mid'">€100–250 OK</button>
            <button type="button" class="lc-q-opt" :class="{active: q4==='high'}" @click="q4='high'">Sorun değil, en iyi seçenek</button>
        </div>
    </div>

    <div class="lc-result" x-show="q1 && q2 && q3 && q4" x-cloak>
        <div class="lc-result-pick">🎯 Önerimiz: <span x-text="result().pick"></span></div>
        <div class="lc-result-reason" x-text="result().reason"></div>
    </div>
</div>

{{-- ════════ Karşılaştırma Tablosu ════════ --}}
<div class="lc-section-title"><x-icon name="bar-chart-3" size="18" /> Sınavları Karşılaştır</div>
<div class="lc-table-wrap">
    <table class="lc-table">
        <thead>
            <tr>
                <th>Sınav</th>
                <th>Kabul Eden</th>
                <th>Süre</th>
                <th>Ücret</th>
                <th>Geçer Not</th>
                <th>Geçerlilik</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>DSH</strong><span class="lc-badge lc-badge-blue" style="margin-left:8px;">Üniversite</span></td>
                <td>Tüm Alman üniversiteleri</td>
                <td>4 saat</td>
                <td>€80–180</td>
                <td>DSH-2 (67%+)</td>
                <td>Süresiz</td>
            </tr>
            <tr>
                <td><strong>TestDaF</strong><span class="lc-badge lc-badge-blue" style="margin-left:8px;">Üniversite</span></td>
                <td>Tüm Alman üniversiteleri + uluslararası</td>
                <td>3 saat 10 dk</td>
                <td>€195</td>
                <td>4×TDN 4</td>
                <td>Süresiz</td>
            </tr>
            <tr>
                <td><strong>Goethe-Zertifikat C1</strong><span class="lc-badge lc-badge-green" style="margin-left:8px;">Geniş kabul</span></td>
                <td>Üniversiteler + iş başvuruları</td>
                <td>4 saat</td>
                <td>€220–280</td>
                <td>%60</td>
                <td>Süresiz</td>
            </tr>
            <tr>
                <td><strong>Goethe B2</strong><span class="lc-badge lc-badge-amber" style="margin-left:8px;">Visa / Ausbildung</span></td>
                <td>Vize + Studienkolleg + Ausbildung</td>
                <td>3 saat</td>
                <td>€200–260</td>
                <td>%60</td>
                <td>Süresiz</td>
            </tr>
            <tr>
                <td><strong>telc Deutsch B2/C1</strong><span class="lc-badge lc-badge-amber" style="margin-left:8px;">Alternatif</span></td>
                <td>Sınırlı üniversite + iş</td>
                <td>4 saat</td>
                <td>€140–200</td>
                <td>%60</td>
                <td>Süresiz</td>
            </tr>
            <tr>
                <td><strong>ÖSD Zertifikat C1</strong><span class="lc-badge lc-badge-amber" style="margin-left:8px;">Avusturya odaklı</span></td>
                <td>AT üniversiteleri + bazı DE</td>
                <td>4 saat</td>
                <td>€150–230</td>
                <td>%60</td>
                <td>Süresiz</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ════════ Puan Dönüştürücü ════════ --}}
<div class="lc-section-title"><x-icon name="zap" size="18" /> Puan Dönüştürücü</div>
<div class="lc-converter" x-data="lcConverter()">
    <div style="font-size:13.5px;color:var(--u-muted);line-height:1.55;">
        Bir sınavın puanını gir, diğer sınavlardaki eşdeğer seviyelerini gör.
    </div>
    <div class="lc-conv-row">
        <div class="lc-conv-field">
            <label>Sınav</label>
            <select x-model="exam">
                <option value="testdaf">TestDaF (TDN)</option>
                <option value="dsh">DSH</option>
                <option value="goethe">Goethe-Zertifikat</option>
                <option value="cefr">CEFR (A1–C2)</option>
            </select>
        </div>
        <div class="lc-conv-arrow">→</div>
        <div class="lc-conv-field">
            <label>Puan / Seviye</label>
            <select x-model="score">
                <template x-for="opt in scoreOptions()" :key="opt.value">
                    <option :value="opt.value" x-text="opt.label"></option>
                </template>
            </select>
        </div>
    </div>
    <div class="lc-conv-out" x-show="score" x-cloak>
        <strong>📊 Eşdeğer seviyeler:</strong>
        <div style="margin-top:6px;line-height:1.7;" x-html="convertResult()"></div>
    </div>
</div>

{{-- ════════ İpuçları ════════ --}}
<div class="lc-section-title"><x-icon name="sparkles" size="18" /> Hazırlık İpuçları</div>
<div class="lc-tips">
    <div class="lc-tip" style="--accent:#16a34a;">
        <div class="lc-tip-title">📚 Hangi materyalleri kullan?</div>
        <div class="lc-tip-body"><strong>Goethe-Institut</strong> resmi kitapları, <strong>Mit Erfolg zu TestDaF</strong>, <strong>Fit für die DSH</strong>. YouTube: <em>Easy German</em>, <em>Deutsch lernen mit Anja</em>.</div>
    </div>
    <div class="lc-tip" style="--accent:#0891b2;">
        <div class="lc-tip-title">🎯 Hangi bölüm zor?</div>
        <div class="lc-tip-body"><strong>Schreiben (yazma)</strong> en zoru — her gün 20 dk metin yazma alıştırması yap. <strong>Hören (dinleme)</strong> ikinci en zor — Tagesschau, Deutsche Welle podcast'leri.</div>
    </div>
    <div class="lc-tip" style="--accent:#f59e0b;">
        <div class="lc-tip-title">⏰ Ne zaman başvur?</div>
        <div class="lc-tip-body">TestDaF için <strong>2 ay önceden</strong>, DSH için üniversite takvimine göre değişir. Kontenjan dolar — erken kayıt önemli.</div>
    </div>
    <div class="lc-tip" style="--accent:#dc2626;">
        <div class="lc-tip-title">⚠️ Sık yapılan hatalar</div>
        <div class="lc-tip-body">"DSH-1 ile başvurabilirim" — HAYIR, çoğu üniversite DSH-2 ister. "Goethe C1 her yerde geçer" — kontrol et, bazı üniversiteler sadece DSH/TestDaF kabul ediyor.</div>
    </div>
</div>

<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:16px 20px;font-size:13.5px;color:#1e3a8a;line-height:1.55;margin-bottom:20px;">
    <strong>💡 Hızlı öneri:</strong> %90 öğrenciye <strong>TestDaF</strong> öneriyoruz — uluslararası geçerli, fix ücret (€195), Türkiye'de de düzenleniyor. DSH sadece Almanya'da, fix kontenjan, üniversiteye özel.
</div>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
function lcQuiz() {
    return {
        q1: '', q2: '', q3: '', q4: '',
        result() {
            // Karar matrisi
            if (this.q1 === 'visa' || this.q1 === 'ausbildung') {
                return {
                    pick: 'Goethe-Zertifikat B2',
                    reason: 'Vize ve Ausbildung başvurularında en yaygın kabul edilen sınav. Almanya konsolosluğu da tanır. Türkiye Goethe-Institut\'larında düzenli sınav var.'
                };
            }
            if (this.q1 === 'studienkolleg') {
                return {
                    pick: 'Goethe B2 veya telc B2',
                    reason: 'Studienkolleg için B2 seviyesi yeterli. Goethe B2 daha geniş kabul görür, telc B2 daha ucuz alternatif.'
                };
            }
            // Üniversite başvurusu
            if (this.q2 === 'a1a2' || this.q2 === 'b1') {
                return {
                    pick: 'Önce B2-C1 seviyesine ulaş, sonra TestDaF',
                    reason: 'Şu seviye için üniversite sınavlarına henüz erken. Önce Goethe veya bir dil kursu ile B2/C1\'e gel. Hedef: 4-6 ay yoğun hazırlık.'
                };
            }
            if (this.q3 === '1m') {
                return {
                    pick: 'Goethe-Zertifikat C1 (TestDaF kontenjan dolu olabilir)',
                    reason: 'TestDaF kontenjanı 1 aya genelde dolar. Goethe-Zertifikat C1 daha esnek tarih seçenekleri sunar. Türkiye Goethe-Institut\'larında sıkça düzenleniyor.'
                };
            }
            if (this.q4 === 'low') {
                return {
                    pick: 'DSH (eğer Almanya\'daysan) veya telc Deutsch C1',
                    reason: 'En ucuz seçenekler. DSH €80-180 ama sadece Almanya\'da düzenleniyor. telc Deutsch C1 €140-200 — Türkiye\'de de mevcut.'
                };
            }
            return {
                pick: 'TestDaF',
                reason: 'Üniversite başvurusu için en güvenli ve geniş kabul gören sınav. Fix ücret €195, Türkiye dahil dünya genelinde düzenleniyor, 4×TDN 4 ile tüm üniversitelere kabul.'
            };
        }
    };
}

function lcConverter() {
    // CEFR → puan eşitlikleri
    const equivalence = {
        'B1':  { testdaf: 'TDN 3 altı',  dsh: '< DSH-1',  goethe: 'Goethe B1',   cefr: 'B1 — Orta seviye' },
        'B2':  { testdaf: 'TDN 3',       dsh: 'DSH-1',    goethe: 'Goethe B2',   cefr: 'B2 — Orta-İleri' },
        'C1':  { testdaf: 'TDN 4',       dsh: 'DSH-2',    goethe: 'Goethe C1',   cefr: 'C1 — İleri' },
        'C1+': { testdaf: 'TDN 5',       dsh: 'DSH-3',    goethe: 'Goethe C1 80+', cefr: 'C1+ — Çok ileri' },
        'C2':  { testdaf: 'TDN 5 (üst)', dsh: 'DSH-3 üst', goethe: 'Goethe C2',  cefr: 'C2 — Akademik ileri' }
    };

    return {
        exam: 'testdaf',
        score: '',
        scoreOptions() {
            const opts = {
                testdaf: [
                    { value: 'B1',  label: 'TDN 3 altı (yetersiz)' },
                    { value: 'B2',  label: 'TDN 3' },
                    { value: 'C1',  label: 'TDN 4 — geçer not (önerilen)' },
                    { value: 'C1+', label: 'TDN 5' },
                ],
                dsh: [
                    { value: 'B1',  label: 'DSH-1 altı (yetersiz)' },
                    { value: 'B2',  label: 'DSH-1 (sınırlı kabul)' },
                    { value: 'C1',  label: 'DSH-2 (önerilen)' },
                    { value: 'C1+', label: 'DSH-3' },
                ],
                goethe: [
                    { value: 'B1',  label: 'Goethe B1' },
                    { value: 'B2',  label: 'Goethe B2' },
                    { value: 'C1',  label: 'Goethe C1' },
                    { value: 'C2',  label: 'Goethe C2' },
                ],
                cefr: [
                    { value: 'B1',  label: 'B1' },
                    { value: 'B2',  label: 'B2' },
                    { value: 'C1',  label: 'C1' },
                    { value: 'C2',  label: 'C2' },
                ],
            };
            return opts[this.exam] || [];
        },
        convertResult() {
            const e = equivalence[this.score];
            if (!e) return '';
            return [
                `<strong>CEFR:</strong> ${e.cefr}`,
                `<strong>TestDaF:</strong> ${e.testdaf}`,
                `<strong>DSH:</strong> ${e.dsh}`,
                `<strong>Goethe:</strong> ${e.goethe}`
            ].join(' &middot; ');
        }
    };
}
</script>

<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'MentorDE') }} Demo - Aday Öğrenci Kayıt</title>
    <style>
        :root {
            --bg: #eef3f9;
            --card: #ffffff;
            --line: #d8e0ea;
            --text: #132238;
            --muted: #52657d;
            --accent: #0f6bdc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(180deg, #eaf1f8 0%, #f8fbff 100%);
            color: var(--text);
        }
        .wrap {
            max-width: 1100px;
            margin: 28px auto;
            padding: 0 16px 30px;
        }
        .top {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        .top a {
            text-decoration: none;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--text);
            border-radius: 8px;
            padding: 9px 12px;
        }
        h1 { margin: 0 0 8px; }
        .meta { color: var(--muted); margin-bottom: 12px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
        }
        .card h2 { margin: 0 0 8px; font-size: 18px; }
        .row { display: flex; gap: 8px; margin-bottom: 8px; }
        .row > * { flex: 1; min-width: 0; }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 14px;
        }
        textarea { min-height: 84px; resize: vertical; }
        .actions { margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap; }
        button {
            border: 0;
            border-radius: 8px;
            background: var(--accent);
            color: #fff;
            padding: 9px 12px;
            cursor: pointer;
        }
        .status {
            margin-top: 8px;
            font-size: 13px;
            color: var(--muted);
        }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <a href="/demo">Demo Akisi</a>
        <a href="/apply">Public Basvuru</a>
        <a href="/config">Config</a>
        <a href="/student-card">Öğrenci Kartı</a>
        <a href="/manager/dashboard">Manager Dashboard</a>
    </div>

    <h1>Aday Öğrenci Kayıt (Demo)</h1>
    <div class="meta">Master v5.0 icindeki "8 bolum guest formu"nun demo sunum versiyonu.</div>

    <div class="grid">
        <section class="card">
            <h2>1) Kimlik</h2>
            <div class="row">
                <input id="gFirstName" placeholder="Ad" value="Ali">
                <input id="gLastName" placeholder="Soyad" value="Demir">
            </div>
            <div class="row">
                <input id="gEmail" placeholder="Email" value="ali@example.com">
                <input id="gPhone" placeholder="Telefon" value="+49 170 0000000">
            </div>
        </section>

        <section class="card">
            <h2>2) Egitim Hedefi</h2>
            <div class="row">
                <select id="gApplicationType">
                    <option value="bachelor">Lisans</option>
                    <option value="master" selected>Yuksek Lisans</option>
                    <option value="ausbildung">Ausbildung</option>
                </select>
                <input id="gTargetTerm" placeholder="Hedef donem" value="2026 Winter">
            </div>
            <div class="row">
                <input id="gTargetCity" placeholder="Hedef sehir" value="Berlin">
                <input id="gLanguageLevel" placeholder="Dil seviyesi" value="B2">
            </div>
        </section>

        <section class="card">
            <h2>3) Kaynak ve Bayi</h2>
            <div class="row">
                <select id="gLeadSource">
                    <option value="instagram" selected>Instagram</option>
                    <option value="google">Google</option>
                    <option value="referral">Referans</option>
                    <option value="dealer">Bayi</option>
                </select>
                <input id="gDealerCode" list="demoDealerSuggestions" placeholder="Dealer Kodu (opsiyonel)">
            </div>
            <div class="row">
                <input id="gCampaign" list="demoCampaignSuggestions" placeholder="Kampanya kodu" value="IG-2602-A">
            </div>
        </section>

        <section class="card">
            <h2>4) Atama</h2>
            <div class="row">
                <select id="gSeniorType">
                    <option value="lisans">Eğitim Danışmanı tipi: lisans</option>
                    <option value="master" selected>Eğitim Danışmanı tipi: master</option>
                </select>
                <input id="gBranch" list="demoBranchSuggestions" placeholder="Sube" value="istanbul">
            </div>
            <div class="row">
                <input id="gPriority" placeholder="Oncelik" value="normal">
                <input id="gRisk" placeholder="Risk seviyesi" value="normal">
            </div>
        </section>

        <section class="card">
            <h2>5) Durum</h2>
            <div class="row">
                <select id="gLeadStatus">
                    <option value="new" selected>new</option>
                    <option value="contacted">contacted</option>
                    <option value="meeting">meeting</option>
                    <option value="offer">offer</option>
                    <option value="contract_signed">contract_signed</option>
                </select>
                <select id="gMeeting">
                    <option value="planlandi" selected>Toplanti: planlandi</option>
                    <option value="tamamlandi">Toplanti: tamamlandi</option>
                </select>
            </div>
        </section>

        <section class="card">
            <h2>6) Notlar</h2>
            <textarea id="gNotes" placeholder="Ilk gorusme notu">Aile karar surecinde, paket karsilastirmasi bekleniyor.</textarea>
        </section>
    </div>

    <section class="card" style="margin-top:12px;">
        <h2>7) Sozlesme Oncesi Kontrol</h2>
        <div class="row">
            <select id="gConsent">
                <option value="true" selected>KVKK Onayi: evet</option>
                <option value="false">KVKK Onayi: hayir</option>
            </select>
            <select id="gDocReady">
                <option value="true" selected>Temel belge: hazir</option>
                <option value="false">Temel belge: eksik</option>
            </select>
        </div>
        <div class="actions">
            <button onclick="simulateGuestSave()">Aday Öğrenci Kaydet (Demo)</button>
            <button onclick="simulateConvert()">Öğrenciye Dönüştür (Demo)</button>
        </div>
        <div id="guestDemoStatus" class="status">Hazir.</div>
    </section>
</div>

<datalist id="demoDealerSuggestions"></datalist>
<datalist id="demoCampaignSuggestions"></datalist>
<datalist id="demoBranchSuggestions"></datalist>

<script>
async function loadDemoSuggestions() {
    const dealerList = document.getElementById('demoDealerSuggestions');
    const campaignList = document.getElementById('demoCampaignSuggestions');
    const branchList = document.getElementById('demoBranchSuggestions');
    try {
        const res = await fetch('/api/v1/config/suggestions?limit=200');
        const data = await res.json();
        const dealerValues = Array.from(new Set((data.dealer_ids || []).map(v => (v || '').toString().trim()).filter(Boolean)));
        const campaignValues = Array.from(new Set([...(data.campaign_codes || []), ...(data.campaign_names || [])].map(v => (v || '').toString().trim()).filter(Boolean)));
        const branchValues = Array.from(new Set((data.branches || []).map(v => (v || '').toString().trim()).filter(Boolean)));
        dealerList.innerHTML = dealerValues.map(v => `<option value="${v}"></option>`).join('');
        campaignList.innerHTML = campaignValues.map(v => `<option value="${v}"></option>`).join('');
        branchList.innerHTML = branchValues.map(v => `<option value="${v}"></option>`).join('');
    } catch (_e) {
        dealerList.innerHTML = '';
        campaignList.innerHTML = '';
        branchList.innerHTML = '';
    }
}

function simulateGuestSave() {
    const status = document.getElementById('guestDemoStatus');
    const first = document.getElementById('gFirstName').value.trim();
    const last = document.getElementById('gLastName').value.trim();
    const lead = document.getElementById('gLeadStatus').value;
    if (!first || !last) {
        status.textContent = 'Ad ve soyad zorunlu.';
        status.style.color = '#b91c1c';
        return;
    }
    status.textContent = `Demo kayit olustu: ${first} ${last} | lead_status:${lead}`;
    status.style.color = '#52657d';
}

function simulateConvert() {
    const status = document.getElementById('guestDemoStatus');
    const consent = document.getElementById('gConsent').value === 'true';
    if (!consent) {
        status.textContent = 'Donusum bloklandi: KVKK onayi gerekli.';
        status.style.color = '#b91c1c';
        return;
    }
    status.textContent = 'Donusum tetiklendi (demo): guests -> students, senior atama ve bildirim akisi baslatilir.';
    status.style.color = '#52657d';
}

loadDemoSuggestions();
</script>
</body>
</html>

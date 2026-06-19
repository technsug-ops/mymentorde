{{-- Cookie Consent Banner — DSGVO/TDDDG compliant (3 buton + kategori panel) --}}
<style>
.cc-banner {
    display:none; position:fixed; bottom:0; left:0; right:0; z-index:99999;
    background:#0f172a; color:#fff; padding:18px 24px;
    border-top:3px solid #1e40af; box-shadow:0 -4px 20px rgba(0,0,0,.3);
}
.cc-banner.show { display:block; }
.cc-banner-inner { max-width:1200px; margin:0 auto; display:flex; align-items:center; gap:18px; flex-wrap:wrap; }
.cc-banner .cc-text { flex:1; min-width:280px; }
.cc-banner .cc-title { font-size:14px; font-weight:700; margin-bottom:4px; }
.cc-banner .cc-desc { font-size:12px; color:rgba(255,255,255,.8); line-height:1.5; }
.cc-banner .cc-desc a { color:#93c5fd; text-decoration:underline; }
.cc-banner .cc-actions { display:flex; gap:8px; flex-wrap:wrap; flex-shrink:0; }
.cc-btn {
    border:1px solid rgba(255,255,255,.2); border-radius:7px;
    padding:9px 18px; font-size:12px; font-weight:600; cursor:pointer; font-family:inherit;
    transition:all .15s;
}
.cc-btn-secondary { background:rgba(255,255,255,.08); color:#fff; }
.cc-btn-secondary:hover { background:rgba(255,255,255,.16); }
.cc-btn-reject { background:rgba(255,255,255,.04); color:#fff; }
.cc-btn-reject:hover { background:rgba(220,38,38,.2); border-color:rgba(220,38,38,.4); }
.cc-btn-primary { background:#1e40af; color:#fff; border-color:#1e40af; font-weight:700; }
.cc-btn-primary:hover { background:#3b5fcc; }

/* Settings modal */
.cc-modal {
    display:none; position:fixed; inset:0; z-index:100000;
    background:rgba(15,23,42,.7); align-items:center; justify-content:center; padding:16px;
}
.cc-modal.show { display:flex; }
.cc-modal-card {
    background:#fff; color:#0f172a; border-radius:14px; max-width:560px; width:100%;
    max-height:90vh; overflow:auto; box-shadow:0 20px 60px rgba(0,0,0,.3);
}
.cc-modal-head { padding:20px 24px; border-bottom:1px solid #e2e8f0; }
.cc-modal-head h3 { font-size:18px; font-weight:800; margin:0 0 4px; }
.cc-modal-head p { font-size:13px; color:#64748b; margin:0; line-height:1.5; }
.cc-modal-body { padding:18px 24px; }
.cc-cat {
    display:flex; align-items:flex-start; gap:14px; padding:14px 0; border-bottom:1px solid #f1f5f9;
}
.cc-cat:last-child { border-bottom:none; }
.cc-cat-info { flex:1; min-width:0; }
.cc-cat-name { font-size:13.5px; font-weight:700; margin-bottom:3px; }
.cc-cat-desc { font-size:12px; color:#64748b; line-height:1.5; }
.cc-toggle { width:42px; height:24px; position:relative; flex-shrink:0; }
.cc-toggle input { opacity:0; width:0; height:0; }
.cc-toggle .slider {
    position:absolute; cursor:pointer; inset:0;
    background:#cbd5e1; border-radius:24px; transition:.2s;
}
.cc-toggle .slider:before {
    position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px;
    background:#fff; border-radius:50%; transition:.2s;
}
.cc-toggle input:checked + .slider { background:#16a34a; }
.cc-toggle input:checked + .slider:before { transform:translateX(18px); }
.cc-toggle input:disabled + .slider { background:#94a3b8; cursor:not-allowed; opacity:.6; }
.cc-modal-foot {
    padding:16px 24px; border-top:1px solid #e2e8f0;
    display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;
}
.cc-modal-foot .cc-btn { color:#0f172a; }
.cc-modal-foot .cc-btn-secondary { background:#f1f5f9; border-color:#e2e8f0; }
.cc-modal-foot .cc-btn-secondary:hover { background:#e2e8f0; }
.cc-modal-foot .cc-btn-reject { background:#fff; border-color:#fecaca; color:#991b1b; }
.cc-modal-foot .cc-btn-reject:hover { background:#fef2f2; }

@media (max-width:640px) {
    .cc-banner { padding:14px 16px; }
    .cc-banner-inner { flex-direction:column; align-items:stretch; }
    .cc-banner .cc-actions { width:100%; }
    .cc-banner .cc-actions .cc-btn { flex:1; min-width:0; padding:10px 8px; font-size:11px; }
}
</style>

{{-- Banner --}}
<div id="cookie-banner" class="cc-banner">
    <div class="cc-banner-inner">
        <div class="cc-text">
            <div class="cc-title">🍪 Çerez Tercihlerinizi Belirleyin</div>
            <div class="cc-desc">
                Bu site, deneyiminizi iyileştirmek için zorunlu çerezleri ve onayınızla analitik/pazarlama
                çerezlerini kullanır. <a href="/cookies">Çerez Politikası</a> · <a href="/privacy">Gizlilik Politikası</a>
            </div>
        </div>
        <div class="cc-actions">
            <button type="button" class="cc-btn cc-btn-reject" data-cc="reject">Tümünü Reddet</button>
            <button type="button" class="cc-btn cc-btn-secondary" data-cc="settings">⚙ Ayarlar</button>
            <button type="button" class="cc-btn cc-btn-primary" data-cc="accept-all">Tümünü Kabul Et</button>
        </div>
    </div>
</div>

{{-- Settings Modal --}}
<div id="cookie-settings-modal" class="cc-modal">
    <div class="cc-modal-card">
        <div class="cc-modal-head">
            <h3>🍪 Çerez Ayarları</h3>
            <p>Hangi çerez kategorilerine onay vereceğinizi seçin. İstediğiniz zaman değiştirebilirsiniz.</p>
        </div>
        <div class="cc-modal-body">
            <div class="cc-cat">
                <div class="cc-cat-info">
                    <div class="cc-cat-name">🔒 Zorunlu Çerezler</div>
                    <div class="cc-cat-desc">Site temel işlevleri için gerekli (oturum, güvenlik, dil tercihi). Devre dışı bırakılamaz.</div>
                </div>
                <label class="cc-toggle">
                    <input type="checkbox" data-cc-cat="essential" checked disabled>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="cc-cat">
                <div class="cc-cat-info">
                    <div class="cc-cat-name">📊 Analitik Çerezler</div>
                    <div class="cc-cat-desc">Sitenin nasıl kullanıldığını anlamamızı sağlar (Google Analytics, PostHog). Onayınızla yüklenir.</div>
                </div>
                <label class="cc-toggle">
                    <input type="checkbox" data-cc-cat="analytics">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="cc-cat">
                <div class="cc-cat-info">
                    <div class="cc-cat-name">📣 Pazarlama Çerezleri</div>
                    <div class="cc-cat-desc">Reklam ve hedefleme amaçlı (Meta Pixel, Google Ads). Onayınızla yüklenir.</div>
                </div>
                <label class="cc-toggle">
                    <input type="checkbox" data-cc-cat="marketing">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="cc-cat">
                <div class="cc-cat-info">
                    <div class="cc-cat-name">⚙ Tercih Çerezleri</div>
                    <div class="cc-cat-desc">Tema, görünüm tercihleri gibi kişisel ayarlarınızı hatırlamak için.</div>
                </div>
                <label class="cc-toggle">
                    <input type="checkbox" data-cc-cat="preferences">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        <div class="cc-modal-foot">
            <button type="button" class="cc-btn cc-btn-reject" data-cc="reject">Tümünü Reddet</button>
            <button type="button" class="cc-btn cc-btn-secondary" data-cc="save-settings">Seçimleri Kaydet</button>
            <button type="button" class="cc-btn cc-btn-primary" data-cc="accept-all">Tümünü Kabul Et</button>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var BANNER_VERSION = '2026-04-28-v2'; // değişirse banner tekrar gösterilir
    var STORAGE_KEY = 'cookie_consent';

    function getStoredConsent(){
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return null;
            var data = JSON.parse(raw);
            if (data.version !== BANNER_VERSION) return null;
            return data;
        } catch(e) { return null; }
    }

    function saveConsent(categories){
        var data = {
            version: BANNER_VERSION,
            categories: categories,
            decided_at: new Date().toISOString(),
        };
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); } catch(e){}

        // DB'ye logla (versiyon + kategoriler — denetim için)
        var csrf = document.querySelector('meta[name=csrf-token]');
        fetch('/cookie-consent', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf ? csrf.content : '',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
            credentials: 'same-origin',
        }).catch(function(){});

        applyConsentToTrackers(categories);
    }

    function applyConsentToTrackers(cats){
        try {
            var ev = new CustomEvent('cookie-consent-updated', { detail: cats });
            window.dispatchEvent(ev);
        } catch(e){}
    }

    function hideAll(){
        document.getElementById('cookie-banner').classList.remove('show');
        document.getElementById('cookie-settings-modal').classList.remove('show');
    }

    function openSettings(){
        var stored = getStoredConsent();
        var cats = stored ? stored.categories : { essential:true, analytics:false, marketing:false, preferences:false };
        document.querySelectorAll('[data-cc-cat]').forEach(function(input){
            var k = input.dataset.ccCat;
            if (k === 'essential') return;
            input.checked = !!cats[k];
        });
        document.getElementById('cookie-banner').classList.remove('show');
        document.getElementById('cookie-settings-modal').classList.add('show');
    }

    var stored = getStoredConsent();
    if (!stored) {
        document.getElementById('cookie-banner').classList.add('show');
    } else {
        applyConsentToTrackers(stored.categories);
    }

    document.querySelectorAll('[data-cc]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var action = this.dataset.cc;
            if (action === 'accept-all') {
                saveConsent({ essential:true, analytics:true, marketing:true, preferences:true });
                hideAll();
            } else if (action === 'reject') {
                saveConsent({ essential:true, analytics:false, marketing:false, preferences:false });
                hideAll();
            } else if (action === 'settings') {
                openSettings();
            } else if (action === 'save-settings') {
                var cats = { essential:true };
                document.querySelectorAll('[data-cc-cat]').forEach(function(input){
                    cats[input.dataset.ccCat] = !!input.checked;
                });
                saveConsent(cats);
                hideAll();
            }
        });
    });

    // Footer'dan açma — global tetik
    window.openCookieSettings = openSettings;
})();
</script>

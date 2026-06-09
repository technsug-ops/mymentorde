{{--
    Polymorphic Belge Talep Linki Modal — premium "doc_request" özelliği için
    ortak markup. Her modülde sadece STORE_URL ve INDEX_URL değişir.

    Parametreler (her zaman geçilir):
      - $modalId       : div id (örn. 'docReqModal_user')
      - $btnId         : tetikleyici buton id ('docReqOpenBtn_user')
      - $indexRoute    : Laravel route name (örn. 'manager.user.document-tokens.index')
      - $storeRoute    : Laravel route name (örn. 'manager.user.document-tokens.store')
      - $routeParam    : route param değeri (User->id, Dealer->id, vs.)
      - $targetLabel   : "kim için" gösterim (örn. "Burak Şahin (HR)" veya "Bayi: ACME LTD")
      - $sendIntro     : WhatsApp/email mesajı için intro (örn. "Merhaba, MentorDE'den ...")

    Kullanım:
      @include('partials.doc-request-modal', [
          'modalId'     => 'docReqModal_user',
          'btnId'       => 'docReqOpenBtn_user',
          'indexRoute'  => 'manager.user.document-tokens.index',
          'storeRoute'  => 'manager.user.document-tokens.store',
          'routeParam'  => $user->id,
          'targetLabel' => $user->name,
          'sendIntro'   => "Merhaba, MentorDE'den belge talebimiz var.",
      ])
--}}
@php
    // ── Addon protection layer — modül kapalı veya rota yoksa partial sessizce skip ──
    try {
        $_drShow = \App\Support\ModuleAccess::enabled('doc_request')
            && \Illuminate\Support\Facades\Route::has($indexRoute)
            && \Illuminate\Support\Facades\Route::has($storeRoute);
        if ($_drShow) {
            $_drModalId = $modalId;
            $_drBtnId   = $btnId;
            $_drIndex   = route($indexRoute, $routeParam);
            $_drStore   = route($storeRoute, $routeParam);
            $_drLabel   = (string) ($targetLabel ?? '');
            $_drIntro   = (string) ($sendIntro ?? "Merhaba, MentorDE'den belge talebimiz var. Lütfen aşağıdaki linke tıklayıp belgeyi yükleyin:");
        }
    } catch (\Throwable $_drEx) {
        $_drShow = false;
    }
@endphp

@if($_drShow ?? false)

<div id="{{ $_drModalId }}" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:14px;max-width:520px;width:100%;max-height:92vh;overflow:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="padding:18px 22px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <strong style="font-size:15px;">📲 Belge Talep Linki Oluştur</strong>
            <button type="button" data-dr-close="{{ $_drModalId }}" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <div style="padding:18px 22px;">
            @if($_drLabel)
                <p style="font-size:13px;color:#475569;line-height:1.5;margin:0 0 14px;">
                    <strong>{{ $_drLabel }}</strong> için tek-kullanımlık belge yükleme linki oluştur.
                </p>
            @endif
            <div style="display:flex;flex-direction:column;gap:12px;">
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">İstenen Belge</span>
                    <select data-dr-cat style="padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;">
                        <option value="">— Yükleniyor —</option>
                    </select>
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">Geçerlilik Süresi</span>
                    <select data-dr-expiry style="padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;">
                        <option value="24">24 saat</option>
                        <option value="48" selected>48 saat (önerilen)</option>
                        <option value="72">3 gün</option>
                        <option value="168">7 gün</option>
                    </select>
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">Özel Mesaj (opsiyonel)</span>
                    <textarea data-dr-msg rows="2" maxlength="500"
                        style="padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;font-family:inherit;resize:vertical;"
                        placeholder="Örn: Pasaportunuzu net çekin."></textarea>
                </label>
                {{-- D7: Hatirlatma destegi — email girilirse 24h ve final hatirlatma otomatik --}}
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">
                        Alıcı E-posta <span style="color:#94a3b8;font-weight:500;text-transform:none;letter-spacing:0;">(opsiyonel — hatırlatma için)</span>
                    </span>
                    <input type="email" data-dr-email maxlength="180"
                           value="{{ $prefillEmail ?? '' }}"
                           placeholder="aday@example.com"
                           style="padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;">
                    <span style="font-size:11px;color:#64748b;line-height:1.4;">
                        ✉ Girersen 24 saat içinde tıklanmazsa hatırlatma, süre dolmadan 1 saat önce son uyarı maili gider.
                    </span>
                </label>
                {{-- D6: WhatsApp direkt gönderim için telefon (opsiyonel) --}}
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">
                        WhatsApp Telefon <span style="color:#94a3b8;font-weight:500;text-transform:none;letter-spacing:0;">(opsiyonel — direkt sohbete götürür)</span>
                    </span>
                    <input type="tel" data-dr-phone maxlength="50"
                           value="{{ $prefillPhone ?? '' }}"
                           placeholder="+905551234567 veya 5551234567"
                           style="padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;">
                    <span style="font-size:11px;color:#64748b;line-height:1.4;">
                        💬 Girersen WhatsApp butonu doğrudan o numaraya açar — manuel arama yok.
                    </span>
                </label>
            </div>
            <button type="button" data-dr-gen data-track="cta_clicked" data-ph-cta-name="doc_request_create_link"
                    style="margin-top:16px;width:100%;padding:12px 18px;border:none;border-radius:10px;background:linear-gradient(135deg,#1e40af,#3b5fcc);color:#fff;font-size:14px;font-weight:700;cursor:pointer;">
                🔗 Linki Oluştur
            </button>
            <div data-dr-result style="display:none;margin-top:16px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
                <div style="font-size:12px;font-weight:700;color:#166534;margin-bottom:8px;">✅ Link hazır:</div>
                <input type="text" data-dr-url readonly
                       style="width:100%;padding:8px 10px;border:1px solid #bbf7d0;border-radius:6px;font-family:ui-monospace,monospace;font-size:11.5px;background:#fff;margin-bottom:10px;">
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="button" data-dr-copy
                            style="flex:1;min-width:100px;padding:8px 12px;border:1px solid #16a34a;background:#fff;color:#166534;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">
                        📋 Kopyala
                    </button>
                    <a data-dr-wa target="_blank" href="#"
                       style="flex:1;min-width:100px;padding:8px 12px;background:#25d366;color:#fff;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;text-align:center;">
                        💬 WhatsApp'la Gönder
                    </a>
                </div>
                <div style="font-size:11px;color:#65a30d;margin-top:8px;">
                    Bu link tek-kullanımlık. Aday yüklediğinde otomatik geçersizleşir.
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var modalId   = @json($_drModalId);
    var btnId     = @json($_drBtnId);
    var INDEX_URL = @json($_drIndex);
    var STORE_URL = @json($_drStore);
    var INTRO     = @json($_drIntro);
    var CSRF      = @json(csrf_token());

    var modal = document.getElementById(modalId);
    var btn   = document.getElementById(btnId);
    if (!modal || !btn) return;

    var catSelect    = modal.querySelector('[data-dr-cat]');
    var expirySelect = modal.querySelector('[data-dr-expiry]');
    var msgInput     = modal.querySelector('[data-dr-msg]');
    var emailInput   = modal.querySelector('[data-dr-email]');
    var phoneInput   = modal.querySelector('[data-dr-phone]');
    var genBtn       = modal.querySelector('[data-dr-gen]');
    var resultBox    = modal.querySelector('[data-dr-result]');
    var urlInput     = modal.querySelector('[data-dr-url]');
    var copyBtn      = modal.querySelector('[data-dr-copy]');
    var waBtn        = modal.querySelector('[data-dr-wa]');
    var closeBtn     = modal.querySelector('[data-dr-close="' + modalId + '"]');

    function loadCategories(){
        catSelect.innerHTML = '<option value="">— Yükleniyor —</option>';
        fetch(INDEX_URL, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }})
            .then(r => r.json())
            .then(data => {
                var groups = {};
                (data.categories || []).forEach(function(c){
                    var top = c.top_category_code || 'diger';
                    if (!groups[top]) groups[top] = [];
                    groups[top].push(c);
                });
                catSelect.innerHTML = '<option value="">Belge seç...</option>';
                var labelMap = { uni_assist:'Uni Asist', vize:'Vize', vize_surec:'Vize Süreç Belgeleri', dil_okulu:'Dil Okulu', uni_kayit:'Üniversite Kayıt', yurt:'İkamet', diger:'Diğer' };
                Object.keys(labelMap).forEach(function(top){
                    if (!groups[top] || !groups[top].length) return;
                    var og = document.createElement('optgroup'); og.label = labelMap[top];
                    groups[top].forEach(function(c){
                        var opt = document.createElement('option');
                        opt.value = c.code;
                        opt.textContent = c.name_tr + (c.name_de ? ' / ' + c.name_de : '');
                        og.appendChild(opt);
                    });
                    catSelect.appendChild(og);
                });
            })
            .catch(() => { catSelect.innerHTML = '<option value="">Yükleme hatası</option>'; });
    }
    // Prefill değerleri sakla — modal her açılışta input'lar reset olmasın
    var PREFILL_EMAIL = emailInput ? emailInput.value : '';
    var PREFILL_PHONE = phoneInput ? phoneInput.value : '';

    function openModal(){
        modal.style.display='flex';
        resultBox.style.display='none';
        msgInput.value='';
        if (emailInput) emailInput.value = PREFILL_EMAIL;
        if (phoneInput) phoneInput.value = PREFILL_PHONE;
        loadCategories();
    }
    function closeModal(){ modal.style.display='none'; }

    btn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

    genBtn.addEventListener('click', function(){
        var cat = catSelect.value;
        if (!cat) { alert('Lütfen bir belge seç.'); return; }
        genBtn.disabled = true; genBtn.textContent = '⏳ Oluşturuluyor...';
        fetch(STORE_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({
                category_code: cat,
                expires_hours: parseInt(expirySelect.value, 10) || 48,
                custom_message: msgInput.value || null,
                recipient_email: (emailInput && emailInput.value) ? emailInput.value.trim() : null,
                recipient_phone: (phoneInput && phoneInput.value) ? phoneInput.value.trim() : null,
            })
        })
        .then(r => r.json().then(d => ({ ok:r.ok, data:d })))
        .then(res => {
            genBtn.disabled = false; genBtn.textContent = '🔗 Linki Oluştur';
            if (!res.ok) { alert(res.data.error || 'Hata oluştu.'); return; }
            urlInput.value = res.data.url;
            var msg = INTRO + "\n\n" + res.data.url;
            // D6: telefon girildiyse direkt o numaraya açar (https://wa.me/{phone}?text=...)
            // E.164 normalize: rakam dışını temizle, başına 90 (TR) ekle eksikse
            var phoneRaw = (phoneInput && phoneInput.value) ? phoneInput.value.trim() : '';
            var phoneDigits = phoneRaw.replace(/[^0-9]/g, '');
            if (phoneDigits.length > 0) {
                // 0 ile başlıyorsa kaldır (Türkiye yerel format), country code yoksa 90 ekle
                if (phoneDigits.charAt(0) === '0') phoneDigits = phoneDigits.substring(1);
                if (phoneDigits.length === 10) phoneDigits = '90' + phoneDigits; // 10 hane → TR mobil
                waBtn.href = 'https://wa.me/' + phoneDigits + '?text=' + encodeURIComponent(msg);
                waBtn.textContent = '💬 ' + phoneDigits + ' WhatsApp';
            } else {
                waBtn.href = 'https://wa.me/?text=' + encodeURIComponent(msg);
                waBtn.textContent = "💬 WhatsApp'la Gönder";
            }
            resultBox.style.display = 'block';
        })
        .catch(() => { genBtn.disabled = false; genBtn.textContent = '🔗 Linki Oluştur'; alert('Bağlantı hatası.'); });
    });

    copyBtn.addEventListener('click', function(){
        urlInput.select();
        navigator.clipboard.writeText(urlInput.value).then(function(){
            copyBtn.textContent = '✓ Kopyalandı';
            setTimeout(function(){ copyBtn.textContent = '📋 Kopyala'; }, 2000);
        }).catch(function(){ document.execCommand('copy'); });
    });
})();
</script>
@endif

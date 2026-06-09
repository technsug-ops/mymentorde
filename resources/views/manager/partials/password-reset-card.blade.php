{{--
    Manager Şifre Sıfırlama — Addon Partial (SEPARABLE)

    Tüm koruma katmanları:
    - ModuleAccess::enabled('manager_password_reset') gate
    - Route::has('manager.quick-admin.password.reset') varlık kontrolü
    - try/catch tüm hata noktaları
    - Partial silinse @includeIf sessizce skip eder

    Parametreler:
      - $email       (string) hedef e-posta
      - $name        (string) görünen isim
      - $defaultRole (string) 'guest' | 'student' — backend user oluştururken kullanılır
      - $personLabel (string) UI etiket "Aday Öğrenci" | "Öğrenci" vb.
      - $idSuffix    (string) HTML id çakışmasın diye unique suffix (örn. 'guest63', 'student123')
--}}
@php
    try {
        $_prShow = \App\Support\ModuleAccess::enabled('manager_password_reset')
            && \Illuminate\Support\Facades\Route::has('manager.quick-admin.password.reset')
            && ! empty(trim((string) ($email ?? '')));
        $_prEmail = (string) ($email ?? '');
        $_prName  = (string) ($name ?? $_prEmail);
        $_prRole  = (string) ($defaultRole ?? 'guest');
        $_prLabel = (string) ($personLabel ?? 'Kullanıcı');
        $_prId    = preg_replace('/[^a-zA-Z0-9]/', '', (string) ($idSuffix ?? uniqid()));
    } catch (\Throwable $_e) {
        $_prShow = false;
    }
@endphp

@if($_prShow)
<section class="panel gd-panel" style="border:1px solid #fde68a;background:#fffbeb;">
    <h2 style="color:#92400e;">🔐 Şifre Sıfırla</h2>
    <div style="font-size:12px;color:#78350f;margin-bottom:10px;line-height:1.5;">
        Tıklayınca otomatik geçici şifre üretilir ve <strong>{{ $_prLabel }} e-postasına</strong>
        gönderilir. Kullanıcı giriş yapınca <strong>zorunlu olarak yeni şifre belirler</strong>.
        Hesabı yoksa otomatik oluşturulur.
    </div>
    <div class="gd-field">
        <label>Hedef E-posta</label>
        <div class="gd-readonly" id="pwdReset-email-{{ $_prId }}">{{ $_prEmail }}</div>
        <input type="hidden" id="pwdReset-name-{{ $_prId }}" value="{{ $_prName }}">
        <input type="hidden" id="pwdReset-role-{{ $_prId }}" value="{{ $_prRole }}">
    </div>
    <div class="gd-actions">
        <button type="button" class="btn" id="pwdReset-btn-{{ $_prId }}"
                style="background:#f59e0b;color:#fff;border:none;font-weight:600;">
            📧 Şifre Sıfırlama Maili Gönder
        </button>
    </div>
    <div id="pwdReset-result-{{ $_prId }}" style="display:none;margin-top:10px;padding:10px 12px;border-radius:6px;background:#dcfce7;border:1px solid #86efac;color:#166534;font-size:12px;font-weight:600;"></div>
    <div id="pwdReset-error-{{ $_prId }}" style="display:none;margin-top:8px;padding:8px 10px;border-radius:6px;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;font-size:12px;"></div>
</section>

{{-- Inline handler — kendi suffix'iyle izole, başka partial'la çakışmaz --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    try {
        var ID = @json($_prId);
        var btn       = document.getElementById('pwdReset-btn-' + ID);
        var emailBox  = document.getElementById('pwdReset-email-' + ID);
        var nameEl    = document.getElementById('pwdReset-name-' + ID);
        var roleEl    = document.getElementById('pwdReset-role-' + ID);
        var resultBox = document.getElementById('pwdReset-result-' + ID);
        var errBox    = document.getElementById('pwdReset-error-' + ID);
        if (!btn || !emailBox || !resultBox || !errBox) return; // partial render olmamış → çık

        var CSRF = @json(csrf_token());
        var URL  = @json(route('manager.quick-admin.password.reset'));
        var EMAIL = (emailBox.textContent || '').trim();
        var NAME = nameEl ? (nameEl.value || '').trim() : '';
        var ROLE = roleEl ? (roleEl.value || '').trim() : 'guest';

        btn.addEventListener('click', function(){
            errBox.style.display = 'none';
            errBox.textContent = '';
            resultBox.style.display = 'none';

            if (!confirm("Şifre sıfırlama maili " + EMAIL + " adresine gönderilsin mi?\n\nKullanıcı geçici şifre ile giriş yapıp yeni şifresini belirleyecek.")) return;

            btn.disabled = true;
            var oldText = btn.textContent;
            btn.textContent = 'Mail gönderiliyor...';

            var body = { email: EMAIL, default_role: ROLE };
            if (NAME) body.name = NAME;

            fetch(URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(body)
            })
            .then(function(r){ return r.json().then(function(j){ return { ok: r.ok, body: j }; }); })
            .then(function(res){
                btn.disabled = false;
                btn.textContent = oldText;
                if (!res.ok || !res.body.ok) {
                    errBox.textContent = res.body.message || 'Mail gönderilemedi.';
                    errBox.style.display = 'block';
                    return;
                }
                resultBox.textContent = res.body.message || '✓ Mail gönderildi.';
                resultBox.style.display = 'block';
            })
            .catch(function(){
                btn.disabled = false;
                btn.textContent = oldText;
                errBox.textContent = 'Ağ hatası — tekrar deneyin.';
                errBox.style.display = 'block';
            });
        });
    } catch (e) {
        // Partial JS hatası ana sayfayı bozmasın
        if (window.console) console.warn('password-reset-card init failed', e);
    }
})();
</script>
@endif

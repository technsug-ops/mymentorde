{{-- ════════════════════════════════════════════════════════════════════
     Hızlı Yönetim Kartı — Yeni Senior + Mevcut Kullanıcıya Rol Verme
     ════════════════════════════════════════════════════════════════════ --}}

<style>
.qa-card { background: var(--u-card,#fff); border: 1px solid var(--u-line,#e2e8f0); border-radius: 12px; padding: 18px 22px; margin: 16px 0 22px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
.qa-card-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.qa-card-head h3 { margin: 0; font-size: 14.5px; font-weight: 800; color: var(--u-text,#111827); }
.qa-card-head .qa-icon { font-size: 22px; }
.qa-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.qa-btn { padding: 10px 18px; background: #5b2e91; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; transition: transform .12s, box-shadow .12s; }
.qa-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(91,46,145,.2); }
.qa-btn.alt { background: rgba(91,46,145,.08); color: #5b2e91; border: 1px solid rgba(91,46,145,.3); }
.qa-btn.alt:hover { background: rgba(91,46,145,.14); }

/* Modal */
.qa-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 1050; align-items: flex-start; justify-content: center; padding: 60px 16px 16px; overflow-y: auto; }
.qa-modal-overlay.is-open { display: flex; }
.qa-modal { background: var(--u-card,#fff); border-radius: 12px; max-width: 520px; width: 100%; padding: 22px 24px; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
.qa-modal h3 { margin: 0 0 14px; font-size: 16px; font-weight: 800; color: var(--u-text,#111827); display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.qa-modal-close { background: none; border: none; font-size: 22px; line-height: 1; color: var(--u-muted,#64748b); cursor: pointer; padding: 0 4px; }
.qa-modal-close:hover { color: #5b2e91; }
.qa-field { margin-bottom: 12px; }
.qa-field label { display: block; font-size: 11.5px; font-weight: 700; color: var(--u-muted,#64748b); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
.qa-field input, .qa-field select { width: 100%; box-sizing: border-box; padding: 9px 12px; font-size: 13.5px; border: 1px solid var(--u-line,#cbd5e1); border-radius: 7px; background: var(--u-card,#fff); color: var(--u-text); outline: none; font-family: inherit; }
.qa-field input:focus, .qa-field select:focus { border-color: #5b2e91; box-shadow: 0 0 0 3px rgba(91,46,145,.1); }
.qa-modal-actions { display: flex; gap: 10px; margin-top: 16px; justify-content: flex-end; }
.qa-msg { padding: 10px 14px; border-radius: 8px; font-size: 12.5px; margin-bottom: 12px; display: none; }
.qa-msg.ok { background: rgba(16,185,129,.1); color: #047857; border: 1px solid rgba(16,185,129,.3); }
.qa-msg.err { background: rgba(239,68,68,.1); color: #b91c1c; border: 1px solid rgba(239,68,68,.3); }
.qa-user-found { background: rgba(126,88,191,.05); border: 1px solid rgba(126,88,191,.2); border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; font-size: 12.5px; }
.qa-user-found strong { color: #5b2e91; }
.qa-pwd-box { background: linear-gradient(135deg,#fef3c7,#fde68a); border: 2px solid #d97706; border-radius: 8px; padding: 12px 14px; margin-top: 10px; }
.qa-pwd-box strong { display: block; font-size: 12px; color: #78350f; margin-bottom: 6px; }
.qa-pwd-mono { font-family: ui-monospace, "Cascadia Code", Consolas, monospace; font-size: 14px; padding: 8px 10px; background: #fff; border-radius: 6px; word-break: break-all; }
</style>

<div class="qa-card">
    <div class="qa-card-head">
        <span class="qa-icon">👤</span>
        <h3>Kullanıcı &amp; Rol Yönetimi</h3>
    </div>
    <div class="qa-actions">
        <button type="button" class="qa-btn" data-qa-open="senior">+ Yeni Danışman / Mentor Ekle</button>
        <button type="button" class="qa-btn alt" data-qa-open="role">🔧 Mevcut Kullanıcıya Rol Ver</button>
    </div>
</div>

{{-- ════════════════════════════════ MODALS ════════════════════════════════ --}}

{{-- 1. Yeni Senior Ekle --}}
<div class="qa-modal-overlay" id="qaModalSenior">
    <div class="qa-modal">
        <h3>+ Yeni Danışman Ekle <button type="button" class="qa-modal-close" data-qa-close>&times;</button></h3>
        <div class="qa-msg ok" id="qaSeniorMsgOk"></div>
        <div class="qa-msg err" id="qaSeniorMsgErr"></div>
        <form id="qaSeniorForm">
            <div class="qa-field">
                <label>Ad Soyad *</label>
                <input type="text" name="name" required maxlength="120" placeholder="Örn: Ayşe Yılmaz">
            </div>
            <div class="qa-field">
                <label>Email *</label>
                <input type="email" name="email" required maxlength="190" placeholder="ayse@panel.mentorde.com">
            </div>
            <div class="qa-field">
                <label>Rol</label>
                <select name="role">
                    <option value="senior">Senior (kıdemli danışman)</option>
                    <option value="mentor">Mentor</option>
                </select>
            </div>
            <div class="qa-field">
                <label>Şifre (boş bırakırsan otomatik üretilir)</label>
                <input type="text" name="password" minlength="8" maxlength="190" placeholder="opsiyonel">
            </div>
            <div class="qa-field">
                <label>Maks. Öğrenci Kapasitesi</label>
                <input type="number" name="max_capacity" min="1" max="500" value="50">
            </div>
            <div class="qa-modal-actions">
                <button type="button" class="qa-btn alt" data-qa-close>Vazgeç</button>
                <button type="submit" class="qa-btn">Oluştur</button>
            </div>
        </form>
        <div id="qaSeniorPwdBox" class="qa-pwd-box" style="display:none;">
            <strong>🔐 İlk Giriş Şifresi (bir kez gösterilir):</strong>
            <div class="qa-pwd-mono" id="qaSeniorPwdValue"></div>
        </div>
    </div>
</div>

{{-- 2. Mevcut Kullanıcıya Rol Ver --}}
<div class="qa-modal-overlay" id="qaModalRole">
    <div class="qa-modal">
        <h3>🔧 Mevcut Kullanıcıya Rol Ver <button type="button" class="qa-modal-close" data-qa-close>&times;</button></h3>
        <div class="qa-msg ok" id="qaRoleMsgOk"></div>
        <div class="qa-msg err" id="qaRoleMsgErr"></div>
        <div class="qa-field">
            <label>Email ile Kullanıcı Ara *</label>
            <input type="email" id="qaRoleEmail" maxlength="190" placeholder="kullanici@panel.mentorde.com">
        </div>
        <div style="margin-bottom:12px;">
            <button type="button" class="qa-btn alt" id="qaRoleSearchBtn">🔍 Kullanıcıyı Bul</button>
        </div>
        <div class="qa-user-found" id="qaRoleUserFound" style="display:none;">
            <div><strong id="qaRoleUserName"></strong></div>
            <div style="font-size:11.5px;color:var(--u-muted,#64748b);margin-top:2px;">
                <span id="qaRoleUserEmail"></span> · Mevcut rol: <strong id="qaRoleUserCurrentRole"></strong>
            </div>
        </div>
        <div class="qa-field" id="qaRoleSelectField" style="display:none;">
            <label>Yeni Rol *</label>
            <select id="qaRoleNewRole">
                <option value="">Seç…</option>
                <optgroup label="Danışman">
                    <option value="senior">senior</option>
                    <option value="mentor">mentor</option>
                </optgroup>
                <optgroup label="Manager / Admin">
                    <option value="manager">manager</option>
                    <option value="operations_admin">operations_admin</option>
                    <option value="operations_staff">operations_staff</option>
                    <option value="system_admin">system_admin</option>
                    <option value="system_staff">system_staff</option>
                </optgroup>
                <optgroup label="Finans">
                    <option value="finance_admin">finance_admin</option>
                    <option value="finance_staff">finance_staff</option>
                </optgroup>
                <optgroup label="Pazarlama / Satış">
                    <option value="marketing_admin">marketing_admin</option>
                    <option value="marketing_staff">marketing_staff</option>
                    <option value="sales_admin">sales_admin</option>
                    <option value="sales_staff">sales_staff</option>
                </optgroup>
                <optgroup label="Portal">
                    <option value="student">student</option>
                    <option value="guest">guest (aday öğrenci)</option>
                    <option value="dealer">dealer</option>
                </optgroup>
            </select>
        </div>
        <div class="qa-modal-actions">
            <button type="button" class="qa-btn alt" data-qa-close>Vazgeç</button>
            <button type="button" class="qa-btn" id="qaRoleAssignBtn" style="display:none;">Rol Ver</button>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var URL_SENIOR_STORE = @json(route('manager.quick-admin.senior.store'));
    var URL_USER_FIND    = @json(route('manager.quick-admin.user.find'));
    var URL_ROLE_ASSIGN  = @json(route('manager.quick-admin.role.assign'));

    function $(id){ return document.getElementById(id); }

    function openModal(name){
        var el = $('qaModal' + name.charAt(0).toUpperCase() + name.slice(1));
        if (el) el.classList.add('is-open');
    }
    function closeAllModals(){
        document.querySelectorAll('.qa-modal-overlay').forEach(function(o){ o.classList.remove('is-open'); });
    }

    // Modal open/close — delegated
    document.addEventListener('click', function(e){
        var openBtn = e.target.closest('[data-qa-open]');
        if (openBtn) { openModal(openBtn.dataset.qaOpen); return; }
        var closeBtn = e.target.closest('[data-qa-close]');
        if (closeBtn) { closeAllModals(); return; }
        // Click on overlay background closes modal
        if (e.target.classList && e.target.classList.contains('qa-modal-overlay')) {
            closeAllModals();
        }
    });

    function showMsg(el, text, isError){
        if (!el) return;
        el.textContent = text;
        el.className = 'qa-msg ' + (isError ? 'err' : 'ok');
        el.style.display = 'block';
    }

    // === Yeni Senior Ekle ===
    $('qaSeniorForm')?.addEventListener('submit', async function(ev){
        ev.preventDefault();
        var form = ev.target;
        var fd = new FormData(form);
        var body = {};
        fd.forEach(function(v, k){ if (v !== '') body[k] = v; });

        $('qaSeniorMsgOk').style.display = 'none';
        $('qaSeniorMsgErr').style.display = 'none';
        $('qaSeniorPwdBox').style.display = 'none';

        var submitBtn = form.querySelector('button[type=submit]');
        submitBtn.disabled = true; var origText = submitBtn.textContent; submitBtn.textContent = 'Oluşturuluyor…';

        try {
            var res = await fetch(URL_SENIOR_STORE, {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
                body: JSON.stringify(body)
            });
            var json = await res.json();
            if (!res.ok || !json.ok) {
                showMsg($('qaSeniorMsgErr'), json.message || ('Hata: HTTP ' + res.status), true);
            } else {
                showMsg($('qaSeniorMsgOk'), json.message || '✓ Senior oluşturuldu.', false);
                if (json.generated_password) {
                    $('qaSeniorPwdValue').textContent = json.generated_password;
                    $('qaSeniorPwdBox').style.display = 'block';
                }
                form.reset();
            }
        } catch (e) {
            showMsg($('qaSeniorMsgErr'), 'Bağlantı hatası: ' + (e.message || 'bilinmeyen'), true);
        }
        submitBtn.disabled = false; submitBtn.textContent = origText;
    });

    // === Mevcut Kullanıcıya Rol Ver ===
    var foundUserId = null;

    $('qaRoleSearchBtn')?.addEventListener('click', async function(){
        var email = ($('qaRoleEmail').value || '').trim();
        if (!email) return;

        $('qaRoleMsgErr').style.display = 'none';
        $('qaRoleUserFound').style.display = 'none';
        $('qaRoleSelectField').style.display = 'none';
        $('qaRoleAssignBtn').style.display = 'none';
        foundUserId = null;

        try {
            var res = await fetch(URL_USER_FIND + '?email=' + encodeURIComponent(email), {
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
            });
            var json = await res.json();
            if (!res.ok || !json.found) {
                showMsg($('qaRoleMsgErr'), json.message || 'Kullanıcı bulunamadı.', true);
            } else {
                foundUserId = json.user.id;
                $('qaRoleUserName').textContent = json.user.name;
                $('qaRoleUserEmail').textContent = json.user.email;
                $('qaRoleUserCurrentRole').textContent = json.user.role;
                $('qaRoleUserFound').style.display = 'block';
                $('qaRoleSelectField').style.display = 'block';
                $('qaRoleAssignBtn').style.display = 'inline-block';
            }
        } catch (e) {
            showMsg($('qaRoleMsgErr'), 'Bağlantı hatası: ' + (e.message || 'bilinmeyen'), true);
        }
    });

    $('qaRoleAssignBtn')?.addEventListener('click', async function(){
        var newRole = $('qaRoleNewRole').value;
        if (!foundUserId || !newRole) {
            showMsg($('qaRoleMsgErr'), 'Önce kullanıcıyı bul ve yeni rol seç.', true);
            return;
        }

        $('qaRoleMsgErr').style.display = 'none';
        $('qaRoleMsgOk').style.display = 'none';

        try {
            var res = await fetch(URL_ROLE_ASSIGN, {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
                body: JSON.stringify({user_id: foundUserId, role: newRole})
            });
            var json = await res.json();
            if (!res.ok || !json.ok) {
                showMsg($('qaRoleMsgErr'), json.message || ('Hata: HTTP ' + res.status), true);
            } else {
                showMsg($('qaRoleMsgOk'), json.message || '✓ Rol güncellendi.', false);
                $('qaRoleUserCurrentRole').textContent = newRole;
                $('qaRoleNewRole').value = '';
            }
        } catch (e) {
            showMsg($('qaRoleMsgErr'), 'Bağlantı hatası: ' + (e.message || 'bilinmeyen'), true);
        }
    });
})();
</script>

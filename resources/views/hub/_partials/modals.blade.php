{{-- Hub: DM Başlat + Grup Oluştur modalleri — CSS :target ile açılır --}}
@php
    $roleColors = [
        'manager'          => '#4577c4',
        'senior'           => '#27ae60',
        'marketing_admin'  => '#8e44ad',
        'marketing_staff'  => '#9b59b6',
        'sales_admin'      => '#e67e22',
        'sales_staff'      => '#d35400',
        'finance_admin'    => '#16a085',
        'finance_staff'    => '#1abc9c',
        'dealer'           => '#e67e22',
        'student'          => '#2ecc71',
        'system_admin'     => '#c0392b',
        'operations_admin' => '#0891b2',
        'operations_staff' => '#0e7490',
    ];
@endphp

{{-- ──────────────── DM Başlat ──────────────── --}}
<div class="hub-modal-bg" id="hubModalDm">
    {{-- backdrop tıklanınca kapat --}}
    <a href="{{ request()->fullUrlWithQuery(['tab' => $tab ?? 'internal']) }}#" class="hub-modal-close" style="position:fixed;inset:0;z-index:9997"></a>
    <div class="hub-modal">
        <a href="{{ request()->fullUrlWithQuery(['tab' => $tab ?? 'internal']) }}#" class="hub-modal-close" title="Kapat">✕</a>
        <h3>💬 Doğrudan Mesaj Başlat</h3>
        <p style="font-size:12px;color:var(--u-muted);margin:-8px 0 12px">Ekipten bir kişi seçin, bireysel konuşma başlasın.</p>
        <input type="text" id="hubDmSearchInput" placeholder="🔍 İsim ara..."
               style="width:100%;padding:8px 11px;border:1px solid var(--u-line);border-radius:7px;font-size:13px;box-sizing:border-box;margin-bottom:8px;background:#fafafa">
        <div class="hub-user-select" id="hubDmUserList">
            @forelse($dmableUsers as $u)
            @php
                $color    = $roleColors[$u->role] ?? '#7f8c8d';
                $initials = strtoupper(substr(preg_replace('/\s+/', '', $u->name), 0, 2));
                $roleName = ucwords(str_replace('_', ' ', $u->role));
            @endphp
            <form method="POST" action="/im/dm/{{ $u->id }}" class="hub-user-row hub-dm-form" data-name="{{ strtolower($u->name) }}" style="cursor:pointer">
                @csrf
                <div class="hub-uavatar" style="background:{{ $color }}">{{ $initials }}</div>
                <div class="hub-uinfo" style="flex:1">
                    <strong>{{ $u->name }}</strong>
                    <small>{{ $roleName }}</small>
                </div>
                <button type="submit" class="btn ok" style="font-size:11px;padding:3px 10px">DM →</button>
            </form>
            @empty
            <div style="font-size:12px;color:var(--u-muted);padding:12px;text-align:center">Kullanılabilir kullanıcı yok.</div>
            @endforelse
        </div>
        <div class="hub-modal-actions">
            <a href="{{ request()->fullUrlWithQuery(['tab' => $tab ?? 'internal']) }}#" class="btn" style="text-decoration:none">Kapat</a>
        </div>
    </div>
</div>

{{-- ──────────────── Grup Oluştur ──────────────── --}}
<div class="hub-modal-bg" id="hubModalGroup">
    <a href="{{ request()->fullUrlWithQuery(['tab' => $tab ?? 'internal']) }}#" class="hub-modal-close" style="position:fixed;inset:0;z-index:9997"></a>
    <div class="hub-modal">
        <a href="{{ request()->fullUrlWithQuery(['tab' => $tab ?? 'internal']) }}#" class="hub-modal-close" title="Kapat">✕</a>
        <h3>👥 Ekip Grubu Oluştur</h3>
        <p style="font-size:12px;color:var(--u-muted);margin:-8px 0 12px">Departman veya proje bazlı grup konuşması başlatın.</p>
        <form method="POST" action="/im/group">
            @csrf
            <input type="hidden" name="type" value="group">
            <div style="margin-bottom:12px">
                <label style="display:block;font-size:11px;font-weight:600;color:var(--u-muted);margin-bottom:5px">Grup Adı *</label>
                <input type="text" name="title" id="hubGroupTitle" required minlength="2" maxlength="80"
                       placeholder="örn: Pazarlama Ekibi, Q2 Projesi…"
                       title="Grup adı en az 2 karakter olmalıdır"
                       style="width:100%;padding:8px 11px;border:1px solid var(--u-line);border-radius:7px;font-size:13px;box-sizing:border-box;background:#fafafa">
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:var(--u-muted);margin-bottom:5px">Katılımcılar</label>
                <input type="text" id="hubGroupSearchInput" placeholder="🔍 İsim ara..."
                       style="width:100%;padding:8px 11px;border:1px solid var(--u-line);border-radius:7px;font-size:13px;box-sizing:border-box;margin-bottom:6px;background:#fafafa">
                <div class="hub-user-select" id="hubGroupUserList">
                    @forelse($dmableUsers as $u)
                    @php
                        $color    = $roleColors[$u->role] ?? '#7f8c8d';
                        $initials = strtoupper(substr(preg_replace('/\s+/', '', $u->name), 0, 2));
                        $roleName = ucwords(str_replace('_', ' ', $u->role));
                    @endphp
                    <label class="hub-user-row" data-name="{{ strtolower($u->name) }}">
                        <input type="checkbox" name="participants[]" value="{{ $u->id }}" style="position:absolute;opacity:0;width:0;height:0">
                        <div class="hub-uavatar" style="background:{{ $color }}">{{ $initials }}</div>
                        <div class="hub-uinfo">
                            <strong>{{ $u->name }}</strong>
                            <small>{{ $roleName }}</small>
                        </div>
                        <div class="hub-ucheck-sq"></div>
                    </label>
                    @empty
                    <div style="font-size:12px;color:var(--u-muted);padding:12px;text-align:center">Kullanılabilir kullanıcı yok.</div>
                    @endforelse
                </div>
            </div>
            <div class="hub-modal-actions">
                <a href="{{ request()->fullUrlWithQuery(['tab' => $tab ?? 'internal']) }}#" class="btn" style="text-decoration:none">İptal</a>
                <button type="submit" class="btn ok">Grubu Kur →</button>
            </div>
        </form>
    </div>
</div>

{{-- ──────────────── Tartışma Odası ──────────────── --}}
<div class="hub-modal-bg" id="hubModalRoom">
    <a href="{{ request()->fullUrlWithQuery(['tab' => $tab ?? 'internal']) }}#" class="hub-modal-close" style="position:fixed;inset:0;z-index:9997"></a>
    <div class="hub-modal">
        <a href="{{ request()->fullUrlWithQuery(['tab' => $tab ?? 'internal']) }}#" class="hub-modal-close" title="Kapat">✕</a>
        <h3>🏠 Tartışma Odası Aç</h3>
        <p style="font-size:12px;color:var(--u-muted);margin:-8px 0 12px">Farklı ekiplerden kişileri belirli bir konu etrafında toplayın.</p>
        <form method="POST" action="/im/group">
            @csrf
            <input type="hidden" name="type" value="room">
            <div style="margin-bottom:12px">
                <label style="display:block;font-size:11px;font-weight:600;color:var(--u-muted);margin-bottom:5px">Oda Adı / Konu *</label>
                <input type="text" name="title" id="hubRoomTitle" required minlength="2" maxlength="80"
                       placeholder="örn: Vize Süreç Toplantısı, Q2 Kampanya…"
                       title="Oda adı en az 2 karakter olmalıdır"
                       style="width:100%;padding:8px 11px;border:1px solid var(--u-line);border-radius:7px;font-size:13px;box-sizing:border-box;background:#fafafa">
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:var(--u-muted);margin-bottom:5px">Katılımcılar <span style="font-weight:normal">(farklı disiplinlerden seçin)</span></label>
                <input type="text" id="hubRoomSearchInput" placeholder="🔍 İsim ara..."
                       style="width:100%;padding:8px 11px;border:1px solid var(--u-line);border-radius:7px;font-size:13px;box-sizing:border-box;margin-bottom:6px;background:#fafafa">
                <div class="hub-user-select" id="hubRoomUserList">
                    @forelse($dmableUsers as $u)
                    @php
                        $color    = $roleColors[$u->role] ?? '#7f8c8d';
                        $initials = strtoupper(substr(preg_replace('/\s+/', '', $u->name), 0, 2));
                        $roleName = ucwords(str_replace('_', ' ', $u->role));
                    @endphp
                    <label class="hub-user-row" data-name="{{ strtolower($u->name) }}">
                        <input type="checkbox" name="participants[]" value="{{ $u->id }}" style="position:absolute;opacity:0;width:0;height:0">
                        <div class="hub-uavatar" style="background:{{ $color }}">{{ $initials }}</div>
                        <div class="hub-uinfo">
                            <strong>{{ $u->name }}</strong>
                            <small>{{ $roleName }}</small>
                        </div>
                        <div class="hub-ucheck-sq"></div>
                    </label>
                    @empty
                    <div style="font-size:12px;color:var(--u-muted);padding:12px;text-align:center">Kullanılabilir kullanıcı yok.</div>
                    @endforelse
                </div>
            </div>
            <div class="hub-modal-actions">
                <a href="{{ request()->fullUrlWithQuery(['tab' => $tab ?? 'internal']) }}#" class="btn" style="text-decoration:none">İptal</a>
                <button type="submit" class="btn ok" style="background:#e74c3c;border-color:#e74c3c">Odayı Aç →</button>
            </div>
        </form>
    </div>
</div>

{{-- HTML5 validation mesajları Türkçeleştir + çift submit engelle --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // Form ilk submit'te butonları disable et — double-click ile çoklu grup oluşmasın
    document.querySelectorAll('.hub-modal form').forEach(function(form){
        form.addEventListener('submit', function(){
            // Valid değilse JS engellemesin (tarayıcı validasyonu zaten submit'i durdurur)
            if (!form.checkValidity || !form.checkValidity()) return;
            form.querySelectorAll('button[type="submit"]').forEach(function(btn){
                btn.disabled = true;
                btn.style.opacity = '0.65';
                btn.style.cursor = 'not-allowed';
                var original = btn.innerHTML;
                btn.setAttribute('data-orig', original);
                btn.innerHTML = '⏳ Oluşturuluyor...';
            });
            // Sayfa yenilenmezse (ör. hata) 8 saniye sonra geri al
            setTimeout(function(){
                form.querySelectorAll('button[type="submit"][disabled]').forEach(function(btn){
                    btn.disabled = false;
                    btn.style.opacity = '';
                    btn.style.cursor = '';
                    if (btn.getAttribute('data-orig')) {
                        btn.innerHTML = btn.getAttribute('data-orig');
                    }
                });
            }, 8000);
        });
    });

    document.querySelectorAll('.hub-modal input[required], .hub-modal textarea[required]').forEach(function(el){
        function setMsg() {
            if (el.validity.valueMissing) {
                el.setCustomValidity('Lütfen bu alanı doldurun.');
            } else if (el.validity.tooShort) {
                var min = el.getAttribute('minlength') || '2';
                el.setCustomValidity('En az ' + min + ' karakter girin.');
            } else if (el.validity.tooLong) {
                var max = el.getAttribute('maxlength') || '';
                el.setCustomValidity('En fazla ' + max + ' karakter girebilirsiniz.');
            } else if (el.validity.typeMismatch) {
                el.setCustomValidity('Geçerli bir değer girin.');
            } else {
                el.setCustomValidity('');
            }
        }
        el.addEventListener('invalid', setMsg);
        el.addEventListener('input', function(){ el.setCustomValidity(''); });
    });
})();
</script>

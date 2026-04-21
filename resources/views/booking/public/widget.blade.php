<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $settings->display_name ?: 'Randevu Al' }} — {{ $brandName ?? 'MentorDE' }}</title>
    @vite(['resources/css/premium.css'])
    <style>
        :root { --brand:#1e40af; --brand-light:#dbeafe; --text:#0f172a; --muted:#64748b; --border:#e2e8f0; --ok:#10b981; --err:#dc2626; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:#f8fafc; color:var(--text); }
        .bw-wrap { max-width:900px; margin:30px auto; padding:0 16px; }
        .bw-head { text-align:center; margin-bottom:20px; }
        .bw-head h1 { margin:0 0 6px; font-size:22px; color:var(--text); }
        .bw-head .sub { color:var(--muted); font-size:14px; }
        .bw-card { background:#fff; border:1px solid var(--border); border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
        .bw-welcome { background:var(--brand-light); border-radius:8px; padding:12px 14px; font-size:13px; color:#1e3a8a; margin-bottom:16px; line-height:1.6; }
        .bw-grid { display:grid; grid-template-columns:280px 1fr; gap:20px; }
        @media(max-width:720px){ .bw-grid { grid-template-columns:1fr; } }
        .bw-days { display:flex; flex-direction:column; gap:6px; max-height:400px; overflow-y:auto; padding-right:4px; }
        .bw-day-btn { text-align:left; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:#fff; cursor:pointer; font-size:13px; transition:all .15s; }
        .bw-day-btn:hover { border-color:var(--brand); background:#fff; }
        .bw-day-btn.active { border-color:var(--brand); background:var(--brand-light); font-weight:700; }
        .bw-day-btn .day-name { display:block; font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
        .bw-day-btn .day-date { display:block; font-size:15px; margin-top:2px; }
        .bw-day-btn .day-count { font-size:11px; color:var(--muted); margin-top:2px; }
        .bw-slots-col { min-height:400px; }
        .bw-slots-col h3 { margin:0 0 10px; font-size:14px; color:var(--text); }
        .bw-slots { display:grid; grid-template-columns:repeat(auto-fill, minmax(100px, 1fr)); gap:8px; }
        .bw-slot-btn { padding:10px; border:1px solid var(--border); border-radius:8px; background:#fff; cursor:pointer; font-size:13px; font-family:monospace; font-weight:600; transition:all .15s; }
        .bw-slot-btn:hover { border-color:var(--brand); background:var(--brand-light); }
        .bw-slot-btn.active { border-color:var(--brand); background:var(--brand); color:#fff; }
        .bw-empty { padding:30px; text-align:center; color:var(--muted); font-size:13px; }
        .bw-btn { padding:10px 20px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
        .bw-btn-primary { background:var(--brand); color:#fff; }
        .bw-btn-primary:disabled { background:var(--muted); cursor:not-allowed; }
        .bw-btn-ghost { background:#f1f5f9; color:var(--text); border:1px solid var(--border); }
        .bw-form { display:none; margin-top:20px; padding-top:20px; border-top:1px solid var(--border); }
        .bw-form.active { display:block; }
        .bw-form-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px; }
        @media(max-width:500px){ .bw-form-row { grid-template-columns:1fr; } }
        .bw-field label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px; }
        .bw-field input, .bw-field textarea { width:100%; padding:9px 11px; border:1px solid var(--border); border-radius:8px; font-size:13px; background:#fff; }
        .bw-summary { background:var(--brand-light); border-radius:8px; padding:10px 14px; font-size:13px; color:#1e3a8a; margin-bottom:14px; }
        .bw-msg-err { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }
        .bw-msg-ok { background:#d1fae5; border:1px solid #6ee7b7; color:#065f46; padding:14px 16px; border-radius:8px; font-size:14px; margin-top:20px; text-align:center; }
        .bw-footer { text-align:center; margin-top:20px; font-size:11px; color:var(--muted); }
    </style>
</head>
<body>

<div class="bw-wrap">

    <div class="bw-head">
        <h1>📅 {{ $settings->display_name ?: 'Randevu Al' }}</h1>
        @if ($senior)
            <div class="sub">Danışman: <strong>{{ $senior->name }}</strong> · {{ $settings->slot_duration }} dakika · {{ $settings->timezone }}</div>
        @endif
    </div>

    @if ($settings->welcome_message)
        <div class="bw-welcome">{{ $settings->welcome_message }}</div>
    @endif

    <div class="bw-card">

        <div id="bw-state-loading" class="bw-empty">⏳ Müsait saatler yükleniyor...</div>

        <div id="bw-state-grid" class="bw-grid" style="display:none;">
            <div class="bw-days" id="bw-days"></div>
            <div class="bw-slots-col">
                <h3 id="bw-slots-title">Gün seçin</h3>
                <div class="bw-slots" id="bw-slots"></div>
            </div>
        </div>

        <div id="bw-state-empty" class="bw-empty" style="display:none;">
            😔 Önümüzdeki {{ $settings->max_future_days }} gün içinde müsait saat yok.
        </div>

        <form id="bw-form" class="bw-form">
            @csrf
            <input type="hidden" name="starts_at_iso" id="bw-starts-at">

            <div class="bw-summary" id="bw-summary"></div>

            <div id="bw-err-box"></div>

            <div class="bw-form-row">
                <div class="bw-field">
                    <label>Ad Soyad *</label>
                    <input type="text" name="invitee_name" required maxlength="180" value="{{ $prefill['invitee_name'] }}">
                </div>
                <div class="bw-field">
                    <label>E-posta *</label>
                    <input type="email" name="invitee_email" required maxlength="180" value="{{ $prefill['invitee_email'] }}">
                </div>
            </div>
            <div class="bw-form-row">
                <div class="bw-field">
                    <label>Telefon</label>
                    <input type="tel" name="invitee_phone" maxlength="64" value="{{ $prefill['invitee_phone'] ?? '' }}">
                </div>
                <div class="bw-field">
                    <label>Not (opsiyonel)</label>
                    <input type="text" name="notes" maxlength="500" placeholder="Görüşmek istediğiniz konu">
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:10px;">
                <button type="submit" class="bw-btn bw-btn-primary" id="bw-submit">✅ Randevuyu Onayla</button>
                <button type="button" class="bw-btn bw-btn-ghost" id="bw-back">← Vazgeç</button>
            </div>
        </form>

        <div id="bw-state-success" style="display:none;">
            <div class="bw-msg-ok">
                <div style="font-size:30px;margin-bottom:8px;">✅</div>
                <div style="font-weight:700;font-size:16px;margin-bottom:6px;">Randevunuz onaylandı!</div>
                <div style="font-size:13px;margin-bottom:14px;">E-posta adresinize onay mesajı gönderildi.</div>
                <div style="font-size:12px;color:#064e3b;">İptal linki: <a id="bw-cancel-link" href="#" style="color:#064e3b;text-decoration:underline;word-break:break-all;"></a></div>
            </div>
        </div>

    </div>

    <div class="bw-footer">
        Randevu sistemi · <a href="/" style="color:var(--muted);">{{ $brandName ?? 'MentorDE' }}</a>
    </div>
</div>

<script>
(function(){
    var SLUG = @json($settings->public_slug);
    var FROM = @json($fromDate);
    var TO   = @json($toDate);
    var TZ   = @json($settings->timezone);

    var slotsUrl = "{{ route('booking.public.slots', ['slug' => $settings->public_slug]) }}";
    var confirmUrl = "{{ route('booking.public.confirm', ['slug' => $settings->public_slug]) }}";

    var daysData = {}; // date → slots[]
    var selectedDate = null;
    var selectedSlot = null;

    var $loading = document.getElementById('bw-state-loading');
    var $grid    = document.getElementById('bw-state-grid');
    var $empty   = document.getElementById('bw-state-empty');
    var $success = document.getElementById('bw-state-success');
    var $form    = document.getElementById('bw-form');
    var $daysEl  = document.getElementById('bw-days');
    var $slotsEl = document.getElementById('bw-slots');
    var $slotsTitle = document.getElementById('bw-slots-title');

    function loadSlots() {
        var fd = new FormData();
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        fd.append('from', FROM);
        fd.append('to', TO);
        fetch(slotsUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(r){ return r.json(); })
        .then(function(json){
            daysData = json.days || {};
            $loading.style.display = 'none';
            var keys = Object.keys(daysData);
            if (keys.length === 0) {
                $empty.style.display = '';
                return;
            }
            $grid.style.display = '';
            renderDays(keys);
            selectDate(keys[0]);
        })
        .catch(function(){
            $loading.textContent = 'Müsait saatler yüklenemedi. Sayfayı yenileyin.';
        });
    }

    function renderDays(dateKeys) {
        var html = '';
        dateKeys.forEach(function(key){
            var d = new Date(key + 'T12:00:00');
            var dayName = d.toLocaleDateString('tr-TR', { weekday: 'long' });
            var dayDate = d.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long' });
            var slotCount = (daysData[key] || []).length;
            html += '<button type="button" class="bw-day-btn" data-date="' + key + '">'
                  + '  <span class="day-name">' + dayName + '</span>'
                  + '  <span class="day-date">' + dayDate + '</span>'
                  + '  <span class="day-count">' + slotCount + ' slot</span>'
                  + '</button>';
        });
        $daysEl.innerHTML = html;
        Array.prototype.forEach.call($daysEl.querySelectorAll('.bw-day-btn'), function(btn){
            btn.addEventListener('click', function(){ selectDate(btn.getAttribute('data-date')); });
        });
    }

    function selectDate(date) {
        selectedDate = date;
        selectedSlot = null;
        $form.classList.remove('active');

        Array.prototype.forEach.call($daysEl.querySelectorAll('.bw-day-btn'), function(btn){
            btn.classList.toggle('active', btn.getAttribute('data-date') === date);
        });

        var d = new Date(date + 'T12:00:00');
        $slotsTitle.textContent = d.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', weekday: 'long' });

        var slots = daysData[date] || [];
        var html = '';
        slots.forEach(function(s){
            html += '<button type="button" class="bw-slot-btn" data-iso="' + s.iso_starts_at + '" data-label="' + s.starts_at + '">'
                  + s.starts_at
                  + '</button>';
        });
        $slotsEl.innerHTML = html || '<div class="bw-empty" style="grid-column:1/-1;">Bu gün için slot yok.</div>';
        Array.prototype.forEach.call($slotsEl.querySelectorAll('.bw-slot-btn'), function(btn){
            btn.addEventListener('click', function(){ selectSlot(btn); });
        });
    }

    function selectSlot(btn) {
        selectedSlot = {
            iso:   btn.getAttribute('data-iso'),
            label: btn.getAttribute('data-label'),
        };
        Array.prototype.forEach.call($slotsEl.querySelectorAll('.bw-slot-btn'), function(b){
            b.classList.toggle('active', b === btn);
        });

        document.getElementById('bw-starts-at').value = selectedSlot.iso;
        var d = new Date(selectedDate + 'T12:00:00');
        var dateText = d.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric', weekday: 'long' });
        document.getElementById('bw-summary').innerHTML = '📅 <strong>' + dateText + '</strong> · 🕒 <strong>' + selectedSlot.label + '</strong> (' + TZ + ')';

        $form.classList.add('active');
        setTimeout(function(){
            $form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            $form.querySelector('input[name="invitee_name"]').focus();
        }, 80);
    }

    document.getElementById('bw-back').addEventListener('click', function(){
        $form.classList.remove('active');
        selectedSlot = null;
        Array.prototype.forEach.call($slotsEl.querySelectorAll('.bw-slot-btn'), function(b){
            b.classList.remove('active');
        });
    });

    $form.addEventListener('submit', function(e){
        e.preventDefault();
        if (!selectedSlot) return;

        var $btn = document.getElementById('bw-submit');
        var $errBox = document.getElementById('bw-err-box');
        $errBox.innerHTML = '';
        $btn.disabled = true;
        $btn.textContent = '⏳ Gönderiliyor...';

        var fd = new FormData($form);
        fetch(confirmUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(r){ return r.json().then(function(j){ return {status:r.status, body:j}; }); })
        .then(function(res){
            if (res.body.ok) {
                $form.style.display = 'none';
                document.getElementById('bw-state-grid').style.display = 'none';
                document.getElementById('bw-cancel-link').href = res.body.cancel_url;
                document.getElementById('bw-cancel-link').textContent = res.body.cancel_url;
                $success.style.display = '';
            } else {
                $errBox.innerHTML = '<div class="bw-msg-err">' + (res.body.error || 'Bir hata oluştu.') + '</div>';
                $btn.disabled = false;
                $btn.textContent = '✅ Randevuyu Onayla';
            }
        })
        .catch(function(){
            $errBox.innerHTML = '<div class="bw-msg-err">Bağlantı hatası. Lütfen tekrar deneyin.</div>';
            $btn.disabled = false;
            $btn.textContent = '✅ Randevuyu Onayla';
        });
    });

    loadSlots();
})();
</script>

</body>
</html>

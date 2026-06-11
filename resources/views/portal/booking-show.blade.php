@extends(($portalRole ?? 'guest') === 'student' ? 'student.layouts.app' : 'guest.layouts.app')

@section('title', ($settings->display_name ?: 'Randevu') . ' — Randevu Al')

@section('content')
<div class="pbs-wrap" style="max-width:1080px;margin:24px auto;padding:0 18px;">

    {{-- Breadcrumb back link --}}
    <div style="margin-bottom:14px;">
        <a href="{{ route($routeName) }}"
           style="display:inline-flex;align-items:center;gap:6px;color:var(--c-muted,#64748b);font-size:13px;text-decoration:none;font-weight:600;">
            <x-icon name="chevron-left" size="14" aria-label="Geri" />
            Uzman havuzuna dön
        </a>
    </div>

    <div style="display:grid;grid-template-columns:340px 1fr;gap:22px;align-items:flex-start;">

        {{-- LEFT: Senior info card --}}
        <aside style="background:var(--c-surface,#fff);border:1px solid var(--c-border,#e2e8f0);border-radius:16px;padding:20px;position:sticky;top:14px;box-shadow:0 1px 2px rgba(0,0,0,.03);">
            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px;margin-bottom:14px;">
                @if($senior?->photo_url)
                    <img src="{{ $senior->photo_url }}" alt="{{ $senior->name }}"
                         style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--c-border,#e2e8f0);">
                @else
                    <div style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,#7e58bf,#1e40af);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:32px;">
                        {{ strtoupper(mb_substr($senior?->name ?? $settings->display_name ?? 'M', 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h2 style="margin:0 0 3px;font-size:18px;font-weight:800;color:var(--c-text,#0f172a);line-height:1.25;">
                        {{ $senior?->name ?? $settings->display_name }}
                    </h2>
                    <div style="font-size:12px;color:var(--c-muted,#64748b);">
                        {{ $settings->slot_duration }} dk · {{ $settings->timezone }}
                    </div>
                </div>
            </div>

            @if($isContracted)
                <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;border-radius:10px;padding:10px 12px;font-size:12.5px;font-weight:700;display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <x-icon name="check-circle" size="14" aria-label="Ücretsiz" />
                    Sözleşmen kapsamında ücretsiz
                </div>
            @else
                @php
                    $previewPrice = null;
                    try {
                        $companyPricing = \App\Models\CompanyBookingPricing::forCompany((int) $settings->company_id);
                        $previewPrice = $companyPricing->priceFor((int) $settings->slot_duration);
                    } catch (\Throwable) {}
                @endphp
                <div style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;border-radius:10px;padding:10px 12px;font-size:12.5px;line-height:1.5;margin-bottom:12px;">
                    <strong style="display:block;margin-bottom:3px;">Ücretli randevu</strong>
                    @if($previewPrice !== null && $previewPrice > 0)
                        {{ number_format($previewPrice, 2, ',', '.') }} EUR · {{ $settings->slot_duration }} dakika
                        <div style="font-size:11px;color:#64748b;margin-top:3px;">Stripe ile guvenli odeme. 24 saat oncesine kadar tam iade.</div>
                    @else
                        Onay sonrasinda Stripe odeme sayfasina yonlendirileceksin.
                    @endif
                </div>
            @endif

            @if($senior?->bio)
                <div style="font-size:13px;color:var(--c-text-soft,#475569);line-height:1.6;margin-bottom:12px;">
                    {{ $senior->bio }}
                </div>
            @endif

            @if($settings->welcome_message)
                <div style="background:var(--accent-soft,#eef2ff);border-radius:10px;padding:10px 12px;font-size:12.5px;color:var(--c-accent,#1e40af);line-height:1.55;">
                    {{ $settings->welcome_message }}
                </div>
            @endif
        </aside>

        {{-- RIGHT: Slot picker --}}
        <section style="background:var(--c-surface,#fff);border:1px solid var(--c-border,#e2e8f0);border-radius:16px;padding:20px;box-shadow:0 1px 2px rgba(0,0,0,.03);">

            <h3 style="margin:0 0 14px;font-size:16px;font-weight:800;color:var(--c-text,#0f172a);display:flex;align-items:center;gap:8px;">
                <x-icon name="calendar" size="18" aria-label="Müsait Saatler" />
                Müsait Saatler
            </h3>

            <div id="pbs-loading" style="padding:40px 0;text-align:center;color:var(--c-muted,#64748b);font-size:13px;">
                Müsait saatler yükleniyor…
            </div>

            <div id="pbs-grid" style="display:none;grid-template-columns:240px 1fr;gap:18px;">
                <div id="pbs-days" style="display:flex;flex-direction:column;gap:6px;max-height:430px;overflow-y:auto;padding-right:4px;"></div>
                <div>
                    <h4 id="pbs-slots-title" style="margin:0 0 10px;font-size:13.5px;font-weight:700;color:var(--c-text,#0f172a);">Gün seçin</h4>
                    <div id="pbs-slots" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px;"></div>
                </div>
            </div>

            <div id="pbs-empty" style="display:none;padding:40px 0;text-align:center;color:var(--c-muted,#64748b);font-size:13px;">
                Önümüzdeki {{ $settings->max_future_days }} gün içinde müsait saat yok.
            </div>

            {{-- Form (slot seçilince görünür) --}}
            <form id="pbs-form" style="display:none;margin-top:20px;padding-top:18px;border-top:1px solid var(--c-border,#e2e8f0);">
                @csrf
                <input type="hidden" name="starts_at_iso" id="pbs-starts-at">

                <div id="pbs-summary" style="background:var(--accent-soft,#eef2ff);color:var(--c-accent,#1e40af);border-radius:10px;padding:11px 14px;font-size:13px;font-weight:600;margin-bottom:14px;"></div>

                <div id="pbs-error" style="display:none;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:12px;"></div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Ad Soyad *</label>
                        <input type="text" name="invitee_name" required maxlength="180" value="{{ $prefill['invitee_name'] }}"
                               style="width:100%;padding:9px 11px;border:1px solid var(--c-border,#e2e8f0);border-radius:8px;font-size:13px;background:var(--c-surface-2,#fff);color:var(--c-text,#0f172a);">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">E-posta *</label>
                        <input type="email" name="invitee_email" required maxlength="180" value="{{ $prefill['invitee_email'] }}"
                               style="width:100%;padding:9px 11px;border:1px solid var(--c-border,#e2e8f0);border-radius:8px;font-size:13px;background:var(--c-surface-2,#fff);color:var(--c-text,#0f172a);">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Telefon</label>
                        <input type="tel" name="invitee_phone" maxlength="64" value="{{ $prefill['invitee_phone'] ?? '' }}"
                               style="width:100%;padding:9px 11px;border:1px solid var(--c-border,#e2e8f0);border-radius:8px;font-size:13px;background:var(--c-surface-2,#fff);color:var(--c-text,#0f172a);">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Konu (opsiyonel)</label>
                        <input type="text" name="notes" maxlength="500" placeholder="Görüşmek istediğin konu"
                               style="width:100%;padding:9px 11px;border:1px solid var(--c-border,#e2e8f0);border-radius:8px;font-size:13px;background:var(--c-surface-2,#fff);color:var(--c-text,#0f172a);">
                    </div>
                </div>

                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <button type="submit" id="pbs-submit"
                            style="padding:11px 22px;background:var(--c-accent,#1e40af);color:#fff;border:none;border-radius:8px;font-size:13.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                        <x-icon name="{{ $isContracted ? 'check' : 'credit-card' }}" size="14" aria-label="Onayla" />
                        {{ $isContracted ? 'Randevuyu Onayla' : 'Ödeme ile Devam Et' }}
                    </button>
                    <button type="button" id="pbs-cancel"
                            style="padding:11px 18px;background:#f1f5f9;color:#334155;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                        Vazgeç
                    </button>
                </div>
            </form>

            {{-- Success state --}}
            <div id="pbs-success" style="display:none;background:#dcfce7;border:1px solid #86efac;color:#166534;padding:20px;border-radius:12px;text-align:center;margin-top:18px;">
                <div style="margin-bottom:10px;display:flex;justify-content:center;">
                    <x-icon name="check-circle" size="36" aria-label="Başarılı" />
                </div>
                <div style="font-weight:800;font-size:16px;margin-bottom:5px;">Randevun onaylandı!</div>
                <div style="font-size:13px;margin-bottom:14px;">E-posta adresine onay mesajı gönderildi.</div>
                <a id="pbs-success-link" href="#"
                   style="display:inline-block;padding:9px 18px;background:#166534;color:#fff;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;">
                    Randevularıma Git
                </a>
            </div>

        </section>
    </div>
</div>

<style>
    .pbs-day-btn {
        text-align:left;padding:10px 12px;border:1px solid var(--c-border,#e2e8f0);
        border-radius:10px;background:var(--c-surface,#fff);cursor:pointer;font-size:13px;
        transition:all .15s;color:var(--c-text,#0f172a);font-family:inherit;
    }
    .pbs-day-btn:hover { border-color:var(--c-accent,#1e40af); }
    .pbs-day-btn.active { border-color:var(--c-accent,#1e40af);background:var(--accent-soft,#eef2ff);font-weight:700; }
    .pbs-day-btn .pbs-day-name { display:block;font-size:11px;color:var(--c-muted,#64748b);text-transform:uppercase;letter-spacing:.04em; }
    .pbs-day-btn .pbs-day-date { display:block;font-size:14.5px;margin-top:2px;color:var(--c-text,#0f172a); }
    .pbs-day-btn .pbs-day-count { display:block;font-size:11px;color:var(--c-muted,#64748b);margin-top:2px; }
    .pbs-slot-btn {
        padding:10px;border:1px solid var(--c-border,#e2e8f0);border-radius:8px;
        background:var(--c-surface,#fff);cursor:pointer;font-size:13px;font-weight:600;
        transition:all .15s;color:var(--c-text,#0f172a);font-family:inherit;
    }
    .pbs-slot-btn:hover { border-color:var(--c-accent,#1e40af);background:var(--accent-soft,#eef2ff); }
    .pbs-slot-btn.active { border-color:var(--c-accent,#1e40af);background:var(--c-accent,#1e40af);color:#fff; }
    @media (max-width:760px){
        .pbs-wrap > div[style*="grid-template-columns:340px"] { grid-template-columns:1fr !important; }
        aside[style*="position:sticky"] { position:static !important; }
        #pbs-grid { grid-template-columns:1fr !important; }
    }
</style>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var SLOTS_URL   = @json(route($routeName . '.slots',   ['slug' => $settings->public_slug]));
    var CONFIRM_URL = @json(route($routeName . '.confirm', ['slug' => $settings->public_slug]));
    var FROM = @json($fromDate);
    var TO   = @json($toDate);
    var TZ   = @json($settings->timezone);

    var daysData = {};
    var selectedDate = null;
    var selectedSlot = null;

    var $loading = document.getElementById('pbs-loading');
    var $grid    = document.getElementById('pbs-grid');
    var $empty   = document.getElementById('pbs-empty');
    var $days    = document.getElementById('pbs-days');
    var $slots   = document.getElementById('pbs-slots');
    var $slotsTitle = document.getElementById('pbs-slots-title');
    var $form    = document.getElementById('pbs-form');
    var $summary = document.getElementById('pbs-summary');
    var $error   = document.getElementById('pbs-error');
    var $success = document.getElementById('pbs-success');
    var $startsAt = document.getElementById('pbs-starts-at');

    function csrf(){
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    function loadSlots(){
        var fd = new FormData();
        fd.append('_token', csrf());
        fd.append('from', FROM);
        fd.append('to', TO);
        fetch(SLOTS_URL, {
            method:'POST',
            body:fd,
            headers:{'X-Requested-With':'XMLHttpRequest'},
            credentials:'same-origin'
        })
        .then(function(r){ return r.json(); })
        .then(function(json){
            daysData = json.days || {};
            $loading.style.display = 'none';
            var keys = Object.keys(daysData);
            if (keys.length === 0){
                $empty.style.display = '';
                return;
            }
            $grid.style.display = 'grid';
            renderDays(keys);
            selectDate(keys[0]);
        })
        .catch(function(){
            $loading.textContent = 'Müsait saatler yüklenemedi. Sayfayı yenileyin.';
        });
    }

    function renderDays(keys){
        var html = '';
        keys.forEach(function(key){
            var d = new Date(key + 'T12:00:00');
            var dayName = d.toLocaleDateString('tr-TR', { weekday:'long' });
            var dayDate = d.toLocaleDateString('tr-TR', { day:'numeric', month:'long' });
            var slotCount = (daysData[key] || []).length;
            html += '<button type="button" class="pbs-day-btn" data-date="'+key+'">'
                  + '<span class="pbs-day-name">'+dayName+'</span>'
                  + '<span class="pbs-day-date">'+dayDate+'</span>'
                  + '<span class="pbs-day-count">'+slotCount+' slot</span>'
                  + '</button>';
        });
        $days.innerHTML = html;
        Array.prototype.forEach.call($days.querySelectorAll('.pbs-day-btn'), function(btn){
            btn.addEventListener('click', function(){ selectDate(btn.getAttribute('data-date')); });
        });
    }

    function selectDate(date){
        selectedDate = date;
        selectedSlot = null;
        $form.style.display = 'none';

        Array.prototype.forEach.call($days.querySelectorAll('.pbs-day-btn'), function(btn){
            btn.classList.toggle('active', btn.getAttribute('data-date') === date);
        });

        var d = new Date(date + 'T12:00:00');
        $slotsTitle.textContent = d.toLocaleDateString('tr-TR', { day:'numeric', month:'long', weekday:'long' });

        var slots = daysData[date] || [];
        var html = '';
        slots.forEach(function(s){
            html += '<button type="button" class="pbs-slot-btn" data-iso="'+s.iso_starts_at+'" data-label="'+s.starts_at+'">'+s.starts_at+'</button>';
        });
        $slots.innerHTML = html || '<div style="grid-column:1/-1;color:var(--c-muted,#64748b);font-size:13px;padding:14px 0;">Bu gün için slot yok.</div>';
        Array.prototype.forEach.call($slots.querySelectorAll('.pbs-slot-btn'), function(btn){
            btn.addEventListener('click', function(){ selectSlot(btn); });
        });
    }

    function selectSlot(btn){
        selectedSlot = { iso: btn.getAttribute('data-iso'), label: btn.getAttribute('data-label') };
        Array.prototype.forEach.call($slots.querySelectorAll('.pbs-slot-btn'), function(b){
            b.classList.toggle('active', b === btn);
        });
        $startsAt.value = selectedSlot.iso;
        var d = new Date(selectedDate + 'T12:00:00');
        var dateText = d.toLocaleDateString('tr-TR', { day:'numeric', month:'long', year:'numeric', weekday:'long' });
        $summary.innerHTML = dateText + ' · <strong>'+selectedSlot.label+'</strong> ('+TZ+')';
        $form.style.display = '';
        $error.style.display = 'none';
        setTimeout(function(){
            $form.scrollIntoView({ behavior:'smooth', block:'start' });
            var nameInput = $form.querySelector('input[name="invitee_name"]');
            if (nameInput) nameInput.focus();
        }, 80);
    }

    document.getElementById('pbs-cancel').addEventListener('click', function(){
        $form.style.display = 'none';
        selectedSlot = null;
        Array.prototype.forEach.call($slots.querySelectorAll('.pbs-slot-btn'), function(b){ b.classList.remove('active'); });
    });

    $form.addEventListener('submit', function(e){
        e.preventDefault();
        if (!selectedSlot) return;
        var btn = document.getElementById('pbs-submit');
        $error.style.display = 'none';
        btn.disabled = true;
        var originalHtml = btn.innerHTML;
        btn.textContent = 'Gönderiliyor…';

        var fd = new FormData($form);
        fetch(CONFIRM_URL, {
            method:'POST', body:fd,
            headers:{'X-Requested-With':'XMLHttpRequest'},
            credentials:'same-origin'
        })
        .then(function(r){ return r.json().then(function(j){ return { status:r.status, body:j }; }); })
        .then(function(res){
            if (res.body.ok){
                // Phase 5 — Stripe Checkout: ücretli randevuda redirect_url Stripe URL'si.
                // res.body.stripe === true ise hemen Stripe sayfasina yonlendir.
                if (res.body.stripe && res.body.redirect_url){
                    $form.style.display = 'none';
                    $grid.style.display = 'none';
                    var $stripeMsg = document.createElement('div');
                    $stripeMsg.style.cssText = 'padding:30px;text-align:center;color:var(--c-muted,#64748b);font-size:14px;';
                    $stripeMsg.innerHTML = 'Stripe odeme sayfasina yonlendiriliyorsunuz…<br><br>'
                        + '<a href="'+res.body.redirect_url+'" style="color:var(--c-accent,#1e40af);font-weight:700;">Otomatik gitmiyorsa tikla</a>';
                    $success.parentNode.insertBefore($stripeMsg, $success);
                    setTimeout(function(){ window.location.href = res.body.redirect_url; }, 250);
                    return;
                }

                // Ücretsiz (sözleşmeli) akış — eski mevcut başarı ekranı
                $form.style.display = 'none';
                $grid.style.display = 'none';
                document.getElementById('pbs-success-link').href = res.body.redirect_url || '#';
                $success.style.display = '';
            } else {
                var msg = '';
                if (res.body.errors && typeof res.body.errors === 'object'){
                    var lines = [];
                    Object.keys(res.body.errors).forEach(function(f){
                        var arr = res.body.errors[f];
                        if (Array.isArray(arr)) lines = lines.concat(arr);
                        else if (typeof arr === 'string') lines.push(arr);
                    });
                    msg = lines.join(' · ');
                }
                if (!msg) msg = res.body.error || res.body.message || 'Bir hata oluştu (HTTP ' + res.status + ').';
                $error.textContent = msg;
                $error.style.display = '';
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        })
        .catch(function(){
            $error.textContent = 'Bağlantı hatası. Lütfen tekrar deneyin.';
            $error.style.display = '';
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    });

    loadSlots();
})();
</script>
@endsection

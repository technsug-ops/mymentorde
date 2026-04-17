{{-- Tanıtım Popup — tüm portal layout'larına @include('partials.promo-popup') ile eklenir --}}
<div id="promo-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9999;align-items:center;justify-content:center;">
    <div id="promo-modal" style="background:#fff;border-radius:16px;max-width:680px;width:94%;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.3);position:relative;animation:promoIn .3s ease;">
        <button id="promo-close" type="button" style="position:absolute;top:10px;right:14px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:50%;width:32px;height:32px;font-size:18px;cursor:pointer;z-index:2;display:flex;align-items:center;justify-content:center;line-height:1;">&times;</button>
        <div id="promo-video" style="border-radius:16px 16px 0 0;overflow:hidden;background:#000;"></div>
        <div id="promo-body" style="padding:20px 24px;">
            <div id="promo-title" style="font-size:18px;font-weight:800;color:#0f172a;margin-bottom:6px;"></div>
            <div id="promo-desc" style="font-size:13px;color:#64748b;line-height:1.6;"></div>
        </div>
    </div>
</div>
<style>
@keyframes promoIn { from { opacity:0; transform:scale(.92) translateY(20px); } to { opacity:1; transform:scale(1) translateY(0); } }
</style>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var pageMap = {
        '/guest/dashboard': 'guest.dashboard',
        '/guest/registration/form': 'guest.registration',
        '/guest/services': 'guest.services',
        '/guest/timeline': 'guest.timeline',
        '/guest/cost-calculator': 'guest.cost',
        '/student/dashboard': 'student.dashboard',
        '/student/materials': 'student.materials',
        '/senior/dashboard': 'senior.dashboard',
        '/dealer/dashboard': 'dealer.dashboard',
        '/manager/dashboard': 'manager.dashboard',
    };
    var path = window.location.pathname.replace(/\/+$/, '');
    var pageCode = pageMap[path] || '';
    if (!pageCode) return;

    fetch('/api/promo-popup?page=' + encodeURIComponent(pageCode), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(popup) {
        if (!popup || !popup.id) return;

        var storageKey = 'promo_popup_' + popup.id;
        var freq = popup.frequency || 'first_login';

        if (freq === 'first_login' && localStorage.getItem(storageKey)) return;
        if (freq === 'per_session' && sessionStorage.getItem(storageKey)) return;

        var delay = (popup.delay_seconds || 3) * 1000;
        setTimeout(function() { showPromo(popup, storageKey, freq); }, delay);
    })
    .catch(function() {});

    function showPromo(popup, storageKey, freq) {
        var overlay = document.getElementById('promo-overlay');
        var videoEl = document.getElementById('promo-video');
        var titleEl = document.getElementById('promo-title');
        var descEl  = document.getElementById('promo-desc');

        titleEl.textContent = popup.title || '';
        descEl.textContent = popup.description || '';

        if (popup.video_url) {
            var embedUrl = toEmbedUrl(popup.video_url, popup.video_type);
            if (popup.video_type === 'custom') {
                videoEl.innerHTML = '<video src="' + embedUrl + '" controls autoplay style="width:100%;max-height:380px;display:block;"></video>';
            } else {
                videoEl.innerHTML = '<iframe src="' + embedUrl + '" frameborder="0" allow="autoplay;encrypted-media;picture-in-picture" allowfullscreen style="width:100%;aspect-ratio:16/9;display:block;"></iframe>';
            }
        } else {
            videoEl.style.display = 'none';
        }

        overlay.style.display = 'flex';

        if (freq === 'first_login') localStorage.setItem(storageKey, '1');
        if (freq === 'per_session') sessionStorage.setItem(storageKey, '1');

        document.getElementById('promo-close').onclick = function() { closePromo(); };
        overlay.onclick = function(e) { if (e.target === overlay) closePromo(); };
    }

    function closePromo() {
        var overlay = document.getElementById('promo-overlay');
        overlay.style.display = 'none';
        var iframe = overlay.querySelector('iframe');
        if (iframe) iframe.src = '';
        var video = overlay.querySelector('video');
        if (video) video.pause();
    }

    function toEmbedUrl(url, type) {
        if (type === 'custom') return url;
        if (type === 'vimeo') {
            var vm = url.match(/vimeo\.com\/(\d+)/);
            return vm ? 'https://player.vimeo.com/video/' + vm[1] + '?autoplay=1' : url;
        }
        var ym = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([A-Za-z0-9_-]{11})/);
        return ym ? 'https://www.youtube.com/embed/' + ym[1] + '?autoplay=1&rel=0' : url;
    }
}());
</script>

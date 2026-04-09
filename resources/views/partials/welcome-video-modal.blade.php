@php
    $wvCfg     = config('welcome_video.' . ($wvPortal ?? 'guest'), []);
    $wvEnabled = $wvCfg['enabled'] ?? false;
    $wvVid     = $wvCfg['youtube_id'] ?? '';
    $wvTitle   = $wvCfg['title']    ?? (config('brand.name', 'MentorDE') . '\'ye Hoş Geldin!');
    $wvSub     = $wvCfg['subtitle'] ?? '';

    // Her kullanıcı için benzersiz key: user_id yedek olarak session id kullan
    $wvUserId  = auth()->id() ?: session()->getId();
    $wvKey     = 'mentorde_wv_v2_' . ($wvPortal ?? 'guest') . '_' . $wvUserId;

    // Sunucu tarafı session kontrolü (güvenilir birincil kontrol)
    $wvSessionKey   = 'wv_dismissed_' . ($wvPortal ?? 'guest') . '_' . (auth()->id() ?? 0);
    $wvAlreadySeen  = session()->has($wvSessionKey);
@endphp

@if($wvEnabled && $wvVid && !$wvAlreadySeen)
{{-- ══ Hoş Geldin Video Modalı ══ --}}
<div id="wvModal"
     role="dialog" aria-modal="true" aria-label="{{ $wvTitle }}"
     style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.78);
            align-items:center;justify-content:center;padding:16px;">
    <div id="wvBox"
         style="background:#fff;border-radius:18px;overflow:hidden;
                width:min(740px,96vw);box-shadow:0 28px 72px rgba(0,0,0,.55);">

        {{-- Başlık --}}
        <div style="padding:18px 22px 14px;display:flex;align-items:flex-start;justify-content:space-between;
                    background:linear-gradient(135deg,#1d4ed8 0%,#7c3aed 100%);color:#fff;">
            <div>
                <div style="font-weight:700;font-size:1.05rem;line-height:1.25;">
                    👋 {{ $wvTitle }}
                </div>
                @if($wvSub)
                <div style="font-size:.78rem;opacity:.82;margin-top:4px;">{{ $wvSub }}</div>
                @endif
            </div>
            <button id="wvBtnX"
                    style="background:rgba(255,255,255,.18);border:none;color:#fff;
                           width:30px;height:30px;border-radius:50%;cursor:pointer;
                           font-size:15px;line-height:1;flex-shrink:0;margin-left:12px;"
                    title="Kapat">✕</button>
        </div>

        {{-- Video alanı --}}
        <div style="position:relative;padding-bottom:56.25%;height:0;background:#000;">
            <iframe id="wvIframe"
                    src="https://www.youtube.com/embed/{{ $wvVid }}?rel=0&modestbranding=1"
                    style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"
                    allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share"
                    allowfullscreen
                    title="{{ $wvTitle }}">
            </iframe>
        </div>

        {{-- Alt bar --}}
        <div style="padding:14px 22px;display:flex;align-items:center;justify-content:space-between;
                    border-top:1px solid #e5e7eb;background:#f8fafc;flex-wrap:wrap;gap:10px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;
                          font-size:.83rem;color:#64748b;user-select:none;">
                <input type="checkbox" id="wvChkDismiss"
                       style="width:15px;height:15px;cursor:pointer;accent-color:#1d4ed8;">
                Bir daha gösterme
            </label>
            <button id="wvBtnOk"
                    style="background:#1d4ed8;color:#fff;border:none;padding:9px 26px;
                           border-radius:8px;cursor:pointer;font-size:.85rem;font-weight:600;
                           letter-spacing:.01em;">
                Tamam, Kapat
            </button>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var LS_KEY      = @json($wvKey);
    var DISMISS_URL = '/welcome-video/dismiss?portal=' + @json($wvPortal ?? 'guest');
    var modal  = document.getElementById('wvModal');
    var iframe = document.getElementById('wvIframe');
    var baseSrc = iframe ? iframe.src : '';
    if (!modal) return;

    // localStorage ikincil kontrol (birincil: sunucu session)
    if (localStorage.getItem(LS_KEY) === '1') return;

    var timer = setTimeout(function () {
        modal.style.display = 'flex';
        // Video autoplay
        if (iframe && baseSrc) {
            iframe.src = baseSrc + '&autoplay=1';
        }
    }, 5000);

    function closeModal(permanent) {
        clearTimeout(timer);
        if (iframe) iframe.src = '';
        modal.style.display = 'none';
        if (permanent) {
            localStorage.setItem(LS_KEY, '1');
            // Sunucu session'a da kaydet
            fetch(DISMISS_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json'
                }
            }).catch(function(){});
        }
    }

    document.getElementById('wvBtnX').addEventListener('click', function () {
        closeModal(document.getElementById('wvChkDismiss').checked);
    });
    document.getElementById('wvBtnOk').addEventListener('click', function () {
        closeModal(document.getElementById('wvChkDismiss').checked);
    });

    // Backdrop tıklama
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal(document.getElementById('wvChkDismiss').checked);
        }
    });

    // ESC tuşu
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            closeModal(document.getElementById('wvChkDismiss').checked);
        }
    });
}());
</script>
@endif

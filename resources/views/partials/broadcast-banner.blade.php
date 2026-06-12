{{--
    Platform Owner — In-App Broadcast Banner.

    Layout'larda @yield('content') OnceSi include edilir:
        @include('partials.broadcast-banner')

    Davranis:
      - Auth user'in company_id'sine ait broadcast'lardan son 7 gun icinde
        gonderilenleri (channel = in_app / both) cekilir.
      - LocalStorage'da dismissed_broadcast_ids array'i tutulur — kullanici X'e
        bastiginda o broadcast id'si bu listeye eklenir ve bir daha gosterilmez.
      - Banner gosterildiginde silent open-tracking ping atilir (signed URL).
      - CTA tiklamasi click-tracking ile redirect olur.

    Hata izolasyonu: try/catch ile sarmali — bir broadcast modulu hatasi
    asla manager/senior dashboard'larini cokertmemeli.
--}}
@php
    $__bb_broadcasts = collect();
    try {
        if (auth()->check() && auth()->user()->company_id) {
            $__bb_broadcasts = \App\Models\PlatformBroadcastRecipient::query()
                ->with('broadcast')
                ->where('user_id', auth()->id())
                ->whereHas('broadcast', function ($q) {
                    $q->whereIn('channel', [
                            \App\Models\PlatformBroadcast::CHANNEL_IN_APP,
                            \App\Models\PlatformBroadcast::CHANNEL_BOTH,
                        ])
                        ->where('status', \App\Models\PlatformBroadcast::STATUS_SENT)
                        ->whereNotNull('sent_at')
                        ->where('sent_at', '>=', now()->subDays(7));
                })
                ->orderByDesc('id')
                ->limit(3)
                ->get();
        }
    } catch (\Throwable $e) {
        // Tablo henuz migrate edilmediyse vs. — sessizce yut
        $__bb_broadcasts = collect();
    }
@endphp

@if ($__bb_broadcasts->isNotEmpty())
    <div id="broadcast-banner-wrap" style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
        @foreach ($__bb_broadcasts as $rec)
            @php $b = $rec->broadcast; @endphp
            <div class="broadcast-banner"
                 data-recipient-id="{{ $rec->id }}"
                 data-broadcast-id="{{ $b->id }}"
                 style="background: linear-gradient(135deg, #7e58bf, #b395e6); color: #fff; border-radius: 10px; padding: 14px 18px; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 12px rgba(126,88,191,.18);">

                <div style="flex-shrink:0; width:38px; height:38px; border-radius:50%; background: rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center;">
                    <x-icon name="megaphone" size="20" />
                </div>

                <div style="flex:1; min-width:0;">
                    <div style="font-weight:700; font-size:14px; margin-bottom:2px;">
                        {{ $b->title }}
                    </div>
                    <div style="font-size:12px; opacity:.95; line-height:1.5;">
                        {{ \Illuminate\Support\Str::limit(strip_tags($b->body), 180) }}
                    </div>
                </div>

                @if ($b->cta_label && $b->cta_url)
                    <a href="{{ URL::signedRoute('broadcast.track.click', ['recipient_id' => $rec->id, 'url' => $b->cta_url]) }}"
                       class="broadcast-cta"
                       style="flex-shrink:0; background:#fff; color:#7e58bf; padding:8px 16px; border-radius:8px; font-weight:700; font-size:12px; text-decoration:none; white-space:nowrap;">
                        {{ $b->cta_label }} <x-icon name="chevron-right" size="12" />
                    </a>
                @endif

                <button type="button" class="broadcast-dismiss"
                        data-broadcast-id="{{ $b->id }}"
                        style="flex-shrink:0; background:transparent; border:none; color:#fff; opacity:.7; cursor:pointer; padding:4px; display:inline-flex;"
                        aria-label="Kapat">
                    <x-icon name="x" size="16" />
                </button>
            </div>
        @endforeach
    </div>

    @php
        $__bb_pings = $__bb_broadcasts->mapWithKeys(fn ($r) => [
            $r->broadcast_id => URL::signedRoute('broadcast.track.open', ['recipient_id' => $r->id]),
        ])->toJson();
    @endphp

    <script nonce="{{ $cspNonce ?? '' }}">
    (function(){
        try {
            const wrap = document.getElementById('broadcast-banner-wrap');
            if (!wrap) return;
            const pings = {!! $__bb_pings !!};

            // Dismissed listesi
            const STORAGE_KEY = 'mentorde_dismissed_broadcasts';
            let dismissed = [];
            try { dismissed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]') || []; } catch (e) {}

            // Bilinen dismissed olanlari gizle
            const banners = wrap.querySelectorAll('.broadcast-banner');
            banners.forEach(function(el){
                const bid = parseInt(el.getAttribute('data-broadcast-id'), 10);
                if (dismissed.indexOf(bid) >= 0) {
                    el.style.display = 'none';
                    return;
                }
                // Gosterildi — open ping at
                const url = pings[bid];
                if (url) {
                    const img = new Image(1, 1);
                    img.src = url;
                }
            });

            // Dismiss handler
            wrap.querySelectorAll('.broadcast-dismiss').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const bid = parseInt(btn.getAttribute('data-broadcast-id'), 10);
                    if (!isNaN(bid) && dismissed.indexOf(bid) < 0) {
                        dismissed.push(bid);
                        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(dismissed)); } catch(e) {}
                    }
                    const banner = btn.closest('.broadcast-banner');
                    if (banner) banner.style.display = 'none';
                    // Tum bannerlar gizlendiyse wrap'i da gizle
                    const visible = wrap.querySelectorAll('.broadcast-banner').length > 0 &&
                                    Array.from(wrap.querySelectorAll('.broadcast-banner')).some(b => b.style.display !== 'none');
                    if (!visible) wrap.style.display = 'none';
                });
            });

            // Tumu dismissed durumdaysa, wrap'i komple gizle
            const allHidden = Array.from(banners).every(b => b.style.display === 'none');
            if (allHidden) wrap.style.display = 'none';
        } catch (e) {
            // No-op
        }
    })();
    </script>
@endif

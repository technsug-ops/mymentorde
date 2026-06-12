{{--
    Notification center bell dropdown — son 10 in_app bildirim.

    Veriyi notification_dispatches tablosundan cekiyor (mevcut altyapi).
    Toplam goruntuleyebilmek icin /notifications sayfasi olusturuluyor
    (varsa link, yoksa simdilik # placeholder).

    Kullanim (her layout topbar'inda):
      @include('partials.notification-center')

    Real-time pus var ama bell dropdown sayfa yenilemesinde dolar —
    notifications.js anlik toast atar, bell sayisi soft polling ile guncellenir
    (basit setInterval; ileri sprintte Echo .listen ile counter++ yapilabilir).
--}}
@auth
    @php
        $__notifQuery = \App\Models\NotificationDispatch::query()
            ->where('channel', 'in_app')
            ->latest('id');

        if (auth()->user()->student_id) {
            $__notifQuery->where('student_id', auth()->user()->student_id);
        } else {
            $__notifQuery->where('user_id', auth()->id());
        }

        $__recentNotifs = $__notifQuery->limit(10)->get([
            'id', 'category', 'subject', 'body', 'created_at', 'read_at'
        ]);
        $__unreadCount = $__notifQuery->clone()->whereNull('read_at')->count();
    @endphp

    <div class="notif-center-wrapper" style="position:relative;">
        <button type="button"
                id="notif-bell-btn"
                class="notif-bell-btn"
                aria-label="Bildirimler"
                aria-haspopup="true"
                aria-expanded="false"
                style="background:transparent;border:0;padding:6px;cursor:pointer;position:relative;color:inherit;">
            <x-icon name="bell" size="20" />
            @if($__unreadCount > 0)
                <span id="notif-bell-count"
                      style="position:absolute;top:0;right:0;background:#dc2626;color:#fff;border-radius:999px;font-size:10px;font-weight:700;padding:2px 6px;min-width:18px;text-align:center;line-height:1;">
                    {{ $__unreadCount > 99 ? '99+' : $__unreadCount }}
                </span>
            @endif
        </button>

        <div id="notif-bell-dropdown"
             role="menu"
             style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:360px;max-width:90vw;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 12px 32px rgba(15,23,42,.18);z-index:1000;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                <strong style="font-size:14px;color:#0f172a;">Bildirimler</strong>
                <a href="/notifications" style="color:#2563eb;font-size:12px;text-decoration:none;font-weight:600;">Tümünü gör</a>
            </div>
            <div style="max-height:420px;overflow-y:auto;">
                @forelse($__recentNotifs as $__n)
                    <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9;{{ $__n->read_at ? 'opacity:.7;' : 'background:#eff6ff;' }}">
                        <div style="font-weight:600;font-size:13px;color:#0f172a;margin-bottom:2px;">
                            {{ $__n->subject ?: ucfirst(str_replace('_', ' ', (string) $__n->category)) }}
                        </div>
                        <div style="font-size:12px;color:#475569;line-height:1.4;">
                            {{ \Illuminate\Support\Str::limit((string) $__n->body, 110) }}
                        </div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:4px;">
                            {{ optional($__n->created_at)->diffForHumans() }}
                        </div>
                    </div>
                @empty
                    <div style="padding:24px 16px;text-align:center;color:#94a3b8;font-size:13px;">
                        Henüz bildirim yok.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        (function(){
            const btn = document.getElementById('notif-bell-btn');
            const dd  = document.getElementById('notif-bell-dropdown');
            if (!btn || !dd) return;

            function toggle(open) {
                const isOpen = typeof open === 'boolean' ? open : dd.style.display === 'none';
                dd.style.display = isOpen ? 'block' : 'none';
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }

            btn.addEventListener('click', (e) => { e.stopPropagation(); toggle(); });
            document.addEventListener('click', (e) => {
                if (!dd.contains(e.target) && !btn.contains(e.target)) toggle(false);
            });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') toggle(false); });
        })();
    </script>
@endauth

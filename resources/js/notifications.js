// ────────────────────────────────────────────────────────────────────
// Real-time notification subscription + toast UI manager.
//
// Echo (window.Echo) hazirsa kullanicinin rol/sirket bilgilerine gore
// dogru kanallara subscribe olur ve toast/sound/browser-notification
// gosterir. Echo yoksa sessizce skip eder — uygulama bozulmaz.
//
// Layout bunu @include('partials.notification-toast') ile yaniyor;
// orada DOM container + window.userId/userRole/companyId set ediliyor.
// ────────────────────────────────────────────────────────────────────

const TOAST_STACK_LIMIT = 3;
const TOAST_DURATION_MS = 6000;
const SOUND_URL = '/sounds/notif.mp3';

let toastCounter = 0;
let audioEl = null;

function getStack() {
    let stack = document.getElementById('mentorde-toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'mentorde-toast-stack';
        stack.setAttribute('aria-live', 'polite');
        stack.setAttribute('role', 'status');
        stack.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:380px;pointer-events:none;';
        document.body.appendChild(stack);
    }
    return stack;
}

function playSound() {
    try {
        if (!audioEl) {
            audioEl = new Audio(SOUND_URL);
            audioEl.volume = 0.5;
            audioEl.preload = 'auto';
        }
        // Tarayicilar autoplay'i bloklayabilir — user interaction sonrasi calisir
        const p = audioEl.play();
        if (p && typeof p.catch === 'function') {
            p.catch(() => { /* sessizce yut, izin yoksa */ });
        }
    } catch (_) { /* noop */ }
}

function notifyBrowser(title, body, url) {
    try {
        if (typeof Notification === 'undefined') return;
        if (Notification.permission !== 'granted') return;
        if (!document.hidden) return; // sekme acik ise toast yeterli

        const n = new Notification(title, {
            body: body || '',
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            tag: 'mentorde-' + Date.now(),
            silent: false,
        });
        n.onclick = function () {
            window.focus();
            if (url) window.location.href = url;
            n.close();
        };
    } catch (_) { /* noop */ }
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[c]);
}

function iconSvg(name) {
    // Mevcut <x-icon> SVG'lerinden bazi temel ikonlar inline:
    const map = {
        'calendar-check': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>',
        'calendar-x':     '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m10 14 4 4M14 14l-4 4"/></svg>',
        'user-plus':      '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>',
        'wallet':         '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>',
        'message-square': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'crown':          '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4l3 12h14l3-12-6 7-4-7-4 7z"/><path d="M5 20h14"/></svg>',
        'bell':           '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
    };
    return map[name] || map['bell'];
}

export function showToast(payload) {
    const title   = escapeHtml(payload.title   || 'Bildirim');
    const message = escapeHtml(payload.message || payload.body || '');
    const url     = payload.url || null;
    const icon    = payload.icon || 'bell';

    const stack = getStack();

    // Stack limit — eski toast'ları kaldır
    while (stack.children.length >= TOAST_STACK_LIMIT) {
        stack.removeChild(stack.firstChild);
    }

    toastCounter += 1;
    const id = 'mentorde-toast-' + toastCounter;

    const div = document.createElement('div');
    div.id = id;
    div.style.cssText = [
        'pointer-events:auto',
        'background:#ffffff',
        'color:#0f172a',
        'border:1px solid #e2e8f0',
        'border-left:4px solid #2563eb',
        'border-radius:12px',
        'padding:14px 16px',
        'box-shadow:0 10px 30px rgba(15,23,42,.14)',
        'display:flex',
        'gap:12px',
        'align-items:flex-start',
        'cursor:' + (url ? 'pointer' : 'default'),
        'transform:translateX(420px)',
        'opacity:0',
        'transition:transform .28s ease, opacity .28s ease',
        'font-family:system-ui,-apple-system,sans-serif',
        'font-size:14px',
        'line-height:1.45',
    ].join(';');

    div.innerHTML = [
        '<span style="flex:0 0 auto;display:inline-flex;width:34px;height:34px;align-items:center;justify-content:center;background:#eff6ff;color:#2563eb;border-radius:8px;">',
            iconSvg(icon),
        '</span>',
        '<div style="flex:1;min-width:0;">',
            '<div style="font-weight:700;color:#0f172a;margin-bottom:2px;">', title, '</div>',
            '<div style="color:#475569;word-wrap:break-word;">', message, '</div>',
        '</div>',
        '<button type="button" aria-label="Kapat" style="flex:0 0 auto;background:transparent;border:0;color:#94a3b8;cursor:pointer;font-size:18px;line-height:1;padding:0;margin-left:4px;">×</button>',
    ].join('');

    stack.appendChild(div);

    // Animate in
    requestAnimationFrame(() => {
        div.style.transform = 'translateX(0)';
        div.style.opacity = '1';
    });

    const dismiss = () => {
        div.style.transform = 'translateX(420px)';
        div.style.opacity = '0';
        setTimeout(() => { if (div.parentNode) div.parentNode.removeChild(div); }, 280);
    };

    const closeBtn = div.querySelector('button');
    closeBtn.addEventListener('click', (e) => { e.stopPropagation(); dismiss(); });

    if (url) {
        div.addEventListener('click', () => { window.location.href = url; });
    }

    setTimeout(dismiss, TOAST_DURATION_MS);

    playSound();
    notifyBrowser(payload.title || 'MentorDE', payload.message || '', url);
}

// ── Browser Notification API izin banner'i ─────────────────────────
function shouldShowPermissionPrompt() {
    if (typeof Notification === 'undefined') return false;
    if (Notification.permission !== 'default') return false;
    if (sessionStorage.getItem('mentorde_notif_prompt_dismissed') === '1') return false;
    return true;
}

function showPermissionPrompt() {
    if (!shouldShowPermissionPrompt()) return;

    const bar = document.createElement('div');
    bar.id = 'mentorde-notif-permission-bar';
    bar.style.cssText = [
        'position:fixed',
        'top:14px',
        'left:50%',
        'transform:translateX(-50%)',
        'z-index:99998',
        'background:#0f172a',
        'color:#fff',
        'padding:10px 16px',
        'border-radius:999px',
        'box-shadow:0 8px 24px rgba(15,23,42,.25)',
        'font-family:system-ui,-apple-system,sans-serif',
        'font-size:13px',
        'display:flex',
        'gap:10px',
        'align-items:center',
    ].join(';');
    bar.innerHTML = [
        '<span>Anında bildirim almak ister misin?</span>',
        '<button type="button" id="mentorde-notif-yes" style="background:#2563eb;color:#fff;border:0;padding:6px 12px;border-radius:999px;font-weight:600;cursor:pointer;">Aç</button>',
        '<button type="button" id="mentorde-notif-no" style="background:transparent;color:#cbd5e1;border:0;padding:6px;cursor:pointer;font-size:18px;line-height:1;">×</button>',
    ].join('');
    document.body.appendChild(bar);

    document.getElementById('mentorde-notif-yes').addEventListener('click', () => {
        Notification.requestPermission().finally(() => bar.remove());
    });
    document.getElementById('mentorde-notif-no').addEventListener('click', () => {
        sessionStorage.setItem('mentorde_notif_prompt_dismissed', '1');
        bar.remove();
    });

    // 12sn sonra otomatik kaldir (kullaniciyi sıkma)
    setTimeout(() => { if (bar.parentNode) bar.remove(); }, 12000);
}

// ── Subscription manager ───────────────────────────────────────────
export function initRealtimeNotifications() {
    if (!window.Echo) return; // Pusher key yok veya init failed → skip
    const userId   = parseInt(window.userId || 0, 10);
    const userRole = String(window.userRole || '');
    const companyId = parseInt(window.companyId || 0, 10);

    if (!userId) return;

    try {
        // 1) Kisisel kanal — chat mesajlari, kullaniciya ozel olaylar
        window.Echo.private(`user.${userId}`)
            .listen('.message.new', (e) => showToast({ ...e, icon: e.icon || 'message-square' }))
            .listen('.booking.canceled', (e) => showToast({ ...e, icon: e.icon || 'calendar-x' }));

        // 2) Senior dashboard kanali
        if (userRole === 'senior') {
            window.Echo.private(`senior.${userId}`)
                .listen('.booking.new', (e) => showToast({ ...e, icon: e.icon || 'calendar-check' }))
                .listen('.booking.canceled', (e) => showToast({ ...e, icon: e.icon || 'calendar-x' }))
                .listen('.payment.received', (e) => showToast({ ...e, icon: e.icon || 'wallet' }));
        }

        // 3) Manager / Marketing Admin / Platform Owner — company-scope kanal
        if (['manager', 'marketing_admin', 'platform_owner'].includes(userRole) && companyId > 0) {
            window.Echo.private(`manager.${companyId}`)
                .listen('.lead.new', (e) => showToast({ ...e, icon: e.icon || 'user-plus' }))
                .listen('.payment.received', (e) => showToast({ ...e, icon: e.icon || 'wallet' }))
                .listen('.booking.canceled', (e) => showToast({ ...e, icon: e.icon || 'calendar-x' }))
                .listen('.tier.upgraded', (e) => showToast({ ...e, icon: e.icon || 'crown' }));
        }

        // 4) Platform Owner global kanal
        if (userRole === 'platform_owner') {
            window.Echo.private('platform.owner')
                .listen('.tier.upgraded', (e) => showToast({ ...e, icon: e.icon || 'crown' }));
        }
    } catch (err) {
        console.warn('[notifications] subscribe failed', err);
    }

    // Permission prompt — kullanici daha once karar vermediyse banner goster
    setTimeout(showPermissionPrompt, 2500);
}

// Auto-init: bootstrap mainline DOMContentLoaded'tan sonra hazirsa direkt baslat,
// degilse DOMContentLoaded'i bekle
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRealtimeNotifications);
} else {
    initRealtimeNotifications();
}

// Diger script'lerin manuel cagirmasi icin
window.MentordeNotifications = { showToast, initRealtimeNotifications };

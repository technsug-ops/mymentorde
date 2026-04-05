import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';

Alpine.plugin(persist);
Alpine.plugin(focus);
Alpine.plugin(collapse);

Alpine.store('toast', {
    items: [],
    show(message, type = 'ok', duration = 3500) {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => { this.items = this.items.filter(t => t.id !== id); }, duration);
    },
    success(m) { this.show(m, 'ok'); },
    error(m) { this.show(m, 'danger', 5000); },
});

Alpine.store('http', {
    _csrf: document.querySelector('meta[name=\"csrf-token\"]')?.content || '',
    async request(url, method = 'GET', body = null) {
        const opts = { method, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
        if (this._csrf) opts.headers['X-CSRF-TOKEN'] = this._csrf;
        if (body && method !== 'GET') { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        const res = await fetch(url, opts);
        if (!res.ok) { const err = await res.json().catch(() => ({})); Alpine.store('toast').error(err.message || 'Hata'); throw new Error(err.message); }
        return res.headers.get('content-type')?.includes('json') ? res.json() : res.text();
    },
    get(u) { return this.request(u); },
    post(u, b) { return this.request(u, 'POST', b); },
    put(u, b) { return this.request(u, 'PUT', b); },
    del(u) { return this.request(u, 'DELETE'); },
});

window.Alpine = Alpine;
Alpine.start();

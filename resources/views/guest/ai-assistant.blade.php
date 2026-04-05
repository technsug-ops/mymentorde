@extends('guest.layouts.app')

@section('title', 'AI Başvuru Asistanı')
@section('page_title', 'AI Başvuru Asistanı')

@push('head')
<style>
.ai-card { margin-bottom: 0 !important; padding: 0 !important; }
.ai-card-head {
    padding: 14px 18px;
    border-bottom: 1px solid var(--u-line);
    display: flex; align-items: center; justify-content: space-between;
}
.ai-card-body { padding: 16px 18px; }
</style>
@endpush

@section('content')
<div style="display:grid;grid-template-columns:3fr 2fr;gap:24px;align-items:start;">

{{-- Sol: Chat Arayüzü --}}
<div class="card ai-card">
    <div class="ai-card-head">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--u-brand);display:flex;align-items:center;justify-content:center;color:#fff;font-size:var(--tx-lg);flex-shrink:0;">🤖</div>
            <div>
                <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">MentorDE AI Asistanı</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">Almanya eğitim başvurusu rehberiniz</div>
            </div>
        </div>
        <span class="badge info" id="remaining-badge">Yükleniyor...</span>
    </div>

    {{-- Mesajlar --}}
    <div id="chat-messages" style="height:420px;overflow-y:auto;padding:16px 18px;display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;gap:8px;align-items:flex-start;">
            <div style="width:28px;height:28px;border-radius:50%;background:var(--u-brand);display:flex;align-items:center;justify-content:center;color:#fff;font-size:var(--tx-sm);flex-shrink:0;">🤖</div>
            <div style="background:var(--u-bg,#f8fafc);border:1px solid var(--u-line);padding:10px 14px;border-radius:0 12px 12px 12px;max-width:85%;font-size:var(--tx-sm);line-height:1.6;">
                Merhaba! Ben MentorDE AI Asistanı. Almanya'ya eğitim başvurunuzla ilgili sorularınızı yanıtlayabilirim.<br><br>
                Örneğin: <em>"Hangi üniversitelere başvurabilirim?"</em>, <em>"Belgelerim tamam mı?"</em>, <em>"Bloke hesap nedir?"</em>
            </div>
        </div>
    </div>

    {{-- Giriş --}}
    <div style="padding:12px 18px;border-top:1px solid var(--u-line);display:flex;gap:8px;align-items:center;">
        <input type="text" id="chat-input" placeholder="Sorunuzu yazın..."
               style="flex:1;border:1px solid var(--u-line);border-radius:8px;padding:9px 14px;font-size:var(--tx-sm);outline:none;background:var(--u-bg,#f8fafc);transition:border-color .15s;"
               onfocus="this.style.borderColor='var(--u-brand)'" onblur="this.style.borderColor='var(--u-line)'"
               maxlength="500">
        <button id="chat-send" class="btn" onclick="sendAiMessage()" style="min-width:84px;flex-shrink:0;">Gönder</button>
    </div>
</div>

{{-- Sağ: Hızlı Sorular + Geçmiş + Limit --}}
<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- Hızlı Sorular --}}
    <div class="card ai-card">
        <div class="ai-card-head">
            <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">💡 Sık Sorulan Sorular</span>
        </div>
        <div class="ai-card-body" style="display:flex;flex-direction:column;gap:6px;">
            @foreach([
                'Hangi üniversitelere başvurabilirim?',
                'Belgelerim tamam mı?',
                'Bloke hesap (Sperrkonto) nedir?',
                'Vize başvurusu ne zaman yapılmalı?',
                'Toplam maliyet ne kadar tutar?',
                'Plus ve Premium paket farkı nedir?',
                'Uni-Assist nedir, nasıl kullanılır?',
            ] as $q)
            <button class="btn alt" onclick="setQuestion('{{ $q }}')"
                    style="text-align:left;font-size:var(--tx-sm);padding:7px 12px;border-radius:7px;width:100%;">{{ $q }}</button>
            @endforeach
        </div>
    </div>

    {{-- Konuşma Geçmişi --}}
    <div class="card ai-card">
        <div class="ai-card-head">
            <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">📋 Önceki Sorularım</span>
        </div>
        <div class="ai-card-body">
            <div id="history-list" style="display:flex;flex-direction:column;gap:8px;max-height:240px;overflow-y:auto;">
                <div style="color:var(--u-muted);font-size:var(--tx-sm);">Yükleniyor...</div>
            </div>
        </div>
    </div>

    {{-- Limit Bilgisi --}}
    <div class="card ai-card" style="background:rgba(37,99,235,.04)!important;">
        <div class="ai-card-body">
            <div style="font-size:var(--tx-sm);color:var(--u-muted);line-height:1.7;">
                <strong style="color:var(--u-text);">Günlük Soru Hakkı</strong><br>
                Basic: 5 soru/gün &middot; Plus: 10 soru/gün &middot; Premium: Sınırsız
            </div>
            <a href="{{ route('guest.services') }}"
               class="btn ok"
               style="display:block;text-align:center;margin-top:10px;font-size:var(--tx-xs);padding:7px 12px;text-decoration:none;">
                Paketi Yükselt →
            </a>
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
const AI_ASK_URL      = '{{ route("guest.ai-assistant.ask") }}';
const AI_HISTORY_URL  = '{{ route("guest.ai-assistant.history") }}';
const AI_REMAINING_URL= '{{ route("guest.ai-assistant.remaining") }}';
const CSRF            = '{{ csrf_token() }}';

async function loadRemaining() {
    const r = await fetch(AI_REMAINING_URL).then(r => r.json()).catch(() => ({remaining: '?', limit: '?'}));
    const badge = document.getElementById('remaining-badge');
    const input = document.getElementById('chat-input');
    const btn   = document.getElementById('chat-send');

    if (r.remaining === 0) {
        badge.textContent = 'Günlük limit doldu';
        badge.className   = 'badge danger';
        input.disabled    = true;
        input.placeholder = 'Günlük soru hakkınız tükendi.';
        btn.disabled      = true;

        // Kota uyarı banner — bir kez ekle
        if (!document.getElementById('quota-banner')) {
            const banner = document.createElement('div');
            banner.id        = 'quota-banner';
            banner.style.cssText = 'margin:10px 18px;padding:10px 14px;border-radius:8px;border:1px solid #fca5a5;background:#fef2f2;font-size:13px;color:#991b1b;display:flex;justify-content:space-between;align-items:center;gap:12px;';
            banner.innerHTML = `<span>Günlük ${r.limit} soru hakkınızı kullandınız. Yarın tekrar sorabilirsiniz.</span>
                <a href="{{ route('guest.services') }}" style="white-space:nowrap;font-weight:700;color:#dc2626;text-decoration:underline;">Paketi Yükselt →</a>`;
            document.getElementById('chat-messages').after(banner);
        }
    } else {
        badge.textContent = `${r.remaining}/${r.limit} hak kaldı`;
        badge.className   = r.remaining <= 1 ? 'badge warn' : 'badge info';
        input.disabled    = false;
        btn.disabled      = false;
    }
}

async function loadHistory() {
    const data = await fetch(AI_HISTORY_URL).then(r => r.json()).catch(() => ({history: []}));
    const el = document.getElementById('history-list');
    if (!data.history.length) {
        el.innerHTML = '<div style="color:var(--u-muted);font-size:var(--tx-sm);">Henüz soru sormadınız.</div>';
        return;
    }
    el.innerHTML = data.history.map(h => `
        <div style="border:1px solid var(--u-line);border-radius:8px;padding:9px 12px;font-size:var(--tx-xs);background:var(--u-bg,#f8fafc);">
            <div style="font-weight:600;margin-bottom:3px;color:var(--u-text);">❓ ${escHtml(h.question)}</div>
            <div style="color:var(--u-muted);font-size:var(--tx-xs);line-height:1.4;">${escHtml(h.answer.substring(0,90))}...</div>
        </div>
    `).join('');
}

function setQuestion(q) {
    document.getElementById('chat-input').value = q;
    document.getElementById('chat-input').focus();
}

function appendMsg(role, text) {
    const wrap = document.getElementById('chat-messages');
    const isBot = role === 'bot';
    const div = document.createElement('div');
    div.className = 'msg-' + role;
    div.style.cssText = 'display:flex;gap:8px;align-items:flex-start' + (isBot ? '' : ';flex-direction:row-reverse');
    div.innerHTML = `
        <div style="width:28px;height:28px;border-radius:50%;background:${isBot ? 'var(--u-brand)' : '#e2e8f0'};display:flex;align-items:center;justify-content:center;font-size:var(--tx-sm);flex-shrink:0;">${isBot ? '🤖' : '👤'}</div>
        <div style="background:${isBot ? 'var(--u-bg,#f8fafc)' : 'var(--u-brand)'};${isBot ? 'border:1px solid var(--u-line);' : ''}color:${isBot ? 'inherit' : '#fff'};padding:10px 14px;border-radius:${isBot ? '0 12px 12px 12px' : '12px 0 12px 12px'};max-width:85%;font-size:var(--tx-sm);line-height:1.6;white-space:pre-wrap;">${escHtml(text)}</div>
    `;
    wrap.appendChild(div);
    wrap.scrollTop = wrap.scrollHeight;
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

async function sendAiMessage() {
    const input = document.getElementById('chat-input');
    const btn   = document.getElementById('chat-send');
    const q     = input.value.trim();
    if (!q) return;

    appendMsg('user', q);
    input.value = '';
    btn.disabled = true;
    btn.textContent = '...';

    appendMsg('bot', '⌛ Yanıt bekleniyor...');

    const res = await fetch(AI_ASK_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
        body: JSON.stringify({question: q})
    }).then(r => r.json()).catch(() => ({ok: false, answer: 'Bağlantı hatası.'}));

    const msgs = document.querySelectorAll('#chat-messages .msg-bot');
    const last = msgs[msgs.length - 1];
    if (last) last.querySelector('div:last-child').textContent = res.answer;

    btn.disabled = false;
    btn.textContent = 'Gönder';
    await loadRemaining();
    await loadHistory();
}

document.getElementById('chat-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendAiMessage(); }
});

loadRemaining();
loadHistory();
</script>
@endpush

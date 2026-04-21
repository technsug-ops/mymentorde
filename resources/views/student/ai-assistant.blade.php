@extends('student.layouts.app')

@section('title', 'AI Asistan')
@section('page_title', 'AI Asistan')

@push('head')
<style>
/* ══════ Hero (Option B) ══════ */
.ai-hero { color:#fff; border-radius:14px; margin-bottom:16px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.1); position:relative;
    background:#831843 url('https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1400&q=80') center/cover; }
.ai-hero::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg, rgba(131,24,67,.93) 0%, rgba(219,39,119,.82) 100%); }
.ai-hero-body { position:relative; display:flex; align-items:center; gap:20px; padding:22px 26px; }
.ai-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:7px; }
.ai-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.85; }
.ai-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.ai-hero-title { font-size:24px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.3px; }
.ai-hero-sub { font-size:12.5px; opacity:.88; line-height:1.5; max-width:600px; }
.ai-hero-stats { display:flex; gap:7px; flex-wrap:wrap; margin-top:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.ai-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.ai-hero-icon { font-size:50px; line-height:1; flex-shrink:0; opacity:.88; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }
@media (max-width:640px){ .ai-hero-body { gap:14px; padding:18px; align-items:flex-start; } .ai-hero-title { font-size:20px; } .ai-hero-sub { font-size:12px; } .ai-hero-icon { font-size:36px; } }

.ai-card { margin-bottom: 0 !important; padding: 0 !important; }
.ai-card-head { padding: 14px 18px; border-bottom: 1px solid var(--u-line); display: flex; align-items: center; justify-content: space-between; }
.ai-card-body { padding: 16px 18px; }
@media(max-width:900px){ .ai-grid { grid-template-columns:1fr !important; } }
</style>
@endpush

@section('content')
{{-- ══════ Hero ══════ --}}
<div class="ai-hero">
    <div class="ai-hero-body">
        <div class="ai-hero-main">
            <div class="ai-hero-label"><span class="ai-hero-marker"></span>Akıllı Danışman</div>
            <h1 class="ai-hero-title">AI Asistan</h1>
            <div class="ai-hero-sub">Almanya eğitim süreciyle ilgili sorularını 7/24 yanıtlıyorum. Sperrkonto'dan vize randevusuna, üniversite kaydından dil sertifikasına kadar — sorulabilir her şey.</div>
            <div class="ai-hero-stats">
                <span class="ai-hero-stat">🤖 Gemini 2.0 destekli</span>
                <span class="ai-hero-stat">⚡ Anında yanıt</span>
                <span class="ai-hero-stat">🇹🇷 Türkçe</span>
            </div>
        </div>
        <div class="ai-hero-icon">🤖</div>
    </div>
</div>

<div class="ai-grid" style="display:grid;grid-template-columns:3fr 2fr;gap:24px;align-items:start;">

{{-- Sol: Chat Arayüzü --}}
<div class="card ai-card">
    <div class="ai-card-head">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--u-brand);display:flex;align-items:center;justify-content:center;color:#fff;font-size:var(--tx-lg);flex-shrink:0;">🤖</div>
            <div>
                <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">{{ config('brand.name', 'MentorDE') }} AI Asistanı</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">Almanya sürecinizde size yardımcı oluyorum</div>
            </div>
        </div>
        <span class="badge info" id="remaining-badge">Yükleniyor...</span>
    </div>

    <div id="chat-messages" style="height:420px;overflow-y:auto;padding:16px 18px;display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;gap:8px;align-items:flex-start;">
            <div style="width:28px;height:28px;border-radius:50%;background:var(--u-brand);display:flex;align-items:center;justify-content:center;color:#fff;font-size:var(--tx-sm);flex-shrink:0;">🤖</div>
            <div style="background:var(--u-bg,#f8fafc);border:1px solid var(--u-line);padding:10px 14px;border-radius:0 12px 12px 12px;max-width:85%;font-size:var(--tx-sm);line-height:1.6;">
                Merhaba! Ben {{ config('brand.name', 'MentorDE') }} AI Asistanı. Almanya'daki eğitim sürecinizle ilgili sorularınızı yanıtlayabilirim.<br><br>
                Örneğin: <em>"Sürecim nerede?"</em>, <em>"Sperrkonto nedir?"</em>, <em>"Üniversite kayıt için ne gerekli?"</em>
            </div>
        </div>
    </div>

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

    <div class="card ai-card">
        <div class="ai-card-head">
            <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">💡 Sık Sorulan Sorular</span>
        </div>
        <div class="ai-card-body" style="display:flex;flex-direction:column;gap:6px;">
            @foreach([
                'Sürecimin hangi aşamasındayım?',
                'Sperrkonto (bloke hesap) nasıl açılır?',
                'Üniversite kaydı için hangi belgeler gerekli?',
                'Vize randevusu ne zaman almalıyım?',
                'İmmatrikülayon süreci nasıl işler?',
                'Wohnheim (öğrenci yurdu) nasıl bulunur?',
                'Almanya\'da sağlık sigortası zorunlu mu?',
            ] as $q)
            <button class="btn alt" onclick="setQuestion('{{ $q }}')"
                    style="text-align:left;font-size:var(--tx-sm);padding:7px 12px;border-radius:7px;width:100%;">{{ $q }}</button>
            @endforeach
        </div>
    </div>

    <div class="card ai-card">
        <div class="ai-card-head">
            <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">📜 Geçmiş Sorular</span>
            <button class="btn alt" style="font-size:var(--tx-xs);padding:4px 10px;" onclick="loadHistory()">Yenile</button>
        </div>
        <div id="history-list" class="ai-card-body" style="max-height:260px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;">
            <div style="color:var(--u-muted);font-size:var(--tx-sm);">Yükleniyor...</div>
        </div>
    </div>

    <div class="card ai-card">
        <div class="ai-card-body" style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:var(--tx-sm);color:var(--u-muted);">Günlük kullanım</span>
            <span id="limit-display" style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">—</span>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
const __aiRoutes = {
    ask:       '/student/ai-assistant/ask',
    history:   '/student/ai-assistant/history',
    remaining: '/student/ai-assistant/remaining',
    csrf:      '{{ csrf_token() }}',
};

function sendAiMessage() {
    const input = document.getElementById('chat-input');
    const q = (input?.value || '').trim();
    if (!q) return;
    appendMessage('user', q);
    input.value = '';
    document.getElementById('chat-send').disabled = true;

    fetch(__aiRoutes.ask, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': __aiRoutes.csrf},
        body: JSON.stringify({question: q}),
    })
    .then(r => r.json())
    .then(d => { appendMessage('bot', d.answer || 'Bir hata oluştu.'); loadRemaining(); })
    .catch(() => appendMessage('bot', 'Bağlantı hatası. Lütfen tekrar deneyin.'))
    .finally(() => document.getElementById('chat-send').disabled = false);
}

function setQuestion(q) {
    const input = document.getElementById('chat-input');
    if (input) { input.value = q; input.focus(); }
}

function appendMessage(role, text) {
    const box = document.getElementById('chat-messages');
    const isBot = role === 'bot';
    const div = document.createElement('div');
    div.style.cssText = 'display:flex;gap:8px;align-items:flex-start;' + (isBot ? '' : 'flex-direction:row-reverse;');
    const avatar = document.createElement('div');
    avatar.style.cssText = 'width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;flex-shrink:0;background:' + (isBot ? 'var(--u-brand)' : '#64748b') + ';';
    avatar.textContent = isBot ? '🤖' : '👤';
    const bubble = document.createElement('div');
    bubble.style.cssText = 'padding:10px 14px;border-radius:' + (isBot ? '0 12px 12px 12px' : '12px 0 12px 12px') + ';max-width:85%;font-size:var(--tx-sm);line-height:1.6;' + (isBot ? 'background:var(--u-bg,#f8fafc);border:1px solid var(--u-line);' : 'background:var(--u-brand);color:#fff;');
    bubble.innerHTML = text.replace(/\n/g, '<br>');
    div.appendChild(avatar);
    div.appendChild(bubble);
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

function loadHistory() {
    fetch(__aiRoutes.history).then(r => r.json()).then(d => {
        const list = document.getElementById('history-list');
        if (!d.history?.length) { list.innerHTML = '<div style="color:var(--u-muted);font-size:var(--tx-sm);">Henüz soru sorulmadı.</div>'; return; }
        list.innerHTML = d.history.map(h => `
            <div style="border-bottom:1px solid var(--u-line);padding-bottom:8px;">
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-text);margin-bottom:3px;">❓ ${escHtml(h.question)}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">🤖 ${escHtml((h.answer||'').substring(0,120))}${(h.answer||'').length > 120 ? '…' : ''}</div>
            </div>`).join('');
    });
}

function loadRemaining() {
    fetch(__aiRoutes.remaining).then(r => r.json()).then(d => {
        const badge = document.getElementById('remaining-badge');
        const lim   = document.getElementById('limit-display');
        if (badge) badge.textContent = d.remaining + ' soru hakkı kaldı';
        if (lim)   lim.textContent   = d.remaining + ' / ' + d.limit;
    });
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

document.getElementById('chat-input')?.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendAiMessage(); } });

loadHistory();
loadRemaining();
</script>
@endpush
@endsection

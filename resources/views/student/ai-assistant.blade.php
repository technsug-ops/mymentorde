@extends('student.layouts.app')

@section('title', 'AI Asistan')
@section('page_title', 'AI Asistan')

@push('head')
<style>
.ai-card { margin-bottom: 0 !important; padding: 0 !important; }
.ai-card-head { padding: 14px 18px; border-bottom: 1px solid var(--u-line); display: flex; align-items: center; justify-content: space-between; }
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

@extends($portalLayout)
@section('title', ($aiLabsName ?? 'AI Labs') . ' — ' . $roleLabel)
@section('page_title', '🧠 ' . ($aiLabsName ?? 'MentorDE AI Labs') . ' — ' . $roleLabel)

@section('content')
<style>
.aia-wrap { max-width:1100px; margin:20px auto; padding:0 16px; }
.aia-grid { display:grid; grid-template-columns:1fr 300px; gap:18px; }
@media(max-width:900px){ .aia-grid { grid-template-columns:1fr; } }
.aia-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px; }
.aia-chat { display:flex; flex-direction:column; height:70vh; min-height:480px; }
.aia-messages { flex:1; overflow-y:auto; padding:8px 4px; border:1px solid #f1f5f9; border-radius:10px; background:#fafbfc; margin-bottom:12px; }
.aia-msg { margin:10px 6px; display:flex; gap:10px; }
.aia-msg.user { justify-content:flex-end; }
.aia-msg-bubble { max-width:80%; padding:10px 14px; border-radius:14px; font-size:14px; line-height:1.55; white-space:pre-wrap; }
.aia-msg.user .aia-msg-bubble { background:#5b2e91; color:#fff; border-bottom-right-radius:4px; }
.aia-msg.bot .aia-msg-bubble { background:#fff; color:#0f172a; border:1px solid #e2e8f0; border-bottom-left-radius:4px; }
.aia-mode-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700; margin-bottom:6px; }
.aia-mode-source { background:#dcfce7; color:#166534; }
.aia-mode-external { background:#fef3c7; color:#92400e; }
.aia-mode-refused { background:#f1f5f9; color:#64748b; }
.aia-citations { margin-top:8px; padding-top:8px; border-top:1px dashed #e2e8f0; font-size:11px; color:#64748b; }
.aia-citations a { color:#5b2e91; text-decoration:none; }
.aia-citations a:hover { text-decoration:underline; }
.aia-feedback { display:flex; gap:6px; margin-top:8px; }
.aia-feedback button {
    background:transparent; border:1px solid #e2e8f0; border-radius:6px;
    padding:3px 10px; cursor:pointer; font-size:12px; transition:all .15s;
}
.aia-feedback button:hover { border-color:#5b2e91; background:#faf7ff; }
.aia-feedback button.voted-good { background:#dcfce7; border-color:#86efac; color:#166534; }
.aia-feedback button.voted-bad { background:#fee2e2; border-color:#fca5a5; color:#991b1b; }
.aia-feedback .fb-sent { font-size:11px; color:#64748b; padding:3px 8px; }
.aia-input-row { display:flex; gap:8px; }
.aia-input-row textarea { flex:1; min-height:52px; max-height:160px; padding:10px 12px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; resize:vertical; font-family:inherit; }
.aia-input-row button { padding:10px 20px; background:#5b2e91; color:#fff; border:none; border-radius:10px; font-weight:700; cursor:pointer; font-size:14px; }
.aia-input-row button:disabled { opacity:.5; cursor:not-allowed; }
.aia-sidebar-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:14px; }
.aia-sidebar-card h3 { margin:0 0 10px; font-size:13px; color:#334155; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
.aia-limit-pill { background:#faf7ff; border:1px solid #ede9fe; border-radius:10px; padding:10px; text-align:center; font-size:12px; color:#5b2e91; }
.aia-limit-pill strong { font-size:22px; display:block; }
.aia-hist-item { padding:8px 10px; border-bottom:1px solid #f1f5f9; font-size:11px; color:#64748b; cursor:pointer; }
.aia-hist-item:hover { background:#faf7ff; color:#5b2e91; }
.aia-hist-item:last-child { border-bottom:none; }
.aia-empty { text-align:center; padding:30px 10px; color:#94a3b8; font-size:12px; }
.aia-typing { color:#64748b; font-style:italic; font-size:13px; }
.aia-error { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:10px; }
.aia-welcome { text-align:center; padding:40px 20px; color:#94a3b8; }
.aia-welcome h2 { color:#5b2e91; margin-bottom:10px; }
</style>

<div class="aia-wrap">
    <div class="aia-grid">
        <div class="aia-card aia-chat">
            <div class="aia-messages" id="aia-messages">
                <div class="aia-welcome" id="aia-welcome">
                    @php
                        $userName = trim(explode(' ', (string) (auth()->user()->name ?? ''))[0] ?? '');
                        $greeting = $userName !== '' ? "Merhaba {$userName} 👋" : 'Merhaba 👋';
                    @endphp
                    <h2>{{ $greeting }}</h2>
                    <p style="font-size:15px; color:#334155; margin-bottom:8px;">
                        Ben <strong>{{ $aiLabsName ?? 'MentorDE AI Labs' }}</strong>, {{ $roleLabel }} rolünde sana yardımcı oluyorum.
                    </p>
                    <p style="font-size:13px;">
                        🟢 Bilgi havuzundan çıkan yanıtlarda kaynak referansı görürsün.<br>
                        🟡 Havuz dışı konularda genel bilgi + uyarı gelir.<br>
                        ⚪ Uzmanlık alanımız dışındaki soruları kibarca reddederim.
                    </p>
                </div>
            </div>
            <div class="aia-input-row">
                <textarea id="aia-input" placeholder="Sorunu yaz... (ör. Sperrkonto için öğrenciye hangi bankayı öneririm?)"></textarea>
                <button id="aia-send" type="button">Gönder</button>
            </div>
        </div>

        <div>
            <div class="aia-sidebar-card">
                <h3>📊 Günlük Kota</h3>
                <div class="aia-limit-pill">
                    <strong id="aia-remaining">—</strong>
                    <span>kalan / <span id="aia-limit">—</span> soru</span>
                </div>
            </div>

            <div class="aia-sidebar-card">
                <h3>🕐 Son Sorular</h3>
                <div id="aia-history">
                    <div class="aia-empty">Henüz soru yok.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    const ROLE = @json($role);
    const ROLE_URL = @json('/' . str_replace('_', '-', $role));
    const BASE = ROLE === 'admin_staff' ? '/admin-staff/ai-assistant' : '/' + ROLE + '/ai-assistant';
    const URLs = {
        ask:       BASE + '/ask',
        askStream: BASE + '/ask-stream',
        history:   BASE + '/history',
        remaining: BASE + '/remaining',
    };

    const msgsEl  = document.getElementById('aia-messages');
    const welcome = document.getElementById('aia-welcome');
    const input   = document.getElementById('aia-input');
    const sendBtn = document.getElementById('aia-send');
    const histEl  = document.getElementById('aia-history');
    const remEl   = document.getElementById('aia-remaining');
    const limEl   = document.getElementById('aia-limit');

    const token = () => document.querySelector('meta[name="csrf-token"]')?.content
                   || document.querySelector('input[name="_token"]')?.value
                   || '{{ csrf_token() }}';

    function escapeHtml(s) {
        return (s || '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function modeBadge(mode) {
        if (mode === 'source')  return '<span class="aia-mode-badge aia-mode-source">🟢 KAYNAKTAN</span>';
        if (mode === 'refused') return '<span class="aia-mode-badge aia-mode-refused">⚪ KAPSAM DIŞI</span>';
        if (mode === 'external')return '<span class="aia-mode-badge aia-mode-external">🟡 GENEL BİLGİ</span>';
        return '';
    }

    function renderSources(sources) {
        if (!sources || !sources.length) return '';
        const items = sources.map(s => {
            const title = escapeHtml(s.title || ('Kaynak #' + s.id));
            if (s.type === 'url' && s.url) {
                return '<a href="' + escapeHtml(s.url) + '" target="_blank">📚 #' + s.id + ' ' + title + '</a>';
            }
            return '<span>📚 #' + s.id + ' ' + title + '</span>';
        }).join(' • ');
        return '<div class="aia-citations">' + items + '</div>';
    }

    function appendUser(text) {
        if (welcome) welcome.style.display = 'none';
        const div = document.createElement('div');
        div.className = 'aia-msg user';
        div.innerHTML = '<div class="aia-msg-bubble">' + escapeHtml(text) + '</div>';
        msgsEl.appendChild(div);
        msgsEl.scrollTop = msgsEl.scrollHeight;
    }

    function appendBot(payload) {
        if (welcome) welcome.style.display = 'none';
        const div = document.createElement('div');
        div.className = 'aia-msg bot';

        const answer = payload.answer || payload.content || 'Yanıt alınamadı.';
        const badge = modeBadge(payload.mode);
        const sources = renderSources(payload.sources_meta);

        // Feedback butonları — conversation_id gelirse
        let feedback = '';
        if (payload.conversation_id && payload.conversation_type) {
            feedback = `<div class="aia-feedback" data-conv-type="${payload.conversation_type}" data-conv-id="${payload.conversation_id}">
                <button type="button" data-rating="good" title="Bu yanıt işime yaradı">👍</button>
                <button type="button" data-rating="bad" title="Bu yanıt yanlış veya eksik">👎</button>
            </div>`;
        }

        div.innerHTML = '<div class="aia-msg-bubble">' + badge +
                        '<div>' + escapeHtml(answer) + '</div>' + sources + feedback + '</div>';
        msgsEl.appendChild(div);
        msgsEl.scrollTop = msgsEl.scrollHeight;

        // Feedback listener
        const fbBar = div.querySelector('.aia-feedback');
        if (fbBar) bindFeedback(fbBar);
    }

    async function bindFeedback(bar) {
        bar.querySelectorAll('button[data-rating]').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (bar.dataset.sent === '1') return;
                const convType = bar.dataset.convType;
                const convId = bar.dataset.convId;
                const rating = btn.dataset.rating;

                const token = document.querySelector('meta[name="csrf-token"]')?.content
                           || document.querySelector('input[name="_token"]')?.value
                           || '{{ csrf_token() }}';
                const fd = new FormData();
                fd.append('conversation_type', convType);
                fd.append('conversation_id', convId);
                fd.append('rating', rating);

                try {
                    const res = await fetch('{{ url("/ai-labs/feedback") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd,
                    });
                    const data = await res.json();
                    if (data.ok) {
                        bar.querySelectorAll('button').forEach(b => b.disabled = true);
                        btn.classList.add(rating === 'good' ? 'voted-good' : 'voted-bad');
                        bar.dataset.sent = '1';
                        bar.insertAdjacentHTML('beforeend', '<span class="fb-sent">✓ Teşekkürler</span>');
                    }
                } catch (e) { /* ignore */ }
            });
        });
    }

    function appendTyping() {
        const div = document.createElement('div');
        div.className = 'aia-msg bot';
        div.id = 'aia-typing-row';
        div.innerHTML = '<div class="aia-msg-bubble aia-typing">⏳ Düşünüyor... (kaynak havuzuna bakıyor)</div>';
        msgsEl.appendChild(div);
        msgsEl.scrollTop = msgsEl.scrollHeight;
    }
    function removeTyping() { document.getElementById('aia-typing-row')?.remove(); }

    async function loadRemaining() {
        try {
            const res = await fetch(URLs.remaining);
            const data = await res.json();
            remEl.textContent = data.remaining ?? '—';
            limEl.textContent = data.limit ?? '—';
        } catch (e) { /* ignore */ }
    }

    async function loadHistory() {
        try {
            const res = await fetch(URLs.history);
            const data = await res.json();
            const rows = data.history || [];
            if (!rows.length) {
                histEl.innerHTML = '<div class="aia-empty">Henüz soru yok.</div>';
                return;
            }
            histEl.innerHTML = rows.slice(0, 10).map(r => {
                const q = (r.question || '').slice(0, 80);
                return '<div class="aia-hist-item" title="' + escapeHtml(r.question || '') + '">' + escapeHtml(q) + (r.question && r.question.length > 80 ? '…' : '') + '</div>';
            }).join('');
        } catch (e) { /* ignore */ }
    }

    function appendStreamingBot() {
        if (welcome) welcome.style.display = 'none';
        const div = document.createElement('div');
        div.className = 'aia-msg bot streaming';
        div.innerHTML = '<div class="aia-msg-bubble"><div class="streaming-text"></div></div>';
        msgsEl.appendChild(div);
        msgsEl.scrollTop = msgsEl.scrollHeight;
        return div;
    }

    async function sendMessage() {
        const q = (input.value || '').trim();
        if (q.length < 3) return;

        input.value = '';
        sendBtn.disabled = true;
        appendUser(q);

        const streamDiv = appendStreamingBot();
        const textEl = streamDiv.querySelector('.streaming-text');

        try {
            const fd = new FormData();
            fd.append('question', q);

            const res = await fetch(URLs.askStream, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token(),
                    'Accept': 'text/event-stream',
                },
                body: fd,
            });

            if (!res.ok || !res.body) {
                textEl.textContent = 'Bağlantı hatası (' + res.status + ')';
                return;
            }

            const reader = res.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let fullText = '';
            let finalMeta = null;

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });

                // SSE event ayırıcı \n\n
                let idx;
                while ((idx = buffer.indexOf('\n\n')) !== -1) {
                    const event = buffer.slice(0, idx);
                    buffer = buffer.slice(idx + 2);
                    for (const line of event.split('\n')) {
                        if (!line.startsWith('data: ')) continue;
                        try {
                            const parsed = JSON.parse(line.slice(6));
                            if (parsed.error) {
                                textEl.innerHTML = '<em style="color:#991b1b;">⚠️ ' + (parsed.error || 'Hata') + '</em>';
                                return;
                            }
                            if (parsed.chunk) {
                                fullText += parsed.chunk;
                                textEl.textContent = fullText;
                                msgsEl.scrollTop = msgsEl.scrollHeight;
                            }
                            if (parsed.done) {
                                finalMeta = parsed;
                            }
                        } catch (e) { /* ignore malformed line */ }
                    }
                }
            }

            // Stream bitti — meta ile bubble'ı zenginleştir
            if (finalMeta) {
                streamDiv.classList.remove('streaming');
                // Mode badge + sources + feedback ekle
                const answer = finalMeta.clean_content || fullText;
                let html = modeBadge(finalMeta.mode);
                html += '<div>' + escapeHtml(answer) + '</div>';
                html += renderSources(finalMeta.sources_meta);
                if (finalMeta.conversation_id && finalMeta.conversation_type) {
                    html += `<div class="aia-feedback" data-conv-type="${finalMeta.conversation_type}" data-conv-id="${finalMeta.conversation_id}">
                        <button type="button" data-rating="good" title="Bu yanıt işime yaradı">👍</button>
                        <button type="button" data-rating="bad" title="Bu yanıt yanlış veya eksik">👎</button>
                    </div>`;
                }
                streamDiv.querySelector('.aia-msg-bubble').innerHTML = html;
                const fbBar = streamDiv.querySelector('.aia-feedback');
                if (fbBar) bindFeedback(fbBar);

                loadRemaining();
                loadHistory();
            }
        } catch (e) {
            textEl.innerHTML = '<em style="color:#991b1b;">❌ ' + e.message + '</em>';
        } finally {
            sendBtn.disabled = false;
            input.focus();
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    loadRemaining();
    loadHistory();
})();
</script>
@endsection

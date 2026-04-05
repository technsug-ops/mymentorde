@extends('senior.layouts.app')

@section('title', 'AI Danışman Asistanı')
@section('page_title', 'AI Danışman Asistanı')

@push('head')
<style>
.sai-card { margin-bottom:0!important; padding:0!important; }
.sai-card-head {
    padding:14px 18px;
    border-bottom:1px solid var(--u-line);
    display:flex; align-items:center; justify-content:space-between;
}
.sai-card-body { padding:14px 18px; }

/* Öğrenci bağlam bandı */
.sai-ctx-band {
    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    padding:10px 18px;
    background:rgba(124,58,237,.06);
    border-bottom:1px solid rgba(124,58,237,.15);
    font-size:12px; color:var(--u-text);
}
.sai-ctx-band.hidden { display:none; }
.sai-ctx-chip {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 9px; border-radius:999px;
    border:1px solid rgba(124,58,237,.25);
    background:#fff; color:#6d28d9; font-size:11px; font-weight:600;
}
.sai-ctx-clear {
    margin-left:auto; font-size:11px; color:#9ca3af; cursor:pointer;
    text-decoration:underline; white-space:nowrap;
}
.sai-ctx-clear:hover { color:#6d28d9; }
</style>
@endpush

@section('content')
<div style="display:grid;grid-template-columns:3fr 2fr;gap:24px;align-items:start;">

{{-- Sol: Chat --}}
<div class="card sai-card">
    <div class="sai-card-head">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--u-brand);display:flex;align-items:center;justify-content:center;color:#fff;font-size:var(--tx-lg);flex-shrink:0;">🤖</div>
            <div>
                <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">MentorDE Danışman Asistanı</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">Almanya eğitim sistemi · öğrenci analizi · süreç rehberliği</div>
            </div>
        </div>
        <span class="badge info" id="remaining-badge">Yükleniyor...</span>
    </div>

    {{-- Öğrenci bağlam bandı --}}
    <div class="sai-ctx-band {{ $studentId ? '' : 'hidden' }}" id="ctx-band">
        <span>🎓 Bağlam:</span>
        <span class="sai-ctx-chip" id="ctx-chip">{{ $studentId ?: '' }}</span>
        <span class="sai-ctx-clear" id="ctx-clear">Bağlamı temizle</span>
    </div>

    {{-- Mesajlar --}}
    <div id="chat-messages" style="height:420px;overflow-y:auto;padding:16px 18px;display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;gap:8px;align-items:flex-start;">
            <div style="width:28px;height:28px;border-radius:50%;background:var(--u-brand);display:flex;align-items:center;justify-content:center;color:#fff;font-size:var(--tx-sm);flex-shrink:0;">🤖</div>
            <div style="background:var(--u-bg,#f8fafc);border:1px solid var(--u-line);padding:10px 14px;border-radius:0 12px 12px 12px;max-width:85%;font-size:var(--tx-sm);line-height:1.6;">
                Merhaba! Ben MentorDE Danışman Asistanı. Öğrencilerinizle ilgili süreçlerde, Almanya eğitim sistemi sorularında ve yanıt taslakları hazırlamada size yardımcı olabilirim.<br><br>
                @if($studentId)
                    <strong>Aktif bağlam:</strong> <code>{{ $studentId }}</code> öğrencisinin verileri yüklendi. Bu öğrenciyle ilgili sorularınızı sorabilirsiniz.
                @else
                    Sağdaki hızlı sorulardan birini seçin veya sorunuzu yazın. Belirli bir öğrenci için analiz yapmak isterseniz "Öğrenci bağlamı ekle" alanına öğrenci ID'sini girin.
                @endif
            </div>
        </div>
    </div>

    {{-- Giriş --}}
    <div style="padding:12px 18px;border-top:1px solid var(--u-line);">
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
            <input type="text" id="student-id-input"
                   placeholder="Öğrenci ID (opsiyonel, örn: STD-000001)"
                   value="{{ $studentId }}"
                   style="flex:1;border:1px solid var(--u-line);border-radius:8px;padding:7px 12px;font-size:var(--tx-xs);outline:none;background:var(--u-bg);transition:border-color .15s;color:var(--u-muted);"
                   onfocus="this.style.borderColor='var(--u-brand)'" onblur="this.style.borderColor='var(--u-line)'"
                   maxlength="32">
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" id="chat-input" placeholder="Sorunuzu yazın..."
                   style="flex:1;border:1px solid var(--u-line);border-radius:8px;padding:9px 14px;font-size:var(--tx-sm);outline:none;background:var(--u-bg);transition:border-color .15s;"
                   onfocus="this.style.borderColor='var(--u-brand)'" onblur="this.style.borderColor='var(--u-line)'"
                   maxlength="600">
            <button id="chat-send" class="btn" onclick="sendAiMessage()" style="min-width:84px;flex-shrink:0;">Gönder</button>
        </div>
    </div>
</div>

{{-- Sağ: Hızlı Sorular + Geçmiş + Limit --}}
<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- Hızlı Sorular --}}
    <div class="card sai-card">
        <div class="sai-card-head">
            <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">⚡ Hızlı Sorular</span>
        </div>
        <div class="sai-card-body" style="display:flex;flex-direction:column;gap:5px;">
            @foreach([
                'Bu öğrenci için hangi üniversiteleri önerirsin?',
                'Motivasyon mektubu taslağı hazırlar mısın?',
                'Vize başvurusu için eksik belgeler neler olabilir?',
                'Bloke hesap açma sürecini öğrenciye nasıl anlatalım?',
                'Studienkolleg gerektiren başvurular hangileri?',
                'Dil sınavı olmadan hangi üniversitelere başvurulur?',
                'Öğrenciye geciken süreç için nasıl açıklama yapayım?',
                'APS belgesi ne zaman ve nasıl alınır?',
            ] as $q)
            <button class="btn alt" onclick="setQuestion('{{ $q }}')"
                    style="text-align:left;font-size:var(--tx-xs);padding:6px 10px;border-radius:7px;width:100%;line-height:1.4;">{{ $q }}</button>
            @endforeach
        </div>
    </div>

    {{-- Konuşma Geçmişi --}}
    <div class="card sai-card">
        <div class="sai-card-head">
            <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">📋 Son Sorularım</span>
        </div>
        <div class="sai-card-body">
            <div id="history-list" style="display:flex;flex-direction:column;gap:8px;max-height:240px;overflow-y:auto;">
                <div style="color:var(--u-muted);font-size:var(--tx-sm);">Yükleniyor...</div>
            </div>
        </div>
    </div>

    {{-- Limit Bilgisi --}}
    <div class="card sai-card" style="background:rgba(124,58,237,.04)!important;">
        <div class="sai-card-body">
            <div style="font-size:var(--tx-sm);color:var(--u-muted);line-height:1.7;">
                <strong style="color:var(--u-text);">Günlük Soru Hakkı</strong><br>
                Senior danışmanlar: <strong>{{ $limit }} soru/gün</strong>
            </div>
            <div style="margin-top:8px;font-size:var(--tx-xs);color:var(--u-muted);">
                💡 Belirli bir öğrenci için analiz yapmak isterseniz sol alttaki "Öğrenci ID" alanını doldurun. AI o öğrencinin belgelerini, risk seviyesini ve süreç aşamasını bilerek yanıt verir.
            </div>
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
const AI_ASK_URL      = '{{ route("senior.ai-assistant.ask") }}';
const AI_HISTORY_URL  = '{{ route("senior.ai-assistant.history") }}';
const AI_REMAINING_URL= '{{ route("senior.ai-assistant.remaining") }}';
const CSRF            = '{{ csrf_token() }}';

let currentStudentId = '{{ $studentId }}';

// ── Kota yükle ──────────────────────────────────────────────────────────────
async function loadRemaining() {
    const r = await fetch(AI_REMAINING_URL).then(r => r.json()).catch(() => ({remaining:'?', limit:'?'}));
    const badge = document.getElementById('remaining-badge');
    const input = document.getElementById('chat-input');
    const btn   = document.getElementById('chat-send');

    if (r.remaining === 0) {
        badge.textContent = 'Günlük limit doldu';
        badge.className   = 'badge danger';
        input.disabled    = true;
        input.placeholder = 'Günlük soru hakkınız tükendi.';
        btn.disabled      = true;

        if (!document.getElementById('quota-banner')) {
            const banner = document.createElement('div');
            banner.id = 'quota-banner';
            banner.style.cssText = 'margin:10px 18px;padding:10px 14px;border-radius:8px;border:1px solid #c4b5fd;background:#f5f3ff;font-size:13px;color:#5b21b6;';
            banner.textContent = `Günlük ${r.limit} soru hakkınızı kullandınız. Yarın tekrar sorabilirsiniz.`;
            document.getElementById('chat-messages').after(banner);
        }
    } else {
        badge.textContent = `${r.remaining}/${r.limit} hak kaldı`;
        badge.className   = r.remaining <= 5 ? 'badge warn' : 'badge info';
        input.disabled    = false;
        btn.disabled      = false;
    }
}

// ── Geçmiş yükle ────────────────────────────────────────────────────────────
async function loadHistory() {
    const url = AI_HISTORY_URL + (currentStudentId ? '?student_id=' + encodeURIComponent(currentStudentId) : '');
    const data = await fetch(url).then(r => r.json()).catch(() => ({history:[]}));
    const el = document.getElementById('history-list');

    if (!data.history.length) {
        el.innerHTML = '<div style="color:var(--u-muted);font-size:var(--tx-sm);">Henüz soru sormadınız.</div>';
        return;
    }
    el.innerHTML = data.history.map(h => `
        <div style="border:1px solid var(--u-line);border-radius:8px;padding:9px 12px;font-size:var(--tx-xs);background:var(--u-bg,#f8fafc);">
            ${h.student_id ? `<div style="font-size:10px;color:#7c3aed;margin-bottom:2px;">🎓 ${escHtml(h.student_id)}</div>` : ''}
            <div style="font-weight:600;margin-bottom:3px;color:var(--u-text);">❓ ${escHtml(h.question)}</div>
            <div style="color:var(--u-muted);line-height:1.4;">${escHtml(h.answer.substring(0,100))}...</div>
        </div>
    `).join('');
}

// ── Öğrenci bağlamı ──────────────────────────────────────────────────────────
function updateContextBand() {
    const band = document.getElementById('ctx-band');
    const chip = document.getElementById('ctx-chip');
    currentStudentId = document.getElementById('student-id-input').value.trim();
    if (currentStudentId) {
        chip.textContent = currentStudentId;
        band.classList.remove('hidden');
    } else {
        band.classList.add('hidden');
    }
}

document.getElementById('student-id-input').addEventListener('input', updateContextBand);
document.getElementById('ctx-clear').addEventListener('click', function() {
    document.getElementById('student-id-input').value = '';
    updateContextBand();
});

// ── Mesaj yardımcıları ───────────────────────────────────────────────────────
function setQuestion(q) {
    document.getElementById('chat-input').value = q;
    document.getElementById('chat-input').focus();
}

function appendMsg(role, text) {
    const wrap  = document.getElementById('chat-messages');
    const isBot = role === 'bot';
    const div   = document.createElement('div');
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

// ── Mesaj gönder ─────────────────────────────────────────────────────────────
async function sendAiMessage() {
    const input = document.getElementById('chat-input');
    const btn   = document.getElementById('chat-send');
    const q     = input.value.trim();
    if (!q) return;

    appendMsg('user', q);
    input.value  = '';
    btn.disabled = true;
    btn.textContent = '...';

    appendMsg('bot', '⌛ Yanıt bekleniyor...');

    const body = { question: q };
    if (currentStudentId) body.student_id = currentStudentId;

    const res = await fetch(AI_ASK_URL, {
        method:  'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
        body:    JSON.stringify(body),
    }).then(r => r.json()).catch(() => ({ok:false, answer:'Bağlantı hatası.'}));

    const msgs = document.querySelectorAll('#chat-messages .msg-bot');
    const last = msgs[msgs.length - 1];
    if (last) last.querySelector('div:last-child').textContent = res.answer;

    btn.disabled    = false;
    btn.textContent = 'Gönder';
    await loadRemaining();
    await loadHistory();
}

document.getElementById('chat-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendAiMessage(); }
});

document.getElementById('student-id-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { document.getElementById('chat-input').focus(); }
});

loadRemaining();
loadHistory();
</script>
@endpush

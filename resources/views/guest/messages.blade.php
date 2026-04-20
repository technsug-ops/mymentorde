@extends('guest.layouts.app')

@section('title', 'Danışman Mesajları')
@section('page_title', 'Mesajlar')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ── gm-* Guest Messages v2 ── */
.gm-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 0;
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    min-height: 640px;
    height: calc(100vh - 140px);
    max-height: 800px;
}
@media(max-width:760px){ .gm-layout { grid-template-columns: 1fr; } .gm-sidebar { display:none; } }

/* ── LEFT SIDEBAR ── */
.gm-sidebar {
    border-right: 1px solid var(--u-line);
    display: flex; flex-direction: column;
    background: var(--u-bg);
    overflow-y: auto;
}
.gm-advisor-card {
    padding: 20px 16px 16px;
    background: var(--hero-gradient);
    color: #fff;
    display: flex; flex-direction: column; align-items: center; gap: 10px;
    text-align: center;
}
.gm-advisor-av {
    width: 56px; height: 56px; border-radius: 50%;
    background: rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 20px; border: 2px solid rgba(255,255,255,.4);
}
.gm-advisor-name { font-weight: 700; font-size: 14px; }
.gm-advisor-role { font-size: 11px; opacity: .8; }
.gm-status-dot {
    display: inline-block; width: 8px; height: 8px;
    border-radius: 50%; margin-right: 4px;
}
.gm-status-dot.online  { background: #4ade80; }
.gm-status-dot.offline { background: #9ca3af; }

.gm-sidebar-section { padding: 14px 14px 0; }
.gm-sidebar-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--u-muted);
    margin-bottom: 8px;
}
.gm-qa-link {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 10px; border-radius: 8px;
    font-size: 13px; color: var(--u-text); text-decoration: none;
    margin-bottom: 4px; transition: background .12s;
}
.gm-qa-link:hover { background: var(--u-card); text-decoration: none; color: var(--u-brand); }
.gm-qa-icon {
    width: 30px; height: 30px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;
}
.gm-info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 7px 10px; font-size: 12px; border-radius: 7px;
    margin-bottom: 3px;
}
.gm-info-row:hover { background: var(--u-card); }
.gm-info-label { color: var(--u-muted); }
.gm-info-val   { font-weight: 600; color: var(--u-text); }

/* ── RIGHT CHAT ── */
.gm-chat {
    display: flex; flex-direction: column;
    background: var(--u-card);
}

/* Chat Header */
.gm-chat-hdr {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 18px;
    border-bottom: 1px solid var(--u-line);
    background: var(--u-card);
    flex-shrink: 0;
}
.gm-chat-hdr-av {
    width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
    background: var(--u-brand); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px;
}
.gm-chat-hdr-info { flex: 1; min-width: 0; }
.gm-chat-hdr-name { font-weight: 700; font-size: 13px; color: var(--u-text); }
.gm-chat-hdr-status { font-size: 11px; color: var(--u-muted); display: flex; align-items: center; gap: 4px; margin-top: 2px; }

/* Search bar */
.gm-search-bar {
    padding: 8px 14px;
    border-bottom: 1px solid var(--u-line);
    background: var(--u-bg);
    display: flex; gap: 6px;
    flex-shrink: 0;
}
.gm-search-bar input {
    flex: 1; border: 1px solid var(--u-line); border-radius: 20px;
    padding: 5px 14px; font-size: 12px;
    background: var(--u-card); color: var(--u-text); outline: none;
    transition: border-color .15s;
}
.gm-search-bar input:focus { border-color: var(--u-brand); }

/* Message area */
.gm-body {
    flex: 1; overflow-y: auto;
    padding: 16px 18px;
    display: flex; flex-direction: column; gap: 8px;
    background: var(--u-bg);
}
.gm-date-sep {
    text-align: center; font-size: 11px; color: var(--u-muted);
    margin: 8px 0; position: relative;
}
.gm-date-sep::before, .gm-date-sep::after {
    content: ''; position: absolute; top: 50%; width: calc(50% - 60px);
    height: 1px; background: var(--u-line);
}
.gm-date-sep::before { left: 0; }
.gm-date-sep::after  { right: 0; }

/* Bubbles */
.brow { display: flex; align-items: flex-end; gap: 6px; }
.brow.mine   { flex-direction: row-reverse; }
.brow.theirs .brow-av {
    width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg,var(--u-brand),#7c3aed);
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 700; color: #fff;
    margin-bottom: 2px;
}
.brow.mine .brow-av { display: none; }
.bbl-wrap { display: flex; flex-direction: column; max-width: min(560px,80%); }
.brow.mine .bbl-wrap { align-items: flex-end; }
.brow.theirs .bbl-wrap { align-items: flex-start; }
.bbl {
    padding: 9px 13px; font-size: 13.5px; line-height: 1.55;
    word-break: break-word; overflow-wrap: break-word;
}
.brow.mine .bbl {
    background: var(--u-brand); color: #fff;
    border-radius: 16px 4px 16px 16px;
    box-shadow: 0 1px 3px rgba(37,99,235,.25);
}
.brow.theirs .bbl {
    background: var(--u-card); color: var(--u-text);
    border: 1px solid var(--u-line);
    border-radius: 4px 16px 16px 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.bbl-meta {
    font-size: 10.5px; color: var(--u-muted); margin-top: 3px;
    display: flex; align-items: center; gap: 4px;
}
.brow.mine .bbl-meta { flex-direction: row-reverse; }
.bbl-quick-tag {
    font-size: 10px; font-weight: 600; padding: 1px 7px; border-radius: 999px;
    background: rgba(255,255,255,.25); color: #fff; margin-bottom: 4px; display: inline-block;
}
.brow.theirs .bbl-quick-tag { background: #fef9c3; color: #713f12; }

/* Footer */
.gm-foot {
    flex-shrink: 0; border-top: 1px solid var(--u-line);
    padding: 10px 14px 10px;
    background: var(--u-card);
}
@media(max-width:600px){
    .gm-foot { padding: 10px 12px calc(80px + env(safe-area-inset-bottom, 0px)); }
}
.gm-foot-row { display: flex; align-items: flex-end; gap: 8px; }
.gm-foot-row textarea {
    flex: 1; border: 1.5px solid var(--u-line); border-radius: 22px;
    padding: 9px 16px; font-size: 13.5px; font-family: inherit;
    resize: none; overflow: hidden; line-height: 1.5;
    min-height: 42px; max-height: 110px;
    background: var(--u-bg); color: var(--u-text);
    transition: border-color .15s;
}
.gm-foot-row textarea:focus { outline: none; border-color: var(--u-brand); }
.gm-foot-row textarea::placeholder { color: #9ca3af; opacity: 1; }
/* Messages sayfasındayken floating chat FAB'ı gizle — zaten mesaj sayfasındayız */
.gchat-fab, .gchat-panel { display: none !important; }
.gm-foot-extras {
    display: flex; align-items: center; gap: 6px; margin-top: 7px; padding: 0 4px;
}
.gm-foot-extras select {
    border: 1px solid var(--u-line); border-radius: 7px;
    padding: 4px 8px; font-size: 11px; font-family: inherit;
    background: var(--u-bg); color: var(--u-muted);
}
.gm-dep-label { display: inline-flex; align-items: center; gap: 5px; }
.gm-dep-label-text { font-size: 11px; color: var(--u-muted); font-weight: 600; }
.gm-ql-label { display: flex; align-items: center; gap: 4px; font-size: 11px; cursor: pointer; color: var(--u-muted); }
.gm-attach-name { font-size: 11px; color: var(--u-muted); margin-left: auto; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }

/* Emoji/GIF (reuse existing classes) */
.eg-picker-wrap{position:relative;display:inline-flex;gap:2px;vertical-align:middle;align-items:center}
.eg-picker-btn{background:none !important;border:none !important;cursor:pointer;font-size:17px !important;padding:4px 5px !important;border-radius:6px !important;color:#888;min-height:0 !important;height:30px !important;width:30px !important;display:inline-flex !important;align-items:center !important;justify-content:center !important}
.eg-picker-btn:hover{background:#f0f4ff !important;color:#2563eb}
.eg-emoji-picker,.eg-gif-picker{position:absolute;bottom:calc(100% + 8px);left:0;z-index:9000;background:#fff;border:1px solid var(--u-line);border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,.18);display:none}
.eg-emoji-picker.open,.eg-gif-picker.open{display:block}
.eg-emoji-picker{width:290px;padding:8px}
.eg-emoji-cats{display:flex;gap:3px;margin-bottom:6px;flex-wrap:wrap}
.eg-emoji-cats button{background:none !important;border:none !important;cursor:pointer;font-size:15px !important;padding:2px 5px !important;border-radius:5px !important;opacity:.6;min-height:0 !important;line-height:1.2 !important}
.eg-emoji-cats button.active,.eg-emoji-cats button:hover{background:#eef4fb !important;opacity:1}
.eg-emoji-grid{display:flex;flex-wrap:wrap;gap:1px;max-height:200px;overflow-y:auto}
.eg-emoji-grid button{font-size:20px !important;background:none !important;border:none !important;cursor:pointer;padding:4px !important;border-radius:5px !important;line-height:1 !important;min-height:0 !important;height:34px !important;width:34px !important}
.eg-emoji-grid button:hover{background:#f0f4ff !important}
.eg-gif-picker{width:290px}
.eg-gif-search{padding:7px 8px;border-bottom:1px solid var(--u-line)}
.eg-gif-search input{width:100%;padding:6px 9px !important;border:1px solid var(--u-line);border-radius:6px;font-size:12px;box-sizing:border-box;min-height:0 !important}
.eg-gif-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px;padding:6px;max-height:230px;overflow-y:auto}
.eg-gif-grid img{width:100%;height:75px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid transparent}
.eg-gif-grid img:hover{border-color:var(--u-brand)}
.hub-gif-loading{text-align:center;padding:16px;color:var(--u-muted);font-size:12px}

/* ════════════════════════════════════════
   MINIMALİST OVERRIDES
════════════════════════════════════════ */
.jm-minimalist .gm-layout {
    box-shadow: none;
    border-radius: 8px;
}
.jm-minimalist .gm-advisor-card {
    border-bottom: 1px solid rgba(0,0,0,.10);
}
.jm-minimalist .gm-status-dot.online  { background: var(--u-ok, #16a34a); }

.jm-minimalist .gm-chat-hdr-av {
    background: var(--u-text, #111);
}

.jm-minimalist .brow.theirs .brow-av {
    background: var(--u-line, #e5e7eb);
    color: var(--u-text, #111);
}
.jm-minimalist .brow.mine .bbl {
    box-shadow: none;
}
.jm-minimalist .brow.theirs .bbl {
    box-shadow: none;
}

.jm-minimalist .eg-picker-btn:hover { background: var(--u-line) !important; color: var(--u-text); }
.jm-minimalist .eg-emoji-picker,
.jm-minimalist .eg-gif-picker { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
</style>
@endpush

@section('content')
@php
    $dueAt      = $thread->next_response_due_at;
    $slaDelayed = $dueAt && now()->greaterThan($dueAt);
    $advInitials = $advisor
        ? strtoupper(substr(trim((string)($advisor->name ?? '??')), 0, 2))
        : '??';

    // Gerçek presence verisi
    $advPres = $advisor ? \App\Services\PresenceService::getPresence($advisor) : null;
    $advPresColors = ['online'=>'#16a34a','away'=>'#d97706','busy'=>'#dc2626','offline'=>'#9ca3af'];
    $advPresColor  = $advPresColors[$advPres['status'] ?? 'offline'] ?? '#9ca3af';
    $advPresLabel  = $advPres['label'] ?? 'Çevrimdışı';
    $isOnline = ($advPres['status'] ?? 'offline') === 'online';
@endphp

<div class="gm-layout">

    {{-- ══ LEFT SIDEBAR ══ --}}
    <aside class="gm-sidebar">

        {{-- Advisor Card --}}
        <div class="gm-advisor-card">
            <div class="gm-advisor-av" style="position:relative;">{{ $advInitials }}
                @if($advisor)
                <span style="position:absolute;bottom:1px;right:1px;width:11px;height:11px;border-radius:50%;border:2px solid var(--u-card,#fff);background:{{ $advPresColor }};"></span>
                @endif
            </div>
            <div>
                <div class="gm-advisor-name">{{ $advisor?->name ?? 'Danışman Atanmadı' }}</div>
                <div class="gm-advisor-role">Kıdemli Danışman</div>
                <div style="font-size:var(--tx-xs);margin-top:5px;opacity:.85;">
                    <span class="gm-status-dot {{ $isOnline ? 'online' : 'offline' }}"></span>
                    {{ $advPresLabel }}
                    @if(!empty($advPres['away_until_fmt']))<span style="font-size:10px;"> · Dönüş: {{ $advPres['away_until_fmt'] }}</span>@endif
                </div>
            </div>
        </div>

        {{-- Conversation Info --}}
        <div class="gm-sidebar-section" style="margin-top:12px;">
            <div class="gm-sidebar-label">Sohbet Bilgisi</div>
            <div class="gm-info-row">
                <span class="gm-info-label">Durum</span>
                <span class="badge {{ $thread->status === 'open' ? 'ok' : 'pending' }}" style="font-size:var(--tx-xs);">
                    {{ $thread->status === 'open' ? 'Açık' : ucfirst((string)$thread->status) }}
                </span>
            </div>
            <div class="gm-info-row">
                <span class="gm-info-label">Departman</span>
                <span class="gm-info-val" style="font-size:var(--tx-xs);">{{ ucfirst($thread->department ?: 'Danışmanlık') }}</span>
            </div>
            @if($dueAt)
            <div class="gm-info-row">
                <span class="gm-info-label">SLA</span>
                <span class="badge {{ $slaDelayed ? 'danger' : 'info' }}" style="font-size:var(--tx-xs);">
                    {{ $slaDelayed ? 'Gecikti' : 'Bekliyor' }}
                </span>
            </div>
            @endif
            <div class="gm-info-row">
                <span class="gm-info-label">Mesaj Sayısı</span>
                <span class="gm-info-val" style="font-size:var(--tx-xs);">{{ $messages->count() }}</span>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="gm-sidebar-section" style="margin-top:14px;padding-bottom:14px;">
            <div class="gm-sidebar-label">Hızlı İşlemler</div>
            <a href="{{ route('guest.registration.documents') }}" class="gm-qa-link">
                <div class="gm-qa-icon" style="background:rgba(37,99,235,.1);">📂</div>
                Belgelerim
            </a>
            <a href="{{ route('guest.tickets') }}" class="gm-qa-link">
                <div class="gm-qa-icon" style="background:rgba(217,119,6,.1);">🎫</div>
                Ticket Aç
            </a>
            <a href="{{ route('guest.cost-calculator') }}" class="gm-qa-link">
                <div class="gm-qa-icon" style="background:rgba(22,163,74,.1);">💰</div>
                Maliyet Hesapla
            </a>
            <a href="{{ route('guest.ai-assistant') }}" class="gm-qa-link">
                <div class="gm-qa-icon" style="background:rgba(124,58,237,.1);">🤖</div>
                AI Asistan
            </a>
        </div>

    </aside>

    {{-- ══ RIGHT CHAT ══ --}}
    <div class="gm-chat">

        {{-- Chat Header --}}
        <div class="gm-chat-hdr">
            <div class="gm-chat-hdr-av" style="position:relative;">{{ $advInitials }}
                @if($advisor)<span style="position:absolute;bottom:1px;right:1px;width:11px;height:11px;border-radius:50%;border:2px solid var(--u-card,#fff);background:{{ $advPresColor }};"></span>@endif
            </div>
            <div class="gm-chat-hdr-info">
                <div class="gm-chat-hdr-name">{{ $advisor?->name ?? 'Danışman Atanmadı' }}</div>
                <div class="gm-chat-hdr-status">
                    <span class="gm-status-dot {{ $isOnline ? 'online' : 'offline' }}" style="width:7px;height:7px;"></span>
                    {{ $advPresLabel }}
                    @if($advisor?->email) · {{ $advisor->email }} @endif
                </div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
                <a href="{{ route('guest.tickets') }}" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;">🎫 Ticket Aç</a>
            </div>
        </div>

        {{-- Search --}}
        <form method="get" action="{{ route('guest.messages') }}" class="gm-search-bar">
            <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Mesajlarda ara…">
            <button class="btn alt" type="submit" style="padding:5px 12px;font-size:var(--tx-xs);border-radius:20px;">Ara</button>
        </form>

        {{-- Messages --}}
        <div class="gm-body" id="msgBody">
            @forelse($messages as $m)
                @php $mine = (string)$m->sender_role === 'guest'; @endphp
                <div class="brow {{ $mine ? 'mine' : 'theirs' }}">
                    @if(!$mine)
                        <div class="brow-av">{{ $advInitials }}</div>
                    @endif
                    <div class="bbl-wrap">
                        @if(!empty($m->is_quick_request))
                            <span class="bbl-quick-tag">⚡ Öncelikli</span>
                        @endif
                        <div class="bbl">
                            @if(!empty($m->message))
                                @if(str_starts_with($m->message, '[gif]:'))
                                    <img src="{{ e(substr($m->message, 6)) }}" alt="GIF" loading="lazy"
                                         style="max-width:100%;border-radius:8px;display:block;">
                                @else
                                    {!! nl2br(e(trim($m->message))) !!}
                                @endif
                            @endif
                            @if(!empty($m->attachment_storage_path))
                                <div style="margin-top:6px;font-size:var(--tx-xs);">
                                    📎 <a href="{{ route('dm.attachment.download', $m->id) }}"
                                         style="color:inherit;opacity:.85;text-decoration:underline;">
                                        {{ $m->attachment_original_name ?: 'Dosyayı indir' }}
                                    </a>
                                </div>
                            @endif
                        </div>
                        <div class="bbl-meta">
                            <span>{{ $m->created_at ? \Carbon\Carbon::parse($m->created_at)->format('d.m H:i') : '-' }}</span>
                            @if($mine)
                                <span style="color:{{ !empty($m->is_read_by_participant) ? 'var(--u-ok)' : 'rgba(255,255,255,.5)' }}">
                                    {{ !empty($m->is_read_by_participant) ? '✓✓' : '✓' }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--u-muted);padding:40px 20px;text-align:center;">
                    <div style="width:64px;height:64px;border-radius:50%;background:var(--u-line);display:flex;align-items:center;justify-content:center;font-size:var(--tx-2xl);margin-bottom:14px;">💬</div>
                    <div style="font-size:var(--tx-base);font-weight:600;color:var(--u-text);margin-bottom:6px;">Henüz mesaj yok</div>
                    <div style="font-size:var(--tx-sm);max-width:240px;line-height:1.5;">Danışmanınıza ilk mesajı gönderin — genellikle birkaç saat içinde yanıt verilir.</div>
                </div>
            @endforelse
        </div>

        {{-- Typing indicator --}}
        <div id="gmTypingIndicator" style="display:none;padding:4px 18px 0;font-size:var(--tx-xs);color:var(--u-muted);font-style:italic;">
            <span style="display:inline-flex;align-items:center;gap:5px;">
                <span style="display:inline-flex;gap:3px;">
                    <span style="width:5px;height:5px;border-radius:50%;background:var(--u-muted);animation:gmDot 1.2s ease-in-out infinite;"></span>
                    <span style="width:5px;height:5px;border-radius:50%;background:var(--u-muted);animation:gmDot 1.2s ease-in-out .3s infinite;"></span>
                    <span style="width:5px;height:5px;border-radius:50%;background:var(--u-muted);animation:gmDot 1.2s ease-in-out .6s infinite;"></span>
                </span>
                Danışmanınız yazıyor...
            </span>
        </div>
        <style>@keyframes gmDot{0%,80%,100%{opacity:.2;transform:scale(.7)}40%{opacity:1;transform:scale(1)}}</style>

        {{-- Send Form --}}
        <form method="post" action="{{ route('guest.messages.send') }}" enctype="multipart/form-data" class="gm-foot">
            @csrf
            <div class="gm-foot-row">
                <div class="eg-picker-wrap">
                    <button type="button" class="eg-picker-btn" onclick="egTogglePicker('emoji','guestMsgBody')" title="Emoji">😊</button>
                    <div class="eg-emoji-picker" id="egEmojiPicker_guestMsgBody">
                        <div class="eg-emoji-cats" id="egEmojiCats_guestMsgBody"></div>
                        <div class="eg-emoji-grid" id="egEmojiGrid_guestMsgBody"></div>
                    </div>
                </div>
                <textarea id="guestMsgBody" name="message" rows="1"
                          placeholder="Mesajınızı yazın…"
                          oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,110)+'px'"
                          onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit()}"
                >{{ old('message') }}</textarea>
                <label class="btn alt" for="msgAttachment" style="cursor:pointer;padding:8px 10px;flex-shrink:0;border-radius:50%;" title="Dosya ekle">📎</label>
                <input type="file" name="attachment" id="msgAttachment" style="display:none;"
                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                       onchange="document.getElementById('msgAttachName').textContent=this.files[0]?.name||''">
                <button class="btn ok" type="submit" style="flex-shrink:0;padding:9px 20px;border-radius:20px;">Gönder</button>
            </div>
            <div class="gm-foot-extras">
                <label class="gm-dep-label">
                    <span class="gm-dep-label-text">Konu:</span>
                    <select name="department">
                        @php $dep = old('department', (string)($thread->department ?: 'advisory')); @endphp
                        <option value="advisory" @selected($dep==='advisory')>Danışmanlık</option>
                        <option value="system"   @selected($dep==='system')>Sistem / Teknik</option>
                    </select>
                </label>
                <label class="gm-ql-label">
                    <input type="checkbox" name="quick_request" value="1" @checked(old('quick_request'))>
                    ⚡ Öncelikli
                </label>
                <span id="msgAttachName" class="gm-attach-name"></span>
            </div>
        </form>

    </div>{{-- /gm-chat --}}
</div>{{-- /gm-layout --}}
@endsection

@push('scripts')
<script>
(function(){
    var _orig=window.__designToggle;
    window.__designToggle=function(){
        if(_orig)_orig.apply(this,arguments);
        setTimeout(function(){
            document.documentElement.classList.toggle('jm-minimalist',localStorage.getItem('mentorde_design')==='minimalist');
        },50);
    };
})();
</script>
<script>
window.__gm = {
    pollUrl:     '{{ route("guest.messages.poll") }}',
    typingUrl:   '{{ route("guest.messages.typing") }}',
    lastId:      {{ $messages->last()?->id ?? 0 }},
    advInitials: '{{ addslashes($advInitials) }}'
};
</script>
<script defer src="{{ Vite::asset('resources/js/emoji-gif-picker.js') }}"></script>
<script defer src="{{ Vite::asset('resources/js/guest-messages.js') }}"></script>
<script>
(function(){
    var b = document.getElementById('msgBody');
    if (b) b.scrollTop = b.scrollHeight;
})();
</script>
@endpush

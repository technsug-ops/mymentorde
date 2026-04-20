@extends('student.layouts.app')

@section('title', 'Destek Talepleri')
@section('page_title', 'Destek Talepleri')

@push('head')
<style>
/* ══════ Hero (Option B) ══════ */
.tkt-hero {
    color:#fff; border-radius:14px; margin-bottom:16px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.1); position:relative;
    background:#4c1d95 url('https://images.unsplash.com/photo-1552664730-d307ca884978?w=1400&q=80') center/cover;
}
.tkt-hero::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, rgba(76,29,149,.92) 0%, rgba(124,58,237,.85) 100%);
}
.tkt-hero-body { position:relative; display:flex; align-items:center; gap:20px; padding:22px 26px; }
.tkt-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:7px; }
.tkt-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.82; }
.tkt-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.tkt-hero-title { font-size:24px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.3px; }
.tkt-hero-sub { font-size:12.5px; opacity:.88; line-height:1.5; max-width:560px; }
.tkt-hero-stats { display:flex; gap:7px; flex-wrap:wrap; margin-top:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.tkt-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.tkt-hero-icon { font-size:50px; line-height:1; flex-shrink:0; opacity:.88; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }
.tkt-hero-actions { display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; }
.tkt-hero-btn {
    display:inline-flex; align-items:center; gap:5px;
    padding:7px 14px; border-radius:20px;
    background:rgba(255,255,255,.96); color:#7c3aed;
    font-size:12px; font-weight:700; text-decoration:none;
    box-shadow:0 2px 8px rgba(0,0,0,.12);
    transition:transform .12s;
}
.tkt-hero-btn:hover { transform:translateY(-1px); text-decoration:none; color:#7c3aed; }

@media (max-width:640px){
    .tkt-hero-body { gap:14px; padding:18px; align-items:flex-start; }
    .tkt-hero-title { font-size:20px; }
    .tkt-hero-sub { font-size:12px; }
    .tkt-hero-icon { font-size:36px; }
}

/* ══════ Layout ══════ */
.tkt-layout { display:grid; grid-template-columns:360px 1fr; gap:16px; align-items:start; }
@media(max-width:900px){ .tkt-layout { grid-template-columns:1fr; } }

/* ══════ Form card ══════ */
.tkt-form-card {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:14px; overflow:hidden; position:sticky; top:86px;
    box-shadow:0 4px 14px rgba(0,0,0,.05);
}
.tkt-form-head {
    padding:16px 20px;
    background:linear-gradient(135deg, #7c3aed, #6d28d9);
    position:relative; overflow:hidden;
}
.tkt-form-head::before {
    content:'🎫'; position:absolute; top:-10px; right:-10px;
    font-size:72px; opacity:.14; pointer-events:none;
}
.tkt-form-head-title {
    font-size:15px; font-weight:800; color:#fff;
    display:flex; align-items:center; gap:7px;
    position:relative;
}
.tkt-form-head-sub { font-size:11.5px; color:rgba(255,255,255,.82); margin-top:3px; position:relative; }
.tkt-form-body { padding:18px 20px; }

.tkt-field-row { margin-bottom:12px; }
.tkt-field-label {
    display:block; font-size:11px; font-weight:700;
    color:var(--u-muted); letter-spacing:.4px;
    text-transform:uppercase; margin-bottom:5px;
}
.tkt-field {
    width:100%; box-sizing:border-box;
    padding:10px 12px; border:1.5px solid var(--u-line); border-radius:9px;
    background:var(--u-card); color:var(--u-text);
    font-size:13px; font-family:inherit;
    transition:border-color .15s, box-shadow .15s;
}
.tkt-field:focus {
    outline:none; border-color:#7c3aed;
    box-shadow:0 0 0 3px rgba(124,58,237,.12);
}
.tkt-field:hover:not(:focus) { border-color:color-mix(in srgb, #7c3aed 30%, var(--u-line)); }
.tkt-field-textarea { min-height:100px; resize:vertical; }
.tkt-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:8px; }

.tkt-submit-row { display:flex; gap:8px; align-items:center; margin-top:4px; }
.tkt-submit-btn {
    padding:10px 22px; border-radius:22px;
    background:linear-gradient(135deg, #7c3aed, #a78bfa);
    color:#fff; border:none;
    font-size:13px; font-weight:700; cursor:pointer;
    display:inline-flex; align-items:center; gap:5px;
    box-shadow:0 4px 14px rgba(124,58,237,.3);
    transition:transform .15s, box-shadow .15s;
}
.tkt-submit-btn:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(124,58,237,.4); }

/* ══════ Guide card ══════ */
.tkt-guide {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:12px; padding:14px 16px; margin-top:12px;
}
.tkt-guide-title {
    font-size:13px; font-weight:700; color:var(--u-text);
    margin-bottom:10px; display:flex; align-items:center; gap:8px;
}
.tkt-guide-title::before {
    content:''; display:inline-block; width:3px; height:14px;
    background:#7c3aed; border-radius:2px;
}
.tkt-guide-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:4px; }
.tkt-guide-item {
    display:flex; gap:9px; align-items:flex-start;
    padding:7px 8px; border-radius:8px;
    font-size:12px; color:var(--u-text); line-height:1.5;
    transition:background .12s;
}
.tkt-guide-item:hover { background:color-mix(in srgb, #7c3aed 5%, transparent); }
.tkt-guide-num {
    flex-shrink:0; width:20px; height:20px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:10px; font-weight:800;
    color:#7c3aed;
    background:color-mix(in srgb, #7c3aed 12%, #fff);
    border:1.5px solid color-mix(in srgb, #7c3aed 28%, transparent);
}

/* ══════ List card ══════ */
.tkt-list-card {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:14px; overflow:hidden;
    box-shadow:0 4px 14px rgba(0,0,0,.05);
}
.tkt-list-head {
    padding:14px 18px; border-bottom:1px solid var(--u-line);
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
}
.tkt-list-title {
    font-size:14px; font-weight:800; color:var(--u-text);
    display:inline-flex; align-items:center; gap:7px;
}
.tkt-list-title::before {
    content:''; display:inline-block; width:3px; height:15px;
    background:#7c3aed; border-radius:2px;
}
.tkt-list-count {
    font-size:10.5px; font-weight:700;
    color:#7c3aed; background:color-mix(in srgb, #7c3aed 10%, transparent);
    padding:2px 9px; border-radius:12px;
}
.tkt-filter-btn {
    padding:5px 12px; border:1.5px solid var(--u-line); border-radius:18px;
    background:var(--u-card); color:var(--u-muted);
    font-size:11.5px; font-weight:600; cursor:pointer;
    transition:all .15s;
    display:inline-flex; align-items:center; gap:4px;
}
.tkt-filter-btn:hover:not(.active) {
    border-color:#7c3aed; color:#7c3aed;
    background:color-mix(in srgb, #7c3aed 6%, var(--u-card));
}
.tkt-filter-btn.active {
    background:#7c3aed; border-color:#7c3aed; color:#fff;
    box-shadow:0 2px 8px rgba(124,58,237,.3);
}

/* ══════ Ticket item ══════ */
.tkt-item {
    padding:16px 18px; position:relative;
    border-bottom:1px solid var(--u-line);
    border-left:3px solid transparent;
    transition:background .12s, border-left-color .12s;
}
.tkt-item:last-child { border-bottom:none; }
.tkt-item[data-hidden="1"] { display:none; }
.tkt-item[data-status="open"]             { border-left-color:#22c55e; }
.tkt-item[data-status="in_progress"]      { border-left-color:#3b82f6; }
.tkt-item[data-status="waiting_response"] { border-left-color:#f59e0b; }
.tkt-item[data-status="closed"]           { border-left-color:var(--u-line); }
.tkt-item[data-priority="urgent"]         { background:color-mix(in srgb, #dc2626 2.5%, transparent); }

.tkt-item-head {
    display:flex; align-items:center; gap:10px;
    margin-bottom:8px; flex-wrap:wrap;
}
.tkt-item-id {
    font-size:10.5px; font-weight:800;
    color:var(--u-muted);
    background:var(--u-bg);
    padding:2px 8px; border-radius:6px;
    flex-shrink:0; letter-spacing:.4px;
}
.tkt-item-subject {
    font-size:14px; font-weight:800; color:var(--u-text);
    flex:1; min-width:140px; line-height:1.3;
}
.tkt-item-status {
    padding:3px 10px; border-radius:12px;
    font-size:10.5px; font-weight:700; flex-shrink:0;
    letter-spacing:.3px;
}
.tkt-item-status.open             { background:color-mix(in srgb, #22c55e 12%, transparent); color:#15803d; }
.tkt-item-status.in_progress      { background:color-mix(in srgb, #3b82f6 12%, transparent); color:#1d4ed8; }
.tkt-item-status.waiting_response { background:color-mix(in srgb, #f59e0b 12%, transparent); color:#b45309; }
.tkt-item-status.closed           { background:var(--u-bg); color:var(--u-muted); }

.tkt-item-meta {
    display:flex; gap:6px; flex-wrap:wrap; align-items:center;
    margin-bottom:10px;
}
.tkt-chip {
    padding:2px 8px; border-radius:10px;
    font-size:10px; font-weight:700; letter-spacing:.3px;
}
.tkt-chip.dept  { background:color-mix(in srgb, #7c3aed 10%, transparent); color:#7c3aed; }
.tkt-chip.pri-urgent { background:color-mix(in srgb, #dc2626 12%, transparent); color:#dc2626; }
.tkt-chip.pri-high   { background:color-mix(in srgb, #f59e0b 12%, transparent); color:#b45309; }
.tkt-chip.pri-normal { background:var(--u-bg); color:var(--u-muted); }
.tkt-chip.pri-low    { background:var(--u-bg); color:var(--u-muted); }
.tkt-item-time { font-size:10.5px; color:var(--u-muted); margin-left:auto; flex-shrink:0; }

.tkt-item-body {
    font-size:13px; color:var(--u-text); line-height:1.55;
    padding:11px 14px; background:var(--u-bg);
    border:1px solid var(--u-line); border-radius:10px;
    margin-bottom:10px;
}

/* Reply bubbles */
.tkt-reply {
    padding:10px 13px; border-radius:12px; margin-bottom:6px;
    border:1px solid var(--u-line);
    max-width:85%;
}
.tkt-reply.from-student {
    background:color-mix(in srgb, #7c3aed 8%, var(--u-card));
    border-color:color-mix(in srgb, #7c3aed 22%, transparent);
    margin-left:auto;
}
.tkt-reply.from-staff {
    background:var(--u-bg);
    margin-right:auto;
}
.tkt-reply-meta {
    font-size:10.5px; color:var(--u-muted); margin-bottom:4px;
    display:flex; align-items:center; gap:5px; flex-wrap:wrap;
}
.tkt-reply-meta strong { color:var(--u-text); font-weight:700; }
.tkt-reply-text { font-size:12.5px; color:var(--u-text); line-height:1.55; }

/* Reply form */
.tkt-reply-form { margin-top:10px; padding-top:10px; border-top:1px dashed var(--u-line); }
.tkt-reply-textarea {
    width:100%; box-sizing:border-box;
    padding:10px 12px; border:1.5px solid var(--u-line); border-radius:9px;
    background:var(--u-card); color:var(--u-text);
    font-size:13px; font-family:inherit;
    min-height:68px; resize:vertical;
    transition:border-color .15s, box-shadow .15s;
    margin-bottom:8px;
}
.tkt-reply-textarea:focus {
    outline:none; border-color:#7c3aed;
    box-shadow:0 0 0 3px rgba(124,58,237,.12);
}
.tkt-reply-actions { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
.tkt-reply-btn {
    padding:7px 14px; border-radius:18px;
    background:#7c3aed; color:#fff; border:none;
    font-size:11.5px; font-weight:700; cursor:pointer;
    display:inline-flex; align-items:center; gap:4px;
    transition:transform .15s, box-shadow .15s;
}
.tkt-reply-btn:hover { transform:translateY(-1px); box-shadow:0 3px 10px rgba(124,58,237,.3); }
.tkt-close-btn {
    padding:7px 14px; border:1px solid #fca5a5; border-radius:18px;
    background:color-mix(in srgb, #dc2626 5%, #fff);
    color:#dc2626; font-size:11.5px; font-weight:700;
    cursor:pointer;
    transition:all .15s;
}
.tkt-close-btn:hover { background:color-mix(in srgb, #dc2626 10%, #fff); }
.tkt-reopen-btn {
    padding:7px 14px; border:1.5px solid var(--u-line); border-radius:18px;
    background:var(--u-card); color:var(--u-muted);
    font-size:11.5px; font-weight:700; cursor:pointer;
    transition:all .15s;
}
.tkt-reopen-btn:hover { border-color:#7c3aed; color:#7c3aed; }

/* Empty */
.tkt-empty {
    padding:56px 20px; text-align:center;
    color:var(--u-muted);
}
.tkt-empty-icon { font-size:48px; opacity:.5; margin-bottom:12px; }
.tkt-empty-title { font-size:14px; font-weight:700; color:var(--u-text); margin-bottom:4px; }
.tkt-empty-sub { font-size:12px; line-height:1.5; }

/* Emoji picker (keep functional, simpler) */
.eg-picker-wrap { position:relative; display:inline-block; }
.eg-picker-btn {
    background:var(--u-card) !important; border:1px solid var(--u-line) !important;
    cursor:pointer; font-size:15px !important;
    padding:0 !important; border-radius:50% !important;
    line-height:1 !important; color:var(--u-muted);
    min-height:0 !important; height:30px !important; width:30px !important;
    display:inline-flex !important; align-items:center !important; justify-content:center !important;
    transition:border-color .12s;
}
.eg-picker-btn:hover { border-color:#7c3aed !important; }
.eg-emoji-picker {
    display:none; position:absolute; bottom:calc(100% + 8px); left:0; z-index:9000;
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,.14); width:280px;
}
.eg-emoji-picker.open { display:block; }
.eg-emoji-cats { display:flex; gap:2px; padding:6px; border-bottom:1px solid var(--u-line); flex-wrap:wrap; }
.eg-emoji-cats button {
    background:none !important; border:none !important; font-size:18px !important;
    padding:3px !important; border-radius:5px !important; cursor:pointer;
    min-height:0 !important; line-height:1.2 !important;
}
.eg-emoji-cats button.active, .eg-emoji-cats button:hover { background:var(--u-bg) !important; }
.eg-emoji-grid {
    display:grid; grid-template-columns:repeat(7,1fr); gap:1px;
    padding:6px; max-height:160px; overflow-y:auto;
}
.eg-emoji-grid button {
    font-size:20px !important; background:none !important; border:none !important;
    padding:2px !important; border-radius:5px !important; cursor:pointer;
    text-align:center; min-height:0 !important; height:34px !important; width:34px !important;
}
.eg-emoji-grid button:hover { background:var(--u-bg) !important; }

@media (max-width:640px){
    .tkt-form-card { position:static; }
    .tkt-item { padding:14px 14px; }
    .tkt-item-subject { font-size:13px; }
    .tkt-reply { max-width:92%; }
}
</style>
@endpush

@section('content')

@php
    $totalCount = $tickets->count();
    $openCount = $tickets->filter(fn($t) => strtolower((string)$t->status) !== 'closed')->count();
    $closedCount = $totalCount - $openCount;
    $urgentCount = $tickets->filter(fn($t) => in_array(strtolower((string)$t->priority), ['urgent','high']))->count();
@endphp

{{-- ══════ Hero ══════ --}}
<div class="tkt-hero">
    <div class="tkt-hero-body">
        <div class="tkt-hero-main">
            <div class="tkt-hero-label"><span class="tkt-hero-marker"></span>Destek Merkezi</div>
            <h1 class="tkt-hero-title">Destek Talepleri</h1>
            <div class="tkt-hero-sub">Resmi talep, departman yönlendirmesi ve kayıt altına alınan işler burada yürür. Hızlı konular için mesajlaşmayı tercih et.</div>
            <div class="tkt-hero-stats">
                <span class="tkt-hero-stat">🎫 {{ $totalCount }} toplam</span>
                <span class="tkt-hero-stat">🟢 {{ $openCount }} açık</span>
                <span class="tkt-hero-stat">⚪ {{ $closedCount }} kapalı</span>
                @if($urgentCount > 0)<span class="tkt-hero-stat">⚡ {{ $urgentCount }} yüksek öncelikli</span>@endif
            </div>
            <div class="tkt-hero-actions">
                <a href="/student/messages" class="tkt-hero-btn">💬 Mesajlar <span>→</span></a>
            </div>
        </div>
        <div class="tkt-hero-icon">🎫</div>
    </div>
</div>

<div class="tkt-layout">

    {{-- LEFT: Form + Guide --}}
    <div>
        <div class="tkt-form-card">
            <div class="tkt-form-head">
                <div class="tkt-form-head-title">Yeni Destek Talebi</div>
                <div class="tkt-form-head-sub">Resmi işlemler için ticket aç</div>
            </div>
            <div class="tkt-form-body">
                <form method="post" action="{{ route('student.tickets.store') }}">
                    @csrf
                    <div class="tkt-field-row">
                        <label class="tkt-field-label">Konu *</label>
                        <input class="tkt-field" name="subject" placeholder="Kısa ve açıklayıcı bir konu yaz..."
                               value="{{ old('subject', $ticketPrefill['subject'] ?? '') }}">
                    </div>
                    <div class="tkt-field-row">
                        <div class="tkt-grid2">
                            <div>
                                <label class="tkt-field-label">Öncelik</label>
                                <select class="tkt-field" name="priority">
                                    @php $pfPriority = old('priority', $ticketPrefill['priority'] ?? 'normal'); @endphp
                                    <option value="normal"  @selected($pfPriority==='normal') >Normal</option>
                                    <option value="low"     @selected($pfPriority==='low')    >Düşük</option>
                                    <option value="high"    @selected($pfPriority==='high')   >Yüksek</option>
                                    <option value="urgent"  @selected($pfPriority==='urgent') >Acil</option>
                                </select>
                            </div>
                            <div>
                                <label class="tkt-field-label">Departman</label>
                                <select class="tkt-field" name="department">
                                    @php $pfDept = old('department', $ticketPrefill['department'] ?? 'auto'); @endphp
                                    <option value="auto"       @selected($pfDept==='auto')      >Otomatik</option>
                                    <option value="advisory"   @selected($pfDept==='advisory')  >Danışmanlık</option>
                                    <option value="operations" @selected($pfDept==='operations')>Operasyon</option>
                                    <option value="finance"    @selected($pfDept==='finance')   >Finans</option>
                                    <option value="marketing"  @selected($pfDept==='marketing') >Marketing</option>
                                    <option value="system"     @selected($pfDept==='system')    >Sistem</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="tkt-field-row">
                        <label class="tkt-field-label">Mesaj</label>
                        <textarea class="tkt-field tkt-field-textarea" name="message"
                                  id="sNewTicketMsg" placeholder="Sorununu veya talebini detaylı anlat...">{{ old('message', $ticketPrefill['message'] ?? '') }}</textarea>
                    </div>
                    <input type="hidden" name="return_to" value="/student/tickets">
                    <div class="tkt-submit-row">
                        <button class="tkt-submit-btn" type="submit">Ticket Aç <span>→</span></button>
                        <div class="eg-picker-wrap">
                            <button type="button" class="eg-picker-btn" onclick="egTogglePicker('emoji','sNewTicketMsg')" title="Emoji">😊</button>
                            <div class="eg-emoji-picker" id="egEmojiPicker_sNewTicketMsg">
                                <div class="eg-emoji-cats" id="egEmojiCats_sNewTicketMsg"></div>
                                <div class="eg-emoji-grid" id="egEmojiGrid_sNewTicketMsg"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="tkt-guide">
            <div class="tkt-guide-title">Nasıl Kullanılır?</div>
            <ol class="tkt-guide-list" style="list-style:none;">
                @foreach([
                    'Resmi takip gerektiren işlerde (operasyon, finans, sözleşme vb.) ticket aç.',
                    'Öncelik ve departmanı seç; emin değilsen "Otomatik" bırak.',
                    'Her ticket\'a yanıt yazabilir, kapatabilir veya tekrar açabilirsin.',
                    'Hızlı iletişim için Mesajlar ekranını kullan.',
                ] as $i => $text)
                <li class="tkt-guide-item">
                    <span class="tkt-guide-num">{{ $i+1 }}</span>
                    <span>{{ $text }}</span>
                </li>
                @endforeach
            </ol>
        </div>
    </div>

    {{-- RIGHT: Ticket list --}}
    <div class="tkt-list-card">
        <div class="tkt-list-head">
            <span class="tkt-list-title">Ticket Listesi</span>
            <span class="tkt-list-count">{{ $totalCount }}</span>
            <div style="flex:1;"></div>
            <button class="tkt-filter-btn active" onclick="tktFilter('all',this)">Tümü</button>
            <button class="tkt-filter-btn" onclick="tktFilter('open',this)">Açık ({{ $openCount }})</button>
            <button class="tkt-filter-btn" onclick="tktFilter('closed',this)">Kapalı</button>
            @if($urgentCount > 0)<button class="tkt-filter-btn" onclick="tktFilter('urgent',this)">Acil</button>@endif
        </div>

        @forelse($tickets as $ticket)
        @php
            $st  = strtolower((string)$ticket->status);
            $pri = strtolower((string)$ticket->priority);
            $stLabel = ['open'=>'Açık','closed'=>'Kapalı','in_progress'=>'İşlemde','waiting_response'=>'Yanıt Bekleniyor'][$st] ?? ucfirst($st);
            $priLabel = ['urgent'=>'Acil','high'=>'Yüksek','normal'=>'Normal','low'=>'Düşük'][$pri] ?? ucfirst($pri);
        @endphp
        <div class="tkt-item" data-status="{{ $st }}" data-priority="{{ $pri }}">

            <div class="tkt-item-head">
                <span class="tkt-item-id">#{{ $ticket->id }}</span>
                <span class="tkt-item-subject">{{ $ticket->subject }}</span>
                <span class="tkt-item-status {{ $st }}">{{ $stLabel }}</span>
            </div>

            <div class="tkt-item-meta">
                @if($ticket->department)
                    <span class="tkt-chip dept">{{ ucfirst($ticket->department) }}</span>
                @endif
                <span class="tkt-chip pri-{{ $pri }}">{{ $priLabel }}</span>
                @if($ticket->last_replied_at)
                    <span class="tkt-item-time">{{ $ticket->last_replied_at }}</span>
                @endif
            </div>

            <div class="tkt-item-body">{{ $ticket->message }}</div>

            @foreach($ticket->replies as $reply)
            @php $isStudent = ($reply->author_role === 'student'); @endphp
            <div class="tkt-reply {{ $isStudent ? 'from-student' : 'from-staff' }}">
                <div class="tkt-reply-meta">
                    <strong>{{ $isStudent ? 'Siz' : ucfirst($reply->author_role) }}</strong>
                    @if(!$isStudent && $reply->author_email) · <span>{{ $reply->author_email }}</span>@endif
                    <span style="margin-left:auto;">{{ $reply->created_at }}</span>
                </div>
                <div class="tkt-reply-text">{{ $reply->message }}</div>
            </div>
            @endforeach

            {{-- Reply form --}}
            <div class="tkt-reply-form">
                <form method="post" action="{{ route('student.tickets.reply', $ticket->id) }}">
                    @csrf
                    <textarea class="tkt-reply-textarea" name="message"
                              id="sReply{{ $ticket->id }}" placeholder="Yanıtını yaz..."></textarea>
                    <div class="tkt-reply-actions">
                        <button class="tkt-reply-btn" type="submit">Yanıt Gönder <span>→</span></button>
                        <div class="eg-picker-wrap">
                            <button type="button" class="eg-picker-btn" onclick="egTogglePicker('emoji','sReply{{ $ticket->id }}')" title="Emoji">😊</button>
                            <div class="eg-emoji-picker" id="egEmojiPicker_sReply{{ $ticket->id }}">
                                <div class="eg-emoji-cats" id="egEmojiCats_sReply{{ $ticket->id }}"></div>
                                <div class="eg-emoji-grid" id="egEmojiGrid_sReply{{ $ticket->id }}"></div>
                            </div>
                        </div>
                        <div style="flex:1;"></div>
                        @if($st !== 'closed')
                        <button class="tkt-close-btn" type="submit" formaction="{{ route('student.tickets.close', $ticket->id) }}">Kapat</button>
                        @else
                        <button class="tkt-reopen-btn" type="submit" formaction="{{ route('student.tickets.reopen', $ticket->id) }}">Tekrar Aç</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="tkt-empty">
            <div class="tkt-empty-icon">🎫</div>
            <div class="tkt-empty-title">Henüz ticket açılmadı</div>
            <div class="tkt-empty-sub">Sol taraftaki formdan yeni bir destek talebi oluşturabilirsin.</div>
        </div>
        @endforelse
    </div>

</div>

<script defer src="{{ Vite::asset('resources/js/emoji-gif-picker.js') }}"></script>
<script defer src="{{ Vite::asset('resources/js/student-tickets.js') }}?v={{ @filemtime(public_path('js/student-tickets.js')) ?: time() }}"></script>
<script>
function tktFilter(type, btn) {
    document.querySelectorAll('.tkt-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.tkt-item').forEach(el => {
        const st  = el.dataset.status;
        const pri = el.dataset.priority;
        const hide = type === 'open'   ? st === 'closed'
                   : type === 'closed' ? st !== 'closed'
                   : type === 'urgent' ? (pri !== 'urgent' && pri !== 'high')
                   : false;
        el.dataset.hidden = hide ? '1' : '0';
        el.style.display = hide ? 'none' : '';
    });
}
</script>
@endsection

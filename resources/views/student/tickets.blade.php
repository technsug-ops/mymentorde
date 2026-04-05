@extends('student.layouts.app')

@section('title', 'Destek Talepleri')
@section('page_title', 'Destek Talepleri')

@push('head')
<style>
/* ── tkt-* Tickets scoped ── */

/* Top bar */
.tkt-topbar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: wrap;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 12px 16px; margin-bottom: 16px;
}
.tkt-topbar-desc { font-size: 13px; color: var(--u-muted); }

/* Layout */
.tkt-layout { display: grid; grid-template-columns: 360px 1fr; gap: 16px; align-items: start; }
@media(max-width:900px){ .tkt-layout { grid-template-columns: 1fr; } }

/* Form card */
.tkt-form-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; overflow: hidden; position: sticky; top: 8px;
}
.tkt-form-head {
    padding: 14px 18px;
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
}
.tkt-form-head-title { font-size: 15px; font-weight: 700; color: #fff; }
.tkt-form-head-sub   { font-size: 11px; color: rgba(255,255,255,.7); margin-top: 2px; }
.tkt-form-body { padding: 16px 18px; }

/* Form fields */
.tkt-field {
    width: 100%; box-sizing: border-box;
    padding: 9px 12px; border: 1.5px solid var(--u-line); border-radius: 8px;
    background: var(--u-bg); color: var(--u-text); font-size: 13px;
    font-family: inherit; transition: border-color .15s; margin-bottom: 8px;
}
.tkt-field:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
.tkt-field-textarea { min-height: 96px; resize: vertical; }
.tkt-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }
.tkt-submit-row { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.tkt-submit-btn {
    padding: 9px 18px; background: #7c3aed; color: #fff; border: none;
    border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer;
    transition: opacity .15s;
}
.tkt-submit-btn:hover { opacity: .88; }

/* Guide card */
.tkt-guide {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 14px 16px; margin-top: 12px;
}
.tkt-guide-title { font-size: 13px; font-weight: 700; color: var(--u-text); margin-bottom: 8px; }
.tkt-guide ol { margin: 0; padding-left: 18px; display: flex; flex-direction: column; gap: 5px; }
.tkt-guide li { font-size: 12px; color: var(--u-muted); line-height: 1.5; }

/* Right panel */
.tkt-list-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; overflow: hidden;
}
.tkt-list-head {
    padding: 12px 18px; border-bottom: 1px solid var(--u-line);
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.tkt-list-title { font-size: 14px; font-weight: 700; color: var(--u-text); margin-right: 4px; }
.tkt-filter-btn {
    padding: 4px 12px; border: 1px solid var(--u-line); border-radius: 999px;
    background: var(--u-bg); color: var(--u-muted); font-size: 12px; font-weight: 600;
    cursor: pointer; transition: all .15s;
}
.tkt-filter-btn:hover,
.tkt-filter-btn.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }

/* Ticket item */
.tkt-item {
    border-bottom: 1px solid var(--u-line); padding: 14px 18px;
}
.tkt-item:last-child { border-bottom: none; }
.tkt-item[data-hidden="1"] { display: none; }

.tkt-item-head {
    display: flex; align-items: flex-start; gap: 10px;
    justify-content: space-between; margin-bottom: 6px;
}
.tkt-item-id { font-size: 11px; font-weight: 700; color: var(--u-muted); flex-shrink: 0; }
.tkt-item-subject { font-size: 14px; font-weight: 700; color: var(--u-text); flex: 1; }
.tkt-item-meta {
    display: flex; gap: 5px; flex-wrap: wrap; align-items: center;
    margin-bottom: 8px; font-size: 11px; color: var(--u-muted);
}
.tkt-item-body {
    font-size: 13px; color: var(--u-text); line-height: 1.5;
    padding: 10px 12px; background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 8px; margin-bottom: 8px;
}

/* Reply bubbles */
.tkt-reply {
    padding: 10px 12px; border-radius: 10px; margin-bottom: 6px;
    border: 1px solid var(--u-line);
}
.tkt-reply.from-student {
    background: rgba(124,58,237,.06); border-color: rgba(124,58,237,.18);
    margin-left: 16px;
}
.tkt-reply.from-staff {
    background: var(--u-bg); margin-right: 16px;
}
.tkt-reply-meta { font-size: 11px; color: var(--u-muted); margin-bottom: 4px; }
.tkt-reply-meta strong { color: var(--u-text); }
.tkt-reply-text { font-size: 13px; color: var(--u-text); line-height: 1.5; }

/* Reply form */
.tkt-reply-form { margin-top: 8px; }
.tkt-reply-textarea {
    width: 100%; box-sizing: border-box;
    padding: 9px 12px; border: 1.5px solid var(--u-line); border-radius: 8px;
    background: var(--u-bg); color: var(--u-text); font-size: 13px;
    font-family: inherit; min-height: 72px; resize: vertical;
    transition: border-color .15s; margin-bottom: 6px;
}
.tkt-reply-textarea:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
.tkt-reply-actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.tkt-reply-btn {
    padding: 6px 14px; background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 7px; font-size: 12px; font-weight: 600; color: var(--u-text);
    cursor: pointer; transition: border-color .15s;
}
.tkt-reply-btn:hover { border-color: #7c3aed; color: #7c3aed; }
.tkt-close-btn {
    padding: 6px 14px; border: 1px solid #fca5a5; border-radius: 7px;
    background: #fff1f2; color: #dc2626; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: background .15s;
}
.tkt-close-btn:hover { background: #fee2e2; }
.tkt-reopen-btn {
    padding: 6px 14px; border: 1px solid var(--u-line); border-radius: 7px;
    background: var(--u-bg); color: var(--u-muted); font-size: 12px; font-weight: 600;
    cursor: pointer; transition: border-color .15s;
}
.tkt-reopen-btn:hover { border-color: #7c3aed; color: #7c3aed; }

/* Empty */
.tkt-empty { padding: 36px 18px; text-align: center; color: var(--u-muted); font-size: 13px; }

/* Emoji / GIF picker (keep functional, fix colors) */
.eg-picker-wrap { position: relative; display: inline-block; }
.eg-picker-btn {
    background: none !important; border: none !important; cursor: pointer;
    font-size: 17px !important; padding: 4px 6px !important; border-radius: 6px !important;
    line-height: 1 !important; color: var(--u-muted); min-height: 0 !important;
    height: 32px !important; width: 32px !important;
    display: inline-flex !important; align-items: center !important; justify-content: center !important;
}
.eg-picker-btn:hover { background: var(--u-bg) !important; }
.eg-emoji-picker, .eg-gif-picker {
    display: none; position: absolute; bottom: calc(100% + 8px); left: 0; z-index: 9000;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,.14); width: 280px;
}
.eg-emoji-picker.open, .eg-gif-picker.open { display: block; }
.eg-emoji-cats {
    display: flex; gap: 2px; padding: 6px;
    border-bottom: 1px solid var(--u-line); flex-wrap: wrap;
}
.eg-emoji-cats button {
    background: none !important; border: none !important; font-size: 18px !important;
    padding: 3px !important; border-radius: 5px !important; cursor: pointer;
    min-height: 0 !important; line-height: 1.2 !important;
}
.eg-emoji-cats button.active, .eg-emoji-cats button:hover { background: var(--u-bg) !important; }
.eg-emoji-grid {
    display: grid; grid-template-columns: repeat(7,1fr); gap: 1px;
    padding: 6px; max-height: 160px; overflow-y: auto;
}
.eg-emoji-grid button {
    font-size: 20px !important; background: none !important; border: none !important;
    padding: 2px !important; border-radius: 5px !important; cursor: pointer;
    text-align: center; min-height: 0 !important; height: 34px !important; width: 34px !important;
}
.eg-emoji-grid button:hover { background: var(--u-bg) !important; }
.eg-gif-picker { width: 300px; }
.eg-gif-search { padding: 8px; border-bottom: 1px solid var(--u-line); }
.eg-gif-search input {
    width: 100%; box-sizing: border-box; border: 1px solid var(--u-line);
    border-radius: 6px; padding: 5px 10px; font-size: 13px; min-height: 0 !important;
    background: var(--u-bg); color: var(--u-text);
}
.eg-gif-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 4px;
    padding: 6px; max-height: 180px; overflow-y: auto;
}
.eg-gif-grid img { width: 100%; border-radius: 6px; cursor: pointer; object-fit: cover; aspect-ratio: 16/9; }
.eg-gif-loading { padding: 12px; text-align: center; color: var(--u-muted); font-size: 12px; grid-column: 1/-1; }
</style>
@endpush

@section('content')

{{-- Top bar --}}
<div class="tkt-topbar">
    <div class="tkt-topbar-desc">Resmi talep ve departman yönlendirmesi burada yürür. Hızlı iletişim için Mesajlar'ı kullanın.</div>
    <a href="/student/messages" style="padding:7px 14px;background:#7c3aed;color:#fff;border-radius:8px;font-size:var(--tx-sm);font-weight:700;text-decoration:none;">💬 Mesajlar</a>
</div>

<div class="tkt-layout">

    {{-- LEFT: Form + Guide --}}
    <div>
        <div class="tkt-form-card">
            <div class="tkt-form-head">
                <div class="tkt-form-head-title">🎫 Yeni Destek Talebi</div>
                <div class="tkt-form-head-sub">Resmi işlemler için ticket açın.</div>
            </div>
            <div class="tkt-form-body">
                <form method="post" action="{{ route('student.tickets.store') }}">
                    @csrf
                    <input class="tkt-field" name="subject" placeholder="Konu *"
                           value="{{ old('subject', $ticketPrefill['subject'] ?? '') }}">
                    <div class="tkt-grid2">
                        <select class="tkt-field" name="priority" style="margin-bottom:0;">
                            @php $pfPriority = old('priority', $ticketPrefill['priority'] ?? 'normal'); @endphp
                            <option value="normal"  @selected($pfPriority==='normal') >Normal</option>
                            <option value="low"     @selected($pfPriority==='low')    >Düşük</option>
                            <option value="high"    @selected($pfPriority==='high')   >Yüksek</option>
                            <option value="urgent"  @selected($pfPriority==='urgent') >Acil</option>
                        </select>
                        <select class="tkt-field" name="department" style="margin-bottom:0;">
                            @php $pfDept = old('department', $ticketPrefill['department'] ?? 'auto'); @endphp
                            <option value="auto"       @selected($pfDept==='auto')      >Otomatik</option>
                            <option value="advisory"   @selected($pfDept==='advisory')  >Danışmanlık</option>
                            <option value="operations" @selected($pfDept==='operations')>Operasyon</option>
                            <option value="finance"    @selected($pfDept==='finance')   >Finans</option>
                            <option value="marketing"  @selected($pfDept==='marketing') >Marketing</option>
                            <option value="system"     @selected($pfDept==='system')    >Sistem</option>
                        </select>
                    </div>
                    <textarea class="tkt-field tkt-field-textarea" name="message"
                              id="sNewTicketMsg" placeholder="Mesajınızı yazın...">{{ old('message', $ticketPrefill['message'] ?? '') }}</textarea>
                    <input type="hidden" name="return_to" value="/student/tickets">
                    <div class="tkt-submit-row">
                        <button class="tkt-submit-btn" type="submit">Ticket Aç →</button>
                        <div class="eg-picker-wrap">
                            <button type="button" class="eg-picker-btn" onclick="egTogglePicker('emoji','sNewTicketMsg')" title="Emoji">😊</button>
                            <div class="eg-emoji-picker" id="egEmojiPicker_sNewTicketMsg">
                                <div class="eg-emoji-cats" id="egEmojiCats_sNewTicketMsg"></div>
                                <div class="eg-emoji-grid" id="egEmojiGrid_sNewTicketMsg"></div>
                            </div>
                        </div>
                        <div class="eg-picker-wrap">
                            <button type="button" class="eg-picker-btn" onclick="egTogglePicker('gif','sNewTicketMsg')" title="GIF" style="font-size:var(--tx-xs) !important;font-weight:700 !important;">GIF</button>
                            <div class="eg-gif-picker" id="egGifPicker_sNewTicketMsg">
                                <div class="eg-gif-search"><input type="text" placeholder="🔍 GIF ara..." oninput="egGifSearch(this.value,'sNewTicketMsg')"></div>
                                <div class="eg-gif-grid" id="egGifGrid_sNewTicketMsg"><div class="eg-gif-loading">Yükleniyor...</div></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="tkt-guide">
            <div class="tkt-guide-title">📖 Kullanım Kılavuzu</div>
            <ol>
                <li>Resmi takip gerektiren işlerde (operasyon, finans, sözleşme vb.) ticket aç.</li>
                <li>Öncelik ve departmanı seç; emin değilsen "Otomatik" bırak.</li>
                <li>Her ticket'a yanıt yazabilir, kapatabilir veya tekrar açabilirsin.</li>
                <li>Hızlı iletişim için Mesajlar ekranını kullan.</li>
            </ol>
        </div>
    </div>

    {{-- RIGHT: Ticket list --}}
    <div class="tkt-list-card">
        <div class="tkt-list-head">
            <span class="tkt-list-title">Ticket Listesi</span>
            <span class="badge" style="margin-right:8px;">{{ $tickets->count() }}</span>
            <button class="tkt-filter-btn active" onclick="tktFilter('all',this)">Tümü</button>
            <button class="tkt-filter-btn" onclick="tktFilter('open',this)">Açık</button>
            <button class="tkt-filter-btn" onclick="tktFilter('closed',this)">Kapalı</button>
            <button class="tkt-filter-btn" onclick="tktFilter('urgent',this)">Acil</button>
        </div>

        @forelse($tickets as $ticket)
        @php
            $st    = strtolower((string) $ticket->status);
            $pri   = strtolower((string) $ticket->priority);
            $stCls = match($st) {
                'open'             => 'ok',
                'in_progress'      => 'info',
                'waiting_response' => 'warn',
                'closed'           => '',
                default            => ''
            };
            $priCls = match($pri) {
                'urgent' => 'danger',
                'high'   => 'warn',
                'low'    => '',
                default  => ''
            };
        @endphp
        <div class="tkt-item"
             data-status="{{ $st }}"
             data-priority="{{ $pri }}">

            <div class="tkt-item-head">
                <span class="tkt-item-id">#{{ $ticket->id }}</span>
                <span class="tkt-item-subject">{{ $ticket->subject }}</span>
                <span class="badge {{ $stCls }}" style="flex-shrink:0;">
                    {{ ['open'=>'Açık','closed'=>'Kapalı','in_progress'=>'İşlemde','waiting_response'=>'Yanıt Bekleniyor'][$st] ?? $st }}
                </span>
            </div>

            <div class="tkt-item-meta">
                @if($ticket->department)
                    <span class="badge info">{{ ucfirst($ticket->department) }}</span>
                @endif
                <span class="badge {{ $priCls }}">{{ ucfirst($pri) }} öncelik</span>
                @if($ticket->last_replied_at)
                    <span>Son: {{ $ticket->last_replied_at }}</span>
                @endif
            </div>

            <div class="tkt-item-body">{{ $ticket->message }}</div>

            @foreach($ticket->replies as $reply)
            @php $isStudent = ($reply->author_role === 'student'); @endphp
            <div class="tkt-reply {{ $isStudent ? 'from-student' : 'from-staff' }}">
                <div class="tkt-reply-meta">
                    <strong>{{ $isStudent ? 'Siz' : ucfirst($reply->author_role) }}</strong>
                    @if(!$isStudent && $reply->author_email) · {{ $reply->author_email }}@endif
                    · {{ $reply->created_at }}
                </div>
                <div class="tkt-reply-text">{{ $reply->message }}</div>
            </div>
            @endforeach

            {{-- Reply form --}}
            <div class="tkt-reply-form">
                <form method="post" action="{{ route('student.tickets.reply', $ticket->id) }}">
                    @csrf
                    <textarea class="tkt-reply-textarea" name="message"
                              id="sReply{{ $ticket->id }}" placeholder="Yanıt yaz..."></textarea>
                    <div class="tkt-reply-actions">
                        <button class="tkt-reply-btn" type="submit">Yanıt Gönder</button>
                        <div class="eg-picker-wrap">
                            <button type="button" class="eg-picker-btn" onclick="egTogglePicker('emoji','sReply{{ $ticket->id }}')" title="Emoji">😊</button>
                            <div class="eg-emoji-picker" id="egEmojiPicker_sReply{{ $ticket->id }}">
                                <div class="eg-emoji-cats" id="egEmojiCats_sReply{{ $ticket->id }}"></div>
                                <div class="eg-emoji-grid" id="egEmojiGrid_sReply{{ $ticket->id }}"></div>
                            </div>
                        </div>
                        <div class="eg-picker-wrap">
                            <button type="button" class="eg-picker-btn" onclick="egTogglePicker('gif','sReply{{ $ticket->id }}')" title="GIF" style="font-size:var(--tx-xs) !important;font-weight:700 !important;">GIF</button>
                            <div class="eg-gif-picker" id="egGifPicker_sReply{{ $ticket->id }}">
                                <div class="eg-gif-search"><input type="text" placeholder="🔍 GIF ara..." oninput="egGifSearch(this.value,'sReply{{ $ticket->id }}')"></div>
                                <div class="eg-gif-grid" id="egGifGrid_sReply{{ $ticket->id }}"><div class="eg-gif-loading">Yükleniyor...</div></div>
                            </div>
                        </div>
                        @if($st !== 'closed')
                        <form method="post" action="{{ route('student.tickets.close', $ticket->id) }}" style="margin:0;">
                            @csrf
                            <button class="tkt-close-btn" type="submit">Kapat</button>
                        </form>
                        @else
                        <form method="post" action="{{ route('student.tickets.reopen', $ticket->id) }}" style="margin:0;">
                            @csrf
                            <button class="tkt-reopen-btn" type="submit">Tekrar Aç</button>
                        </form>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="tkt-empty">
            🎫 Henüz ticket yok.<br>
            <span style="font-size:var(--tx-xs);">Sol taraftan yeni bir destek talebi oluşturabilirsin.</span>
        </div>
        @endforelse
    </div>

</div>

<script defer src="{{ Vite::asset('resources/js/emoji-gif-picker.js') }}" defer></script>
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

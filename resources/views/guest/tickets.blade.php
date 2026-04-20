@extends('guest.layouts.app')

@section('title', 'Destek Talepleri')
@section('page_title', 'Destek Talepleri')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* Tickets sayfasında gchat FAB'ı gizle — form zaten var, üst üste binmesin */
.gchat-fab, .gchat-panel { display: none !important; }
/* ── gt-* Guest Tickets v2 ── */

/* KPI Bar */
.gt-kpi-bar { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:640px){ .gt-kpi-bar { grid-template-columns:repeat(2,1fr); } }
.gt-kpi {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:12px; padding:14px 16px;
    display:flex; align-items:center; gap:12px;
}
.gt-kpi-icon { width:38px; height:38px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
.gt-kpi-val   { font-size:22px; font-weight:800; color:var(--u-text); line-height:1; }
.gt-kpi-label { font-size:11px; color:var(--u-muted); margin-top:3px; }

/* 2-column layout */
.gt-layout { display:grid; grid-template-columns:320px 1fr; gap:20px; align-items:start; }
@media(max-width:860px){ .gt-layout { grid-template-columns:1fr; } }
/* Sol ve sağ kolon eşit yükseklik için */
.gt-layout > div:last-child { min-height: 100%; }

/* LEFT PANEL */
.gt-left { display:flex; flex-direction:column; gap:14px; }

/* Channel pills */
.gt-channels { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.gt-channel {
    padding:12px 14px; border-radius:10px; border:2px solid var(--u-line);
    background:var(--u-card); text-decoration:none;
    transition:border-color .15s, background .15s;
}
.gt-channel.active { border-color:var(--u-brand); background:rgba(37,99,235,.05); }
.gt-channel:hover:not(.active) { border-color:var(--u-muted); text-decoration:none; }
.gt-channel-icon { font-size:22px; margin-bottom:6px; }
.gt-channel-name { font-size:12px; font-weight:700; color:var(--u-text); }
.gt-channel-desc { font-size:11px; color:var(--u-muted); margin-top:2px; line-height:1.4; }

/* Form */
.gt-field { margin-bottom:10px; }
.gt-field label { display:block; font-size:12px; font-weight:600; color:var(--u-muted); margin-bottom:5px; }
.gt-field input,
.gt-field select,
.gt-field textarea {
    width:100%; padding:9px 12px;
    border:1.5px solid var(--u-line); border-radius:8px;
    font-size:13px; color:var(--u-text); background:var(--u-card);
    font-family:inherit; box-sizing:border-box;
    transition:border-color .15s, box-shadow .15s;
}
.gt-field input:focus,
.gt-field select:focus,
.gt-field textarea:focus {
    outline:none; border-color:var(--u-brand);
    box-shadow:0 0 0 3px rgba(37,99,235,.08);
}
.gt-row2 { display:grid; grid-template-columns:1fr 1fr; gap:8px; }

/* Ticket list */
.gt-list { display:flex; flex-direction:column; gap:10px; }
.gt-list-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
.gt-list-title { font-size:var(--tx-sm); font-weight:800; color:var(--u-text); }
.gt-list-sub { font-size:var(--tx-xs); font-weight:400; color:var(--u-muted); }
@media(max-width:640px){
    .gt-list-head { flex-direction:column; align-items:flex-start; gap:4px; }
    .gt-list-sub { font-size:11px; line-height:1.4; }
}
.gt-card {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:12px; overflow:hidden;
    transition:box-shadow .15s;
}
.gt-card:hover { box-shadow:0 2px 12px rgba(0,0,0,.07); }
.gt-card.open-ticket { border-left:3px solid var(--u-ok); }
.gt-card.closed-ticket { border-left:3px solid var(--u-muted); opacity:.85; }
.gt-card.urgent-ticket { border-left:3px solid var(--u-danger); }

/* Card header (clickable toggle) */
.gt-card-hdr {
    padding:14px 16px; cursor:pointer;
    display:flex; align-items:flex-start; gap:12px;
    user-select:none;
}
.gt-card-hdr:hover { background:rgba(0,0,0,.02); }
.gt-card-num {
    font-size:11px; font-weight:700; color:var(--u-muted);
    background:var(--u-bg); border:1px solid var(--u-line);
    border-radius:6px; padding:2px 7px; flex-shrink:0; margin-top:2px;
}
.gt-card-info { flex:1; min-width:0; }
.gt-card-subject { font-size:14px; font-weight:700; color:var(--u-text); margin-bottom:4px; }
.gt-card-meta { font-size:11px; color:var(--u-muted); display:flex; gap:8px; flex-wrap:wrap; }
.gt-card-badges { display:flex; gap:5px; flex-shrink:0; flex-wrap:wrap; align-items:flex-start; }
.gt-chevron { font-size:12px; color:var(--u-muted); flex-shrink:0; transition:transform .2s; margin-top:4px; }
.gt-card.expanded .gt-chevron { transform:rotate(180deg); }

/* Card body (collapsible) */
.gt-card-body { display:none; padding:0 16px 16px; border-top:1px solid var(--u-line); }
.gt-card.expanded .gt-card-body { display:block; }

/* Message box */
.gt-msg-box {
    background:var(--u-bg); border:1px solid var(--u-line);
    border-radius:8px; padding:12px 14px;
    font-size:13px; color:var(--u-text); line-height:1.6;
    margin:12px 0;
}

/* Replies chat style */
.gt-replies { display:flex; flex-direction:column; gap:8px; margin-bottom:12px; }
.gt-reply { display:flex; gap:8px; align-items:flex-start; }
.gt-reply.staff { flex-direction:row; }
.gt-reply.guest { flex-direction:row-reverse; }
.gt-reply-av {
    width:28px; height:28px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:700; color:#fff;
}
.gt-reply.staff .gt-reply-av { background:linear-gradient(135deg,var(--u-brand),#7c3aed); }
.gt-reply.guest .gt-reply-av { background:var(--u-ok); }
.gt-reply-bubble {
    max-width:80%; padding:9px 13px; border-radius:12px;
    font-size:13px; line-height:1.55;
}
.gt-reply.staff .gt-reply-bubble {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:4px 12px 12px 12px;
}
.gt-reply.guest .gt-reply-bubble {
    background:var(--u-brand); color:#fff;
    border-radius:12px 4px 12px 12px;
}
.gt-reply-meta { font-size:10px; color:var(--u-muted); margin-top:3px; }
.gt-reply.guest .gt-reply-meta { text-align:right; color:rgba(255,255,255,.6); }

/* Reply input */
.gt-reply-input {
    border:1.5px solid var(--u-line); border-radius:10px;
    padding:9px 12px; width:100%; font-size:13px;
    font-family:inherit; resize:none; box-sizing:border-box;
    min-height:72px; max-height:150px;
    background:var(--u-bg); color:var(--u-text);
    transition:border-color .15s;
}
.gt-reply-input:focus { outline:none; border-color:var(--u-brand); box-shadow:0 0 0 3px rgba(37,99,235,.08); }

/* Emoji/GIF */
.eg-picker-wrap{position:relative;display:inline-block}
.eg-picker-btn{background:none !important;border:none !important;cursor:pointer;font-size:17px !important;padding:4px 6px !important;border-radius:6px !important;line-height:1 !important;color:#888;min-height:0 !important;height:32px !important;width:32px !important;display:inline-flex !important;align-items:center !important;justify-content:center !important}
.eg-picker-btn:hover{background:#f0f4ff !important}
.eg-emoji-picker,.eg-gif-picker{display:none;position:absolute;bottom:calc(100% + 8px);left:0;z-index:9000;background:#fff;border:1px solid var(--u-line,#e5e9f0);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.14);width:280px}
.eg-emoji-picker.open,.eg-gif-picker.open{display:block}
.eg-emoji-cats{display:flex;gap:2px;padding:6px;border-bottom:1px solid #f0f2f7;flex-wrap:wrap}
.eg-emoji-cats button{background:none !important;border:none !important;font-size:18px !important;padding:3px !important;border-radius:5px !important;cursor:pointer;min-height:0 !important;line-height:1.2 !important}
.eg-emoji-cats button.active,.eg-emoji-cats button:hover{background:#eef4ff !important}
.eg-emoji-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;padding:6px;max-height:160px;overflow-y:auto}
.eg-emoji-grid button{font-size:20px !important;background:none !important;border:none !important;padding:2px !important;border-radius:5px !important;cursor:pointer;text-align:center;min-height:0 !important;height:34px !important;width:34px !important}
.eg-emoji-grid button:hover{background:#eef4ff !important}
.eg-gif-picker{width:300px}
.eg-gif-search{padding:8px;border-bottom:1px solid #f0f2f7}
.eg-gif-search input{width:100%;box-sizing:border-box;border:1px solid var(--u-line,#e5e9f0);border-radius:6px;padding:5px 10px;font-size:13px;min-height:0 !important}
.eg-gif-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px;padding:6px;max-height:180px;overflow-y:auto}
.eg-gif-grid img{width:100%;border-radius:6px;cursor:pointer;object-fit:cover;aspect-ratio:16/9}
.eg-gif-loading{padding:12px;text-align:center;color:#aaa;font-size:12px;grid-column:1/-1}

/* ── SLA countdown bar ── */
.gt-sla-bar { height: 3px; background: var(--u-line); overflow: hidden; }
.gt-sla-fill { height: 100%; border-radius: 0; transition: width .5s; }
.gt-sla-fill.ok   { background: var(--u-ok);     }
.gt-sla-fill.warn { background: #f59e0b;          }
.gt-sla-fill.crit { background: var(--u-danger);  }
.gt-sla-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10px; font-weight: 700; padding: 2px 7px;
    border-radius: 999px; white-space: nowrap;
}
.gt-sla-chip.ok   { background: rgba(22,163,74,.10); color: #166534; }
.gt-sla-chip.warn { background: rgba(245,158,11,.12); color: #92400e; }
.gt-sla-chip.crit { background: rgba(220,38,38,.10);  color: #991b1b; }
.gt-sla-chip.over { background: rgba(220,38,38,.15);  color: #7f1d1d; }

/* ── Minimalist overrides ── */
.jm-minimalist .gt-reply.staff .gt-reply-av { background: var(--u-brand, #111) !important; }
.jm-minimalist .gt-reply.guest .gt-reply-bubble {
    background: var(--u-brand, #111) !important;
    color: #fff !important;
}
.jm-minimalist .gt-card:hover { box-shadow: none !important; }
</style>
@endpush

@section('content')
@php
    $total  = $tickets->count();
    $open   = $tickets->where('status', 'open')->count();
    $closed = $tickets->where('status', 'closed')->count();
    $urgent = $tickets->whereIn('priority', ['urgent','high'])->where('status','open')->count();
@endphp

{{-- ── KPI Bar ── --}}
<div class="gt-kpi-bar">
    <div class="gt-kpi">
        <div class="gt-kpi-icon" style="background:rgba(37,99,235,.1);">🎫</div>
        <div>
            <div class="gt-kpi-val">{{ $total }}</div>
            <div class="gt-kpi-label">Toplam Ticket</div>
        </div>
    </div>
    <div class="gt-kpi">
        <div class="gt-kpi-icon" style="background:rgba(22,163,74,.1);">✅</div>
        <div>
            <div class="gt-kpi-val" style="color:var(--u-ok);">{{ $open }}</div>
            <div class="gt-kpi-label">Açık</div>
        </div>
    </div>
    <div class="gt-kpi">
        <div class="gt-kpi-icon" style="background:rgba(100,116,139,.1);">🔒</div>
        <div>
            <div class="gt-kpi-val" style="color:var(--u-muted);">{{ $closed }}</div>
            <div class="gt-kpi-label">Kapalı</div>
        </div>
    </div>
    <div class="gt-kpi">
        <div class="gt-kpi-icon" style="background:rgba(220,38,38,.1);">⚡</div>
        <div>
            <div class="gt-kpi-val" style="{{ $urgent > 0 ? 'color:var(--u-danger)' : '' }}">{{ $urgent }}</div>
            <div class="gt-kpi-label">Acil / Yüksek</div>
        </div>
    </div>
</div>

<div class="gt-layout">

    {{-- ══ LEFT: Form Panel ══ --}}
    <div class="gt-left">

        {{-- New Ticket Form --}}
        <div class="card ai-card" style="padding:0!important;margin-bottom:0!important;">
            <div style="padding:14px 16px;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-sm);font-weight:800;color:var(--u-text);margin-bottom:2px;">🎫 Yeni Destek Talebi Oluştur</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">Sorununu kayıt altına al, ekip sana dönüş yapsın</div>
            </div>
            <div style="padding:16px;">
                <form method="POST" action="{{ route('guest.tickets.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="gt-field">
                        <label>Konu <span style="color:var(--u-danger);">*</span></label>
                        <input name="subject" placeholder="Ticket konusu" required>
                    </div>
                    <div class="gt-row2">
                        <div class="gt-field">
                            <label>Öncelik</label>
                            <select name="priority">
                                <option value="normal">Normal</option>
                                <option value="low">Düşük</option>
                                <option value="high">Yüksek</option>
                                <option value="urgent">🔴 Acil</option>
                            </select>
                        </div>
                        <div class="gt-field">
                            <label>Departman</label>
                            <select name="department">
                                <option value="advisory">Danışmanlık</option>
                                <option value="system">Sistem</option>
                            </select>
                        </div>
                    </div>
                    <div class="gt-field">
                        <label>Mesaj <span style="color:var(--u-danger);">*</span></label>
                        <textarea name="message" id="gNewTicketMsg"
                            placeholder="Talebinizi detaylı açıklayın…"
                            style="min-height:100px;" required></textarea>
                    </div>
                    <div class="gt-field">
                        <label style="cursor:pointer;display:flex;align-items:center;gap:6px;font-size:var(--tx-xs);color:var(--u-muted);">
                            <input type="file" name="attachment" id="gtNewAttach" style="display:none;"
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                   onchange="document.getElementById('gtNewAttachName').textContent=this.files[0]?.name||''">
                            📎 Dosya ekle (opsiyonel)
                        </label>
                        <span id="gtNewAttachName" style="font-size:var(--tx-xs);color:var(--u-brand);margin-left:2px;"></span>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <button class="btn ok" type="submit" style="flex:1;justify-content:center;">Gönder</button>
                        <div class="eg-picker-wrap">
                            <button type="button" class="eg-picker-btn" onclick="egTogglePicker('emoji','gNewTicketMsg')" title="Emoji">😊</button>
                            <div class="eg-emoji-picker" id="egEmojiPicker_gNewTicketMsg">
                                <div class="eg-emoji-cats" id="egEmojiCats_gNewTicketMsg"></div>
                                <div class="eg-emoji-grid" id="egEmojiGrid_gNewTicketMsg"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Fark açıklaması --}}
        <div style="padding:13px 14px;background:rgba(37,99,235,.04);border:1px solid rgba(37,99,235,.14);border-radius:10px;font-size:var(--tx-xs);color:var(--u-muted);line-height:1.7;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-text);margin-bottom:6px;">📌 Ticket mi, Mesaj mı?</div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <div>🎫 <strong style="color:var(--u-text);">Ticket (bu ekran)</strong> — Belge onayı, sözleşme, resmi talep. Kayıt altında kalır, ekip takip eder.</div>
                <div>💬 <strong style="color:var(--u-text);">Mesaj</strong> — Hızlı soru, günlük iletişim, danışmanla birebir konuşma.</div>
            </div>
            <a href="{{ route('guest.messages') }}" style="display:inline-block;margin-top:8px;color:var(--u-brand);font-weight:600;font-size:var(--tx-xs);">Danışmanına mesaj yaz →</a>
        </div>

    </div>

    {{-- ══ RIGHT: Ticket List ══ --}}
    <div>
        @if($tickets->isEmpty())
            <div class="card" style="padding:20px;">
                <div class="gt-list-head" style="margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--u-line);">
                    <span class="gt-list-title">📂 Mevcut Destek Taleplerim</span>
                    <span class="gt-list-sub">Açtığın talepleri buradan takip edebilir, yanıt ekleyebilirsin</span>
                </div>
                <div style="text-align:center;padding:28px 10px 14px;color:var(--u-muted);">
                    <div style="font-size:48px;margin-bottom:14px;">🎫</div>
                    <div style="font-size:var(--tx-base);font-weight:600;color:var(--u-text);margin-bottom:6px;">Henüz destek talebi açmadınız</div>
                    <div style="font-size:var(--tx-sm);max-width:280px;margin:0 auto;line-height:1.5;">Sol paneldeki formu doldurarak ilk talebinizi oluşturabilirsiniz.</div>
                </div>
            </div>
        @else
            <div class="gt-list-head">
                <span class="gt-list-title">📂 Mevcut Destek Taleplerim</span>
                <span class="gt-list-sub">Açtığın talepleri buradan takip edebilir, yanıt ekleyebilirsin</span>
            </div>
            <div class="gt-list">
            @foreach($tickets as $ticket)
                @php
                    $isOpen   = (string)$ticket->status !== 'closed';
                    $isUrgent = in_array($ticket->priority, ['urgent','high']);
                    $cardClass = $isUrgent && $isOpen ? 'urgent-ticket' : ($isOpen ? 'open-ticket' : 'closed-ticket');

                    $statusLabel = match((string)$ticket->status) {
                        'open'       => ['ok',      'Açık'],
                        'in_progress'=> ['info',    'İşlemde'],
                        'waiting'    => ['warn',    'Bekliyor'],
                        'closed'     => ['pending', 'Kapalı'],
                        default      => ['pending', $ticket->status],
                    };
                    $priorityLabel = match((string)$ticket->priority) {
                        'urgent' => ['danger', '🔴 Acil'],
                        'high'   => ['warn',   '⬆ Yüksek'],
                        'normal' => ['info',   'Normal'],
                        'low'    => ['pending','Düşük'],
                        default  => ['pending', $ticket->priority],
                    };
                    $dueAt     = $ticket->last_replied_at ? \Carbon\Carbon::parse($ticket->last_replied_at)->addHours(24) : null;
                    $isOverdue = $dueAt ? now()->greaterThan($dueAt) : false;
                    $replyCount = $ticket->replies->count();
                @endphp

                @php
                    $slaWindowSec  = 24 * 3600;
                    $slaElapsedSec = $dueAt ? max(0, $slaWindowSec - now()->diffInSeconds($dueAt, false)) : null;
                    $slaFillPct    = $dueAt ? min(100, max(0, ($slaElapsedSec / $slaWindowSec) * 100)) : null;
                    $slaBarClass   = match(true) {
                        !$dueAt || !$isOpen      => 'ok',
                        $isOverdue               => 'crit',
                        $slaElapsedSec < 4*3600  => 'crit',
                        $slaElapsedSec < 8*3600  => 'warn',
                        default                  => 'ok',
                    };
                @endphp
                <div class="gt-card {{ $cardClass }}" id="ticket-{{ $ticket->id }}"
                     @if($dueAt && $isOpen) data-sla-due="{{ $dueAt->toIso8601String() }}" @endif>

                    {{-- Clickable header --}}
                    <div class="gt-card-hdr" onclick="gtToggle({{ $ticket->id }})">
                        <span class="gt-card-num">#{{ $ticket->id }}</span>
                        <div class="gt-card-info">
                            <div class="gt-card-subject">{{ $ticket->subject }}</div>
                            <div class="gt-card-meta">
                                <span>{{ $ticket->created_at ? \Carbon\Carbon::parse($ticket->created_at)->format('d.m.Y') : '-' }}</span>
                                @if($replyCount > 0)
                                    <span>· {{ $replyCount }} yanıt</span>
                                @endif
                            </div>
                        </div>
                        <div class="gt-card-badges">
                            <span class="badge {{ $statusLabel[0] }}" style="font-size:var(--tx-xs);">{{ $statusLabel[1] }}</span>
                            <span class="badge {{ $priorityLabel[0] }}" style="font-size:var(--tx-xs);">{{ $priorityLabel[1] }}</span>
                            @if($dueAt && $isOpen)
                                <span class="gt-sla-chip {{ $isOverdue ? 'over' : $slaBarClass }}" data-sla-chip>
                                    ⏱ {{ $isOverdue ? 'SLA aşıldı' : '' }}
                                </span>
                            @endif
                        </div>
                        <span class="gt-chevron">▼</span>
                    </div>
                    {{-- SLA progress bar (only open tickets with due date) --}}
                    @if($dueAt && $isOpen)
                    <div class="gt-sla-bar">
                        <div class="gt-sla-fill {{ $slaBarClass }}" data-sla-fill
                             style="width:{{ $isOverdue ? 100 : (100 - ($slaFillPct ?? 0)) }}%;"></div>
                    </div>
                    @endif

                    {{-- Collapsible body --}}
                    <div class="gt-card-body">

                        {{-- Original message --}}
                        <div class="gt-msg-box">{{ $ticket->message }}</div>
                        @if($ticket->attachment_name)
                        <div style="margin-top:6px;">
                            <a href="{{ route('guest.tickets.attachment', $ticket->id) }}"
                               class="badge info" style="font-size:var(--tx-xs);text-decoration:none;">
                                📎 {{ $ticket->attachment_name }}
                            </a>
                        </div>
                        @endif

                        {{-- Replies --}}
                        @if($ticket->replies->isNotEmpty())
                        <div class="gt-replies">
                            @foreach($ticket->replies as $reply)
                                @php $isStaff = in_array($reply->author_role, ['manager','senior','system']); @endphp
                                <div class="gt-reply {{ $isStaff ? 'staff' : 'guest' }}">
                                    <div class="gt-reply-av" title="{{ $reply->author_role }}">
                                        {{ $isStaff ? '🛡' : '👤' }}
                                    </div>
                                    <div>
                                        <div class="gt-reply-bubble">{{ $reply->message }}</div>
                                        @if($reply->attachment_name && !$isStaff)
                                        <div style="margin-top:4px;">
                                            <a href="{{ route('guest.tickets.attachment', $ticket->id) }}"
                                               class="badge info" style="font-size:var(--tx-xs);text-decoration:none;">
                                                📎 {{ $reply->attachment_name }}
                                            </a>
                                        </div>
                                        @endif
                                        <div class="gt-reply-meta">
                                            {{ $isStaff ? 'Destek Ekibi' : 'Siz' }}
                                            · {{ $reply->created_at ? \Carbon\Carbon::parse($reply->created_at)->format('d.m H:i') : '-' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Actions + Reply --}}
                        <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;">
                            @if($isOpen)
                                <form method="POST" action="{{ route('guest.tickets.close', $ticket->id) }}" style="display:inline;"
                                      x-data @submit.prevent="$store.modal.confirm('Ticket kapatılsın mı?','Bu ticket kapatılacak.',()=>$el.submit())">
                                    @csrf
                                    <button class="btn warn" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">Kapat</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('guest.tickets.reopen', $ticket->id) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn alt" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">Yeniden Aç</button>
                                </form>
                            @endif
                        </div>

                        @if($isOpen)
                        <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--u-muted);margin-bottom:8px;padding-top:4px;">
                            ↩ Bu talebe yanıt ekle
                        </div>
                        <form method="POST" action="{{ route('guest.tickets.reply', $ticket->id) }}" enctype="multipart/form-data">
                            @csrf
                            <textarea name="message" id="gReply{{ $ticket->id }}"
                                class="gt-reply-input"
                                placeholder="Bu talep hakkında ek bilgi veya soruların varsa yaz…"
                                required></textarea>
                            <div style="display:flex;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap;">
                                <button class="btn ok" type="submit" style="font-size:var(--tx-xs);padding:6px 16px;">Yanıt Gönder</button>
                                <label style="cursor:pointer;display:flex;align-items:center;gap:4px;font-size:var(--tx-xs);color:var(--u-muted);">
                                    <input type="file" name="attachment" id="gtReplyAttach{{ $ticket->id }}" style="display:none;"
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                           onchange="this.nextElementSibling.textContent=this.files[0]?.name||''">
                                    📎 Ekle
                                    <span style="color:var(--u-brand);"></span>
                                </label>
                                <div class="eg-picker-wrap">
                                    <button type="button" class="eg-picker-btn" onclick="egTogglePicker('emoji','gReply{{ $ticket->id }}')" title="Emoji">😊</button>
                                    <div class="eg-emoji-picker" id="egEmojiPicker_gReply{{ $ticket->id }}">
                                        <div class="eg-emoji-cats" id="egEmojiCats_gReply{{ $ticket->id }}"></div>
                                        <div class="eg-emoji-grid" id="egEmojiGrid_gReply{{ $ticket->id }}"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        @else
                            <div style="font-size:var(--tx-xs);color:var(--u-muted);text-align:center;padding:8px;background:var(--u-bg);border-radius:8px;">
                                Bu ticket kapatılmış. Yanıt eklemek için önce yeniden açın.
                            </div>
                        @endif

                    </div>{{-- /gt-card-body --}}
                </div>
            @endforeach
            </div>
        @endif
    </div>

</div>{{-- /gt-layout --}}

<script defer src="{{ Vite::asset('resources/js/emoji-gif-picker.js') }}" defer></script>
<script>
function gtToggle(id) {
    var el = document.getElementById('ticket-' + id);
    if (el) el.classList.toggle('expanded');
}
// Son ticket'ı açık getir (eğer varsa)
document.addEventListener('DOMContentLoaded', function() {
    var cards = document.querySelectorAll('.gt-card');
    if (cards.length > 0) cards[0].classList.add('expanded');
    gtSlaUpdate();
    setInterval(gtSlaUpdate, 30000);
});

// SLA Countdown
function gtSlaUpdate() {
    document.querySelectorAll('.gt-card[data-sla-due]').forEach(function(card) {
        var due = new Date(card.getAttribute('data-sla-due'));
        var now = new Date();
        var diffMs = due - now;
        var chip = card.querySelector('[data-sla-chip]');
        var fill = card.querySelector('[data-sla-fill]');

        if (diffMs <= 0) {
            // Overdue
            if (chip) { chip.textContent = '⏱ SLA aşıldı'; chip.className = 'gt-sla-chip over'; }
            if (fill)  { fill.style.width = '100%'; fill.className = 'gt-sla-fill crit'; }
            return;
        }
        var totalMs   = 24 * 3600 * 1000;
        var elapsed   = totalMs - diffMs;
        var pct       = Math.min(100, Math.max(0, (elapsed / totalMs) * 100));
        var hoursLeft = Math.floor(diffMs / 3600000);
        var minsLeft  = Math.floor((diffMs % 3600000) / 60000);
        var label     = hoursLeft > 0 ? hoursLeft + 's ' + minsLeft + 'dk kaldı' : minsLeft + ' dk kaldı';
        var cls       = hoursLeft >= 8 ? 'ok' : (hoursLeft >= 4 ? 'warn' : 'crit');

        if (chip) { chip.textContent = '⏱ ' + label; chip.className = 'gt-sla-chip ' + cls; }
        if (fill)  { fill.style.width = pct + '%'; fill.className = 'gt-sla-fill ' + cls; }
    });
}
(function(){
    var _orig = window.__designToggle;
    window.__designToggle = function(d){
        if(_orig) _orig(d);
        setTimeout(function(){ document.documentElement.classList.toggle('jm-minimalist', d==='minimalist'); }, 50);
    };
})();
</script>
@endsection

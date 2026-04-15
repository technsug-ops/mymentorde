@extends($layout)
@section('title', 'İletişim Merkezi')
@section('page_title', '💬 İletişim Merkezi')

@push('head')
<style>
/* ── Hub: viewport'u tam doldur ── */
html,body{height:100%;overflow:hidden}
.shell{height:100vh !important;min-height:0 !important;overflow:hidden}
.main{height:100vh !important;padding:0 !important;overflow:hidden;display:flex;flex-direction:column}
.main-inner{flex:1;min-height:0;overflow:hidden;display:flex;flex-direction:column;width:100% !important;margin:0 !important;padding:0 0 50px 0 !important}
.main-inner>.top{flex-shrink:0;padding:10px 16px;border-bottom:1px solid var(--u-line)}
/* ── Hub wrapper ── */
.hub-wrap{display:flex;flex:1;min-height:0;min-width:0;gap:0;border:1px solid var(--u-line);border-radius:8px;overflow:hidden;background:var(--u-card)}
/* ── CSS :target modals ── */
.hub-modal-bg:target{display:flex}
.hub-modal-close{position:absolute;top:14px;right:16px;font-size:18px;color:#999;text-decoration:none;line-height:1;cursor:pointer;padding:2px 6px}
.hub-modal-close:hover{color:var(--u-text)}
/* ── Sol panel ── */
.hub-left{width:280px;min-width:220px;border-right:1px solid var(--u-line);display:flex;flex-direction:column;flex-shrink:0}
.hub-tabs{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid var(--u-line);flex-shrink:0}
.hub-tab{padding:10px 6px;text-align:center;cursor:pointer;font-size:12px;font-weight:600;border:none;background:none;display:flex;align-items:center;justify-content:center;gap:5px;color:var(--u-text);min-height:0 !important}
.hub-tab.active{background:#eef4fb;border-bottom:2px solid var(--u-brand,#4577c4);color:var(--u-brand,#4577c4)}
.hub-tab:hover:not(.active){background:#f5f8ff}
.hub-section{flex:1;overflow:hidden;display:flex;flex-direction:column}
.hub-section.hidden{display:none !important}
/* ── Liste başlığı ── */
.hub-list-head{padding:6px 8px;border-bottom:1px solid var(--u-line);display:flex;flex-direction:column;gap:5px;flex-shrink:0}
.hub-list-head input{width:100%;padding:5px 8px;border:1px solid var(--u-line);border-radius:6px;font-size:12px;box-sizing:border-box;min-height:0 !important}
.hub-list-btns{display:flex;gap:4px;flex-wrap:wrap}
.hub-list-head .btn{padding:3px 8px !important;font-size:11px !important;font-weight:600 !important;white-space:nowrap;min-height:28px !important;border-radius:6px !important;line-height:1.4}
/* ── Konuşma listesi ── */
.hub-item-list{flex:1;overflow-y:auto}
.hub-item{padding:9px 10px;cursor:pointer;border-bottom:1px solid var(--u-line);display:flex;gap:7px;align-items:flex-start;transition:background .12s}
.hub-item:hover{background:#f5f8ff}
.hub-item.active{background:#eef4fb}
.hub-item.pinned{border-left:3px solid var(--u-brand,#4577c4)}
.hub-item.overdue{border-left:3px solid var(--u-danger,#c0392b)}
.hub-item.bulk-mode{position:relative;padding-left:30px}
.hub-item.bulk-mode::before{content:"";position:absolute;left:9px;top:50%;transform:translateY(-50%);width:15px;height:15px;border:1.5px solid #cbd5e1;border-radius:4px;background:#fff;transition:background .12s,border-color .12s}
.hub-item.bulk-mode.bulk-selected::before{background:#dc2626;border-color:#dc2626}
.hub-item.bulk-mode.bulk-selected::after{content:"✓";position:absolute;left:11px;top:50%;transform:translateY(-55%);color:#fff;font-size:10px;font-weight:700;line-height:1}
.hub-item.bulk-mode.bulk-selected{background:#fef2f2 !important}
.hub-avatar{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0}
.hub-avatar.type-guest{background:#e67e22}
.hub-avatar.type-student{background:#27ae60}
.hub-avatar.type-dm{background:#4577c4}
.hub-avatar.type-group{background:#8e44ad}
.hub-avatar.type-room{background:#e74c3c}
.hub-item-meta{flex:1;min-width:0}
.hub-item-meta strong{display:block;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hub-item-preview{font-size:11px;color:var(--u-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}
.hub-item-right{display:flex;flex-direction:column;align-items:flex-end;gap:3px;flex-shrink:0}
.hub-item-time{font-size:10px;color:var(--u-muted)}
.hub-unread{background:var(--u-brand,#4577c4);color:#fff;border-radius:10px;padding:1px 5px;font-size:10px;font-weight:700}
.hub-unread.warn{background:var(--u-danger,#c0392b)}
/* ── Sağ panel ── */
.hub-right{flex:1;display:flex;flex-direction:column;min-width:0;min-height:0}
.hub-panel{flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;position:relative;padding-bottom:34px}
.hub-panel.hidden{display:none !important}
/* ── Thread başlığı ── */
.hub-thread-head{padding:10px 14px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:8px;flex-shrink:0}
.hub-thread-head strong{font-size:13px}
.hub-thread-actions{margin-left:auto;display:flex;gap:5px}
.hub-thread-actions .btn{padding:3px 8px;font-size:11px}
/* ── Mesajlar ── */
.hub-messages{flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:7px}
.hub-empty{display:flex;align-items:center;justify-content:center;height:100%;color:var(--u-muted);font-size:13px;text-align:center}
.hub-msg-row{display:flex;gap:7px;align-items:flex-end}
.hub-msg-row.staff{justify-content:flex-end}
.hub-msg-row.customer{justify-content:flex-start}
.hub-msg-row.system-msg{justify-content:center}
.hub-bubble{padding:7px 11px;border-radius:10px;font-size:13px;line-height:1.45;max-width:100%;overflow-wrap:anywhere;word-break:normal;white-space:pre-wrap}
.hub-msg-row.staff .hub-bubble{background:#dcf8c6;border-radius:10px 0 10px 10px}
.hub-msg-row.customer .hub-bubble{background:var(--u-card,#fff);border:1px solid var(--u-line);border-radius:0 10px 10px 10px}
.hub-msg-row.system-msg .hub-bubble{background:#f0f0f0;color:#888;font-size:11px;padding:3px 10px;border-radius:6px}
.hub-msg-row.deleted .hub-bubble{color:#aaa;font-style:italic}
.hub-msg-sender{font-size:10px;font-weight:700;color:var(--u-brand,#4577c4);margin-bottom:2px}
.hub-msg-time{font-size:10px;color:var(--u-muted);margin-top:2px}
.hub-msg-avatar{width:26px;height:26px;border-radius:50%;background:#ccc;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
.hub-reply-preview{background:#f0f4ff;border-left:3px solid var(--u-brand,#4577c4);padding:3px 7px;border-radius:4px;font-size:10px;color:#555;margin-bottom:3px}
.hub-attach{display:flex;align-items:center;gap:5px;background:#f5f5f5;border-radius:5px;padding:4px 7px;font-size:11px;margin-top:3px}
.hub-attach a{color:var(--u-brand,#4577c4)}
.hub-input-bar{border-top:1px solid var(--u-line);padding:9px 11px;display:flex;gap:6px;align-items:flex-end;flex-wrap:wrap;background:var(--u-card,#fff);flex-shrink:0}
.hub-input-bar textarea{flex:1 1 100%;resize:none;border:1px solid var(--u-line);border-radius:7px;padding:8px 12px;font-size:14px;min-height:42px;max-height:160px;font-family:inherit;line-height:1.45;order:-1}
.hub-input-bar .hub-picker-wrap{margin-right:auto}
.hub-input-bar textarea:focus{outline:none;border-color:var(--u-brand,#4577c4)}
.hub-input-bar .btn{align-self:flex-end;padding:7px 14px}
.hub-reply-bar{padding:5px 11px;background:#f0f4ff;border-top:1px solid #d0dcf5;font-size:11px;color:#444;display:flex;align-items:center;gap:7px;flex-shrink:0}
.hub-reply-bar button{background:none;border:none;cursor:pointer;color:#999;font-size:15px}
.hub-no-select{display:flex;align-items:center;justify-content:center;height:100%;color:var(--u-muted);text-align:center;padding:20px}
/* ── Müşteri özet bar ── */
.hub-summary{display:flex;gap:8px;padding:7px 10px;background:#f8f9fa;border-bottom:1px solid var(--u-line);font-size:11px;flex-wrap:wrap;flex-shrink:0}
.hub-summary .kv{display:flex;gap:3px;align-items:center}
.hub-summary .kv strong{color:var(--u-text)}
/* ── Modaller ── */
.hub-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.42);z-index:9998;align-items:center;justify-content:center}
.hub-modal-bg.open{display:flex}
.hub-modal{background:#fff;border-radius:12px;padding:24px;width:480px;max-width:95vw;z-index:9999;max-height:85vh;overflow-y:auto;box-shadow:0 10px 36px rgba(0,0,0,.2);position:relative}
.hub-modal h3{margin:0 0 16px;font-size:15px;font-weight:700;color:var(--u-text);display:flex;align-items:center;gap:7px;padding-bottom:14px;border-bottom:1px solid var(--u-line)}
.hub-modal .field{margin-bottom:12px}
.hub-modal .field > label{display:block;font-size:11px;font-weight:600;margin-bottom:5px;color:var(--u-muted)}
.hub-modal input[type=text],.hub-modal select,.hub-modal textarea{width:100%;padding:8px 11px;border:1px solid var(--u-line);border-radius:7px;font-size:13px;box-sizing:border-box;background:#fafafa;transition:border-color .15s,box-shadow .15s}
.hub-modal input[type=text]:focus,.hub-modal select:focus,.hub-modal textarea:focus{outline:none;border-color:var(--u-brand,#4577c4);background:#fff;box-shadow:0 0 0 3px rgba(69,119,196,.1)}
.hub-modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:18px;padding-top:14px;border-top:1px solid var(--u-line)}
.hub-user-select{border:1px solid var(--u-line);border-radius:8px;max-height:240px;overflow-y:auto;padding:4px;background:#fafafa}
.hub-user-row{display:flex;align-items:center;gap:10px;padding:8px 10px;cursor:pointer;border-radius:7px;transition:background .12s}
.hub-user-row:hover{background:#eef4fb}
.hub-user-row input[type=radio],.hub-user-row input[type=checkbox]{position:absolute;opacity:0;width:0;height:0;pointer-events:none}
.hub-uavatar{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0}
.hub-uinfo{flex:1;min-width:0}
.hub-uinfo strong{display:block;font-size:12.5px;font-weight:600;color:var(--u-text)}
.hub-uinfo small{font-size:10.5px;color:var(--u-muted)}
.hub-ucheck{width:18px;height:18px;border-radius:50%;border:2px solid #d0d0d0;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .15s;font-size:0}
.hub-ucheck-sq{width:16px;height:16px;border-radius:4px;border:2px solid #d0d0d0;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .15s;font-size:0}
.hub-user-row:has(input:checked){background:#eef4fb}
.hub-user-row:has(input:checked) .hub-ucheck{background:var(--u-brand,#4577c4);border-color:var(--u-brand,#4577c4);font-size:10px;color:#fff;font-weight:800}
.hub-user-row:has(input:checked) .hub-ucheck::after{content:"✓"}
.hub-user-row:has(input:checked) .hub-ucheck-sq{background:var(--u-brand,#4577c4);border-color:var(--u-brand,#4577c4);font-size:9px;color:#fff;font-weight:800}
.hub-user-row:has(input:checked) .hub-ucheck-sq::after{content:"✓"}
/* ── Emoji + GIF ── */
.hub-picker-wrap{position:relative;display:flex;gap:2px;align-items:center;flex-shrink:0}
.hub-picker-btn{background:none !important;border:none !important;cursor:pointer;font-size:17px !important;padding:4px 6px !important;border-radius:6px !important;line-height:1 !important;color:#888;transition:background .12s;min-height:0 !important;height:32px !important;width:32px !important;display:inline-flex !important;align-items:center !important;justify-content:center !important}
.hub-picker-btn:hover{background:#f0f4ff !important;color:#4577c4}
.hub-emoji-picker,.hub-gif-picker{position:absolute;bottom:calc(100% + 8px);left:0;z-index:9000;background:#fff;border:1px solid var(--u-line);border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,.18);display:none;max-width:calc(100vw - 32px)}
.hub-emoji-picker.open,.hub-gif-picker.open{display:block}
.hub-emoji-picker{width:310px;padding:8px}
.hub-emoji-cats{display:flex;gap:3px;margin-bottom:6px;flex-wrap:wrap}
.hub-emoji-cats button{background:none !important;border:none !important;cursor:pointer;font-size:15px !important;padding:2px 5px !important;border-radius:5px !important;opacity:.6;min-height:0 !important;line-height:1.2 !important}
.hub-emoji-cats button.active,.hub-emoji-cats button:hover{background:#eef4fb !important;opacity:1}
.hub-emoji-grid{display:flex;flex-wrap:wrap;gap:1px;max-height:200px;overflow-y:auto}
.hub-emoji-grid button{font-size:20px !important;background:none !important;border:none !important;cursor:pointer;padding:4px !important;border-radius:5px !important;line-height:1 !important;transition:background .1s;min-height:0 !important;height:34px !important;width:34px !important}
.hub-emoji-grid button:hover{background:#f0f4ff !important}
.hub-gif-picker{width:320px}
.hub-gif-search{padding:7px 8px;border-bottom:1px solid var(--u-line)}
.hub-gif-search input{width:100%;padding:6px 9px !important;border:1px solid var(--u-line);border-radius:6px;font-size:12px;box-sizing:border-box;min-height:0 !important}
.hub-gif-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px;padding:6px;max-height:250px;overflow-y:auto}
.hub-gif-grid img{width:100%;height:80px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid transparent;transition:border-color .12s}
.hub-gif-grid img:hover{border-color:var(--u-brand,#4577c4)}
.hub-gif-loading{text-align:center;padding:18px;color:var(--u-muted);font-size:12px}
.hub-bubble img.hub-gif-img{max-width:100%;border-radius:6px;display:block;margin-top:2px}
</style>
@endpush

@section('content')
<script nonce="{{ $cspNonce ?? '' }}">
window.__hub = {
    tab: '{{ $tab }}',
    currentUserId: {{ (int) $currentUser->id }},
    csrf: '{{ csrf_token() }}',
    routes: {
        imPoll:    '/im/conversations/__ID__/poll',
        imSend:    '/im/conversations/__ID__/send',
        imRead:    '/im/conversations/__ID__/read',
        imMute:    '/im/conversations/__ID__/mute',
        imPin:     '/im/conversations/__ID__/pin',
        imDmStart: '/im/dm/__UID__',
        imDelMsg:  '/im/messages/__MID__',
        cSend:     '/messages-center/__TID__/send',
        cStatus:   '/messages-center/__TID__/status',
        cAssign:   '/messages-center/__TID__/assign-advisor',
        cConvert:  '/messages-center/__TID__/convert-to-ticket',
    },
    pollInterval: 10000,
};
</script>

<div class="hub-wrap">

    {{-- ══ SOL PANEL ══ --}}
    <div class="hub-left">

        {{-- Sekme başlıkları --}}
        <div class="hub-tabs">
            @if($canCustomer)
            <a href="/im?tab=customer" class="hub-tab {{ $tab === 'customer' ? 'active' : '' }}" style="text-decoration:none">
                📥 Danışan
                @if($customerUnread > 0)<span class="hub-unread warn">{{ $customerUnread }}</span>@endif
            </a>
            @endif
            @if($canInternal)
            <a href="/im?tab=internal" class="hub-tab {{ $tab === 'internal' ? 'active' : '' }}" style="text-decoration:none">
                💬 Ekip
                @if($internalUnread > 0)<span class="hub-unread">{{ $internalUnread }}</span>@endif
            </a>
            @endif
        </div>

        {{-- ── Danışan thread listesi ── --}}
        @if($canCustomer)
        <div class="hub-section {{ $tab !== 'customer' ? 'hidden' : '' }}" id="hubSectionCustomer">
            <div class="hub-list-head">
                <input type="text" placeholder="Danışan ara..." id="hubCustSearchInput">
            </div>
            <div class="hub-item-list" id="hubCustomerList">
                @if($customerData && $customerData['threads']->count())
                @foreach($customerData['threads'] as $t)
                @php
                    $unread   = $customerData['unreadAdvisorMap'][$t->id] ?? 0;
                    $isActive = $customerData['selectedThread'] && $customerData['selectedThread']->id === $t->id;
                    $isOverdue = $t->next_response_due_at && \Carbon\Carbon::parse($t->next_response_due_at)->isPast() && $t->status === 'open';
                    $tType    = (string)($t->thread_type ?? 'guest');
                    $guestName = '';
                    if ($tType === 'guest' && isset($customerData['guestMap'][$t->guest_application_id])) {
                        $g = $customerData['guestMap'][$t->guest_application_id];
                        $guestName = trim(($g->first_name ?? '').' '.($g->last_name ?? ''));
                    }
                    $label = $tType === 'student' ? ('STU: '.($t->student_id ?? '')) : ($guestName ?: 'GST-'.$t->guest_application_id);
                @endphp
                <div class="hub-item {{ $isActive ? 'active' : '' }} {{ $isOverdue ? 'overdue' : '' }}"
                     data-search="{{ strtolower($label) }}"
                     data-hub-thread="{{ $t->id }}"
                     style="cursor:pointer">
                    <div class="hub-avatar type-{{ $tType }}">{{ strtoupper(substr($label,0,1)) }}</div>
                    <div class="hub-item-meta">
                        <strong>{{ $label }}</strong>
                        <div class="hub-item-preview">{{ Str::limit($t->last_message_preview ?? '', 45) }}</div>
                    </div>
                    <div class="hub-item-right">
                        <span class="hub-item-time">{{ $t->last_message_at ? \Carbon\Carbon::parse($t->last_message_at)->diffForHumans(null, true) : '' }}</span>
                        @if($unread > 0)<span class="hub-unread warn">{{ $unread }}</span>@endif
                        @if($t->status === 'closed')<span style="font-size:10px;color:#aaa">kapalı</span>@endif
                    </div>
                </div>
                @endforeach
                @else
                <div class="hub-empty" style="height:auto;padding:20px">Danışan mesajı yok.</div>
                @endif
            </div>
        </div>
        @endif

        {{-- ── Ekip konuşma listesi ── --}}
        @if($canInternal)
        <div class="hub-section {{ $tab !== 'internal' ? 'hidden' : '' }}" id="hubSectionInternal">
            <div class="hub-list-head">
                <input type="text" placeholder="Konuşma ara..." id="hubIntSearchInput">
                <div class="hub-list-btns" style="position:relative">
                    <button type="button" id="hubNewConvBtn"
                            class="btn ok"
                            style="padding:5px 11px;font-weight:600;cursor:pointer"
                            title="Yeni konuşma başlat">+ Yeni</button>
                    <button type="button" id="hubBulkToggleBtn"
                            class="btn alt"
                            style="padding:5px 9px;font-weight:600;cursor:pointer"
                            title="Birden fazla konuşma seç ve sil">☑ Seç</button>
                    <div id="hubNewConvMenu" style="display:none;position:absolute;top:calc(100% + 4px);right:0;background:#fff;border:1px solid var(--u-line);border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,.12);min-width:160px;z-index:50;padding:4px;">
                        <a href="#hubModalDm"    style="display:block;padding:8px 11px;text-decoration:none;color:var(--u-text);font-size:13px;border-radius:6px" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">💬 Direkt Mesaj</a>
                        <a href="#hubModalGroup" style="display:block;padding:8px 11px;text-decoration:none;color:var(--u-text);font-size:13px;border-radius:6px" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">👥 Grup Konuşması</a>
                        <a href="#hubModalRoom"  style="display:block;padding:8px 11px;text-decoration:none;color:var(--u-text);font-size:13px;border-radius:6px" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">🏠 Konu Odası</a>
                    </div>
                </div>
            </div>
            {{-- Toplu silme action bar — selection mode aktifken ve ≥1 seçili olunca görünür --}}
            <div id="hubBulkBar" style="display:none;padding:8px 11px;background:#fef2f2;border-bottom:1px solid #fecaca;align-items:center;gap:8px;">
                <span id="hubBulkCount" style="font-size:12px;font-weight:700;color:#991b1b;">0 seçili</span>
                <div style="flex:1"></div>
                <button type="button" id="hubBulkDeleteBtn" class="btn" style="padding:5px 11px;font-size:11px;font-weight:600;background:#dc2626;color:#fff;border-color:#dc2626">🗑 Sil</button>
                <button type="button" id="hubBulkCancelBtn" class="btn alt" style="padding:5px 11px;font-size:11px;font-weight:600">İptal</button>
            </div>
            <form id="hubBulkDestroyForm" method="POST" action="{{ route('im.bulk.destroy') }}" style="display:none">@csrf</form>
            {{-- Filter pills (Slack-style) — tek listede tip filtresi --}}
            @php
                $showArchived = $internalData['showArchived'] ?? false;
                $archivedCount = $internalData['archivedCount'] ?? 0;
            @endphp
            <div class="hub-conv-filters" style="display:flex;gap:5px;padding:4px 11px 8px;flex-wrap:wrap;align-items:center">
                @if($showArchived)
                    <a href="/im?tab=internal" class="hub-filter-pill" style="padding:4px 11px;font-size:11px;border-radius:999px;border:1px solid var(--u-line);background:#fff;color:var(--u-text);cursor:pointer;text-decoration:none">← Aktiflere Dön</a>
                    <span style="font-size:10px;color:var(--u-muted);margin-left:4px">📦 {{ $internalData['conversations']->count() }} arşivli konuşma</span>
                @else
                    <button type="button" class="hub-filter-pill active" data-conv-filter="all"    style="padding:4px 11px;font-size:11px;border-radius:999px;border:1px solid var(--u-line);background:var(--u-brand,#4577c4);color:#fff;cursor:pointer;font-weight:600">Tümü</button>
                    <button type="button" class="hub-filter-pill"        data-conv-filter="direct" style="padding:4px 11px;font-size:11px;border-radius:999px;border:1px solid var(--u-line);background:#fff;color:var(--u-text);cursor:pointer">💬 DM</button>
                    <button type="button" class="hub-filter-pill"        data-conv-filter="group"  style="padding:4px 11px;font-size:11px;border-radius:999px;border:1px solid var(--u-line);background:#fff;color:var(--u-text);cursor:pointer">👥 Grup</button>
                    <button type="button" class="hub-filter-pill"        data-conv-filter="room"   style="padding:4px 11px;font-size:11px;border-radius:999px;border:1px solid var(--u-line);background:#fff;color:var(--u-text);cursor:pointer">🏠 Oda</button>
                @endif
            </div>
            <div class="hub-item-list" id="hubInternalList">
                @if($internalData && $internalData['conversations']->count())
                @foreach($internalData['conversations'] as $conv)
                @php
                    $part      = $conv->participants->first();
                    $isPinned  = $part?->is_pinned;
                    $isMuted   = $part?->is_muted;
                    $unread    = $internalData['unreadMap'][$conv->id] ?? 0;
                    $display   = $conv->getDisplayTitle($currentUser->id);
                    $isActive  = $internalData['selected'] && $internalData['selected']->id === $conv->id;
                    $typeIcon  = match($conv->type) { 'direct' => 'dm', 'room' => 'room', default => 'group' };
                    $typeEmoji = match($conv->type) { 'direct' => '', 'room' => '🏠 ', 'announcement' => '📢 ', default => '👥 ' };
                @endphp
                <div class="hub-item {{ $isActive ? 'active' : '' }} {{ $isPinned ? 'pinned' : '' }}"
                     style="{{ $isMuted ? 'opacity:.55;' : '' }}cursor:pointer"
                     data-search="{{ strtolower($display) }}"
                     data-conv-type="{{ $conv->type === 'announcement' ? 'group' : $conv->type }}"
                     data-hub-conv="{{ $conv->id }}">
                    <div class="hub-avatar type-{{ $typeIcon }}">{{ strtoupper(substr($display,0,1)) }}</div>
                    <div class="hub-item-meta">
                        <strong>{{ $typeEmoji }}{{ $display }}</strong>
                        <div class="hub-item-preview">{{ $conv->last_message_preview ?? 'Henüz mesaj yok' }}</div>
                    </div>
                    <div class="hub-item-right">
                        <span class="hub-item-time">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true) : '' }}</span>
                        @if($unread > 0)<span class="hub-unread">{{ $unread }}</span>@endif
                        @if($isPinned)<span style="font-size:11px">📌</span>@endif
                    </div>
                </div>
                @endforeach
                @else
                <div style="padding:16px;text-align:center;">
                    <div style="font-size:12px;color:var(--u-muted);margin-bottom:8px;">Henüz konuşma yok.</div>
                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <a href="#hubModalDm" class="btn ok" style="font-size:11px;padding:5px 8px;text-decoration:none">💬 DM Başlat</a>
                        <a href="#hubModalGroup" class="btn alt" style="font-size:11px;padding:5px 8px;text-decoration:none">👥 Grup Kur</a>
                        <a href="#hubModalRoom" class="btn" style="font-size:11px;padding:5px 8px;background:#e74c3c;color:#fff;border-color:#e74c3c;text-decoration:none">🏠 Oda Aç</a>
                    </div>
                </div>
                @endif

                {{-- Arşivli konuşmalara erişim — sade, alt tarafta, dikkat çekmeyen link --}}
                @if(!$showArchived && $archivedCount > 0)
                <div style="padding:8px 12px 12px;border-top:1px solid var(--u-line);margin-top:4px">
                    <a href="/im?tab=internal&archived=1"
                       style="display:block;text-align:center;font-size:11px;color:var(--u-muted);text-decoration:none;padding:6px;border-radius:6px;transition:background .12s"
                       onmouseover="this.style.background='#f1f5f9';this.style.color='var(--u-text)'"
                       onmouseout="this.style.background='transparent';this.style.color='var(--u-muted)'">
                        📦 {{ $archivedCount }} arşivli konuşma
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>{{-- /hub-left --}}

    {{-- ══ SAĞ PANEL ══ --}}
    <div class="hub-right">

        {{-- Danışan paneli --}}
        @if($canCustomer)
        <div class="hub-panel {{ $tab !== 'customer' ? 'hidden' : '' }}" id="hubPanelCustomer">
            @if($customerData && $customerData['selectedThread'])
            @include('hub._partials.customer-thread-panel', [
                'thread'      => $customerData['selectedThread'],
                'messages'    => $customerData['messages'],
                'advisors'    => $customerData['advisors'],
                'quickReplies'=> $customerData['quickReplies'],
                'guestMap'    => $customerData['guestMap'],
            ])
            @else
            <div class="hub-no-select">
                <div><div style="font-size:28px;margin-bottom:8px">📥</div>
                <strong>Danışan seçin</strong></div>
            </div>
            @endif
        </div>
        @endif

        {{-- Ekip paneli --}}
        @if($canInternal)
        <div class="hub-panel {{ $tab !== 'internal' ? 'hidden' : '' }}" id="hubPanelInternal">
            @if($internalData && $internalData['selected'])
            @include('hub._partials.internal-conv-panel', [
                'conv'        => $internalData['selected'],
                'messages'    => $internalData['messages'],
                'currentUser' => $currentUser,
            ])
            @else
            <div class="hub-no-select" style="padding:32px 24px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0;height:100%;">
                <div style="text-align:center;margin-bottom:24px;">
                    <div style="font-size:24px;margin-bottom:6px;">💬</div>
                    <strong style="font-size:14px;display:block;margin-bottom:4px;">Ekip İletişimi</strong>
                    <span style="font-size:12px;color:var(--u-muted);">Konuşma seçin ya da aşağıdan yeni başlatın.</span>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;width:100%;max-width:600px;">
                    <a href="#hubModalDm" style="border:2px solid #86efac;border-radius:10px;padding:16px 12px;background:#f0fdf4;cursor:pointer;text-align:center;display:block;text-decoration:none">
                        <div style="font-size:22px;margin-bottom:6px;">💬</div>
                        <strong style="font-size:12px;color:#15803d;display:block;margin-bottom:4px;">Direkt Mesaj</strong>
                        <p style="font-size:11px;color:#166534;margin:0 0 10px;line-height:1.4;">Bireysel staff mesajı</p>
                        <span class="btn ok" style="font-size:11px;width:100%;padding:4px 0;display:block">+ DM</span>
                    </a>
                    <a href="#hubModalGroup" style="border:2px solid #93c5fd;border-radius:10px;padding:16px 12px;background:#eff6ff;cursor:pointer;text-align:center;display:block;text-decoration:none">
                        <div style="font-size:22px;margin-bottom:6px;">👥</div>
                        <strong style="font-size:12px;color:#1d4ed8;display:block;margin-bottom:4px;">Ekip Grubu</strong>
                        <p style="font-size:11px;color:#1e40af;margin:0 0 10px;line-height:1.4;">Departman veya proje grubu</p>
                        <span class="btn alt" style="font-size:11px;width:100%;padding:4px 0;display:block">+ Grup</span>
                    </a>
                    <a href="#hubModalRoom" style="border:2px solid #fca5a5;border-radius:10px;padding:16px 12px;background:#fef2f2;cursor:pointer;text-align:center;display:block;text-decoration:none">
                        <div style="font-size:22px;margin-bottom:6px;">🏠</div>
                        <strong style="font-size:12px;color:#b91c1c;display:block;margin-bottom:4px;">Tartışma Odası</strong>
                        <p style="font-size:11px;color:#991b1b;margin:0 0 10px;line-height:1.4;">Multidisipliner konu odası</p>
                        <span class="btn" style="font-size:11px;width:100%;padding:4px 0;display:block;background:#e74c3c;color:#fff;border-color:#e74c3c">+ Oda</span>
                    </a>
                </div>
            </div>
            @endif
        </div>
        @endif

    </div>{{-- /hub-right --}}

</div>{{-- /hub-wrap --}}

@if($canInternal)
@include('hub._partials.modals', ['dmableUsers' => $internalData['dmableUsers'] ?? collect()])
@endif

<script defer src="{{ Vite::asset('resources/js/messaging-hub.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function () {
    hubInit();

    // ── Filter inputs ──
    var el;
    el = document.getElementById('hubCustSearchInput');
    if (el) el.addEventListener('input', function () { hubFilterList('customer', this.value); });
    el = document.getElementById('hubIntSearchInput');
    if (el) el.addEventListener('input', function () { hubFilterList('internal', this.value); });

    // ── Modal search inputs ──
    el = document.getElementById('hubDmSearchInput');
    if (el) el.addEventListener('input', function () { hubDmFilter(this.value); });
    el = document.getElementById('hubRoomSearchInput');
    if (el) el.addEventListener('input', function () { hubRoomFilter(this.value); });
    el = document.getElementById('hubGroupSearchInput');
    if (el) el.addEventListener('input', function () { hubGroupFilter(this.value); });

    // ── GIF search ──
    el = document.getElementById('imGifSearchInput');
    if (el) el.addEventListener('input', function () { hubGifSearch(this.value, 'imBodyInput'); });
    el = document.getElementById('hubCGifSearchInput');
    if (el) el.addEventListener('input', function () { hubGifSearch(this.value, 'hubCBodyInput'); });

    // ── Textarea auto-resize + Enter-to-send ──
    el = document.getElementById('imBodyInput');
    if (el) {
        el.addEventListener('input', function () { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 110) + 'px'; });
        el.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); hubImSend(); } });
    }
    el = document.getElementById('hubCBodyInput');
    if (el) {
        el.addEventListener('input', function () { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 110) + 'px'; });
        el.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); hubSendCustomer(); } });
    }

    // ── File inputs ──
    el = document.getElementById('imFileInput');
    if (el) el.addEventListener('change', function () { hubImFileChosen(this); });
    el = document.getElementById('hubCFileInput');
    if (el) el.addEventListener('change', function () { hubCFileChosen(this); });

    // ── + Yeni dropdown menu (DM/Grup/Oda) ──
    var hubNewBtn  = document.getElementById('hubNewConvBtn');
    var hubNewMenu = document.getElementById('hubNewConvMenu');
    if (hubNewBtn && hubNewMenu) {
        hubNewBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            hubNewMenu.style.display = (hubNewMenu.style.display === 'block') ? 'none' : 'block';
        });
        document.addEventListener('click', function (e) {
            if (!hubNewMenu.contains(e.target) && e.target !== hubNewBtn) {
                hubNewMenu.style.display = 'none';
            }
        });
        // Menü içinde modal link'ine tıklanırsa menüyü kapat (modal browser hash navigation ile açılır)
        hubNewMenu.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () { hubNewMenu.style.display = 'none'; });
        });
    }

    // ── Conversation type filter pills (Tümü / DM / Grup / Oda) ──
    var hubFilterPills = document.querySelectorAll('.hub-filter-pill');
    var hubCurFilter = 'all';
    function _hubApplyConvFilter() {
        var items = document.querySelectorAll('#hubInternalList .hub-item');
        items.forEach(function (it) {
            var t = it.dataset.convType || '';
            var match = (hubCurFilter === 'all') || (t === hubCurFilter);
            it.style.display = match ? '' : 'none';
        });
    }
    hubFilterPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            hubCurFilter = this.dataset.convFilter || 'all';
            hubFilterPills.forEach(function (p) {
                var active = (p.dataset.convFilter === hubCurFilter);
                p.classList.toggle('active', active);
                p.style.background = active ? 'var(--u-brand,#4577c4)' : '#fff';
                p.style.color      = active ? '#fff' : 'var(--u-text)';
                p.style.fontWeight = active ? '600' : '400';
            });
            _hubApplyConvFilter();
        });
    });

    // ── Toplu silme / seçim modu ──
    var hubBulkMode = false;
    var hubBulkToggleBtn = document.getElementById('hubBulkToggleBtn');
    var hubBulkBar       = document.getElementById('hubBulkBar');
    var hubBulkCount     = document.getElementById('hubBulkCount');
    var hubBulkDeleteBtn = document.getElementById('hubBulkDeleteBtn');
    var hubBulkCancelBtn = document.getElementById('hubBulkCancelBtn');
    var hubBulkForm      = document.getElementById('hubBulkDestroyForm');

    function _hubBulkSelected() {
        return document.querySelectorAll('#hubInternalList .hub-item.bulk-selected');
    }
    function _hubBulkUpdate() {
        var n = _hubBulkSelected().length;
        if (hubBulkCount) hubBulkCount.textContent = n + ' seçili';
        if (hubBulkBar)   hubBulkBar.style.display = (hubBulkMode && n > 0) ? 'flex' : 'none';
    }
    function _hubBulkExit() {
        hubBulkMode = false;
        document.querySelectorAll('#hubInternalList .hub-item').forEach(function(it){
            it.classList.remove('bulk-selected');
            it.classList.remove('bulk-mode');
        });
        if (hubBulkToggleBtn) {
            hubBulkToggleBtn.textContent = '☑ Seç';
            hubBulkToggleBtn.classList.remove('ok');
            hubBulkToggleBtn.classList.add('alt');
        }
        _hubBulkUpdate();
    }
    if (hubBulkToggleBtn) {
        hubBulkToggleBtn.addEventListener('click', function(){
            hubBulkMode = !hubBulkMode;
            if (hubBulkMode) {
                document.querySelectorAll('#hubInternalList .hub-item').forEach(function(it){
                    it.classList.add('bulk-mode');
                });
                hubBulkToggleBtn.textContent = '✕ Kapat';
                hubBulkToggleBtn.classList.remove('alt');
                hubBulkToggleBtn.classList.add('ok');
            } else {
                _hubBulkExit();
            }
            _hubBulkUpdate();
        });
    }
    if (hubBulkCancelBtn) {
        hubBulkCancelBtn.addEventListener('click', _hubBulkExit);
    }
    if (hubBulkDeleteBtn && hubBulkForm) {
        hubBulkDeleteBtn.addEventListener('click', function(){
            var selected = _hubBulkSelected();
            if (selected.length === 0) return;
            if (!confirm('DİKKAT: Seçilen ' + selected.length + ' konuşma KALICI olarak silinecek. Geri alınamaz. Devam?')) return;

            // Eski hidden input'ları temizle, yenilerini ekle
            hubBulkForm.querySelectorAll('input[name="conversation_ids[]"]').forEach(function(i){ i.remove(); });
            selected.forEach(function(it){
                var id = it.getAttribute('data-hub-conv');
                if (!id) return;
                var hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'conversation_ids[]';
                hid.value = id;
                hubBulkForm.appendChild(hid);
            });
            hubBulkForm.submit();
        });
    }

    // ── Global delegated click handler ──
    document.addEventListener('click', function (e) {
        var t;

        // Selection mode aktifken hub-item'a tıklama → select toggle (aç değil)
        if (hubBulkMode) {
            t = e.target.closest('#hubInternalList .hub-item');
            if (t) {
                e.preventDefault();
                e.stopPropagation();
                t.classList.toggle('bulk-selected');
                _hubBulkUpdate();
                return;
            }
        }

        t = e.target.closest('[data-hub-tab]');
        if (t) { hubSwitchTab(t.dataset.hubTab); return; }

        t = e.target.closest('[data-hub-modal]');
        if (t) { hubOpenModal(t.dataset.hubModal); return; }

        t = e.target.closest('[data-hub-close]');
        if (t) { hubCloseModal(t.dataset.hubClose); return; }

        t = e.target.closest('[data-hub-thread]');
        if (t) { hubSelectThread(parseInt(t.dataset.hubThread)); return; }

        t = e.target.closest('[data-hub-conv]');
        if (t) { hubSelectConv(parseInt(t.dataset.hubConv)); return; }

        t = e.target.closest('[data-hub-pin]');
        if (t) { hubImPin(parseInt(t.dataset.hubPin)); return; }

        t = e.target.closest('[data-hub-mute]');
        if (t) { hubImMute(parseInt(t.dataset.hubMute)); return; }

        t = e.target.closest('[data-hub-del]');
        if (t) { hubImDelete(parseInt(t.dataset.hubDel), t); return; }

        t = e.target.closest('[data-hub-reply]');
        if (t) { hubImSetReply(parseInt(t.dataset.hubReply), t.dataset.hubReplySender || '', ''); return; }

        t = e.target.closest('[data-hub-picker]');
        if (t) { hubTogglePicker(t.dataset.hubPicker, t.dataset.hubPickerTarget || 'imBodyInput'); return; }

        t = e.target.closest('[data-hub-quick]');
        if (t) { hubQuickReply(parseInt(t.dataset.hubQuick)); return; }

        if (e.target.id === 'imClearReplyBtn' || e.target.closest('#imClearReplyBtn')) { hubImClearReply(); return; }
        if (e.target.id === 'hubCClearReplyBtn' || e.target.closest('#hubCClearReplyBtn')) { hubClearReply(); return; }
        if (e.target.id === 'imSendBtn' || e.target.closest('#imSendBtn')) { hubImSend(); return; }
        if (e.target.id === 'hubCSendBtn' || e.target.closest('#hubCSendBtn')) { hubSendCustomer(); return; }
        if (e.target.id === 'hubStartDmBtn' || e.target.closest('#hubStartDmBtn')) { hubStartDm(); return; }
        if (e.target.id === 'hubCreateRoomBtn' || e.target.closest('#hubCreateRoomBtn')) { hubCreateRoom(); return; }
        if (e.target.id === 'hubCreateGroupBtn' || e.target.closest('#hubCreateGroupBtn')) { hubCreateGroup(); return; }
    });
});
</script>
@endsection

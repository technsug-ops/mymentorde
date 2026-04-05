@extends($layout)
@section('title', 'İletişim Merkezi')
@section('page_title', '💬 İletişim Merkezi')

@push('head')
<style>
/* ── Portal padding override ── */
.main { padding-bottom: 0 !important; }
/* ── Conversation list panel ── */
.im-conv-item { padding:10px 12px;cursor:pointer;border-bottom:1px solid var(--u-line);display:flex;gap:8px;align-items:flex-start;transition:background .15s; }
.im-conv-item:hover { background:#f5f8ff; }
.im-conv-item.active { background:#eef4fb; }
.im-conv-item.pinned { border-left:3px solid var(--u-brand,#4577c4); }
.im-conv-item.muted { opacity:.55; }
.im-conv-avatar { width:36px;height:36px;border-radius:50%;background:var(--u-brand,#4577c4);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0; }
.im-conv-meta { flex:1;min-width:0; }
.im-conv-meta strong { display:block;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.im-conv-preview { font-size:12px;color:var(--u-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px; }
.im-conv-right { display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0; }
.im-conv-time { font-size:11px;color:var(--u-muted); }
.im-unread-badge { background:var(--u-brand,#4577c4);color:#fff;border-radius:10px;padding:1px 6px;font-size:11px;font-weight:700; }
/* ── Chat bubbles (same as student/messages) ── */
.chat-list { display:flex;flex-direction:column;gap:8px;max-height:520px;overflow-y:auto;padding:4px 2px;margin-bottom:12px; }
.bubble-wrap { display:flex;flex-direction:column; }
.bubble-wrap.mine   { align-items:flex-end; }
.bubble-wrap.theirs { align-items:flex-start; }
.bubble-wrap.system { align-items:center; }
.bubble { max-width:78%;padding:10px 14px;border-radius:16px;font-size:13px;line-height:1.55;word-break:break-word;white-space:pre-wrap; }
.bubble-wrap.mine   .bubble { background:var(--u-brand,#4577c4);color:#fff;border-bottom-right-radius:4px; }
.bubble-wrap.theirs .bubble { background:#f0f4f9;color:var(--u-text);border-bottom-left-radius:4px; }
.bubble-wrap.system .bubble { background:#f0f0f0;border-radius:8px;font-size:12px;color:#666;padding:4px 10px;max-width:90%; }
.bubble-meta { display:flex;align-items:center;gap:5px;margin-top:3px;font-size:11px;color:var(--u-muted); }
.bubble-wrap.mine .bubble-meta { flex-direction:row-reverse; }
.bubble-sender { font-size:11px;font-weight:700;color:var(--u-brand,#4577c4);margin-bottom:3px; }
.bubble-deleted { opacity:.6;font-style:italic; }
.im-attach-preview { display:flex;align-items:center;gap:6px;border-radius:6px;padding:4px 8px;font-size:12px;margin-top:4px;background:rgba(255,255,255,.22); }
.bubble-wrap.theirs .im-attach-preview { background:#e8eff8; }
.im-attach-preview a { color:inherit;text-decoration:underline; }
.im-reply-preview { border-left:3px solid rgba(255,255,255,.6);padding:4px 8px;border-radius:4px;font-size:11px;margin-bottom:6px;background:rgba(255,255,255,.18); }
.bubble-wrap.theirs .im-reply-preview { background:#e0eaf8;border-left-color:var(--u-brand,#4577c4);color:#555; }
/* ── Reply bar ── */
.im-reply-bar { padding:6px 10px;background:#f0f4ff;border-radius:6px;font-size:12px;color:#444;display:flex;align-items:center;gap:8px;margin-bottom:8px; }
.im-reply-bar button { background:none;border:none;cursor:pointer;color:#999;font-size:16px;line-height:1; }
/* ── Composer ── */
.composer-panel { position:sticky;bottom:34px;overflow:hidden; }
.file-pick-row { display:flex;align-items:center;gap:6px;margin-top:8px;flex-wrap:wrap; }
.file-pick-name { font-size:12px;color:var(--u-muted); }
/* ── Emoji + GIF Picker ── */
.im-picker-wrap { position:relative;display:inline-flex;gap:2px;align-items:center;flex-shrink:0; }
.im-picker-btn { background:none !important;border:none !important;cursor:pointer;font-size:17px !important;padding:4px 6px !important;border-radius:6px !important;color:#888;min-height:0 !important;height:32px !important;width:32px !important;display:inline-flex !important;align-items:center !important;justify-content:center !important; }
.im-picker-btn:hover { background:#f0f4ff !important;color:#4577c4; }
.im-emoji-picker,.im-gif-picker { position:absolute;bottom:calc(100% + 8px);left:0;z-index:9000;background:#fff;border:1px solid var(--u-line);border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,.18);display:none; }
.im-emoji-picker.open,.im-gif-picker.open { display:block; }
.im-emoji-picker { width:290px;padding:8px; }
.im-emoji-cats { display:flex;gap:3px;margin-bottom:6px;flex-wrap:wrap; }
.im-emoji-cats button { background:none !important;border:none !important;cursor:pointer;font-size:15px !important;padding:2px 5px !important;border-radius:5px !important;opacity:.6;min-height:0 !important;line-height:1.2 !important; }
.im-emoji-cats button.active,.im-emoji-cats button:hover { background:#eef4fb !important;opacity:1; }
.im-emoji-grid { display:flex;flex-wrap:wrap;gap:1px;max-height:200px;overflow-y:auto; }
.im-emoji-grid button { font-size:20px !important;background:none !important;border:none !important;cursor:pointer;padding:4px !important;border-radius:5px !important;line-height:1 !important;min-height:0 !important;height:34px !important;width:34px !important; }
.im-emoji-grid button:hover { background:#f0f4ff !important; }
.im-gif-picker { width:290px; }
.im-gif-search { padding:7px 8px;border-bottom:1px solid var(--u-line); }
.im-gif-search input { width:100%;padding:6px 9px !important;border:1px solid var(--u-line);border-radius:6px;font-size:12px;box-sizing:border-box;min-height:0 !important; }
.im-gif-grid { display:grid;grid-template-columns:1fr 1fr;gap:4px;padding:6px;max-height:230px;overflow-y:auto; }
.im-gif-grid img { width:100%;height:75px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid transparent; }
.im-gif-grid img:hover { border-color:var(--u-brand,#4577c4); }
.im-gif-loading { text-align:center;padding:16px;color:var(--u-muted);font-size:12px; }
/* ── Modal ── */
.im-modal-bg { display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:9998;align-items:center;justify-content:center; }
.im-modal-bg.open { display:flex; }
.im-modal { background:#fff;border-radius:10px;padding:24px;width:460px;max-width:95vw;z-index:9999;max-height:80vh;overflow-y:auto; }
.im-modal h3 { margin:0 0 16px;font-size:16px; }
.im-modal .field { margin-bottom:12px; }
.im-modal label { display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555; }
.im-modal input,.im-modal select,.im-modal textarea { width:100%;padding:7px 10px;border:1px solid var(--u-line);border-radius:6px;font-size:13px;box-sizing:border-box; }
.im-modal-actions { display:flex;gap:8px;justify-content:flex-end;margin-top:16px; }
.im-multi-select { border:1px solid var(--u-line);border-radius:8px;max-height:200px;overflow-y:auto; }
.im-user-row { display:flex !important;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--u-line);transition:background .12s; }
.im-user-row:last-child { border-bottom:none; }
.im-user-row:hover { background:#f5f8ff; }
.im-user-row input[type=radio],.im-user-row input[type=checkbox] { flex-shrink:0;width:16px;height:16px;cursor:pointer;accent-color:var(--u-brand,#4577c4); }
.im-user-avatar-sm { width:32px;height:32px;border-radius:50%;background:var(--u-brand,#4577c4);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0; }
.im-user-name { flex:1;font-size:13px;font-weight:500;color:var(--u-text);min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.im-role-chip { font-size:10px;background:#eef4fb;color:#3a6db5;border-radius:4px;padding:2px 6px;white-space:nowrap;flex-shrink:0; }
</style>
@endpush

@section('content')

<section class="panel" style="margin-bottom:16px;padding:12px 16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div>
            <strong style="font-size:15px;">İletişim Merkezi</strong>
            <span class="muted" style="font-size:12px;margin-left:8px;">Staff · dahili konuşmalar</span>
        </div>
        <div style="display:flex;gap:6px;">
            <button class="btn ok" onclick="imOpenModal('dm')" style="font-size:12px;">💬 Sohbet</button>
            <button class="btn alt" onclick="imOpenModal('group')" style="font-size:12px;">👥 Grup</button>
            <button class="btn" onclick="imOpenModal('room')" style="font-size:12px;background:#7c3aed;color:#fff;border-color:#7c3aed;">🏠 Oda</button>
        </div>
    </div>
</section>

{{-- Data bridge --}}
<script>
window.__im = {
    currentUserId: {{ (int) $currentUser->id }},
    selectedConvId: {{ $selected ? (int) $selected->id : 'null' }},
    pollInterval: 10000,
    routes: {
        send: '/im/conversations/__ID__/send',
        read: '/im/conversations/__ID__/read',
        poll: '/im/conversations/__ID__/poll',
        dmStart: '/im/dm/__UID__',
        deleteMsg: '/im/messages/__MID__',
        mute: '/im/conversations/__ID__/mute',
        pin: '/im/conversations/__ID__/pin',
    },
    csrf: '{{ csrf_token() }}'
};
</script>

<div style="display:grid;grid-template-columns:300px 1fr;gap:16px;align-items:start">

    {{-- Sol: Konuşma Listesi --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:10px 12px;border-bottom:1px solid var(--u-line);">
            <strong style="font-size:12px;color:var(--u-muted);text-transform:uppercase;letter-spacing:.4px;">Konuşmalar</strong>
            <input type="text" id="imSearch" placeholder="Ara…" oninput="imFilterConvs(this.value)"
                   style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:6px 8px;font-size:12px;box-sizing:border-box;margin-top:6px;">
        </div>
        <div id="imConvList" style="max-height:calc(100vh - 240px);overflow-y:auto;">
            @php
                $dmConvs    = $conversations->where('type', 'direct');
                $groupConvs = $conversations->whereIn('type', ['group','announcement']);
                $roomConvs  = $conversations->where('type', 'room');
            @endphp

            {{-- Sohbetler (DM) --}}
            @php
                $dmPartnerIds = $dmConvs->map(fn($c) => $c->participantUsers->firstWhere('id', '!=', $currentUser->id)?->id)->filter()->unique()->values()->all();
                $imPresenceMap = \App\Services\PresenceService::getBulkPresence($dmPartnerIds);
                $presenceColors = ['online'=>'#16a34a','away'=>'#d97706','busy'=>'#dc2626','offline'=>'#9ca3af'];
            @endphp
            <div style="padding:5px 12px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--u-muted);background:#fafbfc;border-bottom:1px solid var(--u-line);">
                💬 Sohbetler
            </div>
            @forelse($dmConvs as $conv)
            @php
                $part        = $conv->participants->first();
                $isMuted     = $part?->is_muted;
                $isPinned    = $part?->is_pinned;
                $unread      = $unreadMap[$conv->id] ?? 0;
                $display     = $conv->getDisplayTitle($currentUser->id);
                $avatar      = strtoupper(substr($display, 0, 1));
                $isActive    = $selected && $selected->id === $conv->id;
                $partnerId   = $conv->participantUsers->firstWhere('id', '!=', $currentUser->id)?->id;
                $pres        = $partnerId ? ($imPresenceMap[$partnerId] ?? null) : null;
                $presColor   = $presenceColors[$pres['status'] ?? 'offline'] ?? '#9ca3af';
            @endphp
            <div class="im-conv-item {{ $isActive ? 'active' : '' }} {{ $isPinned ? 'pinned' : '' }} {{ $isMuted ? 'muted' : '' }}"
                 data-conv-id="{{ $conv->id }}"
                 data-title="{{ strtolower($display) }}"
                 onclick="imSelectConv({{ $conv->id }})">
                <div class="im-conv-avatar" style="background:var(--u-ok,#22a55b);position:relative;">{{ $avatar }}
                    <span style="position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;border:2px solid var(--u-card,#fff);background:{{ $presColor }};"></span>
                </div>
                <div class="im-conv-meta">
                    <strong>{{ $display }}</strong>
                    <div class="im-conv-preview">{{ $conv->last_message_preview ?? 'Henüz mesaj yok' }}</div>
                </div>
                <div class="im-conv-right">
                    <span class="im-conv-time">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true) : '' }}</span>
                    @if($unread > 0)<span class="im-unread-badge">{{ $unread }}</span>@endif
                    @if($isPinned)<span style="font-size:12px" title="Sabitlenmiş">📌</span>@endif
                </div>
            </div>
            @empty
            <div style="padding:10px 14px;text-align:center;">
                <div style="font-size:12px;color:var(--u-muted);">Henüz sohbet yok.</div>
                <button onclick="imOpenModal('dm')" class="btn ok" style="font-size:11px;padding:4px 10px;margin-top:6px;">+ Başlat</button>
            </div>
            @endforelse

            {{-- Ekip Grupları --}}
            <div style="padding:5px 12px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--u-muted);background:#fafbfc;border-top:1px solid var(--u-line);border-bottom:1px solid var(--u-line);">
                👥 Ekip Grupları
            </div>
            @forelse($groupConvs as $conv)
            @php
                $part     = $conv->participants->first();
                $isMuted  = $part?->is_muted;
                $isPinned = $part?->is_pinned;
                $unread   = $unreadMap[$conv->id] ?? 0;
                $display  = $conv->getDisplayTitle($currentUser->id);
                $avatar   = strtoupper(substr($display, 0, 1));
                $isActive = $selected && $selected->id === $conv->id;
                $typeIcon = $conv->type === 'announcement' ? '📢 ' : '👥 ';
            @endphp
            <div class="im-conv-item {{ $isActive ? 'active' : '' }} {{ $isPinned ? 'pinned' : '' }} {{ $isMuted ? 'muted' : '' }}"
                 data-conv-id="{{ $conv->id }}"
                 data-title="{{ strtolower($display) }}"
                 onclick="imSelectConv({{ $conv->id }})">
                <div class="im-conv-avatar">{{ $avatar }}</div>
                <div class="im-conv-meta">
                    <strong>{{ $typeIcon }}{{ $display }}</strong>
                    <div class="im-conv-preview">{{ $conv->last_message_preview ?? 'Henüz mesaj yok' }}</div>
                </div>
                <div class="im-conv-right">
                    <span class="im-conv-time">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true) : '' }}</span>
                    @if($unread > 0)<span class="im-unread-badge">{{ $unread }}</span>@endif
                    @if($isPinned)<span style="font-size:12px" title="Sabitlenmiş">📌</span>@endif
                </div>
            </div>
            @empty
            <div style="padding:10px 14px;text-align:center;">
                <div style="font-size:12px;color:var(--u-muted);">Henüz grup yok.</div>
                <button onclick="imOpenModal('group')" class="btn alt" style="font-size:11px;padding:4px 10px;margin-top:6px;">+ Grup Kur</button>
            </div>
            @endforelse

            {{-- Sohbet Odaları --}}
            <div style="padding:5px 12px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6d28d9;background:#faf5ff;border-top:1px solid #e9d8fd;border-bottom:1px solid #e9d8fd;">
                🏠 Sohbet Odaları
            </div>
            @forelse($roomConvs as $conv)
            @php
                $part     = $conv->participants->first();
                $isMuted  = $part?->is_muted;
                $isPinned = $part?->is_pinned;
                $unread   = $unreadMap[$conv->id] ?? 0;
                $display  = $conv->getDisplayTitle($currentUser->id);
                $avatar   = strtoupper(substr($display, 0, 1));
                $isActive = $selected && $selected->id === $conv->id;
            @endphp
            <div class="im-conv-item {{ $isActive ? 'active' : '' }} {{ $isPinned ? 'pinned' : '' }} {{ $isMuted ? 'muted' : '' }}"
                 data-conv-id="{{ $conv->id }}"
                 data-title="{{ strtolower($display) }}"
                 onclick="imSelectConv({{ $conv->id }})">
                <div class="im-conv-avatar" style="background:#7c3aed;">{{ $avatar }}</div>
                <div class="im-conv-meta">
                    <strong>🏠 {{ $display }}</strong>
                    @if($conv->context_type)<div style="font-size:10px;color:#7c3aed;margin-top:1px;">{{ $conv->context_type }}</div>@endif
                    <div class="im-conv-preview">{{ $conv->last_message_preview ?? 'Henüz mesaj yok' }}</div>
                </div>
                <div class="im-conv-right">
                    <span class="im-conv-time">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true) : '' }}</span>
                    @if($unread > 0)<span class="im-unread-badge" style="background:#7c3aed;">{{ $unread }}</span>@endif
                    @if($isPinned)<span style="font-size:12px" title="Sabitlenmiş">📌</span>@endif
                </div>
            </div>
            @empty
            <div style="padding:10px 14px;text-align:center;">
                <div style="font-size:12px;color:var(--u-muted);">Henüz oda yok.</div>
                <button onclick="imOpenModal('room')" class="btn" style="font-size:11px;padding:4px 10px;margin-top:6px;background:#7c3aed;color:#fff;border-color:#7c3aed;">+ Oda Aç</button>
            </div>
            @endforelse
        </div>
    </section>

    {{-- Sağ: Thread --}}
    @if($selected)
    @php
        $displayTitle = $selected->getDisplayTitle($currentUser->id);
        $selPart = $selected->participants->firstWhere('user_id', $currentUser->id);
        $isMuted  = $selPart?->is_muted;
        $isPinned = $selPart?->is_pinned;
    @endphp
    <section class="panel composer-panel">
    <div style="max-width:780px;margin:0 auto;">
        {{-- Thread header --}}
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line);">
            <div>
                <strong style="font-size:14px;">{{ $displayTitle }}</strong>
                <div class="muted" style="font-size:11px;margin-top:2px;">
                    @if($selected->type === 'group')
                        <span class="badge info">👥 Ekip Grubu</span> · {{ $selected->participantUsers->count() }} kişi
                        @if($selected->context_type) · 🔗 {{ $selected->context_type }}:{{ $selected->context_id }}@endif
                    @elseif($selected->type === 'room')
                        <span style="background:#f3eeff;color:#6d28d9;border-radius:4px;padding:2px 7px;font-size:10px;font-weight:700;">🏠 Sohbet Odası</span>
                        @if($selected->context_type) · <span style="color:#7c3aed;font-weight:600;">{{ $selected->context_type }}</span>@endif
                        · {{ $selected->participantUsers->count() }} kişi
                    @elseif($selected->type === 'announcement')
                        <span class="badge warn">📢 Duyuru Kanalı</span>
                    @else
                        <span class="badge ok">💬 Sohbet</span> · Bireysel mesajlaşma
                    @endif
                </div>
            </div>
            <div style="display:flex;gap:4px;align-items:center;">
                @if(in_array($selected->type, ['group','room']))
                <button type="button" onclick="imToggleMembers()" title="Üyeleri yönet"
                    style="background:none;border:1px solid var(--u-line);border-radius:6px;cursor:pointer;padding:4px 8px;font-size:13px;color:{{ $selected->type === 'room' ? '#7c3aed' : 'var(--u-brand,#4577c4)' }};">
                    {{ $selected->type === 'room' ? '🏠' : '👥' }}
                </button>
                @endif
                <form method="POST" action="/im/conversations/{{ $selected->id }}/pin" style="display:contents;">
                    @csrf
                    <button type="submit" title="{{ $isPinned ? 'Sabitlemeyi kaldır' : 'Sabitle' }}"
                        style="background:none;border:1px solid var(--u-line);border-radius:6px;cursor:pointer;padding:4px 8px;font-size:13px;color:var(--u-muted);">
                        📌
                    </button>
                </form>
                <form method="POST" action="/im/conversations/{{ $selected->id }}/mute" style="display:contents;">
                    @csrf
                    <button type="submit" title="{{ $isMuted ? 'Bildirimleri aç' : 'Sustur' }}"
                        style="background:none;border:1px solid var(--u-line);border-radius:6px;cursor:pointer;padding:4px 8px;font-size:13px;color:var(--u-muted);">
                        {{ $isMuted ? '🔔' : '🔕' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Üye Yönetim Paneli (group ve room) --}}
        @if(in_array($selected->type, ['group','room']))
        @php
            $groupMembers   = $selected->participantUsers ?? collect();
            $memberIds      = $groupMembers->pluck('id')->all();
            $nonMembers     = $dmableUsers->whereNotIn('id', $memberIds);
            $myGroupPart    = $selected->participants->firstWhere('user_id', $currentUser->id);
            $canManageMembers = $myGroupPart?->role === 'admin'
                || in_array($currentUser->role, ['manager','system_admin']);
        @endphp
        @php
            $memberPanelBg     = $selected->type === 'room' ? '#faf5ff' : '#f8fafe';
            $memberPanelBorder = $selected->type === 'room' ? '#e9d8fd' : 'var(--u-line)';
            $memberPanelIcon   = $selected->type === 'room' ? '🏠' : '👥';
            $memberPanelLabel  = $selected->type === 'room' ? 'Oda Katılımcıları' : 'Grup Üyeleri';
        @endphp
        <div id="imMembersPanel" style="display:none;background:{{ $memberPanelBg }};border:1px solid {{ $memberPanelBorder }};border-radius:8px;padding:12px 14px;margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <strong style="font-size:13px;">{{ $memberPanelIcon }} {{ $memberPanelLabel }}</strong>
                <span class="badge info">{{ $groupMembers->count() }} kişi</span>
            </div>

            {{-- Mevcut üyeler --}}
            <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:12px;max-height:200px;overflow-y:auto;">
                @foreach($groupMembers as $gm)
                @php
                    $gmPart = $selected->participants->firstWhere('user_id', $gm->id);
                    $isSelf = (int)$gm->id === (int)$currentUser->id;
                @endphp
                <div style="display:flex;align-items:center;gap:8px;padding:6px 8px;background:#fff;border-radius:6px;border:1px solid #eaeff8;">
                    <div style="width:28px;height:28px;border-radius:50%;background:var(--u-brand,#4577c4);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">{{ strtoupper(substr($gm->name,0,1)) }}</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $gm->name }}
                            @if($isSelf)<span style="font-size:10px;color:var(--u-muted);"> (ben)</span>@endif
                        </div>
                        <div style="font-size:10px;color:var(--u-muted);">{{ str_replace('_',' ',$gm->role) }}@if($gmPart?->role === 'admin') · <strong>Admin</strong>@endif</div>
                    </div>
                    @if($canManageMembers || $isSelf)
                    <form method="POST" action="/im/conversations/{{ $selected->id }}/members/{{ $gm->id }}/remove">
                        @csrf
                        <button type="submit" class="btn warn" style="font-size:11px;padding:3px 8px !important;"
                                onclick="return confirm('{{ $isSelf ? 'Gruptan ayrılmak istediğinize emin misiniz?' : $gm->name . ' gruptan çıkarılsın mı?' }}')"
                                title="{{ $isSelf ? 'Gruptan ayrıl' : 'Çıkar' }}">
                            {{ $isSelf ? 'Ayrıl' : '✕' }}
                        </button>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>

            {{-- Üye Ekle --}}
            @if($canManageMembers && $nonMembers->count() > 0)
            <form method="POST" action="/im/conversations/{{ $selected->id }}/members" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                @csrf
                <select name="user_id" style="flex:1;min-width:150px;border:1px solid var(--u-line);border-radius:6px;padding:6px 8px;font-size:13px;">
                    <option value="">— Kişi seçin —</option>
                    @foreach($nonMembers as $nm)
                    <option value="{{ $nm->id }}">{{ $nm->name }} ({{ str_replace('_',' ',$nm->role) }})</option>
                    @endforeach
                </select>
                <button type="submit" class="btn ok" style="font-size:12px;padding:6px 12px !important;">+ Ekle</button>
            </form>
            @elseif($canManageMembers)
            <div style="font-size:12px;color:var(--u-muted);text-align:center;">Tüm ekip üyeleri zaten grupta.</div>
            @endif
        </div>
        @endif

        {{-- Mesajlar --}}
        <div class="chat-list" id="imMessages">
            @forelse($messages as $msg)
            @php
                $isMine   = (int)($msg->sender_id ?? 0) === (int)$currentUser->id;
                $isSystem = $msg->is_system;
                $wrapClass = $isSystem ? 'system' : ($isMine ? 'mine' : 'theirs');
                $senderName = $msg->sender?->name ?? 'Sistem';
                $bodyText = $msg->getDisplayBody();
            @endphp
            <div class="bubble-wrap {{ $wrapClass }}" id="msg-{{ $msg->id }}" data-msg-id="{{ $msg->id }}">
                @if(!$isMine && !$isSystem)
                <div class="bubble-sender">{{ $senderName }}</div>
                @endif
                <div class="bubble {{ $msg->trashed() ? 'bubble-deleted' : '' }}">
                    @if($msg->replyTo)
                    <div class="im-reply-preview">↩ {{ $msg->replyTo->sender?->name ?? 'Sistem' }}: {{ Str::limit($msg->replyTo->body ?? '(silindi)', 60) }}</div>
                    @endif
                    @if(str_starts_with($bodyText ?? '', '[gif]:'))
                        <img src="{{ e(substr($bodyText, 6)) }}" alt="GIF" loading="lazy" style="max-width:100%;border-radius:6px;display:block">
                    @else
                        {!! nl2br(e($bodyText)) !!}
                    @endif
                    @if($msg->hasAttachment() && !$msg->trashed())
                    <div class="im-attach-preview">
                        📎 <a href="/im/messages/{{ $msg->id }}/download" target="_blank">{{ $msg->attachment_name }}</a>
                        @if($msg->attachment_size)<span style="opacity:.7">({{ number_format($msg->attachment_size/1024,0) }} KB)</span>@endif
                    </div>
                    @endif
                    @if($msg->is_edited && !$msg->trashed())<div style="font-size:10px;opacity:.7;font-style:italic;margin-top:2px;">(düzenlendi)</div>@endif
                </div>
                <div class="bubble-meta">
                    <span>{{ $msg->created_at?->format('H:i') }}</span>
                    @if(!$isSystem && !$msg->trashed() && ((int)($msg->sender_id??0) === (int)$currentUser->id || in_array($currentUser->role, ['manager','system_admin'])))
                    <button onclick="imDeleteMsg({{ $msg->id }},this)" style="background:none;border:none;cursor:pointer;font-size:10px;color:#bbb;padding:0">🗑</button>
                    @endif
                    @if(!$isSystem && !$msg->trashed() && !$isMine)
                    <button onclick="imSetReply({{ $msg->id }},'{{ addslashes($senderName) }}','{{ addslashes(Str::limit($msg->body??'',60)) }}')" style="background:none;border:none;cursor:pointer;font-size:10px;color:#bbb;padding:0">↩</button>
                    @endif
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:48px 0;color:var(--u-muted);">Henüz mesaj yok. İlk mesajı sen gönder!</div>
            @endforelse
        </div>

        {{-- Reply bar --}}
        <div class="im-reply-bar" id="imReplyBar" style="display:none;">
            <span id="imReplyText">↩ ...</span>
            <button onclick="imClearReply()">✕</button>
        </div>

        {{-- Input --}}
        @php $canSend = $selected->type !== 'announcement' || ($selPart?->role === 'admin'); @endphp
        @if($canSend)
        <form id="imSendForm" method="POST" action="/im/conversations/{{ $selected->id }}/send" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="reply_to" id="imReplyToInput" value="">
            <input type="file" id="imFileInput" name="attachment" style="display:none" onchange="imFileChosen(this)">
            <textarea id="imBodyInput" name="body" placeholder="Mesaj yaz…" rows="3"
                      style="width:100%;border:1px solid var(--u-line);border-radius:8px;padding:8px;resize:vertical;"
                      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();imSendForm()}"
                      oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px'"></textarea>
            <div class="file-pick-row">
                <div class="im-picker-wrap">
                    <label for="imFileInput" title="Dosya ekle" style="cursor:pointer;padding:4px 6px;font-size:18px;color:#888;display:inline-flex;align-items:center;border-radius:6px;">📎</label>
                    <button type="button" class="im-picker-btn" onclick="imTogglePicker('emoji','imBodyInput')" title="Emoji">😊</button>
                    <button type="button" class="im-picker-btn" onclick="imTogglePicker('gif','imBodyInput')" title="GIF" style="font-size:12px !important;font-weight:700">GIF</button>
                    <div class="im-emoji-picker" id="imEmojiPicker_imBodyInput">
                        <div class="im-emoji-cats" id="imEmojiCats_imBodyInput"></div>
                        <div class="im-emoji-grid" id="imEmojiGrid_imBodyInput"></div>
                    </div>
                    <div class="im-gif-picker" id="imGifPicker_imBodyInput">
                        <div class="im-gif-search"><input type="text" placeholder="🔍 GIF ara..." oninput="imGifSearch(this.value,'imBodyInput')"></div>
                        <div class="im-gif-grid" id="imGifGrid_imBodyInput"><div class="im-gif-loading">Yükleniyor...</div></div>
                    </div>
                </div>
                <span id="imFileLabel" class="file-pick-name" style="display:none;"></span>
                <button class="btn" type="button" onclick="imSendForm()" style="margin-left:auto;">Gönder ➤</button>
            </div>
        </form>
        @else
        <div style="text-align:center;color:var(--u-muted);font-size:13px;padding:12px;">
            Bu duyuru kanalına yalnızca yöneticiler mesaj gönderebilir.
        </div>
        @endif
    </div>{{-- /max-width wrapper --}}
    </section>

    @else
    <section class="panel" style="min-height:420px;padding:28px 24px;">
        <div style="text-align:center;margin-bottom:28px;">
            <div style="font-size:28px;margin-bottom:8px;">💬</div>
            <strong style="font-size:15px;display:block;margin-bottom:4px;">İletişim Merkezi</strong>
            <span class="muted" style="font-size:12px;">Dahili staff iletişiminizi buradan yönetin. Aşağıdan başlatmak istediğiniz konuşma türünü seçin.</span>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;max-width:720px;margin:0 auto;">

            {{-- DM --}}
            <div style="border:2px solid #86efac;border-radius:12px;padding:20px;background:#f0fdf4;cursor:pointer;" onclick="imOpenModal('dm')">
                <div style="font-size:28px;margin-bottom:10px;">💬</div>
                <strong style="font-size:14px;color:#15803d;display:block;margin-bottom:6px;">Direkt Mesaj</strong>
                <p style="font-size:12px;color:#166534;margin:0 0 14px;line-height:1.5;">Herhangi bir staff üyesine bireysel, özel mesaj gönderin. Hızlı ve doğrudan iletişim için idealdir.</p>
                <button class="btn ok" style="font-size:12px;width:100%;" onclick="event.stopPropagation();imOpenModal('dm')">💬 Sohbet Başlat</button>
            </div>

            {{-- Grup --}}
            <div style="border:2px solid #93c5fd;border-radius:12px;padding:20px;background:#eff6ff;cursor:pointer;" onclick="imOpenModal('group')">
                <div style="font-size:28px;margin-bottom:10px;">👥</div>
                <strong style="font-size:14px;color:#1d4ed8;display:block;margin-bottom:6px;">Ekip Grubu</strong>
                <p style="font-size:12px;color:#1e40af;margin:0 0 14px;line-height:1.5;">Kendi ekibinizdeki üyelerle grup konuşması kurun. Departman, proje veya görev bazlı ekip grupları oluşturabilirsiniz.</p>
                <button class="btn alt" style="font-size:12px;width:100%;" onclick="event.stopPropagation();imOpenModal('group')">👥 Grup Kur</button>
            </div>

            {{-- Oda --}}
            <div style="border:2px solid #c4b5fd;border-radius:12px;padding:20px;background:#f5f3ff;cursor:pointer;" onclick="imOpenModal('room')">
                <div style="font-size:28px;margin-bottom:10px;">🏠</div>
                <strong style="font-size:14px;color:#6d28d9;display:block;margin-bottom:6px;">Tartışma Odası</strong>
                <p style="font-size:12px;color:#5b21b6;margin:0 0 14px;line-height:1.5;">Farklı ekip ve departmanlardan kişileri belirli bir konu etrafında bir araya getirin. Multidisipliner, firma içi tartışma odaları oluşturun.</p>
                <button class="btn" style="font-size:12px;width:100%;background:#7c3aed;color:#fff;border-color:#7c3aed;" onclick="event.stopPropagation();imOpenModal('room')">🏠 Oda Aç</button>
            </div>

        </div>

        @if($conversations->isNotEmpty())
        <div style="text-align:center;margin-top:24px;font-size:12px;color:var(--u-muted);">
            veya soldan mevcut bir konuşmayı seçin
        </div>
        @endif
    </section>
    @endif

</div>

{{-- Sohbet (DM) Modal --}}
<div class="im-modal-bg" id="imModalDm">
    <div class="im-modal">
        <h3>💬 Sohbet Başlat</h3>
        <div style="background:#f0faf5;border:1px solid #c3e6d2;border-radius:6px;padding:8px 12px;font-size:12px;color:#2a6e45;margin-bottom:12px;">
            Herhangi bir staff üyesine bireysel mesaj gönderin. Departman veya rol fark etmez.
        </div>
        <input type="text" id="imDmSearch" placeholder="🔍 İsim ile ara…" oninput="imDmFilter(this.value)"
               style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:8px 10px;font-size:13px;box-sizing:border-box;margin-bottom:8px;">
        <div class="im-multi-select" id="imDmUserList">
            @foreach($dmableUsers as $du)
            @php $initial = strtoupper(substr($du->name, 0, 1)); @endphp
            <label class="im-user-row" data-name="{{ strtolower($du->name) }}">
                <input type="radio" name="dm_user" value="{{ $du->id }}">
                <div class="im-user-avatar-sm" style="background:var(--u-ok,#22a55b);">{{ $initial }}</div>
                <span class="im-user-name">{{ $du->name }}</span>
                <span class="im-role-chip">{{ str_replace('_', ' ', $du->role) }}</span>
            </label>
            @endforeach
        </div>
        <div class="im-modal-actions">
            <button class="btn" onclick="imCloseModal('dm')">İptal</button>
            <button class="btn ok" onclick="imStartDm()">Sohbet Başlat</button>
        </div>
    </div>
</div>

{{-- Ekip Grubu Modal --}}
<div class="im-modal-bg" id="imModalGroup">
    <div class="im-modal">
        <h3>👥 Ekip Grubu Kur</h3>
        <div style="background:#eef4fb;border:1px solid #c8daf5;border-radius:6px;padding:8px 12px;font-size:12px;color:#1d4280;margin-bottom:12px;">
            Kendi ekibinizdeki üyelerle ortak bir grup konuşması başlatın. Proje, departman veya görev bazlı gruplar oluşturabilirsiniz.
        </div>
        <form method="POST" action="/im/group" id="imGroupForm">
            @csrf
            <div class="field">
                <label>Grup Adı *</label>
                <input type="text" name="title" placeholder="Örn: Operations Ekibi, Kampanya Q2…" required maxlength="190">
            </div>
            <div class="field">
                <label>Katılımcıları Seçin *</label>
                <input type="text" placeholder="🔍 İsim ile filtrele…" oninput="imGroupFilter(this.value)"
                       style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:8px 10px;font-size:13px;box-sizing:border-box;margin-bottom:6px;">
                <div class="im-multi-select" id="imGroupUserList">
                    @foreach($dmableUsers as $du)
                    @php $initial = strtoupper(substr($du->name, 0, 1)); @endphp
                    <label class="im-user-row" data-name="{{ strtolower($du->name) }}">
                        <input type="checkbox" name="participant_ids[]" value="{{ $du->id }}">
                        <div class="im-user-avatar-sm">{{ $initial }}</div>
                        <span class="im-user-name">{{ $du->name }}</span>
                        <span class="im-role-chip">{{ str_replace('_', ' ', $du->role) }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="im-modal-actions">
                <button type="button" class="btn" onclick="imCloseModal('group')">İptal</button>
                <button type="submit" class="btn alt">Grubu Kur</button>
            </div>
        </form>
    </div>
</div>

{{-- Sohbet Odası Modal --}}
<div class="im-modal-bg" id="imModalRoom">
    <div class="im-modal">
        <h3>🏠 Sohbet Odası Aç</h3>
        <div style="background:#f3eeff;border:1px solid #ddd6fe;border-radius:6px;padding:8px 12px;font-size:12px;color:#5b21b6;margin-bottom:12px;">
            Belirli bir tema veya proje etrafında farklı disiplinlerden kişileri bir araya getirin. Oda, tüm katılımcılara açık kalır.
        </div>
        <form method="POST" action="/im/group" id="imRoomForm">
            @csrf
            <input type="hidden" name="type" value="room">
            <div class="field">
                <label>Oda Adı *</label>
                <input type="text" name="title" placeholder="Örn: Q2 Kampanya Planlama, Vize Süreç Takibi…" required maxlength="190">
            </div>
            <div class="field">
                <label>Tema / Konu *</label>
                <select name="context_type" required style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 10px;font-size:13px;">
                    <option value="">— Tema seçin —</option>
                    <option value="Proje">📋 Proje</option>
                    <option value="Kampanya">📣 Kampanya</option>
                    <option value="Öğrenci Takibi">🎓 Öğrenci Takibi</option>
                    <option value="Operasyon">⚙️ Operasyon</option>
                    <option value="Eğitim & Gelişim">📚 Eğitim & Gelişim</option>
                    <option value="Strateji">🎯 Strateji</option>
                    <option value="Vize & Başvuru">🛂 Vize & Başvuru</option>
                    <option value="Etkinlik">🎉 Etkinlik</option>
                    <option value="Genel">💬 Genel</option>
                    <option value="Diğer">🔖 Diğer</option>
                </select>
            </div>
            <div class="field">
                <label>Katılımcılar <span style="color:var(--u-muted);font-weight:normal;">(farklı disiplinlerden seçin)</span></label>
                <input type="text" placeholder="🔍 İsim ile filtrele…" oninput="imRoomFilter(this.value)"
                       style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:8px 10px;font-size:13px;box-sizing:border-box;margin-bottom:6px;">
                <div class="im-multi-select" id="imRoomUserList" style="border-color:#ddd6fe;">
                    @foreach($dmableUsers as $du)
                    @php
                        $initial   = strtoupper(substr($du->name, 0, 1));
                        $roleLabel = str_replace('_', ' ', $du->role);
                    @endphp
                    <label class="im-user-row" data-name="{{ strtolower($du->name) }}" data-role="{{ strtolower($roleLabel) }}">
                        <input type="checkbox" name="participant_ids[]" value="{{ $du->id }}">
                        <div class="im-user-avatar-sm" style="background:#7c3aed;">{{ $initial }}</div>
                        <span class="im-user-name">{{ $du->name }}</span>
                        <span class="im-role-chip" style="background:#f3eeff;color:#6d28d9;">{{ $roleLabel }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="im-modal-actions">
                <button type="button" class="btn" onclick="imCloseModal('room')">İptal</button>
                <button type="submit" class="btn" style="background:#7c3aed;color:#fff;border-color:#7c3aed;">Odayı Aç</button>
            </div>
        </form>
    </div>
</div>

<script defer src="{{ Vite::asset('resources/js/messaging.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    imInit();
});
function imToggleMembers() {
    var panel = document.getElementById('imMembersPanel');
    if (!panel) return;
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}
// Oda kullanıcı filtre (isim + rol)
function imRoomFilter(q) {
    var rows = document.querySelectorAll('#imRoomUserList .im-user-row');
    var v = q.toLowerCase().trim();
    rows.forEach(function(r) { r.style.display = (!v || r.dataset.name.includes(v) || (r.dataset.role||'').includes(v)) ? '' : 'none'; });
}
</script>
@endsection

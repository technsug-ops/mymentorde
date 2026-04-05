{{-- Hub: Ekip içi konuşma sağ paneli --}}
@php
    $myParticipant = $conv->participants->first();
    $isPinned = (bool)($myParticipant?->is_pinned);
    $isMuted  = (bool)($myParticipant?->is_muted);
    $display  = $conv->getDisplayTitle($currentUser->id);
    $isGroup  = $conv->type !== 'direct';
    $convEmoji = match($conv->type) { 'room' => '🏠', 'announcement' => '📢', 'group' => '👥', default => '' };
@endphp

{{-- Başlık --}}
<div class="hub-thread-head">
    <div>
        <strong>{{ $convEmoji ? $convEmoji.' ' : '' }}{{ $display }}</strong>
        @if($isGroup && $conv->participantUsers->count() > 0)
        <span style="font-size:10px;color:var(--u-muted);margin-left:5px">
            {{ $conv->participantUsers->count() }} katılımcı
        </span>
        @endif
    </div>
    <div class="hub-thread-actions">
        <button class="btn alt" data-hub-pin="{{ $conv->id }}" title="{{ $isPinned ? 'Sabitlemeyi kaldır' : 'Sabitle' }}"
                style="font-size:12px">{{ $isPinned ? '📌' : '📍' }}</button>
        <button class="btn" data-hub-mute="{{ $conv->id }}" title="{{ $isMuted ? 'Bildirimleri aç' : 'Sessize al' }}"
                style="font-size:12px">{{ $isMuted ? '🔔' : '🔕' }}</button>
    </div>
</div>

{{-- Mesajlar --}}
<div class="hub-messages" id="imMessages" data-conv-id="{{ $conv->id }}">
    @forelse($messages as $msg)
    @php
        $isDeleted = $msg->trashed();
        $isMine    = (int)$msg->sender_id === (int)$currentUser->id;
        $isSystem  = (bool)($msg->is_system ?? false);
        $sender    = $msg->sender;
        $senderName = $sender?->name ?? 'Sistem';
        $rowCls    = $isSystem ? 'system-msg' : ($isMine ? 'staff' : 'customer');
        $letter    = strtoupper(substr($senderName, 0, 1));
    @endphp
    <div class="hub-msg-row {{ $rowCls }}{{ $isDeleted ? ' deleted' : '' }}" id="imsg-{{ $msg->id }}" data-msg-id="{{ $msg->id }}">
        @if(!$isMine && !$isSystem)
        <div class="hub-msg-avatar" style="background:#4577c4">{{ $letter }}</div>
        @endif
        <div style="max-width:95%">
            @if(!$isMine && !$isSystem)
            <div class="hub-msg-sender">{{ $senderName }}</div>
            @endif
            @if($msg->reply_to_message_id && !$isDeleted)
            <div class="hub-reply-preview">↩ {{ Str::limit($msg->replyTo?->body ?? '…', 60) }}</div>
            @endif
            <div class="hub-bubble">
                @if($isDeleted)
                    🚫 Bu mesaj silindi.
                @elseif($msg->attachment_path)
                    @if($msg->body && !str_starts_with($msg->body, '[dosya]'))
                        {!! nl2br(e($msg->body)) !!}
                    @endif
                    <div class="hub-attach">
                        📎 <a href="/im/messages/{{ $msg->id }}/download" target="_blank">{{ $msg->attachment_name ?? 'dosya' }}</a>
                    </div>
                @elseif(str_starts_with($msg->body ?? '', '[gif]:'))
                    <img class="hub-gif-img" src="{{ e(substr($msg->body, 6)) }}" alt="GIF" loading="lazy">
                @else
                    {!! nl2br(e($msg->body)) !!}
                @endif
                @if($msg->is_edited && !$isDeleted)
                <span style="font-size:10px;color:#aaa;margin-top:2px;display:block">(düzenlendi)</span>
                @endif
            </div>
            <div class="hub-msg-time">
                {{ \Carbon\Carbon::parse($msg->created_at)->format('d.m H:i') }}
                @if(!$isSystem && !$isDeleted)
                    @if($isMine)
                    <button data-hub-del="{{ $msg->id }}"
                            style="background:none;border:none;cursor:pointer;font-size:10px;color:#bbb;padding:0 2px">🗑</button>
                    @else
                    <button data-hub-reply="{{ $msg->id }}" data-hub-reply-sender="{{ addslashes($senderName) }}"
                            style="background:none;border:none;cursor:pointer;font-size:10px;color:#bbb;padding:0 2px">↩</button>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="hub-empty">Henüz mesaj yok. İlk mesajı gönder!</div>
    @endforelse
</div>

{{-- Reply bar --}}
<div class="hub-reply-bar" id="imReplyBar" style="display:none">
    <span id="imReplyText" style="flex:1"></span>
    <button id="imClearReplyBtn" title="İptal">✕</button>
    <input type="hidden" id="imReplyToInput" name="reply_to_message_id" value="">
</div>

{{-- Send form --}}
<div class="hub-input-bar">
    <textarea id="imBodyInput" placeholder="Mesaj yaz..." rows="1"></textarea>
    <div class="hub-picker-wrap">
        <button class="hub-picker-btn" data-hub-picker="emoji" data-hub-picker-target="imBodyInput" title="Emoji">😊</button>
        <button class="hub-picker-btn" data-hub-picker="gif" data-hub-picker-target="imBodyInput" title="GIF" style="font-size:12px;font-weight:700;letter-spacing:-.5px">GIF</button>
        {{-- Emoji picker --}}
        <div class="hub-emoji-picker" id="hubEmojiPicker_imBodyInput">
            <div class="hub-emoji-cats" id="hubEmojiCats_imBodyInput"></div>
            <div class="hub-emoji-grid" id="hubEmojiGrid_imBodyInput"></div>
        </div>
        {{-- GIF picker --}}
        <div class="hub-gif-picker" id="hubGifPicker_imBodyInput">
            <div class="hub-gif-search">
                <input type="text" id="imGifSearchInput" placeholder="🔍 GIF ara...">
            </div>
            <div class="hub-gif-grid" id="hubGifGrid_imBodyInput">
                <div class="hub-gif-loading">Yükleniyor...</div>
            </div>
        </div>
    </div>
    <label class="btn alt" for="imFileInput" style="cursor:pointer;padding:7px 10px" title="Dosya ekle">📎</label>
    <input type="file" id="imFileInput" name="attachment" style="display:none">
    <button class="btn ok" id="imSendBtn">Gönder</button>
</div>
<div id="imFileLabel" style="display:none;font-size:11px;padding:3px 11px;color:var(--u-muted);border-top:1px solid var(--u-line)"></div>

<script nonce="{{ $cspNonce ?? '' }}">
window.__hubInternal = {
    convId:  {{ (int)$conv->id }},
    lastMsgId: (function(){
        var msgs = document.querySelectorAll('#imMessages [data-msg-id]');
        var max = 0;
        msgs.forEach(function(el){ max = Math.max(max, parseInt(el.dataset.msgId)||0); });
        return max;
    })(),
};
</script>

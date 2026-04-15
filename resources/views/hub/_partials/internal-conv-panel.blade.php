{{-- Hub: Ekip içi konuşma sağ paneli --}}
@php
    $myParticipant = $conv->participants->where('user_id', $currentUser->id)->first();
    $isPinned = (bool)($myParticipant?->is_pinned);
    $isMuted  = (bool)($myParticipant?->is_muted);
    $display  = $conv->getDisplayTitle($currentUser->id);
    $isGroup  = $conv->type !== 'direct';
    $convEmoji = match($conv->type) { 'room' => '🏠', 'announcement' => '📢', 'group' => '👥', default => '' };

    // Slack-tarzı yetki kontrolü
    $convSvc    = app(\App\Services\ConversationService::class);
    $permLevel  = $convSvc->permissionLevel($conv, $currentUser);
    $isChanAdmin = $permLevel === 'admin';
    $isManager   = $permLevel === 'manager';
    $canManage   = $isChanAdmin || $isManager;
    $isArchived  = $conv->isArchived();
@endphp

{{-- Başlık --}}
<div class="hub-thread-head">
    <div>
        <strong>{{ $convEmoji ? $convEmoji.' ' : '' }}{{ $display }}</strong>
        @if($isArchived)
        <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;margin-left:5px;font-weight:600">📦 ARŞİVLİ</span>
        @endif
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
        @if($isGroup)
        <button class="btn alt" type="button" id="imSettingsToggle" title="Grup/Oda Ayarları"
                style="font-size:12px">⚙️</button>
        @endif
    </div>
</div>

{{-- ⚙️ Grup/Oda Yönetim Paneli (collapsible) --}}
@if($isGroup)
<div id="imSettingsPanel" style="display:none;background:#f8fafc;border-bottom:1px solid var(--u-line);padding:14px 16px;max-height:360px;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <strong style="font-size:13px">⚙️ {{ $conv->type === 'room' ? 'Oda' : 'Grup' }} Yönetimi</strong>
        @if($canManage)
            <span style="font-size:10px;background:#dbeafe;color:#1e3a8a;padding:2px 7px;border-radius:4px;font-weight:700">
                {{ $isManager ? 'MANAGER' : 'ADMIN' }}
            </span>
        @endif
    </div>

    {{-- Üye listesi --}}
    <div style="margin-bottom:14px">
        <div style="font-size:11px;font-weight:600;color:var(--u-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em">
            Üyeler ({{ $conv->participantUsers->count() }})
        </div>
        <div style="display:grid;gap:6px">
            @foreach($conv->participantUsers as $p)
                @php
                    // pivot belongsToMany eager-loaded, role buradan okunur
                    $pRole    = $p->pivot->role ?? 'member';
                    $pIsAdmin = $pRole === 'admin';
                    $isSelf   = $p->id === $currentUser->id;
                @endphp
                <div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:#fff;border:1px solid var(--u-line);border-radius:6px">
                    <div style="width:28px;height:28px;border-radius:50%;background:#4577c4;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr($p->name, 0, 2)) }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:600;color:var(--u-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $p->name }}{{ $isSelf ? ' (siz)' : '' }}
                        </div>
                        <div style="font-size:10px;color:var(--u-muted)">
                            @if($pIsAdmin)<span style="color:#f59e0b">⭐ Admin</span> · @endif{{ ucwords(str_replace('_', ' ', $p->role ?? '')) }}
                        </div>
                    </div>
                    <div style="display:flex;gap:4px;flex-shrink:0">
                        @if($canManage && !$isSelf)
                            @if(!$pIsAdmin)
                                <form method="POST" action="/im/conversations/{{ $conv->id }}/members/{{ $p->id }}/promote" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn alt" style="font-size:10px;padding:3px 8px" title="Admin yap">⭐</button>
                                </form>
                            @else
                                <form method="POST" action="/im/conversations/{{ $conv->id }}/members/{{ $p->id }}/demote" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn alt" style="font-size:10px;padding:3px 8px" title="Admin yetkisini kaldır">⏷</button>
                                </form>
                            @endif
                            <form method="POST" action="/im/conversations/{{ $conv->id }}/members/{{ $p->id }}/remove" style="display:inline"
                                  onsubmit="return confirm('{{ $p->name }} adlı üyeyi çıkarmak istediğinize emin misiniz?')">
                                @csrf
                                <button type="submit" class="btn" style="font-size:10px;padding:3px 8px;background:#ef4444;color:#fff;border-color:#ef4444" title="Çıkar">✕</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Aksiyonlar --}}
    <div style="display:flex;gap:6px;flex-wrap:wrap;padding-top:10px;border-top:1px solid var(--u-line)">
        {{-- Gruptan Ayrıl — herkese görünür --}}
        <form method="POST" action="/im/conversations/{{ $conv->id }}/members/{{ $currentUser->id }}/remove" style="display:inline"
              onsubmit="return confirm('Bu {{ $conv->type === 'room' ? 'odadan' : 'gruptan' }} ayrılmak istediğinize emin misiniz?')">
            @csrf
            <button type="submit" class="btn alt" style="font-size:11px;padding:5px 12px">🚪 Ayrıl</button>
        </form>

        {{-- Archive / Unarchive — admin + manager --}}
        @if($canManage)
            @if($isArchived)
                <form method="POST" action="/im/conversations/{{ $conv->id }}/unarchive" style="display:inline">
                    @csrf
                    <button type="submit" class="btn" style="font-size:11px;padding:5px 12px;background:#f59e0b;color:#fff;border-color:#f59e0b">📤 Arşivden Çıkar</button>
                </form>
            @else
                <form method="POST" action="/im/conversations/{{ $conv->id }}/archive" style="display:inline"
                      onsubmit="return confirm('Bu {{ $conv->type === 'room' ? 'odayı' : 'grubu' }} arşivlemek istediğinize emin misiniz? Yeni mesaj gönderilemez.')">
                    @csrf
                    <button type="submit" class="btn alt" style="font-size:11px;padding:5px 12px">📦 Arşivle</button>
                </form>
            @endif
        @endif

        {{-- Destroy — grup admin (oluşturan) + manager --}}
        @if($canManage)
            <form method="POST" action="/im/conversations/{{ $conv->id }}/destroy" style="display:inline"
                  onsubmit="return confirm('DİKKAT: Bu {{ $conv->type === 'room' ? 'oda' : 'grup' }} KALICI olarak silinecek. Geri alınamaz. Devam edilsin mi?')">
                @csrf
                <button type="submit" class="btn" style="font-size:11px;padding:5px 12px;background:#dc2626;color:#fff;border-color:#dc2626">🗑 Kalıcı Sil</button>
            </form>
        @endif
    </div>
</div>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var btn = document.getElementById('imSettingsToggle');
    var pnl = document.getElementById('imSettingsPanel');
    if (btn && pnl) {
        btn.addEventListener('click', function(){
            pnl.style.display = (pnl.style.display === 'none' || pnl.style.display === '') ? 'block' : 'none';
        });
    }
})();
</script>
@endif

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
        <div style="max-width:78%;min-width:0">
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
@if($isArchived)
<div style="padding:20px 24px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-top:2px solid #f59e0b;text-align:center">
    <div style="font-size:24px;margin-bottom:4px">📦</div>
    <div style="font-size:14px;font-weight:600;color:#78350f;margin-bottom:4px">Bu konuşma arşivlendi</div>
    <div style="font-size:12px;color:#92400e;margin-bottom:14px">Yeni mesaj gönderilemez. Eski mesajlar korunuyor.</div>
    @if($canManage)
        <form method="POST" action="/im/conversations/{{ $conv->id }}/unarchive" style="display:inline-block">
            @csrf
            <button type="submit" class="btn ok" style="font-size:14px;padding:10px 24px;background:#16a34a;color:#fff;border-color:#16a34a;font-weight:600;box-shadow:0 2px 8px rgba(22,163,74,.3);cursor:pointer">
                📤 Arşivden Çıkar
            </button>
        </form>
    @else
        <div style="font-size:11px;color:#92400e;font-style:italic">Arşivden çıkarma yetkisi yalnızca grup yöneticisinde.</div>
    @endif
</div>
@else
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
@endif

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

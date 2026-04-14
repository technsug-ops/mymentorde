{{-- Hub: Müşteri thread sağ paneli --}}
@php
    $tType = (string)($thread->thread_type ?? 'guest');
    $guest = ($tType === 'guest' && isset($guestMap[$thread->guest_application_id]))
        ? $guestMap[$thread->guest_application_id] : null;
    $threadLabel = $tType === 'student'
        ? 'Öğrenci: '.$thread->student_id
        : ($guest ? trim($guest->first_name.' '.$guest->last_name) : 'GST-'.$thread->guest_application_id);
    $studentId = trim((string)($thread->student_id ?: ($guest->converted_student_id ?? '')));
    $isOverdue = $thread->next_response_due_at
        && \Carbon\Carbon::parse($thread->next_response_due_at)->isPast()
        && $thread->status === 'open';
@endphp

{{-- Başlık --}}
<div class="hub-thread-head">
    <div>
        <strong>{{ $threadLabel }}</strong>
        @if($thread->status === 'open')
            <span class="badge ok" style="font-size:10px;margin-left:5px">Açık</span>
        @else
            <span class="badge" style="font-size:10px;margin-left:5px;background:#eee;color:#666">Kapalı</span>
        @endif
        @if($isOverdue)
            <span class="badge danger" style="font-size:10px;margin-left:3px">⚠ Gecikmiş</span>
        @endif
    </div>
    <div class="hub-thread-actions">
        {{-- Danışman ata --}}
        <form method="POST" action="/messages-center/{{ $thread->id }}/assign-advisor"
              style="display:flex;gap:4px;align-items:center">
            @csrf
            <select name="advisor_user_id" style="font-size:11px;padding:2px 4px;border:1px solid var(--u-line);border-radius:4px;height:26px">
                <option value="">— Danışman —</option>
                @foreach($advisors as $adv)
                    <option value="{{ $adv->id }}" {{ $thread->advisor_user_id == $adv->id ? 'selected' : '' }}>
                        {{ $adv->name }}
                    </option>
                @endforeach
            </select>
            <button class="btn alt" type="submit" style="padding:3px 7px;font-size:11px">Ata</button>
        </form>
        {{-- Aç/Kapat --}}
        <form method="POST" action="/messages-center/{{ $thread->id }}/status">
            @csrf
            <input type="hidden" name="status" value="{{ $thread->status === 'open' ? 'closed' : 'open' }}">
            <button class="btn {{ $thread->status === 'open' ? 'warn' : 'ok' }}" type="submit"
                    style="padding:3px 8px;font-size:11px">
                {{ $thread->status === 'open' ? 'Kapat' : 'Yeniden Aç' }}
            </button>
        </form>
    </div>
</div>

{{-- Özet bar: Aday Öğrenci bilgisi + Öğrenci kısayolları --}}
<div class="hub-summary">
    @if($guest)
    <span class="kv"><strong>E-posta:</strong> {{ $guest->email }}</span>
    @endif
    @if($studentId !== '')
    <span class="kv"><strong>ID:</strong> {{ $studentId }}</span>
    @endif
    @if($thread->department)
    <span class="kv"><strong>Dept:</strong> {{ ucfirst($thread->department) }}</span>
    @endif
    @if($thread->next_response_due_at)
    <span class="kv" style="{{ $isOverdue ? 'color:var(--u-danger)' : '' }}">
        <strong>SLA:</strong> {{ \Carbon\Carbon::parse($thread->next_response_due_at)->format('d.m H:i') }}
    </span>
    @endif
    @if($studentId !== '')
    <span style="margin-left:auto;display:flex;gap:4px">
        <a class="btn alt" href="/senior/students?q={{ urlencode($studentId) }}"
           style="font-size:10px;padding:2px 8px" title="Öğrenci kaydını aç">Öğrenci</a>
        <a class="btn" href="/senior/notes?q={{ urlencode($studentId) }}"
           style="font-size:10px;padding:2px 8px" title="İlgili notlar">Notlar</a>
        <a class="btn" href="/senior/tickets?q={{ urlencode($studentId) }}"
           style="font-size:10px;padding:2px 8px" title="İlgili ticketlar">Ticketlar</a>
    </span>
    @endif
</div>

{{-- Mesajlar --}}
<div class="hub-messages" id="hubCustomerMessages">
    @forelse($messages as $msg)
    @php
        $isStaff = !in_array((string)($msg->sender_role ?? ''), ['guest', 'student'], true);
        $rowCls  = $isStaff ? 'staff' : 'customer';
        $isRead  = (bool)($msg->is_read_by_advisor ?? false);
        $isQuick = (bool)($msg->is_quick_request ?? false);
    @endphp
    <div class="hub-msg-row {{ $rowCls }}" id="cmsg-{{ $msg->id }}">
        @if(!$isStaff)
        <div class="hub-msg-avatar">{{ strtoupper(substr($threadLabel,0,1)) }}</div>
        @endif
        <div style="max-width:95%">
            @if(!$isStaff)
            <div class="hub-msg-sender">{{ $threadLabel }}</div>
            @endif
            @if($isQuick)
            <div style="font-size:10px;color:#e67e22;font-weight:700;margin-bottom:2px">⚡ Hızlı Talep</div>
            @endif
            <div class="hub-bubble">
                @if($msg->attachment_storage_path && !$msg->message)
                    <div class="hub-attach">📎 <a href="{{ Storage::url($msg->attachment_storage_path) }}" target="_blank">{{ $msg->attachment_original_name ?? 'dosya' }}</a></div>
                @elseif($msg->attachment_storage_path)
                    {!! nl2br(e($msg->message)) !!}
                    <div class="hub-attach">📎 <a href="{{ Storage::url($msg->attachment_storage_path) }}" target="_blank">{{ $msg->attachment_original_name ?? 'dosya' }}</a></div>
                @elseif(str_starts_with($msg->message ?? '', '[gif]:'))
                    <img class="hub-gif-img" src="{{ e(substr($msg->message, 6)) }}" alt="GIF" loading="lazy">
                @else
                    {!! nl2br(e($msg->message)) !!}
                @endif
            </div>
            <div class="hub-msg-time">
                {{ \Carbon\Carbon::parse($msg->created_at)->format('d.m H:i') }}
                @if(!$isStaff)
                    <span style="font-size:9px;color:{{ $isRead ? '#27ae60' : '#aaa' }};margin-left:4px">
                        {{ $isRead ? '✓ okundu' : '● okunmadı' }}
                    </span>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="hub-empty">Henüz mesaj yok.</div>
    @endforelse
</div>

{{-- Hızlı cevaplar --}}
@if(!empty($quickReplies))
<div style="padding:5px 11px;border-top:1px solid var(--u-line);display:flex;gap:5px;flex-wrap:wrap;flex-shrink:0">
    @foreach($quickReplies as $qr)
    <button class="btn alt" data-hub-quick="{{ $loop->index }}"
            style="font-size:10px;padding:2px 7px;white-space:nowrap">
        {{ Str::limit($qr, 30) }}
    </button>
    @endforeach
</div>
@endif

{{-- Reply bar --}}
<div class="hub-reply-bar" id="hubCReplyBar" style="display:none">
    <span id="hubCReplyText" style="flex:1"></span>
    <button id="hubCClearReplyBtn" title="İptal">✕</button>
    <input type="hidden" id="hubCReplyInput" value="">
</div>

{{-- Send form --}}
<div class="hub-input-bar">
    <textarea id="hubCBodyInput" placeholder="Mesaj yaz..." rows="1"></textarea>
    <div class="hub-picker-wrap">
        <button class="hub-picker-btn" data-hub-picker="emoji" data-hub-picker-target="hubCBodyInput" title="Emoji">😊</button>
        <button class="hub-picker-btn" data-hub-picker="gif" data-hub-picker-target="hubCBodyInput" title="GIF" style="font-size:12px;font-weight:700;letter-spacing:-.5px">GIF</button>
        <div class="hub-emoji-picker" id="hubEmojiPicker_hubCBodyInput">
            <div class="hub-emoji-cats" id="hubEmojiCats_hubCBodyInput"></div>
            <div class="hub-emoji-grid" id="hubEmojiGrid_hubCBodyInput"></div>
        </div>
        <div class="hub-gif-picker" id="hubGifPicker_hubCBodyInput">
            <div class="hub-gif-search">
                <input type="text" id="hubCGifSearchInput" placeholder="🔍 GIF ara...">
            </div>
            <div class="hub-gif-grid" id="hubGifGrid_hubCBodyInput">
                <div class="hub-gif-loading">Yükleniyor...</div>
            </div>
        </div>
    </div>
    <label class="btn alt" for="hubCFileInput" style="cursor:pointer;padding:7px 10px" title="Dosya ekle">📎</label>
    <input type="file" id="hubCFileInput" style="display:none">
    <button class="btn ok" id="hubCSendBtn">Gönder</button>
</div>
<div id="hubCFileLabel" style="display:none;font-size:11px;padding:3px 11px;color:var(--u-muted);border-top:1px solid var(--u-line)"></div>

<script nonce="{{ $cspNonce ?? '' }}">
window.__hubCustomer = {
    threadId: {{ (int)$thread->id }},
    sendUrl:  '/messages-center/{{ (int)$thread->id }}/send',
    quickReplies: @json($quickReplies ?? []),
};
</script>

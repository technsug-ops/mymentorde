@extends('student.layouts.app')

@section('title', 'Danışman Mesajları')
@section('page_title', 'Danışman Mesajları')

@push('head')
<style>
/* ── msg-* scoped — no portal-unified conflict ── */
.msg-wrap { display:flex; flex-direction:column; overflow:hidden; }
.msg-hdr {
    display:flex; align-items:center; gap:12px;
    padding:12px 18px; border-bottom:1px solid var(--u-line);
    flex-shrink:0;
}
.msg-hdr-av {
    width:42px; height:42px; border-radius:50%; flex-shrink:0;
    background:var(--u-brand); color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:16px;
}
.msg-hdr-info { flex:1; min-width:0; }
.msg-hdr-name { font-weight:700; font-size:14px; line-height:1.3; }
.msg-hdr-sub  { font-size:12px; color:var(--u-muted); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.msg-hdr-right { display:flex; gap:6px; align-items:center; flex-shrink:0; flex-wrap:wrap; }

.msg-filters { display:flex; gap:6px; flex-wrap:wrap; padding:10px 18px; border-bottom:1px solid var(--u-line); flex-shrink:0; }
.msg-fpill {
    border:1px solid var(--u-line); background:var(--u-card); color:var(--u-brand);
    border-radius:999px; padding:4px 13px; cursor:pointer; font-size:12px;
    transition:all .15s;
}
.msg-fpill.active { background:var(--u-brand); color:#fff; border-color:var(--u-brand); }

.msg-body {
    flex:1; overflow-y:auto; padding:16px 18px;
    display:flex; flex-direction:column; gap:10px;
    min-height:240px; max-height:54vh;
}

.brow { display:flex; }
.brow.mine   { justify-content:flex-end; }
.brow.mine > div { display:flex; flex-direction:column; align-items:flex-end; }
.brow.theirs { justify-content:flex-start; }
.bbl {
    max-width:min(720px,92%);
    padding:9px 13px; border-radius:16px;
    font-size:13.5px; line-height:1.55;
    word-break:break-word; overflow-wrap:break-word;
}
.brow.mine   .bbl { background:var(--u-brand); color:#fff; border-bottom-right-radius:4px; }
.brow.theirs .bbl { background:#f0f4f9; color:var(--u-text); border-bottom-left-radius:4px; }
.bbl-meta {
    display:flex; align-items:center; gap:4px;
    margin-top:3px; font-size:11px; color:var(--u-muted);
}
.brow.mine .bbl-meta { justify-content:flex-end; }
.bbl-quick {
    background:#fff3cd; color:#7a5c00; border:1px solid #f5d97a;
    border-radius:999px; padding:1px 7px; font-size:10px;
    display:inline-block; margin-bottom:5px;
}
.brow.mine .bbl-quick { background:rgba(255,255,255,.25); color:#fff; border-color:rgba(255,255,255,.4); }

.msg-foot { flex-shrink:0; border-top:1px solid var(--u-line); padding:10px 14px; background:var(--u-card); }
.msg-foot-main { display:flex; align-items:flex-end; gap:8px; }
.msg-foot-main textarea {
    flex:1; border:1px solid var(--u-line); border-radius:10px;
    padding:8px 11px; font-size:13.5px; font-family:inherit;
    resize:none; overflow:hidden; line-height:1.5;
    min-height:38px; max-height:110px;
    background:var(--u-bg); color:var(--u-text); box-sizing:border-box;
}
.msg-foot-meta { display:flex; align-items:center; gap:8px; margin-top:8px; flex-wrap:wrap; }
.msg-foot-qlbl { display:flex; align-items:center; gap:5px; font-size:12px; cursor:pointer; }

/* emoji/gif pickers */
.eg-picker-wrap{position:relative;display:inline-flex;gap:2px;vertical-align:middle;align-items:center}
.eg-picker-btn{background:none !important;border:none !important;cursor:pointer;font-size:17px !important;padding:4px 5px !important;border-radius:6px !important;color:#888;min-height:0 !important;height:30px !important;width:30px !important;display:inline-flex !important;align-items:center !important;justify-content:center !important}
.eg-picker-btn:hover{background:#f0f4ff !important;color:#4577c4}
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
.eg-gif-grid img:hover{border-color:var(--u-brand,#4577c4)}
.hub-gif-loading{text-align:center;padding:16px;color:var(--u-muted);font-size:12px}
</style>
@endpush

@section('content')
@php
    $advInitials = $advisor
        ? strtoupper(substr(trim((string)($advisor->name ?? '??')), 0, 2))
        : '??';
    $advPresence = $advisor ? \App\Services\PresenceService::getPresence($advisor) : null;
    $advPresColors = ['online'=>'#16a34a','away'=>'#d97706','busy'=>'#dc2626','offline'=>'#9ca3af'];
    $advPresColor = $advPresColors[$advPresence['status'] ?? 'offline'] ?? '#9ca3af';
    $advPresLabel = $advPresence['label'] ?? 'Çevrimdışı';
@endphp

<section class="panel msg-wrap">

    {{-- ── Danışman başlık satırı ── --}}
    <div class="msg-hdr">
        <div class="msg-hdr-av" style="position:relative;">{{ $advInitials }}
            @if($advisor)
            <span style="position:absolute;bottom:1px;right:1px;width:11px;height:11px;border-radius:50%;border:2px solid var(--u-card,#fff);background:{{ $advPresColor }};" title="{{ $advPresLabel }}"></span>
            @endif
        </div>
        <div class="msg-hdr-info">
            <div class="msg-hdr-name">{{ $advisor?->name ?? 'Danışman atanmadı' }}</div>
            <div class="msg-hdr-sub">
                @if($advisor)
                    <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:{{ $advPresColor }};margin-right:3px;vertical-align:middle;"></span>{{ $advPresLabel }} &middot; {{ $advisor->email }}
                @else
                    Yakında bir danışman atanacak.
                @endif
            </div>
        </div>
        <div class="msg-hdr-right">
            <span class="badge chip {{ $thread->status === 'open' ? 'ok' : '' }}">{{ $thread->status === 'open' ? 'Açık' : ucfirst((string)$thread->status) }}</span>
        </div>
    </div>

    {{-- ── Arama + Filtre pilleri ── --}}
    <form method="get" action="{{ route('student.messages') }}"
          style="display:flex;gap:6px;padding:8px 18px 0;flex-shrink:0;">
        <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Mesajlarda ara…"
               style="flex:1;border:1px solid var(--u-line);border-radius:8px;padding:5px 10px;font-size:var(--tx-xs);background:var(--u-bg);color:var(--u-text);">
        <button class="btn alt" type="submit" style="padding:5px 12px;font-size:var(--tx-xs);">Ara</button>
    </form>

    <div class="msg-filters">
        <button type="button" class="msg-fpill active" id="fp-all"    onclick="filterMsg('all',this)">Tümü</button>
        <button type="button" class="msg-fpill"        id="fp-unread" onclick="filterMsg('unread',this)">Okunmamış</button>
        <button type="button" class="msg-fpill"        id="fp-quick"  onclick="filterMsg('quick',this)">⚡ Hızlı Talep</button>
    </div>

    {{-- ── Mesaj geçmişi ── --}}
    <div class="msg-body" id="msgBody">
        @forelse($messages as $m)
            @php $mine = (string)$m->sender_role === 'student'; @endphp
            <div class="brow {{ $mine ? 'mine' : 'theirs' }}"
                 data-read="{{ !empty($m->is_read_by_participant) ? '1' : '0' }}"
                 data-quick="{{ !empty($m->is_quick_request) ? '1' : '0' }}">
                <div>
                    <div class="bbl">
                        @if(!empty($m->is_quick_request))
                            <span class="bbl-quick">⚡ Hızlı Talep</span><br>
                        @endif
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
                        <span>{{ $mine ? 'Siz' : ($advisor?->name ?? 'Danışman') }}</span>
                        <span>·</span>
                        <span>{{ \Carbon\Carbon::parse($m->created_at)->format('d.m H:i') }}</span>
                        @if(!$mine)
                            <span style="color:{{ !empty($m->is_read_by_participant) ? 'var(--u-ok)' : 'var(--u-warn)' }}">
                                {{ !empty($m->is_read_by_participant) ? '✓ okundu' : '● yeni' }}
                            </span>
                        @endif
                        @if($mine)
                            <span style="color:{{ !empty($m->is_read_by_participant) ? 'var(--u-ok)' : 'var(--u-muted)' }}">
                                {{ !empty($m->is_read_by_participant) ? '✓' : '○' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:48px 20px;color:var(--u-muted);">
                Henüz mesaj yok.<br>
                <span style="font-size:var(--tx-xs);">Aşağıdan danışmanınıza mesaj gönderebilirsiniz.</span>
            </div>
        @endforelse
    </div>

    {{-- ── Mesaj gönderme formu ── --}}
    <form method="post" action="{{ route('student.messages.send') }}" enctype="multipart/form-data" class="msg-foot" id="studentSendForm">
        @csrf
        <input type="hidden" name="department" value="advisory">
        <input type="hidden" name="sla_hours" value="{{ (int)($thread->sla_hours ?: 24) }}">
        <div class="msg-foot-main">
            <div class="eg-picker-wrap">
                <button type="button" class="eg-picker-btn" onclick="egTogglePicker('emoji','studentMsgBody')" title="Emoji">😊</button>
                <button type="button" class="eg-picker-btn" onclick="egTogglePicker('gif','studentMsgBody')" title="GIF" style="font-size:var(--tx-xs);font-weight:700">GIF</button>
                <div class="eg-emoji-picker" id="egEmojiPicker_studentMsgBody">
                    <div class="eg-emoji-cats" id="egEmojiCats_studentMsgBody"></div>
                    <div class="eg-emoji-grid" id="egEmojiGrid_studentMsgBody"></div>
                </div>
                <div class="eg-gif-picker" id="egGifPicker_studentMsgBody">
                    <div class="eg-gif-search"><input type="text" placeholder="🔍 GIF ara…" oninput="egGifSearch(this.value,'studentMsgBody')"></div>
                    <div class="eg-gif-grid" id="egGifGrid_studentMsgBody"><div class="hub-gif-loading">Yükleniyor…</div></div>
                </div>
            </div>
            <textarea id="studentMsgBody" name="message" rows="1"
                      placeholder="Danışmanınıza mesaj yazın… (Enter = gönder, Shift+Enter = satır)"
                      oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,110)+'px'"
                      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();document.getElementById('studentSendForm').requestSubmit()}"
            ></textarea>
            <label class="btn alt" for="studentMsgAttachment" style="cursor:pointer;padding:7px 10px;flex-shrink:0;" title="Dosya ekle">📎</label>
            <input type="file" name="attachment" id="studentMsgAttachment" style="display:none;"
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                   onchange="document.getElementById('studentMsgAttachName').textContent=this.files[0]?.name||''">
            <button class="btn ok" type="submit" id="studentSendBtn" style="flex-shrink:0;padding:8px 16px;">Gönder</button>
        </div>
        <div class="msg-foot-meta">
            <span id="studentMsgAttachName" style="font-size:var(--tx-xs);color:var(--u-muted);max-width:180px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;"></span>
            <label class="msg-foot-qlbl">
                <input type="checkbox" name="quick_request" value="1"> ⚡ Hızlı bilgi talebi
            </label>
            <span style="flex:1"></span>
            <a class="btn alt" href="/student/tickets" style="font-size:var(--tx-xs);padding:4px 10px;">Ticket Aç</a>
        </div>
    </form>

</section>
@endsection

@push('scripts')
<script defer src="{{ Vite::asset('resources/js/emoji-gif-picker.js') }}"></script>
<script defer src="{{ Vite::asset('resources/js/student-messages.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var b = document.getElementById('msgBody');
    if (b) b.scrollTop = b.scrollHeight;

    // ── Çift gönderimi engelle ──
    var form = document.getElementById('studentSendForm');
    var btn  = document.getElementById('studentSendBtn');
    if (form && btn) {
        form.addEventListener('submit', function(e) {
            if (btn.disabled) { e.preventDefault(); return; }
            btn.disabled = true;
            btn.textContent = 'Gönderiliyor…';
            // 8 sn sonra resetle (ağ hatası veya sayfa yenilenmezse)
            setTimeout(function() {
                btn.disabled = false;
                btn.textContent = 'Gönder';
            }, 8000);
        });
    }
})();
</script>
@endpush

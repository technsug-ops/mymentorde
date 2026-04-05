@extends('dealer.layouts.app')

@section('title', 'Destek Talebi #' . $ticket->id)
@section('page_title', 'Destek Talebi #' . $ticket->id)

@push('head')
<style>
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
</style>
@endpush

@section('content')
    @if(session('status'))
        <div class="panel" style="border-color:var(--u-ok);background:#f0faf4;margin-bottom:10px;">{{ session('status') }}</div>
    @endif

    {{-- Header --}}
    <section class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
            <div>
                <h2 style="margin:0 0 6px;">#{{ $ticket->id }} {{ $ticket->subject }}</h2>
                <div class="muted">
                    Departman: {{ $ticket->department ?: '-' }}
                    | Öncelik: {{ match($ticket->priority ?? '') { 'low'=>'Düşük','normal'=>'Normal','high'=>'Yüksek',default=>($ticket->priority ?: '–') } }}
                    | Açıldı: {{ optional($ticket->created_at)->format('Y-m-d H:i') }}
                    @if($ticket->last_replied_at)
                        | Son Yanıt: {{ $ticket->last_replied_at->format('Y-m-d H:i') }}
                    @endif
                </div>
                @if($guestApp)
                    <div class="muted" style="margin-top:4px;">
                        Lead: #{{ $guestApp->id }} {{ $guestApp->first_name }} {{ $guestApp->last_name }}
                        <a class="btn" style="padding:2px 8px;font-size:var(--tx-xs);margin-left:8px;"
                           href="{{ route('dealer.leads.show', $guestApp->id) }}">Lead Detay</a>
                    </div>
                @endif
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                @php
                    $statusMap = [
                        'open'        => ['label' => 'Açık',      'class' => 'ok'],
                        'in_progress' => ['label' => 'İşlemde',   'class' => 'warn'],
                        'closed'      => ['label' => 'Kapalı',    'class' => 'closed'],
                        'resolved'    => ['label' => 'Çözüldü',   'class' => 'ok'],
                    ];
                    $st = $statusMap[$ticket->status] ?? ['label' => $ticket->status, 'class' => ''];
                @endphp
                <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                <a class="btn" href="/dealer/advisor">← Danışmanıma Dön</a>
            </div>
        </div>
    </section>

    {{-- Thread --}}
    <section class="card">
        <h2>Mesaj Geçmişi</h2>
        <div style="display:flex;flex-direction:column;gap:12px;">
            {{-- İlk mesaj --}}
            @if($ticket->message)
                @php
                    $isDealer = true;
                    $authorEmail = $ticket->created_by_email ?: '-';
                @endphp
                <div class="panel" style="border-color:var(--u-brand);background:#f0f6ff;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                        <div>
                            <span class="badge info">dealer</span>
                            <span class="muted" style="margin-left:6px;font-size:var(--tx-xs);">{{ $authorEmail }}</span>
                        </div>
                        <span class="muted" style="font-size:var(--tx-xs);">{{ optional($ticket->created_at)->format('Y-m-d H:i') }}</span>
                    </div>
                    <div style="white-space:pre-wrap;">{{ $ticket->message }}</div>
                </div>
            @endif

            {{-- Yanıtlar --}}
            @foreach($replies as $reply)
                @php
                    $isDealer = ($reply->author_role === 'dealer');
                    $borderColor = $isDealer ? 'var(--u-brand)' : 'var(--u-ok)';
                    $bgColor     = $isDealer ? '#f0f6ff' : '#f0faf4';
                    $badgeClass  = $isDealer ? 'info' : 'ok';
                @endphp
                <div class="panel" style="border-color:{{ $borderColor }};background:{{ $bgColor }};">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                        <div>
                            <span class="badge {{ $badgeClass }}">{{ $reply->author_role ?: 'staff' }}</span>
                            <span class="muted" style="margin-left:6px;font-size:var(--tx-xs);">{{ $reply->author_email ?: '-' }}</span>
                        </div>
                        <span class="muted" style="font-size:var(--tx-xs);">{{ optional($reply->created_at)->format('Y-m-d H:i') }}</span>
                    </div>
                    <div style="white-space:pre-wrap;">{{ $reply->message }}</div>
                </div>
            @endforeach

            @if($replies->isEmpty() && !$ticket->message)
                <div class="muted">Henüz mesaj yok.</div>
            @endif
        </div>
    </section>

    {{-- Reply form --}}
    @if($ticket->status !== 'closed')
        <section class="card">
            <h2>Yanıt Yaz</h2>
            <form method="POST" action="{{ route('dealer.advisor.tickets.reply', $ticket->id) }}">
                @csrf
                <div style="margin-bottom:12px;">
                    <label>Mesaj *</label>
                    <textarea name="message" id="dealerReplyMsg" rows="5"
                        placeholder="Yanıtınızı buraya yazın..."
                        required>{{ old('message') }}</textarea>
                    @error('message')<div class="muted" style="color:var(--u-danger);">{{ $message }}</div>@enderror
                </div>
                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <button class="btn btn-primary">Yanıtla</button>
                    <div class="eg-picker-wrap">
                        <button type="button" class="eg-picker-btn" onclick="egTogglePicker('emoji','dealerReplyMsg')" title="Emoji">😊</button>
                        <div class="eg-emoji-picker" id="egEmojiPicker_dealerReplyMsg">
                            <div class="eg-emoji-cats" id="egEmojiCats_dealerReplyMsg"></div>
                            <div class="eg-emoji-grid" id="egEmojiGrid_dealerReplyMsg"></div>
                        </div>
                    </div>
                    <div class="eg-picker-wrap">
                        <button type="button" class="eg-picker-btn" onclick="egTogglePicker('gif','dealerReplyMsg')" title="GIF" style="font-size:var(--tx-xs) !important;font-weight:700 !important;letter-spacing:-.5px">GIF</button>
                        <div class="eg-gif-picker" id="egGifPicker_dealerReplyMsg">
                            <div class="eg-gif-search"><input type="text" placeholder="🔍 GIF ara..." oninput="egGifSearch(this.value,'dealerReplyMsg')"></div>
                            <div class="eg-gif-grid" id="egGifGrid_dealerReplyMsg"><div class="eg-gif-loading">Yükleniyor...</div></div>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    @else
        <div class="panel muted">Bu ticket kapatıldığı için yeni yanıt gönderilemez.</div>
    @endif

    <script defer src="{{ Vite::asset('resources/js/emoji-gif-picker.js') }}" defer></script>
    @include('dealer._partials.usage-guide', [
        'items' => [
            'Dealer rolü ile açık ticket\'lara yanıt ekleyebilirsin.',
            'Mavi arka planlı mesajlar dealer tarafından gönderilmiş, yeşil arka planlı mesajlar operasyon/manager yanıtlarıdır.',
            'Kapalı ticket\'larda yanıt formu görünmez.',
        ]
    ])
@endsection

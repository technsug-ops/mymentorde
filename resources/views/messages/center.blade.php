@php
    $role = auth()->user()?->role;
    $taskLayout = in_array($role, ['senior','mentor'])
        ? 'senior.layouts.app'
        : ($role === 'manager' ? 'manager.layouts.app' : 'layouts.staff');
@endphp
@extends($taskLayout)

@section('title', 'Mesaj Merkezi')
@section('page_title', 'Mesaj Merkezi')

@push('head')
<style>
    .summary7 { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:10px; margin-bottom:12px; }
    .msg-grid  { display:grid; grid-template-columns:390px 1fr; gap:10px; }
    @media (max-width:980px) { .msg-grid { grid-template-columns:1fr; } .summary7 { grid-template-columns:repeat(2,minmax(0,1fr)); } }

    /* Textarea */
    .mc-textarea { width:100%; box-sizing:border-box; border:1px solid var(--u-line,#e5e9f0) !important; border-radius:8px !important; padding:10px 12px !important; font-size:14px !important; resize:vertical; min-height:80px; font-family:inherit; color:var(--u-text,#1a2332); background:#fff; outline:none; line-height:1.5; display:block; }
    .mc-textarea:focus { border-color:var(--u-brand,#1f6fd9) !important; box-shadow:0 0 0 3px rgba(31,111,217,.08); }

    /* Picker */
    .mc-send-row { display:flex; gap:6px; align-items:center; margin-top:8px; flex-wrap:wrap; }
    .eg-picker-wrap { position:relative; }
    .eg-picker-btn { background:none !important; border:none !important; cursor:pointer; font-size:17px !important; padding:4px 6px !important; border-radius:6px !important; line-height:1 !important; color:#888; transition:background .12s; min-height:0 !important; height:32px !important; width:32px !important; display:inline-flex !important; align-items:center !important; justify-content:center !important; }
    .eg-picker-btn:hover { background:#f0f4ff !important; color:#1f6fd9; }
    .eg-emoji-picker,.eg-gif-picker { display:none; position:absolute; bottom:calc(100% + 8px); left:0; z-index:9000; background:#fff; border:1px solid var(--u-line,#e5e9f0); border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,.14); width:280px; }
    .eg-emoji-picker.open,.eg-gif-picker.open { display:block; }
    .eg-emoji-cats { display:flex; gap:2px; padding:6px; border-bottom:1px solid #f0f2f7; flex-wrap:wrap; }
    .eg-emoji-cats button { background:none !important; border:none !important; font-size:18px !important; padding:3px !important; border-radius:5px !important; cursor:pointer; min-height:0 !important; line-height:1.2 !important; }
    .eg-emoji-cats button.active,.eg-emoji-cats button:hover { background:#eef4ff !important; }
    .eg-emoji-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:1px; padding:6px; max-height:160px; overflow-y:auto; }
    .eg-emoji-grid button { font-size:20px !important; background:none !important; border:none !important; padding:2px !important; border-radius:5px !important; cursor:pointer; text-align:center; min-height:0 !important; height:34px !important; width:34px !important; }
    .eg-emoji-grid button:hover { background:#eef4ff !important; }
    .eg-gif-picker { width:300px; }
    .eg-gif-search { padding:8px; border-bottom:1px solid #f0f2f7; }
    .eg-gif-search input { width:100%; box-sizing:border-box; border:1px solid var(--u-line,#e5e9f0); border-radius:6px; padding:5px 10px; font-size:13px; min-height:0 !important; }
    .eg-gif-grid { display:grid; grid-template-columns:1fr 1fr; gap:4px; padding:6px; max-height:180px; overflow-y:auto; }
    .eg-gif-grid img { width:100%; border-radius:6px; cursor:pointer; object-fit:cover; aspect-ratio:16/9; }
    .eg-gif-loading { padding:12px; text-align:center; color:#aaa; font-size:12px; grid-column:1/-1; }

    /* Message bubbles */
    .mc-msg { padding:10px 12px; border-bottom:1px solid var(--u-line,#e5e9f0); }
    .mc-msg:last-child { border-bottom:none; }
    .mc-msg.staff { background:#f1f7ff; }
    .mc-msg.customer { background:#fff; }
    .mc-msg-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; }
    .mc-msg-role { font-size:11px; font-weight:700; letter-spacing:.3px; color:var(--u-brand,#1f6fd9); background:#e8f0fe; padding:2px 8px; border-radius:999px; }
    .mc-msg.customer .mc-msg-role { color:#5b5e6d; background:#f0f1f5; }
    .mc-msg-time { font-size:11px; color:var(--u-muted,#8a95a3); }
    .mc-msg-body { white-space:pre-wrap; font-size:13.5px; line-height:1.5; color:var(--u-text,#1a2332); }
    .mc-msg-gif { max-width:220px; border-radius:8px; display:block; margin-top:4px; }
    .mc-msg-attach { margin-top:6px; }
    .mc-quick-badge { display:inline-block; padding:2px 8px; border-radius:999px; background:#fff3cd; border:1px solid #f2cf6b; font-size:11px; margin-bottom:4px; }
</style>
@endpush

@section('content')
    <p class="muted" style="margin:0 0 12px;">Aday Öğrenci + Öğrenci direkt mesaj takip ve yonetim</p>

    <div class="summary7">
        <div class="panel"><div class="muted">Toplam Thread</div><div style="font-size:var(--tx-2xl);font-weight:700;">{{ (int) ($summary['total'] ?? 0) }}</div></div>
        <div class="panel"><div class="muted">Acik</div><div style="font-size:var(--tx-2xl);font-weight:700;">{{ (int) ($summary['open'] ?? 0) }}</div></div>
        <div class="panel"><div class="muted">Kapali</div><div style="font-size:var(--tx-2xl);font-weight:700;">{{ (int) ($summary['closed'] ?? 0) }}</div></div>
        <div class="panel"><div class="muted">SLA Geciken</div><div style="font-size:var(--tx-2xl);font-weight:700;">{{ (int) ($summary['overdue'] ?? 0) }}</div></div>
        <div class="panel"><div class="muted">Danışmansiz</div><div style="font-size:var(--tx-2xl);font-weight:700;">{{ (int) ($summary['unassigned'] ?? 0) }}</div></div>
        <div class="panel"><div class="muted">Advisor Okunmamis</div><div style="font-size:var(--tx-2xl);font-weight:700;">{{ (int) ($summary['unread_for_advisor'] ?? 0) }}</div></div>
        <div class="panel"><div class="muted">Katilimci Okunmamis</div><div style="font-size:var(--tx-2xl);font-weight:700;">{{ (int) ($summary['unread_for_participant'] ?? 0) }}</div></div>
    </div>

    {{-- Filtre --}}
    <section class="card" style="margin-bottom:10px;border-left:3px solid var(--u-brand,#1f6fd9);">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <span style="font-size:var(--tx-sm);font-weight:600;color:var(--u-brand,#1f6fd9);">🔍 Filtrele</span>
            <span class="muted" style="font-size:var(--tx-xs);">Thread listesini daralt</span>
        </div>
        <form method="get" action="{{ route('messages.center') }}" style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;">
            <select name="type" class="btn" style="text-align:left;">
                <option value="" @selected(($filters['type'] ?? '') === '')>Tum tipler</option>
                <option value="guest" @selected(($filters['type'] ?? '') === 'guest')>Aday Öğrenci</option>
                <option value="student" @selected(($filters['type'] ?? '') === 'student')>Öğrenci</option>
            </select>
            <select name="status" class="btn" style="text-align:left;">
                <option value="" @selected(($filters['status'] ?? '') === '')>Tum durumlar</option>
                <option value="open" @selected(($filters['status'] ?? '') === 'open')>Acik</option>
                <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Kapali</option>
            </select>
            <select name="department" class="btn" style="text-align:left;">
                <option value="" @selected(($filters['department'] ?? '') === '')>Tum departmanlar</option>
                <option value="operations" @selected(($filters['department'] ?? '') === 'operations')>Operasyon</option>
                <option value="advisory" @selected(($filters['department'] ?? '') === 'advisory')>Danışmanlık</option>
                <option value="finance" @selected(($filters['department'] ?? '') === 'finance')>Finans</option>
                <option value="marketing" @selected(($filters['department'] ?? '') === 'marketing')>Marketing</option>
                <option value="system" @selected(($filters['department'] ?? '') === 'system')>Sistem</option>
            </select>
            <select name="advisor_id" class="btn" style="text-align:left;">
                <option value="0">Tum danışmanlar</option>
                @foreach($advisors as $a)
                    <option value="{{ $a->id }}" @selected((int)($filters['advisor_id'] ?? 0) === (int)$a->id)>{{ $a->name }} ({{ $a->role }})</option>
                @endforeach
            </select>
            <input type="text" class="btn" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ara (student/guest/mesaj)">
            <button class="btn btn-primary" type="submit">Filtrele</button>
        </form>
    </section>

    {{-- Departman Sekmeleri + Toplu İşlem --}}
    <section class="card" style="margin-bottom:10px;border-left:3px solid var(--u-warn,#d97706);background:var(--u-bg,#f5f7fa);">
        @php $activeDepartment = (string) ($filters['route_department'] ?? ''); @endphp
        @php $isSalesStaff = auth()->user()?->role === \App\Models\User::ROLE_SALES_STAFF; @endphp
        <div class="pill-links" style="margin-bottom:0;">
            @if(!$isSalesStaff)
            <a href="/messages-center" class="pill-link {{ $activeDepartment === '' ? 'active' : '' }}">Tum Mesajlar</a>
            <a href="/messages-center/operations" class="pill-link {{ $activeDepartment === 'operations' ? 'active' : '' }}">Operasyon</a>
            <a href="/messages-center/advisory" class="pill-link {{ $activeDepartment === 'advisory' ? 'active' : '' }}">Danışmanlık</a>
            <a href="/messages-center/finance" class="pill-link {{ $activeDepartment === 'finance' ? 'active' : '' }}">Finans</a>
            @endif
            <a href="/messages-center/marketing" class="pill-link {{ ($activeDepartment === 'marketing' || $isSalesStaff) ? 'active' : '' }}">Marketing</a>
            @if(!$isSalesStaff)
            <a href="/messages-center/system" class="pill-link {{ $activeDepartment === 'system' ? 'active' : '' }}">Sistem</a>
            @endif
        </div>
        @if(auth()->user()?->role !== \App\Models\User::ROLE_SALES_STAFF)
        <div style="border-top:1px solid var(--u-line,#e5e9f0);padding-top:10px;margin-top:10px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <span style="font-size:var(--tx-sm);font-weight:600;color:var(--u-warn,#d97706);">⚡ Toplu İşlem</span>
                <span class="muted" style="font-size:var(--tx-xs);">Listeden seçili thread'lere uygula</span>
            </div>
            <form method="post" action="{{ route('messages.center.bulk-update') }}" id="bulkUpdateForm" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:8px;">
                @csrf
                <input id="bulkThreadIdsPreview" class="btn" type="text" placeholder="Secilen thread ID'ler" readonly>
                <select name="status" class="btn" style="text-align:left;">
                    <option value="">Durum degistirme</option>
                    <option value="open">Acik</option>
                    <option value="closed">Kapali</option>
                </select>
                <select name="department" class="btn" style="text-align:left;">
                    <option value="">Departman degistirme</option>
                    <option value="operations">Operasyon</option>
                    <option value="advisory">Danışmanlık</option>
                    <option value="finance">Finans</option>
                    <option value="marketing">Marketing</option>
                    <option value="system">Sistem</option>
                </select>
                <select name="advisor_user_id" class="btn" style="text-align:left;">
                    <option value="">Danışman degistirme</option>
                    @foreach($advisors as $a)
                        <option value="{{ $a->id }}">{{ $a->name }} ({{ $a->role }})</option>
                    @endforeach
                </select>
                <button class="btn btn-primary" type="submit">Toplu Güncelle</button>
            </form>
            <form method="post" action="{{ route('messages.center.bulk-mark-read') }}" id="bulkReadForm" style="display:flex;gap:8px;margin-top:8px;">
                @csrf
                <button class="btn" type="submit">Secilenleri Okundu Isaretle</button>
            </form>
        </div>
        @endif
    </section>

    <div class="msg-grid">
        <section class="card">
            <h3 style="margin:0 0 8px;">Thread Listesi</h3>
            <div style="max-height:760px;overflow:auto;border:1px solid #dbe4f2;border-radius:10px;">
                @forelse($threads as $t)
                    @php $isActive = $selectedThread && (int)$selectedThread->id === (int)$t->id; @endphp
                    @php $unreadForAdvisor = (int) ($unreadAdvisorMap[$t->id] ?? 0); @endphp
                    <a href="{{ route('messages.center', array_merge(request()->query(), ['thread_id' => $t->id])) }}" style="display:block;padding:10px;border-bottom:1px solid #e3ebf6;text-decoration:none;color:inherit;background:{{ $isActive ? '#eef5ff' : '#fff' }};">
                        <div style="display:flex;justify-content:space-between;">
                            <strong>#{{ $t->id }} {{ strtoupper($t->thread_type) }}</strong>
                            <span class="muted">{{ $t->status }}</span>
                        </div>
                        <label style="display:flex;align-items:center;gap:6px;margin:6px 0;">
                            <input type="checkbox" class="thread-select" value="{{ (int) $t->id }}">
                            <small class="muted">sec</small>
                            @if($unreadForAdvisor > 0)
                                <span style="margin-left:auto;display:inline-block;padding:2px 8px;border-radius:999px;background:#ffe9d2;border:1px solid #f6c48b;color:#7f4100;">{{ $unreadForAdvisor }} okunmamis</span>
                            @endif
                        </label>
                        <div class="muted">guest:{{ $t->guest_application_id ?: '-' }} | student:{{ $t->student_id ?: '-' }}</div>
                        <div class="muted">dept:{{ $t->department ?: '-' }} | sla:{{ (int)($t->sla_hours ?: 24) }}s | advisor:#{{ $t->advisor_user_id ?: '-' }}</div>
                        <div class="muted">son: {{ $t->last_message_at ?: '-' }} | due: {{ $t->next_response_due_at ?: '-' }}</div>
                        <div>{{ $t->last_message_preview ?: '-' }}</div>
                    </a>
                @empty
                    <div class="muted" style="padding:10px;">Thread kaydi yok.</div>
                @endforelse
            </div>
        </section>

        <section class="card">
            <div style="max-width:780px;margin:0 auto;">
            @if($selectedThread)
                <h3 style="margin:0 0 8px;">Thread #{{ $selectedThread->id }} Detay</h3>

                @if(auth()->user()?->role !== \App\Models\User::ROLE_SALES_STAFF)
                <form method="post" action="{{ route('messages.center.assign-advisor', $selectedThread->id) }}" style="display:grid;grid-template-columns:1fr 1fr 120px auto;gap:8px;margin-bottom:8px;">
                    @csrf
                    <select name="advisor_user_id" class="btn" style="text-align:left;">
                        @foreach($advisors as $a)
                            <option value="{{ $a->id }}" @selected((int)$selectedThread->advisor_user_id === (int)$a->id)>{{ $a->name }} ({{ $a->email }}) [{{ $a->role }}]</option>
                        @endforeach
                    </select>
                    <select name="department" class="btn" style="text-align:left;">
                        <option value="operations" @selected($selectedThread->department === 'operations')>Operasyon</option>
                        <option value="advisory" @selected($selectedThread->department === 'advisory')>Danışmanlık</option>
                        <option value="finance" @selected($selectedThread->department === 'finance')>Finans</option>
                        <option value="marketing" @selected($selectedThread->department === 'marketing')>Marketing</option>
                        <option value="system" @selected($selectedThread->department === 'system')>Sistem</option>
                    </select>
                    <input name="sla_hours" type="number" min="1" max="168" value="{{ (int)($selectedThread->sla_hours ?: 24) }}" class="btn" placeholder="SLA">
                    <button class="btn btn-primary" type="submit">Danışman Ata</button>
                </form>

                <form method="post" action="{{ route('messages.center.status', $selectedThread->id) }}" style="display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:8px;">
                    @csrf
                    <select name="status" class="btn" style="text-align:left;">
                        <option value="open" @selected($selectedThread->status === 'open')>Acik</option>
                        <option value="closed" @selected($selectedThread->status === 'closed')>Kapali</option>
                    </select>
                    <button class="btn" type="submit">Durum Güncelle</button>
                </form>

                @if((string) $selectedThread->thread_type === 'guest')
                    <form method="post" action="{{ route('messages.center.convert-ticket', $selectedThread->id) }}" style="margin-bottom:8px;">
                        @csrf
                        <button class="btn" type="submit">Bu Thread'i Ticket'a Cevir</button>
                    </form>
                @endif
                @endif

                <form method="post" action="{{ route('messages.center.send', $selectedThread->id) }}" style="margin-bottom:10px;">
                    @csrf
                    <textarea class="mc-textarea" id="mcMsgBody" name="message" rows="4" placeholder="Manager notu / yönlendirme mesajı..."></textarea>
                    <div class="mc-send-row">
                        <button class="btn btn-primary" type="submit">Mesaj Gönder</button>
                        <div class="eg-picker-wrap">
                            <button type="button" class="eg-picker-btn" onclick="egTogglePicker('emoji','mcMsgBody')" title="Emoji">😊</button>
                            <div class="eg-emoji-picker" id="egEmojiPicker_mcMsgBody">
                                <div class="eg-emoji-cats" id="egEmojiCats_mcMsgBody"></div>
                                <div class="eg-emoji-grid" id="egEmojiGrid_mcMsgBody"></div>
                            </div>
                        </div>
                        <div class="eg-picker-wrap">
                            <button type="button" class="eg-picker-btn" onclick="egTogglePicker('gif','mcMsgBody')" title="GIF" style="font-size:var(--tx-xs) !important;font-weight:700 !important;letter-spacing:-.5px">GIF</button>
                            <div class="eg-gif-picker" id="egGifPicker_mcMsgBody">
                                <div class="eg-gif-search">
                                    <input type="text" placeholder="🔍 GIF ara..." oninput="egGifSearch(this.value,'mcMsgBody')">
                                </div>
                                <div class="eg-gif-grid" id="egGifGrid_mcMsgBody">
                                    <div class="eg-gif-loading">Yükleniyor...</div>
                                </div>
                            </div>
                        </div>
                        @foreach(($quickReplies ?? []) as $reply)
                            <button type="button" class="btn quick-reply-btn" data-reply="{{ $reply }}">{{ \Illuminate\Support\Str::limit($reply, 28, '...') }}</button>
                        @endforeach
                    </div>
                </form>

                <div style="max-height:520px;overflow:auto;border:1px solid #dbe4f2;border-radius:10px;">
                    @forelse($messages as $m)
                        @php
                            $isStaff = in_array((string)$m->sender_role, ['manager','senior','mentor','operations_admin','operations_staff','marketing_admin','marketing_staff','sales_admin','sales_staff','system_admin','system_staff'], true);
                            $msgBody = (string)($m->message ?? '');
                            $isGif   = str_starts_with($msgBody, '[gif]:');
                        @endphp
                        <div class="mc-msg {{ $isStaff ? 'staff' : 'customer' }}">
                            <div class="mc-msg-head">
                                <span class="mc-msg-role">{{ strtoupper((string)$m->sender_role) }}</span>
                                <span class="mc-msg-time">{{ \Carbon\Carbon::parse($m->created_at)->format('d.m.y H:i') }}</span>
                            </div>
                            @if(!empty($m->is_quick_request))
                                <div class="mc-quick-badge">⚡ Hızlı Talep</div>
                            @endif
                            @if($msgBody !== '')
                                @if($isGif)
                                    <img class="mc-msg-gif" src="{{ e(substr($msgBody, 6)) }}" alt="GIF" loading="lazy">
                                @else
                                    <div class="mc-msg-body">{{ $msgBody }}</div>
                                @endif
                            @endif
                            @if(!empty($m->attachment_storage_path))
                                <div class="mc-msg-attach">
                                    <a class="btn" href="{{ route('dm.attachment.download', $m->id) }}">📎 {{ $m->attachment_original_name ?: 'Dosyayı indir' }}</a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="muted" style="padding:12px;">Henüz mesaj yok.</div>
                    @endforelse
                </div>
            @else
                <h3 style="margin:0 0 8px;">Detay</h3>
                <div class="muted">Soldan bir thread secin.</div>
            @endif
            </div>{{-- /max-width wrapper --}}
        </section>
    </div>
<script defer src="{{ Vite::asset('resources/js/emoji-gif-picker.js') }}" defer></script>
<script defer src="{{ Vite::asset('resources/js/messages-center.js') }}" defer></script>
@endsection

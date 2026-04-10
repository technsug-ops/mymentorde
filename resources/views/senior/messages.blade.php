@extends('senior.layouts.app')

@section('title', 'Mesajlar — ' . config('brand.name', 'MentorDE'))

@section('content')
@php
    use App\Models\User;
    $currentUser = auth()->user();
    $currentUserId = (int) ($currentUser?->id ?? 0);

    $threadLabel = function ($thread) use ($guestMap) {
        if ($thread->thread_type === 'guest' && $thread->guest_application_id) {
            $g = $guestMap[$thread->guest_application_id] ?? null;
            if ($g) {
                $name = trim(($g->first_name ?? '') . ' ' . ($g->last_name ?? ''));
                return $name !== '' ? $name : ($g->email ?? 'Guest #' . $thread->guest_application_id);
            }
            return 'Guest #' . $thread->guest_application_id;
        }
        if ($thread->thread_type === 'student' && $thread->student_id) {
            return 'Öğrenci ' . $thread->student_id;
        }
        return 'Thread #' . $thread->id;
    };

    $threadBadge = function ($thread) {
        return match ($thread->thread_type) {
            'guest'   => ['👤', 'Guest',   '#dbeafe', '#1e40af'],
            'student' => ['🎓', 'Öğrenci', '#dcfce7', '#166534'],
            default   => ['💬', 'Mesaj',   '#f1f5f9', '#475569'],
        };
    };
@endphp

<div style="padding:24px;">
    {{-- Header --}}
    <div style="margin-bottom:18px;">
        <h1 style="margin:0;font-size:22px;font-weight:700;color:var(--u-text,#0f172a);">
            <span style="margin-right:8px;">💬</span>Mesaj Merkezi
        </h1>
        <div style="font-size:13px;color:var(--u-muted,#64748b);margin-top:4px;">
            Guest ve öğrencilerden gelen mesajları yanıtlayın. Toplam {{ count($threads) }} konuşma.
        </div>
    </div>

    @if(session('status'))
        <div style="padding:10px 14px;background:#dcfce7;color:#166534;border-radius:8px;margin-bottom:12px;font-size:13px;">
            {{ session('status') }}
        </div>
    @endif

    {{-- Arama --}}
    <form method="GET" action="{{ route('senior.messages') }}" style="margin-bottom:16px;">
        <div style="display:flex;gap:8px;align-items:center;">
            <input type="search" name="q" value="{{ $search ?? '' }}"
                   placeholder="🔍 Öğrenci ID, thread tipi veya guest ID ile ara..."
                   style="flex:1;padding:9px 12px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:13px;background:#fff;">
            <button type="submit"
                    style="padding:9px 18px;background:var(--u-brand,#0f172a);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer;">
                Ara
            </button>
            @if(!empty($search))
                <a href="{{ route('senior.messages') }}"
                   style="padding:9px 14px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;">
                    Temizle
                </a>
            @endif
        </div>
    </form>

    @if($threads->isEmpty())
        <div style="background:#fff;border:1px dashed #cbd5e1;border-radius:12px;padding:60px 20px;text-align:center;color:#64748b;">
            <div style="font-size:48px;margin-bottom:12px;">📭</div>
            <div style="font-weight:600;color:#0f172a;">Henüz mesaj yok</div>
            <div style="font-size:13px;margin-top:4px;">Guest veya öğrenciler size yazdığında burada görünecek.</div>
        </div>
    @else
    <div style="display:grid;grid-template-columns:320px 1fr;gap:16px;align-items:start;">
        {{-- Sol: Thread listesi --}}
        <aside style="background:#fff;border:1px solid var(--u-line,#e2e8f0);border-radius:12px;padding:10px;position:sticky;top:16px;max-height:calc(100vh - 120px);overflow-y:auto;">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.5px;padding:6px 10px 10px;">
                Konuşmalar ({{ count($threads) }})
            </div>
            @foreach($threads as $thread)
                @php
                    $isActive = $selectedThread && (int) $selectedThread->id === (int) $thread->id;
                    $badge = $threadBadge($thread);
                    $label = $threadLabel($thread);
                    $lastAt = $thread->last_message_at ? \Carbon\Carbon::parse($thread->last_message_at) : null;
                @endphp
                <a href="{{ route('senior.messages', ['thread_id' => $thread->id, 'q' => $search]) }}"
                   style="display:block;padding:10px 12px;border-radius:8px;text-decoration:none;color:#0f172a;margin-bottom:4px;border:1px solid transparent;{{ $isActive ? 'background:#eff6ff;border-color:#bfdbfe;' : '' }}"
                   onmouseover="this.style.background='{{ $isActive ? '#eff6ff' : '#f8fafc' }}'"
                   onmouseout="this.style.background='{{ $isActive ? '#eff6ff' : 'transparent' }}'">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;background:{{ $badge[2] }};color:{{ $badge[3] }};white-space:nowrap;">
                            {{ $badge[0] }} {{ $badge[1] }}
                        </span>
                        @if($thread->status === 'open')
                            <span style="font-size:9px;color:#16a34a;font-weight:600;">● Açık</span>
                        @endif
                    </div>
                    <div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $label }}">
                        {{ $label }}
                    </div>
                    @if($thread->last_message_preview)
                        <div style="font-size:11px;color:#64748b;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $thread->last_message_preview }}
                        </div>
                    @endif
                    @if($lastAt)
                        <div style="font-size:10px;color:#94a3b8;margin-top:3px;">
                            {{ $lastAt->diffForHumans() }}
                        </div>
                    @endif
                </a>
            @endforeach
        </aside>

        {{-- Sağ: Seçili thread mesajları + cevap formu --}}
        <main style="background:#fff;border:1px solid var(--u-line,#e2e8f0);border-radius:12px;display:flex;flex-direction:column;max-height:calc(100vh - 120px);overflow:hidden;">
            @if($selectedThread)
                @php
                    $selBadge = $threadBadge($selectedThread);
                    $selLabel = $threadLabel($selectedThread);
                @endphp

                {{-- Thread header --}}
                <div style="padding:14px 18px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;">
                    <div>
                        <div style="font-size:15px;font-weight:700;color:#0f172a;">{{ $selLabel }}</div>
                        <div style="font-size:11px;color:#64748b;margin-top:2px;">
                            <span style="padding:2px 7px;border-radius:99px;background:{{ $selBadge[2] }};color:{{ $selBadge[3] }};font-weight:600;">
                                {{ $selBadge[0] }} {{ $selBadge[1] }}
                            </span>
                            @if($selectedThread->department)
                                · Departman: {{ $selectedThread->department }}
                            @endif
                            @if($selectedThread->sla_hours)
                                · SLA: {{ $selectedThread->sla_hours }}h
                            @endif
                        </div>
                    </div>
                    @if($selectedThread->status === 'open')
                        <span style="font-size:11px;padding:4px 10px;border-radius:99px;background:#dcfce7;color:#166534;font-weight:600;">● Açık</span>
                    @endif
                </div>

                {{-- Mesajlar --}}
                <div style="flex:1;overflow-y:auto;padding:16px 18px;display:flex;flex-direction:column;gap:10px;">
                    @if($messages->isEmpty())
                        <div style="text-align:center;color:#94a3b8;padding:40px 20px;font-size:13px;">
                            Henüz mesaj yok. Cevap yazarak konuşmayı başlatın.
                        </div>
                    @else
                        @foreach($messages as $msg)
                            @php
                                $isMine = in_array($msg->sender_role, [User::ROLE_SENIOR, User::ROLE_MENTOR, User::ROLE_MANAGER], true);
                                $msgTime = \Carbon\Carbon::parse($msg->created_at);
                            @endphp
                            <div style="display:flex;{{ $isMine ? 'justify-content:flex-end;' : 'justify-content:flex-start;' }}">
                                <div style="max-width:min(720px,82%);">
                                    <div style="padding:9px 13px;border-radius:14px;font-size:13.5px;line-height:1.55;word-break:break-word;{{ $isMine ? 'background:var(--u-brand,#1e40af);color:#fff;border-bottom-right-radius:4px;' : 'background:#f1f5f9;color:#0f172a;border-bottom-left-radius:4px;' }}">
                                        @if($msg->is_quick_request)
                                            <div style="font-size:10px;padding:1px 7px;border-radius:99px;display:inline-block;margin-bottom:5px;{{ $isMine ? 'background:rgba(255,255,255,.25);color:#fff;' : 'background:#fff3cd;color:#7a5c00;' }}">
                                                ⚡ Hızlı İstek
                                            </div><br>
                                        @endif
                                        @if($msg->message)
                                            {{ $msg->message }}
                                        @endif
                                        @if($msg->attachment_storage_path)
                                            <div style="margin-top:6px;padding-top:6px;border-top:1px solid {{ $isMine ? 'rgba(255,255,255,.25)' : '#e2e8f0' }};">
                                                <a href="{{ route('dm.attachment.download', $msg->id) }}"
                                                   style="font-size:12px;color:inherit;text-decoration:underline;">
                                                    📎 {{ $msg->attachment_original_name ?: 'dosya' }}
                                                    @if($msg->attachment_size_kb)
                                                        ({{ $msg->attachment_size_kb }} KB)
                                                    @endif
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                    <div style="font-size:10px;color:#94a3b8;margin-top:3px;{{ $isMine ? 'text-align:right;' : '' }}">
                                        {{ $msg->sender_role }} · {{ $msgTime->format('d.m.Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Cevap formu --}}
                <form method="POST" action="{{ route('senior.messages.send', $selectedThread) }}"
                      enctype="multipart/form-data"
                      style="padding:12px 18px;border-top:1px solid #f1f5f9;background:#f8fafc;flex-shrink:0;">
                    @csrf
                    <div style="display:flex;gap:8px;align-items:flex-end;">
                        <textarea name="message" rows="2" maxlength="5000"
                                  placeholder="Cevap yazın... (Enter ile satır, Ctrl+Enter ile gönder)"
                                  style="flex:1;border:1px solid #e2e8f0;border-radius:10px;padding:9px 12px;font-size:13.5px;font-family:inherit;resize:vertical;min-height:42px;max-height:140px;background:#fff;"></textarea>
                        <button type="submit"
                                style="padding:10px 18px;background:var(--u-brand,#1e40af);color:#fff;border:none;border-radius:10px;font-weight:600;cursor:pointer;font-size:13px;white-space:nowrap;">
                            Gönder ↑
                        </button>
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;margin-top:8px;font-size:11px;color:#64748b;">
                        <label style="cursor:pointer;">
                            📎 <input type="file" name="attachment" style="font-size:11px;">
                        </label>
                        <label style="cursor:pointer;display:flex;align-items:center;gap:4px;">
                            <input type="checkbox" name="quick_request" value="1">
                            ⚡ Hızlı istek olarak işaretle
                        </label>
                    </div>
                </form>
            @else
                <div style="padding:60px 20px;text-align:center;color:#94a3b8;">
                    <div style="font-size:48px;margin-bottom:12px;">💬</div>
                    <div style="font-weight:600;color:#64748b;">Sol panelden bir konuşma seçin</div>
                </div>
            @endif
        </main>
    </div>
    @endif
</div>
@endsection

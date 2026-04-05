@php
    $taskLayout = in_array(auth()->user()?->role, ['senior','mentor'])
        ? 'senior.layouts.app'
        : 'layouts.staff';
@endphp
@extends($taskLayout)

@section('title', 'Ticket Center')
@section('page_title', 'Ticket Center')

@push('head')
<style>
/* ── Card padding & spacing ── */
.card { padding: 18px 20px; }
.tc-stats { margin-bottom:14px; }

/* ── Layout helpers ── */
.row2 { display:grid; grid-template-columns:1fr 1fr;         gap:10px; }
.row3 { display:grid; grid-template-columns:1fr 1fr 1fr;     gap:10px; }
.row4 { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:10px; }
@media(max-width:900px){ .row4 { grid-template-columns:1fr 1fr; } .row3 { grid-template-columns:1fr 1fr; } }
@media(max-width:600px){ .row4,.row3,.row2 { grid-template-columns:1fr; } }

/* ── Form inputs ── */
.card input:not([type=checkbox]):not([type=radio]),
.card select,
.card textarea {
    width:100%; box-sizing:border-box;
    height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e5e7eb);
    border-radius:8px;
    background:var(--u-bg,#f9fafb);
    color:var(--u-text,#111827);
    font-size:13px; outline:none;
    transition:border-color .15s, box-shadow .15s;
}
.card textarea { height:auto; padding:8px 10px; }
.card input:focus,.card select:focus,.card textarea:focus {
    border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.12);
}

/* ── Pill navigation ── */
.link-group-box { margin-top:12px; }
.link-group-label { font-size:11px; font-weight:700; color:var(--u-muted,#6b7280); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:6px; }
.pill-links { display:flex; flex-wrap:wrap; gap:6px; }
.pill-link { display:inline-block; padding:4px 12px; border-radius:20px; border:1px solid var(--u-line,#e5e7eb); background:var(--u-card,#fff); color:var(--u-text,#374151); font-size:12px; font-weight:500; text-decoration:none; transition:all .12s; }
.pill-link:hover { background:#eff6ff; border-color:#93c5fd; color:#1d4ed8; }
.pill-link.active { background:#2563eb; border-color:#2563eb; color:#fff; font-weight:600; }

/* ── Quick links ── */
.btn-link { display:inline-flex; align-items:center; min-height:32px; border-radius:8px; border:1px solid var(--u-line,#e5e7eb); background:#eef4fb; color:#204d87; font-weight:600; text-decoration:none; padding:0 12px; font-size:13px; transition:background .12s; }
.btn-link:hover { background:#dbeafe; }

/* ── Stats bar ── */
.tc-stats { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; margin-bottom:12px; }
.s-stat { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e7eb); border-radius:10px; padding:10px 14px; }
.s-stat .k { font-size:11px; font-weight:600; color:var(--u-muted,#6b7280); text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.s-stat .v { font-size:22px; font-weight:700; color:var(--u-text,#111827); line-height:1; }
@media(max-width:900px){ .tc-stats { grid-template-columns:repeat(3,1fr); } }
@media(max-width:600px){ .tc-stats { grid-template-columns:repeat(2,1fr); } }

/* ── Ticket list ── */
.ticket-list { display:flex; flex-direction:column; gap:0; border:1px solid var(--u-line,#e5e7eb); border-radius:10px; overflow:hidden; }

/* Row: compact header */
.tc-row { display:grid; grid-template-columns:32px 1fr 90px 80px 100px 140px 90px; align-items:center; gap:8px; padding:8px 12px; border-bottom:1px solid var(--u-line,#e5e7eb); background:var(--u-card,#fff); cursor:pointer; transition:background .1s; user-select:none; }
.tc-row:last-of-type { border-bottom:none; }
.tc-row:hover { background:var(--u-bg,#f9fafb); }
.tc-row.tc-open { background:#eff6ff; border-bottom-color:#bfdbfe; }

.tc-row .tc-check { display:flex; align-items:center; justify-content:center; }
.tc-row .tc-check input { width:auto; min-height:0; cursor:pointer; }
.tc-row .tc-title { font-size:13px; font-weight:600; color:var(--u-text,#111827); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tc-row .tc-id { font-size:11px; color:var(--u-muted,#9ca3af); font-weight:500; }
.tc-row .tc-guest { font-size:12px; color:var(--u-muted,#6b7280); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tc-row .tc-date { font-size:11px; color:var(--u-muted,#9ca3af); text-align:right; }
@media(max-width:1100px){ .tc-row { grid-template-columns:32px 1fr 90px 80px 100px 90px; } .tc-date { display:none; } }
@media(max-width:800px){ .tc-row { grid-template-columns:32px 1fr 90px 90px; } .tc-guest,.tc-dept { display:none; } }

/* Expandable detail */
.tc-detail { display:none; padding:14px 16px; border-bottom:1px solid var(--u-line,#e5e7eb); background:#f8faff; }
.tc-detail.tc-open { display:block; }
.tc-detail .tc-msg { font-size:13px; color:var(--u-text,#374151); line-height:1.6; margin-bottom:12px; padding:10px 12px; background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e7eb); border-radius:8px; }
.tc-detail .tc-replies { margin-bottom:12px; }
.tc-detail .reply { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e7eb); border-radius:8px; padding:8px 12px; margin-bottom:6px; font-size:13px; }
.tc-detail .reply strong { color:var(--u-text,#111827); }
.tc-detail .reply .t-meta { font-size:11px; color:var(--u-muted,#9ca3af); margin-top:2px; }
.tc-detail .tc-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }

.tc-section-label { font-size:11px; font-weight:700; color:var(--u-muted,#9ca3af); text-transform:uppercase; letter-spacing:.06em; margin-bottom:8px; }

/* List header */
.tc-head-row { display:grid; grid-template-columns:32px 1fr 90px 80px 100px 140px 90px; gap:8px; padding:6px 12px; background:var(--u-bg,#f3f4f6); border-bottom:1px solid var(--u-line,#e5e7eb); }
.tc-head-row span { font-size:11px; font-weight:700; color:var(--u-muted,#6b7280); text-transform:uppercase; letter-spacing:.05em; }
@media(max-width:1100px){ .tc-head-row { grid-template-columns:32px 1fr 90px 80px 100px 90px; } .tc-head-row span:last-child { display:none; } }
@media(max-width:800px){ .tc-head-row { grid-template-columns:32px 1fr 90px 90px; } .tc-head-row span:nth-child(n+5) { display:none; } }

/* Chip / badge */
.chip { display:inline-block; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; background:var(--u-bg,#f3f4f6); color:var(--u-muted,#6b7280); }
.chip.open    { background:#dcfce7; color:#15803d; }
.chip.in_progress { background:#fef9c3; color:#854d0e; }
.chip.waiting_response { background:#dbeafe; color:#1d4ed8; }
.chip.closed  { background:#f3f4f6; color:#9ca3af; }
.chip.high    { background:#fee2e2; color:#991b1b; }
.chip.medium  { background:#fef3c7; color:#92400e; }
.chip.low     { background:#dcfce7; color:#166534; }

/* t-meta */
.t-meta { font-size:12px; color:var(--u-muted,#6b7280); }

/* Btn inline */
.btn-sm { display:inline-flex; align-items:center; height:30px; padding:0 12px; border-radius:7px; font-size:12px; font-weight:600; cursor:pointer; border:none; transition:background .12s; }
.btn-route { background:#2563eb; color:#fff; }
.btn-route:hover { background:#1d4ed8; }
.btn-dm { background:#7c3aed; color:#fff; }
.btn-dm:hover { background:#6d28d9; }
.btn-close-t { background:#f3f4f6; color:#374151; border:1px solid var(--u-line,#e5e7eb); }
.btn-close-t:hover { background:#e5e7eb; }
</style>
@endpush

@section('content')
    {{-- Top card: description + quick links + dept nav --}}
    <section class="card" style="margin-bottom:12px;">
        @php
            $activeDepartment = (string)($routeDepartment ?? '');
            $total      = collect($rows ?? [])->count();
            $open       = collect($rows ?? [])->where('status','open')->count();
            $inProgress = collect($rows ?? [])->where('status','in_progress')->count();
            $waiting    = collect($rows ?? [])->where('status','waiting_response')->count();
            $closed     = collect($rows ?? [])->where('status','closed')->count();
        @endphp
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
            <span class="t-meta">Manager merkezi ticket yönetimi — departman veya email ile yönlendir.</span>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="/manager/dashboard"  class="btn-link">Manager</a>
                <a href="/tasks"              class="btn-link">Task Board</a>
                <a href="/manager/requests"   class="btn-link">Request Center</a>
            </div>
        </div>
        <div class="link-group-box">
            <span class="link-group-label">Departman Kuyrukları</span>
            <div class="pill-links">
                @if($roleScopedDepartment === null)
                    {{-- Manager / system_admin: tüm sekmeler --}}
                    <a href="/tickets-center"            class="pill-link {{ $activeDepartment===''           ? 'active':'' }}">Tüm Ticketlar</a>
                    <a href="/tickets-center/operations" class="pill-link {{ $activeDepartment==='operations' ? 'active':'' }}">Operasyon</a>
                    <a href="/tickets-center/finance"    class="pill-link {{ $activeDepartment==='finance'    ? 'active':'' }}">Finans</a>
                    <a href="/tickets-center/advisory"   class="pill-link {{ $activeDepartment==='advisory'   ? 'active':'' }}">Danışmanlık</a>
                    <a href="/tickets-center/marketing"  class="pill-link {{ $activeDepartment==='marketing'  ? 'active':'' }}">Marketing</a>
                    <a href="/tickets-center/system"     class="pill-link {{ $activeDepartment==='system'     ? 'active':'' }}">Sistem</a>
                @else
                    {{-- Scoped rol: sadece kendi departmanı --}}
                    @php $deptLabels = ['operations'=>'Operasyon','finance'=>'Finans','advisory'=>'Danışmanlık','marketing'=>'Marketing','system'=>'Sistem']; @endphp
                    <a href="/tickets-center/{{ $roleScopedDepartment }}" class="pill-link active">
                        {{ $deptLabels[$roleScopedDepartment] ?? $roleScopedDepartment }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <div class="tc-stats">
        <div class="s-stat"><div class="k">Toplam</div><div class="v">{{ $total }}</div></div>
        <div class="s-stat"><div class="k">Açık</div><div class="v">{{ $open }}</div></div>
        <div class="s-stat"><div class="k">İşlemde</div><div class="v">{{ $inProgress }}</div></div>
        <div class="s-stat"><div class="k">Yanıt Bekliyor</div><div class="v">{{ $waiting }}</div></div>
        <div class="s-stat"><div class="k">Kapalı</div><div class="v">{{ $closed }}</div></div>
    </div>

    {{-- Filter --}}
    <section class="card" style="margin-bottom:12px;">
        <div class="tc-section-label" style="margin-bottom:10px;">Filtrele</div>
        <form method="GET" action="/tickets-center">
            <div class="row4">
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ara: konu / mesaj / email">
                <select name="status">
                    <option value="">Tüm durumlar</option>
                    @foreach(($statusOptions ?? []) as $k => $v)
                        <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <select name="department" @disabled(!empty($routeDepartment))>
                    <option value="">Tüm departmanlar</option>
                    @foreach(($departmentOptions ?? []) as $k => $v)
                        <option value="{{ $k }}" @selected(($filters['department'] ?? '') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                @if(!empty($routeDepartment))
                    <input type="hidden" name="department" value="{{ $routeDepartment }}">
                @elseif(!empty($roleScopedDepartment))
                    <input type="hidden" name="department" value="{{ $roleScopedDepartment }}">
                @endif
                <select name="priority">
                    <option value="">Tüm öncelikler</option>
                    @foreach(($priorityOptions ?? []) as $k => $v)
                        <option value="{{ $k }}" @selected(($filters['priority'] ?? '') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:8px;margin-top:8px;">
                <button type="submit" class="btn-sm btn-route">Filtrele</button>
                <a href="{{ !empty($routeDepartment) ? '/tickets-center/'.$routeDepartment : '/tickets-center' }}" style="text-decoration:none;">
                    <button type="button" class="btn-sm btn-close-t">Temizle</button>
                </a>
            </div>
        </form>
    </section>

    {{-- Bulk operations --}}
    <section class="card" style="margin-bottom:12px;">
        <div class="tc-section-label" style="margin-bottom:10px;">Toplu İşlem</div>
        <form method="POST" action="/tickets-center/bulk-route" id="bulkRouteForm">
            @csrf
            <input type="hidden" name="current_department" value="{{ $routeDepartment ?? '' }}">
            <div class="row4">
                <input id="bulkTicketIds" name="ticket_ids_preview" placeholder="Aşağıdan seçim yapın" readonly>
                <select name="department" required @disabled(!empty($roleScopedDepartment))>
                    @foreach(($departmentOptions ?? []) as $k => $v)
                        <option value="{{ $k }}" @selected((($roleScopedDepartment ?? '') !== '' ? $roleScopedDepartment : ($filters['department'] ?? 'operations')) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                @if(!empty($roleScopedDepartment))
                    <input type="hidden" name="department" value="{{ $roleScopedDepartment }}">
                @endif
                <input name="assignee_email" placeholder="Sorumlu email (opsiyonel)" list="userEmails">
                <select name="status">
                    @foreach(($statusOptions ?? []) as $k => $v)
                        <option value="{{ $k }}" @selected($k === 'open')>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:8px;">
                <label style="display:flex;align-items:center;gap:5px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" id="selectAllTickets" style="width:auto;min-height:0;"> Tümünü seç
                </label>
                <label style="display:flex;align-items:center;gap:5px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="auto_assign" value="1" style="width:auto;min-height:0;"> Otomatik dağıt
                </label>
                <select name="sla_hours" style="height:30px;font-size:12px;border-radius:7px;padding:0 8px;">
                    <option value="">SLA Deadline —</option>
                    <option value="4">⏱ 4 saat</option>
                    <option value="8">⏱ 8 saat</option>
                    <option value="24">📅 24 saat</option>
                    <option value="48">📅 48 saat</option>
                    <option value="72">📅 72 saat</option>
                    <option value="168">📅 1 hafta</option>
                </select>
                <span class="t-meta" id="ticketSelectedCount">0 seçili</span>
                <button type="submit" class="btn-sm btn-route">Toplu Yönlendir</button>
            </div>
        </form>
        <form method="POST" action="/tickets-center/bulk-status" id="bulkStatusForm" style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
            @csrf
            <input type="hidden" name="current_department" value="{{ $routeDepartment ?? '' }}">
            <button type="submit" name="status" value="closed" class="btn-sm btn-close-t">Seçilenleri Kapat</button>
            <button type="submit" name="status" value="open"   class="btn-sm btn-route" style="background:#059669;">Seçilenleri Yeniden Aç</button>
        </form>
    </section>

    {{-- Ticket list --}}
    <section class="card" style="padding:0;overflow:hidden;">
        @if(($rows ?? [])->isNotEmpty())
        <div class="ticket-list">
            {{-- Header row --}}
            <div class="tc-head-row">
                <span></span>
                <span>Konu</span>
                <span>Durum</span>
                <span>Öncelik</span>
                <span>Departman</span>
                <span>Guest</span>
                <span>Son Yanıt</span>
            </div>

            @foreach(($rows ?? []) as $row)
            @php
                $statusLabel = $statusOptions[$row->status] ?? $row->status;
                $priorityLabel = $priorityOptions[$row->priority] ?? $row->priority;
                $deptLabel = $departmentOptions[$row->department] ?? $row->department;
                $guestName = trim(($row->guestApplication?->first_name ?? '') . ' ' . ($row->guestApplication?->last_name ?? ''));
                $lastReply = $row->last_replied_at ? \Carbon\Carbon::parse($row->last_replied_at)->diffForHumans() : '-';
                $rowSlaDue = $row->sla_due_at ? \Carbon\Carbon::parse($row->sla_due_at) : null;
                $rowSlaOverdue = $rowSlaDue && $rowSlaDue->isPast() && $row->status !== 'closed';
            @endphp

            {{-- Compact row --}}
            <div class="tc-row {{ $rowSlaOverdue ? 'style=border-left:3px solid #dc2626;' : '' }}" id="tcr-{{ $row->id }}"
                 onclick="tcToggle({{ $row->id }}, event)"
                 @if($rowSlaOverdue) style="border-left:3px solid #dc2626;" @endif>
                <div class="tc-check" onclick="event.stopPropagation()">
                    <input type="checkbox" class="ticket-select" value="{{ $row->id }}">
                </div>
                <div>
                    <div class="tc-title">
                        #{{ $row->id }} {{ $row->subject }}
                        @if($rowSlaOverdue)
                            <span style="font-size:10px;font-weight:700;color:#dc2626;padding:1px 5px;background:#fee2e2;border-radius:4px;margin-left:4px;">DEADLINE GEÇTİ</span>
                        @elseif($rowSlaDue && !$rowSlaOverdue)
                            <span style="font-size:10px;color:var(--u-muted);padding:1px 5px;background:var(--u-bg);border-radius:4px;margin-left:4px;">⏱ {{ $rowSlaDue->format('d.m H:i') }}</span>
                        @endif
                    </div>
                </div>
                <div><span class="chip {{ $row->status }}">{{ $statusLabel }}</span></div>
                <div><span class="chip {{ $row->priority ?? '' }}">{{ $priorityLabel }}</span></div>
                <div class="tc-dept"><span class="chip">{{ $deptLabel }}</span></div>
                <div class="tc-guest">{{ $guestName ?: ($row->guestApplication?->email ?? '-') }}</div>
                <div class="tc-date">{{ $lastReply }}</div>
            </div>

            {{-- Expandable detail --}}
            <div class="tc-detail" id="tcd-{{ $row->id }}">
                {{-- Message --}}
                <div class="tc-section-label">Mesaj</div>
                <div class="tc-msg">{{ $row->message }}</div>

                {{-- SLA info --}}
                @php
                    $slaDue = $row->sla_due_at ? \Carbon\Carbon::parse($row->sla_due_at) : null;
                    $slaOverdue  = $slaDue && $slaDue->isPast() && $row->status !== 'closed';
                    $slaUrgent   = $slaDue && !$slaDue->isPast() && $slaDue->diffInHours(now()) <= 2 && $row->status !== 'closed';
                @endphp
                <div class="t-meta" style="margin-bottom:10px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                    <span>Guest: <strong>{{ $row->guestApplication?->email ?? '-' }}</strong></span>
                    <span>SLA İlk Yanıt: {{ $row->sla_first_response_hours !== null ? $row->sla_first_response_hours.'s' : '-' }}</span>
                    <span>SLA Çözüm: {{ $row->sla_resolution_hours !== null ? $row->sla_resolution_hours.'s' : '-' }}</span>
                    @if($slaDue)
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;
                            {{ $slaOverdue ? 'background:#fee2e2;color:#dc2626;' : ($slaUrgent ? 'background:#fef3c7;color:#d97706;' : 'background:#dcfce7;color:#16a34a;') }}">
                            {{ $slaOverdue ? '🚨 Deadline Geçti' : ($slaUrgent ? '⚠️ Yaklaşıyor' : '✅ Deadline') }}:
                            {{ $slaDue->format('d.m H:i') }}
                        </span>
                    @endif
                </div>

                {{-- Replies --}}
                @if($row->replies->isNotEmpty())
                <div class="tc-replies">
                    <div class="tc-section-label">Yanıtlar ({{ $row->replies->count() }})</div>
                    @foreach($row->replies as $reply)
                    <div class="reply">
                        <strong>{{ $reply->author_role }}</strong>
                        <span class="t-meta"> — {{ $reply->author_email ?: '-' }} &nbsp;·&nbsp; {{ $reply->created_at }}</span>
                        <div style="margin-top:4px;">{{ $reply->message }}</div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Route form --}}
                <div class="tc-section-label">Yönlendir / Güncelle</div>
                <form method="POST" action="/tickets-center/{{ $row->id }}/route" onclick="event.stopPropagation()">
                    @csrf
                    <input type="hidden" name="current_department" value="{{ $routeDepartment ?? '' }}">
                    <div class="row3" style="margin-bottom:8px;">
                        <select name="department" @disabled(!empty($roleScopedDepartment))>
                            @foreach(($departmentOptions ?? []) as $k => $v)
                                <option value="{{ $k }}" @selected((string)(($roleScopedDepartment ?? '') !== '' ? $roleScopedDepartment : $row->department) === (string)$k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        @if(!empty($roleScopedDepartment))
                            <input type="hidden" name="department" value="{{ $roleScopedDepartment }}">
                        @endif
                        <input name="assignee_email" value="{{ $row->assignedUser?->email ?? '' }}" placeholder="Sorumlu email (opsiyonel)" list="userEmails">
                        <select name="status">
                            @foreach(($statusOptions ?? []) as $k => $v)
                                <option value="{{ $k }}" @selected((string)$row->status === (string)$k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tc-actions" style="flex-wrap:wrap;gap:8px;">
                        <label style="display:flex;align-items:center;gap:5px;font-size:13px;cursor:pointer;">
                            <input type="checkbox" name="auto_assign" value="1" style="width:auto;min-height:0;"> Otomatik ata
                        </label>
                        <select name="sla_hours" style="height:30px;font-size:12px;border-radius:7px;padding:0 8px;min-width:130px;">
                            <option value="">SLA Deadline —</option>
                            <option value="4" @selected((int)($row->sla_hours??0)===4)>⏱ 4 saat</option>
                            <option value="8" @selected((int)($row->sla_hours??0)===8)>⏱ 8 saat</option>
                            <option value="24" @selected((int)($row->sla_hours??0)===24)>📅 24 saat (1 gün)</option>
                            <option value="48" @selected((int)($row->sla_hours??0)===48)>📅 48 saat (2 gün)</option>
                            <option value="72" @selected((int)($row->sla_hours??0)===72)>📅 72 saat (3 gün)</option>
                            <option value="168" @selected((int)($row->sla_hours??0)===168)>📅 1 hafta</option>
                        </select>
                        <button type="submit" class="btn-sm btn-route">Yönlendir</button>
                    </div>
                </form>

                {{-- DM convert --}}
                <form method="POST" action="{{ route('tickets.center.convert-dm', $row->id) }}" style="margin-top:8px;" onclick="event.stopPropagation()">
                    @csrf
                    <button type="submit" class="btn-sm btn-dm">DM'ye Çevir (Mesaj Merkezi)</button>
                </form>
            </div>
            @endforeach
        </div>
        @else
            <div class="t-meta" style="padding:24px 16px;">Ticket bulunamadı.</div>
        @endif
    </section>

    {{-- Usage guide --}}
    <section class="card" style="margin-top:12px;">
        <div class="tc-section-label" style="margin-bottom:8px;">Kullanım Kılavuzu</div>
        <ol class="t-meta" style="margin:0;padding-left:18px;line-height:2;">
            <li>Ticket satırına tıkla → detay ve yönlendirme formu açılır.</li>
            <li>Departmanı belirle, gerekirse sorumlu email gir ve <strong>Yönlendir</strong>'e bas.</li>
            <li>Yönlendirme sonrası task ataması otomatik senkronlanır.</li>
            <li>Manager dışı erişim için rol-template'e <code>ticket.center.view</code> ve <code>ticket.center.route</code> izinlerini ver.</li>
        </ol>
    </section>

    <datalist id="userEmails">
        @foreach(($users ?? []) as $u)
            <option value="{{ $u->email }}">{{ $u->name }} ({{ $u->role }})</option>
        @endforeach
    </datalist>

<script>
window.tcToggle = function(id, e) {
    if (e && e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON' || e.target.tagName === 'SELECT' || e.target.tagName === 'A')) return;
    var row    = document.getElementById('tcr-' + id);
    var detail = document.getElementById('tcd-' + id);
    if (!detail) return;
    var isOpen = detail.classList.toggle('tc-open');
    if (row) row.classList.toggle('tc-open', isOpen);
};
</script>
@endsection

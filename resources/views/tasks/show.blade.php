@php
    $taskLayout = in_array(auth()->user()?->role, ['senior','mentor'])
        ? 'senior.layouts.app'
        : 'layouts.staff';
@endphp
@extends($taskLayout)

@section('title', '#' . $task->id . ' — ' . $task->title)
@section('page_title', 'Görev Detayı')

@push('head')
<style>
/* ── Layout ── */
.ts-wrap  { max-width:1100px; margin:0 auto; }
.ts-topbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.ts-back  { display:inline-flex; align-items:center; gap:5px; font-size:13px; color:var(--u-brand); text-decoration:none; font-weight:600; }
.ts-back:hover { text-decoration:underline; }
.ts-grid  { display:grid; grid-template-columns:1fr 300px; gap:16px; align-items:start; }
@media(max-width:880px){ .ts-grid { grid-template-columns:1fr; } }

/* ── Hero ── */
.ts-hero  { background:var(--u-card); border:1px solid var(--u-line); border-radius:14px; padding:20px 24px; margin-bottom:14px; border-left:5px solid #e2e8f0; }
.ts-hero.p-urgent { border-left-color:#ef4444; }
.ts-hero.p-high   { border-left-color:#f59e0b; }
.ts-hero.p-normal { border-left-color:#3b82f6; }
.ts-hero.p-low    { border-left-color:#94a3b8; }
.ts-hero-num   { font-size:11px; color:var(--u-muted); font-weight:700; margin-right:5px; }
.ts-hero-title { font-size:1.25rem; font-weight:800; color:var(--u-text); line-height:1.3; margin-bottom:10px; }
.ts-hero-tags  { display:flex; gap:6px; flex-wrap:wrap; align-items:center; margin-bottom:12px; }
.ts-hero-desc  { font-size:var(--tx-sm,.8125rem); color:var(--u-text); line-height:1.7; white-space:pre-wrap; background:var(--u-bg); border-radius:8px; padding:10px 12px; }
.ts-progress   { height:5px; border-radius:3px; background:var(--u-line); margin-top:10px; overflow:hidden; }
.ts-progress-bar { height:100%; background:var(--u-ok); border-radius:3px; transition:width .3s; }

/* ── Status badge colors ── */
.st-todo        { background:#e0e7ff; color:#3730a3; }
.st-in_progress { background:#dbeafe; color:#1e40af; }
.st-in_review   { background:#ede9fe; color:#6d28d9; }
.st-on_hold     { background:#fef3c7; color:#92400e; }
.st-blocked     { background:#fee2e2; color:#991b1b; }
.st-done        { background:#d1fae5; color:#065f46; }
.st-cancelled   { background:#f3f4f6; color:#6b7280; }

/* ── Sections ── */
.ts-sec  { background:var(--u-card); border:1px solid var(--u-line); border-radius:12px; margin-bottom:14px; overflow:hidden; }
.ts-sec-hd { font-size:var(--tx-xs,.6875rem); font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.06em; padding:10px 16px 8px; border-bottom:1px solid var(--u-line); display:flex; align-items:center; justify-content:space-between; }
.ts-sec-body { padding:12px 16px; }

/* ── Actions ── */
.ts-actions { display:flex; flex-wrap:wrap; gap:8px; padding:12px 16px; }
.ts-hold-bar { background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:8px 14px; font-size:var(--tx-sm,.8125rem); color:#92400e; margin:0 16px 12px; }

/* ── Meta rows ── */
.ts-row { display:flex; justify-content:space-between; align-items:center; gap:8px; padding:7px 0; border-bottom:1px solid var(--u-line); font-size:var(--tx-sm,.8125rem); }
.ts-row:last-child { border-bottom:none; }
.ts-lbl { color:var(--u-muted); font-weight:600; white-space:nowrap; flex-shrink:0; }
.ts-val { color:var(--u-text); text-align:right; font-weight:500; }

/* ── Checklist ── */
.ts-cl-row { display:flex; align-items:center; gap:8px; padding:6px 0; border-bottom:1px solid var(--u-line); font-size:var(--tx-sm,.8125rem); }
.ts-cl-row:last-of-type { border-bottom:none; }
.ts-cl-row input[type=checkbox] { width:16px; height:16px; min-height:0; accent-color:var(--u-brand); flex-shrink:0; cursor:pointer; }
.ts-cl-row.done span { text-decoration:line-through; color:var(--u-muted); }
.ts-cl-del { background:none; border:none; cursor:pointer; color:var(--u-muted); font-size:16px; padding:0 4px; flex-shrink:0; line-height:1; }
.ts-cl-del:hover { color:var(--u-danger); }
.ts-cl-add { display:flex; gap:6px; margin-top:10px; padding-top:10px; border-top:1px solid var(--u-line); }
.ts-cl-add input { flex:1; height:32px; padding:0 8px; border:1px solid var(--u-line); border-radius:7px; font-size:var(--tx-sm,.8125rem); background:var(--u-bg); color:var(--u-text); outline:none; }
.ts-cl-add input:focus { border-color:var(--u-brand); }

/* ── Comments ── */
.ts-cmt-item { display:flex; gap:10px; padding:10px 0; border-bottom:1px solid var(--u-line); }
.ts-cmt-item:last-child { border-bottom:none; }
.ts-cmt-avatar { width:32px; height:32px; border-radius:50%; background:var(--u-brand); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
.ts-cmt-body { flex:1; min-width:0; }
.ts-cmt-meta { display:flex; gap:8px; align-items:center; margin-bottom:4px; }
.ts-cmt-name { font-size:var(--tx-sm,.8125rem); font-weight:700; color:var(--u-text); }
.ts-cmt-role { font-size:var(--tx-xs,.6875rem); color:var(--u-muted); }
.ts-cmt-time { font-size:var(--tx-xs,.6875rem); color:var(--u-muted); margin-left:auto; }
.ts-cmt-text { font-size:var(--tx-sm,.8125rem); color:var(--u-text); line-height:1.6; white-space:pre-wrap; }
.ts-cmt-att  { font-size:var(--tx-xs,.6875rem); margin-top:4px; color:var(--u-brand); }
.ts-cmt-form { margin-top:12px; padding-top:12px; border-top:1px solid var(--u-line); }
.ts-cmt-form textarea { width:100%; min-height:72px; padding:8px 10px; border:1px solid var(--u-line); border-radius:8px; font-size:var(--tx-sm,.8125rem); resize:vertical; outline:none; background:var(--u-bg); color:var(--u-text); box-sizing:border-box; }
.ts-cmt-form textarea:focus { border-color:var(--u-brand); box-shadow:0 0 0 2px rgba(30,64,175,.08); }
.ts-cmt-actions { display:flex; gap:8px; margin-top:8px; align-items:center; }

/* ── Activity ── */
.ts-act-row { display:flex; gap:10px; padding:7px 0; border-bottom:1px solid var(--u-line); font-size:var(--tx-xs,.6875rem); }
.ts-act-row:last-child { border-bottom:none; }
.ts-act-who  { font-weight:700; color:var(--u-text); white-space:nowrap; }
.ts-act-what { color:var(--u-muted); flex:1; }
.ts-act-when { color:var(--u-muted); white-space:nowrap; }

/* ── Watcher chip ── */
.ts-wchip { display:inline-flex; align-items:center; gap:4px; background:#eef4fb; border:1px solid #c7ddf9; border-radius:20px; padding:3px 10px; font-size:var(--tx-xs,.6875rem); color:#1d4f8c; margin:2px; }

/* ── Edit form ── */
.ts-ef label { font-size:var(--tx-xs,.6875rem); font-weight:700; color:var(--u-muted); display:block; margin-bottom:3px; margin-top:10px; }
.ts-ef label:first-child { margin-top:0; }
.ts-ef input, .ts-ef select, .ts-ef textarea {
    width:100%; box-sizing:border-box; padding:6px 10px; border:1px solid var(--u-line);
    border-radius:7px; font-size:var(--tx-sm,.8125rem); background:var(--u-bg); color:var(--u-text); outline:none;
}
.ts-ef input:focus, .ts-ef select:focus, .ts-ef textarea:focus { border-color:var(--u-brand); }
.ts-ef textarea { min-height:72px; resize:vertical; }

/* ── Inline source chip ── */
.ts-src-chip { display:inline-flex; align-items:center; gap:4px; font-size:var(--tx-xs,.6875rem); background:#eef4fb; border:1px solid #c7ddf9; border-radius:6px; padding:2px 8px; color:#1d4f8c; text-decoration:none; font-weight:600; }
.ts-src-chip:hover { background:#dbeafe; }
</style>
@endpush

@section('content')
@php
$statusLabels = ['todo'=>'Yapılacak','in_progress'=>'Devam Ediyor','in_review'=>'İncelemede','on_hold'=>'Beklemede','blocked'=>'Bloke','done'=>'Tamamlandı','cancelled'=>'İptal'];
$prioLabels   = ['low'=>'Düşük','normal'=>'Normal','high'=>'Yüksek','urgent'=>'Acil'];
$prioCls      = ['low'=>'p-low','normal'=>'p-normal','high'=>'p-high','urgent'=>'p-urgent'];
$roleLabels   = ['manager'=>'Manager','system_admin'=>'Sistem Admin','marketing_admin'=>'Mktg Admin','sales_admin'=>'Satış Admin','marketing_staff'=>'Mktg Staff','sales_staff'=>'Satış Staff','senior'=>'Senior','mentor'=>'Mentor','operations_admin'=>'Ops Admin','finance_admin'=>'Fin Admin'];
$dueDate    = $task->due_date ? $task->due_date->format('d.m.Y') : null;
$isOverdue  = $dueDate && !in_array($task->status, ['done','cancelled']) && $task->due_date->isPast();
$isDueToday = $dueDate && !in_array($task->status, ['done','cancelled']) && $task->due_date->isToday();
$sPri       = $task->priority ?? 'normal';
$sStatus    = $task->status ?? 'todo';
$checklist  = $task->checklists ?? collect();
$doneCount  = $checklist->where('is_done', true)->count();
$totalCount = $checklist->count();
$clPct      = $totalCount > 0 ? round($doneCount / $totalCount * 100) : 0;
$watchers   = $task->watchers ?? collect();
$depTask    = $task->dependsOn;
$authId     = auth()->id();
$isWatching = $watchers->contains('user_id', $authId);

/* Kaynak linki */
$srcLink = null; $srcLabel = null;
$st = (string)($task->source_type ?? '');
if (in_array($st, ['guest_ticket_opened','guest_ticket_replied'], true)) { $srcLink = '/tickets-center/'.($task->department ?: 'operations'); $srcLabel = 'Ticket Merkezi'; }
elseif ($st === 'guest_document_uploaded') { $srcLink = '/config#guest-ops'; $srcLabel = 'Belge Onay'; }
elseif (in_array($st, ['guest_contract_requested','guest_contract_signed_uploaded'], true)) { $srcLink = '/config#guest-applications'; $srcLabel = 'Guest Dönüşüm'; }
elseif ($st === 'student_onboarding_auto') { $srcLink = '/tasks/advisory'; $srcLabel = 'Advisory'; }
elseif ($st === 'manager_request_created') { $srcLink = '/manager/requests'; $srcLabel = 'Manager Request'; }
elseif (in_array($st, ['conversation_quick_request','conversation_response_due','conversation_message'], true)) { $srcLink = '/messages-center/advisory'; $srcLabel = 'Mesaj Merkezi'; }
@endphp

<div class="ts-wrap">

    {{-- Top bar --}}
    <div class="ts-topbar">
        <a href="{{ $baseUrl }}" class="ts-back">← Görev Listesi</a>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @if(session('success'))<span class="badge ok" style="font-size:12px;">{{ session('success') }}</span>@endif
            @if(session('error'))<span class="badge danger" style="font-size:12px;">{{ session('error') }}</span>@endif
            {{-- Watcher toggle --}}
            <form method="POST" action="/tasks/{{ $task->id }}/watch" id="watch-form">
                @csrf
                @if($isWatching) @method('DELETE') @endif
                <button type="submit" class="btn alt" style="font-size:12px;padding:6px 12px;" title="{{ $isWatching ? 'Takibi bırak' : 'Takip et' }}">
                    👁 {{ $isWatching ? 'Takip Ediliyor' : 'Takip Et' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Hero kart --}}
    <div class="ts-hero {{ $prioCls[$sPri] ?? 'p-normal' }}">
        <div class="ts-hero-title">
            <span class="ts-hero-num">#{{ $task->id }}</span>{{ $task->title }}
        </div>
        <div class="ts-hero-tags">
            <span class="badge st-{{ $sStatus }}" style="font-size:12px;">{{ $statusLabels[$sStatus] ?? $sStatus }}</span>
            <span class="badge {{ $sPri === 'urgent' ? 'danger' : ($sPri === 'high' ? 'warn' : 'info') }}" style="font-size:12px;">{{ $prioLabels[$sPri] ?? $sPri }}</span>
            @if($dueDate)
                <span class="badge {{ $isOverdue ? 'danger' : ($isDueToday ? 'warn' : '') }}" style="font-size:12px;">
                    📅 {{ $dueDate }}{{ $isOverdue ? ' — Gecikti' : ($isDueToday ? ' — Bugün' : '') }}
                </span>
            @endif
            @if($task->is_recurring)
                <span class="badge info" style="font-size:12px;">🔁 {{ ucfirst($task->recurrence_pattern ?? 'Tekrarlı') }}</span>
            @endif
            @if($totalCount > 0)
                <span class="badge {{ $doneCount === $totalCount ? 'ok' : 'info' }}" style="font-size:12px;">☑ {{ $doneCount }}/{{ $totalCount }}</span>
            @endif
            @if($srcLink)
                <a href="{{ $srcLink }}" class="ts-src-chip">↗ {{ $srcLabel }}</a>
            @endif
        </div>
        @if($task->description)
            <div class="ts-hero-desc">{{ $task->description }}</div>
        @endif
        @if($totalCount > 0)
            <div class="ts-progress"><div class="ts-progress-bar" style="width:{{ $clPct }}%;"></div></div>
        @endif
    </div>

    {{-- Hold uyarı --}}
    @if($sStatus === 'on_hold' && $task->hold_reason)
        <div class="ts-hold-bar">⏸ <strong>Bekleme:</strong> {{ $task->hold_reason }}</div>
    @endif

    <div class="ts-grid">

        {{-- ── Sol sütun ── --}}
        <div>

            {{-- Aksiyonlar --}}
            @if($canEdit)
            <div class="ts-sec">
                <div class="ts-sec-hd">Aksiyonlar</div>
                <div class="ts-actions">
                    @if(in_array($sStatus, ['todo','in_progress','blocked']))
                        <form method="POST" action="/tasks/{{ $task->id }}/request-review">@csrf
                            <button class="btn alt" style="font-size:13px;">🔍 İncelemeye Gönder</button>
                        </form>
                    @endif
                    @if($sStatus === 'in_review')
                        <form method="POST" action="/tasks/{{ $task->id }}/approve">@csrf
                            <button class="btn ok" style="font-size:13px;">✅ Onayla / Tamamla</button>
                        </form>
                        <form method="POST" action="/tasks/{{ $task->id }}/request-revision">@csrf
                            <button class="btn warn" style="font-size:13px;">↩ Revizyon İste</button>
                        </form>
                    @endif
                    @if($sStatus === 'todo')
                        <form method="POST" action="/tasks/{{ $task->id }}/done">@csrf
                            <button class="btn ok" style="font-size:13px;">✔ Tamamla</button>
                        </form>
                    @endif
                    @if($sStatus === 'on_hold')
                        <form method="POST" action="/tasks/{{ $task->id }}/resume">@csrf
                            <button class="btn ok" style="font-size:13px;">▶ Devam Et</button>
                        </form>
                    @endif
                    @if(!in_array($sStatus, ['done','cancelled','on_hold']))
                        <form method="POST" action="/tasks/{{ $task->id }}/hold"
                              onsubmit="var r=prompt('Bekleme nedeni:','');if(r!==null){this.querySelector('[name=hold_reason]').value=r;}else{event.preventDefault();}">
                            @csrf <input type="hidden" name="hold_reason" value="">
                            <button class="btn alt" style="font-size:13px;">⏸ Beklet</button>
                        </form>
                    @endif
                    @if($sStatus === 'done')
                        <form method="POST" action="/tasks/{{ $task->id }}/reopen">@csrf
                            <button class="btn alt" style="font-size:13px;">↩ Yeniden Aç</button>
                        </form>
                    @endif
                    @if(!in_array($sStatus, ['done','cancelled']))
                        <form method="POST" action="/tasks/{{ $task->id }}/cancel" onsubmit="return confirm('İptal edilsin mi?')">@csrf
                            <button class="btn warn" style="font-size:13px;">✕ İptal Et</button>
                        </form>
                    @endif
                </div>
            </div>
            @endif

            {{-- Kontrol listesi --}}
            <div class="ts-sec">
                <div class="ts-sec-hd">
                    <span>Kontrol Listesi</span>
                    @if($totalCount > 0)<span style="color:var(--u-text);font-weight:700;">{{ $clPct }}%</span>@endif
                </div>
                <div class="ts-sec-body" id="cl-list-{{ $task->id }}">
                    @forelse($checklist as $item)
                    <div class="ts-cl-row {{ $item->is_done ? 'done' : '' }}" id="cl-item-{{ $item->id }}">
                        <input type="checkbox" class="cl-toggle" data-task="{{ $task->id }}" data-item="{{ $item->id }}"
                               {{ $item->is_done ? 'checked' : '' }}>
                        <span style="flex:1;">{{ $item->title }}</span>
                        @if($canEdit)
                        <button type="button" class="ts-cl-del cl-del" data-task="{{ $task->id }}" data-item="{{ $item->id }}">×</button>
                        @endif
                    </div>
                    @empty
                    <div style="color:var(--u-muted);font-size:var(--tx-sm,.8125rem);" id="cl-empty">Henüz kontrol listesi yok.</div>
                    @endforelse
                </div>
                @if($canEdit)
                <form class="cl-add-form ts-cl-add" data-task="{{ $task->id }}" style="padding:0 16px 14px;">
                    <input type="text" name="title" placeholder="Yeni madde ekle…" maxlength="190">
                    <button class="btn" type="submit" style="font-size:12px;padding:5px 12px;">+ Ekle</button>
                </form>
                @endif
            </div>

            {{-- Yorumlar --}}
            <div class="ts-sec">
                <div class="ts-sec-hd">Yorumlar</div>
                <div class="ts-sec-body">
                    <div id="comment-list-{{ $task->id }}">
                        <div style="color:var(--u-muted);font-size:var(--tx-sm,.8125rem);">Yükleniyor…</div>
                    </div>
                    <form class="task-comment-form ts-cmt-form" data-task-id="{{ $task->id }}">
                        @csrf
                        <textarea name="body" placeholder="Yorum yaz…" rows="3"></textarea>
                        <div class="ts-cmt-actions">
                            <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--u-muted);cursor:pointer;">
                                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" style="display:none;" id="cmt-file-{{ $task->id }}">
                                <span onclick="document.getElementById('cmt-file-{{ $task->id }}').click()" style="cursor:pointer;">📎 Dosya ekle</span>
                            </label>
                            <span id="comment-fname-{{ $task->id }}" style="font-size:11px;color:var(--u-muted);"></span>
                            <button class="btn" type="submit" style="margin-left:auto;font-size:13px;">Gönder</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Aktivite Günlüğü --}}
            @if($activityLogs->count() > 0)
            <div class="ts-sec">
                <div class="ts-sec-hd">Aktivite Günlüğü</div>
                <div class="ts-sec-body" style="padding:8px 16px;">
                    @foreach($activityLogs as $log)
                    <div class="ts-act-row">
                        <div class="ts-act-who">{{ $log->user?->name ?? 'Sistem' }}</div>
                        <div class="ts-act-what">
                            <strong>{{ $log->action }}</strong>
                            @if($log->old_value || $log->new_value): {{ $log->old_value ?? '—' }} → {{ $log->new_value ?? '—' }}@endif
                        </div>
                        <div class="ts-act-when">{{ $log->created_at?->format('d.m H:i') }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ── Sağ sütun ── --}}
        <div>

            {{-- Bilgiler --}}
            <div class="ts-sec">
                <div class="ts-sec-hd">Bilgiler</div>
                <div class="ts-sec-body" style="padding:8px 16px;">
                    <div class="ts-row">
                        <span class="ts-lbl">Durum</span>
                        <span class="ts-val"><span class="badge st-{{ $sStatus }}" style="font-size:11px;">{{ $statusLabels[$sStatus] ?? $sStatus }}</span></span>
                    </div>
                    <div class="ts-row">
                        <span class="ts-lbl">Öncelik</span>
                        <span class="ts-val"><span class="badge {{ $sPri==='urgent'?'danger':($sPri==='high'?'warn':'info') }}" style="font-size:11px;">{{ $prioLabels[$sPri] ?? $sPri }}</span></span>
                    </div>
                    <div class="ts-row">
                        <span class="ts-lbl">Departman</span>
                        <span class="ts-val">{{ $deptOptions[$task->department] ?? $task->department ?? '—' }}</span>
                    </div>
                    @if($task->process_type)
                    <div class="ts-row">
                        <span class="ts-lbl">Süreç</span>
                        <span class="ts-val"><span class="badge info" style="font-size:11px;">{{ \App\Models\MarketingTask::PROCESS_TYPES[$task->process_type] ?? $task->process_type }}</span></span>
                    </div>
                    @endif
                    @if($task->workflow_stage)
                    <div class="ts-row">
                        <span class="ts-lbl">Aşama</span>
                        <span class="ts-val" style="font-size:11px;">{{ \App\Models\MarketingTask::WORKFLOW_STAGES[$task->process_type??''][$task->workflow_stage] ?? $task->workflow_stage }}</span>
                    </div>
                    @endif
                    <div class="ts-row">
                        <span class="ts-lbl">Atanan</span>
                        <span class="ts-val">{{ $task->assignedUser?->name ?? '—' }}</span>
                    </div>
                    <div class="ts-row">
                        <span class="ts-lbl">Oluşturan</span>
                        <span class="ts-val">{{ $task->createdByUser?->name ?? '—' }}</span>
                    </div>
                    <div class="ts-row">
                        <span class="ts-lbl">Oluşturulma</span>
                        <span class="ts-val">{{ $task->created_at?->format('d.m.Y H:i') }}</span>
                    </div>
                    @if($task->completed_at)
                    <div class="ts-row">
                        <span class="ts-lbl">Tamamlandı</span>
                        <span class="ts-val" style="color:var(--u-ok);font-weight:700;">{{ $task->completed_at->format('d.m.Y H:i') }}</span>
                    </div>
                    @endif
                    @if($dueDate)
                    <div class="ts-row">
                        <span class="ts-lbl">Son Tarih</span>
                        <span class="ts-val" style="color:{{ $isOverdue ? 'var(--u-danger)' : 'var(--u-text)' }};font-weight:{{ $isOverdue?'700':'500' }};">{{ $dueDate }}{{ $isOverdue ? ' ⚠' : '' }}</span>
                    </div>
                    @endif
                    @if($task->escalate_after_hours)
                    <div class="ts-row">
                        <span class="ts-lbl">Eskalasyon</span>
                        <span class="ts-val">{{ $task->escalate_after_hours }}s</span>
                    </div>
                    @endif
                    @if($task->is_recurring)
                    <div class="ts-row">
                        <span class="ts-lbl">Tekrar</span>
                        <span class="ts-val">{{ $task->recurrence_pattern }} / {{ $task->recurrence_interval_days }}g</span>
                    </div>
                    @if($task->next_run_at)
                    <div class="ts-row">
                        <span class="ts-lbl">Sonraki</span>
                        <span class="ts-val">{{ $task->next_run_at->format('d.m.Y') }}</span>
                    </div>
                    @endif
                    @endif
                    @if($srcLink)
                    <div class="ts-row">
                        <span class="ts-lbl">Kaynak</span>
                        <span class="ts-val"><a href="{{ $srcLink }}" class="ts-src-chip">↗ {{ $srcLabel }}</a></span>
                    </div>
                    @elseif($task->source_type)
                    <div class="ts-row">
                        <span class="ts-lbl">Kaynak tipi</span>
                        <span class="ts-val" style="font-size:11px;color:var(--u-muted);">{{ $task->source_type }}</span>
                    </div>
                    @endif
                    @if($depTask)
                    <div class="ts-row">
                        <span class="ts-lbl">Bağımlı</span>
                        <span class="ts-val">
                            <a href="/tasks/{{ $depTask->id }}/show" style="color:var(--u-brand);font-size:12px;">#{{ $depTask->id }} {{ Str::limit($depTask->title, 22) }}</a>
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Takipçiler --}}
            <div class="ts-sec">
                <div class="ts-sec-hd">
                    <span>Takipçiler ({{ $watchers->count() }})</span>
                    <form method="POST" action="/tasks/{{ $task->id }}/watch">
                        @csrf @if($isWatching) @method('DELETE') @endif
                        <button type="submit" style="background:none;border:none;cursor:pointer;font-size:11px;color:var(--u-brand);font-weight:700;padding:0;">
                            {{ $isWatching ? '− Bırak' : '+ Takip Et' }}
                        </button>
                    </form>
                </div>
                <div class="ts-sec-body">
                    @forelse($watchers as $w)
                        <span class="ts-wchip">👁 {{ $w->user_name ?? $w->user_email ?? 'Kullanıcı' }}</span>
                    @empty
                        <span style="font-size:var(--tx-sm,.8125rem);color:var(--u-muted);">Henüz takipçi yok.</span>
                    @endforelse
                </div>
            </div>

            {{-- Düzenle --}}
            @if($canEdit)
            <div class="ts-sec">
                <div class="ts-sec-hd">
                    <span>Düzenle</span>
                    <button onclick="this.closest('.ts-sec').querySelector('.ts-ef').style.display=this.closest('.ts-sec').querySelector('.ts-ef').style.display==='none'?'block':'none'" style="background:none;border:none;cursor:pointer;font-size:11px;color:var(--u-brand);font-weight:700;padding:0;">Aç / Kapat</button>
                </div>
                <div class="ts-ef" style="padding:12px 16px;display:none;">
                    <form method="POST" action="/tasks/{{ $task->id }}">
                        @csrf @method('PUT')
                        <label>Başlık</label>
                        <input name="title" value="{{ old('title', $task->title) }}" required maxlength="190">
                        <label>Açıklama</label>
                        <textarea name="description" rows="3" maxlength="2000">{{ old('description', $task->description) }}</textarea>
                        <label>Durum</label>
                        <select name="status">
                            @foreach($statusOptions as $k => $v)
                                <option value="{{ $k }}" {{ $task->status===$k?'selected':'' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        <label>Öncelik</label>
                        <select name="priority">
                            @foreach($priorityOptions as $k => $v)
                                <option value="{{ $k }}" {{ $task->priority===$k?'selected':'' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        <label>Atanan</label>
                        <select name="assigned_user_id">
                            <option value="">— Atanmamış —</option>
                            @foreach(($assignees ?? []) as $u)
                                <option value="{{ $u->id }}" {{ $task->assigned_user_id==$u->id?'selected':'' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <label>Son Tarih</label>
                        <input type="date" name="due_date" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}">
                        <div style="display:flex;gap:8px;margin-top:12px;">
                            <button class="btn ok" type="submit" style="font-size:13px;">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Son güncelleme --}}
            <div style="font-size:11px;color:var(--u-muted);padding:4px 2px;text-align:center;">
                #{{ $task->id }} &bull; {{ $task->updated_at?->format('d.m.Y H:i') }}
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var TASK_ID = {{ $task->id }};
    var _csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var roleLabels = @json($roleLabels ?? []);

    function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    /* ── Yorum yükle ── */
    function loadComments() {
        var el = document.getElementById('comment-list-' + TASK_ID);
        if (!el) return;
        fetch('/tasks/' + TASK_ID + '/comments', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(d => {
                var list = d.comments || [];
                if (!list.length) { el.innerHTML = '<div style="color:var(--u-muted);font-size:13px;">Henüz yorum yok.</div>'; return; }
                el.innerHTML = list.map(c => {
                    var initials = (c.user_name || 'U').split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase();
                    var role = roleLabels[c.user_role] || c.user_role || '';
                    var att = c.attachment_path ? '<div class="ts-cmt-att">📎 <a href="/task-comment-file/' + c.id + '" target="_blank" style="color:var(--u-brand);">Ek dosya</a></div>' : '';
                    return '<div class="ts-cmt-item"><div class="ts-cmt-avatar">' + esc(initials) + '</div><div class="ts-cmt-body"><div class="ts-cmt-meta"><span class="ts-cmt-name">' + esc(c.user_name) + '</span><span class="ts-cmt-role">' + esc(role) + '</span><span class="ts-cmt-time">' + esc(c.created_at) + '</span></div><div class="ts-cmt-text">' + esc(c.body) + '</div>' + att + '</div></div>';
                }).join('');
            })
            .catch(() => { document.getElementById('comment-list-' + TASK_ID).innerHTML = '<div style="color:var(--u-danger);font-size:13px;">Yüklenemedi.</div>'; });
    }
    loadComments();

    /* ── Yorum gönder ── */
    document.querySelectorAll('.task-comment-form').forEach(form => {
        var fileInput = form.querySelector('input[type=file]');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                var fn = form.querySelector('span[id^="comment-fname"]');
                if (fn) fn.textContent = this.files[0]?.name || '';
            });
        }
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(this);
            if (!fd.get('_token')) fd.set('_token', _csrf);
            var btn = this.querySelector('button[type=submit]');
            if (btn) btn.disabled = true;
            fetch('/tasks/' + TASK_ID + '/comments', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json()).then(d => {
                    if (d.ok) { this.reset(); var fn = form.querySelector('[id^="comment-fname"]'); if (fn) fn.textContent = ''; loadComments(); }
                    else alert(d.message || 'Gönderilemedi.');
                }).catch(() => alert('Bağlantı hatası.')).finally(() => { if (btn) btn.disabled = false; });
        });
    });

    /* ── Checklist toggle ── */
    function updateProgress(data) {
        if (data.progress === undefined) return;
        var bar = document.querySelector('.ts-progress-bar');
        if (bar) bar.style.width = data.progress + '%';
        document.querySelectorAll('.ts-sec-hd span:last-child').forEach(el => {
            if (el.closest('.ts-sec') && el.closest('.ts-sec').querySelector('#cl-list-' + TASK_ID)) el.textContent = data.progress + '%';
        });
    }
    document.querySelectorAll('.cl-toggle').forEach(cb => {
        cb.addEventListener('change', function () {
            var tid = this.dataset.task, iid = this.dataset.item;
            var row = document.getElementById('cl-item-' + iid);
            var span = row?.querySelector('span');
            var done = this.checked;
            if (span) span.style = done ? 'text-decoration:line-through;color:var(--u-muted);flex:1;' : 'flex:1;';
            if (row) row.classList.toggle('done', done);
            fetch('/tasks/' + tid + '/checklist/' + iid + '/toggle', { method: 'PATCH', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': _csrf } })
                .then(r => r.json()).then(d => updateProgress(d))
                .catch(() => { this.checked = !done; if (span) span.style = ''; });
        });
    });

    /* ── Checklist sil ── */
    document.querySelectorAll('.cl-del').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!confirm('Bu madde silinsin mi?')) return;
            var tid = this.dataset.task, iid = this.dataset.item;
            var row = document.getElementById('cl-item-' + iid);
            fetch('/tasks/' + tid + '/checklist/' + iid, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': _csrf } })
                .then(r => r.json()).then(d => { if (row) row.remove(); updateProgress(d); })
                .catch(() => alert('Silinemedi.'));
        });
    });

    /* ── Checklist ekle ── */
    document.querySelectorAll('.cl-add-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var tid = this.dataset.task;
            var inp = this.querySelector('input[name=title]');
            var title = inp?.value.trim();
            if (!title) return;
            var btn = this.querySelector('button[type=submit]');
            if (btn) btn.disabled = true;
            fetch('/tasks/' + tid + '/checklist', { method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': _csrf }, body: JSON.stringify({ title }) })
                .then(r => r.json()).then(d => {
                    if (!d.id) { alert(d.error || 'Eklenemedi.'); return; }
                    var list = document.getElementById('cl-list-' + tid);
                    var empty = document.getElementById('cl-empty');
                    if (empty) empty.remove();
                    var row = document.createElement('div');
                    row.className = 'ts-cl-row'; row.id = 'cl-item-' + d.id;
                    row.innerHTML = '<input type="checkbox" class="cl-toggle" data-task="' + tid + '" data-item="' + d.id + '"><span style="flex:1;">' + esc(title) + '</span><button type="button" class="ts-cl-del cl-del" data-task="' + tid + '" data-item="' + d.id + '">×</button>';
                    list?.appendChild(row);
                    row.querySelector('.cl-toggle')?.addEventListener('change', function () {
                        fetch('/tasks/'+tid+'/checklist/'+d.id+'/toggle',{method:'PATCH',headers:{'X-CSRF-TOKEN':_csrf}}).then(r=>r.json()).then(updateProgress);
                    });
                    row.querySelector('.cl-del')?.addEventListener('click', function () {
                        if (!confirm('Silinsin mi?')) return;
                        fetch('/tasks/'+tid+'/checklist/'+d.id,{method:'DELETE',headers:{'X-CSRF-TOKEN':_csrf}}).then(r=>r.json()).then(dd=>{row.remove();updateProgress(dd);});
                    });
                    if (inp) inp.value = '';
                    updateProgress(d);
                }).catch(() => alert('Bağlantı hatası.')).finally(() => { if (btn) btn.disabled = false; });
        });
    });
})();
</script>
@endsection

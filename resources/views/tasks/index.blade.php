@php
    $role = auth()->user()?->role;
    $taskLayout = in_array($role, ['senior','mentor'])
        ? 'senior.layouts.app'
        : ($role === 'manager' ? 'manager.layouts.app' : 'layouts.staff');
@endphp
@extends($taskLayout)

@section('title', 'Görev Panosu')
@section('page_title', 'Görev Panosu')

@push('head')
<style>
/* ── Temel eksik sınıflar ── */
.card { padding: 18px 20px; }
.row2 { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.row2 .full { grid-column:1/-1; }
.full { width:100%; box-sizing:border-box; }
.actions { display:flex; gap:8px; flex-wrap:wrap; padding-top:10px; }
.muted { color:var(--u-muted,#64748b); font-size:var(--tx-sm,.8125rem); }

/* ── Form elemanları ── */
.card input:not([type=checkbox]):not([type=radio]),
.card select,
.card textarea {
    width: 100%; box-sizing: border-box;
    height: 36px; padding: 0 10px;
    border: 1px solid var(--u-line,#e2e8f0);
    border-radius: 8px;
    background: var(--u-card,#fff);
    color: var(--u-text,#0f172a);
    font-size: var(--tx-sm,.8125rem);
    outline: none;
    transition: border-color .15s;
    appearance: auto;
}
.card textarea {
    height: 64px; padding: 8px 10px; resize: vertical;
}
.card input:not([type=checkbox]):not([type=radio]):focus,
.card select:focus,
.card textarea:focus {
    border-color: var(--u-brand,#1e40af);
    box-shadow: 0 0 0 2px rgba(30,64,175,.10);
}
.card input[type=date] { padding: 0 8px; }
.card label { font-size:var(--tx-sm,.8125rem); color:var(--u-text,#0f172a); display:flex; align-items:center; gap:6px; height:36px; }
.card h3 { font-size:var(--tx-base,.9375rem); font-weight:700; color:var(--u-text,#0f172a); margin:0 0 14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

/* Quick-nav link groups */
.link-group-box { background:var(--u-bg,#f8fafc); border:1px solid var(--u-line,#e2e8f0); border-radius:10px; padding:10px 14px; }
.link-group-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--u-muted,#64748b); display:block; margin-bottom:7px; }
.pill-links { display:flex; flex-wrap:wrap; gap:6px; }
.pill-link { display:inline-block; padding:4px 12px; border-radius:999px; border:1px solid var(--u-line,#e2e8f0); background:#fff; color:var(--u-text,#0f172a); font-size:12px; font-weight:600; text-decoration:none; transition:background .12s,border-color .12s; white-space:nowrap; }
.pill-link:hover { background:#eff6ff; border-color:#93c5fd; color:#1d4ed8; text-decoration:none; }
.pill-link.active { background:var(--u-brand,#1e40af); border-color:transparent; color:#fff; }

/* Grid */
.grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:14px; }
@media(max-width:800px){ .grid2 { grid-template-columns:1fr; } }

/* View toggle active */
.btn.view-active { background:var(--u-brand,#1e40af); color:#fff; border-color:transparent; }

/* ── ClickUp-style tablo liste ── */
.tl-table { border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); margin-bottom:16px; }
/* task adı 1fr → kalan tüm alanı kaplar */
.tl-cols { display:grid; grid-template-columns:32px 1fr 96px 68px 110px 130px 88px 78px 34px; }
.tl-head { background:var(--u-bg,#f8fafc); border-bottom:2px solid var(--u-line,#e2e8f0); }
.tl-head .tl-c { font-size:var(--tx-xs,.6875rem); font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--u-muted,#64748b); padding:7px 8px; }
.tl-row { border-bottom:1px solid var(--u-line,#e2e8f0); border-left:3px solid transparent; transition:background .1s; cursor:pointer; }
.tl-row:last-child { border-bottom:none; }
.tl-row:hover { background:#f4f8ff; }
.tl-row.tl-open { background:#eef4ff; border-left-color:var(--u-brand,#1e40af) !important; }
.tl-row.tk-urgent { border-left-color:#ef4444; }
.tl-row.tk-high   { border-left-color:#f59e0b; }
.tl-row.tk-normal { border-left-color:#3b82f6; }
.tl-row.tk-low    { border-left-color:#94a3b8; }
.tl-row.tk-done   { opacity:.72; }
.tl-row.tk-cancelled { opacity:.50; }
.tl-c { padding:7px 8px; display:flex; align-items:center; font-size:var(--tx-sm,.8125rem); min-width:0; overflow:hidden; }
.tl-c-check { justify-content:center; }
.tl-c-name  { gap:5px; }
.tl-title   { font-weight:600; color:var(--u-text,#0f172a); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; min-width:0; text-decoration:none; }
.tl-title:hover { color:var(--u-brand,#1e40af); text-decoration:underline; }
.tl-row:hover .tl-title { color:var(--u-brand,#1e40af); }
.tl-num     { font-size:var(--tx-xs,.6875rem); color:var(--u-muted); flex-shrink:0; background:var(--u-bg,#f3f4f6); padding:1px 5px; border-radius:4px; }
/* Badge: kompakt */
.tl-c .badge { font-size:var(--tx-xs,.6875rem); padding:2px 7px; white-space:nowrap; }
.tl-c-due.over  { color:#ef4444; font-weight:700; font-size:var(--tx-xs,.6875rem); }
.tl-c-due.today { color:#d97706; font-weight:700; font-size:var(--tx-xs,.6875rem); }
.tl-c-due       { font-size:var(--tx-xs,.6875rem); color:var(--u-muted); }
.tl-c-assignee  { font-size:var(--tx-xs,.6875rem); color:var(--u-text,#374151); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tl-c-src a { font-size:var(--tx-xs,.6875rem); color:var(--u-brand,#1e40af); text-decoration:none; white-space:nowrap; padding:2px 6px; background:#eef4ff; border-radius:5px; }
.tl-c-src a:hover { background:#dbeafe; }
.tl-expand-btn { background:none; border:none; cursor:pointer; color:var(--u-muted); font-size:var(--tx-xs,.6875rem); padding:3px 5px; border-radius:4px; line-height:1; transition:all .15s; width:26px; height:26px; display:flex; align-items:center; justify-content:center; }
.tl-expand-btn:hover { background:var(--u-bg); color:var(--u-brand); }
.tl-expand-btn.open { transform:rotate(180deg); color:var(--u-brand); }
.tl-detail { display:none; border-bottom:1px solid var(--u-line); background:#f8fbff; border-left:3px solid var(--u-brand,#1e40af); }
.tl-detail.open { display:block; }
.tl-detail-inner { padding:10px 16px 4px; }
@media(max-width:1100px){ .tl-cols { grid-template-columns:32px 1fr 96px 68px 110px 110px 36px; }
  .tl-c-src,.tl-c-due { display:none; } }
@media(max-width:800px){ .tl-cols { grid-template-columns:32px 1fr 96px 68px 36px; }
  .tl-c-dept,.tl-c-assignee { display:none; } }

/* Board top card */
.board-top { margin-bottom:14px; }
.board-top .muted { margin-bottom:10px; font-size:13px; }

/* ── Task Board v2.1 — tüm sınıflar tb-/tk- prefix'li ── */
.tb-stats { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; margin-bottom:16px; }
.tb-stat  { background:var(--u-card); border:1px solid var(--u-line); border-radius:10px; padding:12px 16px; }
.tb-stat .tb-lbl { font-size:var(--tx-xs,.6875rem); color:var(--u-muted); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.tb-stat .tb-val { font-size:1.625rem; font-weight:700; color:var(--u-text); line-height:1; }
.tb-stat.tb-overdue   .tb-val { color:var(--u-danger); }
.tb-stat.tb-review    .tb-val { color:#7c3aed; }
.tb-stat.tb-hold      .tb-val { color:#d97706; }
.tb-stat.tb-cancelled .tb-val { color:#6b7280; }
a.tb-stat { display:block; transition:box-shadow .15s, background .15s; }
a.tb-stat:hover { box-shadow:0 2px 8px rgba(0,0,0,.1); background:#f0f6ff; }

.tb-bulk     { border:1px dashed var(--u-line); border-radius:10px; padding:12px 14px; margin-bottom:12px; background:#f8fbff; }
.tb-bulk-row { display:grid; grid-template-columns:1.4fr 1fr 1fr 1fr 1fr; gap:8px; }

.board-top   { display:grid; gap:10px; margin-bottom:12px; }
.board-title { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }

/* ── Task kartı ── */
.tk { background:var(--u-card); border:1px solid var(--u-line); border-radius:12px; margin-bottom:12px; overflow:hidden; border-left:4px solid #e2e8f0; transition:box-shadow .15s; }
.tk:hover { box-shadow:0 2px 12px rgba(0,0,0,.07); }
.tk.tk-urgent { border-left-color:#ef4444; }
.tk.tk-high   { border-left-color:#f59e0b; }
.tk.tk-normal { border-left-color:#3b82f6; }
.tk.tk-low    { border-left-color:#94a3b8; }
.tk.tk-done      { opacity:.82; background:#f8fffe; }
.tk.tk-done .tk-title { text-decoration:line-through; color:var(--u-muted); }
.tk.tk-blocked   { background:#fffbf5; }
.tk.tk-in_review { border-left-color:#7c3aed; background:#faf5ff; }
.tk.tk-on_hold   { border-left-color:#d97706; background:#fffbeb; opacity:.88; }
.tk.tk-cancelled { border-left-color:#9ca3af; opacity:.6; background:#f9fafb; }
.tk.tk-cancelled .tk-title { text-decoration:line-through; color:var(--u-muted); }

/* Hold reason info bar */
.tk-hold-bar { padding:4px 14px 6px 42px; font-size:12px; color:#92400e; background:#fffbeb; border-top:1px solid #fde68a; }
.tk-hold-bar strong { color:#b45309; }

.tk-head   { display:flex; align-items:flex-start; gap:10px; padding:12px 14px 6px; }
.tk-cb     { flex-shrink:0; margin-top:3px; }
.tk-cb input[type=checkbox] { width:18px; height:18px; min-height:0; cursor:pointer; accent-color:var(--u-brand); }
.tk-titlewrap { flex:1; min-width:0; }
.tk-num    { font-size:11px; font-weight:700; color:var(--u-muted); margin-right:5px; }
.tk-title  { font-size:14px; font-weight:700; color:var(--u-text); line-height:1.35; word-break:break-word; }
.tk-tags   { display:flex; align-items:center; gap:5px; flex-shrink:0; flex-wrap:wrap; justify-content:flex-end; max-width:320px; }

.tk-meta   { display:flex; align-items:center; flex-wrap:wrap; gap:10px; padding:4px 14px 8px 42px; font-size:12px; color:var(--u-muted); }
.tk-mi     { display:inline-flex; align-items:center; gap:3px; }
.tk-mi.tk-overdue { color:#ef4444; font-weight:700; }
.tk-mi.tk-today   { color:#d97706; font-weight:700; }
.tk-mi.tk-ok      { color:#16a34a; }

.tk-desc   { padding:0 14px 8px 42px; font-size:13px; color:var(--u-text); opacity:.9; }
.tk-hint   { padding:0 14px 8px 42px; font-size:11px; color:var(--u-muted); font-style:italic; }
.tk-src    { display:inline-flex; align-items:center; gap:3px; text-decoration:none; border-radius:6px; border:1px solid #c7ddf9; background:#ebf4ff; color:#1d4f8c; font-size:11px; padding:2px 8px; font-weight:600; }

.tk-divider { height:1px; background:var(--u-line); margin:0 14px; }

.tk-footer { display:flex; align-items:center; gap:6px; flex-wrap:wrap; padding:8px 14px; }
.tk-footer form { display:contents; }

.tk-edit-wrap { border-top:1px solid var(--u-line); background:#f8fbff; }
.tk-edit-sum  { cursor:pointer; font-size:12px; color:var(--u-brand); font-weight:600; padding:8px 14px; display:block; user-select:none; }
.tk-edit-sum:hover { background:#eef4fb; }
.tk-edit-body { padding:10px 14px 14px; }

.tk-cmt-wrap { border-top:1px solid var(--u-line); background:#f9fafb; }

@media (max-width:1100px) {
    .tk-2col { grid-template-columns: 1fr !important; }
}
@media (max-width:900px) {
    .tb-stats { grid-template-columns:repeat(4,minmax(0,1fr)) !important; }
    .tb-bulk-row { grid-template-columns:1fr; }
    .tk-tags { display:none; }
}

/* ── Kanban Board ── */
.kanban-board { display:flex; gap:8px; align-items:flex-start; }
.kanban-col { flex:1; min-width:0; background:#f0f5fc; border-radius:12px; padding:8px; min-height:180px; }
.kanban-col-title { font-size:11px; font-weight:700; text-transform:uppercase; color:#2b4d74; margin-bottom:6px; padding:3px 7px; border-radius:6px; background:#dce8f7; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.kanban-card { background:#fff; border:1px solid #d2deea; border-radius:8px; padding:7px 9px; margin-bottom:6px; cursor:grab; font-size:12px; word-break:break-word; }
.kanban-card:active { cursor:grabbing; opacity:.8; }
.kanban-card .kc-title { font-weight:700; margin-bottom:3px; font-size:12px; line-height:1.3; }
.kanban-card .kc-meta { font-size:10px; color:#6b7c93; }
.kanban-col.drag-over { background:#dce8f7; }
.priority-urgent { border-left:3px solid #d80a2a; }
.priority-high    { border-left:3px solid #e88a00; }
.priority-normal  { border-left:3px solid #0a67d8; }
.priority-low     { border-left:3px solid #aabdd4; }

/* ── Gantt Chart ── */
.gantt-wrap { overflow-x:auto; background:var(--u-card); border:1px solid var(--u-line); border-radius:10px; padding:14px; }
.gantt-chart { min-width:820px; }
.gantt-ruler { display:flex; margin-left:220px; height:28px; border-bottom:2px solid var(--u-line); position:relative; margin-bottom:4px; }
.gantt-ruler-tick { position:absolute; font-size:10px; color:var(--u-muted); transform:translateX(-50%); white-space:nowrap; top:6px; }
.gantt-ruler-tick::before { content:''; position:absolute; top:-6px; left:50%; width:1px; height:6px; background:var(--u-line); }
.gantt-row { display:flex; align-items:center; min-height:38px; border-bottom:1px solid #f0f4f8; }
.gantt-row:hover { background:#f8fbff; }
.gantt-label { width:220px; flex:none; font-size:12px; padding:4px 10px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; color:var(--u-text); }
.gantt-label .gantt-id { font-size:10px; color:var(--u-muted); margin-right:3px; }
.gantt-track { flex:1; position:relative; height:26px; }
.gantt-bar { position:absolute; height:22px; top:2px; border-radius:5px; display:flex; align-items:center; padding:0 7px; font-size:10px; color:#fff; overflow:hidden; white-space:nowrap; font-weight:600; min-width:6px; }
.gantt-today { position:absolute; top:-28px; bottom:-4px; width:2px; background:#ef4444; z-index:5; pointer-events:none; }
.gantt-today::before { content:'Bugün'; position:absolute; top:0; left:4px; font-size:9px; color:#ef4444; white-space:nowrap; font-weight:700; }
.gantt-empty { padding:30px; text-align:center; color:var(--u-muted); font-size:13px; }
</style>
@endpush

@section('content')
    <section class="card board-top">
        @php $activeDepartment = (string)($routeDepartment ?? ''); @endphp
        <div class="board-title">
            <div>
                <div class="muted">Manager + Advisory + Admin ekipleri için ortak gorev takibi.</div>
            </div>
        </div>

        @php
            $userRole = (string) (auth()->user()?->role ?? '');
            $canSeeManager   = in_array($userRole, [\App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_SYSTEM_ADMIN, \App\Models\User::ROLE_SYSTEM_STAFF], true);
            $canSeeSenior    = $canSeeManager || in_array($userRole, [\App\Models\User::ROLE_SENIOR, \App\Models\User::ROLE_MENTOR], true);
            $canSeeMarketing = $canSeeManager || in_array($userRole, [
                \App\Models\User::ROLE_MARKETING_ADMIN, \App\Models\User::ROLE_MARKETING_STAFF,
                \App\Models\User::ROLE_SALES_ADMIN, \App\Models\User::ROLE_SALES_STAFF,
            ], true);
        @endphp
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <div class="link-group-box" style="flex:1;min-width:0;">
            <span class="link-group-label">Hızlı Gecis</span>
            <div class="pill-links">
                @if($canSeeManager)
                    <a class="pill-link" href="/manager/dashboard">Manager</a>
                    <a class="pill-link" href="/manager/requests">Request Center</a>
                @endif
                @if($canSeeSenior)
                    <a class="pill-link" href="/senior/dashboard">Eğitim Danışmanı</a>
                @endif
                @if($userRole !== \App\Models\User::ROLE_SALES_STAFF)
                <a class="pill-link" href="/tickets-center">Ticket Center</a>
                @endif
                <a class="pill-link" href="/messages-center">Mesaj Merkezi</a>
                @if($canSeeMarketing)
                    <a class="pill-link" href="/mktg-admin/tasks">Marketing Tasks</a>
                @endif
            </div>
        </div>

        <div class="link-group-box" style="flex:1;min-width:0;">
            <span class="link-group-label">Departman Kuyruklari</span>
            <div class="pill-links">
                @if($isGlobalViewer)
                    <a class="pill-link {{ $activeDepartment === '' ? 'active' : '' }}" href="/tasks">Tum Tasklar</a>
                @endif
                @if($isGlobalViewer || $roleScopedDepartment === 'operations')
                    <a class="pill-link {{ $activeDepartment === 'operations' ? 'active' : '' }}" href="/tasks/operations">Operasyon</a>
                @endif
                @if($isGlobalViewer || $roleScopedDepartment === 'finance')
                    <a class="pill-link {{ $activeDepartment === 'finance' ? 'active' : '' }}" href="/tasks/finance">Finans</a>
                @endif
                @if($isGlobalViewer || $roleScopedDepartment === 'advisory')
                    <a class="pill-link {{ $activeDepartment === 'advisory' ? 'active' : '' }}" href="/tasks/advisory">Danışmanlık</a>
                @endif
                @if($isGlobalViewer || $roleScopedDepartment === 'marketing')
                    <a class="pill-link {{ $activeDepartment === 'marketing' ? 'active' : '' }}" href="/tasks/marketing">Marketing</a>
                @endif
                @if($isGlobalViewer || $roleScopedDepartment === 'system')
                    <a class="pill-link {{ $activeDepartment === 'system' ? 'active' : '' }}" href="/tasks/system">Sistem</a>
                @endif
                @if($roleScopedDepartment !== null && !$isGlobalViewer)
                    {{-- Scoped user: "Tum Tasklar" kendi departmanına filtreli gider --}}
                    <a class="pill-link {{ $activeDepartment === '' ? 'active' : '' }}" href="/tasks">Tüm Tasklar</a>
                @endif
            </div>
        </div>
        </div>{{-- /flex row --}}
    </section>

    @php $baseUrl = ($routeDepartment ?? '') !== '' ? '/tasks/'.$routeDepartment : '/tasks'; @endphp
    <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;margin-bottom:8px;">
        <button onclick="switchView('list')"    id="btn-view-list"    class="btn" style="font-size:13px;padding:6px 14px;">≡ Liste</button>
        <button onclick="switchView('kanban')"  id="btn-view-kanban"  class="btn alt" style="font-size:13px;padding:6px 14px;">⊞ Board</button>
        <button onclick="switchView('gantt')"   id="btn-view-gantt"   class="btn alt" style="font-size:13px;padding:6px 14px;">📅 Gantt</button>
        @if($userRole !== \App\Models\User::ROLE_SALES_STAFF)
        <button onclick="switchView('tickets')" id="btn-view-tickets" class="btn alt" style="font-size:13px;padding:6px 14px;">🎫 Ticketler
            @php $openCount = ($tickets ?? collect())->whereIn('status',['open','in_progress','waiting_response'])->count(); @endphp
            @if($openCount > 0)<span style="background:#ef4444;color:#fff;border-radius:999px;font-size:10px;font-weight:700;padding:1px 6px;margin-left:3px;">{{ $openCount }}</span>@endif
        </button>
        @endif
    </div>

    <div class="tb-stats" style="grid-template-columns:repeat(8,minmax(0,1fr));margin-bottom:12px;">
        <a class="tb-stat" href="{{ $baseUrl }}" style="text-decoration:none;cursor:pointer;" title="Tüm tasklar"><div class="tb-lbl">Toplam</div><div class="tb-val">{{ $stats['total'] ?? 0 }}</div></a>
        <a class="tb-stat" href="{{ $baseUrl }}?status=todo" style="text-decoration:none;cursor:pointer;" title="Yapılacakları göster"><div class="tb-lbl">Yapılacak</div><div class="tb-val">{{ $stats['todo'] ?? 0 }}</div></a>
        <a class="tb-stat" href="{{ $baseUrl }}?status=in_progress" style="text-decoration:none;cursor:pointer;" title="Devam edenleri göster"><div class="tb-lbl">Devam Eden</div><div class="tb-val">{{ $stats['in_progress'] ?? 0 }}</div></a>
        <a class="tb-stat tb-review" href="{{ $baseUrl }}?status=in_review" style="text-decoration:none;cursor:pointer;" title="İncelemedeleri göster"><div class="tb-lbl">İncelemede</div><div class="tb-val">{{ $stats['in_review'] ?? 0 }}</div></a>
        <a class="tb-stat tb-hold" href="{{ $baseUrl }}?status=hold_block" style="text-decoration:none;cursor:pointer;" title="Beklemedeki ve bloke taskları göster"><div class="tb-lbl">Beklemede + Bloke</div><div class="tb-val">{{ ($stats['on_hold'] ?? 0) + ($stats['blocked'] ?? 0) }}</div></a>
        <a class="tb-stat" href="{{ $baseUrl }}?status=done" style="text-decoration:none;cursor:pointer;" title="Tamamlananları göster"><div class="tb-lbl">Tamamlanan</div><div class="tb-val">{{ $stats['done'] ?? 0 }}</div></a>
        <a class="tb-stat tb-overdue" href="{{ $baseUrl }}?sla=overdue" style="text-decoration:none;cursor:pointer;" title="Gecikenleri göster"><div class="tb-lbl">Geciken</div><div class="tb-val">{{ $stats['overdue'] ?? 0 }}</div></a>
        <a class="tb-stat tb-cancelled" href="{{ $baseUrl }}?status=cancelled" style="text-decoration:none;cursor:pointer;" title="İptal edilenleri göster"><div class="tb-lbl">İptal</div><div class="tb-val">{{ $stats['cancelled'] ?? 0 }}</div></a>
    </div>

    <div id="view-list">
    <div class="grid2">
        <section class="card">
            <h3>Yeni Task</h3>
            <form method="POST" action="/tasks">
                @csrf
                <div class="row2">
                    <input class="full" name="title" placeholder="Baslik" required>
                    <textarea class="full" name="description" placeholder="Aciklama"></textarea>
                    <select name="status">
                        @foreach(($statusOptions ?? []) as $k => $label)
                            <option value="{{ $k }}" @selected($k === 'todo')>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="priority">
                        @foreach(($priorityOptions ?? []) as $k => $label)
                            <option value="{{ $k }}" @selected($k === 'normal')>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="department" @disabled(!empty($roleScopedDepartment))>
                        @foreach(($departmentOptions ?? []) as $k => $label)
                            <option value="{{ $k }}" @selected((($roleScopedDepartment ?? '') !== '' ? $roleScopedDepartment : (($routeDepartment ?? '') !== '' ? $routeDepartment : 'operations')) === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if(!empty($roleScopedDepartment))
                        <input type="hidden" name="department" value="{{ $roleScopedDepartment }}">
                    @endif
                    <input type="date" name="due_date">
                    <select name="assigned_user_id">
                        <option value="">Atanan kisi (opsiyonel)</option>
                        @foreach(($assignees ?? []) as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    <div class="full" style="display:flex;flex-direction:column;gap:6px;">
                        <label style="height:auto;">
                            <input type="checkbox" name="is_recurring" value="1" id="cb-recurring" style="width:auto;min-height:0;" onchange="document.getElementById('recurring-opts').style.display=this.checked?'grid':'none'">
                            ↻ Tekrarlayan görev
                        </label>
                        <div id="recurring-opts" style="display:none;grid-template-columns:1fr 1fr;gap:8px;">
                            <select name="recurrence_pattern">
                                <option value="weekly">Haftalık</option>
                                <option value="daily">Günlük</option>
                                <option value="monthly">Aylık</option>
                            </select>
                            <input type="number" min="1" max="365" name="recurrence_interval_days" value="7" placeholder="Tekrar aralığı (gün)">
                        </div>
                    </div>
                    <select name="depends_on_task_id" class="full" title="Bu görev tamamlanmadan çalışmaya başlayamaz">
                        <option value="">🔗 Bağımlı olduğu görev (opsiyonel)</option>
                        @foreach(($recentTasks ?? []) as $rt)
                        <option value="{{ $rt->id }}">#{{ $rt->id }} — {{ Str::limit($rt->title, 55) }}</option>
                        @endforeach
                    </select>
                    <input type="number" class="full" min="1" max="720" name="escalate_after_hours" value="24" placeholder="Eskalasyon (saat)">
                    <select name="process_type" id="new-process-type">
                        <option value="">Süreç tipi (opsiyonel)</option>
                        @foreach(($processTypeOptions ?? []) as $k => $label)
                            <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="workflow_stage" id="new-workflow-stage">
                        <option value="">Süreç aşaması (opsiyonel)</option>
                    </select>
                </div>
                <div class="actions">
                    <button class="btn" type="submit">Task Ekle</button>
                    <a class="btn alt" href="/tasks">Yenile</a>
                </div>
            </form>
        </section>

        <section class="card">
            <h3>Filtre</h3>
            <form method="GET" action="/tasks">
                <div class="row2">
                    <select name="status">
                        <option value="">Tum durumlar</option>
                        @foreach(($statusOptions ?? []) as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="priority">
                        <option value="">Tum oncelikler</option>
                        @foreach(($priorityOptions ?? []) as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['priority'] ?? '') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="department" @disabled(!empty($roleScopedDepartment))>
                        <option value="">Tum departmanlar</option>
                        @foreach(($departmentOptions ?? []) as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['department'] ?? '') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if(!empty($roleScopedDepartment))
                        <input type="hidden" name="department" value="{{ $roleScopedDepartment }}">
                    @endif
                    <select class="full" name="assignee">
                        <option value="0">Tum ekip</option>
                        @foreach(($assignees ?? []) as $u)
                            <option value="{{ $u->id }}" @selected((int)($filters['assignee'] ?? 0) === (int)$u->id)>{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    <select class="full" name="process_type">
                        <option value="">Tüm süreçler</option>
                        @foreach(($processTypeOptions ?? []) as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['process_type'] ?? '') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select class="full" name="source_type">
                        <option value="">Tum kaynaklar</option>
                        @foreach(($sourceOptions ?? []) as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['source_type'] ?? '') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="actions">
                    <button class="btn" type="submit">Filtrele</button>
                    <a class="btn alt" href="{{ ($routeDepartment ?? '') !== '' ? ('/tasks/'.$routeDepartment) : '/tasks' }}">Temizle</a>
                </div>
            </form>
        </section>
    </div>

    {{-- ── v3 — Template Uygula ─────────────────────────────────────────── --}}
    <details class="card" id="tpl-apply-wrap" style="margin-bottom:16px;">
        <summary style="cursor:pointer;font-weight:600;font-size:14px;list-style:none;display:flex;align-items:center;gap:8px;">
            <span style="font-size:18px;">📋</span> Şablondan Task Oluştur
            <span style="font-size:12px;font-weight:400;color:var(--u-muted);margin-left:4px;">(Hazır şablon seç, otomatik görev listesi oluştur)</span>
        </summary>
        <div style="margin-top:14px;">
            <div class="row2" style="max-width:720px;">
                <div>
                    <label style="font-size:12px;color:var(--u-muted);display:block;margin-bottom:4px;">Departman</label>
                    <select id="tpl-dept" style="width:100%;">
                        <option value="">Tümü</option>
                        @foreach(($departmentOptions ?? []) as $k => $label)
                            <option value="{{ $k }}"
                                @if(($roleScopedDepartment ?? '') === $k) selected @endif>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;color:var(--u-muted);display:block;margin-bottom:4px;">Şablon</label>
                    <select id="tpl-select" style="width:100%;">
                        <option value="">— Departman seçin —</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;color:var(--u-muted);display:block;margin-bottom:4px;">Öğrenci ID (opsiyonel)</label>
                    <input id="tpl-student" type="text" placeholder="ör. STU-001" style="width:100%;">
                </div>
                <div>
                    <label style="font-size:12px;color:var(--u-muted);display:block;margin-bottom:4px;">Eğitim Danışmanı E-posta (opsiyonel)</label>
                    <input id="tpl-senior" type="email" placeholder="senior@example.com" style="width:100%;">
                </div>
            </div>
            <div id="tpl-preview" style="margin-top:10px;padding:10px 14px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:8px;font-size:13px;color:var(--u-muted);min-height:36px;">
                Şablon seçildiğinde görevler burada görünür.
            </div>
            <div class="actions" style="margin-top:10px;">
                <button class="btn ok" id="tpl-apply-btn" type="button" disabled>✓ Şablonu Uygula</button>
                <span id="tpl-feedback" style="font-size:13px;margin-left:10px;"></span>
            </div>
        </div>
    </details>

    <section class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            <h3 style="margin:0;">Task Listesi</h3>
            <div style="display:flex;gap:6px;">
                <button onclick="switchView('list')"   id="btn-view-list2"   class="btn" style="font-size:12px;padding:5px 12px;">≡ Liste</button>
                <button onclick="switchView('kanban')" id="btn-view-kanban2" class="btn alt" style="font-size:12px;padding:5px 12px;">⊞ Board</button>
                <button onclick="switchView('gantt')"  id="btn-view-gantt2"  class="btn alt" style="font-size:12px;padding:5px 12px;">📅 Gantt</button>
            </div>
        </div>
        <form method="POST" action="/tasks/bulk-update" class="tb-bulk" id="taskBulkForm">
            @csrf
            <div class="tb-bulk-row">
                <input id="bulkTaskPreview" placeholder="Secilen tasklar" readonly>
                <select name="status">
                    <option value="">Durum degistirme</option>
                    @foreach(($statusOptions ?? []) as $k => $label)
                        <option value="{{ $k }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select name="priority">
                    <option value="">Oncelik degistirme</option>
                    @foreach(($priorityOptions ?? []) as $k => $label)
                        <option value="{{ $k }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select name="department" @disabled(!empty($roleScopedDepartment))>
                    <option value="">Departman degistirme</option>
                    @foreach(($departmentOptions ?? []) as $k => $label)
                        <option value="{{ $k }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if(!empty($roleScopedDepartment))
                    <input type="hidden" name="department" value="{{ $roleScopedDepartment }}">
                @endif
                <select name="assigned_user_id">
                    <option value="">Atanan degistirme</option>
                    @foreach(($assignees ?? []) as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Toplu Güncelle</button>
            </div>
        </form>

        @php
        $statusBadge = [
            'todo'        => '',
            'in_progress' => 'info',
            'in_review'   => 'pending',
            'on_hold'     => 'warn',
            'blocked'     => 'danger',
            'done'        => 'ok',
            'cancelled'   => '',
        ];
        $priorityBadge = ['low'=>'','normal'=>'info','high'=>'warn','urgent'=>'danger'];
        $today = now()->toDateString();
        $authUserId   = (int) auth()->id();
        $authUserRole = (string) (auth()->user()?->role ?? '');
        $canApprove   = in_array($authUserRole, [\App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_SYSTEM_ADMIN], true);
        @endphp
        {{-- ── Tablo başlığı ── --}}
        <div class="tl-table">
        <div class="tl-cols tl-head">
            <div class="tl-c tl-c-check"><input type="checkbox" id="select-all" style="width:15px;height:15px;min-height:0;cursor:pointer;" onchange="document.querySelectorAll('.task-select').forEach(cb=>cb.checked=this.checked)"></div>
            <div class="tl-c tl-c-name">Task</div>
            <div class="tl-c tl-c-status">Durum</div>
            <div class="tl-c tl-c-priority">Öncelik</div>
            <div class="tl-c tl-c-dept">Dept</div>
            <div class="tl-c tl-c-assignee">Atanan</div>
            <div class="tl-c tl-c-due">Tarih</div>
            <div class="tl-c tl-c-src">Kaynak</div>
            <div class="tl-c"></div>
        </div>
        @forelse(($rows ?? []) as $row)
            @php
                $sourceLink = null;
                $sourceLinkLabel = 'Kaynaga Git';
                if (in_array((string)$row->source_type, ['guest_ticket_opened', 'guest_ticket_replied'], true)) {
                    $sourceLink = '/tickets-center/'.((string)($row->department ?: 'operations'));
                    $sourceLinkLabel = 'Ticket';
                } elseif ((string)$row->source_type === 'guest_registration_submit') {
                    $sourceLink = '/tickets-center/operations';
                    $sourceLinkLabel = 'Ops Kuyruğu';
                } elseif ((string)$row->source_type === 'guest_document_uploaded') {
                    $sourceLink = '/config#guest-ops';
                    $sourceLinkLabel = 'Belge Onay';
                } elseif (in_array((string)$row->source_type, ['guest_contract_requested','guest_contract_signed_uploaded'], true)) {
                    $sourceLink = '/config#guest-applications';
                    $sourceLinkLabel = 'Aday Öğrenci Dönüşüm';
                } elseif ((string)$row->source_type === 'student_assignment_upsert') {
                    $sourceLink = '/config';
                    $sourceLinkLabel = 'Config';
                } elseif (in_array((string)$row->source_type, ['student_process_outcome_created','student_step_request'], true)) {
                    $sourceLink = '/manager/dashboard';
                    $sourceLinkLabel = 'Manager';
                } elseif ((string)$row->source_type === 'student_document_uploaded') {
                    $sourceLink = '/config#document-standards';
                    $sourceLinkLabel = 'Doküman Onay';
                } elseif ((string)$row->source_type === 'student_onboarding_auto') {
                    $sourceLink = '/tasks/advisory';
                    $sourceLinkLabel = 'Advisory';
                } elseif ((string)$row->source_type === 'manager_request_created') {
                    $sourceLink = '/manager/requests';
                    $sourceLinkLabel = 'Request';
                } elseif (in_array((string)$row->source_type, ['conversation_quick_request','conversation_response_due','conversation_message'], true)) {
                    $sourceLink = '/messages-center/advisory';
                    $sourceLinkLabel = 'Mesaj Merkezi';
                }

                $dueStr  = $row->due_date ? $row->due_date->format('Y-m-d') : null;
                $terminalStatus = in_array((string)$row->status, ['done', 'cancelled'], true);
                $isOver  = $dueStr && $dueStr < $today && ! $terminalStatus;
                $isToday = $dueStr && $dueStr === $today && ! $terminalStatus;
                $dueCls  = $isOver ? 'tk-overdue' : ($isToday ? 'tk-today' : ($terminalStatus ? 'tk-ok' : ''));
                $dueIcon = $isOver ? '⚠' : ($isToday ? '⏰' : '📅');
                $dueLabel = $isOver ? 'GECİKMİŞ · '.$dueStr : ($isToday ? 'BUGÜN' : ($dueStr ?? '-'));

                $sbCls = $statusBadge[$row->status] ?? '';
                $pbCls = $priorityBadge[$row->priority] ?? '';
                $tkCls = 'tk-'.$row->priority
                    . (in_array((string)$row->status, ['done','blocked','in_review','on_hold','cancelled'], true) ? ' tk-'.$row->status : '');

                $assigneeName = $row->assignedUser?->name ?? $row->assignedUser?->email ?? '-';
                $creatorName  = $row->createdByUser?->name ?? $row->createdByUser?->email ?? '-';
            @endphp

            {{-- ── Kompakt satır ── --}}
            <div class="tl-cols tl-row {{ $tkCls }}" id="task-{{ $row->id }}" onclick="tkDetailOpen({{ $row->id }})" title="Görevi aç">
                <div class="tl-c tl-c-check" onclick="event.stopPropagation()">
                    <input type="checkbox" class="task-select" value="{{ $row->id }}" style="width:15px;height:15px;min-height:0;cursor:pointer;">
                </div>
                <div class="tl-c tl-c-name" onclick="event.stopPropagation()">
                    <span class="tl-num">#{{ $row->id }}</span>
                    <a class="tl-title" href="/tasks/{{ $row->id }}/show">{{ $row->title }}</a>
                    @if($row->is_recurring)<span title="Tekrarlayan görev — {{ $row->recurrence_pattern }}, {{ $row->recurrence_interval_days }}g" style="font-size:11px;color:var(--u-brand);" class="badge info" style="font-size:10px;">↻ {{ ucfirst($row->recurrence_pattern ?? 'haftalık') }}</span>@endif
                    @if($row->depends_on_task_id && ($row->dependsOn?->status ?? '') !== 'done')<span class="badge danger" style="font-size:10px;" title="Engellendi: #{{ $row->depends_on_task_id }}">🔒 Bloke</span>@endif
                    @if($row->checklist_total > 0)<span style="font-size:10px;color:var(--u-muted);flex-shrink:0;">☑ {{ $row->checklist_done }}/{{ $row->checklist_total }}</span>@endif
                </div>
                <div class="tl-c tl-c-status"><span class="badge {{ $sbCls }}">{{ $statusOptions[$row->status] ?? $row->status }}</span></div>
                <div class="tl-c tl-c-priority"><span class="badge {{ $pbCls }}">{{ $priorityOptions[$row->priority] ?? $row->priority }}</span></div>
                <div class="tl-c tl-c-dept"><span class="badge">{{ $departmentOptions[$row->department] ?? $row->department }}</span></div>
                <div class="tl-c tl-c-assignee">{{ $assigneeName }}</div>
                <div class="tl-c tl-c-due {{ $isOver ? 'over' : ($isToday ? 'today' : '') }}">
                    @if($dueStr){{ $isOver ? '⚠ ' : ($isToday ? '⏰ ' : '') }}{{ $row->due_date->format('d.m.Y') }}@else —@endif
                </div>
                <div class="tl-c tl-c-src" onclick="event.stopPropagation()">
                    @if($sourceLink)<a href="{{ $sourceLink }}">↗ {{ $sourceLinkLabel }}</a>@endif
                </div>
                <div class="tl-c tl-c-expand" style="justify-content:center;" onclick="event.stopPropagation()">
                    <button class="tl-expand-btn" id="tl-btn-{{ $row->id }}" onclick="tlToggle({{ $row->id }})" title="Hızlı genişlet">▼</button>
                </div>
            </div>

            {{-- ── Detay satırı (gizli) ── --}}
            <div class="tl-detail" id="tl-detail-{{ $row->id }}">
                <div class="tl-detail-inner">
                    {{-- Açıklama + bilgi --}}
                    @if((string)$row->description !== '')
                        <div style="font-size:13px;color:var(--u-text);margin-bottom:8px;padding:8px 12px;background:var(--u-card);border-radius:6px;border:1px solid var(--u-line);">{{ $row->description }}</div>
                    @endif
                    @if($row->status === 'on_hold' && $row->hold_reason)
                        <div style="font-size:12px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:6px 12px;margin-bottom:8px;">⏸ <strong>Bekleme:</strong> {{ $row->hold_reason }}</div>
                    @endif
                    @if(in_array((string)$row->source_type, ['guest_document_uploaded','guest_contract_signed_uploaded'], true))
                        <div style="font-size:11px;color:var(--u-muted);font-style:italic;margin-bottom:8px;">Onay için kaynağa gidip kaydı "approved/done" yapın.</div>
                    @endif
                    {{-- Meta --}}
                    <div style="display:flex;flex-wrap:wrap;gap:12px;font-size:12px;color:var(--u-muted);margin-bottom:10px;">
                        <span>👤 {{ $assigneeName }}@if($assigneeName !== $creatorName) <span style="opacity:.7;">({{ $creatorName }} oluşturdu)</span>@endif</span>
                        @if($row->depends_on_task_id)
                        <span title="Bu görev tamamlanmadan başlanamaz" style="color:{{ ($row->dependsOn?->status ?? '') === 'done' ? 'var(--u-ok)' : '#dc2626' }};">
                            🔗 Bekliyor: #{{ $row->depends_on_task_id }}
                            @if($row->dependsOn) — {{ Str::limit($row->dependsOn->title, 40) }}
                                <span style="font-size:10px;opacity:.7;">({{ $row->dependsOn->status }})</span>
                            @endif
                        </span>
                        @endif
                        @if($row->process_type)<span class="badge info" style="font-size:11px;">{{ \App\Models\MarketingTask::PROCESS_TYPES[$row->process_type] ?? $row->process_type }}</span>@endif
                        @if($row->is_recurring && $row->next_run_at)<span>↻ Sonraki: {{ $row->next_run_at->format('d.m.Y') }}</span>@endif
                    </div>
                </div>

                {{-- ── Footer butonlar — v2.1 state machine ── --}}
                <div class="tk-footer" style="padding:0 18px 12px;display:flex;gap:6px;flex-wrap:wrap;">
                    @if($row->status === 'todo')
                        <form method="POST" action="/tasks/{{ $row->id }}/done">@csrf
                            <button class="btn ok" type="submit">✓ Tamamla</button>
                        </form>
                        <form method="POST" action="/tasks/{{ $row->id }}/cancel" onsubmit="return confirm('İptal edilsin mi?');">@csrf
                            <button class="btn warn" type="submit">✗ İptal</button>
                        </form>
                    @elseif($row->status === 'in_progress')
                        <form method="POST" action="/tasks/{{ $row->id }}/request-review">@csrf
                            <button class="btn ok" type="submit">→ İncelemeye Gönder</button>
                        </form>
                        {{-- Hold butonu: form + inline input ── --}}
                        <form method="POST" action="/tasks/{{ $row->id }}/hold" class="tk-hold-form" style="display:flex;gap:5px;align-items:center;">
                            @csrf
                            <input type="text" name="hold_reason" placeholder="Bekleme nedeni" maxlength="255" style="font-size:12px;padding:4px 8px;border-radius:6px;border:1px solid var(--u-line);width:160px;" required>
                            <button class="btn alt" type="submit" style="font-size:12px;padding:4px 10px;">⏸ Beklet</button>
                        </form>
                        <form method="POST" action="/tasks/{{ $row->id }}/cancel" onsubmit="return confirm('İptal edilsin mi?');">@csrf
                            <button class="btn warn" type="submit" style="font-size:12px;padding:4px 10px;">✗ İptal</button>
                        </form>
                    @elseif($row->status === 'in_review')
                        @if($canApprove || ($isDeptAdmin && (int)$row->assigned_user_id !== $authUserId))
                            <form method="POST" action="/tasks/{{ $row->id }}/approve">@csrf
                                <button class="btn ok" type="submit">✓ Onayla</button>
                            </form>
                        @endif
                        <form method="POST" action="/tasks/{{ $row->id }}/request-revision">@csrf
                            <button class="btn alt" type="submit">↺ Revizyon İste</button>
                        </form>
                    @elseif($row->status === 'on_hold')
                        <form method="POST" action="/tasks/{{ $row->id }}/resume">@csrf
                            <button class="btn ok" type="submit">▶ Devam Et</button>
                        </form>
                        <form method="POST" action="/tasks/{{ $row->id }}/cancel" onsubmit="return confirm('İptal edilsin mi?');">@csrf
                            <button class="btn warn" type="submit" style="font-size:12px;padding:4px 10px;">✗ İptal</button>
                        </form>
                    @elseif($row->status === 'blocked')
                        <form method="POST" action="/tasks/{{ $row->id }}/resume">@csrf
                            <button class="btn alt" type="submit">▶ Engel Kalktı</button>
                        </form>
                        <form method="POST" action="/tasks/{{ $row->id }}/cancel" onsubmit="return confirm('İptal edilsin mi?');">@csrf
                            <button class="btn warn" type="submit" style="font-size:12px;padding:4px 10px;">✗ İptal</button>
                        </form>
                    @elseif($row->status === 'done' || $row->status === 'cancelled')
                        <form method="POST" action="/tasks/{{ $row->id }}/reopen">@csrf
                            <button class="btn alt" type="submit">↺ Yeniden Aç</button>
                        </form>
                    @endif
                    <button class="btn alt task-comment-toggle" data-task-id="{{ $row->id }}" type="button">💬 Yorumlar</button>
                    {{-- v3 — Watcher butonu --}}
                    @php $isWatching = $row->watchers->contains('user_id', auth()->id()); @endphp
                    <button type="button"
                        class="btn alt task-watch-btn"
                        data-task-id="{{ $row->id }}"
                        data-watching="{{ $isWatching ? '1' : '0' }}"
                        style="font-size:12px;padding:4px 10px;"
                        title="{{ $isWatching ? 'Takibi bırak' : 'Takip et — değişikliklerde bildirim al' }}">
                        {{ $isWatching ? '👁 Takip Ediliyor' : '👁 Takip Et' }}
                        <span class="watcher-count" style="color:var(--u-muted);font-size:11px;">({{ $row->watchers->count() }})</span>
                    </button>
                    @if($isDeptAdmin || $isGlobalViewer)
                        <form method="POST" action="/tasks/{{ $row->id }}" onsubmit="return confirm('Task silinsin mi?');" style="margin-left:auto;">
                            @csrf @method('DELETE')
                            <button class="btn warn" type="submit">Sil</button>
                        </form>
                    @endif
                </div>

                {{-- ── Düzenle paneli ── --}}
                <details class="tk-edit-wrap">
                    <summary class="tk-edit-sum">✎ Düzenle</summary>
                    <div class="tk-edit-body">
                        <form method="POST" action="/tasks/{{ $row->id }}">
                            @csrf @method('PUT')
                            <div class="row2">
                                <input class="full" name="title" value="{{ $row->title }}" required>
                                <textarea class="full" name="description">{{ $row->description }}</textarea>
                                <select name="status" id="edit-status-{{ $row->id }}"
                                    onchange="document.getElementById('edit-hold-{{ $row->id }}').style.display=(this.value==='on_hold'?'block':'none')">
                                    @foreach(($statusOptions ?? []) as $k => $label)
                                        <option value="{{ $k }}" @selected($row->status === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div id="edit-hold-{{ $row->id }}" style="{{ $row->status === 'on_hold' ? '' : 'display:none;' }}">
                                    <input type="text" name="hold_reason" value="{{ $row->hold_reason ?? '' }}"
                                        placeholder="Bekleme nedeni (on_hold için)" maxlength="255">
                                </div>
                                <select name="priority">
                                    @foreach(($priorityOptions ?? []) as $k => $label)
                                        <option value="{{ $k }}" @selected($row->priority === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="department" @disabled(!empty($roleScopedDepartment))>
                                    @foreach(($departmentOptions ?? []) as $k => $label)
                                        <option value="{{ $k }}" @selected((string)(($roleScopedDepartment ?? '') !== '' ? $roleScopedDepartment : $row->department) === (string)$k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if(!empty($roleScopedDepartment))
                                    <input type="hidden" name="department" value="{{ $roleScopedDepartment }}">
                                @endif
                                <input type="date" name="due_date" value="{{ $row->due_date ? $row->due_date->format('Y-m-d') : '' }}">
                                <select name="assigned_user_id">
                                    <option value="">Atanan kişi yok</option>
                                    @foreach(($assignees ?? []) as $u)
                                        <option value="{{ $u->id }}" @selected((int)$row->assigned_user_id === (int)$u->id)>{{ $u->name }} ({{ $u->email }})</option>
                                    @endforeach
                                </select>
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <label style="display:flex;align-items:center;gap:8px;font-size:12px;">
                                        <input type="checkbox" name="is_recurring" value="1" id="cb-rec-{{ $row->id }}" style="width:auto;min-height:0;" @checked($row->is_recurring)
                                               onchange="document.getElementById('rec-opts-{{ $row->id }}').style.display=this.checked?'grid':'none'">
                                        ↻ Tekrarlayan görev
                                    </label>
                                    <div id="rec-opts-{{ $row->id }}" style="display:{{ $row->is_recurring ? 'grid' : 'none' }};grid-template-columns:1fr 1fr;gap:6px;">
                                        <select name="recurrence_pattern">
                                            <option value="weekly"  @selected($row->recurrence_pattern === 'weekly')>Haftalık</option>
                                            <option value="daily"   @selected($row->recurrence_pattern === 'daily')>Günlük</option>
                                            <option value="monthly" @selected($row->recurrence_pattern === 'monthly')>Aylık</option>
                                        </select>
                                        <input type="number" min="1" max="365" name="recurrence_interval_days" value="{{ $row->recurrence_interval_days ?: 7 }}" placeholder="Aralık (gün)">
                                    </div>
                                    @if($row->is_recurring && $row->next_run_at)
                                    <div style="font-size:11px;color:var(--u-muted);">↻ Sonraki çalışma: {{ $row->next_run_at->format('d.m.Y') }}</div>
                                    @endif
                                </div>
                                <select name="depends_on_task_id" title="Bu görev tamamlanmadan çalışmaya başlayamaz">
                                    <option value="">🔗 Bağımlı görev (opsiyonel)</option>
                                    @foreach(($recentTasks ?? []) as $rt)
                                    @if($rt->id !== $row->id)
                                    <option value="{{ $rt->id }}" @selected((int)$row->depends_on_task_id === (int)$rt->id)>#{{ $rt->id }} — {{ Str::limit($rt->title, 50) }}</option>
                                    @endif
                                    @endforeach
                                </select>
                                <input type="number" min="1" max="720" name="escalate_after_hours" value="{{ $row->escalate_after_hours ?: 24 }}" placeholder="Eskalasyon (saat)">
                            </div>
                            <div style="display:flex;gap:8px;margin-top:10px;">
                                <button class="btn" type="submit">Güncelle</button>
                            </div>
                        </form>
                    </div>
                </details>

                {{-- ── Checklist paneli ── --}}
                <details class="tk-cmt-wrap" id="checklist-wrap-{{ $row->id }}" open>
                    <summary style="cursor:pointer;font-size:12px;color:var(--u-brand);font-weight:600;padding:8px 14px;display:flex;align-items:center;gap:8px;user-select:none;">
                        ☑ Checklist
                        @if($row->checklist_total > 0)
                            <span style="font-size:11px;color:var(--u-muted);">{{ $row->checklist_done }}/{{ $row->checklist_total }}</span>
                            <span class="badge {{ $row->checklist_done === $row->checklist_total && $row->checklist_total > 0 ? 'ok' : 'info' }}"
                                  style="font-size:10px;">{{ $row->checklist_progress }}%</span>
                        @endif
                    </summary>
                    <div style="padding:6px 14px 10px 14px;" id="checklist-body-{{ $row->id }}">
                        {{-- Progress bar --}}
                        @if($row->checklist_total > 0)
                        <div style="height:4px;background:var(--u-line);border-radius:2px;margin-bottom:8px;overflow:hidden;">
                            <div class="cl-progress-bar" data-task="{{ $row->id }}"
                                 style="height:100%;background:var(--u-ok);width:{{ $row->checklist_progress }}%;transition:width .2s;"></div>
                        </div>
                        @endif
                        {{-- Madde listesi --}}
                        <div id="cl-list-{{ $row->id }}">
                            @foreach($row->checklists as $cl)
                            <div class="cl-item" id="cl-item-{{ $cl->id }}" style="display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid var(--u-line);">
                                <input type="checkbox" data-task="{{ $row->id }}" data-item="{{ $cl->id }}"
                                       class="cl-toggle" style="width:16px;height:16px;min-height:0;accent-color:var(--u-ok);flex-shrink:0;"
                                       {{ $cl->is_done ? 'checked' : '' }}>
                                <span style="{{ $cl->is_done ? 'text-decoration:line-through;color:var(--u-muted);' : '' }}font-size:13px;flex:1;">{{ $cl->title }}</span>
                                @if($isDeptAdmin || $isGlobalViewer || (int)$row->assigned_user_id === $authUserId || (int)$row->created_by_user_id === $authUserId)
                                <button type="button" class="cl-del" data-task="{{ $row->id }}" data-item="{{ $cl->id }}"
                                        style="background:none;border:none;cursor:pointer;color:var(--u-danger);font-size:14px;padding:0 4px;flex-shrink:0;">×</button>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        {{-- Madde ekle --}}
                        @if($isDeptAdmin || $isGlobalViewer || (int)$row->assigned_user_id === $authUserId || (int)$row->created_by_user_id === $authUserId)
                        <form class="cl-add-form" data-task="{{ $row->id }}" style="display:flex;gap:6px;margin-top:8px;">
                            @csrf
                            <input type="text" name="title" placeholder="Madde ekle..." maxlength="255"
                                   style="flex:1;font-size:12px;padding:4px 8px;border-radius:6px;border:1px solid var(--u-line);" required>
                            <button type="submit" class="btn alt" style="font-size:12px;padding:4px 10px;">+ Ekle</button>
                        </form>
                        @endif
                    </div>
                </details>

                {{-- ── Yorum paneli ── --}}
                <div class="tk-cmt-wrap" id="comments-{{ $row->id }}" style="display:none;">
                    <div style="padding:10px 14px;">
                        <div id="comment-list-{{ $row->id }}" style="margin-bottom:10px;"></div>
                        <form class="task-comment-form" data-task-id="{{ $row->id }}" style="display:flex;flex-direction:column;gap:6px;" enctype="multipart/form-data">
                            @csrf
                            <textarea name="body" placeholder="Yorum yazın..." rows="2" style="resize:vertical;" required></textarea>
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <label class="btn alt" for="comment-file-{{ $row->id }}" style="cursor:pointer;font-size:12px;">📎 Dosya Ekle</label>
                                <input type="file" id="comment-file-{{ $row->id }}" name="attachment" style="display:none;"
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                       onchange="document.getElementById('comment-fname-{{ $row->id }}').textContent = this.files[0]?.name || ''">
                                <span id="comment-fname-{{ $row->id }}" style="font-size:12px;color:var(--u-muted);"></span>
                                <button class="btn" type="submit" style="margin-left:auto;">Gönder</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>{{-- /tl-detail --}}
        @empty
            <div class="muted" style="padding:20px;text-align:center;">Görev bulunamadı.</div>
        @endforelse
        </div>{{-- /tl-table --}}
    </section>

    <section class="card">
        <h3 style="margin-top:0;">Kullanim Kilavuzu</h3>
        <ol style="margin:0;padding-left:18px;color:var(--u-muted);">
            <li>Bu ekran ortak board'dur; manager/global roller tum tasklari gorebilir.</li>
            <li>Tekrarlayan gorev acarsan scheduler otomatik yeni task uretir.</li>
            <li>Termin gecip belirtilen saat asilirsa sistem escalation bildirimi kuyruga atar.</li>
        </ol>
    </section>
    </div>{{-- /view-list --}}

{{-- ── Kanban Görünümü ── --}}
<div id="view-kanban" style="display:none;margin-top:4px;">
    <div class="kanban-board" id="kanban-board">
        <div class="kanban-col" id="col-todo" data-status="todo" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title">Yapılacak</div>
        </div>
        <div class="kanban-col" id="col-in_progress" data-status="in_progress" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title">Devam Ediyor</div>
        </div>
        <div class="kanban-col" id="col-in_review" data-status="in_review" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)"
             style="border-top:3px solid #7c3aed;">
            <div class="kanban-col-title" style="background:#ede9fe;color:#4c1d95;">İncelemede</div>
        </div>
        <div class="kanban-col" id="col-blocked" data-status="blocked" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title">Bloke</div>
        </div>
        <div class="kanban-col" id="col-done" data-status="done" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title">Tamamlandı</div>
        </div>
        <div class="kanban-col" id="col-on_hold" data-status="on_hold" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)"
             style="border-top:3px solid #d97706;min-width:160px;">
            <div class="kanban-col-title" style="background:#fef3c7;color:#92400e;">Beklemede</div>
        </div>
    </div>
    <p class="muted" style="font-size:12px;margin-top:8px;">Görevi sürükle-bırak ile kolonlar arasında taşı.</p>
</div>

{{-- ── Task Detay Modalı ── --}}
<div id="tk-detail-bg" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9990;align-items:center;justify-content:center;"
     onclick="if(event.target===this)tkDetailClose()">
    <div style="background:#fff;border-radius:14px;width:560px;max-width:95vw;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,.2);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--u-line);">
            <span id="tk-detail-num" style="font-size:12px;color:var(--u-muted);font-weight:700;"></span>
            <button onclick="tkDetailClose()" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--u-muted);line-height:1;padding:0 4px;">×</button>
        </div>
        <div style="overflow-y:auto;padding:18px 20px;flex:1;" id="tk-detail-body">
            <div class="muted">Yükleniyor…</div>
        </div>
        <div style="padding:12px 20px;border-top:1px solid var(--u-line);display:flex;gap:8px;flex-wrap:wrap;">
            <button class="btn alt" onclick="tkDetailClose()">Kapat</button>
            <a id="tk-detail-list-link" class="btn" href="#" onclick="tkDetailGoList(event)" style="font-size:13px;">≡ Listede Göster</a>
            <a id="tk-detail-actions-link" class="btn ok" href="#" onclick="tkDetailGoActions(event)" style="font-size:13px;margin-left:auto;">✎ Aksiyonlar</a>
        </div>
    </div>
</div>

{{-- ── Ticket Görünümü ── --}}
<div id="view-tickets" style="display:none;margin-top:4px;">
@php
    $tkStatusLabels = ['open'=>'Açık','in_progress'=>'İşlemde','waiting_response'=>'Yanıt Bekleniyor','closed'=>'Kapatıldı'];
    $tkStatusCls    = ['open'=>'danger','in_progress'=>'info','waiting_response'=>'warn','closed'=>''];
    $tkPrioLabels   = ['low'=>'Düşük','normal'=>'Normal','high'=>'Yüksek','urgent'=>'Acil'];
    $tkPrioCls      = ['urgent'=>'danger','high'=>'warn','normal'=>'info','low'=>''];
    $tkDeptLabels   = ['operations'=>'Operasyon','finance'=>'Finans','advisory'=>'Danışmanlık','marketing'=>'Marketing','system'=>'Sistem'];
@endphp
<div class="tl-table">
    <div class="tl-cols tl-head" style="grid-template-columns:48px 1fr 110px 80px 110px 140px 130px;">
        <div class="tl-c">#</div>
        <div class="tl-c">Konu</div>
        <div class="tl-c">Durum</div>
        <div class="tl-c">Öncelik</div>
        <div class="tl-c">Departman</div>
        <div class="tl-c">Başvuran</div>
        <div class="tl-c">Atanan / Tarih</div>
    </div>
    @forelse($tickets ?? [] as $tk)
    @php
        $tkSt  = $tk->status ?? 'open';
        $tkPr  = $tk->priority ?? 'normal';
        $tkApp = $tk->guestApplication;
        $tkWho = $tkApp ? trim($tkApp->first_name . ' ' . $tkApp->last_name) : ($tk->created_by_email ?? '—');
    @endphp
    <div class="tl-cols tl-row" style="grid-template-columns:48px 1fr 110px 80px 110px 140px 130px;cursor:default;"
         onclick="event.stopPropagation()">
        <div class="tl-c" style="color:var(--u-muted);font-size:11px;font-weight:700;">#{{ $tk->id }}</div>
        <div class="tl-c tl-c-name">
            <a href="/tickets-center/{{ $tk->department ?? 'operations' }}?ticket={{ $tk->id }}"
               style="color:var(--u-text);font-weight:600;font-size:var(--tx-sm,.8125rem);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='var(--u-brand)'" onmouseout="this.style.color='var(--u-text)'">
                {{ $tk->subject }}
            </a>
        </div>
        <div class="tl-c"><span class="badge {{ $tkStatusCls[$tkSt] ?? '' }}" style="font-size:11px;">{{ $tkStatusLabels[$tkSt] ?? $tkSt }}</span></div>
        <div class="tl-c"><span class="badge {{ $tkPrioCls[$tkPr] ?? '' }}" style="font-size:11px;">{{ $tkPrioLabels[$tkPr] ?? $tkPr }}</span></div>
        <div class="tl-c"><span class="badge" style="font-size:11px;">{{ $tkDeptLabels[$tk->department ?? ''] ?? ($tk->department ?? '—') }}</span></div>
        <div class="tl-c" style="font-size:var(--tx-xs,.6875rem);color:var(--u-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $tkWho }}">{{ $tkWho }}</div>
        <div class="tl-c" style="font-size:var(--tx-xs,.6875rem);color:var(--u-muted);">
            {{ $tk->assignedUser?->name ?? '—' }}<br>
            <span style="font-size:10px;">{{ $tk->created_at?->format('d.m.Y H:i') }}</span>
        </div>
    </div>
    @empty
    <div style="padding:28px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm,.8125rem);">Açık ticket yok.</div>
    @endforelse
</div>
<p class="muted" style="font-size:11px;margin-top:6px;">Kapalı ticketlar gösterilmez. Detay için <a href="/tickets-center" style="color:var(--u-brand);">Ticket Merkezi</a>'ne git.</p>
</div>

{{-- ── Gantt Görünümü ── --}}
<div id="view-gantt" style="display:none;margin-top:4px;">
    <div id="gantt-container">
        <div class="muted">📅 Yükleniyor…</div>
    </div>
    <p class="muted" style="font-size:11px;margin-top:8px;">Yalnızca bitiş tarihi olan, tamamlanmamış görevler gösterilir.</p>
</div>

<script>
/* ── Yeni Task formu: process_type değişince workflow_stage doldur ── */
(function () {
    var processCatalog = @json(\App\Models\MarketingTask::WORKFLOW_STAGES);
    var ptSel = document.getElementById('new-process-type');
    var wsSel = document.getElementById('new-workflow-stage');
    if (!ptSel || !wsSel) return;
    ptSel.addEventListener('change', function () {
        var stages = (processCatalog || {})[this.value] || {};
        wsSel.innerHTML = '<option value="">Süreç aşaması (opsiyonel)</option>';
        Object.keys(stages).forEach(function (k) {
            var o = document.createElement('option');
            o.value = k; o.textContent = stages[k];
            wsSel.appendChild(o);
        });
    });
})();

/* ── tl-table satır toggle — global ── */
window.tlToggle = function(id) {
    var detail = document.getElementById('tl-detail-' + id);
    var btn    = document.getElementById('tl-btn-' + id);
    var row    = document.getElementById('task-' + id);
    if (!detail) return;
    var open = detail.classList.toggle('open');
    if (btn) btn.classList.toggle('open', open);
    if (row) row.classList.toggle('tl-open', open);
};

/* ── Task Board: override task-kanban.js to use /tasks endpoints ── */
(function () {
    function tbEsc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    var prioClsMap = { urgent:'priority-urgent', high:'priority-high', normal:'priority-normal', low:'priority-low' };
    var _dept = {{ json_encode($routeDepartment ?? '') }};
    var _csrfTok = function () { var el = document.querySelector('input[name="_token"]'); return el ? el.value : ''; };

    /* ── Kanban load — calls /tasks/kanban/data ── */
    var _origSwitchView = window.switchView;
    window.switchView = function (mode) {
        var listEl    = document.getElementById('view-list');
        var kanbanEl  = document.getElementById('view-kanban');
        var ganttEl   = document.getElementById('view-gantt');
        var ticketsEl = document.getElementById('view-tickets');
        var inactive = { background:'#eef4fb', color:'#204d87' };
        var active   = { background:'#0a67d8', color:'#fff' };
        var allBtns = ['btn-view-list','btn-view-kanban','btn-view-gantt','btn-view-tickets','btn-view-list2','btn-view-kanban2','btn-view-gantt2'];
        allBtns.forEach(function(id){ var b=document.getElementById(id); if(b){b.style.background=inactive.background;b.style.color=inactive.color;} });
        if (listEl)    listEl.style.display    = 'none';
        if (kanbanEl)  kanbanEl.style.display  = 'none';
        if (ganttEl)   ganttEl.style.display   = 'none';
        if (ticketsEl) ticketsEl.style.display = 'none';
        if (mode === 'kanban') {
            if (kanbanEl) kanbanEl.style.display = 'block';
            ['btn-view-kanban','btn-view-kanban2'].forEach(function(id){ var b=document.getElementById(id); if(b){b.style.background=active.background;b.style.color=active.color;} });
            loadKanbanData();
        } else if (mode === 'gantt') {
            if (ganttEl) ganttEl.style.display = 'block';
            ['btn-view-gantt','btn-view-gantt2'].forEach(function(id){ var b=document.getElementById(id); if(b){b.style.background=active.background;b.style.color=active.color;} });
            loadGanttData();
        } else if (mode === 'tickets') {
            if (ticketsEl) ticketsEl.style.display = 'block';
            var b = document.getElementById('btn-view-tickets'); if(b){b.style.background=active.background;b.style.color=active.color;}
        } else {
            if (listEl) listEl.style.display = 'block';
            ['btn-view-list','btn-view-list2'].forEach(function(id){ var b=document.getElementById(id); if(b){b.style.background=active.background;b.style.color=active.color;} });
        }
        try { localStorage.setItem('tb_task_view', mode); } catch (_) {}
    };

    var _kanbanLoaded = false;
    function loadKanbanData() {
        if (_kanbanLoaded) return;
        _kanbanLoaded = true;
        var url = '/tasks/kanban/data' + (_dept ? '?department=' + _dept : '');
        fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrfTok() } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var cols = ['todo', 'in_progress', 'in_review', 'blocked', 'done', 'on_hold'];
                cols.forEach(function (status) {
                    var col = document.getElementById('col-' + status);
                    if (!col) return;
                    Array.from(col.children).forEach(function (ch) {
                        if (!ch.classList.contains('kanban-col-title')) col.removeChild(ch);
                    });
                    (data[status] || []).forEach(function (t) {
                        var card = document.createElement('div');
                        card.className = 'kanban-card ' + (prioClsMap[t.priority] || 'priority-normal');
                        card.setAttribute('draggable', 'true');
                        card.dataset.id     = t.id;
                        card.dataset.status = t.status;
                        var due = t.due_date ? ' | ' + t.due_date : '';
                        var ass = t.assignee ? ' | ' + tbEsc(t.assignee) : '';
                        var proc = t.process_type ? '<span style="font-size:9px;background:#f5f3ff;color:#7c3aed;border-radius:4px;padding:1px 5px;margin-left:3px;">' + tbEsc(t.process_type_label || t.process_type) + '</span>' : '';
                        card.innerHTML = '<div class="kc-title">#' + t.id + ' ' + tbEsc(t.title) + proc + '</div>' +
                            '<div class="kc-meta">' + tbEsc(t.priority || '') + due + ass + '</div>';
                        var _dragging = false;
                        card.addEventListener('dragstart', function (e) {
                            _dragging = true;
                            e.dataTransfer.effectAllowed = 'move';
                            e.dataTransfer.setData('text/plain', String(t.id));
                            setTimeout(function () { card.style.opacity = '0.5'; }, 0);
                        });
                        card.addEventListener('dragend', function () {
                            card.style.opacity = '';
                            setTimeout(function () { _dragging = false; }, 200);
                        });
                        card.addEventListener('click', function () {
                            if (_dragging) return;
                            window.location.href = '/tasks/' + t.id + '/show';
                        });
                        col.appendChild(card);
                    });
                });
            })
            .catch(function (err) { console.warn('[Kanban] load error', err); });
    }

    /* ── kanbanDragOver ── */
    window.kanbanDragOver = function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        e.currentTarget.classList.add('drag-over');
    };

    /* ── kanbanDrop: persist via /tasks/{id}/kanban ── */
    window.kanbanDrop = function (e, colEl) {
        e.preventDefault();
        colEl.classList.remove('drag-over');
        var dragId = e.dataTransfer.getData('text/plain');
        if (!dragId) return;
        var newStatus = colEl.dataset.status;
        if (!newStatus) return;
        var dragEl = document.querySelector('.kanban-card[data-id="' + dragId + '"]');
        var existing = Array.from(colEl.children).filter(function (c) { return c.classList.contains('kanban-card'); });
        var newOrder = existing.length;
        if (dragEl) { colEl.appendChild(dragEl); dragEl.dataset.status = newStatus; }
        fetch('/tasks/' + dragId + '/kanban', {
            method: 'PATCH',
            headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': _csrfTok() },
            body: JSON.stringify({ status: newStatus, column_order: newOrder }),
        }).then(function (r) { return r.json(); }).catch(function () {});
    };

    /* ── Gantt ── */
    var _ganttLoaded = false;
    function loadGanttData() {
        if (_ganttLoaded) return;
        _ganttLoaded = true;
        var url = '/tasks/gantt/data' + (_dept ? '?department=' + _dept : '');
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) { renderGantt(d); })
            .catch(function () { document.getElementById('gantt-container').innerHTML = '<div class="muted">Yüklenemedi.</div>'; });
    }
    window.loadGantt = loadGanttData;

    function renderGantt(d) {
        var tasks = d.tasks || [];
        var container = document.getElementById('gantt-container');
        if (!tasks.length) { container.innerHTML = '<div class="muted">Tarih atanmış tamamlanmamış görev yok.</div>'; return; }
        var startMs = new Date(d.range_start).getTime();
        var endMs   = new Date(d.range_end).getTime();
        var todayMs = new Date(d.today).getTime();
        var rangeMs = endMs - startMs;
        var DAY_MS  = 86400000;
        function pct(ms) { return Math.max(0, Math.min(100, ((ms - startMs) / rangeMs) * 100)); }
        function fmtDate(ds) { return new Date(ds + 'T00:00:00').toLocaleDateString('tr-TR', { day:'2-digit', month:'short' }); }
        function escG(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
        var prioColor = { urgent:'#ef4444', high:'#f59e0b', normal:'#3b82f6', low:'#94a3b8' };
        var ruler = '<div class="gantt-ruler">';
        for (var ms = startMs; ms <= endMs; ms += DAY_MS * 7) {
            ruler += '<div class="gantt-ruler-tick" style="left:' + pct(ms) + '%">' + fmtDate(new Date(ms).toISOString().slice(0,10)) + '</div>';
        }
        ruler += '</div>';
        var todayPct = pct(todayMs);
        var rows = '';
        tasks.forEach(function (t) {
            var bL = pct(new Date(t.start + 'T00:00:00').getTime());
            var bR = pct(new Date(t.end   + 'T00:00:00').getTime());
            var bW = Math.max(bR - bL, 0.8);
            var color = prioColor[t.priority] || '#3b82f6';
            rows += '<div class="gantt-row"><div class="gantt-label"><span class="gantt-id">#' + t.id + '</span>' + escG(t.title) + '</div>' +
                '<div class="gantt-track"><div class="gantt-today" style="left:' + todayPct + '%"></div>' +
                '<div class="gantt-bar" style="left:' + bL + '%;width:' + bW + '%;background:' + color + '">' + escG(t.title) + '</div></div></div>';
        });
        container.innerHTML = '<div class="gantt-wrap"><div class="gantt-chart">' + ruler + rows + '</div></div>';
    }

    /* ── Task Detay Modalı ── */
    var _detailTaskId = null;
    var statusColors = { todo:'#6b7c93', in_progress:'#2563eb', in_review:'#7c3aed', on_hold:'#d97706', blocked:'#ef4444', done:'#16a34a', cancelled:'#9ca3af' };

    window.tkDetailOpen = function (taskId) {
        _detailTaskId = taskId;
        var bg   = document.getElementById('tk-detail-bg');
        var body = document.getElementById('tk-detail-body');
        var num  = document.getElementById('tk-detail-num');
        if (!bg) return;
        num.textContent = '#' + taskId;
        body.innerHTML  = '<div class="muted" style="padding:20px 0;text-align:center;">Yükleniyor…</div>';
        bg.style.display = 'flex';
        fetch('/tasks/' + taskId + '/detail', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.error) { body.innerHTML = '<div style="color:red;">' + tbEsc(d.error) + '</div>'; return; }
                var sc = statusColors[d.status] || '#6b7c93';
                var cl = '';
                if (d.checklist && d.checklist.length) {
                    cl = '<div style="margin-top:14px;"><div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:6px;">Checklist</div>';
                    d.checklist.forEach(function (c) {
                        cl += '<div style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:13px;' + (c.is_done ? 'color:var(--u-muted);text-decoration:line-through;' : '') + '">' +
                            '<span style="font-size:15px;">' + (c.is_done ? '☑' : '☐') + '</span>' + tbEsc(c.title) + '</div>';
                    });
                    cl += '</div>';
                }
                body.innerHTML =
                    '<div style="font-size:17px;font-weight:700;line-height:1.35;margin-bottom:12px;">' + tbEsc(d.title) + '</div>' +
                    '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;">' +
                        '<span style="background:' + sc + '1a;color:' + sc + ';border:1px solid ' + sc + '44;border-radius:6px;padding:2px 10px;font-size:12px;font-weight:700;">' + tbEsc(d.status_label) + '</span>' +
                        '<span style="background:#f0f5fc;color:#2b4d74;border:1px solid #dce8f7;border-radius:6px;padding:2px 10px;font-size:12px;">' + tbEsc(d.priority_label) + '</span>' +
                        '<span style="background:#f0f5fc;color:#2b4d74;border:1px solid #dce8f7;border-radius:6px;padding:2px 10px;font-size:12px;">' + tbEsc(d.department_label) + '</span>' +
                    '</div>' +
                    '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;margin-bottom:12px;">' +
                        '<div><span style="color:var(--u-muted);font-size:11px;">ATANAN</span><br>' + tbEsc(d.assignee) + '</div>' +
                        '<div><span style="color:var(--u-muted);font-size:11px;">OLUŞTURAN</span><br>' + tbEsc(d.creator) + '</div>' +
                        '<div><span style="color:var(--u-muted);font-size:11px;">VERİM TARİHİ</span><br>' + (d.due_date || '—') + '</div>' +
                        '<div><span style="color:var(--u-muted);font-size:11px;">OLUŞTURULMA</span><br>' + (d.created_at || '—') + '</div>' +
                    '</div>' +
                    (d.description ? '<div style="background:#f8fbff;border:1px solid var(--u-line);border-radius:8px;padding:10px 12px;font-size:13px;margin-bottom:4px;white-space:pre-wrap;">' + tbEsc(d.description) + '</div>' : '') +
                    (d.hold_reason ? '<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:8px 12px;font-size:12px;color:#92400e;margin-top:8px;">⏸ ' + tbEsc(d.hold_reason) + '</div>' : '') +
                    cl;
            })
            .catch(function () { body.innerHTML = '<div class="muted">Yüklenemedi.</div>'; });
    };

    window.tkDetailClose = function () {
        var bg = document.getElementById('tk-detail-bg');
        if (bg) bg.style.display = 'none';
        _detailTaskId = null;
    };

    window.tkDetailGoList = function (e) {
        e.preventDefault();
        tkDetailClose();
        switchView('list');
        if (_detailTaskId) {
            setTimeout(function () {
                var el = document.getElementById('task-' + _detailTaskId);
                if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.style.outline = '2px solid #0a67d8'; setTimeout(function () { el.style.outline = ''; }, 2000); }
            }, 100);
        }
    };

    /* Modal'dan inline expand'e geç (aksiyonlar için) */
    window.tkDetailGoActions = function (e) {
        e.preventDefault();
        var tid = _detailTaskId;
        tkDetailClose();
        switchView('list');
        if (tid) {
            setTimeout(function () {
                var el = document.getElementById('task-' + tid);
                if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                tlToggle(tid);
            }, 120);
        }
    };

    // Esc tuşu ile kapat
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tkDetailClose(); });

    /* ── Init ── */
    var pref = 'list';
    try { pref = localStorage.getItem('tb_task_view') || 'list'; } catch (_) {}
    // Run after DOM ready (scripts are not deferred)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { switchView(pref); });
    } else {
        switchView(pref);
    }
})();
</script>
@endsection

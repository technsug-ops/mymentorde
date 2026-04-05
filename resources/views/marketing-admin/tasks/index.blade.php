@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<button onclick="switchView('list')"   id="btn-view-list"   class="btn view-active" style="font-size:var(--tx-xs);padding:6px 12px;">≡ Liste</button>
<button onclick="switchView('kanban')" id="btn-view-kanban" class="btn alt"         style="font-size:var(--tx-xs);padding:6px 12px;">⊞ Board</button>
<button onclick="switchView('gantt')"  id="btn-view-gantt"  class="btn alt"         style="font-size:var(--tx-xs);padding:6px 12px;">📅 Gantt</button>
@endsection

@section('title', 'Görev Panosu')
@section('page_subtitle', 'Marketing &amp; Sales ekibi — diğer birimlerden atanan görevler dahil')

@section('content')
<style>
/* ── Genel form elemanları ── */
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; }
.card input:not([type=checkbox]):not([type=radio]),
.card select, .card textarea,
details input:not([type=checkbox]):not([type=radio]),
details select, details textarea {
    width:100%; box-sizing:border-box; height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.card textarea { height:64px; padding:8px 10px; resize:vertical; }
.card input:focus, .card select:focus, .card textarea:focus,
details input:focus, details select:focus, details textarea:focus {
    border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10);
}
.card input[type=date], details input[type=date] { padding:0 8px; }
.card h3 { font-size:14px; font-weight:700; margin:0 0 14px; padding-bottom:10px; border-bottom:1px solid var(--u-line); }
.row2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.row2 .full { grid-column:1/-1; }
.full { width:100%; box-sizing:border-box; }
.actions { display:flex; gap:8px; flex-wrap:wrap; padding-top:10px; }
.muted { color:var(--u-muted,#64748b); font-size:12px; }
.tk-field { display:flex; flex-direction:column; gap:3px; }
.tk-lbl   { font-size:11px; font-weight:600; color:var(--u-muted,#64748b); letter-spacing:.01em; }

/* ── 8-col Stats Bar ── */
.tb-stats { display:grid; grid-template-columns:repeat(8,minmax(0,1fr)); gap:8px; margin-bottom:12px; }
@media(max-width:1100px){ .tb-stats{ grid-template-columns:repeat(4,minmax(0,1fr)); } }
@media(max-width:700px){ .tb-stats{ grid-template-columns:repeat(2,minmax(0,1fr)); } }
.tb-stat { background:var(--u-card); border:1px solid var(--u-line); border-radius:10px; padding:10px 14px; text-decoration:none; display:block; transition:box-shadow .15s,background .15s; }
.tb-stat:hover { box-shadow:0 2px 8px rgba(0,0,0,.1); background:#f0f6ff; }
.tb-stat .tb-lbl { font-size:10px; color:var(--u-muted); text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
.tb-stat .tb-val { font-size:24px; font-weight:700; color:var(--u-text); line-height:1; }
.tb-stat.tb-overdue   .tb-val { color:var(--u-danger,#dc2626); }
.tb-stat.tb-review    .tb-val { color:#7c3aed; }
.tb-stat.tb-hold      .tb-val { color:#d97706; }
.tb-stat.tb-cancelled .tb-val { color:#6b7280; }

/* ── Bulk bar ── */
.tb-bulk {
    border:1.5px solid var(--u-brand,#1e40af);
    border-radius:10px; padding:10px 16px; margin-bottom:10px;
    background:color-mix(in srgb,var(--u-brand,#1e40af) 6%,var(--u-card,#fff));
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.tb-bulk-lbl {
    font-size:11px; font-weight:700; color:var(--u-brand,#1e40af);
    letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; flex-shrink:0;
}
.tb-bulk-row { display:contents; }
.tb-bulk select, .tb-bulk input[readonly] {
    height:34px; padding:0 10px; border:1px solid var(--u-line,#e2e8f0);
    border-radius:8px; background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:12px; outline:none; transition:border-color .15s; appearance:auto;
    min-width:0; flex:1 1 110px;
}
.tb-bulk input[readonly] { color:var(--u-muted,#64748b); flex:2 1 160px; cursor:default; }
.tb-bulk select:focus { border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10); }
@media(max-width:900px){ .tb-bulk{ flex-wrap:wrap; } }

/* ── View toggle active ── */
.btn.view-active { background:var(--u-brand,#1e40af); color:#fff; border-color:transparent; }

/* ── ClickUp-style tablo ── */
.tl-table { border:1px solid var(--u-line); border-radius:10px; overflow:hidden; background:var(--u-card); }
.tl-cols { display:grid; grid-template-columns:32px 1fr 96px 68px 130px 88px 78px 34px; }
.tl-head { background:var(--u-bg,#f8fafc); border-bottom:2px solid var(--u-line); }
.tl-head .tl-c { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--u-muted); padding:7px 8px; }
.tl-row { border-bottom:1px solid var(--u-line); border-left:3px solid transparent; transition:background .1s; cursor:pointer; }
.tl-row:last-child { border-bottom:none; }
.tl-row:hover { background:#f4f8ff; }
.tl-row.tl-open { background:#eef4ff; border-left-color:var(--u-brand,#1e40af) !important; }
.tl-row.tk-urgent   { border-left-color:#ef4444; }
.tl-row.tk-high     { border-left-color:#f59e0b; }
.tl-row.tk-normal   { border-left-color:#3b82f6; }
.tl-row.tk-low      { border-left-color:#94a3b8; }
.tl-row.tk-done     { opacity:.72; }
.tl-row.tk-cancelled{ opacity:.50; }
.tl-row.tk-in_review{ border-left-color:#7c3aed; }
.tl-row.tk-on_hold  { border-left-color:#d97706; }
.tl-c { padding:7px 8px; display:flex; align-items:center; font-size:13px; min-width:0; overflow:hidden; }
.tl-c-check { justify-content:center; }
.tl-c-name  { gap:5px; }
.tl-title   { font-weight:600; color:var(--u-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; min-width:0; }
.tl-row.tk-done .tl-title, .tl-row.tk-cancelled .tl-title { text-decoration:line-through; color:var(--u-muted); }
.tl-row:hover .tl-title { color:var(--u-brand,#1e40af); }
.tl-row.tk-done:hover .tl-title, .tl-row.tk-cancelled:hover .tl-title { color:var(--u-muted); }
.tl-num   { font-size:10px; color:var(--u-muted); flex-shrink:0; background:var(--u-bg,#f3f4f6); padding:1px 5px; border-radius:4px; }
.tl-c .badge { font-size:10px; padding:2px 7px; white-space:nowrap; }
.tl-c-due { font-size:11px; color:var(--u-muted); }
.tl-c-due.over  { color:#ef4444; font-weight:700; font-size:11px; }
.tl-c-due.today { color:#d97706; font-weight:700; font-size:11px; }
.tl-c-assignee  { font-size:11px; color:var(--u-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tl-expand-btn { background:none; border:none; cursor:pointer; color:var(--u-muted); font-size:11px; padding:3px 5px; border-radius:4px; width:26px; height:26px; display:flex; align-items:center; justify-content:center; transition:all .15s; }
.tl-expand-btn:hover { background:var(--u-bg); color:var(--u-brand); }
.tl-expand-btn.open { transform:rotate(180deg); color:var(--u-brand); }
.tl-detail { display:none; border-bottom:1px solid var(--u-line); background:#f8fbff; border-left:3px solid var(--u-brand,#1e40af); }
.tl-detail.open { display:block; }
.tl-detail-inner { padding:10px 16px 4px; }
@media(max-width:1100px){ .tl-cols{ grid-template-columns:32px 1fr 96px 68px 130px 34px; } .tl-c-due{ display:none; } }
@media(max-width:800px){ .tl-cols{ grid-template-columns:32px 1fr 96px 34px; } .tl-c-assignee,.tl-c-priority{ display:none; } }

/* ── Hold reason bar ── */
.tk-hold-bar { padding:4px 14px 6px; font-size:12px; color:#92400e; background:#fffbeb; border-top:1px solid #fde68a; }

/* ── Task detail panel sections ── */
.tk-edit-wrap { border-top:1px solid var(--u-line); background:#f8fbff; }
.tk-edit-sum  { cursor:pointer; font-size:12px; color:var(--u-brand); font-weight:600; padding:8px 14px; display:block; user-select:none; }
.tk-edit-sum:hover { background:#eef4fb; }
.tk-edit-body { padding:10px 14px 14px; }
.tk-edit-body input,.tk-edit-body select,.tk-edit-body textarea {
    width:100%; border:1px solid var(--u-line); border-radius:8px; padding:7px 10px; font-size:13px; min-height:36px;
}
.tk-edit-body textarea { min-height:64px; resize:vertical; }
.tf-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
.tf-grid .full { grid-column:1/-1; }

/* Sub-tasks + Checklist */
.tk-sub-wrap { border-top:1px solid var(--u-line); background:#f9fbff; }
.tk-sub-sum  { cursor:pointer; font-size:12px; color:#5b72a8; font-weight:600; padding:7px 14px; display:flex; align-items:center; gap:6px; user-select:none; }
.tk-sub-sum:hover { background:#eef4fb; }
.tk-sub-count { display:inline-flex; align-items:center; background:#3b82f6; color:#fff; border-radius:12px; font-size:10px; padding:1px 7px; font-weight:700; }
.tk-sub-count.all-done { background:var(--u-ok,#16a34a); }
.tk-sub-body  { padding:6px 14px 12px; }
.tk-sub-item  { display:flex; align-items:center; gap:8px; padding:5px 0; border-bottom:1px solid #f0f4f8; }
.tk-sub-check { width:15px; height:15px; flex:none; cursor:pointer; accent-color:var(--u-ok,#16a34a); }
.tk-sub-title { flex:1; font-size:13px; color:var(--u-text); }
.tk-sub-title.done { text-decoration:line-through; color:var(--u-muted); }
.tk-sub-del   { background:none; border:none; cursor:pointer; color:var(--u-muted); font-size:14px; padding:0 4px; }
.tk-sub-del:hover { color:#ef4444; }
.tk-sub-add   { display:flex; gap:6px; margin-top:8px; }
.tk-sub-input { flex:1; border:1px solid var(--u-line); border-radius:6px; padding:6px 10px; font-size:12px; min-height:0; }

/* Attachments */
.ta-panel { border-top:1px solid var(--u-line); background:#fafcff; }
.ta-sum   { cursor:pointer; font-size:12px; color:#5b72a8; font-weight:600; padding:8px 14px; display:block; user-select:none; }
.ta-sum:hover { background:#eef4fb; }
.ta-body  { padding:10px 14px 14px; }
.ta-gallery { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px; min-height:4px; }
.ta-item  { position:relative; }
.ta-thumb { width:90px; height:70px; object-fit:cover; border-radius:6px; border:1px solid var(--u-line); display:block; }
.ta-video { width:180px; height:110px; border-radius:6px; display:block; }
.ta-file-link { display:inline-flex; align-items:center; gap:4px; background:#eef4fb; border:1px solid var(--u-line); border-radius:6px; padding:6px 10px; font-size:12px; color:var(--u-brand); text-decoration:none; max-width:200px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.ta-del-btn { position:absolute; top:-5px; right:-5px; background:#ef4444; color:#fff; border:none; border-radius:50%; width:18px; height:18px; font-size:10px; line-height:18px; text-align:center; cursor:pointer; padding:0; font-weight:700; }
.ta-drop-zone { border:2px dashed var(--u-line); border-radius:8px; padding:12px; text-align:center; font-size:12px; color:var(--u-muted); cursor:pointer; margin-bottom:8px; }
.ta-drop-zone:hover,.ta-drop-zone.ta-drag-over { background:#eef4fb; border-color:var(--u-brand); color:var(--u-brand); }
.ta-controls { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
.ta-link-input { flex:1; min-width:140px; border:1px solid var(--u-line); border-radius:6px; padding:6px 10px; font-size:12px; min-height:0; }
.ta-status { font-size:11px; color:var(--u-muted); margin-top:6px; }

/* Mentions */
.tm-mentions { display:flex; flex-wrap:wrap; gap:5px; padding:3px 14px 6px; }
.tm-chip { display:inline-flex; align-items:center; gap:4px; background:#eef4fb; border:1px solid #c4d7f0; border-radius:20px; padding:2px 8px; font-size:11px; color:#2b5a9e; }
.tm-chip-del { background:none; border:none; cursor:pointer; color:#8fa6c5; font-size:12px; line-height:1; padding:0; }
.tm-chip-del:hover { color:#ef4444; }
.tm-modal-bg { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:9990; }
.tm-modal-box { background:#fff; border-radius:14px; padding:20px; width:380px; max-width:95vw; max-height:80vh; display:flex; flex-direction:column; box-shadow:0 8px 30px rgba(0,0,0,.18); }
.tm-search { border:1px solid var(--u-line); border-radius:8px; padding:8px 12px; font-size:13px; width:100%; margin-bottom:10px; }
.tm-user-list { overflow-y:auto; flex:1; }
.tm-user-item { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; cursor:pointer; font-size:13px; transition:background .1s; }
.tm-user-item:hover { background:#f0f5fc; }
.tm-user-item.tm-sel { background:#eef4fb; font-weight:600; color:#204d87; }
.tm-user-item .tm-role { font-size:10px; color:var(--u-muted); }

/* Kanban */
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

/* Gantt */
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

{{-- ── 8-col Stats Bar ── --}}
<div class="tb-stats">
    <a class="tb-stat" href="/mktg-admin/tasks" style="text-decoration:none;">
        <div class="tb-lbl">Toplam</div><div class="tb-val">{{ $stats['total'] ?? 0 }}</div>
    </a>
    <a class="tb-stat" href="/mktg-admin/tasks?status=todo" style="text-decoration:none;">
        <div class="tb-lbl">Yapılacak</div><div class="tb-val">{{ $stats['todo'] ?? 0 }}</div>
    </a>
    <a class="tb-stat" href="/mktg-admin/tasks?status=in_progress" style="text-decoration:none;">
        <div class="tb-lbl">Devam Eden</div><div class="tb-val">{{ $stats['in_progress'] ?? 0 }}</div>
    </a>
    <a class="tb-stat tb-review" href="/mktg-admin/tasks?status=in_review" style="text-decoration:none;">
        <div class="tb-lbl">İncelemede</div><div class="tb-val">{{ $stats['in_review'] ?? 0 }}</div>
    </a>
    <a class="tb-stat tb-hold" href="/mktg-admin/tasks?status=on_hold" style="text-decoration:none;">
        <div class="tb-lbl">Beklemede+Bloke</div><div class="tb-val">{{ $stats['on_hold'] ?? 0 }}</div>
    </a>
    <a class="tb-stat" href="/mktg-admin/tasks?status=done" style="text-decoration:none;">
        <div class="tb-lbl">Tamamlanan</div><div class="tb-val">{{ $stats['done'] ?? 0 }}</div>
    </a>
    <a class="tb-stat tb-overdue" href="/mktg-admin/tasks?sla=overdue" style="text-decoration:none;">
        <div class="tb-lbl">Geciken</div><div class="tb-val">{{ $stats['overdue'] ?? 0 }}</div>
    </a>
    <a class="tb-stat tb-cancelled" href="/mktg-admin/tasks?status=cancelled" style="text-decoration:none;">
        <div class="tb-lbl">İptal</div><div class="tb-val">{{ $stats['cancelled'] ?? 0 }}</div>
    </a>
</div>

{{-- ── Liste Görünümü ── --}}
<div id="view-list">

    {{-- ── Yeni Görev (tam genişlik, gizli form) ── --}}
    <details class="card" id="newTaskDetails" style="margin-bottom:10px;">
        <summary style="cursor:pointer;font-weight:700;font-size:var(--tx-sm);list-style:none;display:flex;align-items:center;gap:6px;padding-bottom:0;user-select:none;">
            <span style="font-size:var(--tx-base);color:var(--u-brand,#1e40af);">＋</span> Yeni Görev
            <span class="muted" style="font-size:var(--tx-xs);font-weight:400;">(formu aç)</span>
        </summary>
        <div style="margin-top:14px;border-top:1px solid var(--u-line);padding-top:12px;">
            <form method="POST" action="/mktg-admin/tasks">
                @csrf
                <div class="row2">
                    <div class="tk-field full">
                        <span class="tk-lbl">Başlık <span style="color:var(--u-danger,#dc2626);">*</span></span>
                        <input name="title" placeholder="Görev başlığı girin" required>
                    </div>
                    <div class="tk-field full">
                        <span class="tk-lbl">Açıklama</span>
                        <textarea name="description" placeholder="Opsiyonel açıklama..."></textarea>
                    </div>
                    <div class="tk-field">
                        <span class="tk-lbl">Durum</span>
                        <select name="status">
                            @foreach(($statusOptions ?? []) as $k => $lbl)
                                <option value="{{ $k }}" @selected($k === 'todo')>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tk-field">
                        <span class="tk-lbl">Öncelik</span>
                        <select name="priority">
                            @foreach(($priorityOptions ?? []) as $k => $lbl)
                                <option value="{{ $k }}" @selected($k === 'normal')>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tk-field">
                        <span class="tk-lbl">Başlangıç (Gantt)</span>
                        <input type="date" name="start_date">
                    </div>
                    <div class="tk-field">
                        <span class="tk-lbl">Bitiş / Vade</span>
                        <input type="date" name="due_date">
                    </div>
                    <div class="tk-field full">
                        <span class="tk-lbl">Atanan Kişi</span>
                        <select name="assigned_user_id">
                            <option value="">— Seçilmedi (opsiyonel) —</option>
                            @foreach(($assignees ?? []) as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="actions">
                    <button class="btn ok" type="submit">Görev Ekle</button>
                </div>
            </form>
        </div>
    </details>

    {{-- ── Filtre (yatay bar) ── --}}
    <div class="card" style="margin-bottom:10px;padding:10px 14px;">
        <form method="GET" action="/mktg-admin/tasks" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <select name="status" style="flex:1;min-width:130px;height:34px;border:1px solid var(--u-line);border-radius:8px;padding:0 8px;font-size:var(--tx-xs);background:var(--u-card);color:var(--u-text);">
                <option value="">Tüm durumlar</option>
                @foreach(($statusOptions ?? []) as $k => $label)
                    <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="priority" style="flex:1;min-width:130px;height:34px;border:1px solid var(--u-line);border-radius:8px;padding:0 8px;font-size:var(--tx-xs);background:var(--u-card);color:var(--u-text);">
                <option value="">Tüm öncelikler</option>
                @foreach(($priorityOptions ?? []) as $k => $label)
                    <option value="{{ $k }}" @selected(($filters['priority'] ?? '') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="assignee" style="flex:2;min-width:160px;height:34px;border:1px solid var(--u-line);border-radius:8px;padding:0 8px;font-size:var(--tx-xs);background:var(--u-card);color:var(--u-text);">
                <option value="0">Tüm ekip</option>
                @foreach(($assignees ?? []) as $u)
                    <option value="{{ $u->id }}" @selected((int)($filters['assignee'] ?? 0) === (int)$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit" style="height:34px;padding:0 14px;font-size:var(--tx-xs);">Filtrele</button>
            <a class="btn alt" href="/mktg-admin/tasks" style="height:34px;padding:0 14px;font-size:var(--tx-xs);display:flex;align-items:center;">Temizle</a>
        </form>
    </div>

    {{-- ── Toplu Güncelleme ── --}}
    <div class="tb-bulk" id="taskBulkWrap">
        <form method="POST" action="/mktg-admin/tasks/bulk-update" id="taskBulkForm" style="display:contents;">
            @csrf
            <input type="hidden" name="task_ids" id="bulkTaskIds" value="">
            <span class="tb-bulk-lbl">✦ Toplu İşlem</span>
            <div class="tb-bulk-row">
                <input id="bulkTaskPreview" placeholder="Seçilen görevler (listeden seç)" readonly>
                <select name="status">
                    <option value="">Durum değiştir</option>
                    @foreach(($statusOptions ?? []) as $k => $label)
                        <option value="{{ $k }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select name="priority">
                    <option value="">Öncelik değiştir</option>
                    @foreach(($priorityOptions ?? []) as $k => $label)
                        <option value="{{ $k }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select name="assigned_user_id">
                    <option value="">Atanan değiştir</option>
                    @foreach(($assignees ?? []) as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn ok" type="submit" style="height:34px;font-size:var(--tx-xs);padding:0 16px;white-space:nowrap;flex-shrink:0;">Uygula</button>
        </form>
    </div>

    {{-- ── Görev Tablosu ── --}}
    <section class="card" style="padding:0;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px 10px;border-bottom:1px solid var(--u-line);">
            <span style="font-weight:700;font-size:var(--tx-sm);">Görev Listesi</span>
            <span class="muted" style="font-size:var(--tx-xs);">{{ $rows->total() }} kayıt</span>
        </div>

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
        $authId = (int) ($authUserId ?? 0);
        $isAdmin = (bool) ($isAdmin ?? false);
        $authRole = (string) ($authUserRole ?? '');
        $canApprove = $isAdmin || in_array($authRole, [\App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_SYSTEM_ADMIN], true);
        @endphp

        <div class="tl-table" style="border:none;border-radius:0;">
            <div class="tl-cols tl-head">
                <div class="tl-c tl-c-check">
                    <input type="checkbox" id="select-all" style="width:15px;height:15px;min-height:0;cursor:pointer;"
                           onchange="document.querySelectorAll('.task-select').forEach(cb=>cb.checked=this.checked);updateBulkPreview()">
                </div>
                <div class="tl-c tl-c-name">Görev</div>
                <div class="tl-c">Durum</div>
                <div class="tl-c tl-c-priority">Öncelik</div>
                <div class="tl-c tl-c-assignee">Atanan</div>
                <div class="tl-c tl-c-due">Tarih</div>
                <div class="tl-c">Etiket</div>
                <div class="tl-c"></div>
            </div>

            @forelse(($rows ?? []) as $row)
            @php
                $dueStr  = $row->due_date ? $row->due_date->format('Y-m-d') : null;
                $terminalStatus = in_array((string)$row->status, ['done','cancelled'], true);
                $isOver  = $dueStr && $dueStr < $today && !$terminalStatus;
                $isToday = $dueStr && $dueStr === $today && !$terminalStatus;
                $dueCls  = $isOver ? 'over' : ($isToday ? 'today' : '');

                $sbCls = $statusBadge[$row->status] ?? '';
                $pbCls = $priorityBadge[$row->priority] ?? '';
                $tkCls = 'tk-'.$row->priority
                    . (in_array((string)$row->status, ['done','blocked','in_review','on_hold','cancelled'], true) ? ' tk-'.$row->status : '');

                $assigneeName = $row->assignedUser?->name ?? $row->assignedUser?->email ?? '-';
                $creatorName  = $row->createdByUser?->name ?? $row->createdByUser?->email ?? '-';

                $subCount  = $row->subtasks->count();
                $subDone   = $row->subtasks->where('status','done')->count();
                $isWatching = $row->watchers->contains('user_id', $authId);
                $watcherCount = $row->watchers->count();
                $rowMentions = $row->mentioned_user_ids ?? [];
            @endphp

            {{-- ── Kompakt satır ── --}}
            <div class="tl-cols tl-row {{ $tkCls }}" id="task-{{ $row->id }}" onclick="tlToggle({{ $row->id }})">
                <div class="tl-c tl-c-check" onclick="event.stopPropagation()">
                    <input type="checkbox" class="task-select" value="{{ $row->id }}"
                           style="width:15px;height:15px;min-height:0;cursor:pointer;"
                           onchange="updateBulkPreview()">
                </div>
                <div class="tl-c tl-c-name">
                    <span class="tl-num">#{{ $row->id }}</span>
                    <span class="tl-title">{{ $row->title }}</span>
                    @if($row->is_recurring)<span title="Tekrarlayan" style="font-size:var(--tx-xs);color:var(--u-muted);">↻</span>@endif
                    @if($row->checklist_total > 0)
                        <span style="font-size:var(--tx-xs);color:var(--u-muted);flex-shrink:0;">☑ {{ $row->checklist_done }}/{{ $row->checklist_total }}</span>
                    @endif
                    @if($subCount > 0)
                        <span style="font-size:var(--tx-xs);color:var(--u-muted);flex-shrink:0;">📋 {{ $subDone }}/{{ $subCount }}</span>
                    @endif
                </div>
                <div class="tl-c"><span class="badge {{ $sbCls }}">{{ $statusOptions[$row->status] ?? $row->status }}</span></div>
                <div class="tl-c tl-c-priority"><span class="badge {{ $pbCls }}">{{ $priorityOptions[$row->priority] ?? $row->priority }}</span></div>
                <div class="tl-c tl-c-assignee">{{ $assigneeName }}</div>
                <div class="tl-c tl-c-due {{ $dueCls }}">
                    @if($dueStr){{ $isOver ? '⚠ ' : ($isToday ? '⏰ ' : '') }}{{ $row->due_date->format('d.m.Y') }}@else —@endif
                </div>
                <div class="tl-c" style="gap:4px;flex-wrap:wrap;">
                    @foreach(($rowMentions ?? []) as $uid)
                        @if(isset($mentionedUsersMap[$uid]))
                            <span style="font-size:var(--tx-xs);background:#eef4fb;border:1px solid #c4d7f0;border-radius:20px;padding:1px 6px;color:#2b5a9e;white-space:nowrap;">@{{ $mentionedUsersMap[$uid]->name }}</span>
                        @endif
                    @endforeach
                </div>
                <div class="tl-c" style="justify-content:center;" onclick="event.stopPropagation()">
                    <button class="tl-expand-btn" id="tl-btn-{{ $row->id }}" onclick="tlToggle({{ $row->id }})" title="Detay">▼</button>
                </div>
            </div>

            {{-- ── Detay satırı ── --}}
            <div class="tl-detail" id="tl-detail-{{ $row->id }}">
                <div class="tl-detail-inner">

                    {{-- Hold reason bar --}}
                    @if($row->status === 'on_hold' && $row->hold_reason)
                    <div class="tk-hold-bar">⏸ <strong>Bekleme nedeni:</strong> {{ $row->hold_reason }}</div>
                    @endif

                    {{-- Açıklama --}}
                    @if((string)$row->description !== '')
                    <div style="font-size:var(--tx-sm);color:var(--u-text);padding:8px 12px;background:var(--u-card);border-radius:6px;border:1px solid var(--u-line);margin-bottom:8px;">{{ $row->description }}</div>
                    @endif

                    {{-- Meta --}}
                    <div style="display:flex;flex-wrap:wrap;gap:12px;font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:10px;">
                        <span>👤 {{ $assigneeName }}@if($assigneeName !== $creatorName) <span style="opacity:.7;">({{ $creatorName }} oluşturdu)</span>@endif</span>
                        @if($row->depends_on_task_id)<span>🔗 Bağımlı: #{{ $row->depends_on_task_id }}</span>@endif
                        @if($row->is_recurring && $row->next_run_at)<span>↻ Sonraki: {{ $row->next_run_at->format('d.m.Y') }}</span>@endif
                        <span>👁 {{ $watcherCount }} takipçi</span>
                    </div>
                </div>

                {{-- ── State Machine Butonları ── --}}
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;padding:0 16px 12px;">
                    @if($row->status === 'todo')
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/done">@csrf
                            <button class="btn ok" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">✓ Tamamla</button>
                        </form>
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/cancel"
                              onsubmit="return confirm('İptal edilsin mi?');">@csrf
                            <button class="btn warn" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">✗ İptal</button>
                        </form>
                    @elseif($row->status === 'in_progress')
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/request-review">@csrf
                            <button class="btn ok" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">→ İncelemeye Gönder</button>
                        </form>
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/hold"
                              style="display:flex;gap:5px;align-items:center;">@csrf
                            <input type="text" name="hold_reason" placeholder="Bekleme nedeni"
                                   maxlength="255" style="font-size:var(--tx-xs);padding:4px 8px;border-radius:6px;border:1px solid var(--u-line);width:160px;height:32px;">
                            <button class="btn alt" type="submit" style="font-size:var(--tx-xs);padding:4px 10px;">⏸ Beklet</button>
                        </form>
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/cancel"
                              onsubmit="return confirm('İptal edilsin mi?');">@csrf
                            <button class="btn warn" type="submit" style="font-size:var(--tx-xs);padding:4px 10px;">✗ İptal</button>
                        </form>
                    @elseif($row->status === 'in_review')
                        @if($canApprove || ((int)$row->assigned_user_id !== $authId))
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/approve">@csrf
                            <button class="btn ok" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">✓ Onayla</button>
                        </form>
                        @endif
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/request-revision">@csrf
                            <button class="btn alt" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">↺ Revizyon İste</button>
                        </form>
                    @elseif($row->status === 'on_hold')
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/resume">@csrf
                            <button class="btn ok" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">▶ Devam Et</button>
                        </form>
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/cancel"
                              onsubmit="return confirm('İptal edilsin mi?');">@csrf
                            <button class="btn warn" type="submit" style="font-size:var(--tx-xs);padding:4px 10px;">✗ İptal</button>
                        </form>
                    @elseif($row->status === 'blocked')
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/resume">@csrf
                            <button class="btn alt" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">▶ Engel Kalktı</button>
                        </form>
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/cancel"
                              onsubmit="return confirm('İptal edilsin mi?');">@csrf
                            <button class="btn warn" type="submit" style="font-size:var(--tx-xs);padding:4px 10px;">✗ İptal</button>
                        </form>
                    @elseif(in_array($row->status, ['done','cancelled']))
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}/reopen">@csrf
                            <button class="btn alt" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">↺ Yeniden Aç</button>
                        </form>
                    @endif

                    {{-- Etiketle --}}
                    <button type="button" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;"
                            onclick="tmOpen({{ $row->id }}, {{ json_encode($row->mentioned_user_ids ?? []) }})">👥 Etiketle</button>

                    {{-- Watcher --}}
                    <button type="button"
                            class="btn alt task-watch-btn"
                            data-task-id="{{ $row->id }}"
                            data-watching="{{ $isWatching ? '1' : '0' }}"
                            style="font-size:var(--tx-xs);padding:5px 12px;"
                            onclick="toggleWatch(this)">
                        {{ $isWatching ? '👁 Takip Ediliyor' : '👁 Takip Et' }}
                        <span class="watcher-count" style="color:var(--u-muted);font-size:var(--tx-xs);">({{ $watcherCount }})</span>
                    </button>

                    @if($isAdmin || (int)$row->assigned_user_id === $authId || (int)($row->created_by_user_id ?? 0) === $authId)
                    <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}"
                          onsubmit="return confirm('Bu görev silinsin mi?');" style="margin-left:auto;">
                        @csrf @method('DELETE')
                        <button class="btn warn" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">Sil</button>
                    </form>
                    @endif
                </div>

                {{-- ── Mention chips ── --}}
                @if(!empty($rowMentions))
                <div class="tm-mentions" id="tm-mentions-{{ $row->id }}">
                    @foreach($rowMentions as $uid)
                        @if(isset($mentionedUsersMap[$uid]))
                        <span class="tm-chip" id="tm-chip-{{ $row->id }}-{{ $uid }}">
                            @{{ $mentionedUsersMap[$uid]->name }}
                            <button type="button" class="tm-chip-del" onclick="tmToggle({{ $row->id }}, {{ $uid }}, 'remove')">×</button>
                        </span>
                        @endif
                    @endforeach
                </div>
                @else
                <div class="tm-mentions" id="tm-mentions-{{ $row->id }}"></div>
                @endif

                {{-- ── Düzenle paneli ── --}}
                <details class="tk-edit-wrap">
                    <summary class="tk-edit-sum">✎ Düzenle</summary>
                    <div class="tk-edit-body">
                        <form method="POST" action="/mktg-admin/tasks/{{ $row->id }}">
                            @csrf @method('PUT')
                            <div class="tf-grid">
                                <input class="full" name="title" value="{{ $row->title }}" required>
                                <textarea class="full" name="description">{{ $row->description }}</textarea>
                                <select name="status"
                                    onchange="document.getElementById('edit-hold-{{ $row->id }}').style.display=(this.value==='on_hold'?'block':'none')">
                                    @foreach(($statusOptions ?? []) as $k => $label)
                                        <option value="{{ $k }}" @selected($row->status === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div id="edit-hold-{{ $row->id }}" style="{{ $row->status === 'on_hold' ? '' : 'display:none;' }}">
                                    <input type="text" name="hold_reason" value="{{ $row->hold_reason ?? '' }}"
                                           placeholder="Bekleme nedeni" maxlength="255">
                                </div>
                                <select name="priority">
                                    @foreach(($priorityOptions ?? []) as $k => $label)
                                        <option value="{{ $k }}" @selected($row->priority === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="date" name="start_date" value="{{ $row->start_date ? $row->start_date->format('Y-m-d') : '' }}">
                                <input type="date" name="due_date" value="{{ $row->due_date ? $row->due_date->format('Y-m-d') : '' }}">
                                <select name="assigned_user_id">
                                    <option value="">Atanan kişi yok</option>
                                    @foreach(($assignees ?? []) as $u)
                                        <option value="{{ $u->id }}" @selected((int)$row->assigned_user_id === (int)$u->id)>{{ $u->name }} ({{ $u->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            @if($row->subtasks->count() === 0)
                            <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
                                <label style="font-size:var(--tx-xs);color:var(--u-muted);flex:none;">İlerleme</label>
                                <input type="range" name="progress" min="0" max="100" value="{{ $row->progress ?? 0 }}"
                                       style="flex:1;" oninput="this.nextElementSibling.textContent=this.value+'%'">
                                <span style="font-size:var(--tx-xs);min-width:36px;">{{ $row->progress ?? 0 }}%</span>
                            </div>
                            @endif
                            <div style="display:flex;gap:8px;margin-top:10px;">
                                <button class="btn" type="submit">Güncelle</button>
                            </div>
                        </form>
                    </div>
                </details>

                {{-- ── Alt Görevler ── --}}
                @php $subDoneLocal = $row->subtasks->where('status','done')->count(); $subCountLocal = $row->subtasks->count(); @endphp
                <details class="tk-sub-wrap">
                    <summary class="tk-sub-sum">
                        📋 Alt Görevler
                        @if($subCountLocal > 0)
                        <span class="tk-sub-count {{ $subDoneLocal === $subCountLocal ? 'all-done' : '' }}" id="sub-count-{{ $row->id }}">{{ $subDoneLocal }}/{{ $subCountLocal }}</span>
                        @else
                        <span class="tk-sub-count" id="sub-count-{{ $row->id }}" style="display:none">0/0</span>
                        @endif
                    </summary>
                    <div class="tk-sub-body" data-task-id="{{ $row->id }}">
                        <div class="tk-sub-list" id="sub-list-{{ $row->id }}">
                            @foreach($row->subtasks as $sub)
                            <div class="tk-sub-item" id="sub-item-{{ $sub->id }}">
                                <input type="checkbox" class="tk-sub-check"
                                       {{ $sub->status === 'done' ? 'checked' : '' }}
                                       onchange="subtaskToggle({{ $row->id }}, {{ $sub->id }}, this)">
                                <span class="tk-sub-title {{ $sub->status === 'done' ? 'done' : '' }}" id="sub-title-{{ $sub->id }}">{{ $sub->title }}</span>
                                <button type="button" class="tk-sub-del" onclick="subtaskDelete({{ $row->id }}, {{ $sub->id }})">✕</button>
                            </div>
                            @endforeach
                        </div>
                        <form class="tk-sub-add" onsubmit="return subtaskAdd(event, {{ $row->id }})">
                            <input type="text" class="tk-sub-input" placeholder="+ Alt görev ekle…" maxlength="190">
                            <button type="submit" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;">Ekle</button>
                        </form>
                    </div>
                </details>

                {{-- ── Checklist ── --}}
                <details class="tk-sub-wrap" id="checklist-wrap-{{ $row->id }}" open>
                    <summary class="tk-sub-sum">
                        ☑ Checklist
                        @if($row->checklist_total > 0)
                            <span class="tk-sub-count {{ $row->checklist_done >= $row->checklist_total ? 'all-done' : '' }}" id="cl-count-{{ $row->id }}">{{ $row->checklist_done }}/{{ $row->checklist_total }}</span>
                        @else
                            <span class="tk-sub-count" id="cl-count-{{ $row->id }}" style="display:none">0/0</span>
                        @endif
                    </summary>
                    <div class="tk-sub-body" style="padding:6px 14px 10px;">
                        @if($row->checklist_total > 0)
                        <div style="height:4px;background:var(--u-line);border-radius:2px;margin-bottom:8px;overflow:hidden;">
                            <div class="cl-progress-bar" data-task="{{ $row->id }}"
                                 style="height:100%;background:var(--u-ok);width:{{ $row->checklist_total > 0 ? round($row->checklist_done/$row->checklist_total*100) : 0 }}%;transition:width .2s;"></div>
                        </div>
                        @endif
                        <div id="cl-list-{{ $row->id }}">
                            @foreach($row->checklists as $cl)
                            <div class="tk-sub-item cl-item" id="cl-item-{{ $cl->id }}">
                                <input type="checkbox" class="tk-sub-check cl-toggle"
                                       data-task="{{ $row->id }}" data-item="{{ $cl->id }}"
                                       {{ $cl->is_done ? 'checked' : '' }}>
                                <span class="tk-sub-title {{ $cl->is_done ? 'done' : '' }}" id="cl-title-{{ $cl->id }}">{{ $cl->title }}</span>
                                <button type="button" class="cl-del tk-sub-del" data-task="{{ $row->id }}" data-item="{{ $cl->id }}">✕</button>
                            </div>
                            @endforeach
                        </div>
                        <form class="cl-add-form" data-task="{{ $row->id }}" style="display:flex;gap:6px;margin-top:8px;">
                            @csrf
                            <input type="text" name="title" class="tk-sub-input" placeholder="+ Checklist maddesi…" maxlength="255">
                            <button type="submit" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;">Ekle</button>
                        </form>
                    </div>
                </details>

                {{-- ── Ekler ── --}}
                <details class="ta-panel" ontoggle="if(this.open) taToggle(this.querySelector('.ta-sum'))">
                    <summary class="ta-sum">📎 Ekler</summary>
                    <div class="ta-body ta-panel" data-task-id="{{ $row->id }}">
                        <div class="ta-gallery"></div>
                        <div class="ta-drop-zone"
                             ondragover="event.preventDefault();this.classList.add('ta-drag-over')"
                             ondragleave="this.classList.remove('ta-drag-over')"
                             ondrop="event.preventDefault();this.classList.remove('ta-drag-over');(async()=>{const panel=this.closest('.ta-panel');for(const f of event.dataTransfer.files)await handleFileUpload(panel.dataset.taskId,f,panel)})()">
                            🖼 Dosyayı buraya sürükle veya yapıştır (Ctrl+V)
                        </div>
                        <div class="ta-controls">
                            <label class="btn alt" style="cursor:pointer;font-size:var(--tx-xs);padding:6px 12px;">
                                📁 Dosya Seç
                                <input type="file" style="display:none;" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" multiple onchange="taFileChange(this)">
                            </label>
                            <input type="url" class="ta-link-input" placeholder="https://... link ekle">
                            <button class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" onclick="taAddLink(this)">🔗 Link Ekle</button>
                        </div>
                        <div class="ta-status"></div>
                    </div>
                </details>
            </div>{{-- /tl-detail --}}

            @empty
            <div class="muted" style="padding:24px;text-align:center;">Filtre kriterlerine uygun görev bulunamadı.</div>
            @endforelse
        </div>{{-- /tl-table --}}
        @if($rows->hasPages())
            <div style="padding:12px 16px;border-top:1px solid var(--u-line);">{{ $rows->appends(request()->query())->links() }}</div>
        @endif
    </section>
</div>{{-- /view-list --}}

{{-- ── Kanban ── --}}
<div id="view-kanban" style="display:none;margin-top:4px;">
    <div class="kanban-board" id="kanban-board">
        <div class="kanban-col" id="col-todo" data-status="todo" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title">Yapılacak</div>
        </div>
        <div class="kanban-col" id="col-in_progress" data-status="in_progress" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title">Devam Ediyor</div>
        </div>
        <div class="kanban-col" id="col-in_review" data-status="in_review" style="border-top:3px solid #7c3aed;" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title" style="background:#ede9fe;color:#4c1d95;">İncelemede</div>
        </div>
        <div class="kanban-col" id="col-blocked" data-status="blocked" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title">Bloke</div>
        </div>
        <div class="kanban-col" id="col-done" data-status="done" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title">Tamamlandı</div>
        </div>
        <div class="kanban-col" id="col-on_hold" data-status="on_hold" style="border-top:3px solid #d97706;" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title" style="background:#fef3c7;color:#92400e;">Beklemede</div>
        </div>
        <div class="kanban-col" id="col-cancelled" data-status="cancelled" style="border-top:3px solid #9ca3af;" ondragover="kanbanDragOver(event)" ondrop="kanbanDrop(event,this)">
            <div class="kanban-col-title" style="background:#f3f4f6;color:#4b5563;">İptal</div>
        </div>
    </div>
    <p class="muted" style="font-size:var(--tx-xs);margin-top:8px;">Görevi sürükle-bırak ile kolonlar arasında taşı.</p>
</div>

{{-- ── Gantt ── --}}
<div id="view-gantt" style="display:none;margin-top:4px;">
    <div id="gantt-container"><div class="gantt-empty muted">📅 Yükleniyor…</div></div>
    <p class="muted" style="font-size:var(--tx-xs);margin-top:8px;">Yalnızca bitiş tarihi olan, tamamlanmamış görevler gösterilir.</p>
</div>

{{-- ── Mention Modal ── --}}
<div id="tm-modal-bg" class="tm-modal-bg" style="display:none;" onclick="if(event.target===this)tmClose()">
    <div class="tm-modal-box">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:12px;">👥 Kullanıcı Etiketle</div>
        <input id="tm-search" class="tm-search" placeholder="İsim veya e-posta ara…" oninput="tmFilter(this.value)" autocomplete="off">
        <div id="tm-user-list" class="tm-user-list" style="max-height:320px;"></div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px;">
            <button class="btn alt" onclick="tmClose()">Kapat</button>
        </div>
    </div>
</div>

<script>
var _csrf = function() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value || '';
};

/* ── tl-table row toggle ── */
window.tlToggle = function(id) {
    var detail = document.getElementById('tl-detail-' + id);
    var btn    = document.getElementById('tl-btn-' + id);
    var row    = document.getElementById('task-' + id);
    if (!detail) return;
    var open = detail.classList.toggle('open');
    if (btn) btn.classList.toggle('open', open);
    if (row) row.classList.toggle('tl-open', open);
};

/* ── Bulk select preview ── */
window.updateBulkPreview = function() {
    var checked = Array.from(document.querySelectorAll('.task-select:checked')).map(function(cb){ return cb.value; });
    document.getElementById('bulkTaskIds').value = checked.join(',');
    document.getElementById('bulkTaskPreview').value = checked.length > 0
        ? checked.length + ' görev seçildi (#' + checked.slice(0,3).join(', #') + (checked.length > 3 ? '…' : '') + ')'
        : '';
};

/* ── Watcher toggle ── */
window.toggleWatch = function(btn) {
    var taskId = btn.dataset.taskId;
    fetch('/mktg-admin/tasks/' + taskId + '/watch', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() },
    }).then(function(r){ return r.json(); })
    .then(function(d) {
        if (!d.ok) return;
        btn.dataset.watching = d.watching ? '1' : '0';
        var countSpan = btn.querySelector('.watcher-count');
        var label = d.watching ? '👁 Takip Ediliyor' : '👁 Takip Et';
        btn.innerHTML = label + ' <span class="watcher-count" style="color:var(--u-muted);font-size:11px;">(' + d.count + ')</span>';
        btn.dataset.watching = d.watching ? '1' : '0';
    });
};

/* ── View switcher (override task-kanban.js for mktg endpoints) ── */
(function() {
    var inactive = { background: '#eef4fb', color: '#204d87' };
    var active   = { background: '#0a67d8', color: '#fff' };
    var viewBtns = ['btn-view-list','btn-view-kanban','btn-view-gantt'];

    window.switchView = function(mode) {
        var listEl   = document.getElementById('view-list');
        var kanbanEl = document.getElementById('view-kanban');
        var ganttEl  = document.getElementById('view-gantt');
        viewBtns.forEach(function(id) {
            var b = document.getElementById(id);
            if (b) { b.style.background = inactive.background; b.style.color = inactive.color; b.classList.remove('view-active'); }
        });
        [listEl, kanbanEl, ganttEl].forEach(function(el){ if (el) el.style.display = 'none'; });
        if (mode === 'kanban') {
            if (kanbanEl) kanbanEl.style.display = 'block';
            var b = document.getElementById('btn-view-kanban');
            if (b) { b.style.background = active.background; b.style.color = active.color; }
            loadKanbanData();
        } else if (mode === 'gantt') {
            if (ganttEl) ganttEl.style.display = 'block';
            var b = document.getElementById('btn-view-gantt');
            if (b) { b.style.background = active.background; b.style.color = active.color; }
            loadGanttData();
        } else {
            if (listEl) listEl.style.display = 'block';
            var b = document.getElementById('btn-view-list');
            if (b) { b.style.background = active.background; b.style.color = active.color; }
        }
        try { localStorage.setItem('mktg_task_view', mode); } catch(_) {}
    };

    var _kanbanLoaded = false;
    function loadKanbanData() {
        if (_kanbanLoaded) return;
        _kanbanLoaded = true;
        var prioMap = { urgent:'priority-urgent', high:'priority-high', normal:'priority-normal', low:'priority-low' };
        fetch('/mktg-admin/tasks/kanban', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() } })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            var cols = ['todo','in_progress','in_review','blocked','done','on_hold','cancelled'];
            cols.forEach(function(status) {
                var col = document.getElementById('col-' + status);
                if (!col) return;
                Array.from(col.children).forEach(function(ch){ if (!ch.classList.contains('kanban-col-title')) col.removeChild(ch); });
                (data[status] || []).forEach(function(t) {
                    var card = document.createElement('div');
                    card.className = 'kanban-card ' + (prioMap[t.priority] || 'priority-normal');
                    card.setAttribute('draggable','true');
                    card.dataset.id = t.id; card.dataset.status = t.status;
                    var due = t.due_date ? ' | ' + t.due_date : '';
                    var ass = t.assignee ? ' | ' + escK(t.assignee) : '';
                    card.innerHTML = '<div class="kc-title">#' + t.id + ' ' + escK(t.title) + '</div>'
                        + '<div class="kc-meta">' + escK(t.priority||'') + due + ass + '</div>';
                    var _drag = false;
                    card.addEventListener('dragstart', function(e) {
                        _drag = true; e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/plain', String(t.id));
                        setTimeout(function(){ card.style.opacity = '0.5'; }, 0);
                    });
                    card.addEventListener('dragend', function() { card.style.opacity = ''; setTimeout(function(){ _drag = false; }, 200); });
                    col.appendChild(card);
                });
            });
        }).catch(function(err){ console.warn('[Kanban] load error', err); });
    }

    window.kanbanDragOver = function(e) { e.preventDefault(); e.currentTarget.classList.add('drag-over'); };
    window.kanbanDrop = function(e, colEl) {
        e.preventDefault(); colEl.classList.remove('drag-over');
        var dragId = e.dataTransfer.getData('text/plain');
        if (!dragId) return;
        var newStatus = colEl.dataset.status;
        var dragEl = document.querySelector('.kanban-card[data-id="' + dragId + '"]');
        var newOrder = Array.from(colEl.children).filter(function(c){ return c.classList.contains('kanban-card'); }).length;
        if (dragEl) { colEl.appendChild(dragEl); dragEl.dataset.status = newStatus; }
        fetch('/mktg-admin/tasks/' + dragId + '/kanban', {
            method: 'PATCH',
            headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': _csrf() },
            body: JSON.stringify({ status: newStatus, column_order: newOrder }),
        }).then(function(r){ return r.json(); }).catch(function(){});
    };

    function escK(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    var _ganttLoaded = false;
    function loadGanttData() {
        if (_ganttLoaded) return; _ganttLoaded = true;
        fetch('/mktg-admin/tasks/gantt', { headers: { 'Accept':'application/json' } })
        .then(function(r){ return r.json(); })
        .then(function(d){ renderGantt(d); })
        .catch(function(){ document.getElementById('gantt-container').innerHTML = '<div class="gantt-empty">Yüklenemedi.</div>'; });
    }

    function renderGantt(d) {
        var tasks = d.tasks || [];
        var container = document.getElementById('gantt-container');
        if (!tasks.length) { container.innerHTML = '<div class="gantt-empty muted">Tarih atanmış tamamlanmamış görev yok.</div>'; return; }
        var startMs = new Date(d.range_start).getTime(), endMs = new Date(d.range_end).getTime();
        var todayMs = new Date(d.today).getTime(), rangeMs = endMs - startMs, DAY_MS = 86400000;
        function pct(ms){ return Math.max(0, Math.min(100, ((ms - startMs) / rangeMs) * 100)); }
        function fmt(ds){ return new Date(ds + 'T00:00:00').toLocaleDateString('tr-TR', { day:'2-digit', month:'short' }); }
        function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
        var prioColor = { urgent:'#ef4444', high:'#f59e0b', normal:'#3b82f6', low:'#94a3b8' };
        var ruler = '<div class="gantt-ruler">';
        for (var ms = startMs; ms <= endMs; ms += DAY_MS * 7)
            ruler += '<div class="gantt-ruler-tick" style="left:' + pct(ms) + '%">' + fmt(new Date(ms).toISOString().slice(0,10)) + '</div>';
        ruler += '</div>';
        var todayPct = pct(todayMs), rows = '';
        tasks.forEach(function(t) {
            var bL = pct(new Date(t.start+'T00:00:00').getTime()), bR = pct(new Date(t.end+'T00:00:00').getTime());
            var bW = Math.max(bR - bL, 0.8), color = prioColor[t.priority] || '#3b82f6';
            rows += '<div class="gantt-row"><div class="gantt-label"><span class="gantt-id">#' + t.id + '</span>' + esc(t.title) + '</div>'
                + '<div class="gantt-track"><div class="gantt-today" style="left:' + todayPct + '%"></div>'
                + '<div class="gantt-bar" style="left:' + bL + '%;width:' + bW + '%;background:' + color + '">' + esc(t.title) + '</div></div></div>';
        });
        container.innerHTML = '<div class="gantt-wrap"><div class="gantt-chart">' + ruler + rows + '</div></div>';
    }

    // Restore saved view
    document.addEventListener('DOMContentLoaded', function() {
        try { switchView(localStorage.getItem('mktg_task_view') || 'list'); }
        catch(_) { switchView('list'); }
    });
})();

/* ── Sub-tasks ── */
window.subtaskToggle = function(taskId, subId, checkbox) {
    fetch('/mktg-admin/tasks/' + taskId + '/subtasks/' + subId + '/toggle', {
        method: 'POST', headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': _csrf() },
    }).then(function(r){ return r.json(); })
    .then(function(d) {
        if (!d.ok) { checkbox.checked = !checkbox.checked; return; }
        var el = document.getElementById('sub-title-' + subId);
        if (el) el.className = 'tk-sub-title' + (d.status === 'done' ? ' done' : '');
        syncSubProgress(taskId);
    }).catch(function(){ checkbox.checked = !checkbox.checked; });
};
window.subtaskAdd = function(event, taskId) {
    event.preventDefault();
    var input = event.target.querySelector('.tk-sub-input');
    var title = (input.value || '').trim(); if (!title) return false;
    fetch('/mktg-admin/tasks/' + taskId + '/subtasks', {
        method:'POST', headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':_csrf() },
        body: JSON.stringify({ title: title }),
    }).then(function(r){ return r.json(); }).then(function(d) {
        if (!d.ok) return; input.value = '';
        var list = document.getElementById('sub-list-' + taskId); if (!list) return;
        var item = document.createElement('div');
        item.className = 'tk-sub-item'; item.id = 'sub-item-' + d.subtask.id;
        item.innerHTML = '<input type="checkbox" class="tk-sub-check" onchange="subtaskToggle(' + taskId + ',' + d.subtask.id + ',this)">'
            + '<span class="tk-sub-title" id="sub-title-' + d.subtask.id + '">' + escSub(d.subtask.title) + '</span>'
            + '<button type="button" class="tk-sub-del" onclick="subtaskDelete(' + taskId + ',' + d.subtask.id + ')">✕</button>';
        list.appendChild(item); syncSubProgress(taskId);
    }); return false;
};
window.subtaskDelete = function(taskId, subId) {
    if (!confirm('Alt görev silinsin mi?')) return;
    fetch('/mktg-admin/tasks/' + taskId + '/subtasks/' + subId, {
        method:'DELETE', headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN':_csrf() },
    }).then(function(r){ return r.json(); }).then(function(d) {
        if (!d.ok) return;
        var item = document.getElementById('sub-item-' + subId); if (item) item.remove();
        syncSubProgress(taskId);
    });
};
function syncSubProgress(taskId) {
    var list = document.getElementById('sub-list-' + taskId); if (!list) return;
    var total = list.querySelectorAll('.tk-sub-item').length;
    var done  = list.querySelectorAll('.tk-sub-check:checked').length;
    var pct   = total > 0 ? Math.round(done / total * 100) : 0;
    var countEl = document.getElementById('sub-count-' + taskId);
    if (countEl) { countEl.textContent = done + '/' + total; countEl.className = 'tk-sub-count' + (done === total && total > 0 ? ' all-done' : ''); countEl.style.display = total > 0 ? '' : 'none'; }
}
function escSub(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

/* ── Mention / Tag ── */
var _tmTaskId = null, _tmSelected = [], _tmUsers = null;
window.tmOpen = function(taskId, currentMentions) {
    _tmTaskId = taskId; _tmSelected = (currentMentions||[]).map(Number);
    document.getElementById('tm-search').value = '';
    document.getElementById('tm-modal-bg').style.display = 'flex';
    if (_tmUsers) { tmRenderList(_tmUsers); return; }
    document.getElementById('tm-user-list').innerHTML = '<div style="padding:10px;color:var(--u-muted)">Yükleniyor…</div>';
    fetch('/mktg-admin/tasks/users', { headers: { 'Accept':'application/json' } })
    .then(function(r){ return r.json(); })
    .then(function(d){ _tmUsers = d.users || []; tmRenderList(_tmUsers); })
    .catch(function(){ document.getElementById('tm-user-list').innerHTML = '<div style="padding:10px;color:red">Yüklenemedi</div>'; });
};
window.tmClose  = function() { document.getElementById('tm-modal-bg').style.display = 'none'; };
window.tmFilter = function(q) { if (!_tmUsers) return; tmRenderList(_tmUsers.filter(function(u){ return !q || u.name.toLowerCase().includes(q.toLowerCase()) || u.email.toLowerCase().includes(q.toLowerCase()); })); };
function tmRenderList(users) {
    var html = '';
    users.forEach(function(u) {
        var sel = _tmSelected.includes(Number(u.id));
        html += '<div class="tm-user-item' + (sel?' tm-sel':'') + '" onclick="tmToggle(' + _tmTaskId + ',' + u.id + ',\'' + (sel?'remove':'add') + '\')">'
            + '<span style="width:28px;height:28px;border-radius:50%;background:#dce8f7;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex:none;">' + escT(u.name.charAt(0).toUpperCase()) + '</span>'
            + '<div><div>' + escT(u.name) + (sel?' ✓':'') + '</div><div class="tm-role">' + escT(u.email) + ' · ' + escT(u.role||'') + '</div></div></div>';
    });
    document.getElementById('tm-user-list').innerHTML = html || '<div style="padding:10px;color:var(--u-muted)">Kullanıcı bulunamadı</div>';
}
window.tmToggle = function(taskId, userId, action) {
    fetch('/mktg-admin/tasks/' + taskId + '/mention', {
        method:'POST', headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':_csrf() },
        body: JSON.stringify({ user_id: userId, action: action }),
    }).then(function(r){ return r.json(); }).then(function(d) {
        if (!d.ok) return;
        if (action === 'add') { if (!_tmSelected.includes(Number(userId))) _tmSelected.push(Number(userId)); }
        else { _tmSelected = _tmSelected.filter(function(id){ return id !== Number(userId); }); }
        if (_tmUsers) tmFilter(document.getElementById('tm-search').value);
        var mentionsEl = document.getElementById('tm-mentions-' + taskId);
        if (mentionsEl) {
            var chipId = 'tm-chip-' + taskId + '-' + userId;
            if (action === 'add') {
                var user = (_tmUsers||[]).find(function(u){ return Number(u.id)===Number(userId); });
                if (user && !document.getElementById(chipId)) {
                    var chip = document.createElement('span');
                    chip.className = 'tm-chip'; chip.id = chipId;
                    chip.innerHTML = '@' + escT(user.name) + '<button type="button" class="tm-chip-del" onclick="tmToggle(' + taskId + ',' + userId + ',\'remove\')">×</button>';
                    mentionsEl.appendChild(chip);
                }
            } else { var ex = document.getElementById(chipId); if (ex) ex.remove(); }
        }
    });
};
function escT(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

/* ── Checklist ── */
function clCsrf(){ return _csrf(); }
function clUpdateProgress(taskId, progress, done, total) {
    var bar = document.querySelector('.cl-progress-bar[data-task="' + taskId + '"]');
    if (bar) bar.style.width = progress + '%';
    var countEl = document.getElementById('cl-count-' + taskId);
    if (countEl) { countEl.textContent = done + '/' + total; countEl.style.display = total > 0 ? '' : 'none'; countEl.className = 'tk-sub-count' + (done >= total && total > 0 ? ' all-done' : ''); }
}
document.querySelectorAll('.cl-toggle').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var taskId = this.dataset.task, itemId = this.dataset.item;
        var titleEl = document.getElementById('cl-title-' + itemId);
        if (titleEl) titleEl.classList.toggle('done', this.checked);
        fetch('/mktg-admin/tasks/' + taskId + '/checklist/' + itemId + '/toggle', {
            method:'PATCH', headers:{ 'X-CSRF-TOKEN':clCsrf() }
        }).then(function(r){ return r.json(); }).then(function(d){ if (d.progress!==undefined) clUpdateProgress(taskId, d.progress, d.done, d.total); });
    });
});
document.querySelectorAll('.cl-del').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (!confirm('Checklist maddesi silinsin mi?')) return;
        var taskId = this.dataset.task, itemId = this.dataset.item;
        var row = document.getElementById('cl-item-' + itemId);
        fetch('/mktg-admin/tasks/' + taskId + '/checklist/' + itemId, {
            method:'DELETE', headers:{ 'X-CSRF-TOKEN':clCsrf() }
        }).then(function(r){ return r.json(); }).then(function(d){ if (row) row.remove(); if (d.progress!==undefined) clUpdateProgress(taskId, d.progress, d.done, d.total); });
    });
});
document.querySelectorAll('.cl-add-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var taskId = this.dataset.task;
        var input = this.querySelector('input[name="title"]');
        var title = input ? input.value.trim() : ''; if (!title) return;
        var btn = this.querySelector('button[type="submit"]'); if (btn) btn.disabled = true;
        fetch('/mktg-admin/tasks/' + taskId + '/checklist', {
            method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':clCsrf() },
            body: JSON.stringify({ title: title })
        }).then(function(r){ return r.json(); }).then(function(d) {
            if (d.id) {
                var list = document.getElementById('cl-list-' + taskId);
                if (list) {
                    var div = document.createElement('div');
                    div.className = 'tk-sub-item cl-item'; div.id = 'cl-item-' + d.id;
                    div.innerHTML = '<input type="checkbox" class="tk-sub-check cl-toggle" data-task="' + taskId + '" data-item="' + d.id + '">'
                        + '<span class="tk-sub-title" id="cl-title-' + d.id + '">' + escSub(d.title) + '</span>'
                        + '<button type="button" class="cl-del tk-sub-del" data-task="' + taskId + '" data-item="' + d.id + '">✕</button>';
                    list.appendChild(div);
                    div.querySelector('.cl-toggle').addEventListener('change', function() {
                        var tid = this.dataset.task, iid = this.dataset.item;
                        var tEl = document.getElementById('cl-title-' + iid);
                        if (tEl) tEl.classList.toggle('done', this.checked);
                        fetch('/mktg-admin/tasks/' + tid + '/checklist/' + iid + '/toggle', {
                            method:'PATCH', headers:{'X-CSRF-TOKEN':clCsrf()}
                        }).then(function(r){ return r.json(); }).then(function(r2){ if (r2.progress!==undefined) clUpdateProgress(tid, r2.progress, r2.done, r2.total); });
                    });
                    div.querySelector('.cl-del').addEventListener('click', function() {
                        if (!confirm('Silinsin mi?')) return;
                        var tid = this.dataset.task, iid = this.dataset.item;
                        fetch('/mktg-admin/tasks/' + tid + '/checklist/' + iid, {
                            method:'DELETE', headers:{'X-CSRF-TOKEN':clCsrf()}
                        }).then(function(r){ return r.json(); }).then(function(r2){ div.remove(); if (r2.progress!==undefined) clUpdateProgress(tid, r2.progress, r2.done, r2.total); });
                    });
                }
                if (d.progress!==undefined) clUpdateProgress(taskId, d.progress, 0, d.total||0);
                if (input) input.value = '';
            } else { alert(d.error || 'Eklenemedi.'); }
        }).catch(function(){ alert('Bağlantı hatası.'); }).finally(function(){ if (btn) btn.disabled = false; });
    });
});
</script>

<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Pazarlama Görevleri</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📋 Görev Yönetimi</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li><strong>todo → in_progress → done</strong> akışını takip et</li>
                <li>Öncelik: critical &gt; high &gt; medium &gt; low</li>
                <li>Atanmamış görevler ekip verimliliğini düşürür</li>
                <li>Geciken görevler kırmızıyla işaretlenir</li>
            </ul>
        </div>
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🗓 Kanban Görünümü</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Tablo ↔ Kanban görünümünü üst bar'dan değiştir</li>
                <li>Kanban: sütunlar arası sürükle-bırak ile durum güncelle</li>
                <li>Filtrele: kişi, durum, öncelik, kampanya, tarih</li>
                <li>Etikete tıkla → o etiketteki tüm görevleri filtrele</li>
            </ul>
        </div>
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">⏱ Zaman & Raporlama</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Görev detayında zamanlayıcıyı başlat/durdur → harcanan süreyi izle</li>
                <li>Tekrar eden görevler → recurrence ayarla (günlük/haftalık/aylık)</li>
                <li>Checklist ile alt adımları takip et, ilerleme çubuğu otomatik güncellenir</li>
                <li>Rapor menüsünden dönemsel görev tamamlanma istatistiklerini gör</li>
            </ul>
        </div>
    </div>
</details>
@endsection

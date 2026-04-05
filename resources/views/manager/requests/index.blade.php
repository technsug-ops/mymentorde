@php
    $reqLayout = match(true) {
        in_array(auth()->user()?->role, ['senior','mentor'])                                       => 'senior.layouts.app',
        in_array(auth()->user()?->role, ['marketing_admin','marketing_staff','sales_admin','sales_staff']) => 'layouts.staff',
        default => 'manager.layouts.app',
    };
@endphp
@extends($reqLayout)

@section('title', 'Manager – Request Center')
@section('page_title', 'Manager\'a Talep')

@push('head')
<style>
.mgr-kpi-strip { display:grid; grid-template-columns:repeat(5,1fr); gap:8px; margin-bottom:12px; }
@media(max-width:900px){ .mgr-kpi-strip { grid-template-columns:1fr 1fr 1fr; } }
@media(max-width:600px){ .mgr-kpi-strip { grid-template-columns:1fr 1fr; } }
/* CSS var bridge: manager layout → premium.css */
:root { --surface:var(--u-card,#fff); --border:var(--u-line,#e2e8f0); --text:var(--u-text,#0f172a); --muted:var(--u-muted,#64748b); --bg:var(--u-bg,#f8fafc); }
.mgr-kpi { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-top:3px solid #1e40af; border-radius:10px; padding:12px 14px; }
.mgr-kpi-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.mgr-kpi-val   { font-size:22px; font-weight:800; color:var(--text,#0f172a); line-height:1; }
.mgr-filter-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }
/* Form elemanları */
.mgr-req-form input, .mgr-req-form select, .mgr-req-form textarea {
    width:100%; box-sizing:border-box; padding:6px 10px; height:34px;
    border:1px solid var(--border,#e2e8f0); border-radius:7px;
    background:var(--surface,#fff); color:var(--text,#0f172a);
    font-size:13px; outline:none; appearance:auto;
}
.mgr-req-form textarea { height:72px; padding:8px 10px; resize:vertical; }
.mgr-req-form input:focus, .mgr-req-form select:focus, .mgr-req-form textarea:focus { border-color:#1e40af; box-shadow:0 0 0 2px rgba(30,64,175,.08); }
/* Request kart */
.req-card { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-left:3px solid #1e40af; border-radius:10px; padding:14px 16px; margin-bottom:8px; }
.req-card.urgent { border-left-color:#dc2626; }
.req-card.in_review { border-left-color:#d97706; }
.req-card.done { border-left-color:#16a34a; }
.req-head { display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:flex-start; margin-bottom:6px; }
.req-title { font-size:14px; font-weight:700; color:var(--text,#0f172a); }
.req-chips { display:flex; gap:5px; flex-wrap:wrap; align-items:center; }
.req-meta  { font-size:11px; color:var(--muted,#64748b); line-height:1.7; }
</style>
@endpush

@section('content')

{{-- KPI Strip --}}
@php
    $total   = collect($rows ?? [])->count();
    $open    = collect($rows ?? [])->where('status', 'open')->count();
    $review  = collect($rows ?? [])->where('status', 'in_review')->count();
    $done    = collect($rows ?? [])->whereIn('status', ['approved','rejected','done'])->count();
    $urgent  = collect($rows ?? [])->where('priority', 'urgent')->count();
@endphp
<div class="mgr-kpi-strip">
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Toplam</div>
        <div class="mgr-kpi-val">{{ $total }}</div>
    </div>
    <div class="mgr-kpi" style="{{ $open > 0 ? 'border-top-color:#1e40af;' : '' }}">
        <div class="mgr-kpi-label">Açık</div>
        <div class="mgr-kpi-val" style="{{ $open > 0 ? 'color:#1e40af;' : '' }}">{{ $open }}</div>
    </div>
    <div class="mgr-kpi" style="{{ $review > 0 ? 'border-top-color:#d97706;' : '' }}">
        <div class="mgr-kpi-label">İncelemede</div>
        <div class="mgr-kpi-val" style="{{ $review > 0 ? 'color:#b45309;' : '' }}">{{ $review }}</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:#16a34a;">
        <div class="mgr-kpi-label">Tamamlanan</div>
        <div class="mgr-kpi-val" style="color:#15803d;">{{ $done }}</div>
    </div>
    <div class="mgr-kpi" style="{{ $urgent > 0 ? 'border-top-color:#dc2626;' : '' }}">
        <div class="mgr-kpi-label">Acil</div>
        <div class="mgr-kpi-val" style="{{ $urgent > 0 ? 'color:#dc2626;' : '' }}">{{ $urgent }}</div>
    </div>
</div>

{{-- Üst bağlantılar --}}
<div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
    <a href="/tasks" style="padding:6px 14px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:var(--tx-xs);font-weight:600;color:var(--muted,#64748b);text-decoration:none;background:var(--surface,#fff);">Task Board →</a>
    <a href="/tickets-center" style="padding:6px 14px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:var(--tx-xs);font-weight:600;color:var(--muted,#64748b);text-decoration:none;background:var(--surface,#fff);">Ticket Center →</a>
</div>

{{-- Yeni Talep / Manager bilgi --}}
@if(!($isManager ?? false))
<section class="card" style="margin-bottom:12px;padding:16px 18px;">
    <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">Yeni Talep Oluştur</div>
    <form method="POST" action="/manager/requests" class="mgr-req-form">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;margin-bottom:8px;">
            <div style="display:flex;flex-direction:column;gap:3px;">
                <label class="mgr-filter-label">Tip</label>
                <select name="request_type" required>
                    @foreach(($typeOptions ?? []) as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:3px;">
                <label class="mgr-filter-label">Öncelik</label>
                <select name="priority">
                    @foreach(($priorityOptions ?? []) as $k => $v)
                        <option value="{{ $k }}" @selected($k==='normal')>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:3px;">
                <label class="mgr-filter-label">Termin</label>
                <input type="date" name="due_date">
            </div>
            <div style="display:flex;flex-direction:column;gap:3px;">
                <label class="mgr-filter-label">Hedef Manager</label>
                <select name="target_manager_id">
                    <option value="">Otomatik</option>
                    @foreach(($managers ?? []) as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <input name="subject" placeholder="Talep başlığı *" required style="width:100%;max-width:480px;margin-bottom:6px;">
        <textarea name="description" placeholder="Açıklama (opsiyonel)" style="width:100%;max-width:600px;height:72px;margin-bottom:8px;display:block;"></textarea>
        <button type="submit" style="padding:7px 18px;background:#1e40af;color:#fff;border:none;border-radius:7px;font-size:var(--tx-sm);font-weight:600;cursor:pointer;">Talep Oluştur</button>
    </form>
</section>
@else
<div style="padding:10px 14px;margin-bottom:12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;font-size:var(--tx-xs);color:#1e40af;">
    Bu ekran alt rollerden gelen talepleri yönetmek içindir. Manager yeni iş açacaksa <strong>Task Board</strong> üzerinden görev oluşturmalıdır.
</div>
@endif

{{-- Filtre --}}
<section class="card" style="margin-bottom:12px;padding:16px 18px;">
    <form method="GET" action="/manager/requests" class="mgr-req-form" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Durum</label>
            <select name="status">
                <option value="">Tümü</option>
                @foreach(($statusOptions ?? []) as $k => $v)
                    <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Öncelik</label>
            <select name="priority">
                <option value="">Tümü</option>
                @foreach(($priorityOptions ?? []) as $k => $v)
                    <option value="{{ $k }}" @selected(($filters['priority'] ?? '') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Tip</label>
            <select name="type">
                <option value="">Tümü</option>
                @foreach(($typeOptions ?? []) as $k => $v)
                    <option value="{{ $k }}" @selected(($filters['type'] ?? '') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" style="padding:6px 16px;background:#1e40af;color:#fff;border:none;border-radius:7px;font-size:var(--tx-xs);font-weight:600;cursor:pointer;">Filtrele</button>
            <a href="/manager/requests" style="padding:6px 12px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:var(--tx-xs);color:var(--muted,#64748b);text-decoration:none;background:var(--surface,#fff);">Temizle</a>
        </div>
    </form>
</section>

{{-- Talep Listesi --}}
@forelse(($rows ?? []) as $row)
    @php
        $linkedTask = ($taskMap ?? collect())->get((string) $row->id);
        $suggestedDept = match ((string)($row->request_type ?? 'general')) {
            'finance'    => 'finance',
            'operations' => 'operations',
            'approval'   => 'advisory',
            'system'     => 'system',
            'marketing'  => 'marketing',
            default      => 'operations',
        };
        $taskLink = '/tasks/'.($linkedTask?->department ?: $suggestedDept);

        $statusBadge = match((string)($row->status ?? '')) {
            'open'     => 'info',
            'in_review'=> 'warn',
            'approved' => 'ok',
            'done'     => 'ok',
            'rejected' => 'danger',
            default    => 'pending',
        };
        $priorBadge = match((string)($row->priority ?? '')) {
            'urgent'   => 'danger',
            'high'     => 'warn',
            'normal'   => 'pending',
            'low'      => 'info',
            default    => 'pending',
        };
        $cardClass = match((string)($row->status ?? '')) {
            'in_review'                    => 'in_review',
            'approved','done'              => 'done',
            'rejected'                     => 'done',
            default                        => (($row->priority ?? '') === 'urgent' ? 'urgent' : ''),
        };
    @endphp
    <article class="req-card {{ $cardClass }}">
        <div class="req-head">
            <div>
                <div class="req-title">#{{ $row->id }} — {{ $row->subject }}</div>
                <div class="req-chips" style="margin-top:5px;">
                    <span class="badge {{ $statusBadge }}">{{ $statusOptions[$row->status] ?? $row->status }}</span>
                    <span class="badge {{ $priorBadge }}">{{ $priorityOptions[$row->priority] ?? $row->priority }}</span>
                    <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);padding:2px 8px;border:1px solid var(--border,#e2e8f0);border-radius:999px;background:var(--bg,#f8fafc);">{{ $typeOptions[$row->request_type] ?? $row->request_type }}</span>
                    @if($linkedTask)
                        <span style="font-size:var(--tx-xs);color:#1e40af;padding:2px 8px;border:1px solid rgba(30,64,175,.3);border-radius:999px;background:rgba(30,64,175,.06);">Task #{{ $linkedTask->id }} · {{ $linkedTask->status }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="req-meta">
            İsteyen: <strong>{{ $row->requester?->email ?? '–' }}</strong> &nbsp;·&nbsp;
            Manager: {{ $row->manager?->email ?? '–' }} &nbsp;·&nbsp;
            Termin: {{ $row->due_date ? $row->due_date->format('d.m.Y') : '–' }}
        </div>
        <div class="req-meta">
            Açıldı: {{ optional($row->requested_at)->format('d.m.Y H:i') ?? '–' }}
            @if($row->responded_at) &rarr; İnceleme: {{ optional($row->responded_at)->format('d.m.Y H:i') }} @endif
            @if($row->resolved_at) &rarr; Kapandı: {{ optional($row->resolved_at)->format('d.m.Y H:i') }} @endif
        </div>

        @if((string)$row->description !== '')
            <div style="margin-top:6px;font-size:var(--tx-sm);color:var(--text,#0f172a);background:var(--bg,#f8fafc);border-radius:6px;padding:8px 10px;">{{ $row->description }}</div>
        @endif
        @if((string)$row->decision_note !== '')
            <div style="margin-top:5px;font-size:var(--tx-xs);color:var(--muted,#64748b);font-style:italic;">Karar notu: {{ $row->decision_note }}</div>
        @endif

        <div style="margin-top:8px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <a href="{{ $taskLink }}" style="font-size:var(--tx-xs);font-weight:600;color:#1e40af;text-decoration:none;">
                {{ $linkedTask ? '→ Task kuyruğuna git' : '→ Departman task kuyruğu: '.$suggestedDept }}
            </a>
        </div>

        @if($isManager ?? false)
        <form method="POST" action="/manager/requests/{{ $row->id }}/status" class="mgr-req-form" style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border,#e2e8f0);">
            @csrf
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
                <div style="display:flex;flex-direction:column;gap:3px;">
                    <label class="mgr-filter-label">Durum Güncelle</label>
                    <select name="status">
                        @foreach(($statusOptions ?? []) as $k => $v)
                            <option value="{{ $k }}" @selected((string)$row->status === (string)$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:3px;flex:1;min-width:180px;">
                    <label class="mgr-filter-label">Karar Notu</label>
                    <input name="decision_note" value="{{ $row->decision_note }}" placeholder="Opsiyonel not...">
                </div>
                <button type="submit" style="padding:6px 14px;background:#1e40af;color:#fff;border:none;border-radius:7px;font-size:var(--tx-xs);font-weight:600;cursor:pointer;">Güncelle</button>
            </div>
        </form>
        @endif
    </article>
@empty
    <div style="padding:32px;text-align:center;color:var(--muted,#64748b);background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;">Talep kaydı bulunamadı.</div>
@endforelse

@endsection

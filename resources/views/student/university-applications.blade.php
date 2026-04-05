@extends('student.layouts.app')

@section('title', 'Üniversite Başvurularım')
@section('page_title', 'Üniversite Başvurularım')

@push('head')
<style>
/* ── ua-* University Applications scoped ── */

/* Stats strip */
.ua-stats {
    display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px;
}
@media(max-width:700px){ .ua-stats { grid-template-columns: 1fr 1fr; } }
.ua-stat {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 14px 16px;
    border-left: 4px solid var(--u-line);
}
.ua-stat.s-all    { border-left-color: var(--u-brand); }
.ua-stat.s-ok     { border-left-color: #16a34a; }
.ua-stat.s-pend   { border-left-color: #d97706; }
.ua-stat.s-rej    { border-left-color: #dc2626; }
.ua-stat-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--u-muted); margin-bottom: 6px; }
.ua-stat-val { font-size: 28px; font-weight: 800; line-height: 1; color: var(--u-text); }
.ua-stat.s-ok  .ua-stat-val { color: #16a34a; }
.ua-stat.s-pend .ua-stat-val { color: #d97706; }
.ua-stat.s-rej .ua-stat-val { color: #dc2626; }

/* Section header */
.ua-section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; gap: 8px;
}
.ua-section-title { font-size: 15px; font-weight: 700; color: var(--u-text); }

/* Application cards grid */
.ua-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 12px; margin-bottom: 24px; }
@media(max-width:780px){ .ua-grid { grid-template-columns: 1fr; } }

.ua-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; overflow: hidden; display: flex; flex-direction: column;
    transition: box-shadow .15s, border-color .15s;
}
.ua-card:hover { border-color: var(--u-brand); box-shadow: 0 4px 16px rgba(124,58,237,.08); }

.ua-card-head {
    padding: 14px 16px 10px; display: flex; align-items: flex-start;
    gap: 12px; border-bottom: 1px solid var(--u-line);
}
.ua-priority-badge {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    background: rgba(124,58,237,.1); border: 1px solid rgba(124,58,237,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800; color: #7c3aed;
}
.ua-uni-name {
    font-size: 14px; font-weight: 800; color: var(--u-text); line-height: 1.2; margin-bottom: 3px;
}
.ua-uni-loc  { font-size: 12px; color: var(--u-muted); }
.ua-card-body { padding: 12px 16px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
.ua-dept { font-size: 13px; font-weight: 600; color: var(--u-text); }
.ua-meta-row { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.ua-chip {
    display: inline-flex; align-items: center; padding: 3px 8px;
    background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 999px; font-size: 11px; font-weight: 600; color: var(--u-muted);
}
.ua-card-foot {
    padding: 10px 16px; border-top: 1px solid var(--u-line);
    background: var(--u-bg); display: flex; flex-wrap: wrap;
    gap: 8px; align-items: center; font-size: 11px; color: var(--u-muted);
}
.ua-date-item strong { color: var(--u-text); }

/* Empty state */
.ua-empty {
    text-align: center; padding: 36px 20px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; color: var(--u-muted); font-size: 13px;
    margin-bottom: 20px;
}

/* Institution docs */
.ua-docs-wrap { margin-bottom: 20px; }
.ua-cat-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: var(--u-muted); margin: 16px 0 6px;
}
.ua-cat-label:first-child { margin-top: 0; }

.ua-doc-list {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; overflow: hidden;
}
.ua-doc-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-bottom: 1px solid var(--u-line);
}
.ua-doc-item:last-child { border-bottom: none; }
.ua-doc-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    background: var(--u-bg); border: 1px solid var(--u-line);
    display: flex; align-items: center; justify-content: center; font-size: 15px;
}
.ua-doc-info { flex: 1; min-width: 0; }
.ua-doc-name { font-size: 13px; font-weight: 600; color: var(--u-text); }
.ua-doc-sub  { font-size: 11px; color: var(--u-muted); margin-top: 2px; }
.ua-doc-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.ua-dl-btn {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 6px; font-size: 11px; font-weight: 600; color: var(--u-text);
    text-decoration: none; transition: border-color .15s;
}
.ua-dl-btn:hover { border-color: var(--u-brand); color: var(--u-brand); }
</style>
@endpush

@section('content')
@php
    $statusLabels = \App\Models\StudentUniversityApplication::STATUS_LABELS;
    $statusBadge  = \App\Models\StudentUniversityApplication::STATUS_BADGE;
    $degreeLabels = \App\Models\StudentUniversityApplication::DEGREE_LABELS;
    $portalLabels = \App\Models\StudentUniversityApplication::PORTAL_LABELS;

    $apps     = $applications ?? collect();
    $total    = $apps->count();
    $accepted = $apps->whereIn('status', ['accepted','conditional_accepted'])->count();
    $pending  = $apps->whereIn('status', ['planned','submitted','under_review'])->count();
    $rejected = $apps->where('status','rejected')->count();
    $instDocs = $institutionDocs ?? collect();
@endphp

{{-- ── STATS ── --}}
<div class="ua-stats">
    <div class="ua-stat s-all">
        <div class="ua-stat-lbl">Toplam Başvuru</div>
        <div class="ua-stat-val">{{ $total }}</div>
    </div>
    <div class="ua-stat s-ok">
        <div class="ua-stat-lbl">Kabul</div>
        <div class="ua-stat-val">{{ $accepted }}</div>
    </div>
    <div class="ua-stat s-pend">
        <div class="ua-stat-lbl">Bekleyen</div>
        <div class="ua-stat-val">{{ $pending }}</div>
    </div>
    <div class="ua-stat s-rej">
        <div class="ua-stat-lbl">Ret</div>
        <div class="ua-stat-val">{{ $rejected }}</div>
    </div>
</div>

{{-- ── UNİVERSİTE BAŞVURULARI ── --}}
<div class="ua-section-head">
    <div class="ua-section-title">🎓 Üniversite Başvurularım</div>
    @if($total > 0)
        <span class="badge">{{ $total }} başvuru</span>
    @endif
</div>

@if($apps->isEmpty())
<div class="ua-empty">
    🎓 Henüz üniversite başvurusu eklenmedi.<br>
    <span style="font-size:var(--tx-xs);">Danışmanınız başvurularınızı buraya ekleyecektir.</span>
</div>
@else
<div class="ua-grid">
    @foreach($apps->sortBy('priority') as $app)
    @php
        $badgeClass  = $statusBadge[$app->status] ?? 'info';
        $statusLabel = $statusLabels[$app->status] ?? $app->status;
        $degreeLabel = $degreeLabels[$app->degree_type] ?? $app->degree_type;
    @endphp
    <div class="ua-card">
        <div class="ua-card-head">
            <div class="ua-priority-badge">#{{ $app->priority }}</div>
            <div style="flex:1;min-width:0;">
                <div class="ua-uni-name">{{ $app->university_name }}</div>
                @if($app->city)
                <div class="ua-uni-loc">📍 {{ $app->city }}{{ $app->state ? ', '.$app->state : '' }}</div>
                @endif
            </div>
            <span class="badge {{ $badgeClass }}" style="flex-shrink:0;">{{ $statusLabel }}</span>
        </div>
        <div class="ua-card-body">
            <div class="ua-dept">{{ $app->department_name }}</div>
            <div class="ua-meta-row">
                <span class="ua-chip">{{ $degreeLabel }}</span>
                @if($app->semester)<span class="ua-chip">{{ $app->semester }}</span>@endif
                @if($app->application_portal)<span class="ua-chip">{{ $portalLabels[$app->application_portal] ?? $app->application_portal }}</span>@endif
                @if($app->application_number)<span class="ua-chip">Ref: {{ $app->application_number }}</span>@endif
            </div>
            @if($app->notes)
            <div style="font-size:var(--tx-xs);color:var(--u-muted);font-style:italic;border-left:2px solid var(--u-line);padding-left:8px;">
                {{ $app->notes }}
            </div>
            @endif
        </div>
        @if($app->deadline || $app->submitted_at || $app->result_at)
        <div class="ua-card-foot">
            @if($app->deadline)
                <span class="ua-date-item">Son tarih: <strong>{{ $app->deadline->format('d.m.Y') }}</strong></span>
            @endif
            @if($app->submitted_at)
                <span class="ua-date-item">Gönderildi: <strong>{{ $app->submitted_at->format('d.m.Y') }}</strong></span>
            @endif
            @if($app->result_at)
                <span class="ua-date-item">Sonuç: <strong>{{ $app->result_at->format('d.m.Y') }}</strong></span>
            @endif
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- ── KURUMDAN GELEN BELGELER ── --}}
<div class="ua-section-head" style="margin-top:4px;">
    <div class="ua-section-title">📂 Kurumdan Gelen Belgeler</div>
    <span class="badge">{{ $instDocs->count() }}</span>
</div>

@if($instDocs->isEmpty())
<div class="ua-empty">
    📂 Danışmanınız tarafından henüz belge paylaşılmadı.
</div>
@else
<div class="ua-docs-wrap">
    @php $docGrouped = $instDocs->groupBy('institution_category'); @endphp
    @foreach($docGrouped as $catKey => $catDocs)
    @php
        $catInfo  = ($catalog ?? [])[$catKey] ?? [];
        $catLabel = ($catInfo['icon'] ?? '📁') . ' ' . ($catInfo['label_tr'] ?? $catKey);
    @endphp
    <div class="ua-cat-label">{{ $catLabel }}</div>
    <div class="ua-doc-list">
        @foreach($catDocs as $doc)
        @php
            $docBadge  = match($doc->status) { 'received','completed'=>'ok', 'action_required'=>'warn', 'expected'=>'info', default=>'pending' };
            $docStatus = ['expected'=>'Bekleniyor','received'=>'Alındı','action_required'=>'Aksiyon Gerekli','completed'=>'Tamamlandı','archived'=>'Arşivlendi'][$doc->status] ?? $doc->status;
            $docIcons  = ['expected'=>'⏳','received'=>'📄','action_required'=>'⚠️','completed'=>'✅','archived'=>'📦'];
        @endphp
        <div class="ua-doc-item">
            <div class="ua-doc-icon">{{ $docIcons[$doc->status] ?? '📄' }}</div>
            <div class="ua-doc-info">
                <div class="ua-doc-name">{{ $doc->document_type_label }}</div>
                <div class="ua-doc-sub">
                    @if($doc->institution_name){{ $doc->institution_name }}@endif
                    @if($doc->received_date) · {{ $doc->received_date->format('d.m.Y') }}@endif
                    @if($doc->notes) · {{ $doc->notes }}@endif
                </div>
            </div>
            <div class="ua-doc-actions">
                <span class="badge {{ $docBadge }}">{{ $docStatus }}</span>
                @if($doc->file_id && $doc->file)
                <a class="ua-dl-btn" href="{{ Storage::url($doc->file->storage_path ?? '') }}" target="_blank">
                    ⬇ İndir
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endforeach
</div>
@endif

@endsection

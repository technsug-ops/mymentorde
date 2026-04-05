@extends('senior.layouts.app')

@section('title', 'Toplu Belge İnceleme')
@section('page_title', 'Toplu Belge İnceleme')

@push('head')
<style>
.br-stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
.br-stat  { flex:1; min-width:90px; background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e7eb); border-radius:12px; padding:12px 16px; text-align:center; }
.br-stat-val { font-size:24px; font-weight:700; line-height:1; }
.br-stat-lbl { font-size:11px; color:#9ca3af; margin-top:3px; }

.br-doc-row { display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border:1px solid var(--u-line,#e5e7eb); border-radius:10px; background:var(--u-card,#fff); margin-bottom:6px; transition:background .15s; }
.br-doc-row.focused { background:#f5f3ff; border-color:#c4b5fd; }
.br-doc-row.approved { opacity:.6; }
.br-doc-row.rejected { opacity:.6; background:#fff1f1; }
.br-doc-info { flex:1; min-width:0; }
.br-doc-name { font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.br-doc-meta { font-size:11px; color:#9ca3af; margin-top:3px; }
.br-actions { display:flex; gap:6px; flex-shrink:0; align-items:center; }
.br-key-hint { background:#f3f4f6; border-radius:6px; padding:6px 12px; font-size:12px; color:#6b7280; }
</style>
@endpush

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <h2 style="margin:0;">📋 Toplu Belge İnceleme</h2>
    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
        <span class="br-key-hint">⌨️ A=Onayla · R=Reddet · N=Not · ↑↓ Gezin</span>
        @foreach(['uploaded' => 'Bekleyenler', 'approved' => 'Onaylananlar', 'rejected' => 'Reddedilenler', 'all' => 'Tümü'] as $s => $lbl)
        <a class="btn {{ $statusFilter === $s ? 'ok' : 'alt' }}" href="/senior/batch-review?status={{ $s }}">{{ $lbl }}</a>
        @endforeach
    </div>
</div>

{{-- Stats --}}
<div class="br-stats">
    <div class="br-stat">
        <div class="br-stat-val" style="color:#f59e0b;">{{ $stats['uploaded'] }}</div>
        <div class="br-stat-lbl">Bekleyen</div>
    </div>
    <div class="br-stat">
        <div class="br-stat-val" style="color:#16a34a;">{{ $stats['approved'] }}</div>
        <div class="br-stat-lbl">Onaylanan</div>
    </div>
    <div class="br-stat">
        <div class="br-stat-val" style="color:#dc2626;">{{ $stats['rejected'] }}</div>
        <div class="br-stat-lbl">Reddedilen</div>
    </div>
    <div class="br-stat">
        <div class="br-stat-val" style="color:#6b7280;">{{ $grouped->sum(fn($g) => $g->count()) }}</div>
        <div class="br-stat-lbl">Gösterilen</div>
    </div>
</div>

@if($grouped->isEmpty())
<div class="panel muted">Bu filtrede belge bulunamadı.</div>
@else

{{-- Note modal --}}
<div id="br-note-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:420px;box-shadow:0 8px 32px rgba(0,0,0,.2);">
        <h3 style="margin:0 0 12px;">Not Ekle</h3>
        <textarea id="br-note-text" rows="4" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px;font-size:var(--tx-sm);resize:vertical;" placeholder="İnceleme notu..."></textarea>
        <div style="display:flex;gap:8px;margin-top:12px;justify-content:flex-end;">
            <button class="btn" onclick="brNoteClose()">İptal</button>
            <button class="btn ok" onclick="brNoteSubmit()">Kaydet</button>
        </div>
    </div>
</div>

@if(isset($docPaginator) && $docPaginator->lastPage() > 1)
<div style="margin-bottom:10px;font-size:var(--tx-xs);color:var(--u-muted);">
    Toplam {{ $docPaginator->total() }} belge · Sayfa {{ $docPaginator->currentPage() }}/{{ $docPaginator->lastPage() }}
</div>
@endif

@foreach($grouped as $studentId => $docs)
<article class="panel" style="margin-bottom:14px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap;">
        <strong style="font-size:var(--tx-sm);">👤 {{ $studentId }}</strong>
        <span class="badge info">{{ $docs->count() }} belge</span>
        <a href="/senior/students/{{ $studentId }}" style="font-size:var(--tx-xs);color:#7c3aed;margin-left:auto;">360° Görünüm →</a>
    </div>
    <div id="docs-{{ Str::slug($studentId) }}">
        @foreach($docs as $doc)
        <div class="br-doc-row {{ $doc->status }}" data-doc-id="{{ $doc->id }}" data-status="{{ $doc->status }}">
            <div class="br-doc-info">
                <div class="br-doc-name" title="{{ $doc->original_file_name }}">{{ $doc->original_file_name }}</div>
                <div class="br-doc-meta">
                    @if($doc->category){{ $doc->category->name ?? '' }} · @endif
                    {{ $doc->created_at?->format('d.m.Y H:i') }}
                    @if($doc->review_note) · <em>{{ \Illuminate\Support\Str::limit((string)$doc->review_note, 50) }}</em>@endif
                </div>
            </div>
            <span class="badge {{ $doc->status === 'approved' ? 'ok' : ($doc->status === 'rejected' ? 'danger' : 'warn') }}" id="status-{{ $doc->id }}">{{ $doc->status }}</span>
            <div class="br-actions">
                <button class="btn ok"   onclick="brAction({{ $doc->id }}, 'approve')" title="Onayla (A)" id="btn-approve-{{ $doc->id }}">✓</button>
                <button class="btn warn" onclick="brAction({{ $doc->id }}, 'reject')"  title="Reddet (R)" id="btn-reject-{{ $doc->id }}">✕</button>
                <button class="btn alt"  onclick="brNoteOpen({{ $doc->id }})"          title="Not (N)"    id="btn-note-{{ $doc->id }}">📝</button>
            </div>
        </div>
        @endforeach
    </div>
</article>
@endforeach

@if(isset($docPaginator) && $docPaginator->hasPages())
<div style="margin-top:16px;">{{ $docPaginator->links() }}</div>
@endif
@endif

@push('scripts')
<script defer src="{{ Vite::asset('resources/js/senior-batch-review.js') }}"></script>
<script>
const BR_CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
</script>
@endpush
@endsection

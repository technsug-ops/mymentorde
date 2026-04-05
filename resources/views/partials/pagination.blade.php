@if($paginator->hasPages())
<div style="display:flex;gap:8px;align-items:center;padding:10px 12px;border-top:1px solid var(--u-line,#e5e9f0);">
    @if($paginator->onFirstPage())
        <span class="btn" style="opacity:0.45;cursor:default;">← Önceki</span>
    @else
        <a class="btn" href="{{ $paginator->previousPageUrl() }}">← Önceki</a>
    @endif
    <span class="muted" style="font-size:12px;">
        Sayfa {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        &nbsp;·&nbsp; {{ number_format($paginator->total()) }} kayıt
    </span>
    @if($paginator->hasMorePages())
        <a class="btn" href="{{ $paginator->nextPageUrl() }}">Sonraki →</a>
    @else
        <span class="btn" style="opacity:0.45;cursor:default;">Sonraki →</span>
    @endif
</div>
@endif

@props(['paginator'])
@if($paginator->hasPages())
<div style="display:flex;align-items:center;justify-content:center;gap:4px;padding:12px 0;flex-wrap:wrap;">
    @if($paginator->onFirstPage())<span class="btn alt" style="opacity:.4;pointer-events:none;padding:5px 10px;font-size:12px;">‹</span>@else<a class="btn alt" href="{{ $paginator->previousPageUrl() }}" style="padding:5px 10px;font-size:12px;">‹</a>@endif
    <span style="font-size:12px;color:var(--u-muted);padding:0 8px;">{{ $paginator->currentPage() }}/{{ $paginator->lastPage() }} ({{ $paginator->total() }})</span>
    @if($paginator->hasMorePages())<a class="btn alt" href="{{ $paginator->nextPageUrl() }}" style="padding:5px 10px;font-size:12px;">›</a>@else<span class="btn alt" style="opacity:.4;pointer-events:none;padding:5px 10px;font-size:12px;">›</span>@endif
</div>
@endif

@extends($layout)

@section('title', 'Sözleşmelerim')

@section('content')
<div style="max-width:760px;">

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="margin:0;font-size:20px;font-weight:700;">📄 Sözleşmelerim</h1>
</div>

@if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:6px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#166534;">
        {{ session('success') }}
    </div>
@endif

@if($contracts->isEmpty())
    <div class="card" style="padding:40px;text-align:center;color:var(--u-muted);">
        <div style="font-size:32px;margin-bottom:8px;">📋</div>
        <div style="font-size:14px;">Henüz size gönderilmiş bir sözleşme yok.</div>
    </div>
@else
    <div class="list">
        @foreach($contracts as $c)
        <div class="item" style="display:flex;align-items:center;gap:14px;padding:14px 16px;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:14px;font-weight:600;margin-bottom:3px;">{{ $c->title }}</div>
                <div style="font-size:12px;color:var(--u-muted);">
                    {{ $c->contract_no }}
                    @if($c->issued_at) · Gönderilme: {{ \Carbon\Carbon::parse($c->issued_at)->format('d.m.Y') }} @endif
                    @if($c->approved_at) · Onaylanma: {{ \Carbon\Carbon::parse($c->approved_at)->format('d.m.Y') }} @endif
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                <span class="badge {{ $c->statusBadge() }}">{{ $c->statusLabel() }}</span>
                @if($c->status === 'issued')
                    <span class="badge warn" style="font-size:11px;">⏳ İmza Bekliyor</span>
                @endif
                <a href="{{ route('my-contracts.show', $c) }}" class="btn" style="font-size:12px;padding:5px 12px;">Görüntüle</a>
            </div>
        </div>
        @endforeach
    </div>
@endif

</div>
@endsection

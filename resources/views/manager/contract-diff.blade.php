@extends('manager.layouts.app')
@section('title','Sözleşme Karşılaştırma')
@section('page_title', 'Sözleşme Karşılaştırma')
@section('content')
<div class="page-header">
    <h1>Sözleşme Karşılaştırma — {{ $guest->first_name }} {{ $guest->last_name }}</h1>
    <a href="{{ url('/manager/contract-template') }}" class="btn alt">← Geri</a>
</div>

@if($snapshots->isNotEmpty())
<div class="card" style="margin-bottom:16px">
    <h3>Snapshot Geçmişi</h3>
    <div class="list">
        @foreach($snapshots as $snap)
        <div class="item" style="gap:12px">
            <span class="badge info">v{{ $snap->snapshot_version }}</span>
            <span>{{ $snap->submitted_at ? \Carbon\Carbon::parse($snap->submitted_at)->format('d.m.Y H:i') : '—' }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="card">
    <h3>Metin Karşılaştırma</h3>
    @if(empty($diff))
        <p class="muted">Karşılaştırılacak iki versiyon yok. URL'ye <code>?v1=...&v2=...</code> parametreleri ekleyin.</p>
        <div style="margin-top:12px">
            <strong>Mevcut Snapshot:</strong>
            <pre style="white-space:pre-wrap;font-size:var(--tx-sm);background:#f8fafd;padding:12px;border-radius:6px;max-height:400px;overflow:auto">{{ $current ?: '(Boş)' }}</pre>
        </div>
    @else
        <div style="font-family:monospace;font-size:var(--tx-sm);line-height:1.7">
            @foreach($diff as $line)
                @if($line['type'] === 'added')
                    <div style="background:#e6f9f0;color:#1a6b3c;padding:2px 8px">+ {{ $line['text'] }}</div>
                @elseif($line['type'] === 'removed')
                    <div style="background:#fff0f0;color:#9b1c1c;padding:2px 8px">− {{ $line['text'] }}</div>
                @else
                    <div style="padding:2px 8px;color:#555">  {{ $line['text'] }}</div>
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection

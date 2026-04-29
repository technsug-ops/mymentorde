@extends('manager.layouts.app')

@section('title', 'Kupon Kullanımları')
@section('page_title', 'Kupon Kullanımları')
@section('page_subtitle', $filteredCode ? 'Filtre: ' . $filteredCode->code : 'Tüm kodların kullanım geçmişi')

@push('head')
<style>
.dc-table { width:100%; border-collapse: collapse; font-size: 13px; background: var(--u-card);
    border: 1px solid var(--u-line); border-radius: 10px; overflow: hidden; }
.dc-table th { padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 700;
    color: var(--u-muted); text-transform: uppercase; letter-spacing:.4px;
    border-bottom: 2px solid var(--u-line); background: var(--u-bg); }
.dc-table td { padding: 11px 12px; border-bottom: 1px solid var(--u-line); color: var(--u-text); }
.dc-code { font-family: monospace; font-weight: 700; }
.dc-meta { color: var(--u-muted); font-size: 11.5px; }
.dc-empty { padding: 30px 20px; text-align:center; color: var(--u-muted); }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <div style="margin-bottom:14px;">
        <a href="{{ route('manager.discount-codes.index') }}">← Kod listesine dön</a>
    </div>

    @if($redemptions->isEmpty())
        <div class="dc-empty">Henüz kupon kullanımı yok.</div>
    @else
        <table class="dc-table">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kod</th>
                    <th>Aday</th>
                    <th>Orijinal Tutar</th>
                    <th>İndirim</th>
                    <th>Net Tutar</th>
                </tr>
            </thead>
            <tbody>
            @foreach($redemptions as $r)
                <tr>
                    <td>{{ $r->redeemed_at->format('d.m.Y H:i') }}</td>
                    <td>
                        <span class="dc-code">{{ $r->discountCode?->code ?? '—' }}</span>
                    </td>
                    <td>
                        @if($r->guestApplication)
                            <strong>{{ trim(($r->guestApplication->first_name ?? '') . ' ' . ($r->guestApplication->last_name ?? '')) }}</strong>
                            <div class="dc-meta">{{ $r->guestApplication->email }}</div>
                        @else
                            <span class="dc-meta">#{{ $r->guest_application_id }}</span>
                        @endif
                    </td>
                    <td>{{ number_format((float) $r->original_amount_eur, 2, ',', '.') }} EUR</td>
                    <td style="color:#15803d;font-weight:700;">-{{ number_format((float) $r->discount_amount_eur, 2, ',', '.') }} EUR</td>
                    <td><strong>{{ number_format((float) $r->final_amount_eur, 2, ',', '.') }} EUR</strong></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:12px;">{{ $redemptions->links() }}</div>
    @endif
</div>
@endsection

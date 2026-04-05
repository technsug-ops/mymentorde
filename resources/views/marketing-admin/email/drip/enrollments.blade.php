@extends('marketing-admin.layouts.app')
@section('title', $sequence->name . ' — Kayıtlar')

@section('content')
<div class="page-header">
    <a href="/mktg-admin/email/drip-sequences/{{ $sequence->id }}" class="u-muted" style="font-size:var(--tx-sm);">← {{ $sequence->name }}</a>
    <h1 style="margin-top:4px;">Drip Kayıtları</h1>
</div>

<div class="list">
    @forelse($enrollments as $e)
    <div class="item">
        <div style="flex:1;">
            <div style="font-weight:500;">{{ $e->guest?->first_name }} {{ $e->guest?->last_name }} <span class="u-muted" style="font-size:var(--tx-xs);">{{ $e->guest?->email }}</span></div>
            <div class="u-muted" style="font-size:var(--tx-xs);">Adım: {{ $e->current_step }} · Sonraki: {{ $e->next_send_at?->diffForHumans() ?? '—' }}</div>
        </div>
        <span class="badge {{ match($e->status) { 'active'=>'info', 'completed'=>'ok', 'unsubscribed'=>'danger', default=>'pending' } }}">
            {{ $e->status }}
        </span>
    </div>
    @empty
    <div class="item"><span class="u-muted">Kayıt yok.</span></div>
    @endforelse
</div>
{{ $enrollments->links('partials.pagination') }}

<details class="card" style="margin-top:12px;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Drip Kayıtları</h3>
        <span class="det-chev">▼</span>
    </summary>
    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
        <li><strong>active:</strong> E-posta dizisinde ilerliyor — adım ve sonraki gönderim zamanı görünür</li>
        <li><strong>completed:</strong> Tüm adımları tamamladı — dönüşüm analizi için lead'i izle</li>
        <li><strong>unsubscribed:</strong> Abonelikten çıktı — tekrar iletişim kurma</li>
        <li>Çok fazla unsubscribe → e-posta içeriği veya sıklığı çok agresif</li>
    </ul>
</details>
@endsection

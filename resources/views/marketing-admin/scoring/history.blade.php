@extends('marketing-admin.layouts.app')

@section('title', 'Score Geçmişi')

@section('content')

<div class="card" style="margin-bottom:14px;">
    <div class="label" style="font-weight:600;font-size:var(--tx-base);">
        {{ $guest->first_name }} {{ $guest->last_name }}
        <span class="badge {{ in_array($guest->lead_score_tier, ['champion','sales_ready']) ? 'ok' : ($guest->lead_score_tier === 'hot' ? 'warn' : 'info') }}" style="margin-left:10px;">
            {{ $guest->lead_score_tier ?? 'cold' }}
        </span>
    </div>
    <div class="muted" style="margin-top:4px;">Güncel Puan: <strong>{{ $guest->lead_score }}</strong></div>
</div>

<div class="card">
    <div class="label" style="font-weight:600;margin-bottom:12px;">Puan Geçmişi</div>
    <div class="list">
        <div class="item" style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);border-bottom:1px solid var(--u-line);">
            <span style="flex:2;">Aksiyon</span>
            <span style="width:70px;text-align:right;">Puan</span>
            <span style="width:70px;text-align:right;">Öncesi</span>
            <span style="width:70px;text-align:right;">Sonrası</span>
            <span style="width:130px;text-align:right;">Tarih</span>
        </div>
        @forelse($logs as $log)
        <div class="item">
            <span style="flex:2;">
                <strong>{{ $log->action_code }}</strong>
                @if($log->tier_before && $log->tier_after && $log->tier_before !== $log->tier_after)
                <span class="badge warn" style="margin-left:6px;font-size:var(--tx-xs);">Tier değişti: {{ $log->tier_before }} → {{ $log->tier_after }}</span>
                @endif
            </span>
            <span style="width:70px;text-align:right;font-weight:600;color:{{ $log->points >= 0 ? 'var(--u-ok)' : 'var(--u-danger)' }}">
                {{ $log->points >= 0 ? '+' : '' }}{{ $log->points }}
            </span>
            <span style="width:70px;text-align:right;color:var(--u-muted);">{{ $log->score_before }}</span>
            <span style="width:70px;text-align:right;font-weight:600;">{{ $log->score_after }}</span>
            <span style="width:130px;text-align:right;font-size:var(--tx-xs);color:var(--u-muted);">{{ optional($log->created_at)->format('d.m.Y H:i') }}</span>
        </div>
        @empty
        <div class="item muted">Henüz puan kaydı yok.</div>
        @endforelse
    </div>
    <div style="margin-top:12px;">{{ $logs->links() }}</div>
</div>

<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Puan Geçmişi</h3>
        <span class="det-chev">▼</span>
    </summary>
    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
        <li>Her lead puanı değişikliğinin kaydı — hangi eylem puanı artırdı veya düşürdü</li>
        <li>Puan kaynağını izle: form doldurma, sayfa ziyareti, e-posta açma, dönüşüm vb.</li>
        <li>Düşen puan → lead soğuyor → re-engagement kampanyasına al</li>
        <li>Ani puan artışı → satış ekibine yönlendir (hot lead)</li>
    </ul>
</details>

@endsection

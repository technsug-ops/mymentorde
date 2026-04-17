@extends('manager.layouts.app')
@section('title', 'Tanıtım Popup Yönetimi')
@section('page_title', 'Tanıtım Popup Yönetimi')

@section('topbar-actions')
<a href="{{ route('manager.promo-popups.create') }}" class="btn ok" style="font-size:12px;padding:6px 16px;">+ Yeni Popup</a>
@endsection

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

@if($popups->isEmpty())
<div class="card" style="padding:40px;text-align:center;">
    <div style="font-size:48px;margin-bottom:12px;">📺</div>
    <div style="font-size:16px;font-weight:700;margin-bottom:6px;">Henüz popup tanımlanmamış</div>
    <div class="u-muted" style="font-size:13px;margin-bottom:16px;">Aday öğrenci, öğrenci veya danışman portallarında gösterilecek tanıtım videoları ekleyin.</div>
    <a href="{{ route('manager.promo-popups.create') }}" class="btn" style="background:#1e40af;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;">+ İlk Popup'ı Oluştur</a>
</div>
@else
<div style="display:grid;gap:12px;">
@foreach($popups as $p)
<div class="card" style="padding:16px;{{ !$p->is_active ? 'opacity:.5;' : '' }}display:grid;grid-template-columns:1fr auto;gap:16px;align-items:start;">
    <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <span style="font-size:16px;font-weight:800;color:var(--u-text);">{{ $p->title }}</span>
            @if($p->isCurrentlyActive())
            <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">✓ AKTİF</span>
            @elseif($p->is_active && $p->starts_at?->isFuture())
            <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">⏰ ZAMANLANMIŞ</span>
            @else
            <span style="background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">PASİF</span>
            @endif
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;font-size:12px;color:var(--u-muted);margin-bottom:8px;">
            <span>📺 {{ $p->video_type }}</span>
            <span>⏱ {{ $p->delay_seconds }}sn gecikme</span>
            <span>🔄 {{ \App\Models\PromoPopup::FREQUENCY_OPTIONS[$p->frequency] ?? $p->frequency }}</span>
            <span>⚡ Öncelik: {{ $p->priority }}</span>
            @if($p->starts_at)<span>📅 {{ $p->starts_at->format('d.m.Y') }} – {{ $p->ends_at?->format('d.m.Y') ?? '∞' }}</span>@endif
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px;">
            @foreach($p->target_roles ?? [] as $r)
            <span style="background:#eff6ff;color:#1e40af;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">{{ \App\Models\PromoPopup::ROLE_OPTIONS[$r] ?? $r }}</span>
            @endforeach
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            @foreach($p->target_pages ?? [] as $pg)
            <span style="background:#f3f4f6;color:#4b5563;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:600;">{{ \App\Models\PromoPopup::PAGE_OPTIONS[$pg] ?? $pg }}</span>
            @endforeach
        </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:6px;min-width:100px;">
        <form method="POST" action="{{ route('manager.promo-popups.toggle', $p) }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn {{ $p->is_active ? 'warn' : 'ok' }}" style="font-size:11px;padding:5px 12px;width:100%;">
                {{ $p->is_active ? '⏸ Pasif Yap' : '▶ Aktif Et' }}
            </button>
        </form>
        <a href="{{ route('manager.promo-popups.edit', $p) }}" class="btn alt" style="font-size:11px;padding:5px 12px;text-align:center;text-decoration:none;">✏️ Düzenle</a>
        <form method="POST" action="{{ route('manager.promo-popups.destroy', $p) }}" onsubmit="return confirm('Sil?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn warn" style="font-size:11px;padding:5px 12px;width:100%;">🗑 Sil</button>
        </form>
    </div>
</div>
@endforeach
</div>
@endif
@endsection

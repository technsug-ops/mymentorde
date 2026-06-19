@extends('platform.layouts.app')

@section('title', $broadcast->title . ' — Duyuru Detay')

@section('content')
    <div class="plat-page-header">
        <div>
            <h1 class="plat-page-title">
                <x-icon name="megaphone" size="22" /> {{ $broadcast->title }}
            </h1>
            <p class="plat-page-sub">
                <span class="plat-badge {{ $broadcast->statusBadgeClass() }}">{{ $broadcast->statusLabel() }}</span>
                &middot; Kanal: <strong>{{ $broadcast->channel === 'in_app' ? 'In-App' : ucfirst($broadcast->channel) }}</strong>
                &middot; Olusturan: {{ $broadcast->createdBy?->name ?? '-' }}
                &middot; {{ $broadcast->created_at->format('d.m.Y H:i') }}
            </p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('platform.broadcasts') }}" class="plat-btn plat-btn-ghost">
                <x-icon name="arrow-left" size="14" /> Liste
            </a>

            @if (in_array($broadcast->status, ['draft', 'scheduled'], true))
                <form method="POST" action="{{ route('platform.broadcasts.send', $broadcast->id) }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="plat-btn plat-btn-primary"
                            onclick="return confirm('Bu duyuru hedef alicilara gonderilecek. Emin misin?');">
                        <x-icon name="send" size="14" /> Simdi Gonder
                    </button>
                </form>
                <form method="POST" action="{{ route('platform.broadcasts.cancel', $broadcast->id) }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="plat-btn plat-btn-danger"
                            onclick="return confirm('Bu duyuru iptal edilecek. Emin misin?');">
                        <x-icon name="x-circle" size="14" /> Iptal
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Stats KPI'lar --}}
    <div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="users" size="12" /> Toplam Alici</div>
            <div class="plat-kpi-value">{{ number_format($stats['total']) }}</div>
            <div class="plat-kpi-sub">hedef sayisi</div>
        </div>
        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="send" size="12" /> Gonderilen</div>
            <div class="plat-kpi-value">{{ number_format($stats['sent']) }}</div>
            <div class="plat-kpi-sub">{{ $stats['failed'] }} basarisiz</div>
        </div>
        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="eye" size="12" /> Acilma</div>
            <div class="plat-kpi-value">{{ number_format($stats['opened']) }}</div>
            <div class="plat-kpi-sub">{{ $broadcast->openRate() }}% oran</div>
        </div>
        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="mouse-pointer-click" size="12" /> Tiklama</div>
            <div class="plat-kpi-value">{{ number_format($stats['clicked']) }}</div>
            <div class="plat-kpi-sub">{{ $broadcast->clickRate() }}% oran</div>
        </div>
    </div>

    <div class="plat-grid plat-grid-2" style="align-items:start;">
        {{-- SOL: Icerik --}}
        <div class="plat-card">
            <h3 class="plat-card-title"><x-icon name="pencil" size="14" /> Icerik</h3>
            <div style="background:var(--plat-bg); border:1px solid var(--plat-border); border-radius:8px; padding:18px 20px;">
                <h2 style="color:var(--plat-accent-2); margin:0 0 12px; font-size:18px;">
                    {{ $broadcast->title }}
                </h2>
                <div style="font-size:13px; line-height:1.6; color:var(--plat-text); white-space:pre-wrap;">{{ $broadcast->body }}</div>
                @if ($broadcast->cta_label && $broadcast->cta_url)
                    <div style="margin-top:18px;">
                        <a href="{{ $broadcast->cta_url }}" target="_blank" rel="noopener"
                           style="display:inline-block;background:linear-gradient(135deg,var(--plat-accent),var(--plat-accent-2));color:#fff;padding:9px 20px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;">
                            {{ $broadcast->cta_label }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- SAG: Hedefleme + Zamanlama --}}
        <div class="plat-card">
            <h3 class="plat-card-title"><x-icon name="target" size="14" /> Hedefleme</h3>
            <div style="font-size:13px; color:var(--plat-text); line-height:1.9;">
                <div><strong>Segment:</strong> {{ ucfirst($broadcast->target_segment) }}</div>
                @if (is_array($broadcast->target_tiers) && $broadcast->target_tiers)
                    <div><strong>Tier filtresi:</strong>
                        @foreach ($broadcast->target_tiers as $t)
                            <span class="plat-badge plat-badge-{{ $t }}">{{ $t }}</span>
                        @endforeach
                    </div>
                @endif
                @if (is_array($broadcast->target_company_ids) && $broadcast->target_company_ids)
                    <div><strong>Specific company ID'leri:</strong> {{ implode(', ', $broadcast->target_company_ids) }}</div>
                @endif
                <div><strong>Kanal:</strong> {{ $broadcast->channel === 'in_app' ? 'In-App' : ucfirst($broadcast->channel) }}</div>
            </div>

            <h3 class="plat-card-title" style="margin-top:20px;"><x-icon name="clock" size="14" /> Zaman</h3>
            <div style="font-size:13px; color:var(--plat-text); line-height:1.9;">
                <div><strong>Olusturma:</strong> {{ $broadcast->created_at->format('d.m.Y H:i') }}</div>
                @if ($broadcast->scheduled_for)
                    <div><strong>Zamanlanan:</strong> {{ $broadcast->scheduled_for->format('d.m.Y H:i') }}</div>
                @endif
                @if ($broadcast->sent_at)
                    <div><strong>Gonderim:</strong> {{ $broadcast->sent_at->format('d.m.Y H:i') }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Recipient tablosu --}}
    <div class="plat-card" style="padding:0; margin-top:20px;">
        <div style="padding:14px 20px; border-bottom:1px solid var(--plat-border);">
            <h3 class="plat-card-title" style="margin:0;">
                <x-icon name="users" size="14" /> Alici Listesi
                <span class="plat-card-sub" style="margin-left:8px;">({{ $recipients->total() }})</span>
            </h3>
        </div>

        @if ($recipients->isEmpty())
            <div style="padding:40px 20px; text-align:center; color:var(--plat-muted);">
                Henuz hicbir alici yok. Duyuru taslak/zamanlanmis durumda olabilir.
            </div>
        @else
            <table class="plat-table">
                <thead>
                <tr>
                    <th>Company</th>
                    <th>Durum</th>
                    <th>Gonderim</th>
                    <th>Acilma</th>
                    <th>Tiklama</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($recipients as $rec)
                    <tr>
                        <td>{{ $rec->company?->name ?? '—' }}</td>
                        <td>
                            @php
                                $cls = match ($rec->status) {
                                    'clicked' => 'plat-badge-premium',
                                    'opened'  => 'plat-badge-active',
                                    'sent'    => 'plat-badge-trial',
                                    'failed'  => 'plat-badge-inactive',
                                    default   => 'plat-badge-inactive',
                                };
                            @endphp
                            <span class="plat-badge {{ $cls }}">{{ ucfirst($rec->status) }}</span>
                            @if ($rec->error)
                                <div style="font-size:11px; color:var(--plat-danger); margin-top:2px;">
                                    {{ \Illuminate\Support\Str::limit($rec->error, 60) }}
                                </div>
                            @endif
                        </td>
                        <td style="font-size:12px;">{{ $rec->delivered_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        <td style="font-size:12px;">{{ $rec->opened_at?->format('d.m.Y H:i')    ?? '—' }}</td>
                        <td style="font-size:12px;">{{ $rec->clicked_at?->format('d.m.Y H:i')   ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div style="padding:14px 20px;">
                {{ $recipients->links() }}
            </div>
        @endif
    </div>
@endsection
